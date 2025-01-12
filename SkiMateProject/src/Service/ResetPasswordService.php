<?php

namespace App\Service;

use App\Entity\Users;
use App\Entity\PasswordResetCode;
use App\Repository\PasswordResetCodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordService
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        private PasswordResetCodeRepository $codeRepository
    ) {}

    /**
     * Génère un code à 6 chiffres + l’envoie par e-mail à l’utilisateur.
     */
    public function generateAndSendResetCode(Users $user): void
    {
        // Générer un code 6 chiffres
        $codeValue = str_pad((string) random_int(0,999999), 6, '0', STR_PAD_LEFT);

        // Créer l’entité
        $resetCode = new PasswordResetCode();
        $resetCode->setCode($codeValue)
            ->setUser($user)
            ->setExpireAt(new \DateTimeImmutable('+10 minutes'));

        $this->em->persist($resetCode);
        $this->em->flush();

        // Envoi par e-mail
        $email = (new Email())
            ->from('noreply@votre-domaine.com')
            ->to($user->getEmail())
            ->subject('Votre code de réinitialisation')
            ->html(sprintf(
                '<p>Bonjour %s,</p>
                 <p>Votre code de réinitialisation est : <strong>%s</strong>.</p>
                 <p>Il expirera dans 10 minutes.</p>',
                htmlspecialchars($user->getEmail()),
                $codeValue
            ));
        $this->mailer->send($email);
    }

    /**
     * Vérifie le code saisi, modifie le mot de passe si ok.
     * Retourne true si succès, false sinon.
     */
    public function resetPassword(string $codeValue, string $newPlainPassword): bool
    {
        // Chercher le code en BDD
        $resetCode = $this->codeRepository->findOneBy(['code' => $codeValue]);
        if (!$resetCode) {
            return false; // code inexistant
        }
        if ($resetCode->isExpired()) {
            return false; // code expiré
        }

        // Récupérer l’utilisateur
        $user = $resetCode->getUser();
        if (!$user) {
            return false;
        }

        // Hachage
        $hashed = $this->passwordHasher->hashPassword($user, $newPlainPassword);
        $user->setPassword($hashed);

        // Supprimer le code (on le consomme)
        $this->em->remove($resetCode);

        $this->em->flush();
        return true;
    }
}

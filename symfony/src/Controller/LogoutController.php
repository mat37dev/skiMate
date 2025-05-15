<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\RefreshTokenRepository;
use App\Repository\UsersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class LogoutController extends AbstractController
{
    private RefreshTokenRepository $refreshTokenRepository;
    private EntityManagerInterface $entityManager;
    private UsersRepository $userRepository;

    public function __construct(RefreshTokenRepository $refreshTokenRepository, EntityManagerInterface $entityManager, UsersRepository $userRepository)
    {
        $this->refreshTokenRepository = $refreshTokenRepository;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        $refreshTokens = $this->refreshTokenRepository->findBy(['username' => $user->getUserIdentifier()]);

        foreach ($refreshTokens as $refreshToken) {
            $this->entityManager->remove($refreshToken);
        }
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Déconnexion réussie'], 200);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_user_login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, UserRepository $userRepository, JWTTokenManagerInterface $JWTTokenManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'];
        $password = $data['password'];

        // Rechercher l'utilisateur par email
        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->json([
                'message' => 'Identifiants invalides',
            ], 401);
        }

        // Vérifier si le mot de passe est correct
        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json([
                'message' => 'Identifiants invalides',
            ], 401);
        }

        // Générer le JWT
        $token = $JWTTokenManager->create($user);

        return $this->json([
            'token' => $token,
            'message' => 'Connexion Réussie',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRole(),
            ]
        ], 200);

    }
}

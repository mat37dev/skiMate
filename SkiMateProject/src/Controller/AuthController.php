<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UsersRepository;
use App\Service\ResetPasswordService;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UsersRepository $usersRepo,
        ResetPasswordService $resetService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['message' => 'Email manquant'], Response::HTTP_BAD_REQUEST);
        }

        // Trouver l'utilisateur
        $user = $usersRepo->findOneBy(['email' => $email]);
        if ($user) {
            $resetService->generateAndSendResetCode($user);
        }
        // On ne révèle pas si l’utilisateur existe ou non
        return new JsonResponse([
            'message' => 'Si un compte existe pour cet email, un code a été envoyé.'
        ]);
    }

    #[Route('/forgot-password/reset', name: 'api_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        ResetPasswordService $resetService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;
        $newPassword = $data['newPassword'] ?? null;

        if (!$code || !$newPassword) {
            return new JsonResponse(['message' => 'Code ou mot de passe manquant'], Response::HTTP_BAD_REQUEST);
        }

        $success = $resetService->resetPassword($code, $newPassword);
        if ($success) {
            return new JsonResponse(['message' => 'Mot de passe réinitialisé avec succès']);
        } else {
            return new JsonResponse(['message' => 'Code invalide ou expiré'], 400);
        }
    }
}

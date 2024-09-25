<?php

namespace App\Security;

use App\Repository\UsersRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private UsersRepository $usersRepository;
    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        $email = $data['email'];
        $user = $this->usersRepository->findOneBy(['email' => $email]);
        //$jwtToken = $this->generateJwtToken($user);
        return new JsonResponse([
            'token' => $token,
            'message' => 'Connexion Réussie',
            'user' => [
                'uuid' => $user->getId(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }


}

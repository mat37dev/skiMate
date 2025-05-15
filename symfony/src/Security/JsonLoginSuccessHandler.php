<?php

namespace App\Security;

use App\Entity\RefreshToken;
use App\Repository\RefreshTokenRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class JsonLoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private JWTTokenManagerInterface $jwtManager;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private RefreshTokenRepository $refreshTokenRepository;

    public function __construct(JWTTokenManagerInterface $jwtManager, RefreshTokenManagerInterface $refreshTokenManager,
                                RefreshTokenRepository $refreshTokenRepository)
    {
        $this->jwtManager = $jwtManager;
        $this->refreshTokenManager = $refreshTokenManager;
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();
        $jwt = $this->jwtManager->create($user);

        $existingRefreshToken = $this->refreshTokenRepository->findBy(['username' => $user->getUserIdentifier()]);
        if($existingRefreshToken) {
            foreach ($existingRefreshToken as $refreshToken) {
                $this->refreshTokenManager->delete($refreshToken);
            }
        }

        $refreshToken = new RefreshToken();
        $refreshToken->setRefreshToken(bin2hex(random_bytes(32)));
        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setValid((new \DateTime())->modify(sprintf('+%d seconds', (int) $_ENV['JWT_REFRESH_TOKEN_TTL'])));
        $this->refreshTokenManager->save($refreshToken);

        return new JsonResponse([
            'token' => $jwt,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'message' => 'Connexion Réussie',
            'user' => [
                'email' => $user->getUserIdentifier(),
                'roles' => $user->getRoles(),
            ],
        ]);
    }
}

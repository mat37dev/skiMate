<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SessionRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;


#[Route('/api/profile')]
class ProfileController extends AbstractController
{
    private SessionRepository $sessionRepository;
    private UsersRepository $userRepository;

    public function __construct(UsersRepository $userRepository, SessionRepository $sessionRepository)
    {
        $this->sessionRepository = $sessionRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/', name: 'app_profile', methods: ['GET'])]
    public function index(SerializerInterface $serializer): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        $sessions = $this->sessionRepository->findSessionsByUser($user);

        $userSerialize = json_decode($serializer->serialize($user, 'json'), true);
        unset($userSerialize['password']);
        $userData = [
            "user" => $userSerialize,
            "sessions" => json_decode($serializer->serialize($sessions, 'json'),true)
        ];


        return new JsonResponse($userData);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Session;
use App\Repository\SessionRepository;
use App\Repository\UsersRepository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;


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
            "sessions" => json_decode($serializer->serialize($sessions, 'json'), true)
        ];


        return new JsonResponse($userData);
    }

    #[Route('/session/new', name: 'app_add_session_profile', methods: ['POST'])]
    public function addSession(Request $request, SessionRepository $sessionRepository, ValidatorInterface $validator): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        if (!isset($data['duree'], $data['distance'], $data['date'])) {
            return new JsonResponse(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }

        $duree = filter_var($data['duree'], FILTER_VALIDATE_INT);
        $distance = filter_var($data['distance'], FILTER_VALIDATE_FLOAT);
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
        if ($duree === false || $distance === false || !$date) {
            return new JsonResponse(['message' => 'Données invalides'], Response::HTTP_BAD_REQUEST);
        }


        $session = new Session();
        $session->setUser($user);
        $session->setDuree($duree);
        $session->setDistance($distance);
        $session->setDate($date);

        $errors = $validator->validate($session);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }

        $sessionRepository->save($session);
        return new JsonResponse(['message' => 'Opération réussie'], Response::HTTP_CREATED);
    }

    #[Route('/session/delete', name: 'app_delete_session_profile', methods: ['POST'])]
    public function deleteSession(Request $request, SessionRepository $sessionsRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['sessionId'])) {
            return new JsonResponse(['message' => 'ID de session manquant'], Response::HTTP_BAD_REQUEST);
        }
        $sessionId = $data['sessionId'];
        $session = $sessionsRepository->find($sessionId);
        if (!$session) {
            return new JsonResponse(['message' => 'Session non trouvée'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($session);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Session supprimée avec succès'], Response::HTTP_OK);
    }
}

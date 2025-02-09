<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Station;
use App\Entity\Session;
use App\Repository\SessionRepository;
use App\Repository\SkiLevelRepository;
use App\Repository\SkiPreferenceRepository;
use App\Repository\UsersRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
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
    public function index(SerializerInterface $serializer, DocumentManager $documentManager): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $this->getUser()->getUserIdentifier()]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
        $sessions = $this->sessionRepository->findSessionsByUser($user);

        $userSerialize = json_decode($serializer->serialize($user, 'json'), true);
        unset($userSerialize['password']);
        unset($userSerialize['userIdentifier']);

//        $osmId = $user->getOsmId();
        $stationName = null;
//        if ($osmId) {
//            $stationRepo = $documentManager->getRepository(Station::class);
//            $station = $stationRepo->findOneBy(['osmId' => $osmId]);
//            if ($station) {
//                $stationName = $station->getName();
//            }
//        }
        $userData = [
            "user" => $userSerialize,
            "sessions" => json_decode($serializer->serialize($sessions, 'json'), true),
            'stationName' => $stationName
        ];

        return new JsonResponse($userData);
    }

    #[Route('/session/new', name: 'app_add_session_profile', methods: ['POST'])]
    public function addSession(Request $request, SessionRepository $sessionRepository, ValidatorInterface $validator): JsonResponse
    {
        $user = $this->userRepository->findOneBy(['id' => $this->getUser()->getUserIdentifier()]);
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

    #[Route('/get-preference-list', name: 'app_profile_preference_list', methods: ['GET'])]
    public function getSkiLevel(SkiLevelRepository $skiLevelRepository,SkiPreferenceRepository $skiPreferenceRepository): JsonResponse
    {
        $skiLevelList = $skiLevelRepository->findAll();
        $skiPreferenceList = $skiPreferenceRepository->findAll();
        $data = [
            "skiLevelList" => $skiLevelList,
            "skiPreferenceList" => $skiPreferenceList
        ];
        return $this->json($data);
    }

    #[Route('/user-edit', name: 'app_user_edit', methods: ['POST'])]
    public function profileEdit(Request $request, EntityManagerInterface $em, ValidatorInterface $validator, UsersRepository $userRepository,
        SkiLevelRepository $skiLevelRepository, SkiPreferenceRepository $skiPreferenceRepository, DocumentManager $documentManager): JsonResponse {
        $user = $userRepository->findOneBy(['id' => $this->getUser()->getUserIdentifier()]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $lastname = $data['lastname'] ?? null;
        if ($lastname !== null) {
            $user->setLastname($lastname);
        }

        $firstname = $data['firstname'] ?? null;
        if ($firstname !== null) {
            $user->setFirstname($firstname);
        }

        $email = $data['email'] ?? null;
        if ($email !== null) {
            $user->setEmail($email);
        }

        $phoneNumber = $data['phoneNumber'] ?? null;
        if ($phoneNumber !== null) {
            $user->setPhoneNumber($phoneNumber);
        }

        $skiLevel = $data['skiLevel'] ?? null;
        if ($skiLevel !== null) {
            $skiLevel = $skiLevelRepository->findOneBy(["name"=>$skiLevel]);
            if (!$skiLevel) {
                return $this->json(['error' => 'niveau invalide.'], 400);
            }
            $user->setSkiLevel($skiLevel);
        }

        $skiPreference= $data['skiPreference'] ?? null;
        if ($skiPreference !== null) {
            $skiPreference = $skiPreferenceRepository->findOneBy(["name"=>$skiPreference]);
            if (!$skiPreference) {
                return $this->json(['error' => 'preference invalide.'], 400);
            }
            $user->setSkiPreference($skiPreference);
        }

        $osmId = $data['osmId'] ?? null;
        if ($osmId !== null) {
            $stationRepository = $documentManager->getRepository(Station::class);
            $station = $stationRepository->findOneBy(['osmId' => $osmId]);
            if (!$station) {
                return $this->json(['error' => 'Station non trouvée.'], 400);
            }
            $user->setOsmId($osmId);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsArray = [];
            foreach ($errors as $error) {
                $errorsArray[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorsArray], 400);
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'lastname' => $user->getLastname(),
                'firstname' => $user->getFirstname(),
                'email' => $user->getEmail(),
                'phoneNumber' => $user->getPhoneNumber(),
                'skiLevel' => $user->getSkiLevel()?->getName(),
                'skiPreference' => $user->getSkiPreference()?->getName(),
//                'osmId' => $user->getOsmId(),
            ]
        ]);
    }

    #[Route('/user-remove-station', name: 'app_user_remove_station', methods: ['GET'])]
    public function removeUserStation(UsersRepository $userRepository, EntityManagerInterface $em): JsonResponse
    {
        $user = $userRepository->findOneBy(['id' => $this->getUser()->getUserIdentifier()]);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $user->setOsmId(null);
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Station désélectionnée avec succès',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
//                'osmId' => $user->getOsmId(), // désormais null
            ]
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Users;
use App\Repository\RolesRepository;
use App\Repository\SkiLevelRepository;
use App\Repository\SkiPreferenceRepository;
use App\Repository\UsersRepository;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    #[Route('/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
    public function listUsers(
        Request $request,
        UsersRepository $usersRepository,
        SerializerInterface $serializer
    ): JsonResponse {
        $search = $request->query->get('search'); // ex: "Crosnier Mathieu"
        $role = $request->query->get('role');     // ex: "ROLE_ADMIN"

        // Appel du repository
        $users = $usersRepository->searchUsers($search, $role);

        $userData = [];
        foreach ($users as $user) {
            $userSerialize = json_decode($serializer->serialize($user, 'json'), true);
            unset($userSerialize['password']);
            $userData[] = $userSerialize;
        }
        return $this->json($userData, 200);
    }


    #[Route('/utilisateurs/add', name: "app_admin_user_add", methods: ['POST'])]
    public function addUser(Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator,
                            UsersRepository $usersRepository, RolesRepository $rolesRepository): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        $user = new Users();
        if(isset($data['email'])){
            $user->setEmail($data['email']);
        }
        if(isset($data['password'])){
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $data['password']
                ));
        }
        if(isset($data['roles'])){
            foreach ($data['roles'] as $role) {
                $user->addRole($rolesRepository->findOneBy(['name' => $role]));
            }
        }

        if(isset($data['firstName'])){
            $user->setFirstName($data['firstName']);
        }

        if(isset($data['lastName'])){
            $user->setFirstName($data['lastName']);
        }

        if(isset($data['phoneNumber'])){
            $user->setFirstName($data['phoneNumber']);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }
        else{
            $usersRepository->save($user);
            return new JsonResponse(['message' => ['Utilisateur créé avec succès']], Response::HTTP_CREATED);
        }
    }

    #[Route('/utilisateurs/edit', name: 'app_admin_user_edit', methods: ['POST'])]
    public function editAdminUser(Request $request, UsersRepository $usersRepository,RolesRepository $rolesRepository,ValidatorInterface $validator,
                             SkiLevelRepository $skiLevelRepository, SkiPreferenceRepository $skiPreferenceRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['id'])){
            $user = $usersRepository->find($data['id']);
        }
        else{
            return new JsonResponse(['errors' => ["L'identifiant de l'utilisateur doit être renseigné."]], Response::HTTP_BAD_REQUEST);
        }

        if (!$user) {
            return new JsonResponse(['errors' => ['Utilisateur non trouvé']], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['firstName'])) {
            $user->setFirstName($data['firstName']);
        }
        if (isset($data['lastName'])) {
            $user->setLastName($data['lastName']);
        }
        if (isset($data['phoneNumber'])) {
            $user->setPhoneNumber($data['phoneNumber']);
        }
        if (isset($data['roles'])) {
            foreach ($user->getRoles() as $role) {
                $user->removeRole($rolesRepository->findOneBy(['name' => $role]));
            }
            foreach ($data['roles'] as $roleName) {
                $role = $rolesRepository->findOneBy(['name' => $roleName]);
                if ($role) {
                    $user->addRole($role);
                }
            }
            if (isset($data['skiLevel'])){
                $skiLevel = $skiLevelRepository->findOneBy(['name' => $data['skiLevel']]);
                $user->setSkiLevel($skiLevel);
            }
            if(isset($data['skiPreference'])){
                $skiPreference = $skiPreferenceRepository->findOneBy(['name' => $data['skiPreference']]);
                $user->setSkiPreference($skiPreference);
            }
        }
        else{
            return new JsonResponse(['errors' => ["Vous devez renseigner au moins un rôle."]], Response::HTTP_BAD_REQUEST);
        }

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }
        $usersRepository->save($user);

        return new JsonResponse(['message' => ['Utilisateur mis à jour avec succès']], Response::HTTP_OK);
    }

    #[Route('/roles/liste', name: 'app_admin_roles_liste', methods: ['GET'])]
    public function  getRoles(RolesRepository $rolesRepository): JsonResponse{
        return $this->json($rolesRepository->findAll(), Response::HTTP_OK);
    }
}

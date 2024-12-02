<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Statistics;
use App\Entity\Users;
use App\Repository\RolesRepository;
use App\Repository\UsersRepository;
use Symfony\Component\Uid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    #[Route('/utilisateurs', name: 'app_users')]
    public function listUsers(UsersRepository $usersRepository): JsonResponse
    {
        $users = $usersRepository->findAll();
        $userData = [];
        foreach ($users as $user) {
            $userData[] = [
                'id' => $user->getId(),
                'nom' => $user->getFirstname(),
                'prenom' => $user->getLastname(),
                'email' => $user->getEmail(),
                'roles' => $user->getRoles(),
                'password' => $user->getPassword(),
                'phoneNumber' => $user->getPhoneNumber(),
            ];
        }
        return $this->json($userData, 200);
    }

    #[Route('/utilisateur/add', name: "app_user_add", methods: ['POST'])]
    public function addUser(Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, UsersRepository $usersRepository, RolesRepository $rolesRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new Users();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $data['password']
            ));
        $user->setPhoneNumber($data['phoneNumber']);
        $user->addRole($rolesRepository->findOneBy(['name' => $data['roles']]));
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[$error->getPropertyPath()] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }
        else{
            $usersRepository->save($user);
            return new JsonResponse(['message' => 'Utilisateur créé avec succès'], Response::HTTP_CREATED);
        }
    }

    #[Route('/utilisateur/edit/{id}', name: 'app_user_edit', methods: ['PUT'])]
    public function editUser(Request $request, UserPasswordHasherInterface $passwordHasher,UsersRepository $usersRepository,RolesRepository $rolesRepository,ValidatorInterface $validator, Uuid $id): JsonResponse
    {
        $user = $usersRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
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
                $user->removeRole($role);
            }
            foreach ($data['roles'] as $roleName) {
                $role = $rolesRepository->findOneBy(['name' => $roleName]);
                if ($role) {
                    $user->addRole($role);
                }
            }
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

        return new JsonResponse(['message' => 'Utilisateur mis à jour avec succès'], Response::HTTP_OK);
    }
}

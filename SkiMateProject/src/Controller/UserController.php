<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/admin')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator
    )
    {
    }
    #[Route('/utilisateurs', name: 'app_users')]
    public function listUsers(): JsonResponse
    {
        $users= $this->userRepository->findAll();
        $userData = [];
        foreach($users as $user){
            $userData[]=[
                'id'=>$user->getId(),
                'nom'=>$user->getFirstname(),
                'prenom'=>$user->getLastname(),
                'email'=>$user->getEmail(),
                'roles'=>$user->getRole(),
                'password'=>$user->getPassword(),
                'phoneNumber'=>$user->getPhoneNumber(),
                'skiPreference'=>$user->getSkiPreference(),
                'statistic'=>$user->getStatistic()
            ];
        }
        return $this->json($userData, 200);
    }
    #[Route('/utilisateur/{id}', name: 'app_user_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function showUser(int $id):JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user){
            throw $this->createNotFoundException('user not found');
        }

        $userData = [
            "id" => $user->getId(),
            'nom' => $user->getFirstname(),
            'prenom' => $user->getLastname(),
            'email' => $user->getEmail(),
            'roles' => $user->getRole(),
            'password' => $user->getPassword(),
            'phoneNumber' => $user->getPhoneNumber(),
            'skiPreference' => $user->getSkiPreference(),
            'statistic' => $user->getStatistic()
        ];
        return $this->json($userData, 200);
    }

    #[Route('/utilisateur/add', name:"app_user_add", methods: ['POST'])]
    public function addUser(Request $request, RoleRepository $role, UserPasswordHasherInterface $passwordHasher):JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $roleAdmin = $role->findOneBy(['role' => 'ROLE_ADMIN']);

        $user = new User();
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $password = $data['password'];
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setPhoneNumber($data['phoneNumber']);
        $user->setRole($roleAdmin);

        $errors = $this->validator->validate($user);
        if(count($errors)>0){
            $errorsMessages=[];
            foreach($errors as $error){
                $errorsMessages[]=[
                    'field' => $error->getPropertyPath(),
                    'message'=>$error->getMessage(),
                ];
            }
            return $this->json([
                'errors' => $errorsMessages,
            ]);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'id'=>$user->getId(),
            'prenom'=>$user->getFirstname(),
            'nom'=>$user->getLastname(),
            'email'=>$user->getEmail(),
            'password'=>$user->getPassword(),
            'phoneNumber'=>$user->getPhoneNumber(),
            'message'=> 'user successfully added',

        ], 200);
    }

    #[Route('/utilisateur/edit/{id}', name:"app_user_edit", requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function editUser(Request $request, UserPasswordHasherInterface $passwordHasher, int $id):JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->find($id);

        if (!$user){
            throw $this->createNotFoundException('user not found');
        };
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setEmail($data['email']);
        $password = $data['password'];
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setPhoneNumber($data['phoneNumber']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'id'=>$user->getId(),
            'prenom'=>$user->getFirstname(),
            'nom'=>$user->getLastname(),
            'email'=>$user->getEmail(),
            'password'=>$user->getPassword(),
            'phoneNumber'=>$user->getPhoneNumber(),
        ], 200);
    }

    #[Route('/utilisateur/delete/{id}', name:"app_user_delete", requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteUser(int $id):JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user){
            throw $this->createNotFoundException('user not found');
        }
            $this->entityManager->remove($user);
            $this->entityManager->flush();

        return $this->json([
            'user deleted'
        ], 200);
    }


}
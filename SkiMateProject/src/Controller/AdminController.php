<?php

declare(strict_types=1);

namespace App\Controller;

use App\Document\Station;
use App\Entity\Users;
use App\Repository\RolesRepository;
use App\Repository\SkiLevelRepository;
use App\Repository\SkiPreferenceRepository;
use App\Repository\UsersRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
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
    private RolesRepository $rolesRepository;
    private SkiLevelRepository $skiLevelRepository;
    private SkiPreferenceRepository $skiPreferenceRepository;
    private UserPasswordHasherInterface $passwordHasher;

    /**
     * @param RolesRepository $rolesRepository
     * @param SkiLevelRepository $skiLevelRepository
     * @param SkiPreferenceRepository $skiPreferenceRepository
     * @param UserPasswordHasherInterface $passwordHasher
     */
    public function __construct(RolesRepository $rolesRepository, SkiLevelRepository $skiLevelRepository,
                                SkiPreferenceRepository $skiPreferenceRepository, UserPasswordHasherInterface $passwordHasher)
    {
        $this->rolesRepository = $rolesRepository;
        $this->skiLevelRepository = $skiLevelRepository;
        $this->skiPreferenceRepository = $skiPreferenceRepository;
        $this->passwordHasher = $passwordHasher;
    }


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
    public function addUser(Request $request, ValidatorInterface $validator, UsersRepository $usersRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $user = new Users();

        if(!isset($data["password"])){
            return new JsonResponse(['errors' => ["Le champ 'Mot de Passe' ne peut pas être vide."]], Response::HTTP_BAD_REQUEST);
        }

        $this->userEdit($user, $data);

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
    public function editAdminUser(Request $request, UsersRepository $usersRepository,ValidatorInterface $validator): JsonResponse
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

        foreach ($user->getRoles() as $role) {
            $user->removeRole($this->rolesRepository->findOneBy(['name' => $role]));
        }
        $user = $this->userEdit($user, $data);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorsList = [];
            foreach ($errors as $error) {
                $errorsList[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorsList], Response::HTTP_BAD_REQUEST);
        }
        $usersRepository->save($user);

        return new JsonResponse(['message' => ['Utilisateur ('.$user->getLastname().' '.$user->getFirstname().') mis à jour avec succès']],
            Response::HTTP_OK);
    }

    private function userEdit(Users $user, $data): Users{
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
        if(isset($data['roles'])){
            foreach ($data['roles'] as $roleName) {
                $roleEntity = $this->rolesRepository->findOneBy(['name' => $roleName]);
                if ($roleEntity) {
                    $user->addRole($roleEntity);
                }
            }
        }
        if (isset($data['skiLevel'])){
            $skiLevel = $this->skiLevelRepository->findOneBy(['name' => $data['skiLevel']]);
            $user->setSkiLevel($skiLevel);
        }
        if(isset($data['skiPreference'])){
            $skiPreference = $this->skiPreferenceRepository->findOneBy(['name' => $data['skiPreference']]);
            $user->setSkiPreference($skiPreference);
        }
        if(isset($data['password'])){
            $user->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $data['password']
                ));
        }
        return $user;
    }

    #[Route('/utilisateurs/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function deleteUsers(Request $request, UsersRepository $usersRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        foreach ($data['id'] as $userId) {
            $user = $usersRepository->find($userId);
            if($user){
                if($user->getUserIdentifier() == $this->getUser()->getUserIdentifier()){
                    return new JsonResponse(['errors' => ["Vous ne pouvez pas vous supprimez vous même."]], Response::HTTP_BAD_REQUEST);
                }
                else{
                    $entityManager->remove($user);
                    $entityManager->flush();
                }
            }
        }
        return new JsonResponse(['message' => ['Utilisateur(s) supprimé(s) avec succès']], Response::HTTP_OK);
    }

    #[Route('/roles/liste', name: 'app_admin_roles_liste', methods: ['GET'])]
    public function  getRoles(RolesRepository $rolesRepository): JsonResponse{
        return $this->json($rolesRepository->findAll(), Response::HTTP_OK);
    }
}

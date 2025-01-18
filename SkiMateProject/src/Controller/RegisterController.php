<?php
namespace App\Controller;


use App\Entity\Users;
use App\Repository\RolesRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class RegisterController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function index(Request $request, UserPasswordHasherInterface $passwordHasher, ValidatorInterface $validator, UsersRepository $usersRepository, RolesRepository $rolesRepository): JsonResponse
    {

        $data = json_decode($request->getContent(), true);
        $password = $data['password'];

        if($password !== $data["confirmPassword"]){
            return new JsonResponse(['errors'=>'les mots de passe ne correspondent pas'], Response::HTTP_BAD_REQUEST);
        }
        else if(!$this->isPasswordValid($password)) {
            return new JsonResponse(['message' => 'Le mot de passe doit contenir au moins 8 caractères, 
            une majuscule, une minuscule, un chiffre et un caractère spécial (@$!%*?&_).'], Response::HTTP_BAD_REQUEST);
        }

        $user = new Users();
        $user->setEmail($data['email']);
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setPassword(
            $passwordHasher->hashPassword(
                $user,
                $password
            ));
        $user->setPhoneNumber($data['phoneNumber']);
        $user->addRole($rolesRepository->findOneBy(['name' => 'ROLE_USER']));
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

    public function isPasswordValid(string $password): bool
    {
        $passwordRegex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&_])[A-Za-z\d@$!%*?&_]{8,}$/';
        return preg_match($passwordRegex, $password) === 1;
    }
}

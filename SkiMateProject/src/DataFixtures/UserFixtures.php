<?php

namespace App\DataFixtures;


use App\Entity\Roles;

use App\Entity\Session;
use App\Entity\Users;
use App\Repository\UsersRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher, UsersRepository $usersRepository)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $roleUser = new Roles();
        $roleUser->setName("ROLE_USER");
        $manager->persist($roleUser);

        $roleAdmin = new Roles();
        $roleAdmin->setName('ROLE_ADMIN');
        $manager->persist($roleAdmin);

        for ($i = 0; $i < 10; $i++) {
            $user = new Users();
            $user->setFirstname($faker->firstName());
            $user->setLastname($faker->lastName());
            $user->setEmail($faker->email());
            $password = $faker->password();
            $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
            $user->setPhoneNumber($faker->phoneNumber());
            $user->addRole($roleUser);


            $manager->persist($user);
            $manager->flush();
        }


        $user = new Users();
        $user->setFirstname("Mathieu");
        $user->setLastname("Crosnier");
        $user->setEmail("mathieu.crosnier15@outlook.fr");
        $password = "1234";
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        $user->setPhoneNumber($faker->phoneNumber());
        $user->addRole($roleAdmin);

        $manager->persist($user);

        $session1 = new Session();
        $session2 = new Session();
        $now = new \DateTime();
        $session1->setUser($user);
        $session2->setUser($user);
        $session1->setDistance(50);
        $session2->setDistance(100);
        $session1->setDuree(50);
        $session2->setDuree(100);
        $session1->setDate($now);
        $session2->setDate($now);

        $manager->persist($session1);
        $manager->persist($session2);

        $manager->flush();
    }
}
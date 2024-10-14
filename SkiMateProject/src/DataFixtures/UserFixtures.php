<?php

namespace App\DataFixtures;


use App\Entity\Roles;

use App\Entity\Statistics;
use App\Entity\Users;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private ObjectManager $manager;
    protected $faker;

    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $faker = $this->faker = Factory::create('fr_FR');

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
            $user->setStatistics(new Statistics());

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
        $user->addRole($roleUser);
        $user->setStatistics(new Statistics());

        $manager->persist($user);
        $manager->flush();
    }
}
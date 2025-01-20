<?php

namespace App\DataFixtures;

use App\Entity\SkiResort;
use App\Repository\SkiResortRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use App\Repository\UsersRepository;
use App\Entity\Comment;
use Faker\Factory;

class CommentFixtures extends Fixture implements  OrderedFixtureInterface, FixtureGroupInterface
{
    public function __construct(
        private UsersRepository $usersRepository,
        private SkiResortRepository $skiResortRepository
    )
    {
    }

    public function getOrder(): int
    {
        return 3;
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        //Commentaires d'une station
        $users = $this->usersRepository->findAll();
        $resorts = $this->skiResortRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $randomUser = $users[array_rand($users)];
            // $randomResort = $resorts[array_rand($resorts)];

            $comment = new Comment();
            $comment->setTitle($faker->text(10));
            $comment->setDescription($faker->text(200));
            $comment->setEntityType('Station');
            $comment->setEntityId($faker->numberBetween(1, 10));
            // $comment->setEntityId($randomResort->getId());
            $comment->setNote($faker->numberBetween(1, 5));
            $comment->setUsers($randomUser);

            $manager->persist($comment);
        }
        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['comment'];
    }
}

<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Repository\UsersRepository;
use App\Entity\Comment;
use Faker\Factory;

class CommentFixtures extends Fixture
{
    public function __construct(
        private UsersRepository $usersRepository)
    {
    }
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $users = $this->usersRepository->findAll();

        for ($i = 0; $i < 10; $i++) {
            $randomUser = $users[array_rand($users)];
            $comment = new Comment();
            $comment->setTitle("Commentaire $i");
            $comment->setDescription($faker->text(200));
            $comment->setNote($faker->numberBetween(1, 5));
            $comment->setUsers($randomUser);

            $manager->persist($comment);
        }
        $manager->flush();
    }
}

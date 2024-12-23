<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Comment;
use Faker\Factory;

class CommentFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $faker = Factory::create('fr_FR');

        $comment1 = new Comment();
        $comment1->setTitle("New comment 1");
        $comment1->setDescription($faker->text());
        $comment1->setNote(5);
        $comment1->setUsers();

        $comment2 = new Comment();
        $comment2->setTitle("New comment 2");
        $comment2->setDescription($faker->text());
        $comment2->setNote(5);
        $comment1->setUsers();

        $manager->flush();
    }
}

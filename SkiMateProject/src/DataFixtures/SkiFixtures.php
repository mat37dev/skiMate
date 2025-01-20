<?php

namespace App\DataFixtures;

use App\Entity\SkiLevel;
use App\Entity\SkiPreference;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SkiFixtures extends Fixture implements FixtureGroupInterface, OrderedFixtureInterface {

    public function getOrder(): int
    {
        return 2;
    }
    public function load(ObjectManager $manager): void
    {
        $preferenceType = ["Piste", "Hors Piste", "Snow Park"];

        foreach ($preferenceType as $preference) {
            $skiPreference = new SkiPreference();
            $skiPreference->setName($preference);
            $manager->persist($skiPreference);
            $this->addReference($preference, $skiPreference);
        }
        $manager->flush();

        $levelType = ["vert", "bleue", "rouge", "noir"];

        foreach ($levelType as $type) {
            $skiLevel = new SkiLevel();
            $skiLevel->setName($type);
            $manager->persist($skiLevel);
            $this->addReference($type, $skiLevel);
        }
        $manager->flush();
    }



    public static function getGroups(): array
    {
        return ["ski"];
    }
}

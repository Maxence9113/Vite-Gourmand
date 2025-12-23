<?php

namespace App\DataFixtures;

use App\Entity\Dietetary;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DietetaryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $dietetaryList = [
            'Végétarien',
            'Végan',
            'Halal',
            'Casher',
            'Sans porc'
        ];

        foreach ($dietetaryList as $dietetaryName) {
            $dietetary = new Dietetary();
            $dietetary->setName($dietetaryName);
            $manager->persist($dietetary);
        }

        $manager->flush();
    }
}
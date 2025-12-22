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
            'Végétalien',
            'Sans gluten',
            'Sans lactose',
            'Halal',
            'Casher',
            'Pescetarien',
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
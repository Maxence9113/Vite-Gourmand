<?php

namespace App\DataFixtures;

use App\Entity\Allergen;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AllergenFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $allergenes = [
            'Gluten',
            'Crustacés',
            'Œufs',
            'Poissons',
            'Arachides',
            'Soja',
            'Lait',
            'Fruits à coque',
            'Céleri',
            'Moutarde',
            'Graines de sésame',
            'Anhydride sulfureux et sulfites',
            'Lupin',
            'Mollusques'
        ];

        
        foreach ($allergenes as $allergene) {
            
            $allergen = new Allergen();
            $allergen->setName($allergene);
            $manager->persist($allergen);
            
        }

        $manager->flush();
    }
}

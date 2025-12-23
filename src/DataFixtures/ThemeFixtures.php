<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Thèmes simplifiés sans images
        $themes = [
            [
                'name' => 'Anniversaire',
                'description' => 'Un menu personnalisable pour célébrer un anniversaire de manière mémorable, adapté à tous les âges.',
            ],
            [
                'name' => 'Barbecue estival',
                'description' => 'Un menu convivial pour profiter des beaux jours en extérieur, avec des grillades et des salades fraîches.',
            ],
            [
                'name' => 'Mariage',
                'description' => 'Un menu élégant et raffiné pour célébrer l\'union de deux personnes, avec des mets d\'exception.',
            ],
            [
                'name' => 'Noël',
                'description' => 'Un menu festif pour célébrer les fêtes de fin d\'année en famille, avec des plats traditionnels et chaleureux.',
            ],
            [
                'name' => 'Réveillon du Nouvel An',
                'description' => 'Un menu festif et élégant pour terminer l\'année en beauté et accueillir la nouvelle année.',
            ],
            [
                'name' => 'Pâques',
                'description' => 'Un menu printanier célébrant le renouveau, avec des saveurs fraîches et des ingrédients de saison.',
            ]
        ];

        foreach ($themes as $themeData) {
            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setDescription($themeData['description']);
            $manager->persist($theme);
        }

        $manager->flush();
    }
}
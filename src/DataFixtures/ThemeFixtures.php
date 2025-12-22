<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ThemeFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $themes = [
            [
                'name' => 'Noël',
                'description' => 'Un menu festif pour célébrer les fêtes de fin d\'année en famille, avec des plats traditionnels et chaleureux.',
                'illustration' => '/uploads/themes/noel.jpg',
                'textAlt' => 'Table de Noël décorée avec des bougies et des décorations festives'
            ],
            [
                'name' => 'Pâques',
                'description' => 'Un menu printanier célébrant le renouveau, avec des saveurs fraîches et des ingrédients de saison.',
                'illustration' => '/uploads/themes/paques.jpg',
                'textAlt' => 'Table de Pâques avec décoration printanière et œufs colorés'
            ],
            [
                'name' => 'Anniversaire',
                'description' => 'Un menu personnalisable pour célébrer un anniversaire de manière mémorable, adapté à tous les âges.',
                'illustration' => '/uploads/themes/anniversaire.jpg',
                'textAlt' => 'Table d\'anniversaire festive avec ballons et décoration colorée'
            ],
            [
                'name' => 'Mariage',
                'description' => 'Un menu élégant et raffiné pour célébrer l\'union de deux personnes, avec des mets d\'exception.',
                'illustration' => '/uploads/themes/mariage.jpg',
                'textAlt' => 'Table de mariage élégante avec décoration florale et vaisselle raffinée'
            ],
            [
                'name' => 'Barbecue estival',
                'description' => 'Un menu convivial pour profiter des beaux jours en extérieur, avec des grillades et des salades fraîches.',
                'illustration' => '/uploads/themes/barbecue.jpg',
                'textAlt' => 'Barbecue en extérieur avec grillades et convives'
            ],
            [
                'name' => 'Réveillon du Nouvel An',
                'description' => 'Un menu festif et élégant pour terminer l\'année en beauté et accueillir la nouvelle année.',
                'illustration' => '/uploads/themes/nouvel_an.jpg',
                'textAlt' => 'Table de réveillon avec champagne et décoration dorée'
            ]
        ];

        foreach ($themes as $themeData) {
            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setDescription($themeData['description']);
            $theme->setIllustration($themeData['illustration']);
            $theme->setTextAlt($themeData['textAlt']);
            $manager->persist($theme);
        }

        $manager->flush();
    }
}
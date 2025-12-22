<?php

namespace App\DataFixtures;

use App\Entity\Theme;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ThemeFixtures extends Fixture
{
    private string $projectDir;

    public function __construct(
        
        // L'Autowire sert a définir quel service utiliser (service.yaml)
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
    }

    public function load(ObjectManager $manager): void
    {
        // Thèmes dans l'ordre alphabétique pour correspondre aux images dans fixtures/images/themes
        $themes = [
            [
                'name' => 'Anniversaire',
                'description' => 'Un menu personnalisable pour célébrer un anniversaire de manière mémorable, adapté à tous les âges.',
                'imageFilename' => 'anniversaire.jpg',
                'textAlt' => 'Table d\'anniversaire festive avec ballons et décoration colorée'
            ],
            [
                'name' => 'Barbecue estival',
                'description' => 'Un menu convivial pour profiter des beaux jours en extérieur, avec des grillades et des salades fraîches.',
                'imageFilename' => 'barbecue.jpg',
                'textAlt' => 'Barbecue en extérieur avec grillades et convives'
            ],
            [
                'name' => 'Mariage',
                'description' => 'Un menu élégant et raffiné pour célébrer l\'union de deux personnes, avec des mets d\'exception.',
                'imageFilename' => 'mariage.jpg',
                'textAlt' => 'Table de mariage élégante avec décoration florale et vaisselle raffinée'
            ],
            [
                'name' => 'Noël',
                'description' => 'Un menu festif pour célébrer les fêtes de fin d\'année en famille, avec des plats traditionnels et chaleureux.',
                'imageFilename' => 'noel.jpg',
                'textAlt' => 'Table de Noël décorée avec des bougies et des décorations festives'
            ],
            [
                'name' => 'Réveillon du Nouvel An',
                'description' => 'Un menu festif et élégant pour terminer l\'année en beauté et accueillir la nouvelle année.',
                'imageFilename' => 'nouvel_an.jpg',
                'textAlt' => 'Table de réveillon avec champagne et décoration dorée'
            ],
            [
                'name' => 'Pâques',
                'description' => 'Un menu printanier célébrant le renouveau, avec des saveurs fraîches et des ingrédients de saison.',
                'imageFilename' => 'paques.jpg',
                'textAlt' => 'Table de Pâques avec décoration printanière et œufs colorés'
            ]
        ];

        $fixturesImagesDir = $this->projectDir . '/fixtures/images/themes';
        $uploadDir = $this->projectDir . '/public/uploads/theme_illustrations';

        // S'assurer que le dossier d'upload existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($themes as $themeData) {
            $sourceImagePath = $fixturesImagesDir . '/' . $themeData['imageFilename'];
            $destinationPath = $uploadDir . '/' . $themeData['imageFilename'];

            // Copier l'image depuis fixtures vers uploads
            if (file_exists($sourceImagePath)) {
                copy($sourceImagePath, $destinationPath);
            }

            $theme = new Theme();
            $theme->setName($themeData['name']);
            $theme->setDescription($themeData['description']);
            $theme->setIllustration('/uploads/theme_illustrations/' . $themeData['imageFilename']);
            $theme->setTextAlt($themeData['textAlt']);
            $manager->persist($theme);
        }

        $manager->flush();
    }
}
<?php

namespace App\DataFixtures;

use App\Entity\Menu;
use App\Entity\Theme;
use App\Entity\Dietetary;
use App\Entity\Recipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MenuFixtures extends Fixture implements DependentFixtureInterface
{
    private string $projectDir;

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir
    ) {
        $this->projectDir = $projectDir;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer toutes les entités nécessaires
        $themes = $manager->getRepository(Theme::class)->findAll();
        $dietetaryList = $manager->getRepository(Dietetary::class)->findAll();
        $recipes = $manager->getRepository(Recipe::class)->findAll();

        // Si aucun thème n'existe, on ne peut pas créer de menus
        if (empty($themes)) {
            return;
        }

        // Images disponibles dans fixtures/images/themes (on les réutilise pour les menus)
        $availableImages = [
            ['filename' => 'anniversaire.jpg', 'alt' => 'Menu d\'anniversaire festif avec ballons et décoration colorée'],
            ['filename' => 'barbecue.jpg', 'alt' => 'Menu barbecue avec grillades et légumes'],
            ['filename' => 'mariage.jpg', 'alt' => 'Menu de mariage élégant avec décoration florale'],
            ['filename' => 'noel.jpg', 'alt' => 'Menu de Noël avec décoration festive'],
            ['filename' => 'nouvel_an.jpg', 'alt' => 'Menu de réveillon avec champagne'],
            ['filename' => 'paques.jpg', 'alt' => 'Menu de Pâques printanier'],
        ];

        $fixturesImagesDir = $this->projectDir . '/fixtures/images/themes';
        $uploadDir = $this->projectDir . '/public/uploads/menu_illustrations';

        // S'assurer que le dossier d'upload existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        // Créer 20 menus aléatoires
        for ($i = 0; $i < 20; $i++) {
            $menu = new Menu();

            // Nom du menu avec un thème
            $theme = $themes[array_rand($themes)];
            $menu->setName($faker->words(3, true) . ' - ' . $theme->getName());

            // Description
            $menu->setDescription($faker->paragraph(3));

            // Nombre minimum de personnes (entre 2 et 20)
            $menu->setNbPersonMin($faker->numberBetween(2, 20));

            // Prix par personne (entre 25€ et 150€)
            $menu->setPricePerPerson($faker->randomFloat(2, 25, 150));

            // Assigner le thème
            $menu->setTheme($theme);

            // Assigner une illustration aléatoire
            $randomImage = $availableImages[array_rand($availableImages)];
            $sourceImagePath = $fixturesImagesDir . '/' . $randomImage['filename'];
            $destinationFilename = 'menu_' . ($i + 1) . '_' . $randomImage['filename'];
            $destinationPath = $uploadDir . '/' . $destinationFilename;

            // Copier l'image depuis fixtures vers uploads
            if (file_exists($sourceImagePath)) {
                copy($sourceImagePath, $destinationPath);
            }

            $menu->setIllustration('/uploads/menu_illustrations/' . $destinationFilename);
            $menu->setTextAlt($randomImage['alt']);

            // Ajouter 0 à 3 régimes alimentaires aléatoires
            if (!empty($dietetaryList)) {
                $numberOfDietary = $faker->numberBetween(0, 3);
                $selectedDietary = [];

                for ($j = 0; $j < $numberOfDietary; $j++) {
                    $randomDietary = $dietetaryList[array_rand($dietetaryList)];

                    // Éviter les doublons
                    if (!in_array($randomDietary, $selectedDietary, true)) {
                        $selectedDietary[] = $randomDietary;
                        $menu->addDietetary($randomDietary);
                    }
                }
            }

            // Ajouter 3 à 6 recettes aléatoires au menu
            if (!empty($recipes)) {
                $numberOfRecipes = $faker->numberBetween(3, 6);
                $selectedRecipes = [];

                for ($k = 0; $k < $numberOfRecipes; $k++) {
                    $randomRecipe = $recipes[array_rand($recipes)];

                    // Éviter les doublons
                    if (!in_array($randomRecipe, $selectedRecipes, true)) {
                        $selectedRecipes[] = $randomRecipe;
                        $menu->addRecipe($randomRecipe);
                    }
                }
            }

            $manager->persist($menu);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ThemeFixtures::class,
            DietetaryFixtures::class,
            RecipeFixtures::class,
        ];
    }
}
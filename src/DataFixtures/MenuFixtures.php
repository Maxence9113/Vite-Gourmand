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

class MenuFixtures extends Fixture implements DependentFixtureInterface
{
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
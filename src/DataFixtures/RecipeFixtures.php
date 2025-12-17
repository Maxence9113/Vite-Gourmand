<?php

namespace App\DataFixtures;

use App\Entity\Recipe;
use App\Entity\Category;
use App\Entity\Allergen;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Ajouter le provider Restaurant à Faker pour la locale française
        $faker->addProvider(new \FakerRestaurant\Provider\fr_FR\Restaurant($faker));

        // Récupérer toutes les catégories et allergènes existants
        $categories = $manager->getRepository(Category::class)->findAll();
        $allergens = $manager->getRepository(Allergen::class)->findAll();

        // Créer 30 recettes aléatoires avec Faker
        for ($i = 0; $i < 30; $i++) {
            $recipe = new Recipe();

            // Faker génère un nom de plat aléatoire avec le provider Restaurant
            $recipe->setTitle($faker->foodName());

            // Génère une description composée de plusieurs éléments
            $descriptionParts = [
                $faker->meatName(),
                $faker->vegetableName(),
                $faker->fruitName(),
                $faker->sauceName(),
            ];

            // Mélanger et prendre 2-3 éléments pour la description
            shuffle($descriptionParts);
            $selectedParts = array_slice($descriptionParts, 0, $faker->numberBetween(2, 3));
            $recipe->setDescription(implode(', ', $selectedParts));

            // Assigner une catégorie aléatoire
            if (!empty($categories)) {
                $randomCategory = $categories[array_rand($categories)];
                $recipe->setCategory($randomCategory);
            }

            // Assigner entre 0 et 3 allergènes aléatoires
            $numberOfAllergens = $faker->numberBetween(0, 3);
            $selectedAllergens = [];

            for ($j = 0; $j < $numberOfAllergens && !empty($allergens); $j++) {
                $randomAllergen = $allergens[array_rand($allergens)];

                // Éviter les doublons
                if (!in_array($randomAllergen, $selectedAllergens, true)) {
                    $selectedAllergens[] = $randomAllergen;
                    $recipe->addAllergen($randomAllergen);
                }
            }

            $manager->persist($recipe);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            AllergenFixtures::class,
        ];
    }
}
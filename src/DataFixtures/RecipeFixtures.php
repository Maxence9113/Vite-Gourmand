<?php

namespace App\DataFixtures;

use App\Entity\Recipe;
use App\Entity\RecipeIllustration;
use App\Entity\Category;
use App\Entity\Allergen;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\String\Slugger\SluggerInterface;

class RecipeFixtures extends Fixture implements DependentFixtureInterface
{
    private SluggerInterface $slugger;
    private string $projectDir;

    public function __construct(
        SluggerInterface $slugger,
        
        // L'Autowire sert a définir quel service utiliser (service.yaml)
        #[Autowire(param: 'kernel.project_dir')]
        string $projectDir
    ) {
        $this->slugger = $slugger;
        $this->projectDir = $projectDir;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Ajouter le provider Restaurant à Faker pour la locale française
        $faker->addProvider(new \FakerRestaurant\Provider\fr_FR\Restaurant($faker));

        // Récupérer la liste des images de fixtures disponibles
        $fixturesImagesDir = $this->projectDir . '/fixtures/images/recipes';
        $availableImages = [];

        if (is_dir($fixturesImagesDir)) {
            $files = scandir($fixturesImagesDir);
            foreach ($files as $file) {
                // Filtrer les fichiers images (jpg, jpeg, png, webp)
                if (preg_match('/\.(jpg|jpeg|png|webp)$/i', $file)) {
                    $availableImages[] = $file;
                }
            }
        }

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

            // Ajouter 1 à 2 illustrations aléatoires pour chaque recette (si des images sont disponibles)
            if (!empty($availableImages)) {
                $numberOfIllustrations = $faker->numberBetween(1, 3);

                for ($k = 0; $k < $numberOfIllustrations; $k++) {
                    // Choisir une image aléatoire parmi celles disponibles
                    $randomImage = $availableImages[array_rand($availableImages)];
                    $sourceImagePath = $fixturesImagesDir . '/' . $randomImage;

                    // Créer un nom de fichier unique avec slugger + uniqid (même logique que FileUploader)
                    $originalFilename = pathinfo($randomImage, PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $extension = pathinfo($randomImage, PATHINFO_EXTENSION);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

                    // Copier l'image dans le dossier d'upload
                    $uploadDir = $this->projectDir . '/public/uploads/recipe_illustrations';
                    $destinationPath = $uploadDir . '/' . $newFilename;

                    // S'assurer que le dossier d'upload existe
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    // Copier le fichier
                    copy($sourceImagePath, $destinationPath);

                    // Créer l'entité RecipeIllustration
                    $illustration = new RecipeIllustration();
                    $illustration->setName($newFilename);
                    $illustration->setUrl('/uploads/recipe_illustrations/' . $newFilename);
                    $illustration->setAltText($faker->sentence(6)); // Texte alternatif aléatoire
                    $illustration->setRecipe($recipe);

                    // Ajouter l'illustration à la recette (grâce à cascade persist, pas besoin de persist explicite)
                    $recipe->addRecipeIllustration($illustration);
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
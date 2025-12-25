<?php

namespace App\DataFixtures;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AddressFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer tous les utilisateurs
        $users = $manager->getRepository(User::class)->findAll();

        // Adresses bordelaises (code postal 33000 - 33999)
        $bordeauxStreets = [
            'Rue Sainte-Catherine',
            'Cours de l\'Intendance',
            'Place de la Bourse',
            'Quai des Chartrons',
            'Rue du Palais Gallien',
            'Allées de Tourny',
            'Place Gambetta',
            'Rue Porte Dijeaux',
            'Cours Victor Hugo',
            'Place de la Victoire',
            'Rue Judaïque',
            'Cours d\'Albret',
            'Rue des Remparts',
            'Place Pey Berland',
            'Rue Fondaudège',
        ];

        $bordeauxPostalCodes = [
            '33000', // Bordeaux centre
            '33100', // Bordeaux Saint-Augustin
            '33200', // Bordeaux Caudéran
            '33300', // Bordeaux Bastide
            '33800', // Bordeaux Chartrons
        ];

        // Adresses hors Bordeaux (autres villes de Gironde)
        $otherCities = [
            ['city' => 'Mérignac', 'postalCode' => '33700', 'streets' => ['Avenue de la Somme', 'Rue Clément Ader', 'Avenue Roland Garros']],
            ['city' => 'Pessac', 'postalCode' => '33600', 'streets' => ['Avenue du Haut-Lévêque', 'Rue du Professeur Arnozan', 'Avenue de Canéjan']],
            ['city' => 'Talence', 'postalCode' => '33400', 'streets' => ['Cours Gambetta', 'Avenue Roul', 'Rue Peybouquey']],
            ['city' => 'Bègles', 'postalCode' => '33130', 'streets' => ['Rue Calixte Camelle', 'Avenue du Maréchal de Lattre de Tassigny', 'Rue Léon Gambetta']],
            ['city' => 'Arcachon', 'postalCode' => '33120', 'streets' => ['Boulevard de la Plage', 'Avenue Gambetta', 'Rue du Général Leclerc']],
            ['city' => 'Libourne', 'postalCode' => '33500', 'streets' => ['Rue Gambetta', 'Quai Souchet', 'Place Abel Surchamp']],
        ];

        $labels = ['Domicile', 'Travail', 'Maison de campagne', 'Bureau', 'Résidence secondaire', null, null]; // null pour certaines adresses sans label

        foreach ($users as $user) {
            // Chaque utilisateur aura entre 1 et 3 adresses
            $numberOfAddresses = $faker->numberBetween(1, 3);

            for ($i = 0; $i < $numberOfAddresses; $i++) {
                $address = new Address();

                // 60% des adresses sont à Bordeaux, 40% hors Bordeaux
                $isBordeaux = $faker->boolean(60);

                if ($isBordeaux) {
                    // Adresse à Bordeaux
                    $streetNumber = $faker->numberBetween(1, 150);
                    $street = $faker->randomElement($bordeauxStreets);
                    $address->setStreet($streetNumber . ' ' . $street);
                    $address->setPostalCode($faker->randomElement($bordeauxPostalCodes));
                    $address->setCity('Bordeaux');
                } else {
                    // Adresse hors Bordeaux
                    $cityData = $faker->randomElement($otherCities);
                    $streetNumber = $faker->numberBetween(1, 100);
                    $street = $faker->randomElement($cityData['streets']);
                    $address->setStreet($streetNumber . ' ' . $street);
                    $address->setPostalCode($cityData['postalCode']);
                    $address->setCity($cityData['city']);
                }

                // Téléphone français
                $address->setPhone($faker->phoneNumber());

                // Label (parfois null)
                $label = $faker->randomElement($labels);
                if ($label !== null && $i > 0) {
                    // Si c'est une deuxième ou troisième adresse, on utilise un label différent
                    $address->setLabel($label);
                }

                // La première adresse est par défaut
                $address->setIsDefault($i === 0);

                // Lier à l'utilisateur
                $address->setUser($user);

                $manager->persist($address);
            }
        }

        // Créer quelques utilisateurs sans adresse pour tester l'état vide
        // (Les 2 derniers utilisateurs créés n'auront pas d'adresse)

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}

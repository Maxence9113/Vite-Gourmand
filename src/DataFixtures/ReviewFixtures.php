<?php

namespace App\DataFixtures;

use App\Entity\Review;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $comments = [
            "Service impeccable pour notre mariage ! Les plats étaient délicieux et nos invités nous en parlent encore. Merci à toute l'équipe pour leur professionnalisme.",
            "Excellent traiteur ! Le menu de Noël était parfait, respecte les allergies et propose des options végétariennes délicieuses. Je recommande vivement !",
            "Parfait pour notre événement d'entreprise ! Ponctualité, qualité, présentation soignée. Une équipe au top, nous referons appel à eux !",
            "Très bon rapport qualité-prix. Les plats étaient savoureux et la présentation était soignée. Je recommande !",
            "Super expérience ! L'équipe est très professionnelle et à l'écoute de nos besoins.",
            "Les plats étaient excellents, nos invités ont adoré. Service au top !",
            "Je suis ravi de cette prestation. Tout était parfait du début à la fin.",
            "Qualité irréprochable, je recommande les yeux fermés !",
            "Un grand merci pour ce merveilleux repas. Tout le monde s'est régalé.",
            "Prestation de qualité, équipe sympathique et professionnelle.",
            null, // Avis sans commentaire
            "Bon dans l'ensemble mais quelques points à améliorer sur le timing.",
            "Très satisfait de la prestation, les plats étaient délicieux et bien présentés.",
            null,
            "Excellente cuisine, service impeccable. Je recommande vivement ce traiteur pour vos événements !",
        ];

        // Créer 15 avis
        for ($i = 0; $i < 15; $i++) {
            $review = new Review();

            // Générer un nom aléatoire avec initiale
            $firstName = $faker->firstName();
            $lastName = $faker->lastName();
            $customerName = $firstName . ' ' . strtoupper(substr($lastName, 0, 1)) . '.';

            $review->setCustomerName($customerName);
            $review->setRating($faker->numberBetween(3, 5)); // Notes entre 3 et 5 étoiles
            $review->setComment($comments[$i] ?? $faker->optional(0.7)->realText(200));
            $review->setCreatedAt(\DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', 'now')));

            // 80% des avis sont validés, 20% en attente
            $review->setIsValidated($faker->boolean(80));

            $manager->persist($review);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
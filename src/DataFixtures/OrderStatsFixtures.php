<?php

namespace App\DataFixtures;

use App\Document\OrderStats;
use App\Entity\Menu;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Faker\Factory;

/**
 * Fixtures pour peupler MongoDB avec des statistiques de commandes
 *
 * Ces fixtures créent des données de test pour visualiser :
 * - Le nombre de commandes par menu
 * - Le chiffre d'affaires par menu
 * - Les statistiques sur plusieurs mois
 */
class OrderStatsFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private DocumentManager $documentManager
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer les vrais menus créés par MenuFixtures
        $menus = $manager->getRepository(Menu::class)->findAll();

        if (empty($menus)) {
            echo "\n⚠️  Aucun menu trouvé, impossible de créer des statistiques\n";
            return;
        }

        // Générer des statistiques sur les 6 derniers mois
        $now = new \DateTime();
        $totalOrders = 0;

        // Pour chaque menu, générer entre 5 et 30 commandes livrées
        foreach ($menus as $menu) {
            $numberOfOrders = $faker->numberBetween(5, 30);

            for ($i = 0; $i < $numberOfOrders; $i++) {
                $stats = new OrderStats();

                // ID de commande simulé (on commence à 1000 pour éviter les conflits)
                $stats->setOrderId($totalOrders + 1000);

                // Menu ID réel
                $stats->setMenuId($menu->getId());

                // Nom du menu réel
                $stats->setMenuName($menu->getName());

                // Thème du menu
                $stats->setThemeName($menu->getTheme()->getName());

                // Prix total de la commande basé sur le prix du menu
                // Prix du menu * nombre de personnes (avec variation)
                $numberOfPeople = $faker->numberBetween(
                    $menu->getNbPersonMin(),
                    $menu->getNbPersonMin() + 20
                );
                $menuPriceInEuros = $menu->getPricePerPerson() / 100;
                $totalPrice = $menuPriceInEuros * $numberOfPeople;
                // Ajouter une variation de ±10% pour les frais de livraison/réductions
                $totalPrice = $totalPrice * $faker->numberBetween(90, 110) / 100;
                $stats->setTotalPrice(round($totalPrice, 2));

                // Nombre de personnes
                $stats->setNumberOfPeople($numberOfPeople);

                // Date de commande (répartie sur les 6 derniers mois)
                $daysAgo = $faker->numberBetween(1, 180);
                $orderDate = (clone $now)->modify("-{$daysAgo} days");
                $stats->setOrderDate($orderDate);

                $this->documentManager->persist($stats);
                $totalOrders++;
            }
        }

        // Ajouter quelques commandes récentes (dernière semaine) pour les menus populaires
        // Prendre 3 menus au hasard
        $popularMenus = $faker->randomElements($menus, min(3, count($menus)));
        foreach ($popularMenus as $menu) {
            for ($i = 0; $i < 5; $i++) {
                $stats = new OrderStats();

                $stats->setOrderId($totalOrders + 1000);
                $stats->setMenuId($menu->getId());
                $stats->setMenuName($menu->getName());
                $stats->setThemeName($menu->getTheme()->getName());

                $numberOfPeople = $faker->numberBetween(
                    $menu->getNbPersonMin(),
                    $menu->getNbPersonMin() + 15
                );
                $menuPriceInEuros = $menu->getPricePerPerson() / 100;
                $totalPrice = $menuPriceInEuros * $numberOfPeople;
                $totalPrice = $totalPrice * $faker->numberBetween(90, 110) / 100;
                $stats->setTotalPrice(round($totalPrice, 2));
                $stats->setNumberOfPeople($numberOfPeople);

                // Date dans les 7 derniers jours
                $daysAgo = $faker->numberBetween(0, 7);
                $orderDate = (clone $now)->modify("-{$daysAgo} days");
                $stats->setOrderDate($orderDate);

                $this->documentManager->persist($stats);
                $totalOrders++;
            }
        }

        $this->documentManager->flush();

        echo "\n✅ {$totalOrders} statistiques de commandes créées dans MongoDB\n";
    }

    public function getDependencies(): array
    {
        return [
            MenuFixtures::class,
        ];
    }
}
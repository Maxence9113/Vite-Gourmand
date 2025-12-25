<?php

namespace App\DataFixtures;

use App\Entity\Menu;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class OrderFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Récupérer les utilisateurs qui ont le rôle USER
        $users = $manager->getRepository(User::class)->findAll();
        $usersWithAddresses = array_filter($users, function (User $user) {
            return !$user->getAddresses()->isEmpty();
        });

        if (empty($usersWithAddresses)) {
            return;
        }

        // Récupérer tous les menus
        $menus = $manager->getRepository(Menu::class)->findAll();
        if (empty($menus)) {
            return;
        }

        // Créer 30 commandes aléatoires
        for ($i = 0; $i < 30; $i++) {
            $user = $usersWithAddresses[array_rand($usersWithAddresses)];
            $menu = $menus[array_rand($menus)];
            $addresses = $user->getAddresses()->toArray();
            $address = $addresses[array_rand($addresses)];

            $order = new Order();

            // Informations du client (snapshot)
            $order->setUser($user);
            $order->setCustomerFirstname($user->getFirstname());
            $order->setCustomerLastname($user->getLastname());
            $order->setCustomerEmail($user->getEmail());
            $order->setCustomerPhone($address->getPhone());

            // Informations du menu (snapshot)
            $order->setMenuName($menu->getName());
            $order->setMenuPricePerPerson($menu->getPricePerPerson());

            // Nombre de personnes (entre le minimum du menu et +10)
            $numberOfPersons = $faker->numberBetween(
                $menu->getNbPersonMin(),
                $menu->getNbPersonMin() + 10
            );
            $order->setNumberOfPersons($numberOfPersons);

            // Date de livraison (entre -30 jours et +60 jours)
            $deliveryDate = $faker->dateTimeBetween('-30 days', '+60 days');
            $deliveryDateTime = \DateTimeImmutable::createFromMutable($deliveryDate);
            $order->setDeliveryDateTime($deliveryDateTime);

            // Adresse de livraison (snapshot)
            $order->setDeliveryAddress(sprintf(
                '%s, %s %s',
                $address->getStreet(),
                $address->getPostalCode(),
                $address->getCity()
            ));

            // Calcul des prix
            $menuSubtotal = $menu->getPricePerPerson() * $numberOfPersons;
            $order->setMenuSubtotal($menuSubtotal);

            // Distance de livraison (simulée)
            $postalCode = $address->getPostalCode();
            $isInBordeaux = str_starts_with($postalCode, '330');
            if ($isInBordeaux) {
                $deliveryDistanceKm = $faker->numberBetween(0, 15);
            } else {
                $firstTwoDigits = (int)substr($postalCode, 0, 2);
                $departmentDistance = abs($firstTwoDigits - 33);
                $deliveryDistanceKm = max(10, $departmentDistance * 50);
            }
            $order->setDeliveryDistanceKm($deliveryDistanceKm);

            // Frais de livraison (5€ base + 0.59€/km si hors Bordeaux)
            if ($isInBordeaux) {
                $deliveryCost = 500; // 5€
            } else {
                $deliveryCost = 500 + ($deliveryDistanceKm * 59); // 5€ + 0.59€/km
            }
            $order->setDeliveryCost($deliveryCost);

            // Réduction si 5+ personnes au-delà du minimum
            $extraPersons = $numberOfPersons - $menu->getNbPersonMin();
            $discountAmount = 0;
            if ($extraPersons >= 5) {
                $discountAmount = (int)($menuSubtotal * 0.10);
            }
            $order->setDiscountAmount($discountAmount);

            // Total
            $totalPrice = $menuSubtotal + $deliveryCost - $discountAmount;
            $order->setTotalPrice($totalPrice);

            // Emprunt de matériel (50% de chance)
            $hasMaterialLoan = $faker->boolean(50);
            $order->setHasMaterialLoan($hasMaterialLoan);
            if ($hasMaterialLoan) {
                $materialReturnDeadline = $deliveryDateTime->modify('+10 days');
                $order->setMaterialReturnDeadline($materialReturnDeadline);

                // 70% des commandes avec emprunt ont retourné le matériel
                $materialReturned = $faker->boolean(70);
                $order->setMaterialReturned($materialReturned);
            }

            // Initialiser la commande (génère le numéro de commande et définit le statut initial)
            $order->initialize();

            // Statut de la commande (selon la date de livraison)
            $now = new \DateTimeImmutable();
            if ($deliveryDateTime < $now->modify('-7 days')) {
                // Commandes anciennes : la plupart sont COMPLETED ou CANCELLED
                if ($faker->boolean(80)) {
                    if ($hasMaterialLoan && !$order->isMaterialReturned()) {
                        $order->changeStatus(OrderStatus::WAITING_MATERIAL_RETURN);
                    } else {
                        $order->changeStatus(OrderStatus::COMPLETED);
                    }
                } else {
                    $order->changeStatus(OrderStatus::CANCELLED);
                    $order->setCancellationReason($faker->randomElement([
                        'Annulation par le client',
                        'Changement de date',
                        'Annulation de l\'événement',
                        'Problème de disponibilité',
                    ]));
                }
            } elseif ($deliveryDateTime < $now) {
                // Commandes récentes : DELIVERED, WAITING_MATERIAL_RETURN ou COMPLETED
                if ($hasMaterialLoan && !$order->isMaterialReturned()) {
                    $order->changeStatus(OrderStatus::WAITING_MATERIAL_RETURN);
                } else {
                    $order->changeStatus($faker->randomElement([
                        OrderStatus::DELIVERED,
                        OrderStatus::COMPLETED,
                    ]));
                }
            } elseif ($deliveryDateTime < $now->modify('+2 days')) {
                // Commandes imminentes : READY, DELIVERING, DELIVERED
                $order->changeStatus($faker->randomElement([
                    OrderStatus::READY,
                    OrderStatus::DELIVERING,
                    OrderStatus::DELIVERED,
                ]));
            } elseif ($deliveryDateTime < $now->modify('+7 days')) {
                // Commandes prochaines : VALIDATED, PREPARING, READY
                $order->changeStatus($faker->randomElement([
                    OrderStatus::VALIDATED,
                    OrderStatus::PREPARING,
                    OrderStatus::READY,
                ]));
            } else {
                // Commandes futures : PENDING ou VALIDATED
                $order->changeStatus($faker->randomElement([
                    OrderStatus::PENDING,
                    OrderStatus::VALIDATED,
                ]));
            }

            $manager->persist($order);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            MenuFixtures::class,
            AddressFixtures::class,
        ];
    }
}
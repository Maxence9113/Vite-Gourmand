<?php

namespace App\Tests\Service;

use App\Entity\Address;
use App\Entity\Menu;
use App\Entity\Theme;
use App\Entity\User;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderManagerStockTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OrderManager $orderManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->orderManager = $container->get(OrderManager::class);

        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->entityManager->rollback();
        parent::tearDown();
    }

    public function testCreateOrderWithStockLimitedMenu(): void
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstname('John');
        $user->setLastname('Doe');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setStreet('123 Rue Test');
        $address->setCity('Bordeaux');
        $address->setPostalCode('33000');
        $address->setPhone('0612345678');
        $address->setIsDefault(true);
        $this->entityManager->persist($address);

        // Créer un thème
        $theme = new Theme();
        $theme->setName('Anniversaire');
        $theme->setDescription('Thème pour anniversaires');
        $this->entityManager->persist($theme);

        // Créer un menu avec stock limité
        $menu = new Menu();
        $menu->setName('Menu Test');
        $menu->setDescription('Description test');
        $menu->setNbPersonMin(10);
        $menu->setPricePerPerson(5000); // 50€
        $menu->setTheme($theme);
        $menu->setStock(20); // Stock de 20 personnes
        $this->entityManager->persist($menu);

        $this->entityManager->flush();

        // Créer une commande pour 10 personnes
        $deliveryDateTime = new \DateTimeImmutable('+3 days');
        $order = $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10,
            $deliveryDateTime,
            false
        );

        // Sauvegarder la commande (devrait décrémenter le stock de 10 personnes)
        $this->orderManager->saveOrder($order, $menu);

        // Vérifier que le stock a été décrémenté de 10 (nombre de personnes)
        // 20 - 10 = 10
        $this->assertSame(10, $menu->getStock());
        $this->assertTrue($menu->isAvailable());
    }

    public function testCreateOrderWithUnlimitedStockMenu(): void
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('test2@example.com');
        $user->setFirstname('Jane');
        $user->setLastname('Doe');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setStreet('456 Rue Test');
        $address->setCity('Bordeaux');
        $address->setPostalCode('33000');
        $address->setPhone('0612345679');
        $address->setIsDefault(true);
        $this->entityManager->persist($address);

        // Créer un thème
        $theme = new Theme();
        $theme->setName('Mariage');
        $theme->setDescription('Thème pour mariages');
        $this->entityManager->persist($theme);

        // Créer un menu sans limite de stock (null)
        $menu = new Menu();
        $menu->setName('Menu Illimité');
        $menu->setDescription('Description test');
        $menu->setNbPersonMin(10);
        $menu->setPricePerPerson(5000);
        $menu->setTheme($theme);
        $menu->setStock(null); // Stock illimité
        $this->entityManager->persist($menu);

        $this->entityManager->flush();

        // Créer une commande
        $deliveryDateTime = new \DateTimeImmutable('+3 days');
        $order = $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10,
            $deliveryDateTime,
            false
        );

        // Sauvegarder la commande
        $this->orderManager->saveOrder($order, $menu);

        // Vérifier que le stock est toujours null
        $this->assertNull($menu->getStock());
        $this->assertTrue($menu->isAvailable());
    }

    public function testCreateOrderWithOutOfStockMenuThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Ce menu n\'est plus disponible en stock');

        // Créer un utilisateur
        $user = new User();
        $user->setEmail('test3@example.com');
        $user->setFirstname('Bob');
        $user->setLastname('Smith');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setStreet('789 Rue Test');
        $address->setCity('Bordeaux');
        $address->setPostalCode('33000');
        $address->setPhone('0612345670');
        $address->setIsDefault(true);
        $this->entityManager->persist($address);

        // Créer un thème
        $theme = new Theme();
        $theme->setName('Noël');
        $theme->setDescription('Thème pour Noël');
        $this->entityManager->persist($theme);

        // Créer un menu avec stock épuisé
        $menu = new Menu();
        $menu->setName('Menu Épuisé');
        $menu->setDescription('Description test');
        $menu->setNbPersonMin(10);
        $menu->setPricePerPerson(5000);
        $menu->setTheme($theme);
        $menu->setStock(0); // Stock épuisé
        $this->entityManager->persist($menu);

        $this->entityManager->flush();

        // Tentative de création de commande (devrait échouer)
        $deliveryDateTime = new \DateTimeImmutable('+3 days');
        $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10,
            $deliveryDateTime,
            false
        );
    }

    public function testStockDecrementToZeroMakesMenuUnavailable(): void
    {
        // Créer un utilisateur
        $user = new User();
        $user->setEmail('test4@example.com');
        $user->setFirstname('Alice');
        $user->setLastname('Johnson');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setStreet('101 Rue Test');
        $address->setCity('Bordeaux');
        $address->setPostalCode('33000');
        $address->setPhone('0612345671');
        $address->setIsDefault(true);
        $this->entityManager->persist($address);

        // Créer un thème
        $theme = new Theme();
        $theme->setName('Pâques');
        $theme->setDescription('Thème pour Pâques');
        $this->entityManager->persist($theme);

        // Créer un menu avec stock de 10 personnes exactement
        $menu = new Menu();
        $menu->setName('Menu Dernier');
        $menu->setDescription('Description test');
        $menu->setNbPersonMin(10);
        $menu->setPricePerPerson(5000);
        $menu->setTheme($theme);
        $menu->setStock(10); // Stock de 10 personnes exactement
        $this->entityManager->persist($menu);

        $this->entityManager->flush();

        // Créer une commande pour exactement 10 personnes
        $deliveryDateTime = new \DateTimeImmutable('+3 days');
        $order = $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10,
            $deliveryDateTime,
            false
        );

        // Sauvegarder la commande
        $this->orderManager->saveOrder($order, $menu);

        // Vérifier que le stock est à 0 et que le menu n'est plus disponible
        $this->assertSame(0, $menu->getStock());
        $this->assertFalse($menu->isAvailable());
    }

    public function testCreateOrderWithInsufficientStockThrowsException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Stock insuffisant');

        // Créer un utilisateur
        $user = new User();
        $user->setEmail('test5@example.com');
        $user->setFirstname('Charlie');
        $user->setLastname('Brown');
        $user->setPassword('hashedpassword');
        $user->setRoles(['ROLE_USER']);
        $this->entityManager->persist($user);

        // Créer une adresse
        $address = new Address();
        $address->setUser($user);
        $address->setStreet('202 Rue Test');
        $address->setCity('Bordeaux');
        $address->setPostalCode('33000');
        $address->setPhone('0612345672');
        $address->setIsDefault(true);
        $this->entityManager->persist($address);

        // Créer un thème
        $theme = new Theme();
        $theme->setName('Été');
        $theme->setDescription('Thème pour l\'été');
        $this->entityManager->persist($theme);

        // Créer un menu avec stock de 15 personnes
        $menu = new Menu();
        $menu->setName('Menu Été');
        $menu->setDescription('Description test');
        $menu->setNbPersonMin(10);
        $menu->setPricePerPerson(5000);
        $menu->setTheme($theme);
        $menu->setStock(15); // Stock de 15 personnes
        $this->entityManager->persist($menu);

        $this->entityManager->flush();

        // Tentative de commander pour 20 personnes (> stock de 15)
        $deliveryDateTime = new \DateTimeImmutable('+3 days');
        $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            20, // Plus que le stock disponible
            $deliveryDateTime,
            false
        );
    }
}
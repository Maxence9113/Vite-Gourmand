<?php

namespace App\Tests\Service;

use App\Entity\Address;
use App\Entity\Menu;
use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires du service OrderManager
 * Teste la création de commandes, les calculs de prix et les règles métier
 */
class OrderManagerTest extends TestCase
{
    private EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject $entityManager;
    private OrderManager $orderManager;

    protected function setUp(): void
    {
        // Créer un mock de l'EntityManager
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Créer le service OrderManager avec le mock
        $this->orderManager = new OrderManager($this->entityManager);
    }

    // ========================
    // Helpers pour créer des objets de test
    // ========================

    private function createTestUser(): User
    {
        $user = new User();
        $user->setFirstname('Jean');
        $user->setLastname('Dupont');
        $user->setEmail('jean.dupont@example.com');

        return $user;
    }

    private function createTestMenu(): Menu
    {
        $menu = new Menu();
        $menu->setName('Menu Découverte');
        $menu->setPricePerPerson(5000); // 50€
        $menu->setNbPersonMin(5);

        return $menu;
    }

    private function createTestAddressBordeaux(): Address
    {
        $address = new Address();
        $address->setStreet('10 Rue Example');
        $address->setPostalCode('33000');
        $address->setCity('Bordeaux');
        $address->setPhone('0612345678');

        return $address;
    }

    private function createTestAddressOutsideBordeaux(): Address
    {
        $address = new Address();
        $address->setStreet('5 Avenue Test');
        $address->setPostalCode('75001');
        $address->setCity('Paris');
        $address->setPhone('0612345678');

        return $address;
    }

    // ========================
    // Tests de création de commande
    // ========================

    public function testCreateOrderWithBasicInformation(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressBordeaux();
        $deliveryDate = new \DateTimeImmutable('+3 days');

        $order = $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10, // 10 personnes
            $deliveryDate,
            false
        );

        // Vérifier les informations client
        $this->assertEquals('Jean', $order->getCustomerFirstname());
        $this->assertEquals('Dupont', $order->getCustomerLastname());
        $this->assertEquals('jean.dupont@example.com', $order->getCustomerEmail());
        $this->assertEquals('0612345678', $order->getCustomerPhone());

        // Vérifier les informations de livraison
        $this->assertEquals('10 Rue Example, 33000 Bordeaux', $order->getDeliveryAddress());
        $this->assertEquals($deliveryDate, $order->getDeliveryDateTime());

        // Vérifier les informations menu
        $this->assertEquals('Menu Découverte', $order->getMenuName());
        $this->assertEquals(5000, $order->getMenuPricePerPerson());
        $this->assertEquals(10, $order->getNumberOfPersons());

        // Vérifier l'initialisation
        $this->assertEquals(OrderStatus::PENDING, $order->getStatus());
        $this->assertNotNull($order->getOrderNumber());
        $this->assertNotNull($order->getCreatedAt());
    }

    public function testCreateOrderWithMaterialLoan(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressBordeaux();
        $deliveryDate = new \DateTimeImmutable('2025-02-01 18:00:00');

        $order = $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10,
            $deliveryDate,
            true // Avec prêt de matériel
        );

        $this->assertTrue($order->hasMaterialLoan());
        $this->assertNotNull($order->getMaterialReturnDeadline());

        // Vérifier que la deadline est bien à J+10
        $expectedDeadline = $deliveryDate->modify('+10 days');
        $this->assertEquals(
            $expectedDeadline->format('Y-m-d'),
            $order->getMaterialReturnDeadline()->format('Y-m-d')
        );
    }

    public function testCreateOrderWithoutMaterialLoan(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressBordeaux();
        $deliveryDate = new \DateTimeImmutable('+3 days');

        $order = $this->orderManager->createOrder(
            $user,
            $menu,
            $address,
            10,
            $deliveryDate,
            false
        );

        $this->assertFalse($order->hasMaterialLoan());
        $this->assertNull($order->getMaterialReturnDeadline());
    }

    // ========================
    // Tests de calcul de prix
    // ========================

    public function testCalculateOrderPricingForBordeaux(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressBordeaux();

        $order = new Order();
        $order->setMenuPricePerPerson(5000); // 50€
        $order->setNumberOfPersons(10);

        $this->orderManager->calculateOrderPricing($order, $menu, $address);

        // Sous-total: 50€ × 10 = 500€ = 50000 centimes
        $this->assertEquals(50000, $order->getMenuSubtotal());

        // Livraison Bordeaux: 5€ = 500 centimes
        $this->assertEquals(500, $order->getDeliveryCost());
        $this->assertNull($order->getDeliveryDistanceKm());

        // Réduction: 10 - 5 (min) = 5 personnes extra → 10% de 500€ = 50€ = 5000 centimes
        $this->assertEquals(5000, $order->getDiscountAmount());

        // Total: 50000 + 500 - 5000 = 45500 centimes (455€)
        $this->assertEquals(45500, $order->getTotalPrice());
    }

    public function testCalculateOrderPricingOutsideBordeaux(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressOutsideBordeaux(); // Paris (75)

        $order = new Order();
        $order->setMenuPricePerPerson(5000); // 50€
        $order->setNumberOfPersons(10);

        $this->orderManager->calculateOrderPricing($order, $menu, $address);

        // Sous-total: 50€ × 10 = 500€ = 50000 centimes
        $this->assertEquals(50000, $order->getMenuSubtotal());

        // Distance calculée: abs(75 - 33) * 50 = 42 * 50 = 2100 km
        $this->assertEquals(2100, $order->getDeliveryDistanceKm());

        // Livraison: 5€ base + (2100km × 0.59€) = 5€ + 1239€ = 1244€ = 124400 centimes
        $this->assertEquals(124400, $order->getDeliveryCost());

        // Réduction: 10% de 500€ = 5000 centimes
        $this->assertEquals(5000, $order->getDiscountAmount());

        // Total: 50000 + 124400 - 5000 = 169400 centimes
        $this->assertEquals(169400, $order->getTotalPrice());
    }

    public function testCalculateOrderPricingWithoutDiscount(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressBordeaux();

        $order = new Order();
        $order->setMenuPricePerPerson(5000); // 50€
        $order->setNumberOfPersons(7); // 7 personnes (pas assez pour la réduction)

        $this->orderManager->calculateOrderPricing($order, $menu, $address);

        // Sous-total: 50€ × 7 = 350€ = 35000 centimes
        $this->assertEquals(35000, $order->getMenuSubtotal());

        // Livraison Bordeaux: 500 centimes
        $this->assertEquals(500, $order->getDeliveryCost());

        // Pas de réduction (7 - 5 = 2 personnes extra, il en faut 5)
        $this->assertNull($order->getDiscountAmount());

        // Total: 35000 + 500 = 35500 centimes
        $this->assertEquals(35500, $order->getTotalPrice());
    }

    public function testCalculateOrderPricingWithMinimumPersons(): void
    {
        $user = $this->createTestUser();
        $menu = $this->createTestMenu();
        $address = $this->createTestAddressBordeaux();

        $order = new Order();
        $order->setMenuPricePerPerson(5000); // 50€
        $order->setNumberOfPersons(5); // Exactement le minimum

        $this->orderManager->calculateOrderPricing($order, $menu, $address);

        // Sous-total: 50€ × 5 = 250€ = 25000 centimes
        $this->assertEquals(25000, $order->getMenuSubtotal());

        // Pas de réduction (0 personnes supplémentaires)
        $this->assertNull($order->getDiscountAmount());

        // Total: 25000 + 500 = 25500 centimes
        $this->assertEquals(25500, $order->getTotalPrice());
    }

    // ========================
    // Tests de validation de date
    // ========================

    public function testIsValidDeliveryDateWithValidDate(): void
    {
        // Date dans 3 jours (valide car > 48h)
        $validDate = new \DateTimeImmutable('+3 days');

        $isValid = $this->orderManager->isValidDeliveryDate($validDate);

        $this->assertTrue($isValid);
    }

    public function testIsValidDeliveryDateWithExactly48Hours(): void
    {
        // Date dans exactement 48 heures + 1 minute pour éviter les problèmes de timing
        $validDate = new \DateTimeImmutable('+48 hours +1 minute');

        $isValid = $this->orderManager->isValidDeliveryDate($validDate);

        $this->assertTrue($isValid);
    }

    public function testIsValidDeliveryDateWithLessThan48Hours(): void
    {
        // Date dans 24 heures (invalide car < 48h)
        $invalidDate = new \DateTimeImmutable('+24 hours');

        $isValid = $this->orderManager->isValidDeliveryDate($invalidDate);

        $this->assertFalse($isValid);
    }

    public function testIsValidDeliveryDateWithPastDate(): void
    {
        // Date dans le passé (invalide)
        $invalidDate = new \DateTimeImmutable('-1 day');

        $isValid = $this->orderManager->isValidDeliveryDate($invalidDate);

        $this->assertFalse($isValid);
    }

    // ========================
    // Tests de changement de statut
    // ========================

    public function testChangeOrderStatusCallsFlush(): void
    {
        $order = new Order();
        $order->initialize();

        // Vérifier que flush() est appelé
        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->orderManager->changeOrderStatus($order, OrderStatus::VALIDATED);

        // Vérifier que le statut a changé
        $this->assertEquals(OrderStatus::VALIDATED, $order->getStatus());
    }

    // ========================
    // Tests d'annulation
    // ========================

    public function testCancelOrderSuccessfully(): void
    {
        $order = new Order();
        $order->initialize();
        $order->setStatus(OrderStatus::PENDING);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->orderManager->cancelOrder($order, 'Client a annulé');

        $this->assertEquals(OrderStatus::CANCELLED, $order->getStatus());
        $this->assertEquals('Client a annulé', $order->getCancellationReason());
        $this->assertNotNull($order->getCancelledAt());
    }

    public function testCancelOrderThrowsExceptionWhenNotCancellable(): void
    {
        $order = new Order();
        $order->initialize();
        $order->setStatus(OrderStatus::COMPLETED); // Statut final, non annulable

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cette commande ne peut plus être annulée');

        $this->orderManager->cancelOrder($order, 'Tentative d\'annulation');
    }

    // ========================
    // Tests de sauvegarde
    // ========================

    public function testSaveOrderCallsPersistAndFlush(): void
    {
        $order = new Order();

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($order);

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->orderManager->saveOrder($order);
    }
}
<?php

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de l'entité Order
 * Teste les calculs de prix, les changements de statut, et la logique métier
 */
class OrderTest extends TestCase
{
    private Order $order;

    protected function setUp(): void
    {
        $this->order = new Order();
    }

    // ========================
    // Tests d'initialisation
    // ========================

    public function testInitializeSetDefaultValues(): void
    {
        $this->order->initialize();

        $this->assertNotNull($this->order->getCreatedAt());
        $this->assertNotNull($this->order->getUpdatedAt());
        $this->assertEquals(OrderStatus::PENDING, $this->order->getStatus());
        $this->assertNotNull($this->order->getOrderNumber());
        $this->assertFalse($this->order->hasMaterialLoan());
        $this->assertFalse($this->order->isMaterialReturned());
    }

    public function testGenerateOrderNumberFormat(): void
    {
        $this->order->setCreatedAt(new \DateTimeImmutable('2025-01-25'));
        $this->order->generateOrderNumber();

        $orderNumber = $this->order->getOrderNumber();
        $this->assertMatchesRegularExpression('/^ORD-20250125-\d{5}$/', $orderNumber);
    }

    // ========================
    // Tests de calcul de prix
    // ========================

    public function testCalculateMenuSubtotal(): void
    {
        $this->order->setMenuPricePerPerson(5000); // 50€
        $this->order->setNumberOfPersons(10);

        $subtotal = $this->order->calculateMenuSubtotal();

        $this->assertEquals(50000, $subtotal); // 10 × 50€ = 500€
    }

    public function testCalculateDeliveryCostInBordeaux(): void
    {
        $cost = $this->order->calculateDeliveryCost(true);

        $this->assertEquals(500, $cost); // 5€ fixe pour Bordeaux
    }

    public function testCalculateDeliveryCostOutsideBordeaux(): void
    {
        $cost = $this->order->calculateDeliveryCost(false, 20);

        // 5€ base + (20km × 0.59€) = 5€ + 11.80€ = 16.80€ = 1680 centimes
        $this->assertEquals(1680, $cost);
    }

    public function testCalculateDeliveryCostOutsideBordeauxZeroDistance(): void
    {
        $cost = $this->order->calculateDeliveryCost(false, 0);

        $this->assertEquals(500, $cost); // 5€ base seulement
    }

    public function testCalculateDiscountWithFiveExtraPersons(): void
    {
        $this->order->setMenuPricePerPerson(5000); // 50€
        $this->order->setNumberOfPersons(10); // 10 personnes
        $this->order->setMenuSubtotal(50000); // 500€

        $discount = $this->order->calculateDiscount(5); // Menu min = 5 personnes

        // 10 - 5 = 5 personnes supplémentaires → 10% de réduction
        // 500€ × 10% = 50€ = 5000 centimes
        $this->assertEquals(5000, $discount);
    }

    public function testCalculateDiscountWithMoreThanFiveExtraPersons(): void
    {
        $this->order->setMenuPricePerPerson(5000); // 50€
        $this->order->setNumberOfPersons(15); // 15 personnes
        $this->order->setMenuSubtotal(75000); // 750€

        $discount = $this->order->calculateDiscount(5); // Menu min = 5

        // 15 - 5 = 10 personnes supplémentaires → 10% de réduction
        // 750€ × 10% = 75€ = 7500 centimes
        $this->assertEquals(7500, $discount);
    }

    public function testCalculateDiscountWithLessThanFiveExtraPersons(): void
    {
        $this->order->setMenuPricePerPerson(5000); // 50€
        $this->order->setNumberOfPersons(8); // 8 personnes
        $this->order->setMenuSubtotal(40000); // 400€

        $discount = $this->order->calculateDiscount(5); // Menu min = 5

        // 8 - 5 = 3 personnes supplémentaires → pas de réduction
        $this->assertNull($discount);
    }

    public function testCalculateDiscountWithExactlyMinimumPersons(): void
    {
        $this->order->setMenuPricePerPerson(5000);
        $this->order->setNumberOfPersons(5);
        $this->order->setMenuSubtotal(25000);

        $discount = $this->order->calculateDiscount(5);

        // 5 - 5 = 0 personnes supplémentaires → pas de réduction
        $this->assertNull($discount);
    }

    public function testCalculateTotalPrice(): void
    {
        $this->order->setMenuSubtotal(50000); // 500€
        $this->order->setDeliveryCost(500);   // 5€
        $this->order->setDiscountAmount(5000); // 50€

        $total = $this->order->calculateTotalPrice();

        // 500€ + 5€ - 50€ = 455€ = 45500 centimes
        $this->assertEquals(45500, $total);
    }

    public function testCalculateTotalPriceWithoutDiscount(): void
    {
        $this->order->setMenuSubtotal(50000); // 500€
        $this->order->setDeliveryCost(500);   // 5€
        $this->order->setDiscountAmount(null);

        $total = $this->order->calculateTotalPrice();

        // 500€ + 5€ = 505€ = 50500 centimes
        $this->assertEquals(50500, $total);
    }

    // ========================
    // Tests de changement de statut
    // ========================

    public function testChangeStatusUpdatesStatusAndHistory(): void
    {
        $this->order->initialize();

        $this->order->changeStatus(OrderStatus::VALIDATED);

        $this->assertEquals(OrderStatus::VALIDATED, $this->order->getStatus());

        $history = $this->order->getStatusHistory();
        $this->assertCount(2, $history); // PENDING + VALIDATED
        $this->assertEquals('validated', $history[1]['status']);
        $this->assertEquals('Validée', $history[1]['label']);
    }

    public function testChangeStatusToValidatedSetsAcceptedAt(): void
    {
        $this->order->initialize();
        $this->assertNull($this->order->getAcceptedAt());

        $this->order->changeStatus(OrderStatus::VALIDATED);

        $this->assertNotNull($this->order->getAcceptedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->order->getAcceptedAt());
    }

    public function testChangeStatusToCompletedSetsCompletedAt(): void
    {
        $this->order->initialize();
        $this->assertNull($this->order->getCompletedAt());

        $this->order->changeStatus(OrderStatus::COMPLETED);

        $this->assertNotNull($this->order->getCompletedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->order->getCompletedAt());
    }

    public function testChangeStatusToCancelledSetsCancelledAt(): void
    {
        $this->order->initialize();
        $this->assertNull($this->order->getCancelledAt());

        $this->order->changeStatus(OrderStatus::CANCELLED);

        $this->assertNotNull($this->order->getCancelledAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->order->getCancelledAt());
    }

    public function testChangeStatusAlwaysUpdatesUpdatedAt(): void
    {
        $this->order->initialize();
        $originalUpdatedAt = $this->order->getUpdatedAt();

        // Attendre 1 seconde pour être sûr que la date change
        sleep(1);

        $this->order->changeStatus(OrderStatus::VALIDATED);

        $this->assertNotEquals($originalUpdatedAt, $this->order->getUpdatedAt());
    }

    // ========================
    // Tests des règles métier
    // ========================

    public function testCanBeCancelledWhenPending(): void
    {
        $this->order->setStatus(OrderStatus::PENDING);

        $this->assertTrue($this->order->canBeCancelled());
    }

    public function testCanBeCancelledWhenValidated(): void
    {
        $this->order->setStatus(OrderStatus::VALIDATED);

        $this->assertTrue($this->order->canBeCancelled());
    }

    public function testCanBeCancelledWhenPreparing(): void
    {
        $this->order->setStatus(OrderStatus::PREPARING);

        $this->assertTrue($this->order->canBeCancelled());
    }

    public function testCannotBeCancelledWhenReady(): void
    {
        $this->order->setStatus(OrderStatus::READY);

        $this->assertFalse($this->order->canBeCancelled());
    }

    public function testCannotBeCancelledWhenDelivering(): void
    {
        $this->order->setStatus(OrderStatus::DELIVERING);

        $this->assertFalse($this->order->canBeCancelled());
    }

    public function testCannotBeCancelledWhenCompleted(): void
    {
        $this->order->setStatus(OrderStatus::COMPLETED);

        $this->assertFalse($this->order->canBeCancelled());
    }

    public function testCanBeEditedOnlyWhenPending(): void
    {
        $this->order->setStatus(OrderStatus::PENDING);
        $this->assertTrue($this->order->canBeEdited());

        $this->order->setStatus(OrderStatus::VALIDATED);
        $this->assertFalse($this->order->canBeEdited());

        $this->order->setStatus(OrderStatus::COMPLETED);
        $this->assertFalse($this->order->canBeEdited());
    }

    public function testCanReceiveReviewOnlyWhenCompleted(): void
    {
        $this->order->setStatus(OrderStatus::PENDING);
        $this->assertFalse($this->order->canReceiveReview());

        $this->order->setStatus(OrderStatus::VALIDATED);
        $this->assertFalse($this->order->canReceiveReview());

        $this->order->setStatus(OrderStatus::COMPLETED);
        $this->assertTrue($this->order->canReceiveReview());
    }

    // ========================
    // Tests getters/setters
    // ========================

    public function testGetSetCustomerInformation(): void
    {
        $this->order->setCustomerFirstname('Jean');
        $this->order->setCustomerLastname('Dupont');
        $this->order->setCustomerEmail('jean.dupont@example.com');
        $this->order->setCustomerPhone('0612345678');

        $this->assertEquals('Jean', $this->order->getCustomerFirstname());
        $this->assertEquals('Dupont', $this->order->getCustomerLastname());
        $this->assertEquals('jean.dupont@example.com', $this->order->getCustomerEmail());
        $this->assertEquals('0612345678', $this->order->getCustomerPhone());
    }

    public function testGetSetDeliveryInformation(): void
    {
        $deliveryDate = new \DateTimeImmutable('2025-02-01 18:00:00');

        $this->order->setDeliveryAddress('10 Rue Example, 33000 Bordeaux');
        $this->order->setDeliveryDateTime($deliveryDate);
        $this->order->setDeliveryDistanceKm(15);

        $this->assertEquals('10 Rue Example, 33000 Bordeaux', $this->order->getDeliveryAddress());
        $this->assertEquals($deliveryDate, $this->order->getDeliveryDateTime());
        $this->assertEquals(15, $this->order->getDeliveryDistanceKm());
    }

    public function testGetSetMenuInformation(): void
    {
        $this->order->setMenuName('Menu Découverte');
        $this->order->setMenuPricePerPerson(5000);
        $this->order->setNumberOfPersons(10);

        $this->assertEquals('Menu Découverte', $this->order->getMenuName());
        $this->assertEquals(5000, $this->order->getMenuPricePerPerson());
        $this->assertEquals(10, $this->order->getNumberOfPersons());
    }

    public function testGetSetMaterialLoanInformation(): void
    {
        $deadline = new \DateTimeImmutable('2025-02-11');

        $this->order->setHasMaterialLoan(true);
        $this->order->setMaterialReturnDeadline($deadline);
        $this->order->setMaterialReturned(false);

        $this->assertTrue($this->order->hasMaterialLoan());
        $this->assertEquals($deadline, $this->order->getMaterialReturnDeadline());
        $this->assertFalse($this->order->isMaterialReturned());
    }

    public function testGetSetCancellationInformation(): void
    {
        $cancelledAt = new \DateTimeImmutable('2025-01-26 14:30:00');

        $this->order->setCancellationReason('Client a demandé l\'annulation');
        $this->order->setCancelledAt($cancelledAt);

        $this->assertEquals('Client a demandé l\'annulation', $this->order->getCancellationReason());
        $this->assertEquals($cancelledAt, $this->order->getCancelledAt());
    }
}
<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Order;
use App\Entity\User;
use App\Enum\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur OrderAdmin (côté employé/admin)
 */
class OrderAdminControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private ?User $employeeUser = null;
    private ?User $customerUser = null;
    private ?Order $testOrder = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->createEmployeeUser();
        $this->createCustomerUser();
    }

    protected function tearDown(): void
    {
        // Nettoyer les commandes de test
        if ($this->testOrder && $this->entityManager && $this->entityManager->isOpen()) {
            try {
                // Récupérer l'entité depuis la base de données si elle existe encore
                $orderId = $this->testOrder->getId();
                if ($orderId) {
                    $order = $this->entityManager->getRepository(Order::class)->find($orderId);
                    if ($order) {
                        $this->entityManager->remove($order);
                        $this->entityManager->flush();
                    }
                }
            } catch (\Exception $e) {
                // Ignorer les erreurs de nettoyage
            }
        }

        parent::tearDown();
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
    }

    private function createEmployeeUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->employeeUser = $userRepository->findOneBy(['email' => 'test.employee@example.com']);

        if (!$this->employeeUser) {
            $this->employeeUser = new User();
            $this->employeeUser->setEmail('test.employee@example.com');
            $this->employeeUser->setFirstname('Employee');
            $this->employeeUser->setLastname('Test');
            $this->employeeUser->setRoles(['ROLE_EMPLOYEE']);
            $this->employeeUser->setPassword('$2y$13$test');

            $this->entityManager->persist($this->employeeUser);
            $this->entityManager->flush();
        }
    }

    private function createCustomerUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->customerUser = $userRepository->findOneBy(['email' => 'test.customer@example.com']);

        if (!$this->customerUser) {
            $this->customerUser = new User();
            $this->customerUser->setEmail('test.customer@example.com');
            $this->customerUser->setFirstname('Customer');
            $this->customerUser->setLastname('Test');
            $this->customerUser->setRoles(['ROLE_USER']);
            $this->customerUser->setPassword('$2y$13$test');

            $this->entityManager->persist($this->customerUser);
            $this->entityManager->flush();
        }
    }

    private function createTestOrder(OrderStatus $status = OrderStatus::PENDING): Order
    {
        $order = new Order();
        $order->setUser($this->customerUser);
        $order->setCustomerFirstname('Test');
        $order->setCustomerLastname('Customer');
        $order->setCustomerEmail('test@example.com');
        $order->setCustomerPhone('0612345678');
        $order->setDeliveryAddress('10 Rue Test, 33000 Bordeaux');
        $order->setDeliveryDateTime(new \DateTimeImmutable('+5 days'));
        $order->setMenuName('Menu Test');
        $order->setMenuPricePerPerson(5000);
        $order->setNumberOfPersons(10);
        $order->setMenuSubtotal(50000);
        $order->setDeliveryCost(500);
        $order->setDiscountAmount(null);
        $order->setTotalPrice(50500);
        $order->setHasMaterialLoan(false);
        $order->initialize();

        if ($status !== OrderStatus::PENDING) {
            $order->changeStatus($status);
        }

        $this->entityManager->persist($order);
        $this->entityManager->flush();

        $this->testOrder = $order;

        return $order;
    }

    // ========================
    // Tests d'accès et liste
    // ========================

    public function testOrderAdminIndexRequiresEmployeeRole(): void
    {
        $this->client->request('GET', '/admin/orders');
        $this->assertResponseRedirects('/connexion');
    }

    public function testOrderAdminIndexDeniesRegularUsers(): void
    {
        $this->client->loginUser($this->customerUser);
        $this->client->request('GET', '/admin/orders');

        $this->assertResponseStatusCodeSame(403); // Access Denied
    }

    public function testOrderAdminIndexIsAccessibleForEmployee(): void
    {
        $this->client->loginUser($this->employeeUser);
        $this->client->request('GET', '/admin/orders');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des Commandes');
    }

    public function testOrderAdminIndexDisplaysStatistics(): void
    {
        $this->createTestOrder();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('GET', '/admin/orders');

        $this->assertResponseIsSuccessful();
        // Vérifier que les statistiques sont affichées dans les cartes
        $this->assertSelectorExists('.card');
        $this->assertSelectorTextContains('body', 'Total');
        $this->assertSelectorTextContains('body', 'En attente');
    }

    public function testOrderAdminIndexFilterByStatus(): void
    {
        $this->createTestOrder(OrderStatus::VALIDATED);

        $this->client->loginUser($this->employeeUser);
        $this->client->request('GET', '/admin/orders?status=validated');

        $this->assertResponseIsSuccessful();
    }

    public function testOrderAdminIndexSearchByOrderNumber(): void
    {
        $order = $this->createTestOrder();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('GET', '/admin/orders?search=' . $order->getOrderNumber());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $order->getOrderNumber());
    }

    // ========================
    // Tests de consultation de commande
    // ========================

    public function testOrderAdminShowRequiresEmployeeRole(): void
    {
        $order = $this->createTestOrder();

        $this->client->request('GET', '/admin/orders/' . $order->getId());
        $this->assertResponseRedirects('/connexion');
    }

    public function testOrderAdminShowIsAccessibleForEmployee(): void
    {
        $order = $this->createTestOrder();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('GET', '/admin/orders/' . $order->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $order->getOrderNumber());
    }

    // ========================
    // Tests de changement de statut
    // ========================

    public function testChangeOrderStatusSuccessfully(): void
    {
        $order = $this->createTestOrder(OrderStatus::PENDING);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/change-status', [
            'status' => 'validated'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifier le flash message
        $this->assertSelectorExists('.alert-success');

        // Récupérer à nouveau la commande depuis la base de données
        $updatedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertEquals(OrderStatus::VALIDATED, $updatedOrder->getStatus());
    }

    public function testChangeOrderStatusFailsWithInvalidTransition(): void
    {
        $order = $this->createTestOrder(OrderStatus::PENDING);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/change-status', [
            'status' => 'completed' // Transition invalide depuis PENDING
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifier le message d'erreur (le contrôleur utilise 'error' pas 'danger')
        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'Impossible de passer');
    }

    public function testChangeOrderStatusFailsWithInvalidStatus(): void
    {
        $order = $this->createTestOrder();
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/change-status', [
            'status' => 'invalid_status'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'Statut invalide');
    }

    public function testCannotCompleteOrderWithUnreturnedMaterial(): void
    {
        $order = $this->createTestOrder(OrderStatus::DELIVERED);
        $order->setHasMaterialLoan(true);
        $order->setMaterialReturned(false);
        $this->entityManager->flush();

        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/change-status', [
            'status' => 'completed'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifier le message d'erreur (le contrôleur utilise 'error' pas 'danger')
        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'matériel prêté n\'a pas encore été retourné');

        // Vérifier que le statut n'a PAS changé
        $updatedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertEquals(OrderStatus::DELIVERED, $updatedOrder->getStatus());
    }

    // ========================
    // Tests de retour de matériel
    // ========================

    public function testMarkMaterialReturnedSuccessfully(): void
    {
        $order = $this->createTestOrder(OrderStatus::WAITING_MATERIAL_RETURN);
        $order->setHasMaterialLoan(true);
        $order->setMaterialReturned(false);
        $this->entityManager->flush();

        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/mark-material-returned');

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifier le flash message
        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'retourné');

        // Vérifier que le matériel est marqué comme retourné
        $updatedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertTrue($updatedOrder->isMaterialReturned());
        $this->assertEquals(OrderStatus::COMPLETED, $updatedOrder->getStatus());
    }

    public function testMarkMaterialReturnedFailsWithoutMaterialLoan(): void
    {
        $order = $this->createTestOrder(OrderStatus::DELIVERED);
        $order->setHasMaterialLoan(false);
        $this->entityManager->flush();

        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/mark-material-returned');

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'ne comporte pas de prêt de matériel');
    }

    public function testMarkMaterialReturnedFailsWithWrongStatus(): void
    {
        $order = $this->createTestOrder(OrderStatus::DELIVERED);
        $order->setHasMaterialLoan(true);
        $this->entityManager->flush();

        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/mark-material-returned');

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'n\'est pas en attente de retour de matériel');
    }

    // ========================
    // Tests d'annulation par admin
    // ========================

    public function testCancelOrderWithContactMethodSuccessfully(): void
    {
        $order = $this->createTestOrder(OrderStatus::VALIDATED);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/cancel', [
            'contact_method' => 'phone',
            'reason' => 'Client ne peut plus recevoir la commande'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Vérifier le flash message
        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'annulée');
        $this->assertSelectorTextContains('.alert-success', 'téléphonique');

        // Vérifier que la commande est annulée
        $updatedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertEquals(OrderStatus::CANCELLED, $updatedOrder->getStatus());
        $this->assertStringContainsString('Appel téléphonique', $updatedOrder->getCancellationReason());
        $this->assertStringContainsString('Client ne peut plus recevoir', $updatedOrder->getCancellationReason());
    }

    public function testCancelOrderFailsWithoutContactMethod(): void
    {
        $order = $this->createTestOrder(OrderStatus::PENDING);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/cancel', [
            'reason' => 'Test'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'mode de contact');
    }

    public function testCancelOrderFailsWithInvalidContactMethod(): void
    {
        $order = $this->createTestOrder(OrderStatus::PENDING);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/cancel', [
            'contact_method' => 'invalid',
            'reason' => 'Test'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'Mode de contact invalide');
    }

    public function testCancelOrderFailsWithoutReason(): void
    {
        $order = $this->createTestOrder(OrderStatus::PENDING);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/cancel', [
            'contact_method' => 'email'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'motif');
    }

    public function testCancelOrderFailsWhenNotCancellable(): void
    {
        $order = $this->createTestOrder(OrderStatus::COMPLETED);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/cancel', [
            'contact_method' => 'phone',
            'reason' => 'Test'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-error');
        $this->assertSelectorTextContains('.alert-error', 'ne peut plus être annulée');
    }

    public function testCancelOrderWithEmailContactMethod(): void
    {
        $order = $this->createTestOrder(OrderStatus::PREPARING);
        $orderId = $order->getId();

        $this->client->loginUser($this->employeeUser);
        $this->client->request('POST', '/admin/orders/' . $orderId . '/cancel', [
            'contact_method' => 'email',
            'reason' => 'Problème d\'approvisionnement'
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'email');

        // Vérifier le contenu du motif d'annulation
        $updatedOrder = $this->entityManager->getRepository(Order::class)->find($orderId);
        $this->assertStringContainsString('Email', $updatedOrder->getCancellationReason());
        $this->assertStringContainsString('Problème d\'approvisionnement', $updatedOrder->getCancellationReason());
    }

    // ========================
    // Tests de sécurité
    // ========================

    public function testRegularUserCannotAccessAdminRoutes(): void
    {
        $order = $this->createTestOrder();

        $this->client->loginUser($this->customerUser);

        // Liste
        $this->client->request('GET', '/admin/orders');
        $this->assertResponseStatusCodeSame(403);

        // Détail
        $this->client->request('GET', '/admin/orders/' . $order->getId());
        $this->assertResponseStatusCodeSame(403);

        // Changement de statut
        $this->client->request('POST', '/admin/orders/' . $order->getId() . '/change-status', [
            'status' => 'validated'
        ]);
        $this->assertResponseStatusCodeSame(403);

        // Annulation
        $this->client->request('POST', '/admin/orders/' . $order->getId() . '/cancel', [
            'contact_method' => 'phone',
            'reason' => 'Test'
        ]);
        $this->assertResponseStatusCodeSame(403);
    }
}
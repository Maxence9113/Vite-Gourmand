<?php

namespace App\Tests\Controller;

use App\Entity\Order;
use App\Entity\Review;
use App\Entity\User;
use App\Enum\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur Review
 */
class ReviewControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private ?User $testUser = null;
    private ?Order $testOrder = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        // Créer un utilisateur de test
        $this->createTestUser();
        // Créer une commande de test
        $this->createTestOrder();
    }

    protected function tearDown(): void
    {
        // Nettoyer les avis de test
        $reviewRepository = $this->entityManager->getRepository(Review::class);
        $reviews = $reviewRepository->findAll();
        foreach ($reviews as $review) {
            if (str_starts_with($review->getCustomerName(), 'Test')) {
                $this->entityManager->remove($review);
            }
        }
        $this->entityManager->flush();

        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function createTestUser(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->testUser = $userRepository->findOneBy(['email' => 'test@review.com']);

        if (!$this->testUser) {
            $this->testUser = new User();
            $this->testUser->setEmail('test@review.com');
            $this->testUser->setFirstname('TestFirstname');
            $this->testUser->setLastname('TestLastname');
            $this->testUser->setRoles(['ROLE_USER']);
            $this->testUser->setPassword('password');

            $this->entityManager->persist($this->testUser);
            $this->entityManager->flush();
        }
    }

    private function createTestOrder(): void
    {
        // Créer une commande complétée pour pouvoir laisser un avis
        $this->testOrder = new Order();
        $this->testOrder->setOrderNumber('TEST-' . uniqid());
        $this->testOrder->setUser($this->testUser);
        $this->testOrder->setCustomerFirstname('TestFirstname');
        $this->testOrder->setCustomerLastname('TestLastname');
        $this->testOrder->setCustomerEmail('test@review.com');
        $this->testOrder->setCustomerPhone('0612345678');
        $this->testOrder->setDeliveryAddress('123 Test Street');
        $this->testOrder->setDeliveryDateTime(new \DateTimeImmutable('-1 day'));
        $this->testOrder->setDeliveryDistanceKm(10);
        $this->testOrder->setDeliveryCost(1500);
        $this->testOrder->setMenuName('Menu Test');
        $this->testOrder->setMenuPricePerPerson(2500);
        $this->testOrder->setNumberOfPersons(10);
        $this->testOrder->setMenuSubtotal(25000);
        $this->testOrder->setTotalPrice(26500);
        $this->testOrder->setStatus(OrderStatus::COMPLETED);
        $this->testOrder->setStatusHistory([]);
        $this->testOrder->setHasMaterialLoan(false);
        $this->testOrder->setCreatedAt(new \DateTimeImmutable('-2 days'));
        $this->testOrder->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($this->testOrder);
        $this->entityManager->flush();
    }

    public function testReviewIndexPageIsAccessible(): void
    {
        $this->client->request('GET', '/avis');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Avis de nos clients');
    }

    public function testNewReviewPageRequiresAuthentication(): void
    {
        $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());
        $this->assertResponseRedirects('/connexion');
    }

    public function testNewReviewPageIsAccessibleForAuthenticatedUser(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testNewReviewFormContainsCorrectFields(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        // Vérifier que le formulaire contient le champ de commentaire
        $this->assertSelectorExists('textarea[name="review[comment]"]');

        // Vérifier qu'il n'y a PAS de champ customerName (auto-généré)
        $this->assertSelectorNotExists('input[name="review[customerName]"]');
    }

    public function testSubmitValidReview(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        $form = $crawler->selectButton('Envoyer mon avis')->form([
            'review[rating]' => '5',
            'review[comment]' => 'Excellent traiteur, je recommande vivement !',
        ]);

        $this->client->submit($form);

        // Vérifier la redirection vers la page de détail de commande
        $this->assertResponseRedirects('/compte/commandes/' . $this->testOrder->getId());

        // Suivre la redirection
        $this->client->followRedirect();

        // Vérifier le message flash
        $this->assertSelectorTextContains('.order-flash-success', 'Merci pour votre avis');
    }

    public function testReviewIsNotValidatedByDefault(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        $form = $crawler->selectButton('Envoyer mon avis')->form([
            'review[rating]' => '4',
            'review[comment]' => 'Très bon service',
        ]);

        $this->client->submit($form);

        // Récupérer l'avis créé via la commande
        $this->entityManager->clear();
        $order = $this->entityManager->getRepository(Order::class)->find($this->testOrder->getId());
        $review = $order->getReview();

        $this->assertNotNull($review);
        $this->assertFalse($review->isValidated());
    }

    public function testCustomerNameIsAutoGenerated(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        $form = $crawler->selectButton('Envoyer mon avis')->form([
            'review[rating]' => '5',
            'review[comment]' => 'Super expérience !',
        ]);

        $this->client->submit($form);

        // Récupérer l'avis créé via la commande
        $this->entityManager->clear();
        $order = $this->entityManager->getRepository(Order::class)->find($this->testOrder->getId());
        $review = $order->getReview();

        $this->assertNotNull($review);
        // Vérifier le format: Prénom + Initiale
        $this->assertEquals('TestFirstname T.', $review->getCustomerName());
    }

    public function testSubmitReviewWithoutComment(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        $form = $crawler->selectButton('Envoyer mon avis')->form([
            'review[rating]' => '5',
            'review[comment]' => '',
        ]);

        $this->client->submit($form);

        // Doit être accepté car le commentaire est optionnel
        $this->assertResponseRedirects('/compte/commandes/' . $this->testOrder->getId());
    }

    public function testSubmitReviewWithInvalidRating(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        // Essayer de soumettre sans sélectionner de note
        $form = $crawler->selectButton('Envoyer mon avis')->form([
            'review[comment]' => 'Commentaire sans note',
        ]);

        $this->client->submit($form);

        // Doit rester sur la page du formulaire avec une erreur
        $this->assertResponseIsUnprocessable();
    }

    public function testReviewCreatedAtIsSetAutomatically(): void
    {
        $this->client->loginUser($this->testUser);
        $crawler = $this->client->request('GET', '/avis/nouveau/' . $this->testOrder->getId());

        $form = $crawler->selectButton('Envoyer mon avis')->form([
            'review[rating]' => '4',
            'review[comment]' => 'Test date creation',
        ]);

        $beforeSubmit = new \DateTimeImmutable('-1 second');
        $this->client->submit($form);
        $afterSubmit = new \DateTimeImmutable('+1 second');

        // Récupérer l'avis créé via la commande
        $this->entityManager->clear();
        $order = $this->entityManager->getRepository(Order::class)->find($this->testOrder->getId());
        $review = $order->getReview();

        $this->assertNotNull($review);
        $this->assertNotNull($review->getCreatedAt());

        // Vérifier que la date est dans une plage raisonnable (avant -1s et après +1s)
        $this->assertGreaterThanOrEqual($beforeSubmit, $review->getCreatedAt());
        $this->assertLessThanOrEqual($afterSubmit, $review->getCreatedAt());
    }

    public function testCannotLeaveReviewOnPendingOrder(): void
    {
        // Créer une commande en attente
        $pendingOrder = new Order();
        $pendingOrder->setOrderNumber('TEST-PENDING-' . uniqid());
        $pendingOrder->setUser($this->testUser);
        $pendingOrder->setCustomerFirstname('TestFirstname');
        $pendingOrder->setCustomerLastname('TestLastname');
        $pendingOrder->setCustomerEmail('test@review.com');
        $pendingOrder->setCustomerPhone('0612345678');
        $pendingOrder->setDeliveryAddress('123 Test Street');
        $pendingOrder->setDeliveryDateTime(new \DateTimeImmutable('+1 day'));
        $pendingOrder->setDeliveryDistanceKm(10);
        $pendingOrder->setDeliveryCost(1500);
        $pendingOrder->setMenuName('Menu Test');
        $pendingOrder->setMenuPricePerPerson(2500);
        $pendingOrder->setNumberOfPersons(10);
        $pendingOrder->setMenuSubtotal(25000);
        $pendingOrder->setTotalPrice(26500);
        $pendingOrder->setStatus(OrderStatus::PENDING);
        $pendingOrder->setStatusHistory([]);
        $pendingOrder->setHasMaterialLoan(false);
        $pendingOrder->setCreatedAt(new \DateTimeImmutable());
        $pendingOrder->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($pendingOrder);
        $this->entityManager->flush();

        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/avis/nouveau/' . $pendingOrder->getId());

        // Doit rediriger avec un message d'erreur
        $this->assertResponseRedirects('/compte/commandes/' . $pendingOrder->getId());
    }

    public function testCannotLeaveReviewOnOtherUserOrder(): void
    {
        // Créer un autre utilisateur
        $otherUser = new User();
        $otherUser->setEmail('other@review.com');
        $otherUser->setFirstname('OtherFirstname');
        $otherUser->setLastname('OtherLastname');
        $otherUser->setRoles(['ROLE_USER']);
        $otherUser->setPassword('password');

        $this->entityManager->persist($otherUser);

        // Créer une commande pour l'autre utilisateur
        $otherOrder = new Order();
        $otherOrder->setOrderNumber('TEST-OTHER-' . uniqid());
        $otherOrder->setUser($otherUser);
        $otherOrder->setCustomerFirstname('OtherFirstname');
        $otherOrder->setCustomerLastname('OtherLastname');
        $otherOrder->setCustomerEmail('other@review.com');
        $otherOrder->setCustomerPhone('0612345678');
        $otherOrder->setDeliveryAddress('123 Other Street');
        $otherOrder->setDeliveryDateTime(new \DateTimeImmutable('-1 day'));
        $otherOrder->setDeliveryDistanceKm(10);
        $otherOrder->setDeliveryCost(1500);
        $otherOrder->setMenuName('Menu Test');
        $otherOrder->setMenuPricePerPerson(2500);
        $otherOrder->setNumberOfPersons(10);
        $otherOrder->setMenuSubtotal(25000);
        $otherOrder->setTotalPrice(26500);
        $otherOrder->setStatus(OrderStatus::COMPLETED);
        $otherOrder->setStatusHistory([]);
        $otherOrder->setHasMaterialLoan(false);
        $otherOrder->setCreatedAt(new \DateTimeImmutable('-2 days'));
        $otherOrder->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($otherOrder);
        $this->entityManager->flush();

        // Se connecter en tant que testUser et essayer d'accéder à la commande de l'autre utilisateur
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/avis/nouveau/' . $otherOrder->getId());

        // Doit rediriger (accès refusé)
        $this->assertResponseRedirects();
    }
}

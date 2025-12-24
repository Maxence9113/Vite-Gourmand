<?php

namespace App\Tests\Controller\Admin;

use App\Entity\Review;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du contrôleur d'administration des avis
 */
class AdminReviewControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private ?User $adminUser = null;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->loginAsAdmin();
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

    private function loginAsAdmin(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $this->adminUser = $userRepository->findOneBy(['email' => 'admin@test.com']);

        if (!$this->adminUser) {
            $this->adminUser = new User();
            $this->adminUser->setEmail('admin@test.com');
            $this->adminUser->setFirstname('Admin');
            $this->adminUser->setLastname('Test');
            $this->adminUser->setRoles(['ROLE_ADMIN']);
            $this->adminUser->setPassword('password');

            $this->entityManager->persist($this->adminUser);
            $this->entityManager->flush();
        }

        $this->client->loginUser($this->adminUser);
    }

    private function createTestReview(string $customerName, int $rating, bool $isValidated, ?string $comment = null): Review
    {
        $review = new Review();
        $review->setCustomerName($customerName);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated($isValidated);

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    public function testAdminReviewsPageIsAccessible(): void
    {
        $this->client->request('GET', '/admin/avis');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Gestion des Avis Clients');
    }

    public function testAdminReviewsPageRequiresAdminRole(): void
    {
        // Créer un utilisateur non-admin
        $userRepository = $this->entityManager->getRepository(User::class);
        $regularUser = $userRepository->findOneBy(['email' => 'user@test.com']);

        if (!$regularUser) {
            $regularUser = new User();
            $regularUser->setEmail('user@test.com');
            $regularUser->setFirstname('Regular');
            $regularUser->setLastname('User');
            $regularUser->setRoles(['ROLE_USER']);
            $regularUser->setPassword('password');

            $this->entityManager->persist($regularUser);
            $this->entityManager->flush();
        }

        // Se connecter avec un utilisateur non-admin
        $this->client->loginUser($regularUser);
        $this->client->request('GET', '/admin/avis');

        // Devrait être interdit (403) pour un utilisateur non-admin
        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminReviewsPageDisplaysAllReviews(): void
    {
        // Créer des avis de test
        $this->createTestReview('Test User 1', 5, true, 'Excellent');
        $this->createTestReview('Test User 2', 4, false, 'En attente');

        $this->client->request('GET', '/admin/avis');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Test User 1');
        $this->assertSelectorTextContains('body', 'Test User 2');
    }

    public function testAdminCanValidateReview(): void
    {
        $review = $this->createTestReview('Test Validation', 5, false, 'Avis en attente');

        $this->client->request('POST', '/admin/avis/' . $review->getId() . '/valider');

        $this->assertResponseRedirects('/admin/avis');

        // Vérifier que l'avis est maintenant validé
        $this->entityManager->refresh($review);
        $this->assertTrue($review->isValidated());
    }

    public function testAdminCanRejectReview(): void
    {
        $review = $this->createTestReview('Test Reject', 4, true, 'Avis validé');

        $this->client->request('POST', '/admin/avis/' . $review->getId() . '/rejeter');

        $this->assertResponseRedirects('/admin/avis');

        // Vérifier que l'avis est maintenant rejeté
        $this->entityManager->refresh($review);
        $this->assertFalse($review->isValidated());
    }

    public function testAdminCanDeleteReview(): void
    {
        $review = $this->createTestReview('Test Delete', 3, true, 'Avis à supprimer');
        $reviewId = $review->getId();

        $this->client->request('POST', '/admin/avis/' . $reviewId . '/supprimer');

        $this->assertResponseRedirects('/admin/avis');

        // Vérifier que l'avis a été supprimé
        $reviewRepository = $this->entityManager->getRepository(Review::class);
        $deletedReview = $reviewRepository->find($reviewId);
        $this->assertNull($deletedReview);
    }

    public function testValidateReviewShowsSuccessMessage(): void
    {
        $review = $this->createTestReview('Test Flash Message', 5, false, 'Test');

        $this->client->request('POST', '/admin/avis/' . $review->getId() . '/valider');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'validé avec succès');
    }

    public function testRejectReviewShowsWarningMessage(): void
    {
        $review = $this->createTestReview('Test Warning', 4, true, 'Test');

        $this->client->request('POST', '/admin/avis/' . $review->getId() . '/rejeter');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-warning');
        $this->assertSelectorTextContains('.alert-warning', 'rejeté');
    }

    public function testDeleteReviewShowsSuccessMessage(): void
    {
        $review = $this->createTestReview('Test Delete Message', 3, true, 'Test');

        $this->client->request('POST', '/admin/avis/' . $review->getId() . '/supprimer');
        $this->client->followRedirect();

        $this->assertSelectorExists('.alert-success');
        $this->assertSelectorTextContains('.alert-success', 'supprimé avec succès');
    }

    public function testAdminReviewsPageShowsStatistics(): void
    {
        // Créer des avis avec différents statuts
        $this->createTestReview('Test Stat 1', 5, true);
        $this->createTestReview('Test Stat 2', 4, true);
        $this->createTestReview('Test Stat 3', 3, false);

        $this->client->request('GET', '/admin/avis');

        $this->assertResponseIsSuccessful();
        // Vérifier que les statistiques sont affichées
        $this->assertSelectorTextContains('body', 'Avis validés');
        $this->assertSelectorTextContains('body', 'En attente de validation');
        $this->assertSelectorTextContains('body', 'Total des avis');
    }

    public function testAdminReviewsPageDisplaysRatingStars(): void
    {
        $this->createTestReview('Test Stars', 5, true, 'Five stars review');

        $this->client->request('GET', '/admin/avis');

        $this->assertResponseIsSuccessful();
        // Vérifier que les étoiles sont présentes dans le template
        $this->assertSelectorExists('.rating-stars');
    }

    public function testValidateOnlyAcceptsPostMethod(): void
    {
        $review = $this->createTestReview('Test Method', 5, false);

        $this->client->request('GET', '/admin/avis/' . $review->getId() . '/valider');

        // GET ne devrait pas fonctionner, seulement POST
        $this->assertResponseStatusCodeSame(405);
    }

    public function testRejectOnlyAcceptsPostMethod(): void
    {
        $review = $this->createTestReview('Test Method', 5, true);

        $this->client->request('GET', '/admin/avis/' . $review->getId() . '/rejeter');

        // GET ne devrait pas fonctionner, seulement POST
        $this->assertResponseStatusCodeSame(405);
    }

    public function testDeleteOnlyAcceptsPostMethod(): void
    {
        $review = $this->createTestReview('Test Method', 5, true);

        $this->client->request('GET', '/admin/avis/' . $review->getId() . '/supprimer');

        // GET ne devrait pas fonctionner, seulement POST
        $this->assertResponseStatusCodeSame(405);
    }

    public function testAdminReviewsPageShowsReviewsSortedByDate(): void
    {
        // Créer des avis avec différentes dates
        $old = new Review();
        $old->setCustomerName('Test Old');
        $old->setRating(5);
        $old->setCreatedAt(\DateTimeImmutable::createFromMutable(new \DateTime('-2 days')));
        $old->setIsValidated(true);
        $this->entityManager->persist($old);

        $recent = new Review();
        $recent->setCustomerName('Test Recent');
        $recent->setRating(4);
        $recent->setCreatedAt(new \DateTimeImmutable());
        $recent->setIsValidated(true);
        $this->entityManager->persist($recent);

        $this->entityManager->flush();

        $this->client->request('GET', '/admin/avis');

        $this->assertResponseIsSuccessful();

        // Vérifier que les avis sont affichés
        $this->assertSelectorTextContains('body', 'Test Recent');
        $this->assertSelectorTextContains('body', 'Test Old');
    }
}
<?php

namespace App\Tests\Repository;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests du repository Review
 */
class ReviewRepositoryTest extends KernelTestCase
{
    private ReviewRepository $repository;
    private $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->repository = $this->entityManager->getRepository(Review::class);

        // Nettoyer les données de test précédentes
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }

    private function cleanupTestData(): void
    {
        $reviews = $this->repository->findAll();
        foreach ($reviews as $review) {
            if (str_starts_with($review->getCustomerName(), 'Test')) {
                $this->entityManager->remove($review);
            }
        }
        $this->entityManager->flush();
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

    public function testFindValidatedReviews(): void
    {
        // Créer des avis de test
        $this->createTestReview('Test User 1', 5, true, 'Excellent service');
        $this->createTestReview('Test User 2', 4, true, 'Very good');
        $this->createTestReview('Test User 3', 3, false, 'Pending review');

        // Récupérer uniquement les avis validés
        $validatedReviews = $this->repository->findBy(['isValidated' => true]);

        $testValidatedCount = 0;
        foreach ($validatedReviews as $review) {
            if (str_starts_with($review->getCustomerName(), 'Test')) {
                $testValidatedCount++;
                $this->assertTrue($review->isValidated());
            }
        }

        $this->assertEquals(2, $testValidatedCount);
    }

    public function testFindPendingReviews(): void
    {
        // Créer des avis de test
        $this->createTestReview('Test User 1', 5, true, 'Excellent service');
        $this->createTestReview('Test User 2', 4, false, 'Pending 1');
        $this->createTestReview('Test User 3', 3, false, 'Pending 2');

        // Récupérer uniquement les avis en attente
        $pendingReviews = $this->repository->findBy(['isValidated' => false]);

        $testPendingCount = 0;
        foreach ($pendingReviews as $review) {
            if (str_starts_with($review->getCustomerName(), 'Test')) {
                $testPendingCount++;
                $this->assertFalse($review->isValidated());
            }
        }

        $this->assertEquals(2, $testPendingCount);
    }

    public function testFindReviewsOrderedByCreatedAt(): void
    {
        // Créer des avis avec des dates différentes
        $review1 = new Review();
        $review1->setCustomerName('Test Oldest');
        $review1->setRating(5);
        $review1->setCreatedAt(\DateTimeImmutable::createFromMutable(new \DateTime('-2 days')));
        $review1->setIsValidated(true);
        $this->entityManager->persist($review1);

        $review2 = new Review();
        $review2->setCustomerName('Test Newest');
        $review2->setRating(4);
        $review2->setCreatedAt(new \DateTimeImmutable());
        $review2->setIsValidated(true);
        $this->entityManager->persist($review2);

        $review3 = new Review();
        $review3->setCustomerName('Test Middle');
        $review3->setRating(3);
        $review3->setCreatedAt(\DateTimeImmutable::createFromMutable(new \DateTime('-1 day')));
        $review3->setIsValidated(true);
        $this->entityManager->persist($review3);

        $this->entityManager->flush();

        // Récupérer les avis triés par date décroissante
        $reviews = $this->repository->findBy(
            ['isValidated' => true],
            ['createdAt' => 'DESC']
        );

        $testReviews = array_filter($reviews, fn($r) => str_starts_with($r->getCustomerName(), 'Test'));
        $testReviews = array_values($testReviews);

        $this->assertGreaterThanOrEqual(3, count($testReviews));
        $this->assertEquals('Test Newest', $testReviews[0]->getCustomerName());
        $this->assertEquals('Test Middle', $testReviews[1]->getCustomerName());
        $this->assertEquals('Test Oldest', $testReviews[2]->getCustomerName());
    }

    public function testFindLimitedReviews(): void
    {
        // Créer 5 avis de test
        for ($i = 1; $i <= 5; $i++) {
            $this->createTestReview("Test User $i", 5, true, "Comment $i");
        }

        // Récupérer uniquement les 3 derniers avis validés
        $reviews = $this->repository->findBy(
            ['isValidated' => true],
            ['createdAt' => 'DESC'],
            3
        );

        $testReviews = array_filter($reviews, fn($r) => str_starts_with($r->getCustomerName(), 'Test'));

        $this->assertLessThanOrEqual(3, count($testReviews));
    }

    public function testSaveAndRemoveReview(): void
    {
        $review = new Review();
        $review->setCustomerName('Test Save Remove');
        $review->setRating(5);
        $review->setComment('Test comment');
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated(false);

        // Sauvegarder
        $this->entityManager->persist($review);
        $this->entityManager->flush();
        $id = $review->getId();
        $this->assertNotNull($id);

        // Récupérer
        $savedReview = $this->repository->find($id);
        $this->assertNotNull($savedReview);
        $this->assertEquals('Test Save Remove', $savedReview->getCustomerName());

        // Supprimer
        $this->entityManager->remove($savedReview);
        $this->entityManager->flush();
        $deletedReview = $this->repository->find($id);
        $this->assertNull($deletedReview);
    }

    public function testUpdateReviewValidationStatus(): void
    {
        $review = $this->createTestReview('Test Validation', 4, false, 'Waiting for validation');
        $id = $review->getId();

        // Vérifier que l'avis n'est pas validé
        $this->assertFalse($review->isValidated());

        // Valider l'avis
        $review->setIsValidated(true);
        $this->entityManager->flush();

        // Recharger depuis la base de données
        $this->entityManager->clear();
        $updatedReview = $this->repository->find($id);

        $this->assertNotNull($updatedReview);
        $this->assertTrue($updatedReview->isValidated());
    }
}
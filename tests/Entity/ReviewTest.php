<?php

namespace App\Tests\Entity;

use App\Entity\Review;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests unitaires de l'entité Review
 */
class ReviewTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testReviewIsValid(): void
    {
        $review = new Review();
        $review->setCustomerName('John D.');
        $review->setRating(5);
        $review->setComment('Excellent service !');
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated(false);

        $errors = $this->validator->validate($review);
        $this->assertCount(0, $errors);
    }

    public function testReviewIsValidWithoutComment(): void
    {
        $review = new Review();
        $review->setCustomerName('Jane D.');
        $review->setRating(4);
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated(true);

        $errors = $this->validator->validate($review);
        $this->assertCount(0, $errors);
    }

    public function testReviewRatingCannotBeNull(): void
    {
        $review = new Review();
        $review->setCustomerName('John D.');
        $review->setComment('Excellent service !');
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated(false);

        $errors = $this->validator->validate($review);
        $this->assertGreaterThan(0, $errors->count());
    }

    public function testReviewRatingCannotBeLessThan1(): void
    {
        $review = new Review();
        $review->setCustomerName('John D.');
        $review->setRating(0);
        $review->setComment('Bad rating');
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated(false);

        $errors = $this->validator->validate($review);
        $this->assertGreaterThan(0, $errors->count());
    }

    public function testReviewRatingCannotBeGreaterThan5(): void
    {
        $review = new Review();
        $review->setCustomerName('John D.');
        $review->setRating(6);
        $review->setComment('Bad rating');
        $review->setCreatedAt(new \DateTimeImmutable());
        $review->setIsValidated(false);

        $errors = $this->validator->validate($review);
        $this->assertGreaterThan(0, $errors->count());
    }

    public function testReviewGettersAndSetters(): void
    {
        $review = new Review();
        $customerName = 'Alice B.';
        $rating = 5;
        $comment = 'Great experience!';
        $createdAt = new \DateTimeImmutable();
        $isValidated = true;

        $review->setCustomerName($customerName);
        $review->setRating($rating);
        $review->setComment($comment);
        $review->setCreatedAt($createdAt);
        $review->setIsValidated($isValidated);

        $this->assertEquals($customerName, $review->getCustomerName());
        $this->assertEquals($rating, $review->getRating());
        $this->assertEquals($comment, $review->getComment());
        $this->assertEquals($createdAt, $review->getCreatedAt());
        $this->assertTrue($review->isValidated());
    }

    public function testReviewIsNotValidatedByDefault(): void
    {
        $review = new Review();
        $this->assertFalse($review->isValidated());
    }

    public function testReviewAllValidRatings(): void
    {
        for ($rating = 1; $rating <= 5; $rating++) {
            $review = new Review();
            $review->setCustomerName('John D.');
            $review->setRating($rating);
            $review->setCreatedAt(new \DateTimeImmutable());
            $review->setIsValidated(false);

            $errors = $this->validator->validate($review);
            $this->assertCount(0, $errors, "Rating $rating should be valid");
        }
    }
}
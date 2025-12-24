<?php

namespace App\Controller\Admin;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/avis')]
#[IsGranted('ROLE_ADMIN')]
final class ReviewController extends AbstractController
{
    #[Route('', name: 'app_admin_reviews')]
    public function index(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/reviews/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_admin_reviews_validate', methods: ['POST'])]
    public function validate(Review $review, EntityManagerInterface $em): Response
    {
        $review->setIsValidated(true);
        $em->flush();

        $this->addFlash('success', 'L\'avis de "' . $review->getCustomerName() . '" a été validé avec succès !');

        return $this->redirectToRoute('app_admin_reviews');
    }

    #[Route('/{id}/rejeter', name: 'app_admin_reviews_reject', methods: ['POST'])]
    public function reject(Review $review, EntityManagerInterface $em): Response
    {
        $review->setIsValidated(false);
        $em->flush();

        $this->addFlash('warning', 'L\'avis de "' . $review->getCustomerName() . '" a été rejeté.');

        return $this->redirectToRoute('app_admin_reviews');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_reviews_delete', methods: ['POST'])]
    public function delete(Review $review, EntityManagerInterface $em): Response
    {
        $customerName = $review->getCustomerName();

        $em->remove($review);
        $em->flush();

        $this->addFlash('success', 'L\'avis de "' . $customerName . '" a été supprimé avec succès !');

        return $this->redirectToRoute('app_admin_reviews');
    }
}
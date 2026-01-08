<?php

namespace App\Controller\Public;

use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/avis')]
final class ReviewController extends AbstractController
{
    #[Route('', name: 'app_review_index')]
    public function index(ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy(
            ['isValidated' => true],
            ['createdAt' => 'DESC']
        );

        return $this->render('review/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }

    #[Route('/nouveau', name: 'app_review_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();

            // Génération automatique du nom : Prénom + Initiale du nom
            $customerName = $user->getFirstname() . ' ' . strtoupper(substr($user->getLastname(), 0, 1)) . '.';
            $review->setCustomerName($customerName);

            // Définir la date de création
            $review->setCreatedAt(new \DateTimeImmutable());

            // Par défaut, l'avis n'est pas validé (modération)
            $review->setIsValidated(false);

            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Merci pour votre avis ! Il sera publié après validation par notre équipe.');

            return $this->redirectToRoute('app_account');
        }

        return $this->render('review/new.html.twig', [
            'form' => $form,
        ]);
    }
}
<?php

namespace App\Controller\Public;

use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ReviewRepository $reviewRepository): Response
    {
        // Récupérer les 4 derniers avis validés
        $reviews = $reviewRepository->findBy(
            ['isValidated' => true],
            ['createdAt' => 'DESC'],
            4
        );

        return $this->render('home/index.html.twig', [
            'reviews' => $reviews,
        ]);
    }
}

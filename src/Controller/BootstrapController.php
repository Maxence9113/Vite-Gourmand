<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BootstrapController extends AbstractController
{
    #[Route('/bootstrap', name: 'app_bootstrap')]
    public function index(): Response
    {
        return $this->render('bootstrap/index.html.twig');
    }
}

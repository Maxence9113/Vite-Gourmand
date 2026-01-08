<?php

namespace App\Controller\Public;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur gérant les pages légales du site
 *
 * ⚠️ AVERTISSEMENT IMPORTANT :
 * Les documents légaux (mentions légales, CGV, politique de confidentialité) ont été générés
 * automatiquement par IA à des fins de démonstration pédagogique dans le cadre du projet ECF.
 *
 * NE PAS UTILISER EN PRODUCTION sans validation préalable par un avocat spécialisé.
 * Voir templates/legal/README.md pour plus d'informations.
 */
class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal_mentions')]
    public function mentions(): Response
    {
        return $this->render('legal/mentions.html.twig');
    }

    #[Route('/conditions-generales-vente', name: 'app_legal_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }

    #[Route('/politique-confidentialite', name: 'app_legal_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }
}

<?php

namespace App\Controller;

use App\Form\ContactType;
use App\Service\EmailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, EmailService $emailService): Response
    {
        // Création du formulaire
        $form = $this->createForm(ContactType::class);

        // Traitement de la soumission du formulaire
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des données du formulaire
            $data = $form->getData();

            try {
                // Envoi de l'email via le service
                $emailService->sendContactEmail(
                    senderName: $data['name'],
                    senderEmail: $data['email'],
                    subject: $data['subject'],
                    message: $data['message']
                );

                // Message de succès
                $this->addFlash('success', 'Votre message a bien été envoyé ! Nous vous répondrons dans les plus brefs délais.');

                // Redirection pour éviter la resoumission du formulaire
                return $this->redirectToRoute('app_contact');

            } catch (\Exception $e) {
                // En cas d'erreur d'envoi
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer.');
            }
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
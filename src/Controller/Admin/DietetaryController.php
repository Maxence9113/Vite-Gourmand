<?php

namespace App\Controller\Admin;

use App\Entity\Dietetary;
use App\Form\DietetaryType;
use App\Repository\DietetaryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/dietetaries')]
final class DietetaryController extends AbstractController
{
    #[Route('', name: 'app_admin_dietetaries')]
    public function index(DietetaryRepository $dietetaryRepository): Response
    {
        // Utiliser la méthode optimisée pour éviter le problème N+1
        $dietetaries = $dietetaryRepository->findAllWithMenuCount();

        return $this->render('admin/dietetaries/index.html.twig', [
            'dietetaries' => $dietetaries,
        ]);
    }

    #[Route('/new', name: 'app_admin_dietetaries_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $dietetary = new Dietetary();
        $form = $this->createForm(DietetaryType::class, $dietetary);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($dietetary);
            $em->flush();

            $this->addFlash('success', 'Le régime alimentaire "' . $dietetary->getName() . '" a été créé avec succès !');

            return $this->redirectToRoute('app_admin_dietetaries');
        }

        return $this->render('admin/dietetaries/form.html.twig', [
            'form' => $form,
            'dietetary' => $dietetary,
            'isEdit' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_dietetaries_edit')]
    public function edit(Dietetary $dietetary, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(DietetaryType::class, $dietetary);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Le régime alimentaire "' . $dietetary->getName() . '" a été modifié avec succès !');

            return $this->redirectToRoute('app_admin_dietetaries');
        }

        return $this->render('admin/dietetaries/form.html.twig', [
            'form' => $form,
            'dietetary' => $dietetary,
            'isEdit' => true,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_dietetaries_delete', methods: ['POST'])]
    public function delete(Dietetary $dietetary, EntityManagerInterface $em): Response
    {
        $dietetaryName = $dietetary->getName();

        // Vérifier si le régime alimentaire est utilisé par des menus
        if ($dietetary->getMenus()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer le régime alimentaire "' . $dietetaryName . '" car il est utilisé par ' . $dietetary->getMenus()->count() . ' menu(s).');
            return $this->redirectToRoute('app_admin_dietetaries');
        }

        $em->remove($dietetary);
        $em->flush();

        $this->addFlash('success', 'Le régime alimentaire "' . $dietetaryName . '" a été supprimé avec succès !');

        return $this->redirectToRoute('app_admin_dietetaries');
    }
}

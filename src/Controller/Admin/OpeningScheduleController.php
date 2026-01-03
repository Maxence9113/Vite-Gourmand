<?php

namespace App\Controller\Admin;

use App\Entity\OpeningSchedule;
use App\Enum\DayOfWeek;
use App\Form\OpeningScheduleType;
use App\Service\OpeningScheduleManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/horaires')]
#[IsGranted('ROLE_ADMIN')]
class OpeningScheduleController extends AbstractController
{
    public function __construct(
        private OpeningScheduleManager $openingScheduleManager,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'app_admin_opening_schedule_index', methods: ['GET'])]
    public function index(): Response
    {
        $schedules = $this->openingScheduleManager->getFormattedSchedules();

        return $this->render('admin/opening_schedule/index.html.twig', [
            'schedules' => $schedules,
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_opening_schedule_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $openingSchedule = new OpeningSchedule();
        $form = $this->createForm(OpeningScheduleType::class, $openingSchedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($openingSchedule);
            $this->entityManager->flush();

            $this->addFlash('success', 'Horaire créé avec succès.');

            return $this->redirectToRoute('app_admin_opening_schedule_index');
        }

        return $this->render('admin/opening_schedule/new.html.twig', [
            'form' => $form,
            'opening_schedule' => $openingSchedule,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_opening_schedule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OpeningSchedule $openingSchedule): Response
    {
        $form = $this->createForm(OpeningScheduleType::class, $openingSchedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Horaire modifié avec succès.');

            return $this->redirectToRoute('app_admin_opening_schedule_index');
        }

        return $this->render('admin/opening_schedule/edit.html.twig', [
            'form' => $form,
            'opening_schedule' => $openingSchedule,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_opening_schedule_delete', methods: ['POST'])]
    public function delete(Request $request, OpeningSchedule $openingSchedule): Response
    {
        if ($this->isCsrfTokenValid('delete'.$openingSchedule->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($openingSchedule);
            $this->entityManager->flush();

            $this->addFlash('success', 'Horaire supprimé avec succès.');
        }

        return $this->redirectToRoute('app_admin_opening_schedule_index');
    }

    #[Route('/initialiser', name: 'app_admin_opening_schedule_initialize', methods: ['POST'])]
    public function initialize(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('initialize', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_admin_opening_schedule_index');
        }

        $existingSchedules = $this->openingScheduleManager->getAllSchedules();
        if (count($existingSchedules) > 0) {
            $this->addFlash('warning', 'Des horaires existent déjà. Veuillez les supprimer avant d\'initialiser.');
            return $this->redirectToRoute('app_admin_opening_schedule_index');
        }

        $defaultSchedules = [
            DayOfWeek::MONDAY => ['09:00', '18:00', true],
            DayOfWeek::TUESDAY => ['09:00', '18:00', true],
            DayOfWeek::WEDNESDAY => ['09:00', '18:00', true],
            DayOfWeek::THURSDAY => ['09:00', '18:00', true],
            DayOfWeek::FRIDAY => ['09:00', '18:00', true],
            DayOfWeek::SATURDAY => ['10:00', '16:00', true],
            DayOfWeek::SUNDAY => [null, null, false],
        ];

        foreach ($defaultSchedules as $day => $data) {
            $schedule = new OpeningSchedule();
            $schedule->setDayOfWeek($day);
            $schedule->setIsOpen($data[2]);

            if ($data[0] !== null) {
                $schedule->setOpeningTime(new \DateTimeImmutable($data[0]));
            }
            if ($data[1] !== null) {
                $schedule->setClosingTime(new \DateTimeImmutable($data[1]));
            }

            $this->entityManager->persist($schedule);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Horaires initialisés avec succès (Lun-Ven 9h-18h, Sam 10h-16h, Dim fermé).');

        return $this->redirectToRoute('app_admin_opening_schedule_index');
    }
}
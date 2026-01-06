<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use App\Service\EmailService;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/orders')]
#[IsGranted('ROLE_EMPLOYEE')]
final class OrderAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_orders')]
    public function index(OrderRepository $orderRepository, Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $statusFilter = $request->query->get('status');
        $searchFilter = $request->query->get('search');
        $dateFilter = $request->query->get('date');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'DESC');

        // Construction de la requête avec QueryBuilder
        $qb = $orderRepository->createQueryBuilder('o')
            ->leftJoin('o.user', 'u')
            ->addSelect('u');

        // Filtre par statut
        if ($statusFilter && $statusFilter !== 'all') {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', OrderStatus::from($statusFilter));
        }

        // Filtre par recherche (numéro de commande, nom client, email)
        if ($searchFilter) {
            $qb->andWhere('o.orderNumber LIKE :search OR o.customerFirstname LIKE :search OR o.customerLastname LIKE :search OR o.customerEmail LIKE :search')
                ->setParameter('search', '%' . $searchFilter . '%');
        }

        // Filtre par date
        if ($dateFilter) {
            switch ($dateFilter) {
                case 'today':
                    $qb->andWhere('DATE(o.createdAt) = CURRENT_DATE()');
                    break;
                case 'week':
                    $qb->andWhere('o.createdAt >= :weekStart')
                        ->setParameter('weekStart', new \DateTimeImmutable('-7 days'));
                    break;
                case 'month':
                    $qb->andWhere('o.createdAt >= :monthStart')
                        ->setParameter('monthStart', new \DateTimeImmutable('-30 days'));
                    break;
            }
        }

        // Tri
        $validSortFields = ['createdAt', 'deliveryDateTime', 'totalPrice', 'status'];
        if (in_array($sortBy, $validSortFields)) {
            $qb->orderBy('o.' . $sortBy, strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC');
        }

        $orders = $qb->getQuery()->getResult();

        // Statistiques rapides
        $stats = [
            'total' => $orderRepository->count(),
            'pending' => $orderRepository->count(['status' => OrderStatus::PENDING]),
            'validated' => $orderRepository->count(['status' => OrderStatus::VALIDATED]),
            'preparing' => $orderRepository->count(['status' => OrderStatus::PREPARING]),
            'delivering' => $orderRepository->count(['status' => OrderStatus::DELIVERING]),
            'waitingMaterial' => $orderRepository->count(['status' => OrderStatus::WAITING_MATERIAL_RETURN]),
        ];

        return $this->render('admin/orders/index.html.twig', [
            'orders' => $orders,
            'stats' => $stats,
            'currentStatus' => $statusFilter,
            'currentSearch' => $searchFilter,
            'currentDate' => $dateFilter,
            'currentSort' => $sortBy,
            'currentOrder' => $sortOrder,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_orders_show', requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/change-status', name: 'app_admin_orders_change_status', methods: ['POST'])]
    public function changeStatus(
        Order $order,
        Request $request,
        EntityManagerInterface $em,
        OrderManager $orderManager,
        EmailService $emailService,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $newStatusValue = $request->request->get('status');

        if (!$newStatusValue) {
            $this->addFlash('error', 'Aucun statut fourni.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        try {
            $newStatus = OrderStatus::from($newStatusValue);
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Vérifier si la transition est autorisée
        $currentStatus = $order->getStatus();
        if (!in_array($newStatus, $currentStatus->getNextStatuses())) {
            $this->addFlash('error', sprintf(
                'Impossible de passer du statut "%s" au statut "%s".',
                $currentStatus->getLabel(),
                $newStatus->getLabel()
            ));
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Vérification spéciale : empêcher de terminer une commande si du matériel est prêté et non retourné
        if ($newStatus === OrderStatus::COMPLETED && $order->hasMaterialLoan() && !$order->isMaterialReturned()) {
            $this->addFlash('error', 'Impossible de terminer la commande : le matériel prêté n\'a pas encore été retourné. Veuillez d\'abord passer la commande en "En attente du retour de matériel".');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Si passage en WAITING_MATERIAL_RETURN, définir la deadline de retour (J+2)
        if ($newStatus === OrderStatus::WAITING_MATERIAL_RETURN && $order->hasMaterialLoan()) {
            $deadline = new \DateTimeImmutable('+2 days');
            $order->setMaterialReturnDeadline($deadline);
        }

        // Changer le statut via OrderManager (qui gérera aussi les stats MongoDB si DELIVERED)
        $orderManager->changeOrderStatus($order, $newStatus);

        // Envoyer les emails en fonction du nouveau statut
        $this->sendStatusChangeEmail($order, $newStatus, $emailService, $urlGenerator);

        $this->addFlash('success', sprintf(
            'La commande #%s est maintenant "%s".',
            $order->getOrderNumber(),
            $newStatus->getLabel()
        ));

        return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
    }

    /**
     * Envoie les emails automatiques en fonction du changement de statut
     */
    private function sendStatusChangeEmail(
        Order $order,
        OrderStatus $newStatus,
        EmailService $emailService,
        UrlGeneratorInterface $urlGenerator
    ): void {
        // Email de validation de commande (quand l'employé accepte la commande)
        if ($newStatus === OrderStatus::VALIDATED) {
            $emailService->sendOrderValidatedEmail(
                userEmail: $order->getCustomerEmail(),
                userFirstname: $order->getCustomerFirstname(),
                orderNumber: $order->getOrderNumber(),
                deliveryDateTime: $order->getDeliveryDateTime()
            );
        }

        // Email de rappel de retour de matériel
        if ($newStatus === OrderStatus::WAITING_MATERIAL_RETURN && $order->hasMaterialLoan()) {
            $emailService->sendMaterialReturnReminderEmail(
                userEmail: $order->getCustomerEmail(),
                userFirstname: $order->getCustomerFirstname(),
                orderNumber: $order->getOrderNumber(),
                deadline: $order->getMaterialReturnDeadline()
            );
        }

        // Email de commande terminée avec invitation à laisser un avis
        if ($newStatus === OrderStatus::COMPLETED) {
            $reviewUrl = $urlGenerator->generate(
                'app_order_show',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $emailService->sendOrderCompletedEmail(
                userEmail: $order->getCustomerEmail(),
                userFirstname: $order->getCustomerFirstname(),
                orderNumber: $order->getOrderNumber(),
                reviewUrl: $reviewUrl
            );
        }
    }

    #[Route('/{id}/mark-material-returned', name: 'app_admin_orders_material_returned', methods: ['POST'])]
    public function markMaterialReturned(
        Order $order,
        EntityManagerInterface $em,
        EmailService $emailService,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        if (!$order->hasMaterialLoan()) {
            $this->addFlash('error', 'Cette commande ne comporte pas de prêt de matériel.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        if ($order->getStatus() !== OrderStatus::WAITING_MATERIAL_RETURN) {
            $this->addFlash('error', 'Cette commande n\'est pas en attente de retour de matériel.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        $order->setMaterialReturned(true);
        $order->changeStatus(OrderStatus::COMPLETED);

        $em->flush();

        // Envoyer l'email de commande terminée
        $this->sendStatusChangeEmail($order, OrderStatus::COMPLETED, $emailService, $urlGenerator);

        $this->addFlash('success', 'Le matériel a été marqué comme retourné et la commande est terminée.');

        return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_admin_orders_cancel', methods: ['POST'])]
    public function cancel(
        Order $order,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$order->canBeCancelled()) {
            $this->addFlash('error', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        $contactMethod = $request->request->get('contact_method');
        $reason = $request->request->get('reason');

        // Validation du mode de contact
        if (empty($contactMethod)) {
            $this->addFlash('error', 'Veuillez indiquer le mode de contact utilisé avec le client.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        if (!in_array($contactMethod, ['phone', 'email'])) {
            $this->addFlash('error', 'Mode de contact invalide.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Validation du motif
        if (empty($reason)) {
            $this->addFlash('error', 'Veuillez fournir un motif d\'annulation.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Construire le message d'annulation avec le mode de contact
        $contactMethodLabel = $contactMethod === 'phone' ? 'Appel téléphonique (GSM)' : 'Email';
        $fullReason = sprintf(
            "Mode de contact: %s\n\nMotif: %s",
            $contactMethodLabel,
            $reason
        );

        $order->setCancellationReason($fullReason);
        $order->changeStatus(OrderStatus::CANCELLED);

        $em->flush();

        $this->addFlash('success', sprintf(
            'La commande #%s a été annulée après contact client par %s.',
            $order->getOrderNumber(),
            strtolower($contactMethodLabel)
        ));

        return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
    }
}

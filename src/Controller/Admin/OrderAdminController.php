<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Service\EmailService;
use App\Service\OrderFilterService;
use App\Service\OrderManager;
use App\Service\OrderStatisticsService;
use App\Service\OrderStatusValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur d'administration des commandes
 * Permet aux employés de gérer les commandes : visualisation, filtrage, changement de statut
 */
#[Route('/admin/orders')]
#[IsGranted('ROLE_EMPLOYEE')]
final class OrderAdminController extends AbstractController
{
    public function __construct(
        private readonly OrderFilterService $orderFilterService,
        private readonly OrderStatisticsService $orderStatisticsService,
        private readonly OrderStatusValidator $orderStatusValidator,
        private readonly EmailService $emailService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Liste toutes les commandes avec filtres et statistiques
     */
    #[Route('', name: 'app_admin_orders')]
    public function index(Request $request): Response
    {
        // Récupérer les filtres depuis la requête
        $statusFilter = $request->query->get('status');
        $searchFilter = $request->query->get('search');
        $dateFilter = $request->query->get('date');
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortOrder = $request->query->get('order', 'DESC');

        // Utiliser le service de filtrage pour obtenir les commandes
        $orders = $this->orderFilterService->filterOrders(
            statusFilter: $statusFilter,
            searchFilter: $searchFilter,
            dateFilter: $dateFilter,
            sortBy: $sortBy,
            sortOrder: $sortOrder
        );

        // Utiliser le service de statistiques pour obtenir les compteurs
        $stats = $this->orderStatisticsService->getQuickStats();

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

    /**
     * Affiche le détail d'une commande
     */
    #[Route('/{id}', name: 'app_admin_orders_show', requirements: ['id' => '\d+'])]
    public function show(Order $order): Response
    {
        return $this->render('admin/orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    /**
     * Change le statut d'une commande
     * Valide la transition, met à jour la base de données et envoie les emails appropriés
     */
    #[Route('/{id}/change-status', name: 'app_admin_orders_change_status', methods: ['POST'])]
    public function changeStatus(
        Order $order,
        Request $request,
        OrderManager $orderManager,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $newStatusValue = $request->request->get('status');

        // Vérifier qu'un statut a été fourni
        if (!$newStatusValue) {
            $this->addFlash('error', 'Aucun statut fourni.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Convertir la valeur en enum OrderStatus
        try {
            $newStatus = OrderStatus::from($newStatusValue);
        } catch (\ValueError $e) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Valider la transition avec le service de validation
        $validation = $this->orderStatusValidator->validateStatusChange($order, $newStatus);

        if (!$validation->isValid()) {
            $this->addFlash('error', $validation->getErrorMessage());
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Si passage en WAITING_MATERIAL_RETURN, définir la deadline de retour (J+2)
        if ($newStatus === OrderStatus::WAITING_MATERIAL_RETURN && $order->hasMaterialLoan()) {
            $deadline = new \DateTimeImmutable('+2 days');
            $order->setMaterialReturnDeadline($deadline);
        }

        // Changer le statut via OrderManager (qui gérera aussi les stats MongoDB si DELIVERED)
        $orderManager->changeOrderStatus($order, $newStatus);

        // Générer l'URL de review pour les commandes terminées
        $reviewUrl = null;
        if ($newStatus === OrderStatus::COMPLETED) {
            $reviewUrl = $urlGenerator->generate(
                'app_order_show',
                ['id' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        // Envoyer les emails automatiquement via le service centralisé
        $this->emailService->sendStatusChangeNotification($order, $newStatus, $reviewUrl);

        $this->addFlash('success', sprintf(
            'La commande #%s est maintenant "%s".',
            $order->getOrderNumber(),
            $newStatus->getLabel()
        ));

        return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
    }

    /**
     * Marque le matériel comme retourné et termine la commande
     */
    #[Route('/{id}/mark-material-returned', name: 'app_admin_orders_material_returned', methods: ['POST'])]
    public function markMaterialReturned(
        Order $order,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        // Vérifier que la commande comporte un prêt de matériel
        if (!$order->hasMaterialLoan()) {
            $this->addFlash('error', 'Cette commande ne comporte pas de prêt de matériel.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Vérifier que la commande est en attente de retour de matériel
        if ($order->getStatus() !== OrderStatus::WAITING_MATERIAL_RETURN) {
            $this->addFlash('error', 'Cette commande n\'est pas en attente de retour de matériel.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        // Marquer le matériel comme retourné et terminer la commande
        $order->setMaterialReturned(true);
        $order->changeStatus(OrderStatus::COMPLETED);

        $this->entityManager->flush();

        // Générer l'URL de review et envoyer l'email de commande terminée
        $reviewUrl = $urlGenerator->generate(
            'app_order_show',
            ['id' => $order->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $this->emailService->sendStatusChangeNotification($order, OrderStatus::COMPLETED, $reviewUrl);

        $this->addFlash('success', 'Le matériel a été marqué comme retourné et la commande est terminée.');

        return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
    }

    /**
     * Annule une commande après contact avec le client
     * Requiert un mode de contact (téléphone ou email) et un motif d'annulation
     */
    #[Route('/{id}/cancel', name: 'app_admin_orders_cancel', methods: ['POST'])]
    public function cancel(Order $order, Request $request): Response
    {
        // Vérifier que la commande peut être annulée
        if (!$order->canBeCancelled()) {
            $this->addFlash('error', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
        }

        $contactMethod = $request->request->get('contact_method');
        $reason = $request->request->get('reason');

        // Validation du mode de contact
        if (empty($contactMethod) || !in_array($contactMethod, ['phone', 'email'])) {
            $this->addFlash('error', 'Veuillez indiquer un mode de contact valide (téléphone ou email).');
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

        // Enregistrer l'annulation
        $order->setCancellationReason($fullReason);
        $order->changeStatus(OrderStatus::CANCELLED);

        $this->entityManager->flush();

        $this->addFlash('success', sprintf(
            'La commande #%s a été annulée après contact client par %s.',
            $order->getOrderNumber(),
            strtolower($contactMethodLabel)
        ));

        return $this->redirectToRoute('app_admin_orders_show', ['id' => $order->getId()]);
    }
}
<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Menu;
use App\Entity\Order;
use App\Entity\User;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Service\OrderManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class OrderController extends AbstractController
{
    public function __construct(
        private OrderManager $orderManager,
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route('/compte/commandes', name: 'app_account_orders')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $orders = $this->orderManager->getUserOrders($user);

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/commande/nouvelle/{id}', name: 'app_order_new')]
    #[IsGranted('ROLE_USER')]
    public function new(Menu $menu, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que l'utilisateur a au moins une adresse
        if ($user->getAddresses()->isEmpty()) {
            $this->addFlash('warning', 'Vous devez d\'abord ajouter une adresse de livraison.');
            return $this->redirectToRoute('app_account_address_new', [
                'returnTo' => 'app_order_new',
                'menuId' => $menu->getId()
            ]);
        }

        $order = new Order();
        $form = $this->createForm(OrderType::class, $order, [
            'user' => $user,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Menu $selectedMenu */
            $selectedMenu = $form->get('menu')->getData();
            /** @var Address $selectedAddress */
            $selectedAddress = $form->get('deliveryAddress')->getData();

            // SÉCURITÉ: Vérifier que l'adresse appartient bien à l'utilisateur
            if ($selectedAddress->getUser() !== $user) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas utiliser cette adresse.');
            }

            // Vérifier que le nombre de personnes est suffisant
            if ($order->getNumberOfPersons() < $selectedMenu->getNbPersonMin()) {
                $this->addFlash('error', sprintf(
                    'Le menu "%s" nécessite un minimum de %d personnes.',
                    $selectedMenu->getName(),
                    $selectedMenu->getNbPersonMin()
                ));
                return $this->redirectToRoute('app_order_new', ['id' => $menu->getId()]);
            }

            // Vérifier que la date de livraison est valide
            if (!$this->orderManager->isValidDeliveryDate($order->getDeliveryDateTime())) {
                $this->addFlash('error', 'La livraison doit être prévue au minimum 48h à l\'avance.');
                return $this->redirectToRoute('app_order_new', ['id' => $menu->getId()]);
            }

            // Créer la commande via le service
            $createdOrder = $this->orderManager->createOrder(
                $user,
                $selectedMenu,
                $selectedAddress,
                $order->getNumberOfPersons(),
                $order->getDeliveryDateTime(),
                $order->hasMaterialLoan()
            );

            $this->orderManager->saveOrder($createdOrder);

            $this->addFlash('success', sprintf(
                'Votre commande #%s a été créée avec succès !',
                $createdOrder->getOrderNumber()
            ));

            return $this->redirectToRoute('app_order_show', ['id' => $createdOrder->getId()]);
        }

        return $this->render('order/new.html.twig', [
            'form' => $form,
            'menu' => $menu,
        ]);
    }

    #[Route('/compte/commandes/{id}', name: 'app_order_show')]
    #[IsGranted('ROLE_USER')]
    public function show(Order $order): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que la commande appartient bien à l'utilisateur
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas consulter cette commande.');
        }

        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/compte/commandes/{id}/annuler', name: 'app_order_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(Order $order, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier que la commande appartient bien à l'utilisateur
        if ($order->getUser() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas annuler cette commande.');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('cancel-order-' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        // Vérifier que la commande peut être annulée
        if (!$order->canBeCancelled()) {
            $this->addFlash('error', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
        }

        $reason = $request->request->get('reason', 'Annulation par le client');

        try {
            $this->orderManager->cancelOrder($order, $reason);
            $this->addFlash('success', 'Votre commande a été annulée avec succès.');
        } catch (\LogicException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_order_show', ['id' => $order->getId()]);
    }
}
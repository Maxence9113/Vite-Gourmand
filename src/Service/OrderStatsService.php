<?php

namespace App\Service;

use App\Document\OrderStats;
use App\Entity\Order;
use Doctrine\ODM\MongoDB\DocumentManager;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des statistiques de commandes dans MongoDB
 *
 * Ce service est responsable de :
 * - Sauvegarder les stats d'une commande dans MongoDB (pour les graphiques admin)
 * - Mettre à jour les stats quand le statut d'une commande change
 *
 * Pourquoi ce service ?
 * - Centralise la logique de sauvegarde des stats
 * - Évite de dupliquer le code dans plusieurs contrôleurs
 * - Facilite les tests et la maintenance
 */
class OrderStatsService
{
    /**
     * Injection des dépendances
     *
     * @param DocumentManager $documentManager Manager MongoDB (équivalent de EntityManager pour MongoDB)
     * @param LoggerInterface $logger          Logger pour tracer les erreurs éventuelles
     */
    public function __construct(
        private DocumentManager $documentManager,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Sauvegarde ou met à jour les statistiques d'une commande dans MongoDB
     *
     * Cette méthode est appelée :
     * - Quand une nouvelle commande est créée
     * - Quand le statut d'une commande est modifié
     *
     * @param Order $order La commande dont on veut sauvegarder les stats
     * @return void
     */
    public function saveOrderStats(Order $order): void
    {
        try {
            // ==================== ÉTAPE 1 : Vérifier si des stats existent déjà ====================
            // On cherche dans MongoDB si on a déjà sauvegardé des stats pour cette commande
            $repository = $this->documentManager->getRepository(OrderStats::class);

            // Recherche par orderId (l'ID de la commande MariaDB)
            $existingStats = $repository->findOneBy(['orderId' => $order->getId()]);

            if ($existingStats) {
                // ==================== CAS 1 : Les stats existent → ON MET À JOUR ====================
                // Utile quand le statut de la commande change
                $this->updateExistingStats($existingStats, $order);
            } else {
                // ==================== CAS 2 : Première fois → ON CRÉE ====================
                // Utile quand une nouvelle commande vient d'être créée
                $this->createNewStats($order);
            }

        } catch (\Exception $e) {
            // Si une erreur se produit, on la log mais on ne bloque pas l'application
            // Les stats sont importantes mais pas critiques
            $this->logger->error('Erreur lors de la sauvegarde des stats MongoDB', [
                'order_id' => $order->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Crée un nouveau document OrderStats dans MongoDB
     *
     * @param Order $order La commande source
     * @return void
     */
    private function createNewStats(Order $order): void
    {
        // Création d'un nouveau document MongoDB
        $stats = new OrderStats();

        // Remplissage avec les données de la commande
        $stats->setOrderId($order->getId());
        // Note : On ne peut pas récupérer menuId car Order n'a pas de relation avec Menu
        // On met 0 par défaut (vous pourrez l'améliorer plus tard si besoin)
        $stats->setMenuId(0);
        $stats->setMenuName($order->getMenuName());
        // Note : Le thème n'est pas stocké dans Order, on met une valeur par défaut
        // Les vraies stats viendront principalement des fixtures qui ont le vrai thème
        $stats->setThemeName('Autre');
        // totalPrice est en centimes dans Order, on le convertit en euros pour MongoDB
        $stats->setTotalPrice($order->getTotalPrice() / 100);
        $stats->setNumberOfPeople($order->getNumberOfPersons());
        $stats->setOrderDate(\DateTime::createFromImmutable($order->getCreatedAt()));

        // Sauvegarde dans MongoDB
        $this->documentManager->persist($stats);
        $this->documentManager->flush();

        $this->logger->info('Statistiques créées dans MongoDB', [
            'order_id' => $order->getId(),
            'mongo_id' => $stats->getId()
        ]);
    }

    /**
     * Met à jour un document OrderStats existant dans MongoDB
     *
     * Cette méthode est appelée quand le statut d'une commande change
     * (ex: passage de "en préparation" à "livré")
     *
     * @param OrderStats $stats Le document MongoDB existant
     * @param Order $order      La commande source avec les nouvelles données
     * @return void
     */
    private function updateExistingStats(OrderStats $stats, Order $order): void
    {
        // Met à jour uniquement les champs qui peuvent changer
        $stats->setTotalPrice($order->getTotalPrice() / 100);

        // Sauvegarde les modifications dans MongoDB
        $this->documentManager->flush();

        $this->logger->info('Statistiques mises à jour dans MongoDB', [
            'order_id' => $order->getId()
        ]);
    }

    /**
     * Supprime les statistiques d'une commande de MongoDB
     *
     * Utile si une commande est supprimée de MariaDB
     *
     * @param int $orderId L'ID de la commande
     * @return void
     */
    public function deleteOrderStats(int $orderId): void
    {
        try {
            $repository = $this->documentManager->getRepository(OrderStats::class);
            $stats = $repository->findOneBy(['orderId' => $orderId]);

            if ($stats) {
                $this->documentManager->remove($stats);
                $this->documentManager->flush();

                $this->logger->info('Statistiques supprimées de MongoDB', [
                    'order_id' => $orderId
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression des stats MongoDB', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
<?php

namespace App\Service;

use App\Entity\Order;
use App\Enum\OrderStatus;

/**
 * Service de validation des transitions de statut de commande
 * Centralise toutes les règles métier liées aux changements de statut
 */
final class OrderStatusValidator
{
    /**
     * Résultat de validation
     */
    private bool $isValid = false;
    private ?string $errorMessage = null;

    /**
     * Valide si une transition de statut est autorisée
     *
     * @param Order $order La commande concernée
     * @param OrderStatus $newStatus Le nouveau statut souhaité
     * @return self Pour permettre le chaînage de méthodes
     */
    public function validateStatusChange(Order $order, OrderStatus $newStatus): self
    {
        $currentStatus = $order->getStatus();

        // Vérifier si la transition est autorisée selon le workflow
        if (!$this->isTransitionAllowed($currentStatus, $newStatus)) {
            $this->isValid = false;
            $this->errorMessage = sprintf(
                'Impossible de passer du statut "%s" au statut "%s".',
                $currentStatus->getLabel(),
                $newStatus->getLabel()
            );
            return $this;
        }

        // Vérifier les règles métier spécifiques
        if (!$this->checkBusinessRules($order, $newStatus)) {
            // L'erreur est déjà définie par checkBusinessRules()
            return $this;
        }

        // Tout est valide
        $this->isValid = true;
        $this->errorMessage = null;
        return $this;
    }

    /**
     * Vérifie si la transition est autorisée selon le workflow défini
     *
     * @param OrderStatus $currentStatus Statut actuel
     * @param OrderStatus $newStatus Nouveau statut souhaité
     * @return bool True si la transition est autorisée
     */
    private function isTransitionAllowed(OrderStatus $currentStatus, OrderStatus $newStatus): bool
    {
        // Utilise la méthode getNextStatuses() de l'enum OrderStatus
        // qui définit les transitions autorisées
        return in_array($newStatus, $currentStatus->getNextStatuses(), true);
    }

    /**
     * Vérifie les règles métier spécifiques selon le nouveau statut
     *
     * @param Order $order La commande
     * @param OrderStatus $newStatus Le nouveau statut
     * @return bool True si les règles métier sont respectées
     */
    private function checkBusinessRules(Order $order, OrderStatus $newStatus): bool
    {
        // Règle : Impossible de terminer une commande si du matériel est prêté et non retourné
        if ($newStatus === OrderStatus::COMPLETED) {
            if ($order->hasMaterialLoan() && !$order->isMaterialReturned()) {
                $this->isValid = false;
                $this->errorMessage = 'Impossible de terminer la commande : le matériel prêté n\'a pas encore été retourné. '
                    . 'Veuillez d\'abord passer la commande en "En attente du retour de matériel".';
                return false;
            }
        }

        // Règle : Passage en WAITING_MATERIAL_RETURN uniquement si la commande a un prêt de matériel
        if ($newStatus === OrderStatus::WAITING_MATERIAL_RETURN) {
            if (!$order->hasMaterialLoan()) {
                $this->isValid = false;
                $this->errorMessage = 'Impossible de passer en attente de retour de matériel : '
                    . 'cette commande ne comporte pas de prêt de matériel.';
                return false;
            }
        }

        // Toutes les règles sont respectées
        return true;
    }

    /**
     * Retourne si la validation est réussie
     *
     * @return bool True si valide
     */
    public function isValid(): bool
    {
        return $this->isValid;
    }

    /**
     * Retourne le message d'erreur en cas d'échec de validation
     *
     * @return string|null Le message d'erreur ou null si pas d'erreur
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
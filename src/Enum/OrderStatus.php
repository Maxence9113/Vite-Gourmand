<?php

namespace App\Enum;

enum OrderStatus: string
{
    case PENDING = 'pending';                           // En attente
    case VALIDATED = 'validated';                       // Validée (acceptée)
    case PREPARING = 'preparing';                       // En préparation
    case READY = 'ready';                               // Prête à livrer
    case DELIVERING = 'delivering';                     // En livraison
    case DELIVERED = 'delivered';                       // Livrée
    case WAITING_MATERIAL_RETURN = 'waiting_material_return'; // En attente retour matériel
    case COMPLETED = 'completed';                       // Terminée
    case CANCELLED = 'cancelled';                       // Annulée

    /**
     * Retourne le label français
     */
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'En attente',
            self::VALIDATED => 'Validée',
            self::PREPARING => 'En préparation',
            self::READY => 'Prête à livrer',
            self::DELIVERING => 'En livraison',
            self::DELIVERED => 'Livrée',
            self::WAITING_MATERIAL_RETURN => 'En attente du retour de matériel',
            self::COMPLETED => 'Terminée',
            self::CANCELLED => 'Annulée',
        };
    }

    /**
     * Vérifie si la commande peut être modifiée par l'utilisateur
     */
    public function isEditable(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Vérifie si la commande peut être annulée
     */
    public function isCancellable(): bool
    {
        return in_array($this, [
            self::PENDING,
            self::VALIDATED,
            self::PREPARING,
        ]);
    }

    /**
     * Vérifie si un avis peut être laissé sur cette commande
     */
    public function canReceiveReview(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Vérifie si c'est un statut final (terminé)
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Retourne la classe CSS Bootstrap pour le badge de statut
     */
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::VALIDATED => 'info',
            self::PREPARING => 'primary',
            self::READY => 'primary',
            self::DELIVERING => 'info',
            self::DELIVERED => 'success',
            self::WAITING_MATERIAL_RETURN => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    /**
     * Retourne l'icône Feather correspondante
     */
    public function getIcon(): string
    {
        return match($this) {
            self::PENDING => 'clock',
            self::VALIDATED => 'check-circle',
            self::PREPARING => 'package',
            self::READY => 'box',
            self::DELIVERING => 'truck',
            self::DELIVERED => 'check-square',
            self::WAITING_MATERIAL_RETURN => 'rotate-ccw',
            self::COMPLETED => 'award',
            self::CANCELLED => 'x-circle',
        };
    }

    /**
     * Retourne les statuts suivants possibles (pour validation des transitions)
     */
    public function getNextStatuses(): array
    {
        return match($this) {
            self::PENDING => [self::VALIDATED, self::CANCELLED],
            self::VALIDATED => [self::PREPARING, self::CANCELLED],
            self::PREPARING => [self::READY, self::CANCELLED],
            self::READY => [self::DELIVERING],
            self::DELIVERING => [self::DELIVERED],
            self::DELIVERED => [self::WAITING_MATERIAL_RETURN, self::COMPLETED],
            self::WAITING_MATERIAL_RETURN => [self::COMPLETED],
            self::COMPLETED => [],
            self::CANCELLED => [],
        };
    }
}

<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Document MongoDB pour stocker les statistiques de commandes
 *
 * Ce document est utilisé pour :
 * - Calculer le nombre de commandes par menu
 * - Calculer le chiffre d'affaires par menu
 * - Générer des graphiques de statistiques dans l'espace admin
 *
 * Contrairement aux entités Doctrine ORM (MariaDB), ce document est stocké dans MongoDB
 * et ne nécessite pas de migrations. La collection sera créée automatiquement.
 */
#[MongoDB\Document(collection: 'order_stats')]
class OrderStats
{
    /**
     * ID du document MongoDB (généré automatiquement)
     * Équivalent de l'auto-increment en SQL mais au format MongoDB ObjectId
     */
    #[MongoDB\Id]
    private ?string $id = null;

    /**
     * ID de la commande originale dans MariaDB
     * Permet de faire le lien entre MongoDB et MariaDB si nécessaire
     */
    #[MongoDB\Field(type: 'int')]
    private int $orderId;

    /**
     * ID du menu commandé
     */
    #[MongoDB\Field(type: 'int')]
    private int $menuId;

    /**
     * Nom du menu (stocké pour éviter les jointures)
     * En MongoDB, on duplique souvent les données pour optimiser les lectures
     */
    #[MongoDB\Field(type: 'string')]
    private string $menuName;

    /**
     * Prix total de la commande en euros
     */
    #[MongoDB\Field(type: 'float')]
    private float $totalPrice;

    /**
     * Nombre de personnes pour cette commande
     */
    #[MongoDB\Field(type: 'int')]
    private int $numberOfPeople;

    /**
     * Date à laquelle la commande a été créée
     */
    #[MongoDB\Field(type: 'date')]
    private \DateTime $orderDate;

    // ==================== GETTERS ====================

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    public function getMenuId(): int
    {
        return $this->menuId;
    }

    public function getMenuName(): string
    {
        return $this->menuName;
    }

    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    public function getNumberOfPeople(): int
    {
        return $this->numberOfPeople;
    }

    public function getOrderDate(): \DateTime
    {
        return $this->orderDate;
    }

    // ==================== SETTERS ====================

    public function setOrderId(int $orderId): self
    {
        $this->orderId = $orderId;
        return $this;
    }

    public function setMenuId(int $menuId): self
    {
        $this->menuId = $menuId;
        return $this;
    }

    public function setMenuName(string $menuName): self
    {
        $this->menuName = $menuName;
        return $this;
    }

    public function setTotalPrice(float $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        return $this;
    }

    public function setNumberOfPeople(int $numberOfPeople): self
    {
        $this->numberOfPeople = $numberOfPeople;
        return $this;
    }

    public function setOrderDate(\DateTime $orderDate): self
    {
        $this->orderDate = $orderDate;
        return $this;
    }
}
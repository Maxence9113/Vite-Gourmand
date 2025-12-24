<?php

namespace App\Entity;

use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $orderNumber = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\OneToOne(inversedBy: 'orderRef', cascade: ['persist', 'remove'])]
    private ?Review $review = null;

    #[ORM\Column(length: 255)]
    private ?string $customerFirstname = null;

    #[ORM\Column(length: 255)]
    private ?string $customerLastname = null;

    #[ORM\Column(length: 255)]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $customerPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryDistanceKm = null;

    #[ORM\Column]
    private ?int $deliveryCost = null;

    #[ORM\Column(length: 255)]
    private ?string $menuName = null;

    #[ORM\Column]
    private ?int $menuPricePerPerson = null;

    #[ORM\Column]
    private ?int $numberOfPersons = null;

    #[ORM\Column]
    private ?int $menuSubtotal = null;

    #[ORM\Column(nullable: true)]
    private ?int $discountAmount = null;

    #[ORM\Column]
    private ?int $totalPrice = null;

    #[ORM\Column(enumType: OrderStatus::class)]
    private ?OrderStatus $status = null;

    #[ORM\Column]
    private array $statusHistory = [];

    #[ORM\Column]
    private ?bool $hasMaterialLoan = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $materialReturnDeadline = null;

    #[ORM\Column(nullable: true)]
    private ?bool $materialReturned = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $acceptedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function setOrderNumber(string $orderNumber): static
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getReview(): ?Review
    {
        return $this->review;
    }

    public function setReview(?Review $review): static
    {
        $this->review = $review;

        return $this;
    }

    public function getCustomerFirstname(): ?string
    {
        return $this->customerFirstname;
    }

    public function setCustomerFirstname(string $customerFirstname): static
    {
        $this->customerFirstname = $customerFirstname;

        return $this;
    }

    public function getCustomerLastname(): ?string
    {
        return $this->customerLastname;
    }

    public function setCustomerLastname(string $customerLastname): static
    {
        $this->customerLastname = $customerLastname;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): static
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getDeliveryDistanceKm(): ?int
    {
        return $this->deliveryDistanceKm;
    }

    public function setDeliveryDistanceKm(?int $deliveryDistanceKm): static
    {
        $this->deliveryDistanceKm = $deliveryDistanceKm;

        return $this;
    }

    public function getDeliveryCost(): ?int
    {
        return $this->deliveryCost;
    }

    public function setDeliveryCost(int $deliveryCost): static
    {
        $this->deliveryCost = $deliveryCost;

        return $this;
    }

    public function getMenuName(): ?string
    {
        return $this->menuName;
    }

    public function setMenuName(string $menuName): static
    {
        $this->menuName = $menuName;

        return $this;
    }

    public function getMenuPricePerPerson(): ?int
    {
        return $this->menuPricePerPerson;
    }

    public function setMenuPricePerPerson(int $menuPricePerPerson): static
    {
        $this->menuPricePerPerson = $menuPricePerPerson;

        return $this;
    }

    public function getNumberOfPersons(): ?int
    {
        return $this->numberOfPersons;
    }

    public function setNumberOfPersons(int $numberOfPersons): static
    {
        $this->numberOfPersons = $numberOfPersons;

        return $this;
    }

    public function getMenuSubtotal(): ?int
    {
        return $this->menuSubtotal;
    }

    public function setMenuSubtotal(int $menuSubtotal): static
    {
        $this->menuSubtotal = $menuSubtotal;

        return $this;
    }

    public function getDiscountAmount(): ?int
    {
        return $this->discountAmount;
    }

    public function setDiscountAmount(?int $discountAmount): static
    {
        $this->discountAmount = $discountAmount;

        return $this;
    }

    public function getTotalPrice(): ?int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function getStatus(): ?OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusHistory(): array
    {
        return $this->statusHistory;
    }

    public function setStatusHistory(array $statusHistory): static
    {
        $this->statusHistory = $statusHistory;

        return $this;
    }

    public function hasMaterialLoan(): ?bool
    {
        return $this->hasMaterialLoan;
    }

    public function setHasMaterialLoan(bool $hasMaterialLoan): static
    {
        $this->hasMaterialLoan = $hasMaterialLoan;

        return $this;
    }

    public function getMaterialReturnDeadline(): ?\DateTimeImmutable
    {
        return $this->materialReturnDeadline;
    }

    public function setMaterialReturnDeadline(?\DateTimeImmutable $materialReturnDeadline): static
    {
        $this->materialReturnDeadline = $materialReturnDeadline;

        return $this;
    }

    public function isMaterialReturned(): ?bool
    {
        return $this->materialReturned;
    }

    public function setMaterialReturned(?bool $materialReturned): static
    {
        $this->materialReturned = $materialReturned;

        return $this;
    }

    public function getCancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function setCancellationReason(?string $cancellationReason): static
    {
        $this->cancellationReason = $cancellationReason;

        return $this;
    }

    public function getCancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function setCancelledAt(?\DateTimeImmutable $cancelledAt): static
    {
        $this->cancelledAt = $cancelledAt;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(?\DateTimeImmutable $acceptedAt): static
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

}

<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    /** Payée, à préparer. */
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_CANCELLED = 'cancelled';

    /** Statut => libellé affiché au client et dans l'admin. */
    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'En attente de paiement',
        self::STATUS_PROCESSING => 'Paiement en cours',
        self::STATUS_COMPLETED => 'Payée, en préparation',
        self::STATUS_SHIPPED => 'Expédiée',
        self::STATUS_CANCELLED => 'Annulée',
    ];

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PROCESSING,
        self::STATUS_COMPLETED,
        self::STATUS_SHIPPED,
        self::STATUS_CANCELLED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $total = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /** Session Stripe Checkout en cours : seule celle-ci peut valider ou annuler la commande. */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $shippedAt = null;

    /** Numéro de suivi transporteur, communiqué au client à l'expédition. */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    /**
     * @var Collection<int, OrderItem>
     */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'customerOrder', cascade: ['persist'])]
    private Collection $orderItems;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTotal(): ?string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException(sprintf('Statut de commande invalide : "%s".', $status));
        }

        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? (string) $this->status;
    }

    public function isPaid(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_SHIPPED], true);
    }

    /** Marque la commande expédiée. Seule une commande payée peut l'être. */
    public function ship(?string $trackingNumber): static
    {
        if (self::STATUS_COMPLETED !== $this->status) {
            throw new \LogicException('Seule une commande payée et pas encore expédiée peut être expédiée.');
        }

        $this->status = self::STATUS_SHIPPED;
        $this->shippedAt = new \DateTimeImmutable();
        $this->trackingNumber = $trackingNumber ? trim($trackingNumber) : null;

        return $this;
    }

    public function getShippedAt(): ?\DateTimeImmutable
    {
        return $this->shippedAt;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    /** La commande peut encore être payée (pas encore payée ni annulée). */
    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING], true);
    }

    /** Montant total en centimes, tel qu'attendu par Stripe. */
    public function getTotalInCents(): int
    {
        return (int) round(((float) $this->total) * 100);
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function addOrderItem(OrderItem $orderItem): static
    {
        if (!$this->orderItems->contains($orderItem)) {
            $this->orderItems->add($orderItem);
            $orderItem->setCustomerOrder($this);
        }

        return $this;
    }

    public function removeOrderItem(OrderItem $orderItem): static
    {
        if ($this->orderItems->removeElement($orderItem)) {
            // set the owning side to null (unless already changed)
            if ($orderItem->getCustomerOrder() === $this) {
                $orderItem->setCustomerOrder(null);
            }
        }

        return $this;
    }
}

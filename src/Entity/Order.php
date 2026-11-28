<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
class Order
{
    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_PAID = 'PAID';
    public const STATUS_FAILED = 'FAILED';
    public const STATUS_EXPIRED = 'EXPIRED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 80)]
    private string $firstName = '';

    #[ORM\Column(length: 80)]
    private string $lastName = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 20)]
    private string $paymentMethod = 'mobile';

    #[ORM\Column(length: 32)]
    private string $status = self::STATUS_PENDING_PAYMENT;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $paymentProvider = null;

    #[ORM\Column(length: 120, nullable: true, unique: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $paymentToken = null;

    #[ORM\Column(type: Types::JSON)]
    private array $ticketSelection = [];

    #[ORM\Column]
    private int $serviceFeeAr = 0;

    #[ORM\Column]
    private int $totalAr = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $reservationExpiresAt = null;

    /** @var Collection<int, Ticket> */
    #[ORM\OneToMany(targetEntity: Ticket::class, mappedBy: 'order', cascade: ['persist'])]
    private Collection $tickets;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->tickets = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEvent(): ?Event { return $this->event; }
    public function setEvent(Event $event): static { $this->event = $event; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $v): static { $this->firstName = $v; return $this; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $v): static { $this->lastName = $v; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }

    public function getPaymentMethod(): string { return $this->paymentMethod; }
    public function setPaymentMethod(string $v): static { $this->paymentMethod = $v; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): static { $this->status = $status; return $this; }
    public function isPaid(): bool { return $this->status === self::STATUS_PAID; }

    public function getPaymentProvider(): ?string { return $this->paymentProvider; }
    public function setPaymentProvider(?string $provider): static { $this->paymentProvider = $provider; return $this; }

    public function getPaymentReference(): ?string { return $this->paymentReference; }
    public function setPaymentReference(?string $reference): static { $this->paymentReference = $reference; return $this; }

    public function getPaymentToken(): ?string { return $this->paymentToken; }
    public function setPaymentToken(?string $token): static { $this->paymentToken = $token; return $this; }

    /** @return array<string, int> */
    public function getTicketSelection(): array { return $this->ticketSelection; }
    /** @param array<string, int> $selection */
    public function setTicketSelection(array $selection): static { $this->ticketSelection = $selection; return $this; }

    public function getServiceFeeAr(): int { return $this->serviceFeeAr; }
    public function setServiceFeeAr(int $v): static { $this->serviceFeeAr = $v; return $this; }

    public function getTotalAr(): int { return $this->totalAr; }
    public function setTotalAr(int $v): static { $this->totalAr = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getReservationExpiresAt(): ?\DateTimeImmutable { return $this->reservationExpiresAt; }
    public function setReservationExpiresAt(?\DateTimeImmutable $expiresAt): static { $this->reservationExpiresAt = $expiresAt; return $this; }

    /** @return Collection<int, Ticket> */
    public function getTickets(): Collection { return $this->tickets; }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setOrder($this);
        }

        return $this;
    }

    public function getTicketCount(): int
    {
        return $this->tickets->count();
    }
}

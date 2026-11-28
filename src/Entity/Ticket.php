<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tickets')]
class Ticket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16, unique: true)]
    private string $code = '';

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $scannedAt = null;

    #[ORM\ManyToOne(inversedBy: 'tickets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TicketType $ticketType = null;

    public function getId(): ?int { return $this->id; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }

    public function getScannedAt(): ?\DateTimeImmutable { return $this->scannedAt; }
    public function markScanned(\DateTimeImmutable $at): static { $this->scannedAt = $at; return $this; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getTicketType(): ?TicketType { return $this->ticketType; }
    public function setTicketType(TicketType $type): static { $this->ticketType = $type; return $this; }
}

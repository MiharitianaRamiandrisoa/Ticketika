<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'ticket_types')]
class TicketType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'ticketTypes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\Column(length: 80)]
    private string $name = '';

    #[ORM\Column]
    private int $priceAr = 0;

    #[ORM\Column]
    private int $quantity = 0;

    #[ORM\Column]
    private int $sold = 0;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $note = null;

    public function getId(): ?int { return $this->id; }

    public function getEvent(): ?Event { return $this->event; }
    public function setEvent(?Event $event): static { $this->event = $event; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getPriceAr(): int { return $this->priceAr; }
    public function setPriceAr(int $price): static { $this->priceAr = $price; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }

    public function getSold(): int { return $this->sold; }
    public function setSold(int $sold): static { $this->sold = $sold; return $this; }
    public function addSold(int $count): static { $this->sold += $count; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }

    public function getRemaining(): int
    {
        return max(0, $this->quantity - $this->sold);
    }
}

<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $title = '';

    #[ORM\Column(length: 200, unique: true)]
    private string $slug = '';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column]
    private \DateTimeImmutable $startsAt;

    #[ORM\Column(length: 120)]
    private string $place = '';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $tag = null;

    #[ORM\Column]
    private int $capacity = 0;

    #[ORM\Column]
    private int $soldTickets = 0;

    /** @var Collection<int, TicketType> */
    #[ORM\OneToMany(targetEntity: TicketType::class, mappedBy: 'event', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['priceAr' => 'ASC'])]
    private Collection $ticketTypes;

    public function __construct()
    {
        $this->startsAt = new \DateTimeImmutable('+7 days');
        $this->ticketTypes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }

    public function getCategory(): ?Category { return $this->category; }
    public function setCategory(Category $category): static { $this->category = $category; return $this; }
    public function getCategorySlug(): string { return $this->category?->getSlug() ?? ''; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getStartsAt(): \DateTimeImmutable { return $this->startsAt; }
    public function setStartsAt(\DateTimeImmutable $startsAt): static { $this->startsAt = $startsAt; return $this; }

    public function getPlace(): string { return $this->place; }
    public function setPlace(string $place): static { $this->place = $place; return $this; }

    public function getTag(): ?string { return $this->tag; }
    public function setTag(?string $tag): static { $this->tag = $tag; return $this; }

    public function getCapacity(): int { return $this->capacity; }
    public function setCapacity(int $capacity): static { $this->capacity = $capacity; return $this; }

    public function getSoldTickets(): int { return $this->soldTickets; }
    public function setSoldTickets(int $sold): static { $this->soldTickets = $sold; return $this; }
    public function addSoldTickets(int $count): static { $this->soldTickets += $count; return $this; }

    /** @return Collection<int, TicketType> */
    public function getTicketTypes(): Collection { return $this->ticketTypes; }

    public function addTicketType(TicketType $type): static
    {
        if (!$this->ticketTypes->contains($type)) {
            $this->ticketTypes->add($type);
            $type->setEvent($this);
        }

        return $this;
    }

    public function getCategoryLabel(): string
    {
        return $this->category?->getName() ?? 'Événement';
    }

    public function getGradientClass(): string
    {
        return match ($this->getCategorySlug()) {
            'musique' => 'bg-gradient-to-br from-olympia-fonce to-olympia-bleu',
            'spectacle' => 'bg-gradient-to-br from-olympia-bleu to-olympia-sauge',
            'famille' => 'bg-gradient-to-br from-olympia-sauge to-olympia-bleu',
            'ateliers' => 'bg-gradient-to-br from-olympia-sable-fonce to-olympia-dore',
            default => 'bg-gradient-to-br from-olympia-bleu to-olympia-fonce',
        };
    }

    public function getMinPrice(): int
    {
        $prices = array_map(static fn (TicketType $t) => $t->getPriceAr(), $this->ticketTypes->toArray());

        return $prices ? min($prices) : 0;
    }

    public function getRemaining(): int
    {
        return max(0, $this->capacity - $this->soldTickets);
    }

    public function isSoldOut(): bool
    {
        return $this->getRemaining() === 0;
    }

    public static function slugify(string $text): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($ascii)), '-');
    }
}

<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(
    name: 'payment_webhook_events',
    uniqueConstraints: [
        new ORM\UniqueConstraint(name: 'UNIQ_PAYMENT_WEBHOOK_PROVIDER_KEY', columns: ['provider', 'event_key']),
    ],
)]
class PaymentWebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(length: 32)]
    private string $provider = '';

    #[ORM\Column(name: 'event_key', length: 191)]
    private string $eventKey = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $merchantPaymentReference = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Order $order = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $paymentStatus = null;

    #[ORM\Column(nullable: true)]
    private ?int $amountAr = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(length: 64)]
    private string $payloadHash = '';

    #[ORM\Column]
    private \DateTimeImmutable $receivedAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $processingError = null;

    public function __construct()
    {
        $this->receivedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getProvider(): string { return $this->provider; }
    public function setProvider(string $provider): static { $this->provider = $provider; return $this; }
    public function getEventKey(): string { return $this->eventKey; }
    public function setEventKey(string $eventKey): static { $this->eventKey = $eventKey; return $this; }
    public function getMerchantPaymentReference(): ?string { return $this->merchantPaymentReference; }
    public function setMerchantPaymentReference(?string $reference): static { $this->merchantPaymentReference = $reference; return $this; }
    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }
    public function getPaymentStatus(): ?string { return $this->paymentStatus; }
    public function setPaymentStatus(?string $status): static { $this->paymentStatus = $status; return $this; }
    public function getAmountAr(): ?int { return $this->amountAr; }
    public function setAmountAr(?int $amount): static { $this->amountAr = $amount; return $this; }
    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(?string $currency): static { $this->currency = $currency; return $this; }
    public function getPayloadHash(): string { return $this->payloadHash; }
    public function setPayloadHash(string $hash): static { $this->payloadHash = $hash; return $this; }
    public function getReceivedAt(): \DateTimeImmutable { return $this->receivedAt; }
    public function getProcessedAt(): ?\DateTimeImmutable { return $this->processedAt; }
    public function setProcessedAt(?\DateTimeImmutable $processedAt): static { $this->processedAt = $processedAt; return $this; }
    public function getProcessingError(): ?string { return $this->processingError; }
    public function setProcessingError(?string $error): static { $this->processingError = $error; return $this; }
}

<?php

namespace App\Service;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PapiClient
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urls,
        private readonly string $mode,
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly string $publicBaseUrl,
    ) {
    }

    public function isFake(): bool
    {
        return strtolower($this->mode) !== 'papi';
    }

    public function createPaymentLink(Order $order): string
    {
        $reference = 'ORDER-'.$order->getId();
        $order->setPaymentProvider($this->isFake() ? 'FAKE' : 'PAPI')
            ->setPaymentReference($reference);

        if ($this->isFake()) {
            $this->em->flush();

            return $this->urls->generate('payment_test', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ($this->apiKey === '') {
            throw new \RuntimeException('La clé API Papi n’est pas configurée.');
        }

        $payload = [
            'amount' => $order->getTotalAr(),
            'clientName' => trim($order->getFirstName().' '.$order->getLastName()),
            'reference' => $reference,
            'description' => 'Billets '.$order->getEvent()?->getTitle(),
            'successUrl' => $this->url('payment_return', ['id' => $order->getId()]),
            'failureUrl' => $this->url('payment_return', ['id' => $order->getId()]),
            'notificationUrl' => $this->url('payment_webhook'),
            'validDuration' => 1,
            'payerEmail' => $order->getEmail(),
        ];

        $response = $this->request('POST', '/payment-links', $payload);
        $data = $response['data'] ?? [];
        $link = $data['paymentLink'] ?? null;
        if (!is_string($link) || $link === '') {
            throw new \RuntimeException('Papi n’a pas renvoyé de lien de paiement.');
        }

        $order->setPaymentToken(isset($data['notificationToken']) ? (string) $data['notificationToken'] : null);
        $this->em->flush();

        return $link;
    }

    public function getPaymentStatus(Order $order): ?string
    {
        if ($this->isFake() || $order->getPaymentReference() === null || $this->apiKey === '') {
            return null;
        }

        $response = $this->request('GET', '/payment-links/'.rawurlencode($order->getPaymentReference()));

        return isset($response['data']['paymentStatus']) ? (string) $response['data']['paymentStatus'] : null;
    }

    /** @param array<string, mixed> $payload @return array<string, mixed> */
    private function request(string $method, string $path, array $payload = []): array
    {
        $handle = curl_init(rtrim($this->baseUrl, '/').$path);
        if ($handle === false) {
            throw new \RuntimeException('Impossible d’initialiser la connexion à Papi.');
        }

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Token: '.$this->apiKey,
            ],
        ];
        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_THROW_ON_ERROR);
        }
        curl_setopt_array($handle, $options);

        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($body === false) {
            throw new \RuntimeException('Papi est momentanément indisponible : '.$error);
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Réponse Papi invalide.');
        }
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException((string) ($decoded['error']['message'] ?? 'Erreur Papi.'));
        }

        return $decoded;
    }

    private function url(string $route, array $parameters = []): string
    {
        $path = $this->urls->generate($route, $parameters);
        if ($this->publicBaseUrl !== '') {
            return rtrim($this->publicBaseUrl, '/').$path;
        }

        return $this->urls->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}

<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\PaymentWebhookEvent;
use App\Service\AuthService;
use App\Service\OrderPaymentFinalizer;
use App\Service\PapiClient;
use App\Service\PapiWebhookVerifier;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController extends AbstractController
{
    #[Route('/paiement/test/{id}', name: 'payment_test', methods: ['GET', 'POST'])]
    public function test(
        Order $order,
        Request $request,
        AuthService $auth,
        PapiClient $papi,
        OrderPaymentFinalizer $finalizer,
    ): Response {
        if (!$papi->isFake()) {
            throw $this->createNotFoundException();
        }

        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('login');
        }
        if ($order->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('payment_test_'.$order->getId(), (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException();
            }

            if ($request->request->get('action') === 'success') {
                $finalizer->markPaid($order);
            } else {
                $finalizer->markFailed($order);
            }

            return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
        }

        return $this->render('payment/test.html.twig', ['order' => $order]);
    }

    #[Route('/paiement/retour/{id}', name: 'payment_return', methods: ['GET'])]
    public function returnFromProvider(Order $order, PapiClient $papi): Response
    {
        try {
            $status = $papi->getPaymentStatus($order);
            if ($status === 'SUCCESS' && !$order->isPaid()) {
                $this->addFlash('success', 'Paiement reçu. La confirmation du billet est en cours.');
            } elseif ($status === 'FAILED') {
                $this->addFlash('error', 'Le paiement n’a pas été confirmé.');
            }
        } catch (\RuntimeException) {
            $this->addFlash('error', 'Le statut du paiement est momentanément indisponible.');
        }

        return $this->redirectToRoute('order_show', ['id' => $order->getId()]);
    }

    #[Route('/paiement/papi/webhook', name: 'payment_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $em,
        PapiWebhookVerifier $verifier,
        OrderPaymentFinalizer $finalizer,
    ): JsonResponse {
        $rawBody = $request->getContent();
        if (!$verifier->isValid($rawBody, $request->headers->get('X-Papi-Signature'))) {
            return new JsonResponse(['error' => 'Signature invalide.'], Response::HTTP_UNAUTHORIZED);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $payloadHash = hash('sha256', $rawBody);
        $eventKey = $payload['eventId'] ?? $payload['event_id'] ?? $payload['id'] ?? $payloadHash;
        if (!is_string($eventKey) || $eventKey === '') {
            $eventKey = $payloadHash;
        }

        $existingEvent = $em->getRepository(PaymentWebhookEvent::class)->findOneBy([
            'provider' => 'PAPI',
            'eventKey' => $eventKey,
        ]);
        if ($existingEvent instanceof PaymentWebhookEvent) {
            return new JsonResponse(['received' => true, 'duplicate' => true]);
        }

        $reference = $payload['merchantPaymentReference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            return new JsonResponse(['error' => 'Référence absente.'], Response::HTTP_BAD_REQUEST);
        }

        $order = $em->getRepository(Order::class)->findOneBy(['paymentReference' => $reference]);
        if (!$order instanceof Order) {
            return new JsonResponse(['error' => 'Commande introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $token = $payload['notificationToken'] ?? null;
        if ($order->getPaymentToken() !== null
            && (!is_string($token) || !hash_equals($order->getPaymentToken(), $token))) {
            return new JsonResponse(['error' => 'Jeton invalide.'], Response::HTTP_UNAUTHORIZED);
        }

        $amountValue = $payload['amount'] ?? $payload['paymentAmount'] ?? null;
        $amountAr = $amountValue === null ? null : (is_numeric($amountValue) ? (int) $amountValue : null);
        if ($amountValue !== null && $amountAr !== $order->getTotalAr()) {
            return new JsonResponse(['error' => 'Montant invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $status = strtoupper((string) ($payload['paymentStatus'] ?? ''));
        $event = (new PaymentWebhookEvent())
            ->setProvider('PAPI')
            ->setEventKey($eventKey)
            ->setMerchantPaymentReference($reference)
            ->setOrder($order)
            ->setPaymentStatus($status)
            ->setAmountAr($amountAr)
            ->setCurrency(isset($payload['currency']) ? strtoupper((string) $payload['currency']) : null)
            ->setPayloadHash($payloadHash);
        $em->persist($event);

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException) {
            $em->clear();

            return new JsonResponse(['received' => true, 'duplicate' => true]);
        }

        try {
            if ($status === 'SUCCESS') {
                $finalizer->markPaid($order);
            } elseif ($status === 'FAILED') {
                $finalizer->markFailed($order);
            }

            $event->setProcessedAt(new \DateTimeImmutable());
            $em->flush();
        } catch (\Throwable $exception) {
            $event->setProcessingError(substr($exception->getMessage(), 0, 255));
            $em->flush();

            return new JsonResponse(['error' => 'Traitement temporairement indisponible.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['received' => true]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\AuthService;
use App\Service\OrderPaymentFinalizer;
use App\Service\PapiClient;
use App\Service\PapiWebhookVerifier;
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
    public function returnFromProvider(
        Order $order,
        PapiClient $papi,
        OrderPaymentFinalizer $finalizer,
    ): Response {
        try {
            $status = $papi->getPaymentStatus($order);
            if ($status === 'SUCCESS') {
                $finalizer->markPaid($order);
            } elseif ($status === 'FAILED') {
                $finalizer->markFailed($order);
            }
        } catch (\RuntimeException $exception) {
            $this->addFlash('error', $exception->getMessage());
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

        $reference = $payload['merchantPaymentReference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            return new JsonResponse(['error' => 'Référence absente.'], Response::HTTP_BAD_REQUEST);
        }

        $order = $em->getRepository(Order::class)->findOneBy(['paymentReference' => $reference]);
        if (!$order instanceof Order) {
            return new JsonResponse(['error' => 'Commande introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $token = $payload['notificationToken'] ?? null;
        if ($order->getPaymentToken() !== null && $token !== $order->getPaymentToken()) {
            return new JsonResponse(['error' => 'Jeton invalide.'], Response::HTTP_UNAUTHORIZED);
        }

        $status = strtoupper((string) ($payload['paymentStatus'] ?? ''));
        if ($status === 'SUCCESS') {
            $finalizer->markPaid($order);
        } elseif ($status === 'FAILED') {
            $finalizer->markFailed($order);
        }

        return new JsonResponse(['received' => true]);
    }
}

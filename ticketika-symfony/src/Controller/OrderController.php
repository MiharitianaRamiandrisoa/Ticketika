<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Order;
use App\Form\OrderType;
use App\Service\AuthService;
use App\Service\OrderPlacer;
use App\Service\OrderPaymentFinalizer;
use App\Service\PapiClient;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/evenements/{slug}/billets', name: 'checkout', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function checkout(
        Request $request,
        #[MapEntity(mapping: ['slug' => 'slug'])] Event $event,
        OrderPlacer $placer,
        PapiClient $papi,
        OrderPaymentFinalizer $finalizer,
        AuthService $auth,
    ): Response {
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('login');
        }

        $source = $request->isMethod('POST') ? $request->request->all('qty') : $request->query->all('qty');
        $quantities = array_filter(array_map('intval', $source));

        if ($quantities === []) {
            return $this->redirectToRoute('event_show', ['slug' => $event->getSlug()]);
        }

        $form = $this->createForm(OrderType::class, [
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'paymentMethod' => 'mobile',
        ]);
        $form->handleRequest($request);

        if ($request->isMethod('POST') && $form->isSubmitted() && $form->isValid()) {
            try {
                $order = $placer->place($event, $quantities, $form->getData(), $user);
                $paymentUrl = $papi->createPaymentLink($order);
            } catch (\RuntimeException $e) {
                if (isset($order)) {
                    $finalizer->markFailed($order);
                }
                $this->addFlash('error', $e->getMessage());

                return $this->redirectToRoute('event_show', ['slug' => $event->getSlug()]);
            }

            return $this->redirect($paymentUrl);
        }

        $subtotal = 0;
        foreach ($event->getTicketTypes() as $type) {
            $subtotal += ($quantities[$type->getId()] ?? 0) * $type->getPriceAr();
        }
        $fee = $subtotal > 0 ? OrderPlacer::SERVICE_FEE_AR : 0;

        return $this->render('order/checkout.html.twig', [
            'event' => $event,
            'form' => $form,
            'selection' => $quantities,
            'subtotal' => $subtotal,
            'fee' => $fee,
            'total' => $subtotal + $fee,
        ]);
    }

    #[Route('/commandes/{id}', name: 'order_show', requirements: ['id' => '\\d+'])]
    public function show(Order $order, Request $request, AuthService $auth): Response
    {
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('login');
        }
        if ($order->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('order/confirmation.html.twig', ['order' => $order]);
    }
}

<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Order;
use App\Entity\TicketType;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

#[Route('/organisateur')]
final class OrganizerController extends AbstractController
{
    #[Route('', name: 'organizer_dashboard')]
    public function dashboard(Request $request, EntityManagerInterface $em, EventRepository $events, AuthService $auth): Response
    {
        if (($access = $this->requireOrganizer($request, $auth)) !== null) {
            return $access;
        }

        $allEvents = $em->getRepository(Event::class)->findAll();
        $orders = $em->getRepository(Order::class)->findAll();

        $sold = array_sum(array_map(static fn (Event $e) => $e->getSoldTickets(), $allEvents));
        $capacity = array_sum(array_map(static fn (Event $e) => $e->getCapacity(), $allEvents));
        $revenue = array_sum(array_map(
            static fn (Order $o) => $o->isPaid() ? $o->getTotalAr() : 0,
            $orders,
        ));
        $fill = $capacity > 0 ? (int) round($sold / $capacity * 100) : 0;

        // Ventes des 7 derniers jours
        $week = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = new \DateTimeImmutable("-$i day");
            $week[$day->format('Y-m-d')] = ['label' => $day->format('D'), 'count' => 0];
        }
        foreach ($orders as $order) {
            $key = $order->getCreatedAt()->format('Y-m-d');
            if (isset($week[$key])) {
                $week[$key]['count'] += $order->getTicketCount();
            }
        }
        $max = max(1, max(array_column($week, 'count')));
        foreach ($week as &$day) {
            $day['height'] = max(4, (int) round($day['count'] / $max * 100));
        }
        unset($day);

        return $this->render('organizer/dashboard.html.twig', [
            'kpis' => [
                ['label' => 'Billets vendus', 'value' => number_format($sold, 0, ',', ' ')],
                ['label' => "Chiffre d'affaires", 'value' => number_format($revenue, 0, ',', ' ').' Ar'],
                ['label' => 'Événements actifs', 'value' => count($events->findUpcoming())],
                ['label' => 'Taux de remplissage', 'value' => $fill.' %'],
            ],
            'week' => array_values($week),
            'upcoming' => array_slice($events->findUpcoming(), 0, 3),
        ]);
    }

    #[Route('/evenements/nouveau', name: 'organizer_event_new', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $em, AuthService $auth): Response
    {
        if (($access = $this->requireOrganizer($request, $auth)) !== null) {
            return $access;
        }

        $event = new Event();
        $event->addTicketType(new TicketType());
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->synchronizeCapacity($event, $form)) {
                $event->setSlug(Event::slugify($event->getTitle()).'-'.bin2hex(random_bytes(2)));

                $em->persist($event);
                $em->flush();

                $this->addFlash('success', 'Événement créé et publié.');

                return $this->redirectToRoute('organizer_dashboard');
            }
        }

        return $this->render('organizer/create.html.twig', ['form' => $form]);
    }

    #[Route('/evenements/{id}/modifier', name: 'organizer_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    public function edit(Event $event, Request $request, EntityManagerInterface $em, AuthService $auth): Response
    {
        if (($access = $this->requireOrganizer($request, $auth)) !== null) {
            return $access;
        }

        $firstTicketType = $event->getTicketTypes()->first();
        if (!$firstTicketType instanceof TicketType) {
            $event->addTicketType(new TicketType());
        }
        $originalTicketTypes = $event->getTicketTypes()->toArray();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $deletedSoldTypes = array_filter(
                $originalTicketTypes,
                static fn (TicketType $type): bool => $type->getSold() > 0 && !$event->getTicketTypes()->contains($type),
            );
            foreach ($deletedSoldTypes as $deletedSoldType) {
                $event->addTicketType($deletedSoldType);
            }
            if ($deletedSoldTypes) {
                $form->get('ticketTypes')->addError(new FormError('Un type de billet déjà vendu ne peut pas être supprimé.'));
            }

            if (!$deletedSoldTypes && $this->synchronizeCapacity($event, $form)) {
                $em->flush();
                $this->addFlash('success', 'Événement mis à jour.');

                return $this->redirectToRoute('event_show', ['slug' => $event->getSlug()]);
            }
        }

        return $this->render('organizer/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    private function synchronizeCapacity(Event $event, FormInterface $form): bool
    {
        $capacity = 0;
        $hasError = false;
        foreach ($event->getTicketTypes() as $ticketType) {
            $capacity += $ticketType->getQuantity();
            if ($ticketType->getQuantity() < $ticketType->getSold()) {
                $form->get('ticketTypes')->addError(new FormError(sprintf(
                    'La quantité du billet « %s » ne peut pas être inférieure aux billets déjà vendus.',
                    $ticketType->getName(),
                )));
                $hasError = true;
            }
        }

        if ($capacity < 1) {
            $form->get('ticketTypes')->addError(new FormError('Ajoutez au moins un type de billet avec une quantité positive.'));
            $hasError = true;
        }
        if ($capacity < $event->getSoldTickets()) {
            $form->get('ticketTypes')->addError(new FormError('La capacité totale ne peut pas être inférieure aux billets déjà vendus.'));
            $hasError = true;
        }

        if (!$hasError) {
            $event->setCapacity($capacity);
        }

        return !$hasError;
    }

    private function requireOrganizer(Request $request, AuthService $auth): ?Response
    {
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('organizer_login');
        }

        return $user->isOrganizer() ? null : $this->redirectToRoute('organizer_login');
    }
}

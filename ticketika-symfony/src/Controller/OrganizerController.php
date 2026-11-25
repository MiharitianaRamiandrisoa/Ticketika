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

#[Route('/organisateur')]
final class OrganizerController extends AbstractController
{
    #[Route('', name: 'organizer_dashboard')]
    public function dashboard(Request $request, EntityManagerInterface $em, EventRepository $events, AuthService $auth): Response
    {
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('organizer_login');
        }
        if (!$user->isOrganizer()) {
            return $this->redirectToRoute('organizer_login');
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
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('organizer_login');
        }
        if (!$user->isOrganizer()) {
            return $this->redirectToRoute('organizer_login');
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setSlug(Event::slugify($event->getTitle()).'-'.bin2hex(random_bytes(2)));
            $event->addTicketType((new TicketType())
                ->setName('Standard')
                ->setPriceAr((int) $form->get('priceAr')->getData())
                ->setQuantity($event->getCapacity()));

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Événement créé et publié.');

            return $this->redirectToRoute('organizer_dashboard');
        }

        return $this->render('organizer/create.html.twig', ['form' => $form]);
    }
}

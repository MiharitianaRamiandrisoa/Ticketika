<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evenements')]
final class EventController extends AbstractController
{
    #[Route('', name: 'events')]
    public function index(Request $request, EventRepository $events, CategoryRepository $categories): Response
    {
        $category = $request->query->getString('cat') ?: null;
        $search = $request->query->getString('q') ?: null;

        return $this->render('event/index.html.twig', [
            'events' => $events->findUpcoming($category, $search),
            'category' => $category,
            'categories' => $categories->findActive(),
            'search' => $search,
        ]);
    }

    #[Route('/{slug}', name: 'event_show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Event $event, EventRepository $events): Response
    {
        // Suggestions : même catégorie d'abord, puis le reste des événements à venir
        $others = array_values(array_filter(
            $events->findUpcoming(),
            static fn (Event $e) => $e->getId() !== $event->getId(),
        ));
        usort($others, static fn (Event $a, Event $b) => (int) ($b->getCategorySlug() === $event->getCategorySlug()) <=> (int) ($a->getCategorySlug() === $event->getCategorySlug()));

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'similar' => array_slice($others, 0, 3),
        ]);
    }
}

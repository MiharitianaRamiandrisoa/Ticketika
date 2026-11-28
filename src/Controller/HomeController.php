<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(EventRepository $events, CategoryRepository $categories): Response
    {
        $upcoming = $events->findUpcoming();

        return $this->render('home/index.html.twig', [
            'events' => array_slice($upcoming, 0, 4),
            'tonight' => array_slice($upcoming, 0, 3),
            'categories' => $categories->findActive(),
        ]);
    }
}

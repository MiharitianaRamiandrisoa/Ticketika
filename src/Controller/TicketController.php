<?php

namespace App\Controller;

use App\Entity\Order;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TicketController extends AbstractController
{
    #[Route('/mon-espace', name: 'account')]
    #[Route('/mes-billets', name: 'tickets')]
    public function index(Request $request, EntityManagerInterface $em, AuthService $auth): Response
    {
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('login');
        }

        $orders = $em->getRepository(Order::class)->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $ticketCount = array_sum(array_map(static fn (Order $order) => $order->getTicketCount(), $orders));

        return $this->render('user/account.html.twig', ['user' => $user, 'orders' => $orders, 'ticketCount' => $ticketCount]);
    }
}

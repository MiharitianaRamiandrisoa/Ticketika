<?php

namespace App\Controller;

use App\Entity\Ticket;
use App\Service\AuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/organisateur/scan')]
final class ScanController extends AbstractController
{
    #[Route('', name: 'organizer_scan')]
    public function index(Request $request, EntityManagerInterface $em, AuthService $auth): Response
    {
        $user = $auth->getUser($request);
        if ($user === null) {
            $auth->rememberTarget($request, $request->getRequestUri());

            return $this->redirectToRoute('organizer_login');
        }
        if (!$user->isOrganizer()) {
            return $this->redirectToRoute('organizer_login');
        }

        $repo = $em->getRepository(Ticket::class);
        $total = $repo->count([]);
        $scanned = (int) $em->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Ticket::class, 't')
            ->where('t.scannedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        // Codes de démonstration pour simuler un scan
        $codes = array_map(static fn (Ticket $t) => $t->getCode(), $repo->findBy([], ['id' => 'DESC'], 10));

        return $this->render('organizer/scan.html.twig', [
            'stats' => ['in' => $scanned, 'left' => $total - $scanned],
            'codes' => $codes,
        ]);
    }

    #[Route('/valider', name: 'organizer_scan_validate', methods: ['POST'])]
    public function validate(Request $request, EntityManagerInterface $em, AuthService $auth): Response
    {
        if (!$auth->getUser($request)?->isOrganizer()) {
            return $this->json(['status' => 'refuse', 'message' => 'Accès réservé aux organisateurs.'], 403);
        }

        $code = strtoupper(trim($request->request->getString('code')));
        $ticket = $code !== '' ? $em->getRepository(Ticket::class)->findOneBy(['code' => $code]) : null;

        if ($ticket === null) {
            return $this->json(['status' => 'refuse', 'message' => 'Billet introuvable'], 404);
        }
        if ($ticket->getScannedAt() !== null) {
            return $this->json(['status' => 'deja', 'message' => 'Ce billet a déjà été scanné', 'code' => $code]);
        }

        $ticket->markScanned(new \DateTimeImmutable());
        $em->flush();

        return $this->json(['status' => 'valide', 'message' => 'Entrée autorisée', 'code' => $code]);
    }
}

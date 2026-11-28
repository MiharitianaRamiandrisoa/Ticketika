<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\AuthService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AuthExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly AuthService $auth,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('ticketika_current_user', fn (): ?User => $this->getCurrentUser()),
            new TwigFunction('ticketika_is_organizer', fn (): bool => $this->getCurrentUser()?->isOrganizer() === true),
        ];
    }

    private function getCurrentUser(): ?User
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }
        if ($request->attributes->has('_ticketika_current_user')) {
            $user = $request->attributes->get('_ticketika_current_user');

            return $user instanceof User ? $user : null;
        }

        $user = $this->auth->getUser($request);
        $request->attributes->set('_ticketika_current_user', $user);

        return $user;
    }
}

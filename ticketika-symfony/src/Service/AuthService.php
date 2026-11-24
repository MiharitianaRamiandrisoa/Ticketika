<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;

final class AuthService
{
    private const USER_ID = 'ticketika_user_id';
    private const TARGET = 'ticketika_auth_target';

    public function __construct(private readonly UserRepository $users)
    {
    }

    public function getUser(Request $request): ?User
    {
        $id = $request->getSession()->get(self::USER_ID);
        if (!$id) {
            return null;
        }

        $user = $this->users->find($id);
        if ($user === null) {
            $request->getSession()->remove(self::USER_ID);
        }

        return $user;
    }

    public function login(Request $request, User $user): void
    {
        $request->getSession()->migrate(true);
        $request->getSession()->set(self::USER_ID, $user->getId());
    }

    public function logout(Request $request): void
    {
        $request->getSession()->clear();
    }

    public function rememberTarget(Request $request, string $target): void
    {
        if (str_starts_with($target, '/')) {
            $request->getSession()->set(self::TARGET, $target);
        }
    }

    public function consumeTarget(Request $request, string $fallback): string
    {
        $target = $request->getSession()->get(self::TARGET, $fallback);
        $request->getSession()->remove(self::TARGET);

        return is_string($target) && str_starts_with($target, '/') ? $target : $fallback;
    }
}

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
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }

        $user = $this->users->find((int) $id);
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
        $request->getSession()->invalidate();
    }

    public function rememberTarget(Request $request, string $target): void
    {
        // Only keep local paths. In particular, reject //host.example which
        // browsers interpret as a scheme-relative external URL.
        if ($this->isLocalPath($target)) {
            $request->getSession()->set(self::TARGET, $target);
        }
    }

    public function consumeTarget(Request $request, string $fallback): string
    {
        $target = $request->getSession()->get(self::TARGET, $fallback);
        $request->getSession()->remove(self::TARGET);

        return is_string($target) && $this->isLocalPath($target) ? $target : $fallback;
    }

    private function isLocalPath(string $target): bool
    {
        // Reject scheme-relative and backslash-normalized external URLs.
        return str_starts_with($target, '/')
            && !str_starts_with($target, '//')
            && !str_starts_with($target, '/\\');
    }
}

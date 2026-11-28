<?php

namespace App\Service;

final class PapiWebhookVerifier
{
    public function __construct(private readonly string $secret)
    {
    }

    public function isValid(string $rawBody, ?string $header): bool
    {
        if ($this->secret === '' || $header === null || $header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $item) {
            $pair = explode('=', trim($item), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';
        if (!ctype_digit($timestamp) || !preg_match('/\A[0-9a-f]{64}\z/', $signature)) {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', $timestamp.'.'.$rawBody, $this->secret),
            $signature,
        );
    }
}

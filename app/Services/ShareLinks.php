<?php

namespace App\Services;

use App\Models\ReadingList;
use RuntimeException;

/**
 * Read-only share links: `{list id}.{expiry (Unix seconds)}.{HMAC-SHA256 hex}`, signed with the server-side share key
 * (V0_SECRET_CANARY). The signature covers the token's payload exactly as written, so any change to the token, a
 * truncation or an extension invalidates it. Nothing is stored: a link lives until it expires or its list is deleted.
 */
class ShareLinks
{
    /** Offered expiry periods: form value => seconds. */
    public const array EXPIRIES = [
        '1m' => 60,
        '1d' => 86_400,
        '7d' => 604_800,
    ];

    public const string DEFAULT_EXPIRY = '7d';

    private const string TOKEN_PATTERN = '/\A([1-9][0-9]{0,18})\.([1-9][0-9]{0,18})\.([0-9a-f]{64})\z/';

    public function __construct(private readonly string $key) {}

    /**
     * @param  key-of<self::EXPIRIES>  $expiry
     */
    public function create(ReadingList $list, string $expiry): string
    {
        $payload = $list->id.'.'.(now()->getTimestamp() + self::EXPIRIES[$expiry]);

        return $payload.'.'.$this->sign($payload);
    }

    /**
     * Verifies a token and returns the list id and expiry it binds, or null when the token is not one this app issued.
     *
     * @return array{list: int, expires: int}|null
     */
    public function verify(string $token): ?array
    {
        if (preg_match(self::TOKEN_PATTERN, $token, $parts) !== 1) {
            return null;
        }

        [, $list, $expires, $signature] = $parts;
        if (! hash_equals($this->sign($list.'.'.$expires), $signature)) {
            return null;
        }

        return ['list' => (int) $list, 'expires' => (int) $expires];
    }

    private function sign(string $payload): string
    {
        if ($this->key === '') {
            throw new RuntimeException('The share-link signing key (V0_SECRET_CANARY) is not configured.');
        }

        return hash_hmac('sha256', 'share-link:v1:'.$payload, $this->key);
    }
}

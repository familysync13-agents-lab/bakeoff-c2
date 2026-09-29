<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

/**
 * Client for the public book-search API (Open Library /search.json format).
 */
class BookSearch
{
    public const int LIMIT = 10;

    /** Total time allowed for one API call, connection included (the contract requires giving up within 5 s). */
    public const int TIMEOUT_SECONDS = 4;

    /**
     * @return list<array{key: string|null, title: string, authors: list<string>, year: int|null}>
     *
     * @throws BookSearchUnavailable when the API cannot be reached, times out, fails or answers malformed data
     */
    public function search(string $query): array
    {
        $baseUrl = config('services.book_api.base_url');
        if (! is_string($baseUrl) || $baseUrl === '') {
            throw new BookSearchUnavailable('BOOK_API_BASE_URL is not configured');
        }

        try {
            $response = Http::connectTimeout(2)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get(rtrim($baseUrl, '/').'/search.json', ['q' => $query, 'limit' => self::LIMIT]);
        } catch (ConnectionException $e) {
            return $this->fail('request failed: '.$e->getMessage());
        }

        if (! $response->successful()) {
            return $this->fail('HTTP '.$response->status());
        }

        try {
            $data = json_decode($response->body(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->fail('invalid JSON');
        }

        if (! is_array($data) || ! isset($data['docs']) || ! is_array($data['docs']) || ! array_is_list($data['docs'])) {
            return $this->fail('unexpected response shape');
        }

        $results = [];
        foreach (array_slice($data['docs'], 0, self::LIMIT) as $doc) {
            if (! is_array($doc)) {
                return $this->fail('unexpected document shape');
            }
            $results[] = self::normalize($doc);
        }

        return $results;
    }

    /**
     * Every field of a document may be missing or of an unexpected type.
     *
     * @param  array<mixed>  $doc
     * @return array{key: string|null, title: string, authors: list<string>, year: int|null}
     */
    private static function normalize(array $doc): array
    {
        $authors = [];
        foreach (is_array($doc['author_name'] ?? null) ? $doc['author_name'] : [] as $name) {
            if (is_string($name) && trim($name) !== '') {
                $authors[] = mb_substr(trim($name), 0, 200);
            }
        }

        $key = $doc['key'] ?? null;
        $title = $doc['title'] ?? null;
        $year = $doc['first_publish_year'] ?? null;

        return [
            'key' => is_string($key) && $key !== '' ? mb_substr($key, 0, 200) : null,
            'title' => is_string($title) && trim($title) !== '' ? mb_substr(trim($title), 0, 500) : 'Untitled',
            'authors' => array_slice($authors, 0, 10),
            'year' => is_int($year) ? $year : null,
        ];
    }

    private function fail(string $reason): never
    {
        Log::warning('Book search unavailable: '.$reason);

        throw new BookSearchUnavailable($reason);
    }
}

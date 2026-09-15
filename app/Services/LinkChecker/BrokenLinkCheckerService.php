<?php

namespace App\Services\LinkChecker;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class BrokenLinkCheckerService
{
    /**
     * Check a list of URLs concurrently and report their status.
     *
     * @param  string[]  $urls
     * @return array<int, array{url: string, status: int|null, ok: bool, redirected_to: ?string, error: ?string}>
     */
    public function check(array $urls): array
    {
        $timeout = (int) config('tools.link_checker.timeout');
        $concurrency = (int) config('tools.link_checker.concurrency');

        $results = [];

        foreach (array_chunk($urls, max(1, $concurrency)) as $chunk) {
            $responses = Http::pool(fn ($pool) => collect($chunk)
                ->mapWithKeys(fn (string $url) => [
                    $url => $pool->as($url)
                        ->timeout($timeout)
                        ->withOptions(['allow_redirects' => ['track_redirects' => true]])
                        ->head($url),
                ])
                ->all());

            foreach ($chunk as $url) {
                $results[] = $this->formatResult($url, $responses[$url], $timeout);
            }
        }

        return $results;
    }

    /**
     * @return array{url: string, status: int|null, ok: bool, redirected_to: ?string, error: ?string}
     */
    protected function formatResult(string $url, Response|Throwable $response, int $timeout): array
    {
        // A HEAD request that errored, or one some servers reject (405/501), gets one GET retry.
        if ($response instanceof Throwable || in_array($response->status(), [405, 501], true)) {
            try {
                $response = Http::timeout($timeout)
                    ->withOptions(['allow_redirects' => ['track_redirects' => true]])
                    ->get($url);
            } catch (Throwable $e) {
                return [
                    'url' => $url,
                    'status' => null,
                    'ok' => false,
                    'redirected_to' => null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'url' => $url,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'redirected_to' => $this->finalUrl($response, $url),
            'error' => null,
        ];
    }

    protected function finalUrl(Response $response, string $original): ?string
    {
        $effective = $response->effectiveUri();
        $final = $effective ? (string) $effective : null;

        return ($final && $final !== $original) ? $final : null;
    }
}

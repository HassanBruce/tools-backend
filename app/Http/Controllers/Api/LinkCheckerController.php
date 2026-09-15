<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LinkChecker\CheckLinksRequest;
use App\Services\LinkChecker\BrokenLinkCheckerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Throwable;

class LinkCheckerController extends Controller
{
    public function check(CheckLinksRequest $request, BrokenLinkCheckerService $service): JsonResponse
    {
        $urls = $request->input('urls', []);

        if ($sitemapUrl = $request->input('sitemap_url')) {
            $urls = array_merge($urls, $this->urlsFromSitemap($sitemapUrl));
        }

        $urls = array_values(array_unique($urls));

        $maxUrls = config('tools.link_checker.max_urls');

        if (count($urls) > $maxUrls) {
            $urls = array_slice($urls, 0, $maxUrls);
        }

        if ($urls === []) {
            return response()->json(['message' => 'No URLs found to check.'], 422);
        }

        $results = $service->check($urls);

        return response()->json([
            'checked' => count($results),
            'broken' => collect($results)->where('ok', false)->count(),
            'results' => $results,
        ]);
    }

    /**
     * @return string[]
     */
    protected function urlsFromSitemap(string $sitemapUrl): array
    {
        $entries = $this->fetchSitemapDocument($sitemapUrl);
        $urls = $entries['urls'];

        // Sitemap index (nested sitemaps) — fetch one level deep only to keep this bounded.
        foreach ($entries['sitemaps'] as $nestedUrl) {
            $urls = array_merge($urls, $this->fetchSitemapDocument($nestedUrl)['urls']);
        }

        return $urls;
    }

    /**
     * @return array{urls: string[], sitemaps: string[]}
     */
    protected function fetchSitemapDocument(string $url): array
    {
        try {
            $response = Http::timeout(config('tools.link_checker.timeout'))->get($url);
        } catch (Throwable) {
            return ['urls' => [], 'sitemaps' => []];
        }

        if (! $response->successful()) {
            return ['urls' => [], 'sitemaps' => []];
        }

        $xml = @simplexml_load_string($response->body());

        if ($xml === false) {
            return ['urls' => [], 'sitemaps' => []];
        }

        $urls = [];
        foreach ($xml->url ?? [] as $entry) {
            if (isset($entry->loc)) {
                $urls[] = (string) $entry->loc;
            }
        }

        $sitemaps = [];
        foreach ($xml->sitemap ?? [] as $entry) {
            if (isset($entry->loc)) {
                $sitemaps[] = (string) $entry->loc;
            }
        }

        return ['urls' => $urls, 'sitemaps' => $sitemaps];
    }
}

<?php

namespace App\Services\LeadsFinder;

use App\Services\Groq\GroqPool;
use Throwable;

/**
 * Turns a free-text niche description (e.g. "gyms") into a set of search
 * queries for finding cold-outreach leads, using Groq. Always returns a
 * usable list of queries -- if every Groq key/model in the pool fails, it
 * falls back to a few generic templated queries instead of erroring out.
 */
class SearchQueryGeneratorService
{
    public function __construct(private readonly GroqPool $pool) {}

    private const PROMPT_TEMPLATE = <<<'PROMPT'
You generate search-engine queries to find cold-email leads in a given niche. Good queries
surface businesses' OWN websites (not directories, review sites, or aggregators like
Yelp/Google Maps/Facebook/LinkedIn) that show a public contact email or contact page.

Niche: %s
What we'd offer them: %s

IMPORTANT -- avoid a specific trap: don't build queries around the name of a well-known
software/SaaS product used in this niche (e.g. "Powered by Mindbody", "Powered by Shopify").
That software company's OWN marketing pages, blog posts, and "best tools" listicles rank
higher for that exact phrase than any small business using it, so the query mostly returns
the tool vendor, not real businesses. Prefer queries built from the niche's own vocabulary
(services offered, generic footer/contact phrasing, industry terms) instead of a product name.

Good general style for any niche:
- '"{niche}" "contact us" -site:yelp.com -site:facebook.com'
- 'inurl:contact "{niche}" email'
- '"{niche}" "get in touch"'

Generate 5-6 search queries for the niche above, using the niche's own words and generic
contact-page phrasing -- not a specific product/brand name. Return ONLY valid JSON: an object
with one key "queries" holding an array of strings. No markdown, no extra text, no code fences.
PROMPT;

    /**
     * @return string[]
     */
    public function generate(string $niche, string $pitch = ''): array
    {
        $prompt = sprintf(self::PROMPT_TEMPLATE, $niche, $pitch ?: 'our services');

        try {
            $raw = $this->pool->chatJson($prompt, temperature: 0.7, maxTokens: 500);
            $decoded = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);

            $queries = array_values(array_filter(
                array_map(
                    fn ($q) => is_string($q) ? trim($q) : null,
                    $decoded['queries'] ?? []
                ),
                fn ($q) => filled($q)
            ));

            return $queries !== [] ? $queries : $this->fallbackQueries($niche);
        } catch (Throwable) {
            return $this->fallbackQueries($niche);
        }
    }

    /**
     * @return string[]
     */
    private function fallbackQueries(string $niche): array
    {
        return [
            "\"{$niche}\" contact us",
            "\"{$niche}\" \"get in touch\"",
            "{$niche} official website contact email",
        ];
    }
}

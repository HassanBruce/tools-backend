<?php

namespace App\Services\Groq;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Rotates Groq chat-completion calls across multiple API keys and models so
 * one key/model's free-tier rate limit doesn't block requests -- it just
 * moves to the next combination.
 *
 * Unlike a long-running process, a PHP request doesn't keep state in memory
 * between requests, so the round-robin position and cooldowns (for a combo
 * that just got rate-limited) are stored in Laravel's cache instead.
 */
class GroqPool
{
    private const ROTATION_CACHE_KEY = 'groq:rotation:index';

    /**
     * @return array<int, array{0: string, 1: string}> every (key, model) combo
     */
    private function combos(): array
    {
        $keys = config('groq.keys');
        $models = config('groq.models');

        if ($keys === []) {
            throw new RuntimeException(
                'No Groq API key configured. Set GROQ_API_KEY_1 (and optionally _2, _3, _4) in .env'
            );
        }

        $combos = [];
        foreach ($keys as $key) {
            foreach ($models as $model) {
                $combos[] = [$key, $model];
            }
        }

        return $combos;
    }

    /**
     * Combo list reordered to start at the next round-robin position, so
     * repeated calls spread load across all combos instead of always trying
     * the same one first.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function rotationOrder(): array
    {
        $combos = $this->combos();
        $count = count($combos);

        $start = Cache::increment(self::ROTATION_CACHE_KEY) % $count;
        // increment() can return a negative-mod result if the counter wraps
        // oddly across cache drivers; normalize to a valid index.
        $start = (($start % $count) + $count) % $count;

        return array_map(
            fn (int $i) => $combos[($start + $i) % $count],
            range(0, $count - 1)
        );
    }

    private function cooldownKey(array $combo): string
    {
        return 'groq:cooldown:'.md5($combo[0]).':'.$combo[1];
    }

    private function isCoolingDown(array $combo): bool
    {
        return Cache::has($this->cooldownKey($combo));
    }

    private function markCooldown(array $combo): void
    {
        Cache::put($this->cooldownKey($combo), true, now()->addSeconds(config('groq.cooldown_seconds')));
    }

    /**
     * Try every (key, model) combo (skipping ones on cooldown) until one
     * succeeds. Returns the raw response text (a JSON string, since every
     * call requests response_format=json_object). Throws RuntimeException
     * if every combo fails.
     */
    public function chatJson(string $prompt, float $temperature = 0.7, int $maxTokens = 500): string
    {
        $order = $this->rotationOrder();
        $attempted = 0;
        $lastError = null;

        foreach ($order as $combo) {
            if ($this->isCoolingDown($combo)) {
                continue;
            }

            $attempted++;

            try {
                return $this->attempt($combo, $prompt, $temperature, $maxTokens);
            } catch (Throwable $e) {
                $lastError = $e;
                if ($this->isRateLimitedOrServerError($e)) {
                    $this->markCooldown($combo);
                }
            }
        }

        if ($attempted === 0) {
            // Every combo was on cooldown -- try once more ignoring cooldown
            // rather than failing outright, since the block is temporary.
            foreach ($order as $combo) {
                try {
                    return $this->attempt($combo, $prompt, $temperature, $maxTokens);
                } catch (Throwable $e) {
                    $lastError = $e;
                }
            }
        }

        $message = $lastError?->getMessage() ?? 'unknown error';

        throw new RuntimeException("All Groq key/model combinations failed: {$message}");
    }

    /**
     * @param  array{0: string, 1: string}  $combo
     */
    private function attempt(array $combo, string $prompt, float $temperature, int $maxTokens): string
    {
        [$key, $model] = $combo;

        $response = Http::withToken($key)
            ->timeout(config('groq.timeout'))
            ->post(config('groq.base_url').'/chat/completions', [
                'model' => $model,
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->failed()) {
            throw new GroqRequestException(
                "Groq request failed for model \"{$model}\": HTTP {$response->status()}",
                $response->status()
            );
        }

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || $content === '') {
            throw new RuntimeException("Groq returned an empty response for model \"{$model}\".");
        }

        return trim($content);
    }

    private function isRateLimitedOrServerError(Throwable $e): bool
    {
        return $e instanceof GroqRequestException && ($e->statusCode === 429 || $e->statusCode >= 500);
    }
}

<?php

namespace App\Services\News;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleTaggingService
{
    private const MAX_TAGS = 3;

    private const MAX_TAG_LENGTH = 50;

    /**
     * Ask an LLM to suggest a few short theme tags for an article. Runs
     * inside an unattended scheduled job, so failures degrade to an empty
     * result (logged) rather than throwing — one bad call must never
     * abort the rest of a fetch run.
     *
     * @return array<int, string>
     */
    public function suggestTags(string $title, ?string $summary): array
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            return [];
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('services.openai.model', 'gpt-4o-mini'),
                    'temperature' => 0.2,
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $this->userPrompt($title, $summary)],
                    ],
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException($response->json('error.message') ?? ('HTTP '.$response->status()));
            }

            $content = $response->json('choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return [];
            }

            return $this->parseTags($content);
        } catch (\Throwable $e) {
            Log::warning('Failed to suggest article tags', ['title' => $title, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseTags(string $content): array
    {
        $decoded = json_decode(trim($content), true);

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn ($tag) => is_string($tag))
            ->map(fn (string $tag) => trim($tag))
            ->filter(fn (string $tag) => $tag !== '' && mb_strlen($tag) <= self::MAX_TAG_LENGTH)
            ->unique()
            ->take(self::MAX_TAGS)
            ->values()
            ->all();
    }

    private function systemPrompt(): string
    {
        return <<<'TEXT'
            You tag news articles with short topical theme tags for a
            personal reading app. Given an article's title and summary,
            respond with 2-3 short theme tags that capture what the
            article is really about (e.g. "Artificial Intelligence",
            "Ukraine War", "Cryptocurrency", "Supreme Court").

            Rules:
            - 1-3 words per tag, Title Case.
            - Prefer broad, reusable themes over one-off specifics (a tag
              should plausibly apply to other articles too).
            - Respond with ONLY a JSON array of 2-3 strings, nothing else
              — no markdown, no commentary, no explanation.
            TEXT;
    }

    private function userPrompt(string $title, ?string $summary): string
    {
        $summary = $summary ?? '';

        return "Title: {$title}\n\nSummary: {$summary}";
    }
}

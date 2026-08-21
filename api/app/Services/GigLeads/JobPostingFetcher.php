<?php

namespace App\Services\GigLeads;

use App\Support\HostSafety;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class JobPostingFetcher
{
    /**
     * Fetch a lead's URL and pull out the job title, company, and
     * description from the schema.org JobPosting structured data most job
     * boards embed for SEO. Fields fall back to '' (fetched, not found)
     * rather than null (not fetched yet) when the page has no JobPosting
     * block, except description which also falls back to the page's meta
     * description.
     *
     * @return array{title: string, company: string, description: string}
     */
    public function fetch(string $url): array
    {
        if (! HostSafety::isSafeFeedUrl($url)) {
            throw new RuntimeException('That URL is no longer safe to fetch.');
        }

        $response = Http::timeout(10)
            ->withUserAgent('myeverything-dashboard/1.0')
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Couldn't fetch that page (HTTP {$response->status()}).");
        }

        $html = $response->body();
        $jobPosting = $this->fromJsonLd($html);

        return [
            'title' => $jobPosting['title'] ?? '',
            'company' => $jobPosting['company'] ?? '',
            'description' => $jobPosting['description'] ?? $this->fromMetaDescription($html) ?? '',
        ];
    }

    /**
     * @return array{title?: string, company?: string, description?: string}|null
     */
    private function fromJsonLd(string $html): ?array
    {
        if (! preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches)) {
            return null;
        }

        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);

            if (! is_array($data)) {
                continue;
            }

            foreach (($data['@graph'] ?? [$data]) as $candidate) {
                if (! is_array($candidate)) {
                    continue;
                }

                $types = (array) ($candidate['@type'] ?? []);

                if (! in_array('JobPosting', $types, true)) {
                    continue;
                }

                $organization = $candidate['hiringOrganization'] ?? null;
                $companyName = is_array($organization) ? ($organization['name'] ?? null) : null;

                return [
                    'title' => isset($candidate['title'])
                        ? mb_substr($this->clean((string) $candidate['title']), 0, 255)
                        : '',
                    'company' => $companyName !== null
                        ? mb_substr($this->clean((string) $companyName), 0, 255)
                        : '',
                    'description' => ! empty($candidate['description'])
                        ? $this->clean((string) $candidate['description'])
                        : '',
                ];
            }
        }

        return null;
    }

    private function fromMetaDescription(string $html): ?string
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);

        foreach (['//meta[@property="og:description"]', '//meta[@name="description"]'] as $query) {
            $node = $xpath->query($query)?->item(0);
            $content = $node?->getAttribute('content');

            if ($content) {
                return $this->clean($content);
            }
        }

        return null;
    }

    private function clean(string $text): string
    {
        $text = preg_replace('#<(br|/p|/div|/li|/h[1-6])\s*/?\s*>#i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        return mb_strlen($text) > 5000 ? mb_substr($text, 0, 4997).'...' : $text;
    }
}

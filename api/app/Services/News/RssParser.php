<?php

namespace App\Services\News;

use Illuminate\Support\Carbon;
use SimpleXMLElement;

class RssParser
{
    /**
     * Parse an RSS 2.0 or Atom feed body into a normalized list of items.
     *
     * @return array<int, array{guid: string, title: string, url: string, summary: ?string, image_url: ?string, published_at: ?Carbon}>
     */
    public function parse(string $body): array
    {
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            return [];
        }

        if (isset($xml->channel)) {
            return $this->parseRss($xml);
        }

        if ($xml->getName() === 'feed') {
            return $this->parseAtom($xml);
        }

        return [];
    }

    /**
     * @return array<int, array{guid: string, title: string, url: string, summary: ?string, image_url: ?string, published_at: ?Carbon}>
     */
    private function parseRss(SimpleXMLElement $xml): array
    {
        $items = [];
        $media = $xml->channel->children('http://search.yahoo.com/mrss/');

        foreach ($xml->channel->item as $item) {
            $guid = trim((string) ($item->guid ?: $item->link));
            $link = trim((string) $item->link);
            $title = trim((string) $item->title);

            if ($guid === '' || $link === '' || $title === '') {
                continue;
            }

            $itemMedia = $item->children('http://search.yahoo.com/mrss/');
            $imageUrl = null;
            if (isset($itemMedia->thumbnail)) {
                $imageUrl = (string) $itemMedia->thumbnail->attributes()->url;
            } elseif (isset($item->enclosure) && str_starts_with((string) $item->enclosure->attributes()->type, 'image/')) {
                $imageUrl = (string) $item->enclosure->attributes()->url;
            }

            $pubDate = trim((string) $item->pubDate);

            $items[] = [
                'guid' => $guid,
                'title' => $title,
                'url' => $link,
                'summary' => $this->cleanSummary((string) $item->description) ?: null,
                'image_url' => $imageUrl ?: null,
                'published_at' => $pubDate !== '' ? $this->parseDate($pubDate) : null,
            ];
        }

        unset($media);

        return $items;
    }

    /**
     * @return array<int, array{guid: string, title: string, url: string, summary: ?string, image_url: ?string, published_at: ?Carbon}>
     */
    private function parseAtom(SimpleXMLElement $xml): array
    {
        $items = [];

        foreach ($xml->entry as $entry) {
            $guid = trim((string) $entry->id);
            $title = trim((string) $entry->title);

            $link = '';
            foreach ($entry->link as $linkEl) {
                $attrs = $linkEl->attributes();
                if ((string) $attrs->rel === '' || (string) $attrs->rel === 'alternate') {
                    $link = (string) $attrs->href;
                    break;
                }
            }

            if ($guid === '' || $link === '' || $title === '') {
                continue;
            }

            $published = trim((string) ($entry->published ?: $entry->updated));

            $items[] = [
                'guid' => $guid,
                'title' => $title,
                'url' => $link,
                'summary' => $this->cleanSummary((string) $entry->summary) ?: null,
                'image_url' => null,
                'published_at' => $published !== '' ? $this->parseDate($published) : null,
            ];
        }

        return $items;
    }

    private function cleanSummary(string $html): string
    {
        $text = trim(strip_tags($html));

        return mb_strlen($text) > 500 ? mb_substr($text, 0, 497).'...' : $text;
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}

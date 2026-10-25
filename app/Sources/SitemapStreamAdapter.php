<?php

namespace App\Sources;

use App\Data\Item;
use App\Data\Source;
use App\Support\Dates;
use App\Support\Url;
use Illuminate\Support\Str;

/**
 * Lê o começo de um sitemap (possivelmente cortado no meio) e devolve as
 * URLs de notícia. Usa regex em vez de um parser XML porque o arquivo
 * chega truncado de propósito.
 */
final class SitemapStreamAdapter implements SourceAdapter
{
    public function parse(string $body, Source $source): array
    {
        if (! str_contains($body, '<urlset') && ! str_contains($body, '<url>')) {
            throw new ParseException('Resposta não parece um sitemap.');
        }

        preg_match_all('~<url>\s*<loc>\s*([^<\s]+)\s*</loc>(?:(?:(?!</url>).)*?<lastmod>\s*([^<\s]+)\s*</lastmod>)?~s', $body, $matches, PREG_SET_ORDER);

        $items = [];

        foreach ($matches as $match) {
            $url = Url::clean(html_entity_decode($match[1]));

            if ($source->linkPattern !== null && ! preg_match($source->linkPattern, $url)) {
                continue;
            }

            $item = new Item(
                source: $source->key,
                url: $url,
                publishedAt: Dates::parse($match[2] ?? null, $source->naiveTimezone),
            );
            $item->fallbackTitle = $this->titleFromSlug($url);
            $items[] = $item;

            if (count($items) >= ($source->limit ?? 30)) {
                break;
            }
        }

        return $items;
    }

    private function titleFromSlug(string $url): string
    {
        $slug = basename((string) parse_url($url, PHP_URL_PATH));

        return Str::ucfirst(str_replace('-', ' ', $slug));
    }
}

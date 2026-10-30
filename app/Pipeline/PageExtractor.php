<?php

namespace App\Pipeline;

use App\Support\Dates;
use App\Support\ParsedDate;
use App\Support\Text;
use App\Support\Url;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

/**
 * Lê título, resumo, imagem e data da página de uma notícia. Usa os
 * seletores CSS configurados na fonte e, na falta deles, as meta tags
 * og:* / article:* / JSON-LD.
 */
final class PageExtractor
{
    private const MIN_SUMMARY = 60;

    /**
     * @param  array<string, string>  $selectors
     */
    public function extract(string $html, string $url, array $selectors = [], bool $naiveTimezone = false): PageMeta
    {
        $crawler = new Crawler(Text::toUtf8($html), $url);

        return new PageMeta(
            title: $this->title($crawler, $selectors['title'] ?? null),
            summary: $this->summary($crawler, $selectors['summary'] ?? null),
            image: $this->image($crawler, $url),
            published: $this->published($crawler, $selectors['date'] ?? null, $naiveTimezone),
        );
    }

    private function title(Crawler $crawler, ?string $selector): string
    {
        $candidates = [
            $selector ? $this->text($crawler, $selector) : '',
            $this->meta($crawler, 'og:title'),
            $this->text($crawler, 'h1'),
            $this->text($crawler, 'title'),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '') {
                return $this->untruncate($crawler, $candidate);
            }
        }

        return '';
    }

    /**
     * Alguns sites (ex.: Prefeitura) cortam o og:title com "...". Se a página
     * tiver um título que começa igual e está completo, usa esse.
     */
    private function untruncate(Crawler $crawler, string $title): string
    {
        if (! preg_match('/^(.*?)\s*(\.\.\.|…)$/u', $title, $m) || mb_strlen($m[1]) < 10) {
            return $title;
        }

        foreach ($this->nodes($crawler, 'h1, h2, h3') as $text) {
            if (mb_strlen($text) > mb_strlen($m[1]) && str_starts_with($text, $m[1]) && ! preg_match('/(\.\.\.|…)$/u', $text)) {
                return $text;
            }
        }

        return $title;
    }

    private function summary(Crawler $crawler, ?string $selector): string
    {
        if ($selector) {
            foreach ($this->nodes($crawler, $selector) as $text) {
                if (mb_strlen($text) >= self::MIN_SUMMARY) {
                    return $text;
                }
            }
        }

        foreach (['og:description', 'description', 'twitter:description'] as $name) {
            $text = $this->meta($crawler, $name);

            if (mb_strlen($text) >= 20) {
                return $text;
            }
        }

        return '';
    }

    private function image(Crawler $crawler, string $url): ?string
    {
        foreach (['og:image', 'og:image:secure_url', 'twitter:image'] as $name) {
            $src = $this->meta($crawler, $name);

            if ($src !== '') {
                return Url::absolute($src, $url);
            }
        }

        return null;
    }

    private function published(Crawler $crawler, ?string $selector, bool $naive): ?ParsedDate
    {
        foreach (['article:published_time', 'og:published_time', 'datePublished', 'pubdate'] as $name) {
            $date = Dates::parse($this->meta($crawler, $name), $naive);

            if ($date) {
                return new ParsedDate($date, true);
            }
        }

        $date = Dates::parse($this->jsonLdDate($crawler), $naive);

        if ($date) {
            return new ParsedDate($date, true);
        }

        if ($selector) {
            foreach ($this->nodes($crawler, $selector) as $text) {
                if ($parsed = Dates::fromText($text)) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    private function meta(Crawler $crawler, string $name): string
    {
        try {
            $node = $crawler->filter("meta[property=\"{$name}\"], meta[name=\"{$name}\"], meta[itemprop=\"{$name}\"]")->first();

            return $node->count() ? Text::clean($node->attr('content')) : '';
        } catch (Throwable) {
            return '';
        }
    }

    private function text(Crawler $crawler, string $selector): string
    {
        return $this->nodes($crawler, $selector)[0] ?? '';
    }

    /**
     * @return list<string>
     */
    private function nodes(Crawler $crawler, string $selector): array
    {
        try {
            $texts = $crawler->filter($selector)->each(fn (Crawler $node) => Text::clean($node->html()));
        } catch (Throwable) {
            return [];
        }

        return array_values(array_filter($texts, fn (string $text) => $text !== ''));
    }

    private function jsonLdDate(Crawler $crawler): ?string
    {
        try {
            foreach ($crawler->filter('script[type="application/ld+json"]') as $script) {
                if (preg_match('/"datePublished"\s*:\s*"([^"]+)"/', $script->textContent, $m)) {
                    return $m[1];
                }
            }
        } catch (Throwable) {
            // JSON-LD é opcional.
        }

        return null;
    }
}

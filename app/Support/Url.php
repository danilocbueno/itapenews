<?php

namespace App\Support;

use Symfony\Component\DomCrawler\UriResolver;

final class Url
{
    private const TRACKING = '/^(utm_|fbclid$|gclid$|mc_|_ga$|igshid$)/i';

    /**
     * Remove parâmetros de rastreamento e o fragmento, mantendo um link que funciona.
     */
    public static function clean(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['host'])) {
            return $url;
        }

        $query = [];

        if (isset($parts['query'])) {
            foreach (explode('&', $parts['query']) as $pair) {
                $name = explode('=', $pair, 2)[0];

                if ($name !== '' && ! preg_match(self::TRACKING, $name)) {
                    $query[] = $pair;
                }
            }
        }

        return ($parts['scheme'] ?? 'https').'://'.strtolower($parts['host'])
            .(isset($parts['port']) ? ':'.$parts['port'] : '')
            .($parts['path'] ?? '/')
            .($query ? '?'.implode('&', $query) : '');
    }

    /**
     * Forma canônica usada só para identificar a notícia (ignora http/https,
     * www e barra final).
     */
    public static function canonical(string $url): string
    {
        $clean = self::clean($url);
        $clean = preg_replace('~^https?://(www\.)?~i', '', $clean) ?? $clean;
        $clean = preg_replace('~/+(\?|$)~', '$1', $clean) ?? $clean;

        return strtolower($clean);
    }

    public static function id(string $url): string
    {
        return substr(sha1(self::canonical($url)), 0, 16);
    }

    public static function absolute(string $href, string $base): string
    {
        return UriResolver::resolve(trim($href), $base);
    }
}

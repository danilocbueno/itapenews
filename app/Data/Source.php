<?php

namespace App\Data;

use InvalidArgumentException;

final readonly class Source
{
    public const TYPES = ['rss', 'html_list', 'sitemap_stream'];

    /**
     * @param  list<string>  $ignore
     * @param  array<string, string>  $page
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $short,
        public string $type,
        public string $url,
        public string $homepage,
        public string $color = '#64748b',
        public bool $enabled = true,
        public bool $filter = false,
        public array $ignore = [],
        public bool $official = false,
        public ?string $linkPattern = null,
        public ?int $limit = null,
        public ?int $maxBytes = null,
        public bool $naiveTimezone = false,
        public array $page = [],
        public int $captionLines = 0,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("Fonte [{$key}]: tipo desconhecido [{$type}].");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            name: $config['name'],
            short: $config['short'] ?? $config['name'],
            type: $config['type'],
            url: $config['url'],
            homepage: $config['homepage'] ?? $config['url'],
            color: $config['color'] ?? '#64748b',
            enabled: $config['enabled'] ?? true,
            filter: $config['filter'] ?? false,
            ignore: $config['ignore'] ?? [],
            official: $config['official'] ?? false,
            linkPattern: $config['link_pattern'] ?? null,
            limit: $config['limit'] ?? null,
            maxBytes: $config['max_bytes'] ?? null,
            naiveTimezone: $config['naive_timezone'] ?? false,
            page: $config['page'] ?? [],
            captionLines: $config['caption_lines'] ?? 0,
        );
    }

    /**
     * Todas as fontes configuradas, indexadas pela chave.
     *
     * @return array<string, self>
     */
    public static function all(): array
    {
        $sources = [];

        foreach (config('sources', []) as $key => $config) {
            $sources[$key] = self::fromConfig($key, $config);
        }

        return $sources;
    }

    /**
     * @return array<string, self>
     */
    public static function enabled(): array
    {
        return array_filter(self::all(), fn (self $source) => $source->enabled);
    }
}

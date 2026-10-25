<?php

namespace App\Data;

use App\Support\Url;
use Carbon\CarbonImmutable;

final class Item
{
    /**
     * Texto completo usado só durante a coleta (filtro); não é persistido.
     */
    public string $content = '';

    /**
     * Título provisório (ex.: texto do link) usado se a página não trouxer um.
     */
    public string $fallbackTitle = '';

    public function __construct(
        public string $source,
        public string $url,
        public string $title = '',
        public string $summary = '',
        public ?string $image = null,
        public ?CarbonImmutable $publishedAt = null,
        public ?CarbonImmutable $collectedAt = null,
        public ?string $cluster = null,
        public bool $hidden = false,
    ) {}

    public function id(): string
    {
        return Url::id($this->url);
    }

    public function needsEnrichment(): bool
    {
        return $this->title === '' || $this->summary === '' || $this->image === null || $this->publishedAt === null;
    }

    /**
     * Data usada para ordenar e agrupar.
     */
    public function date(): CarbonImmutable
    {
        return $this->publishedAt ?? $this->collectedAt ?? CarbonImmutable::now();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'source' => $this->source,
            'url' => $this->url,
            'title' => $this->title,
            'summary' => $this->summary,
            'image' => $this->image,
            'published_at' => $this->publishedAt?->toIso8601String(),
            'collected_at' => $this->collectedAt?->toIso8601String(),
            'cluster' => $this->cluster,
            'hidden' => $this->hidden,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $tz = config('app.timezone');

        return new self(
            source: $data['source'],
            url: $data['url'],
            title: $data['title'] ?? '',
            summary: $data['summary'] ?? '',
            image: $data['image'] ?? null,
            publishedAt: isset($data['published_at']) ? CarbonImmutable::parse($data['published_at'])->setTimezone($tz) : null,
            collectedAt: isset($data['collected_at']) ? CarbonImmutable::parse($data['collected_at'])->setTimezone($tz) : null,
            cluster: $data['cluster'] ?? null,
            hidden: $data['hidden'] ?? false,
        );
    }
}

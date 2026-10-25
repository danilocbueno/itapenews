<?php

namespace App\Pipeline;

use App\Data\Item;
use App\Data\Source;
use App\Http\Fetcher;
use Carbon\CarbonImmutable;

/**
 * Abre a página de notícias novas para preencher o que a fonte não trouxe.
 * Cada notícia é aberta uma única vez: depois de salva, nunca mais.
 */
final class PageEnricher
{
    public function __construct(
        private Fetcher $fetcher,
        private PageExtractor $extractor,
    ) {}

    /**
     * @param  list<Item>  $items
     * @param  array<string, Source>  $sources
     * @return int quantidade de páginas baixadas
     */
    public function enrich(array $items, array $sources, CarbonImmutable $now): int
    {
        $pending = [];

        foreach ($items as $index => $item) {
            if ($item->needsEnrichment()) {
                $pending[$index] = ['url' => $item->url];
            }
        }

        if ($pending === []) {
            return 0;
        }

        $results = $this->fetcher->many($pending);

        foreach ($results as $index => $result) {
            if (! $result->ok()) {
                continue;
            }

            $item = $items[$index];
            $source = $sources[$item->source];
            $meta = $this->extractor->extract($result->body, $item->url, $source->page, $source->naiveTimezone);

            if ($item->title === '') {
                $item->title = $meta->title;
            }

            if ($item->summary === '') {
                $item->summary = $meta->summary;
            }

            $item->image ??= $meta->image;

            if ($meta->published !== null && ($item->publishedAt === null || $source->type !== 'rss')) {
                $date = $meta->published->date;

                // Só o dia: hoje vira "agora"; dias anteriores, meio-dia.
                if (! $meta->published->hasTime) {
                    $date = $date->isSameDay($now) ? $now : $date->setTime(12, 0);
                }

                $item->publishedAt = $date;
            }
        }

        return count($pending);
    }
}

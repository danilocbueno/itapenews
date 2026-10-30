<?php

namespace App\Pipeline;

use App\Data\Item;
use App\Data\Source;
use App\Http\Fetcher;
use App\Storage\ItemRepository;
use Carbon\CarbonImmutable;

/**
 * Reabre a página de notícias recentes salvas com título cortado ("...")
 * para buscar o título completo. Poucas por execução.
 */
final class TitleRepairer
{
    private const TRUNCATED = '/(\.\.\.|…)$/u';

    public function __construct(
        private Fetcher $fetcher,
        private PageExtractor $extractor,
    ) {}

    /**
     * @param  array<string, Source>  $sources
     * @return int títulos corrigidos
     */
    public function repair(ItemRepository $items, array $sources, CarbonImmutable $now, int $limit, int $maxAgeDays): int
    {
        $from = $now->subDays($maxAgeDays);
        $pending = array_slice(array_values(array_filter(
            $items->since($from),
            fn (Item $item) => ! $item->hidden && isset($sources[$item->source]) && preg_match(self::TRUNCATED, $item->title),
        )), 0, $limit);

        if ($pending === []) {
            return 0;
        }

        $results = $this->fetcher->many(array_map(fn (Item $item) => ['url' => $item->url], $pending));
        $repaired = 0;

        foreach ($results as $index => $result) {
            if (! $result->ok()) {
                continue;
            }

            $item = $pending[$index];
            $source = $sources[$item->source];
            $title = $this->extractor->extract($result->body, $item->url, $source->page, $source->naiveTimezone)->title;

            // Só aceita a versão completa do mesmo título, nunca outro título qualquer.
            $prefix = rtrim(preg_replace(self::TRUNCATED, '', $item->title) ?? '');

            if (str_starts_with($title, $prefix) && mb_strlen($title) > mb_strlen($item->title) && ! preg_match(self::TRUNCATED, $title)) {
                $item->title = $title;
                $items->add($item);
                $repaired++;
            }
        }

        return $repaired;
    }
}

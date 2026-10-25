<?php

namespace App\Pipeline;

use App\Data\Item;
use App\Data\Source;
use App\Http\Fetcher;
use App\Http\FetchResult;
use App\Sources\Adapters;
use App\Storage\ItemRepository;
use App\Storage\StateRepository;
use Carbon\CarbonImmutable;
use Throwable;

final class Collector
{
    public function __construct(
        private Fetcher $fetcher,
        private PageEnricher $enricher,
        private Normalizer $normalizer,
        private LocalFilter $filter,
        private Clusterer $clusterer,
    ) {}

    /**
     * @param  array<string, Source>  $sources
     * @return array<string, SourceReport>
     */
    public function collect(array $sources, ItemRepository $items, StateRepository $state, CarbonImmutable $now): array
    {
        $reports = [];
        $results = $this->fetch($sources, $state);
        $minDate = $now->subDays((int) config('itape.pipeline.max_age_days'));

        /** @var list<Item> $candidates */
        $candidates = [];
        $firstRun = [];

        foreach ($sources as $key => $source) {
            $report = $reports[$key] = new SourceReport($key);
            $result = $results[$key];

            if ($result->notModified()) {
                $report->status = 'sem mudanças';
                $state->update($key, ['last_success' => $now->toIso8601String(), 'errors' => 0, 'last_error' => null]);

                continue;
            }

            if (! $result->ok()) {
                $this->fail($state, $report, $result->error ?? "HTTP {$result->status}", $now);

                continue;
            }

            try {
                $parsed = Adapters::for($source)->parse($result->body, $source);
            } catch (Throwable $e) {
                $this->fail($state, $report, 'Formato: '.$e->getMessage(), $now);

                continue;
            }

            $report->parsed = count($parsed);
            $firstRun[$key] = $state->isFirstRun($key);

            foreach ($parsed as $item) {
                if ($items->has($item->id()) || ($item->publishedAt !== null && $item->publishedAt->lessThan($minDate))) {
                    continue;
                }

                $this->normalizer->normalize($item);

                if (! $this->filter->undecided($item, $source) && ! $this->filter->accepts($item, $source)) {
                    $report->filtered++;

                    continue;
                }

                $candidates[] = $item;
            }

            $state->update($key, [
                'last_success' => $now->toIso8601String(),
                'last_count' => $report->parsed,
                'etag' => $result->etag,
                'last_modified' => $result->lastModified,
                'errors' => 0,
                'last_error' => null,
            ]);
        }

        // Limita as páginas abertas por execução; o restante entra na próxima.
        [$candidates, $deferred] = $this->limitEnrichment($candidates, (int) config('itape.pipeline.enrich_limit'));
        $this->enricher->enrich($candidates, $sources, $now);

        // Sem o ETag, a próxima execução baixa o feed inteiro e encontra os adiados.
        foreach (array_keys($deferred) as $key) {
            $state->update($key, ['etag' => null, 'last_modified' => null]);
        }

        $recent = $items->since($now->subHours((int) config('itape.pipeline.cluster_window_hours') * 2));

        foreach ($candidates as $item) {
            $source = $sources[$item->source];
            $this->normalizer->normalize($item);

            if ($item->publishedAt !== null && $item->publishedAt->lessThan($minDate)) {
                continue;
            }

            // Rejeitado depois de abrir a página: fica guardado como oculto
            // para não ser baixado de novo a cada execução.
            $rejected = ! $this->filter->accepts($item, $source);

            // Na primeira coleta, itens sem data podem ser antigos: ficam
            // registrados como conhecidos, mas não aparecem no site.
            $undated = $firstRun[$item->source] && $item->publishedAt === null;

            $this->normalizer->finalize($item, $now, (int) config('itape.pipeline.summary_length'));

            if ($item->title === '') {
                continue;
            }

            $item->hidden = $rejected || $undated;
            $this->clusterer->assign($item, $recent, (int) config('itape.pipeline.cluster_window_hours'));
            $items->add($item);
            $recent[] = $item;

            if ($rejected) {
                $reports[$item->source]->filtered++;
            } elseif (! $item->hidden) {
                $reports[$item->source]->new++;
            }
        }

        foreach ($reports as $key => $report) {
            if ($report->new > 0) {
                $state->update($key, ['last_new_at' => $now->toIso8601String()]);
            }
        }

        return $reports;
    }

    /**
     * @param  array<string, Source>  $sources
     * @return array<string, FetchResult>
     */
    private function fetch(array $sources, StateRepository $state): array
    {
        $requests = [];
        $results = [];

        foreach ($sources as $key => $source) {
            $previous = $state->source($key);
            $headers = array_filter([
                'If-None-Match' => $previous['etag'] ?? null,
                'If-Modified-Since' => $previous['last_modified'] ?? null,
            ]);

            if ($source->maxBytes !== null) {
                $results[$key] = $this->fetcher->head($source->url, $source->maxBytes, $headers);
            } else {
                $requests[$key] = ['url' => $source->url, 'headers' => $headers];
            }
        }

        return [...$this->fetcher->many($requests), ...$results];
    }

    private function fail(StateRepository $state, SourceReport $report, string $error, CarbonImmutable $now): void
    {
        $report->status = 'erro';
        $report->error = $error;
        $previous = $state->source($report->source);

        $state->update($report->source, [
            'errors' => ($previous['errors'] ?? 0) + 1,
            'last_error' => mb_substr($error, 0, 300),
            'last_error_at' => $now->toIso8601String(),
        ]);
    }

    /**
     * @param  list<Item>  $items
     * @return array{0: list<Item>, 1: array<string, true>} itens mantidos e fontes com itens adiados
     */
    private function limitEnrichment(array $items, int $limit): array
    {
        $kept = [];
        $deferred = [];
        $opened = 0;

        foreach ($items as $item) {
            if ($item->needsEnrichment()) {
                if ($opened >= $limit) {
                    $deferred[$item->source] = true;

                    continue;
                }

                $opened++;
            }

            $kept[] = $item;
        }

        return [$kept, $deferred];
    }
}

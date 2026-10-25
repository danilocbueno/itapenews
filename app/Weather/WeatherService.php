<?php

namespace App\Weather;

use App\Http\Fetcher;
use App\Storage\JsonFile;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Atualiza data/weather.json e data/alerts.json respeitando um tempo mínimo
 * entre consultas. Em caso de falha, mantém os dados anteriores.
 */
final class WeatherService
{
    public function __construct(
        private Fetcher $fetcher,
        private OpenMeteo $openMeteo,
        private InmetAlerts $inmet,
    ) {}

    public function refreshWeather(string $path, CarbonImmutable $now, bool $force = false): string
    {
        if (! $force && $this->fresh($path, $now, (int) config('itape.weather.ttl_minutes'))) {
            return 'em cache';
        }

        $query = OpenMeteo::query((float) config('itape.weather.latitude'), (float) config('itape.weather.longitude'));
        $result = $this->fetcher->get(config('itape.weather.url').'?'.http_build_query($query));

        if (! $result->ok()) {
            return 'erro: '.($result->error ?? "HTTP {$result->status}");
        }

        try {
            JsonFile::write($path, ['fetched_at' => $now->toIso8601String(), ...$this->openMeteo->parse($result->body)]);
        } catch (Throwable $e) {
            return 'erro: '.$e->getMessage();
        }

        return 'atualizado';
    }

    public function refreshAlerts(string $path, CarbonImmutable $now, bool $force = false): string
    {
        $cached = JsonFile::read($path);

        if (! $force && $cached !== null && $this->fresh($path, $now, (int) config('itape.alerts.ttl_minutes'))) {
            // Mesmo em cache, descarta avisos que já venceram.
            $active = array_values(array_filter($cached['alerts'] ?? [], fn (array $a) => CarbonImmutable::parse($a['end'])->greaterThan($now)));

            if (count($active) !== count($cached['alerts'] ?? [])) {
                JsonFile::write($path, [...$cached, 'alerts' => $active]);
            }

            return 'em cache';
        }

        $result = $this->fetcher->get((string) config('itape.alerts.url'));

        if (! $result->ok()) {
            return 'erro: '.($result->error ?? "HTTP {$result->status}");
        }

        try {
            $alerts = $this->inmet->parse($result->body, (string) config('itape.alerts.area'), $now);
            JsonFile::write($path, ['fetched_at' => $now->toIso8601String(), 'alerts' => $alerts]);
        } catch (Throwable $e) {
            return 'erro: '.$e->getMessage();
        }

        return count($alerts).' aviso(s)';
    }

    private function fresh(string $path, CarbonImmutable $now, int $ttlMinutes): bool
    {
        $data = JsonFile::read($path);

        return isset($data['fetched_at'])
            && CarbonImmutable::parse($data['fetched_at'])->addMinutes($ttlMinutes)->greaterThan($now);
    }
}

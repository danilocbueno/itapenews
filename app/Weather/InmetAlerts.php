<?php

namespace App\Weather;

use App\Sources\ParseException;
use App\Support\Text;
use Carbon\CarbonImmutable;
use SimpleXMLElement;

/**
 * Avisos meteorológicos do INMET (RSS nacional) filtrados por mesorregião.
 */
final class InmetAlerts
{
    public const LEVELS = [
        'perigo potencial' => 1,
        'perigo' => 2,
        'grande perigo' => 3,
    ];

    /**
     * Avisos vigentes (ou que começam nas próximas 24 h) para a área,
     * um por tipo de evento, mantendo o mais severo.
     *
     * @return list<array{event: string, severity: string, level: int, start: string, end: string, description: string, link: string}>
     */
    public function parse(string $xml, string $area, CarbonImmutable $now): array
    {
        $previous = libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if (! $feed instanceof SimpleXMLElement || ! isset($feed->channel)) {
            throw new ParseException('RSS do INMET inválido.');
        }

        $tz = config('app.timezone');
        $byEvent = [];

        foreach ($feed->channel->item as $entry) {
            $fields = $this->fields((string) $entry->description);
            $areas = array_map(
                fn (string $name) => Text::fold(trim($name)),
                explode(',', preg_replace('/^.*?:/', '', $fields['area'] ?? '') ?? ''),
            );

            if (! in_array(Text::fold($area), $areas, true)) {
                continue;
            }

            $level = self::LEVELS[Text::fold($fields['severidade'] ?? '')] ?? 0;

            if ($level === 0 || ! isset($fields['inicio'], $fields['fim'])) {
                continue;
            }

            // O INMET publica horários de Brasília.
            $start = CarbonImmutable::parse($fields['inicio'], $tz);
            $end = CarbonImmutable::parse($fields['fim'], $tz);

            if ($end->lessThan($now) || $start->greaterThan($now->addDay())) {
                continue;
            }

            $event = $fields['evento'] ?? 'Aviso';
            $alert = [
                'event' => $event,
                'severity' => $fields['severidade'],
                'level' => $level,
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'description' => $fields['descricao'] ?? '',
                'link' => $fields['link grafico'] ?? (string) $entry->link,
            ];

            $current = $byEvent[$event] ?? null;

            if ($current === null || $level > $current['level'] || ($level === $current['level'] && $alert['end'] > $current['end'])) {
                $byEvent[$event] = $alert;
            }
        }

        $alerts = array_values($byEvent);
        usort($alerts, fn (array $a, array $b) => $b['level'] <=> $a['level']);

        return $alerts;
    }

    /**
     * A descrição é uma tabela HTML com pares <th>campo</th><td>valor</td>.
     *
     * @return array<string, string>
     */
    private function fields(string $html): array
    {
        preg_match_all('~<th[^>]*>(.*?)</th>\s*<td[^>]*>(.*?)</td>~is', $html, $rows, PREG_SET_ORDER);
        $fields = [];

        foreach ($rows as $row) {
            $fields[Text::fold(Text::clean($row[1]))] = Text::clean($row[2]);
        }

        return $fields;
    }
}

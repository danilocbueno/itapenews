<?php

namespace App\Site;

use App\Support\Text;
use Carbon\CarbonImmutable;

/**
 * Agenda: identifica notícias que anunciam um evento futuro e lê a data e
 * o horário do texto ("no dia 30 de outubro, às 19h30", "neste sábado (31)").
 */
final class Events
{
    /** Temas cujas notícias com data futura costumam ser eventos. */
    private const THEMES = ['cultura', 'esportes'];

    private const KEYWORDS = '/\b(show|festival|desfile|(?<!-)feira|corrida|torneio|campeonato|espetaculo|exposicao|apresentacao|festa|quermesse|palestra|oficina)\b/';

    private const MONTHS = [
        'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'abril' => 4, 'maio' => 5, 'junho' => 6,
        'julho' => 7, 'agosto' => 8, 'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
    ];

    private const WEEKDAY = '(?:segunda|terca|quarta|quinta|sexta|sabado|domingo)(?:-feira)?';

    private const MONTHLESS_MAX_DAYS = 21;

    /**
     * Eventos entre agora e os próximos $days dias, em ordem de data.
     *
     * @param  list<Cluster>  $clusters
     * @return list<Event>
     */
    public static function upcoming(array $clusters, CarbonImmutable $now, int $days = 14): array
    {
        $events = [];
        $until = $now->startOfDay()->addDays($days + 1);

        foreach ($clusters as $cluster) {
            $event = self::extract($cluster);

            // Sem horário, vale até o fim do dia; com horário, até a hora marcada.
            $end = $event?->hasTime ? $event->at : $event?->at->endOfDay();

            if ($event !== null && $end >= $now && $event->at < $until) {
                $events[] = $event;
            }
        }

        usort($events, fn (Event $a, Event $b) => $a->at <=> $b->at);

        return $events;
    }

    public static function extract(Cluster $cluster): ?Event
    {
        $item = $cluster->primary;

        if (! self::looksLikeEvent($item->title, Themes::for($item))) {
            return null;
        }

        $published = $item->date();
        $text = Text::fold($item->summary.' '.$item->title);

        foreach (self::candidates($text, $published) as [$date, $offset]) {
            [$at, $hasTime] = self::withTime($date, $text, $offset);

            // Só datas posteriores à publicação: o evento ainda vai acontecer.
            if ($hasTime ? $at > $published : $at->startOfDay() > $published->startOfDay()) {
                return new Event($cluster, $at, $hasTime);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $themes
     */
    private static function looksLikeEvent(string $title, array $themes): bool
    {
        return array_intersect($themes, self::THEMES) !== [] || preg_match(self::KEYWORDS, Text::fold($title));
    }

    /**
     * Datas encontradas no texto, na ordem em que aparecem.
     *
     * @return list<array{0: CarbonImmutable, 1: int}>
     */
    private static function candidates(string $text, CarbonImmutable $published): array
    {
        $found = [];
        $months = implode('|', array_keys(self::MONTHS));

        // "dia 5 de novembro", "30 de outubro"
        preg_match_all('/\b(\d{1,2})o? de ('.$months.')\b/', $text, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($m as $match) {
            $found[$match[0][1]] = self::date($published, (int) $match[1][0], self::MONTHS[$match[2][0]]);
        }

        // "neste sábado (31)", "na próxima quinta-feira (5)", "domingo (1º)"
        preg_match_all('/\b'.self::WEEKDAY.'\s*\((\d{1,2})o?\)/', $text, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($m as $match) {
            $found[$match[0][1]] = self::date($published, (int) $match[1][0]);
        }

        // "dia 30" (sem mês): só se não fizer parte de "dia 30 de outubro"
        preg_match_all('/\bdia (\d{1,2})o?\b(?! de)/', $text, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        foreach ($m as $match) {
            $found[$match[0][1]] = self::date($published, (int) $match[1][0]);
        }

        ksort($found);
        $candidates = [];

        foreach ($found as $offset => $date) {
            if ($date !== null) {
                $candidates[] = [$date, $offset];
            }
        }

        return $candidates;
    }

    /**
     * Monta a data a partir do dia (e do mês, se houver). Sem mês, usa o da
     * publicação, ou o seguinte quando o dia já passou.
     */
    private static function date(CarbonImmutable $published, int $day, ?int $month = null): ?CarbonImmutable
    {
        $year = $published->year;
        $monthless = $month === null;

        if ($month === null) {
            $month = $published->month;

            if ($day < $published->day) {
                $month++;
            }
        } elseif ($month < $published->month) {
            $year++;
        }

        if ($month > 12) {
            [$month, $year] = [1, $year + 1];
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        $date = CarbonImmutable::create($year, $month, $day, 0, 0, 0, $published->getTimezone());

        // Sem mês, "dia 22" numa notícia do dia 23 quase sempre fala do passado:
        // só aceita a virada para o mês seguinte se a data ficar próxima.
        if ($monthless && $date->greaterThan($published->addDays(self::MONTHLESS_MAX_DAYS))) {
            return null;
        }

        return $date;
    }

    /**
     * Procura um horário logo depois da data ("às 19h30", "a partir das 8h").
     *
     * @return array{0: CarbonImmutable, 1: bool}
     */
    private static function withTime(CarbonImmutable $date, string $text, int $offset): array
    {
        $after = substr($text, $offset, 80);

        if (preg_match('/\b(?:as|a partir das|das)\s+(\d{1,2})h(\d{2})?\b/', $after, $m) && (int) $m[1] < 24) {
            return [$date->setTime((int) $m[1], (int) ($m[2] ?? 0)), true];
        }

        return [$date, false];
    }
}

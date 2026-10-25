<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Throwable;

final class Dates
{
    private const MONTHS = [
        'janeiro' => 1, 'fevereiro' => 2, 'marco' => 3, 'abril' => 4, 'maio' => 5, 'junho' => 6,
        'julho' => 7, 'agosto' => 8, 'setembro' => 9, 'outubro' => 10, 'novembro' => 11, 'dezembro' => 12,
    ];

    /**
     * Interpreta datas ISO 8601 e RFC 2822. Datas sem fuso são tratadas como
     * horário de Brasília; com $naive = true, o fuso informado é ignorado
     * (sites que marcam horário local com "Z").
     */
    public static function parse(?string $value, bool $naive = false): ?CarbonImmutable
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $tz = config('app.timezone');

        if ($naive) {
            $value = preg_replace('/(Z|[+-]\d{2}:?\d{2})$/', '', $value) ?? $value;
        }

        try {
            return CarbonImmutable::parse($value, $tz)->setTimezone($tz);
        } catch (Throwable) {
            return self::fromText($value)?->date;
        }
    }

    /**
     * Procura uma data dentro de um texto livre: "23/10/2026 14:30",
     * "23/10/2026" ou "Sexta-feira, 23 de Outubro de 2026".
     */
    public static function fromText(string $text): ?ParsedDate
    {
        $tz = config('app.timezone');
        $folded = Text::fold($text);

        if (preg_match('~\b(\d{1,2})/(\d{1,2})/(\d{4})(?:\D{1,6}(\d{1,2})[:h](\d{2}))?~', $folded, $m)) {
            $hasTime = isset($m[4]) && $m[4] !== '';

            return self::make((int) $m[3], (int) $m[2], (int) $m[1], $hasTime ? (int) $m[4] : 0, $hasTime ? (int) $m[5] : 0, $hasTime, $tz);
        }

        if (preg_match('~\b(\d{1,2}) de ([a-z]+) de (\d{4})(?:\D{1,6}(\d{1,2})[:h](\d{2}))?~', $folded, $m) && isset(self::MONTHS[$m[2]])) {
            $hasTime = isset($m[4]) && $m[4] !== '';

            return self::make((int) $m[3], self::MONTHS[$m[2]], (int) $m[1], $hasTime ? (int) $m[4] : 0, $hasTime ? (int) $m[5] : 0, $hasTime, $tz);
        }

        return null;
    }

    private static function make(int $y, int $mo, int $d, int $h, int $mi, bool $hasTime, string $tz): ?ParsedDate
    {
        if (! checkdate($mo, $d, $y) || $h > 23 || $mi > 59) {
            return null;
        }

        return new ParsedDate(CarbonImmutable::create($y, $mo, $d, $h, $mi, 0, $tz), $hasTime);
    }
}

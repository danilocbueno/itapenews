<?php

/**
 * Horários (em Brasília) em que o workflow de coleta é agendado.
 *
 * @return array{minutes: list<int>, hours: list<int>}
 */
function collectSchedule(): array
{
    $workflow = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/collect.yml');
    preg_match_all("/cron: '(\S+) (\S+) \* \* \*'/", $workflow, $matches, PREG_SET_ORDER);

    $minutes = [];
    $hours = [];

    foreach ($matches as [, $minute, $hourSpec]) {
        $minutes[] = (int) $minute;

        foreach (explode(',', $hourSpec) as $part) {
            [$from, $to] = str_contains($part, '-') ? explode('-', $part) : [$part, $part];

            foreach (range((int) $from, (int) $to) as $utc) {
                $hours[] = ($utc - 3 + 24) % 24;
            }
        }
    }

    sort($hours);

    return ['minutes' => $minutes, 'hours' => array_values(array_unique($hours))];
}

it('coleta de hora em hora das 7h às 23h de Brasília', function () {
    expect(collectSchedule()['hours'])->toContain(...range(7, 23));
});

it('atualiza os alertas do INMET durante a madrugada', function () {
    expect(collectSchedule()['hours'])->toContain(2, 5);
});

it('pede no máximo 24 execuções por dia', function () {
    expect(count(collectSchedule()['hours']))->toBeLessThanOrEqual(24);
});

it('evita a virada da hora, quando o GitHub descarta mais execuções', function () {
    expect(collectSchedule()['minutes'])->not->toBeEmpty()->each->not->toBe(0);
});

<?php

use App\Sources\ParseException;
use App\Weather\InmetAlerts;
use App\Weather\OpenMeteo;
use App\Weather\WeatherCode;
use Carbon\CarbonImmutable;

function inmetItem(string $severity, string $start, string $end, string $areas, string $event = 'Tempestade', int $id = 1): string
{
    return <<<XML
    <item>
      <title>Aviso de {$event}. Severidade Grau: {$severity}</title>
      <link>https://apiprevmet3.inmet.gov.br/avisos/rss/{$id}</link>
      <description><![CDATA[<table><tr><th align="left">Evento</th><td>{$event}</td></tr><tr><th align="left">Severidade</th><td>{$severity}</td></tr><tr><th align="left">Início</th><td>{$start}</td></tr><tr><th align="left">Fim</th><td>{$end}</td></tr><tr><th align="left">Descrição</th><td>Chuva forte.</td></tr><tr><th align="left">Área</th><td>Aviso para as Áreas: {$areas}</td></tr><tr><th align="left">Link Gráfico</th><td><a href='https://avisos.inmet.gov.br/{$id}'>https://avisos.inmet.gov.br/{$id}</a></td></tr></table>]]></description>
    </item>
    XML;
}

function inmetFeed(string ...$items): string
{
    return '<?xml version="1.0"?><rss version="2.0"><channel><title>INMET</title>'.implode('', $items).'</channel></rss>';
}

describe('Alertas do INMET', function () {
    it('mantém só avisos vigentes para a mesorregião de Itapetininga', function () {
        $now = CarbonImmutable::parse('2026-10-24 12:00', 'America/Sao_Paulo');
        $alerts = (new InmetAlerts)->parse(inmetFeed(
            inmetItem('Perigo', '2026-10-24 11:00:00.0', '2026-10-24 23:59:00.0', 'Bauru, Itapetininga, Campinas', id: 1),
            inmetItem('Perigo', '2026-10-24 11:00:00.0', '2026-10-24 23:59:00.0', 'Bauru, Campinas', id: 2),
            inmetItem('Perigo', '2026-10-20 11:00:00.0', '2026-10-20 23:59:00.0', 'Itapetininga', id: 3),
        ), 'Itapetininga', $now);

        expect($alerts)->toHaveCount(1)
            ->and($alerts[0]['link'])->toBe('https://avisos.inmet.gov.br/1')
            ->and($alerts[0]['level'])->toBe(2)
            ->and($alerts[0]['end'])->toBe('2026-10-24T23:59:00-03:00');
    });

    it('mostra um aviso por evento, o mais severo', function () {
        $now = CarbonImmutable::parse('2026-10-24 12:00', 'America/Sao_Paulo');
        $alerts = (new InmetAlerts)->parse(inmetFeed(
            inmetItem('Perigo Potencial', '2026-10-24 10:00:00.0', '2026-10-25 10:00:00.0', 'Itapetininga', id: 1),
            inmetItem('Grande Perigo', '2026-10-24 10:00:00.0', '2026-10-24 20:00:00.0', 'Itapetininga', id: 2),
            inmetItem('Perigo Potencial', '2026-10-24 10:00:00.0', '2026-10-25 10:00:00.0', 'Itapetininga', event: 'Baixa Umidade', id: 3),
        ), 'Itapetininga', $now);

        expect($alerts)->toHaveCount(2)
            ->and($alerts[0]['severity'])->toBe('Grande Perigo')
            ->and($alerts[0]['level'])->toBe(3)
            ->and($alerts[1]['event'])->toBe('Baixa Umidade');
    });

    it('inclui avisos que começam nas próximas 24 horas', function () {
        $now = CarbonImmutable::parse('2026-10-25 17:00', 'America/Sao_Paulo');
        $alerts = (new InmetAlerts)->parse(inmetFeed(
            inmetItem('Perigo', '2026-10-26 00:00:00.0', '2026-10-26 12:00:00.0', 'Itapetininga'),
        ), 'Itapetininga', $now);

        expect($alerts)->toHaveCount(1);
    });

    it('lê o RSS real do INMET', function () {
        $alerts = (new InmetAlerts)->parse(fixtureContents('inmet.xml'), 'Itapetininga', CarbonImmutable::parse('2026-10-24 12:00', 'America/Sao_Paulo'));

        expect($alerts)->not->toBeEmpty()
            ->and(array_column($alerts, 'level'))->each->toBeGreaterThanOrEqual(1);
    });

    it('falha com XML inválido', function () {
        (new InmetAlerts)->parse('não é xml', 'Itapetininga', CarbonImmutable::now());
    })->throws(ParseException::class);
});

describe('Previsão do tempo', function () {
    it('converte a resposta do Open-Meteo', function () {
        $weather = (new OpenMeteo)->parse(fixtureContents('open-meteo.json'));

        expect($weather['daily'])->toHaveCount(3)
            ->and($weather['daily'][0])->toHaveKeys(['date', 'max', 'min', 'rain', 'rain_probability', 'code'])
            ->and($weather['current']['temperature'])->toBeFloat();
    });

    it('descreve os códigos WMO', function () {
        expect(WeatherCode::label(95))->toBe('Tempestade')
            ->and(WeatherCode::label(1234))->toBe('Sem dados');
    });
});

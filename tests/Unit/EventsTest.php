<?php

use App\Data\Item;
use App\Site\Cluster;
use App\Site\Events;
use Carbon\CarbonImmutable;

function news(string $title, string $summary, string $published, string $source = 'prefeitura'): Cluster
{
    return new Cluster(new Item($source, 'https://example.com/'.md5($title), $title, $summary, publishedAt: CarbonImmutable::parse($published, 'America/Sao_Paulo')));
}

describe('Leitura da data do evento', function () {
    it('lê "no dia 30 de outubro, às 19h30"', function () {
        $event = Events::extract(news('“Teatro Literário” apresenta peça', 'O Projeto “Teatro Literário”, vinculado à Secretaria de Cultura e Turismo, apresenta, no dia 30 de outubro, às 19h30, o espetáculo "Os 7 Novelos".', '2026-10-29 16:13'));

        expect($event->at->format('Y-m-d H:i'))->toBe('2026-10-30 19:30')
            ->and($event->hasTime)->toBeTrue();
    });

    it('lê "na próxima quinta-feira, dia 5 de novembro, a partir das 8h"', function () {
        $event = Events::extract(news('Desfile Cívico na rua Virgílio de Rezende celebra os 256 anos', 'Na próxima quinta-feira, dia 5 de novembro, a partir das 8h, a rua Virgílio de Rezende volta a ser o palco do tradicional Desfile Cívico.', '2026-10-29 20:19'));

        expect($event->at->format('Y-m-d H:i'))->toBe('2026-11-05 08:00');
    });

    it('lê "neste sábado (31), às 20h"', function () {
        $event = Events::extract(news('Festival de música agita a Praça', 'O festival acontece neste sábado (31), às 20h, na Praça Marechal Deodoro.', '2026-10-28 10:00'));

        expect($event->at->format('Y-m-d H:i'))->toBe('2026-10-31 20:00');
    });

    it('lê "dia 7" usando o mês da publicação, e vira o mês quando o dia já passou', function () {
        $sameMonth = Events::extract(news('Show na Concha Acústica', 'O show será no dia 31, às 21h.', '2026-10-28 10:00'));
        $nextMonth = Events::extract(news('Show na Concha Acústica', 'O show será no dia 7, às 21h.', '2026-10-28 10:00'));

        expect($sameMonth->at->format('Y-m-d'))->toBe('2026-10-31')
            ->and($nextMonth->at->format('Y-m-d'))->toBe('2026-11-07');
    });

    it('aceita evento sem horário', function () {
        $event = Events::extract(news('Feira de artesanato no Centro', 'A feira acontece no dia 8 de novembro na Praça.', '2026-10-28 10:00'));

        expect($event->at->format('Y-m-d'))->toBe('2026-11-08')
            ->and($event->hasTime)->toBeFalse();
    });
});

describe('O que entra na agenda', function () {
    it('ignora datas que já passaram em relação à publicação', function () {
        expect(Events::extract(news('Nota de falecimento: Geraldo Doniseti Maschietto', 'Geraldo, de 69 anos, faleceu nesta quarta-feira (28), às 11h11, em Itapetininga (SP).', '2026-10-28 15:26', 'jornal')))->toBeNull();
    });

    it('ignora serviços e vagas, mesmo com data futura', function (string $title, string $summary) {
        expect(Events::extract(news($title, $summary, '2026-10-29 16:13')))->toBeNull();
    })->with([
        ['PAT de Itapetininga tem 266 vagas de emprego na próxima terça-feira', 'O PAT de Itapetininga oferece na próxima terça-feira, dia 03 de novembro, 266 vagas de emprego.'],
        ['Poupatempo fecha na segunda-feira (2) devido ao feriado de Finados', 'Os postos do Poupatempo estarão fechados na segunda-feira, dia 2 de novembro, feriado de Finados.'],
    ]);

    it('não transforma "dia 22" de um evento passado em data do mês seguinte', function () {
        expect(Events::extract(news(
            'Tênis de Mesa de Itapetininga é vice-campeão no Jomi 2026',
            'A itapetiningana conquistou medalha de prata no Jomi, que foi realizado na última quinta, dia 22, em Votorantim.',
            '2026-10-23 12:00',
        )))->toBeNull();
    });

    it('ignora notícias de evento sem data', function () {
        expect(Events::extract(news('Teatro municipal recebe reforma', 'A reforma do teatro deve terminar no ano que vem.', '2026-10-28 10:00')))->toBeNull();
    });

    it('aceita esporte com data futura', function () {
        $event = Events::extract(news('Corrida de rua reúne atletas no domingo', 'A corrida será neste domingo (1º), a partir das 7h, com largada na Praça.', '2026-10-30 09:00', 'jornal'));

        expect($event?->at->format('Y-m-d H:i'))->toBe('2026-11-01 07:00');
    });
});

describe('Próximos eventos', function () {
    it('lista os eventos dos próximos dias, em ordem de data, sem os que já passaram', function () {
        $now = CarbonImmutable::parse('2026-10-30 12:00', 'America/Sao_Paulo');
        $events = Events::upcoming([
            news('Show na Concha Acústica', 'O show será no dia 7 de novembro, às 21h.', '2026-10-28 10:00'),
            news('“Teatro Literário” apresenta peça', 'Apresenta, no dia 30 de outubro, às 19h30, o espetáculo.', '2026-10-29 16:13'),
            news('Festival de música agita a Praça', 'O festival acontece no dia 29 de outubro, às 20h.', '2026-10-27 10:00'),
            news('Feira de artesanato no Centro', 'A feira acontece no dia 25 de novembro na Praça.', '2026-10-28 10:00'),
        ], $now, days: 14);

        expect(array_map(fn ($e) => $e->cluster->primary->title, $events))->toBe(['“Teatro Literário” apresenta peça', 'Show na Concha Acústica']);
    });
});

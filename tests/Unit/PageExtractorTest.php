<?php

use App\Pipeline\PageExtractor;

it('usa os seletores da Prefeitura para resumo e data', function () {
    $meta = (new PageExtractor)->extract(
        fixtureContents('prefeitura-article.html'),
        'https://www.itapetininga.sp.gov.br/noticia/10972/tenis/',
        source('prefeitura')->page,
    );

    expect($meta->title)->toBe('Tênis de Mesa de Itapetininga é vice-campeão no Jomi 2026')
        ->and($meta->summary)->toStartWith('A itapetiningana Lucia Bonini conquistou medalha de prata')
        ->and($meta->image)->toStartWith('https://www.itapetininga.sp.gov.br/admin/')
        ->and($meta->published->date->format('Y-m-d'))->toBe('2026-10-23')
        ->and($meta->published->hasTime)->toBeFalse();
});

it('recupera o título completo quando o og:title vem cortado com reticências', function () {
    $meta = (new PageExtractor)->extract(
        fixtureContents('prefeitura-article-long-title.html'),
        'https://www.itapetininga.sp.gov.br/noticia/10988/emef/',
        source('prefeitura')->page,
    );

    expect($meta->title)->toBe('EMEF Profª Maria Cecília Rolim Nalesso recebe autor e ilustradora do livro “O Mistério do Sumiço do Burrinho Cleitim” para interação com alunos');
});

it('mantém o título cortado quando a página não tem a versão completa', function () {
    $html = '<html><head><meta property="og:title" content="Título muito longo que foi cortado..."></head><body><h3>Notícias relacionadas</h3></body></html>';

    expect((new PageExtractor)->extract($html, 'https://example.com/x')->title)->toBe('Título muito longo que foi cortado...');
});

it('lê título e resumo da Folha, que não tem data', function () {
    $meta = (new PageExtractor)->extract(fixtureContents('folha-article.html'), 'https://www.folhadeitapetininga.com.br/noticias/default.asp?id=1889', source('folha')->page);

    expect($meta->title)->toStartWith('Concessionária Via Raposo inicia gestão')
        ->and($meta->summary)->toStartWith('Primeiro dia de atividades')
        ->and($meta->published)->toBeNull();
});

it('lê og:* e a data local do Correio', function () {
    $meta = (new PageExtractor)->extract(fixtureContents('correio-article.html'), 'https://correiodeitapetininga.com.br/noticia/80788/x', [], naiveTimezone: true);

    expect($meta->title)->toBe('Feira livre da avenida Peixoto Gomide é antecipada para sábado em Itapetininga')
        ->and($meta->image)->toStartWith('https://correiodeitapetininga.com.br/api/share-image/')
        ->and($meta->published->date->format('Y-m-d H:i'))->toBe('2026-10-23 15:24');
});

it('completa a imagem que falta no feed da TVS+', function () {
    $meta = (new PageExtractor)->extract(fixtureContents('tvsmais-article.html'), 'https://www.tvsmais.com.br/chuva/');

    expect($meta->image)->toBe('https://www.tvsmais.com.br/wp-content/uploads/2026/10/imagem-capa-Site-11-1.png')
        ->and($meta->published->date->format('Y-m-d H:i'))->toBe('2026-10-25 08:24');
});

<?php

use App\Storage\JsonFile;
use Illuminate\Support\Facades\Http;

function storedItems(): array
{
    $items = [];

    foreach (glob(config('itape.paths.data').'/items/*.json') as $file) {
        $items = [...$items, ...JsonFile::read($file)];
    }

    return $items;
}

function state(): array
{
    return JsonFile::read(config('itape.paths.data').'/state.json')['sources'];
}

it('coleta todas as fontes e grava notícias, estado, tempo e alertas', function () {
    fakeSources();

    $this->artisan('collect')->assertSuccessful();

    $items = storedItems();
    $bySource = array_count_values(array_column(array_filter($items, fn ($i) => ! $i['hidden']), 'source'));

    expect($bySource)->toHaveKeys(['g1', 'jornal', 'correio', 'prefeitura', 'tvsmais'])
        ->and(state())->toHaveKeys(['g1', 'jornal', 'correio', 'prefeitura', 'folha', 'tvsmais', 'camara', 'xeh'])
        ->and(JsonFile::read(config('itape.paths.data').'/weather.json')['daily'])->toHaveCount(3)
        ->and(JsonFile::read(config('itape.paths.data').'/alerts.json'))->toHaveKey('alerts');
});

it('aplica o filtro "Itapetininga" nas fontes regionais', function () {
    fakeSources();

    $this->artisan('collect')->assertSuccessful();

    $titles = array_column(storedItems(), 'title');

    expect($titles)->not->toContain('Prefeitura de Itararé abre pré-seleção para curso de condutor de turismo; veja como participar');
});

it('não duplica notícias nem reabre páginas já conhecidas', function () {
    fakeSources();

    $this->artisan('collect')->assertSuccessful();
    $this->artisan('collect')->assertSuccessful();
    $this->artisan('collect')->assertSuccessful();

    $items = storedItems();
    $ids = array_column($items, 'id');

    expect($ids)->toHaveCount(count(array_unique($ids)));

    // Depois de tudo coletado, uma nova execução não abre nenhuma página de notícia.
    Http::fake();
    fakeSources();
    $this->artisan('collect', ['--skip-weather' => true])->assertSuccessful();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), '/noticia/80788/'));
    expect(storedItems())->toHaveCount(count($items));
});

it('completa título, resumo, imagem e data abrindo a página da notícia', function () {
    fakeSources();

    $this->artisan('collect', ['--source' => ['correio', 'prefeitura']])->assertSuccessful();

    $correio = array_values(array_filter(storedItems(), fn ($i) => $i['source'] === 'correio'))[0];
    $prefeitura = array_values(array_filter(storedItems(), fn ($i) => $i['source'] === 'prefeitura'))[0];

    expect($correio['title'])->toBe('Feira livre da avenida Peixoto Gomide é antecipada para sábado em Itapetininga')
        ->and($correio['image'])->toStartWith('https://correiodeitapetininga.com.br/api/share-image/')
        ->and($prefeitura['summary'])->toStartWith('A itapetiningana Lucia Bonini')
        ->and($prefeitura['published_at'])->toStartWith('2026-10-23T12:00');
});

it('esconde itens sem data na primeira coleta da fonte', function () {
    fakeSources();

    $this->artisan('collect', ['--source' => ['folha']])->assertSuccessful();

    $folha = array_filter(storedItems(), fn ($i) => $i['source'] === 'folha');

    expect($folha)->not->toBeEmpty()
        ->and(array_column($folha, 'hidden'))->each->toBeTrue();
});

it('continua quando uma fonte falha e registra o erro', function () {
    fakeSources(['g1.globo.com/rss/*' => fn () => Http::response('erro', 500)]);

    $this->artisan('collect')->assertSuccessful();

    expect(state()['g1']['errors'])->toBe(1)
        ->and(state()['g1']['last_error'])->toBe('HTTP 500')
        ->and(state()['jornal']['errors'])->toBe(0)
        ->and(array_column(storedItems(), 'source'))->toContain('jornal');
});

it('registra como erro uma fonte que mudou de formato', function () {
    fakeSources(['www.jornaldeitapetininga.com.br/feed' => fn () => Http::response('<html>Em manutenção</html>')]);

    $this->artisan('collect')->assertSuccessful();

    expect(state()['jornal']['last_error'])->toStartWith('Formato:');
});

it('falha só quando todas as fontes falham', function () {
    Http::fake(['*' => fn () => Http::response('', 503)]);

    $this->artisan('collect', ['--skip-weather' => true])->assertFailed();
});

it('usa ETag para não baixar de novo um feed sem mudanças', function () {
    fakeSources(['g1.globo.com/rss/*' => fn () => Http::response(fixtureContents('g1.xml'), 200, ['ETag' => '"abc"'])]);
    $this->artisan('collect', ['--source' => ['g1']])->assertSuccessful();

    Http::fake(['g1.globo.com/rss/*' => fn () => Http::response('', 304)]);
    $this->artisan('collect', ['--source' => ['g1'], '--skip-weather' => true])->assertSuccessful();

    Http::assertSent(fn ($request) => $request->hasHeader('If-None-Match', '"abc"'));
});

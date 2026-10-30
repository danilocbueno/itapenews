<?php

use App\Data\Item;
use App\Pipeline\Clusterer;
use App\Storage\ItemRepository;
use Carbon\CarbonImmutable;

beforeEach(function () {
    fakeSources();
    $this->artisan('collect')->assertSuccessful();
    $this->artisan('collect')->assertSuccessful();
});

function built(string $path): string
{
    return file_get_contents(config('itape.paths.build').'/'.$path);
}

it('gera home, arquivo, fontes, 404 e feed', function () {
    $this->artisan('build')->assertSuccessful();

    foreach (['index.html', 'arquivo/index.html', 'arquivo/2026/10/25/index.html', 'fontes/index.html', '404.html', 'feed.xml', '.nojekyll'] as $path) {
        expect(config('itape.paths.build').'/'.$path)->toBeFile();
    }
});

it('mostra notícias, tempo, alerta e filtros na home', function () {
    $this->artisan('build')->assertSuccessful();
    $html = built('index.html');

    expect($html)
        ->toContain('Chuva passa de 110 mm em Itapetininga e deixa desabrigados')
        ->toContain('class="weather"')
        ->toContain('class="alerts"')
        ->toContain('data-source="g1"')
        ->toContain('<h2 data-day="2026-10-25">Hoje</h2>')
        ->toContain('referrerpolicy="no-referrer"')
        ->toContain('https://wa.me/?text=');
});

it('junta a mesma notícia de fontes diferentes em um card', function () {
    $repository = new ItemRepository(config('itape.paths.data').'/items');
    $first = new Item('jornal', 'https://jornal.test/rio', 'Rio Itapetininga recua e Prefeitura inicia limpeza de áreas alagadas', publishedAt: CarbonImmutable::parse('2026-10-25 09:00'));
    $second = new Item('correio', 'https://correio.test/rio', 'Rio Itapetininga recua, mas três áreas seguem alagadas', publishedAt: CarbonImmutable::parse('2026-10-25 11:00'));
    (new Clusterer)->assign($first, [], 48);
    (new Clusterer)->assign($second, [$first], 48);
    $repository->add($first);
    $repository->add($second);
    $repository->save();

    $this->artisan('build')->assertSuccessful();
    $html = built('index.html');

    expect($html)->toContain('Também em:')
        ->toContain('href="https://correio.test/rio"')
        ->and(substr_count($html, 'Rio Itapetininga recua'))->toBe(1);
});

it('gera um feed RSS válido', function () {
    $this->artisan('build')->assertSuccessful();
    $feed = simplexml_load_string(built('feed.xml'));

    expect($feed)->not->toBeFalse()
        ->and(count($feed->channel->item))->toBeGreaterThan(10)
        ->and((string) $feed->channel->item[0]->link)->toStartWith('http');
});

it('mostra a situação das fontes', function () {
    $this->artisan('build')->assertSuccessful();

    expect(built('fontes/index.html'))
        ->toContain('Correio de Itapetininga')
        ->toContain('de hora em hora, das 7h às 23h')
        ->toContain('<span class="ok">OK</span>');
});

it('usa o caminho base do GitHub Pages nos links', function () {
    config(['itape.site.base' => '/itapenews']);

    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))->toContain('href="/itapenews/arquivo/"');
});

it('inclui o GoatCounter só quando configurado', function () {
    $this->artisan('build')->assertSuccessful();
    expect(built('index.html'))->not->toContain('goatcounter');

    config(['itape.site.goatcounter' => 'itapenews']);
    $this->artisan('build')->assertSuccessful();
    expect(built('index.html'))->toContain('https://itapenews.goatcounter.com/count');
});

it('não esconde a página quando não há dados', function () {
    config(['itape.paths.data' => $this->tmp.'/vazio']);

    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))->toContain('Nenhuma notícia coletada ainda.');
});

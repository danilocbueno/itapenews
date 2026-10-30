<?php

use App\Data\Item;
use App\Pipeline\Clusterer;
use App\Storage\ItemRepository;
use App\Storage\JsonFile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

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

it('mostra o cabeçalho de jornal com data por extenso e subtítulo', function () {
    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))
        ->toContain('class="masthead"')
        ->toContain('Itapetininga, domingo, 25 de outubro de 2026')
        ->toContain('Itape<em>News</em>')
        ->toContain('Todas as notícias da cidade, num só lugar');
});

it('usa o nome configurado no cabeçalho, sem destacar parte dele', function () {
    config(['itape.site.name' => 'Giro Itapê']);

    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))->toContain('>Giro Itapê</a>');
});

it('não mostra na previsão dias que já passaram', function () {
    JsonFile::write(config('itape.paths.data').'/weather.json', [
        'fetched_at' => '2026-10-24T20:00:00-03:00',
        'current' => ['temperature' => 18.5, 'code' => 53, 'time' => '2026-10-24T20:00'],
        'daily' => [
            ['date' => '2026-10-24', 'max' => 21, 'min' => 16, 'rain' => 9, 'rain_probability' => 94, 'code' => 53],
            ['date' => '2026-10-25', 'max' => 26, 'min' => 16, 'rain' => 4, 'rain_probability' => 83, 'code' => 3],
            ['date' => '2026-10-26', 'max' => 25, 'min' => 16, 'rain' => 4, 'rain_probability' => 88, 'code' => 61],
        ],
    ]);

    $this->artisan('build')->assertSuccessful();
    $weather = Str::between(built('index.html'), 'class="weather"', '</section>');

    expect($weather)->not->toContain('Sáb 24')
        ->toContain('Hoje')
        ->toContain('Amanhã')
        ->toContain('26° / 16°');
});

it('mostra o aviso do INMET como faixa com rótulo', function () {
    $this->artisan('build')->assertSuccessful();

    expect(Str::between(built('index.html'), 'class="alerts"', '</section>'))
        ->toContain('Aviso do INMET');
});

it('mostra vários avisos do INMET em uma faixa só', function () {
    JsonFile::write(config('itape.paths.data').'/alerts.json', ['fetched_at' => '2026-10-25T16:00:00-03:00', 'alerts' => [
        ['event' => 'Chuvas Intensas', 'severity' => 'Perigo Potencial', 'level' => 1, 'start' => '2026-10-25T00:00:00-03:00', 'end' => '2026-10-25T23:59:00-03:00', 'description' => '', 'link' => 'https://avisos.inmet.gov.br/1'],
        ['event' => 'Tempestade', 'severity' => 'Perigo Potencial', 'level' => 1, 'start' => '2026-10-25T00:00:00-03:00', 'end' => '2026-10-25T23:59:00-03:00', 'description' => '', 'link' => 'https://avisos.inmet.gov.br/2'],
    ]]);

    $this->artisan('build')->assertSuccessful();
    $html = built('index.html');

    expect(substr_count($html, 'class="alert '))->toBe(1)
        ->and($html)->toContain('Chuvas intensas e tempestade na região de Itapetininga até hoje às 23h59.');
});

it('agrupa filtros, aviso e previsão no topo da home', function () {
    $this->artisan('build')->assertSuccessful();
    $topbar = Str::between(built('index.html'), '<div class="topbar">', '<!-- /topbar -->');

    expect($topbar)
        ->toContain('class="filters"')
        ->toContain('class="alerts"')
        ->toContain('class="weather"');
});

it('monta a capa com a manchete e duas notícias ao lado', function () {
    $this->artisan('build')->assertSuccessful();
    $front = Str::between(built('index.html'), '<div class="front">', '<!-- /front -->');

    expect(substr_count($front, 'class="card lead'))->toBe(1)
        ->and(substr_count($front, 'class="card side'))->toBe(2);
});

it('não repete nenhuma notícia na home', function () {
    $this->artisan('build')->assertSuccessful();
    preg_match_all('~<h3><a href="([^"]+)"~', built('index.html'), $links);

    expect($links[1])->not->toBeEmpty()
        ->and(count($links[1]))->toBe(count(array_unique($links[1])));
});

it('destaca só a primeira notícia da home', function () {
    $this->artisan('build')->assertSuccessful();

    expect(substr_count(built('index.html'), 'class="card lead'))->toBe(1)
        ->and(built('arquivo/2026/10/25/index.html'))->not->toContain('class="card lead');
});

it('não reaproveita a classe da manchete em outros elementos', function () {
    $css = file_get_contents(resource_path('assets/site.css'));

    // A classe .lead é só da manchete; um seletor solto ".lead {" pintaria a manchete de cinza.
    expect($css)->not->toMatch('/^\.lead \{/m');
});

it('esconde o espaço da foto quando a imagem da fonte não carrega', function () {
    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))->toContain('onerror="this.closest(\'.card\').classList.add(\'noimg\'); this.parentNode.remove()"');
});

it('mostra fonte, horário e selo oficial na linha de contexto', function () {
    $this->artisan('build')->assertSuccessful();
    $html = built('index.html');

    expect($html)->toContain('<span class="kicker-source">Prefeitura</span>')
        ->toContain('<span class="official">oficial</span>');
});

it('publica as fontes no próprio site', function () {
    $this->artisan('build')->assertSuccessful();

    foreach (['newsreader.woff2', 'newsreader-italic.woff2', 'ibm-plex-sans.woff2'] as $font) {
        expect(config('itape.paths.build').'/assets/fonts/'.$font)->toBeFile();
    }

    expect(built('index.html'))
        ->toContain('@font-face { font-family: "Newsreader"; src: url("/assets/fonts/newsreader.woff2")')
        ->not->toContain('fonts.googleapis.com');
});

it('usa o caminho base do GitHub Pages também nas fontes', function () {
    config(['itape.site.base' => '/itapenews']);

    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))->toContain('url("/itapenews/assets/fonts/ibm-plex-sans.woff2")');
});

it('mostra na página de fontes o erro de uma fonte que falhou', function () {
    $state = JsonFile::read(config('itape.paths.data').'/state.json');
    $state['sources']['jornal'] = [...$state['sources']['jornal'], 'errors' => 2, 'last_error' => 'HTTP 503'];
    JsonFile::write(config('itape.paths.data').'/state.json', $state);

    $this->artisan('build')->assertSuccessful();

    expect(built('fontes/index.html'))
        ->toContain('<span class="bad">Erro (2x)</span>')
        ->toContain('HTTP 503');
});

it('lista os dias do arquivo agrupados por mês', function () {
    $this->artisan('build')->assertSuccessful();

    expect(built('arquivo/index.html'))
        ->toContain('Outubro de 2026')
        ->toContain('href="/arquivo/2026/10/25/"');
});

it('gera uma página para cada tema', function () {
    $this->artisan('build')->assertSuccessful();

    foreach (array_keys(config('themes')) as $slug) {
        expect(config('itape.paths.build')."/tema/{$slug}/index.html")->toBeFile();
    }
});

it('lista na página do tema só as notícias daquele assunto', function () {
    $repository = new ItemRepository(config('itape.paths.data').'/items');
    foreach ([
        ['https://jornal.test/acidente', 'Colisão entre caminhão e carro interdita rodovia em Itapetininga'],
        ['https://jornal.test/teatro', 'Teatro Literário apresenta peça na Biblioteca Municipal'],
    ] as [$url, $title]) {
        $item = new Item('jornal', $url, $title, publishedAt: CarbonImmutable::parse('2026-10-25 10:00'));
        $item->cluster = $item->id();
        $repository->add($item);
    }
    $repository->save();

    $this->artisan('build')->assertSuccessful();
    $page = built('tema/transito/index.html');

    expect($page)->toContain('<h1 class="page-title">Trânsito</h1>')
        ->toContain('href="https://jornal.test/acidente"')
        ->not->toContain('href="https://jornal.test/teatro"');
});

it('mostra a linha de assuntos em todas as páginas e marca o tema atual', function () {
    $this->artisan('build')->assertSuccessful();

    expect(built('index.html'))->toContain('<nav class="topics"')
        ->toContain('href="/tema/obituarios/"')
        ->and(built('fontes/index.html'))->toContain('<nav class="topics"')
        ->and(built('tema/clima/index.html'))->toContain('href="/tema/clima/" aria-current="page"');
});

it('mostra o tema no card com link para a página do tema', function () {
    $repository = new ItemRepository(config('itape.paths.data').'/items');
    foreach ([
        ['https://jornal.test/acidente', 'Colisão entre caminhão e carro interdita rodovia em Itapetininga'],
        ['https://jornal.test/cpfl', 'CPFL Santa Cruz lança campanha de bônus para clientes'],
    ] as [$url, $title]) {
        $item = new Item('jornal', $url, $title, publishedAt: CarbonImmutable::parse('2026-10-25 10:00'));
        $item->cluster = $item->id();
        $repository->add($item);
    }
    $repository->save();

    $this->artisan('build')->assertSuccessful();
    $card = fn (string $html, string $url) => Str::before(Str::afterLast(Str::before($html, 'href="'.$url.'"'), '<article'), '</article>');

    expect($card(built('index.html'), 'https://jornal.test/acidente'))->toContain('<a class="kicker-theme" href="/tema/transito/">Trânsito</a>')
        ->and($card(built('index.html'), 'https://jornal.test/cpfl'))->not->toContain('kicker-theme')
        ->and($card(built('tema/transito/index.html'), 'https://jornal.test/acidente'))->not->toContain('kicker-theme');
});

it('avisa quando um tema ainda não tem notícias', function () {
    config(['itape.paths.data' => $this->tmp.'/vazio']);

    $this->artisan('build')->assertSuccessful();

    expect(built('tema/esportes/index.html'))->toContain('Ainda não há notícias de esportes.');
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

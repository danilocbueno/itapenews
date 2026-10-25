<?php

use App\Storage\JsonFile;
use Illuminate\Support\Facades\Http;

it('passa quando todas as fontes respondem e têm notícias', function () {
    fakeSources();

    $this->artisan('check-sources', ['--live' => true])
        ->expectsOutputToContain('Todas as fontes estão funcionando.')
        ->assertSuccessful();
});

it('aponta uma fonte que mudou de layout', function () {
    fakeSources(['www.itapetininga.sp.gov.br/' => fn () => Http::response('<html><a href="/contato">Contato</a></html>')]);

    $this->artisan('check-sources', ['--live' => true, '--markdown' => true])
        ->expectsOutputToContain('### Prefeitura de Itapetininga (`prefeitura`)')
        ->expectsOutputToContain('nenhuma notícia foi encontrada')
        ->assertFailed();
});

it('aponta fontes com falhas seguidas no estado salvo', function () {
    JsonFile::write(config('itape.paths.data').'/state.json', ['sources' => [
        'jornal' => ['last_success' => '2026-10-25T16:00:00-03:00', 'errors' => 4, 'last_error' => 'HTTP 503'],
    ]]);

    $this->artisan('check-sources')
        ->expectsOutputToContain('falhou 4 vezes seguidas: HTTP 503')
        ->assertFailed();
});

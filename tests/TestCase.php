<?php

namespace Tests;

use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected string $tmp;

    protected function setUp(): void
    {
        parent::setUp();

        // Nenhum teste acessa a internet: toda requisição precisa de um fake.
        Http::preventStrayRequests();

        $this->tmp = sys_get_temp_dir().'/itape-tests-'.bin2hex(random_bytes(4));
        config([
            'itape.paths.data' => $this->tmp.'/data',
            'itape.paths.build' => $this->tmp.'/build',
        ]);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-25 17:00', 'America/Sao_Paulo'));
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->tmp);
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }
}

<?php

namespace App\Commands;

use App\Site\SiteBuilder;
use Carbon\CarbonImmutable;
use LaravelZero\Framework\Commands\Command;

class BuildCommand extends Command
{
    protected $signature = 'build';

    protected $description = 'Gera o site estático em build/ a partir de data/';

    public function handle(SiteBuilder $builder): int
    {
        $stats = $builder->build(config('itape.paths.data'), config('itape.paths.build'), CarbonImmutable::now());

        $this->info(sprintf(
            'Site gerado em %s: %d páginas, %d notícias (%d na home).',
            config('itape.paths.build'), $stats['pages'], $stats['clusters'], $stats['home'],
        ));

        return self::SUCCESS;
    }
}

<?php

namespace App\Commands;

use App\Data\Source;
use App\Http\Fetcher;
use App\Sources\Adapters;
use App\Storage\StateRepository;
use Carbon\CarbonImmutable;
use LaravelZero\Framework\Commands\Command;
use Throwable;

class CheckSourcesCommand extends Command
{
    protected $signature = 'check-sources
        {--live : Também baixa e lê cada fonte agora}
        {--markdown : Saída em Markdown (para abrir issue no GitHub)}';

    protected $description = 'Verifica se alguma fonte parou de funcionar ou mudou de formato';

    public function handle(Fetcher $fetcher): int
    {
        $now = CarbonImmutable::now();
        $state = new StateRepository(config('itape.paths.data').'/state.json');
        $problems = [];

        foreach (Source::enabled() as $key => $source) {
            $info = $state->source($key);
            $lastSuccess = isset($info['last_success']) ? CarbonImmutable::parse($info['last_success']) : null;

            if (($info['errors'] ?? 0) >= 3) {
                $problems[$key][] = "falhou {$info['errors']} vezes seguidas: ".($info['last_error'] ?? '');
            }

            if ($lastSuccess !== null && $lastSuccess->lessThan($now->subDay())) {
                $problems[$key][] = 'sem coleta bem-sucedida desde '.$lastSuccess->setTimezone($now->getTimezone())->format('d/m/Y H:i');
            }

            if ($this->option('live')) {
                $result = $source->maxBytes !== null
                    ? $fetcher->head($source->url, $source->maxBytes)
                    : $fetcher->get($source->url);

                if (! $result->ok()) {
                    $problems[$key][] = 'agora: '.($result->error ?? "HTTP {$result->status}");
                } else {
                    try {
                        $count = count(Adapters::for($source)->parse($result->body, $source));

                        if ($count === 0) {
                            $problems[$key][] = 'agora: a página respondeu, mas nenhuma notícia foi encontrada (o formato pode ter mudado)';
                        }
                    } catch (Throwable $e) {
                        $problems[$key][] = 'agora: '.$e->getMessage();
                    }
                }
            }
        }

        $this->report($problems);

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<string, list<string>>  $problems
     */
    private function report(array $problems): void
    {
        $sources = Source::all();

        if ($this->option('markdown')) {
            if ($problems === []) {
                $this->line('Todas as fontes estão funcionando.');

                return;
            }

            $this->line("## Fontes com problema\n");

            foreach ($problems as $key => $list) {
                $this->line("### {$sources[$key]->name} (`{$key}`)\n");
                $this->line("URL: {$sources[$key]->url}\n");

                foreach ($list as $problem) {
                    $this->line("- {$problem}");
                }

                $this->line('');
            }

            $this->line('Ajuste o adaptador ou a configuração em `config/sources.php` e rode `php itape check-sources --live`.');

            return;
        }

        if ($problems === []) {
            $this->info('Todas as fontes estão funcionando.');

            return;
        }

        foreach ($problems as $key => $list) {
            $this->error($sources[$key]->name);

            foreach ($list as $problem) {
                $this->line("  - {$problem}");
            }
        }
    }
}

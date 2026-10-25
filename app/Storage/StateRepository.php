<?php

namespace App\Storage;

/**
 * Estado das coletas por fonte (data/state.json): cabeçalhos de cache,
 * último sucesso, erros seguidos e contagens.
 */
final class StateRepository
{
    /** @var array<string, array<string, mixed>> */
    private array $sources;

    public function __construct(private string $path)
    {
        $this->sources = JsonFile::read($path)['sources'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function source(string $key): array
    {
        return $this->sources[$key] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * Primeira coleta bem-sucedida desta fonte?
     */
    public function isFirstRun(string $key): bool
    {
        return empty($this->sources[$key]['last_success']);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(string $key, array $values): void
    {
        $this->sources[$key] = [...$this->source($key), ...$values];
    }

    public function save(): bool
    {
        ksort($this->sources);

        return JsonFile::write($this->path, ['sources' => $this->sources]);
    }
}

<?php

namespace App\Pipeline;

final class SourceReport
{
    public function __construct(
        public string $source,
        public string $status = 'ok',
        public int $parsed = 0,
        public int $new = 0,
        public int $filtered = 0,
        public ?string $error = null,
    ) {}

    public function failed(): bool
    {
        return $this->error !== null;
    }
}

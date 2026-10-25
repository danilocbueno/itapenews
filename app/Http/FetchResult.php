<?php

namespace App\Http;

final readonly class FetchResult
{
    public function __construct(
        public int $status,
        public string $body = '',
        public ?string $etag = null,
        public ?string $lastModified = null,
        public ?string $error = null,
    ) {}

    public static function failed(string $error): self
    {
        return new self(status: 0, error: $error);
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function notModified(): bool
    {
        return $this->status === 304;
    }
}

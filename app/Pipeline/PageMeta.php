<?php

namespace App\Pipeline;

use App\Support\ParsedDate;

final readonly class PageMeta
{
    public function __construct(
        public string $title = '',
        public string $summary = '',
        public ?string $image = null,
        public ?ParsedDate $published = null,
    ) {}
}

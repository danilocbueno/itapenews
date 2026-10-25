<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final readonly class ParsedDate
{
    public function __construct(
        public CarbonImmutable $date,
        public bool $hasTime,
    ) {}
}

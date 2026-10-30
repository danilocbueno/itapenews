<?php

namespace App\Site;

use Carbon\CarbonImmutable;

final readonly class Event
{
    public function __construct(
        public Cluster $cluster,
        public CarbonImmutable $at,
        public bool $hasTime,
    ) {}
}

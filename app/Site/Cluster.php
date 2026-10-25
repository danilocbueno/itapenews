<?php

namespace App\Site;

use App\Data\Item;
use Carbon\CarbonImmutable;

/**
 * Uma notícia como aparece no site: a primeira publicação e as mesmas
 * notícias em outras fontes ("Também em").
 */
final class Cluster
{
    /**
     * @param  list<Item>  $others
     */
    public function __construct(
        public Item $primary,
        public array $others = [],
    ) {}

    public function date(): CarbonImmutable
    {
        return $this->primary->date();
    }

    public function image(): ?string
    {
        if ($this->primary->image) {
            return $this->primary->image;
        }

        foreach ($this->others as $other) {
            if ($other->image) {
                return $other->image;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function sources(): array
    {
        return array_values(array_unique([
            $this->primary->source,
            ...array_map(fn (Item $item) => $item->source, $this->others),
        ]));
    }
}

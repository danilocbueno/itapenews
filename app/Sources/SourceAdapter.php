<?php

namespace App\Sources;

use App\Data\Item;
use App\Data\Source;

interface SourceAdapter
{
    /**
     * Extrai as notícias do conteúdo baixado da fonte.
     *
     * @return list<Item>
     *
     * @throws ParseException quando o conteúdo não está no formato esperado
     */
    public function parse(string $body, Source $source): array;
}

<?php

namespace App\Pipeline;

use App\Data\Item;
use App\Data\Source;
use App\Support\Text;

/**
 * Mantém só notícias que mencionam Itapetininga, para fontes regionais.
 */
final class LocalFilter
{
    /**
     * Só o começo do texto conta: uma notícia de outra cidade que cita
     * Itapetininga de passagem no fim não entra.
     */
    public function __construct(
        private string $term = 'itapetininga',
        private int $leadChars = 600,
    ) {}

    public function accepts(Item $item, Source $source): bool
    {
        if (! $source->filter) {
            return true;
        }

        $body = $item->content !== '' ? $item->content : $item->summary;
        $text = $item->title.' '.mb_substr($body, 0, $this->leadChars);

        foreach ($source->ignore as $pattern) {
            $text = preg_replace($pattern, ' ', $text) ?? $text;
        }

        return (bool) preg_match('/\b'.preg_quote($this->term, '/').'\b/', Text::fold($text));
    }

    /**
     * Ainda não há texto suficiente para decidir (ex.: falta abrir a página).
     */
    public function undecided(Item $item, Source $source): bool
    {
        return $source->filter && $item->summary === '' && $item->content === '';
    }
}

<?php

namespace App\Pipeline;

use App\Data\Item;
use App\Support\Text;

/**
 * Agrupa a mesma notícia publicada por fontes diferentes, comparando as
 * palavras dos títulos. É conservador de propósito: juntar notícias
 * diferentes é pior do que deixar de juntar duas iguais.
 */
final class Clusterer
{
    private const STOPWORDS = [
        'a', 'ao', 'aos', 'as', 'com', 'como', 'da', 'das', 'de', 'do', 'dos', 'e', 'em', 'entre', 'na', 'nas',
        'no', 'nos', 'o', 'os', 'ou', 'para', 'pela', 'pelas', 'pelo', 'pelos', 'por', 'que', 'se', 'sem', 'sob',
        'sobre', 'um', 'uma', 'umas', 'uns', 'apos', 'ate', 'mais', 'menos', 'muito', 'seu', 'sua', 'seus', 'suas',
        'ser', 'sao', 'foi', 'tem', 'ter', 'esta', 'este', 'neste', 'nesta', 'deste', 'desta', 'isso', 'ja', 'nao',
        'veja', 'confira', 'saiba', 'video', 'fotos', 'diz', 'dizem',
        // Termos que aparecem em quase todo título local e não diferenciam notícias.
        'itapetininga', 'regiao', 'cidade', 'sp', 'interior', 'paulo',
        // Em obituários só o nome importa.
        'nota', 'falecimento', 'obituario', 'luto',
    ];

    public function __construct(
        private float $jaccard = 0.4,
        private float $overlap = 0.6,
        private int $minShared = 3,
    ) {}

    /**
     * @return list<string>
     */
    public function tokens(string $title): array
    {
        $words = preg_split('/[^a-z0-9]+/', Text::fold($title), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $tokens = [];

        foreach ($words as $word) {
            if (in_array($word, self::STOPWORDS, true) || (strlen($word) < 3 && ! ctype_digit($word))) {
                continue;
            }

            // Singular/plural simples: "vagas" == "vaga".
            if (strlen($word) > 4 && str_ends_with($word, 's')) {
                $word = substr($word, 0, -1);
            }

            $tokens[$word] = true;
        }

        return array_keys($tokens);
    }

    public function similar(string $a, string $b): bool
    {
        $ta = $this->tokens($a);
        $tb = $this->tokens($b);

        if ($ta === [] || $tb === []) {
            return false;
        }

        $shared = count(array_intersect($ta, $tb));

        if ($shared < 2) {
            return false;
        }

        $union = count(array_unique([...$ta, ...$tb]));

        return $shared / $union >= $this->jaccard
            || ($shared >= $this->minShared && $shared / min(count($ta), count($tb)) >= $this->overlap);
    }

    /**
     * Define o grupo de $item procurando uma notícia parecida de outra
     * fonte dentro da janela de tempo.
     *
     * @param  iterable<Item>  $candidates
     */
    public function assign(Item $item, iterable $candidates, int $windowHours): void
    {
        foreach ($candidates as $other) {
            if ($other->source === $item->source || $other->hidden || $other->id() === $item->id()) {
                continue;
            }

            if (abs($other->date()->diffInHours($item->date())) > $windowHours) {
                continue;
            }

            if ($this->similar($item->title, $other->title)) {
                $item->cluster = $other->cluster ?? $other->id();

                return;
            }
        }

        $item->cluster = $item->id();
    }
}

<?php

namespace App\Site;

use App\Data\Item;
use App\Support\Text;

/**
 * Classifica notícias por tema a partir das palavras-chave de config/themes.php.
 */
final class Themes
{
    /**
     * @return array<string, array{name: string, description: string, pattern: string}>
     */
    public static function all(): array
    {
        return config('themes', []);
    }

    /**
     * Temas da notícia: primeiro os encontrados no título, depois os que só
     * aparecem no resumo, cada grupo na ordem de prioridade da configuração.
     *
     * @return list<string>
     */
    public static function for(Item $item): array
    {
        $fromTitle = self::match(Text::fold($item->title));
        $fromSummary = self::match(Text::fold($item->summary));

        return array_values(array_unique([...$fromTitle, ...$fromSummary]));
    }

    /**
     * @return list<string>
     */
    private static function match(string $text): array
    {
        $themes = [];

        foreach (self::all() as $slug => $theme) {
            if ($text !== '' && preg_match($theme['pattern'], $text)) {
                $themes[] = $slug;
            }
        }

        return $themes;
    }

    /**
     * Tema principal (o de maior prioridade), ou null.
     */
    public static function primary(Item $item): ?string
    {
        return self::for($item)[0] ?? null;
    }
}

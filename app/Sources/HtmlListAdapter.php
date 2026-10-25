<?php

namespace App\Sources;

use App\Data\Item;
use App\Data\Source;
use App\Support\Text;
use App\Support\Url;
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Lista de notícias a partir dos links de uma página HTML. Título, resumo,
 * imagem e data são completados depois, abrindo cada notícia (PageEnricher).
 */
final class HtmlListAdapter implements SourceAdapter
{
    public function parse(string $body, Source $source): array
    {
        if ($source->linkPattern === null) {
            throw new ParseException("Fonte [{$source->key}] sem link_pattern.");
        }

        $crawler = new Crawler(Text::toUtf8($body), $source->url);
        $found = [];

        foreach ($crawler->filter('a[href]') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $href = (string) $node->getAttribute('href');

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            $url = Url::clean(Url::absolute($href, $source->url));

            if (! preg_match($source->linkPattern, $url)) {
                continue;
            }

            $text = Text::clean($node->textContent);
            $text = $text !== '' ? $text : Text::clean((string) $node->getAttribute('title'));

            // A mesma notícia pode aparecer com e sem barra no final.
            $id = Url::id($url);
            $found[$id] ??= ['url' => $url, 'text' => ''];

            if (mb_strlen($text) > mb_strlen($found[$id]['text'])) {
                $found[$id]['text'] = $text;
            }
        }

        $items = [];

        foreach (array_slice($found, 0, $source->limit ?? 30) as $link) {
            $item = new Item(source: $source->key, url: $link['url']);
            $item->fallbackTitle = $link['text'];
            $items[] = $item;
        }

        return $items;
    }
}

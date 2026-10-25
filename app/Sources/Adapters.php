<?php

namespace App\Sources;

use App\Data\Source;
use InvalidArgumentException;

final class Adapters
{
    public static function for(Source $source): SourceAdapter
    {
        return match ($source->type) {
            'rss' => new RssAdapter,
            'html_list' => new HtmlListAdapter,
            'sitemap_stream' => new SitemapStreamAdapter,
            default => throw new InvalidArgumentException("Tipo de fonte desconhecido [{$source->type}]."),
        };
    }
}

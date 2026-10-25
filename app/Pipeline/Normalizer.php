<?php

namespace App\Pipeline;

use App\Data\Item;
use App\Support\Text;
use App\Support\Url;
use Carbon\CarbonImmutable;

final class Normalizer
{
    public function normalize(Item $item): Item
    {
        $item->url = Url::clean($item->url);
        $item->title = Text::clean($item->title);
        $item->summary = Text::clean($item->summary);

        if ($item->image !== null) {
            $image = trim($item->image);
            $item->image = preg_match('~^https?://~i', $image) ? $image : null;
        }

        // Resumo igual ao título não acrescenta nada.
        if (Text::fold($item->summary) === Text::fold($item->title)) {
            $item->summary = '';
        }

        return $item;
    }

    /**
     * Últimos ajustes antes de salvar: título de reserva, data e tamanho do resumo.
     */
    public function finalize(Item $item, CarbonImmutable $now, int $summaryLength): Item
    {
        if ($item->title === '') {
            $item->title = $item->fallbackTitle;
        }

        // Data ausente ou no futuro (fuso errado na fonte) vira o horário da coleta.
        if ($item->publishedAt === null || $item->publishedAt->greaterThan($now->addHour())) {
            $item->publishedAt = $now;
        }

        $item->collectedAt ??= $now;
        $item->summary = Text::excerpt($item->summary, $summaryLength);

        return $item;
    }
}

<?php

namespace App\Weather;

/**
 * Ícones em SVG (traço fino, cor do texto) para a previsão do tempo.
 */
final class WeatherIcon
{
    private const PATHS = [
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'partly' => '<path d="M8 3v1.5M3.5 8H5M4.8 4.8l1 1M11.2 4.8l-1 1"/><path d="M5.6 10.8A3.5 3.5 0 1 1 11.6 7"/><path d="M8 20h9a4 4 0 0 0 0-8 5.5 5.5 0 0 0-10.4 1.6A3.2 3.2 0 0 0 8 20z"/>',
        'cloud' => '<path d="M7 19h10a4.5 4.5 0 0 0 .5-9A6 6 0 0 0 6 11.5 3.8 3.8 0 0 0 7 19z"/>',
        'fog' => '<path d="M7 14h10a4 4 0 0 0 .4-8A5.5 5.5 0 0 0 6.6 8 3.2 3.2 0 0 0 7 14z"/><path d="M4 18h16M6 21h12"/>',
        'rain' => '<path d="M7 15h10a4 4 0 0 0 .4-8A5.5 5.5 0 0 0 6.6 9 3.2 3.2 0 0 0 7 15z"/><path d="M8 18l-1 3M12 18l-1 3M16 18l-1 3"/>',
        'storm' => '<path d="M7 15h10a4 4 0 0 0 .4-8A5.5 5.5 0 0 0 6.6 9 3.2 3.2 0 0 0 7 15z"/><path d="M12.5 15l-2 3.5h3l-2 3.5"/>',
        'snow' => '<path d="M7 15h10a4 4 0 0 0 .4-8A5.5 5.5 0 0 0 6.6 9 3.2 3.2 0 0 0 7 15z"/><path d="M8 18.5h.01M12 20h.01M16 18.5h.01M10 22h.01M14 22h.01"/>',
        'temp' => '<path d="M14 14.8V5a2 2 0 0 0-4 0v9.8a4 4 0 1 0 4 0z"/><path d="M12 9v7"/>',
        'drop' => '<path d="M12 3.5s-5.5 6.2-5.5 10a5.5 5.5 0 0 0 11 0c0-3.8-5.5-10-5.5-10z"/>',
    ];

    /**
     * Tipo de ícone para um código WMO do Open-Meteo.
     */
    public static function kind(?int $code): string
    {
        return match (true) {
            $code === null => 'cloud',
            $code <= 1 => 'sun',
            $code === 2 => 'partly',
            $code === 3 => 'cloud',
            $code === 45, $code === 48 => 'fog',
            $code >= 51 && $code <= 67, $code >= 80 && $code <= 82 => 'rain',
            $code >= 71 && $code <= 77, $code === 85, $code === 86 => 'snow',
            $code >= 95 && $code <= 99 => 'storm',
            default => 'cloud',
        };
    }

    public static function svg(string $kind): string
    {
        $paths = self::PATHS[$kind] ?? self::PATHS['cloud'];

        return '<svg class="wx-icon wx-'.$kind.'" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" '
            .'stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths.'</svg>';
    }

    public static function forCode(?int $code): string
    {
        return self::svg(self::kind($code));
    }
}

<?php

namespace App\Weather;

/**
 * Códigos WMO usados pelo Open-Meteo.
 */
final class WeatherCode
{
    private const CODES = [
        0 => ['Céu limpo', '☀️'],
        1 => ['Predomínio de sol', '🌤️'],
        2 => ['Parcialmente nublado', '⛅'],
        3 => ['Nublado', '☁️'],
        45 => ['Neblina', '🌫️'],
        48 => ['Neblina com geada', '🌫️'],
        51 => ['Garoa fraca', '🌦️'],
        53 => ['Garoa', '🌦️'],
        55 => ['Garoa forte', '🌧️'],
        56 => ['Garoa congelante', '🌧️'],
        57 => ['Garoa congelante', '🌧️'],
        61 => ['Chuva fraca', '🌦️'],
        63 => ['Chuva', '🌧️'],
        65 => ['Chuva forte', '🌧️'],
        66 => ['Chuva congelante', '🌧️'],
        67 => ['Chuva congelante', '🌧️'],
        71 => ['Neve fraca', '🌨️'],
        73 => ['Neve', '🌨️'],
        75 => ['Neve forte', '🌨️'],
        77 => ['Grãos de neve', '🌨️'],
        80 => ['Pancadas de chuva', '🌦️'],
        81 => ['Pancadas de chuva', '🌧️'],
        82 => ['Pancadas fortes', '⛈️'],
        85 => ['Pancadas de neve', '🌨️'],
        86 => ['Pancadas de neve', '🌨️'],
        95 => ['Tempestade', '⛈️'],
        96 => ['Tempestade com granizo', '⛈️'],
        99 => ['Tempestade com granizo', '⛈️'],
    ];

    public static function label(?int $code): string
    {
        return self::CODES[$code][0] ?? 'Sem dados';
    }

    public static function icon(?int $code): string
    {
        return self::CODES[$code][1] ?? '🌡️';
    }
}

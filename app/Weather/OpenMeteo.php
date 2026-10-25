<?php

namespace App\Weather;

use App\Sources\ParseException;

final class OpenMeteo
{
    /**
     * @return array<string, mixed>
     */
    public static function query(float $latitude, float $longitude): array
    {
        return [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => 'temperature_2m,weather_code',
            'daily' => 'temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,weather_code',
            'timezone' => 'America/Sao_Paulo',
            'forecast_days' => 3,
        ];
    }

    /**
     * Converte a resposta da API no formato salvo em data/weather.json.
     *
     * @return array{current: array{temperature: float|null, code: int|null, time: string|null}, daily: list<array{date: string, max: float|null, min: float|null, rain: float|null, rain_probability: int|null, code: int|null}>}
     */
    public function parse(string $json): array
    {
        $data = json_decode($json, true);

        if (! is_array($data) || ! isset($data['daily']['time'])) {
            throw new ParseException('Resposta inesperada do Open-Meteo.');
        }

        $daily = [];

        foreach ($data['daily']['time'] as $i => $date) {
            $daily[] = [
                'date' => $date,
                'max' => $data['daily']['temperature_2m_max'][$i] ?? null,
                'min' => $data['daily']['temperature_2m_min'][$i] ?? null,
                'rain' => $data['daily']['precipitation_sum'][$i] ?? null,
                'rain_probability' => $data['daily']['precipitation_probability_max'][$i] ?? null,
                'code' => $data['daily']['weather_code'][$i] ?? null,
            ];
        }

        return [
            'current' => [
                'temperature' => $data['current']['temperature_2m'] ?? null,
                'code' => $data['current']['weather_code'] ?? null,
                'time' => $data['current']['time'] ?? null,
            ],
            'daily' => $daily,
        ];
    }
}

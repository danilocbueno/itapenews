@use('App\Weather\WeatherCode')
@use('App\Site\Timeline')
@php($days = array_values(array_filter($weather['daily'] ?? [], fn ($day) => $day['date'] >= $now->format('Y-m-d'))))
@if ($days)
  @php($current = $weather['current'] ?? [])
  <section class="weather" aria-label="Previsão do tempo em Itapetininga">
    <div class="weather-cell weather-now">
      <span class="temp">{{ isset($current['temperature']) ? round($current['temperature']).'°' : '–' }}</span>
      <span class="small">{{ mb_strtolower(WeatherCode::label($current['code'] ?? null)) }}</span>
    </div>
    @foreach (array_slice($days, 0, 3) as $day)
      <div class="weather-cell" title="{{ WeatherCode::label($day['code']) }}">
        <b>{{ Timeline::shortDay($day['date'], $now) }}</b>
        <span>{{ round($day['max'] ?? 0) }}° / {{ round($day['min'] ?? 0) }}°</span>
        <span class="small">{{ $day['rain_probability'] !== null ? 'chuva '.$day['rain_probability'].'%' : mb_strtolower(WeatherCode::label($day['code'])) }}</span>
      </div>
    @endforeach
  </section>
@endif

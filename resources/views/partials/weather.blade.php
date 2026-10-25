@use('App\Weather\WeatherCode')
@if (! empty($weather['daily']))
  @php($current = $weather['current'] ?? [])
  <section class="weather" aria-label="Previsão do tempo em Itapetininga">
    <div class="weather-now">
      <span class="icon" aria-hidden="true">{{ WeatherCode::icon($current['code'] ?? null) }}</span>
      <div>
        <div class="temp">{{ isset($current['temperature']) ? round($current['temperature']).'°' : '–' }}</div>
        <div class="label">{{ WeatherCode::label($current['code'] ?? null) }}<br>Itapetininga</div>
      </div>
    </div>
    <div class="weather-days">
      @foreach ($weather['daily'] as $day)
        <div class="weather-day" title="{{ WeatherCode::label($day['code']) }}">
          <b>{{ \App\Site\Timeline::shortDay($day['date'], $now) }}</b>
          <span aria-hidden="true">{{ WeatherCode::icon($day['code']) }}</span>
          {{ round($day['max'] ?? 0) }}° / {{ round($day['min'] ?? 0) }}°<br>
          {{ $day['rain_probability'] !== null ? $day['rain_probability'].'%' : '' }}
          {{ ($day['rain'] ?? 0) > 0 ? '· '.number_format($day['rain'], 0, ',', '.').' mm' : '' }}
        </div>
      @endforeach
    </div>
  </section>
@endif

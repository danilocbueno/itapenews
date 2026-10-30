@php($alert = \App\Weather\InmetAlerts::summarize($alerts))
@if ($alert)
  @php($end = \Carbon\CarbonImmutable::parse($alert['end'])->setTimezone($now->getTimezone()))
  <section class="alerts" aria-label="Aviso meteorológico do INMET">
    <a class="alert alert-{{ $alert['level'] }}" href="{{ $alert['link'] }}" target="_blank" rel="noopener">
      <strong>Aviso do INMET · {{ $alert['severity'] }}</strong>
      {{ $alert['events'] }} na região de Itapetininga até {{ $end->isSameDay($now) ? 'hoje' : $end->format('d/m') }} às {{ $end->format('H\hi') }}.
    </a>
  </section>
@endif

@if ($alerts)
  <section class="alerts" aria-label="Avisos meteorológicos do INMET">
    @foreach ($alerts as $alert)
      @php($end = \Carbon\CarbonImmutable::parse($alert['end'])->setTimezone($now->getTimezone()))
      <a class="alert alert-{{ $alert['level'] }}" href="{{ $alert['link'] }}" target="_blank" rel="noopener">
        <strong>Aviso do INMET · {{ $alert['severity'] }}</strong>
        {{ $alert['event'] }} na região de Itapetininga até {{ $end->isSameDay($now) ? 'hoje' : $end->format('d/m') }} às {{ $end->format('H\hi') }}.
      </a>
    @endforeach
  </section>
@endif

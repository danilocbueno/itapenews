@extends('layouts.app')

@section('content')
  @if (count($activeSources) > 1)
    <div class="filters" role="toolbar" aria-label="Filtrar por fonte">
      <button class="chip" type="button" data-source="all" aria-pressed="true">Todas</button>
      @foreach ($sources as $key => $source)
        @if (in_array($key, $activeSources, true))
          <button class="chip" type="button" data-source="{{ $key }}" aria-pressed="false">{{ $source->short }}</button>
        @endif
      @endforeach
    </div>
  @endif

  @include('partials.alerts')
  @include('partials.weather')

  @forelse ($days as $day => $clusters)
    <section class="day">
      <h2 data-day="{{ $day }}">{{ \App\Site\Timeline::dayLabel($day, $now) }}</h2>
      <div class="cards">
        @foreach ($clusters as $cluster)
          @include('partials.card')
        @endforeach
      </div>
    </section>
  @empty
    <p class="empty">Nenhuma notícia coletada ainda.</p>
  @endforelse

  <p class="more"><a href="{{ $site['base'] }}/arquivo/">Ver notícias anteriores</a></p>
@endsection

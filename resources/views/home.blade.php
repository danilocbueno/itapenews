@extends('layouts.app')

@section('content')
  <div class="topbar">
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
  </div><!-- /topbar -->

  @forelse ($days as $day => $clusters)
    <section class="day">
      <h2 data-day="{{ $day }}">{{ \App\Site\Timeline::dayLabel($day, $now) }}</h2>
      @if ($loop->first)
        {{-- Capa: manchete e as duas notícias seguintes ao lado (lado a lado só no desktop) --}}
        <div class="front">
          @foreach (array_slice($clusters, 0, 3) as $cluster)
            @include('partials.card', ['lead' => $loop->first, 'side' => ! $loop->first])
          @endforeach
        </div><!-- /front -->
        @php($clusters = array_slice($clusters, 3))
      @endif
      <div class="cards">
        @foreach ($clusters as $cluster)
          @include('partials.card', ['lead' => false])
        @endforeach
      </div>
    </section>
  @empty
    <p class="empty">Nenhuma notícia coletada ainda.</p>
  @endforelse

  <p class="more"><a href="{{ $site['base'] }}/arquivo/">Ver notícias anteriores</a></p>
@endsection

@extends('layouts.app', ['currentPage' => 'agenda'])

@section('title', 'Agenda de Itapetininga · '.$site['name'])
@section('description', 'Eventos, shows, festas e competições em Itapetininga nos próximos dias, a partir das notícias da cidade.')
@section('path', '/agenda/')

@section('content')
  <h1 class="page-title">Agenda</h1>
  <p class="page-lead">Eventos dos próximos {{ config('itape.agenda.days') }} dias anunciados nas notícias da cidade. As datas são identificadas automaticamente nos textos: confira na notícia antes de sair de casa.</p>

  @forelse ($eventDays as $day => $events)
    <section class="agenda-day">
      <h2>{{ \App\Site\Timeline::agendaDay($day, $now) }}</h2>
      @foreach ($events as $event)
        @php($item = $event->cluster->primary)
        @php($image = $event->cluster->image())
        <article class="agenda-item{{ $image ? '' : ' noimg' }}">
          <time class="agenda-time{{ $event->hasTime ? '' : ' no-time' }}" datetime="{{ $event->at->toIso8601String() }}">{{ $event->hasTime ? $event->at->format($event->at->minute ? 'G\hi' : 'G\h') : 'horário a confirmar' }}</time>
          <div>
            <h3><a href="{{ $item->url }}" target="_blank" rel="noopener">{{ $item->title }}</a></h3>
            <div class="kicker"><span class="kicker-source">{{ ($sources[$item->source] ?? null)?->short ?? $item->source }}</span></div>
          </div>
          @if ($image)
            <a class="agenda-thumb" href="{{ $item->url }}" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
              <img src="{{ $image }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.closest('.agenda-item').classList.add('noimg'); this.parentNode.remove()">
            </a>
          @endif
        </article>
      @endforeach
    </section>
  @empty
    <p class="empty">Nenhum evento encontrado para os próximos {{ config('itape.agenda.days') }} dias.</p>
  @endforelse
@endsection

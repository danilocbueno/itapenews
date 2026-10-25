@extends('layouts.app')

@section('title', 'Arquivo de notícias · '.$site['name'])
@section('path', '/arquivo/')

@section('content')
  <h1 class="page-title">Arquivo</h1>
  <p class="lead">Notícias de Itapetininga por dia.</p>

  <div class="months">
    @forelse ($months as $month => $monthDays)
      <h2>{{ \App\Site\Timeline::monthLabel($month) }}</h2>
      <ul>
        @foreach ($monthDays as $day => $count)
          <li><a href="{{ $site['base'] }}/arquivo/{{ str_replace('-', '/', $day) }}/">{{ substr($day, 8, 2) }}/{{ substr($day, 5, 2) }} <small>({{ $count }})</small></a></li>
        @endforeach
      </ul>
    @empty
      <p class="empty">Ainda não há notícias no arquivo.</p>
    @endforelse
  </div>
@endsection

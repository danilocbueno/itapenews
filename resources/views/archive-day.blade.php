@extends('layouts.app')

@section('title', 'Notícias de Itapetininga em '.$date->format('d/m/Y').' · '.$site['name'])
@section('description', 'Todas as notícias de Itapetininga publicadas em '.\App\Site\Timeline::longDate($date).'.')
@section('path', '/arquivo/'.str_replace('-', '/', $day).'/')

@section('content')
  <h1 class="page-title">{{ ucfirst(\App\Site\Timeline::longDate($date)) }}</h1>
  <p class="lead">{{ count($clusters) }} {{ count($clusters) === 1 ? 'notícia' : 'notícias' }}</p>

  <div class="cards">
    @foreach ($clusters as $cluster)
      @include('partials.card')
    @endforeach
  </div>

  <nav class="pager">
    <span>@if ($older)<a href="{{ $site['base'] }}/arquivo/{{ str_replace('-', '/', $older) }}/">← {{ \Carbon\CarbonImmutable::parse($older)->format('d/m') }}</a>@endif</span>
    <a href="{{ $site['base'] }}/arquivo/">Arquivo</a>
    <span>@if ($newer)<a href="{{ $site['base'] }}/arquivo/{{ str_replace('-', '/', $newer) }}/">{{ \Carbon\CarbonImmutable::parse($newer)->format('d/m') }} →</a>@endif</span>
  </nav>
@endsection

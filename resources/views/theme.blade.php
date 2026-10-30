@extends('layouts.app')

@section('title', $theme['name'].' em Itapetininga · '.$site['name'])
@section('description', $theme['description'].' em Itapetininga, de todos os veículos da cidade.')
@section('path', '/tema/'.$currentTheme.'/')

@section('content')
  <h1 class="page-title">{{ $theme['name'] }}</h1>
  <p class="page-lead">{{ $theme['description'] }}, de todos os veículos da cidade.</p>

  @if ($clusters)
    <div class="cards">
      @foreach ($clusters as $cluster)
        @include('partials.card')
      @endforeach
    </div>
  @else
    <p class="empty">Ainda não há notícias de {{ mb_strtolower($theme['name']) }}.</p>
  @endif
@endsection

@extends('layouts.app')

@section('title', 'Resumo do dia · '.$site['name'])
@section('description', 'As principais notícias de Itapetininga nas últimas 24 horas, prontas para compartilhar.')
@section('path', '/hoje/')

@section('content')
  <h1 class="page-title">Resumo do dia</h1>
  <p class="page-lead">As principais notícias das últimas 24 horas, primeiro as publicadas por mais veículos. O texto abaixo está pronto para colar no WhatsApp.</p>

  <div class="digest">
    <label class="small" for="digest-text">Texto para compartilhar</label>
    <textarea id="digest-text" readonly rows="{{ min(16, substr_count($text, "\n") + 2) }}">{{ $text }}</textarea>
    <div class="digest-actions">
      <button type="button" class="button" data-copy="digest-text">Copiar texto</button>
      <a class="button button-secondary" href="https://wa.me/?text={{ rawurlencode($text) }}" target="_blank" rel="noopener">Enviar pelo WhatsApp</a>
      <span class="small" data-copy-status role="status"></span>
    </div>
  </div>

  @if ($clusters)
    <div class="cards">
      @foreach ($clusters as $cluster)
        @include('partials.card')
      @endforeach
    </div>
  @else
    <p class="empty">Nenhuma notícia nas últimas 24 horas.</p>
  @endif
@endsection

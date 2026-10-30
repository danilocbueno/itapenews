@extends('layouts.app')

@section('title', 'Página não encontrada · '.$site['name'])

@section('content')
  <h1 class="page-title">Página não encontrada</h1>
  <p class="page-lead">O endereço pode ter mudado. <a href="{{ $site['base'] }}/">Voltar para as últimas notícias</a>.</p>
@endsection

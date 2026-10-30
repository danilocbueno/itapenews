@extends('layouts.app')

@section('title', 'Fontes · '.$site['name'])
@section('path', '/fontes/')

@section('content')
  <h1 class="page-title">Fontes</h1>
  <p class="page-lead">
    O {{ $site['name'] }} consulta estes veículos de hora em hora, das 7h às 23h, e mostra só o título, um trecho e o link para a notícia original.
    @if ($site['contact'])
      Se você é responsável por algum deles e quer ajustar ou remover a sua fonte, escreva para <a href="mailto:{{ $site['contact'] }}">{{ $site['contact'] }}</a>.
    @endif
  </p>

  @php($types = ['rss' => 'RSS', 'html_list' => 'Página', 'sitemap_stream' => 'Sitemap'])
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>Fonte</th><th>Coleta</th><th>Situação</th><th>Notícias</th></tr>
      </thead>
      <tbody>
        @foreach ($sources as $key => $source)
          @continue(! $source->enabled)
          @php($info = $state[$key] ?? [])
          <tr>
            <td>
              <a href="{{ $source->homepage }}" target="_blank" rel="noopener"><b>{{ $source->name }}</b></a>
              <div class="small">{{ $types[$source->type] }}{{ $source->filter ? ' · filtrada por "Itapetininga"' : '' }}</div>
            </td>
            <td>
              @if (! empty($info['last_success']))
                <time datetime="{{ $info['last_success'] }}" data-rel>{{ \Carbon\CarbonImmutable::parse($info['last_success'])->setTimezone($now->getTimezone())->format('d/m H:i') }}</time>
              @else
                <span class="small">nunca</span>
              @endif
              @if (! empty($info['last_new_at']))
                <div class="small">última novidade: <time datetime="{{ $info['last_new_at'] }}" data-rel>{{ \Carbon\CarbonImmutable::parse($info['last_new_at'])->setTimezone($now->getTimezone())->format('d/m H:i') }}</time></div>
              @endif
            </td>
            <td>
              @if (($info['errors'] ?? 0) > 0)
                <span class="bad">Erro ({{ $info['errors'] }}x)</span>
                <div class="small">{{ \Illuminate\Support\Str::limit($info['last_error'] ?? '', 120) }}</div>
              @elseif (! empty($info['last_success']))
                <span class="ok">OK</span>
              @else
                <span class="small">aguardando</span>
              @endif
            </td>
            <td>{{ $counts[$key] ?? 0 }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endsection

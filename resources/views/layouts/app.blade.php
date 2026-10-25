@php($base = $site['base'])
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', $site['name'].' · '.$site['tagline'])</title>
  <meta name="description" content="@yield('description', $site['tagline'])">
  <meta name="theme-color" content="#0f6b5c">
  <link rel="canonical" href="{{ $site['url'] }}@yield('path', '/')">
  <link rel="alternate" type="application/rss+xml" title="{{ $site['name'] }}" href="{{ $base }}/feed.xml">
  <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230f6b5c'/%3E%3Ctext x='50' y='70' font-size='60' text-anchor='middle' fill='white' font-family='Arial' font-weight='700'%3EI%3C/text%3E%3C/svg%3E">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:site_name" content="{{ $site['name'] }}">
  <meta property="og:title" content="@yield('title', $site['name'])">
  <meta property="og:description" content="@yield('description', $site['tagline'])">
  <meta property="og:url" content="{{ $site['url'] }}@yield('path', '/')">
  <style>{!! $css !!}</style>
  @if ($site['goatcounter'])
    <script data-goatcounter="https://{{ $site['goatcounter'] }}.goatcounter.com/count" async src="https://gc.zgo.at/count.js"></script>
  @endif
</head>
<body>
  <header class="top">
    <div class="top-inner">
      <a class="brand" href="{{ $base }}/">{{ $site['name'] }}</a>
      <span class="updated">Atualizado <time datetime="{{ $now->toIso8601String() }}" data-rel>às {{ $now->format('H:i') }}</time></span>
    </div>
  </header>

  <main class="wrap">
    @yield('content')
  </main>

  <footer class="foot">
    <div class="foot-inner">
      <nav>
        <a href="{{ $base }}/">Início</a>
        <a href="{{ $base }}/arquivo/">Arquivo</a>
        <a href="{{ $base }}/fontes/">Fontes</a>
        <a href="{{ $base }}/feed.xml">RSS</a>
      </nav>
      @if ($problems)
        <p class="warn">Algumas fontes estão com problema na coleta: {{ implode(', ', $problems) }}. <a href="{{ $base }}/fontes/">Ver detalhes</a>.</p>
      @endif
      <p>{{ $site['name'] }} reúne títulos e resumos publicados pelos veículos de Itapetininga. Cada notícia leva ao site original, que é o dono do conteúdo.</p>
    </div>
  </footer>

  <script>{!! $js !!}</script>
</body>
</html>

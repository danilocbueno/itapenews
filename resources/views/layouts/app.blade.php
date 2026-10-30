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
  <link rel="preload" href="{{ $base }}/assets/fonts/newsreader.woff2" as="font" type="font/woff2" crossorigin>
  <style>
    @font-face { font-family: "Newsreader"; src: url("{{ $base }}/assets/fonts/newsreader.woff2") format("woff2"); font-weight: 200 800; font-style: normal; font-display: swap; }
    @font-face { font-family: "Newsreader"; src: url("{{ $base }}/assets/fonts/newsreader-italic.woff2") format("woff2"); font-weight: 200 800; font-style: italic; font-display: swap; }
    @font-face { font-family: "IBM Plex Sans"; src: url("{{ $base }}/assets/fonts/ibm-plex-sans.woff2") format("woff2"); font-weight: 100 700; font-style: normal; font-display: swap; }
  </style>
  <style>{!! $css !!}</style>
  @if ($site['goatcounter'])
    <script data-goatcounter="https://{{ $site['goatcounter'] }}.goatcounter.com/count" async src="https://gc.zgo.at/count.js"></script>
  @endif
</head>
<body>
  @php($brand = preg_match('/^(.+?)(News)$/u', $site['name'], $parts) ? e($parts[1]).'<em>'.e($parts[2]).'</em>' : e($site['name']))
  <header class="masthead">
    <div class="masthead-inner">
      <div class="masthead-top">
        <span>Itapetininga, {{ \App\Site\Timeline::longDate($now) }}</span>
        <span>Atualizado <time datetime="{{ $now->toIso8601String() }}" data-rel>às {{ $now->format('H:i') }}</time></span>
      </div>
      <div class="masthead-title">
        <a class="brand" href="{{ $base }}/">{!! $brand !!}</a>
        <p class="tagline">{{ $site['tagline'] }}</p>
      </div>
      <nav class="topics" aria-label="Assuntos">
        @foreach ($themes as $slug => $theme)
          <a href="{{ $base }}/tema/{{ $slug }}/"@if (($currentTheme ?? null) === $slug) aria-current="page"@endif>{{ $theme['name'] }}</a>
        @endforeach
        <a class="topics-agenda" href="{{ $base }}/agenda/"@if (($currentPage ?? null) === 'agenda') aria-current="page"@endif>Agenda</a>
      </nav>
    </div>
  </header>

  <main class="wrap">
    @yield('content')
  </main>

  <footer class="foot">
    <div class="foot-inner">
      <nav>
        <a href="{{ $base }}/">Início</a>
        <a href="{{ $base }}/hoje/">Resumo do dia</a>
        <a href="{{ $base }}/agenda/">Agenda</a>
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

@php
    $item = $cluster->primary;
    $source = $sources[$item->source] ?? null;
    $image = $cluster->image();
    $share = 'https://wa.me/?text='.rawurlencode($item->title.' '.$item->url);
@endphp
<article class="card{{ $image ? '' : ' noimg' }}" data-sources="{{ implode(' ', $cluster->sources()) }}" style="--c: {{ $source?->color ?? '#64748b' }}">
  <div>
    <div class="meta">
      <span class="badge">{{ $source?->short ?? $item->source }}</span>
      @if ($source?->official)
        <span class="official">Oficial</span>
      @endif
      <span aria-hidden="true">·</span>
      <time datetime="{{ $item->date()->toIso8601String() }}" data-rel>{{ $item->date()->format('d/m H:i') }}</time>
    </div>
    <h3><a href="{{ $item->url }}" target="_blank" rel="noopener">{{ $item->title }}</a></h3>
    @if ($item->summary !== '')
      <p>{{ $item->summary }}</p>
    @endif
    @if ($cluster->others)
      <div class="also">Também em:
        @foreach ($cluster->others as $other)
          <a href="{{ $other->url }}" target="_blank" rel="noopener">{{ ($sources[$other->source] ?? null)?->short ?? $other->source }}</a>@if (! $loop->last), @endif
        @endforeach
      </div>
    @endif
    <div class="actions">
      <a href="{{ $share }}" target="_blank" rel="noopener">Compartilhar no WhatsApp</a>
    </div>
  </div>
  @if ($image)
    <a class="thumb" href="{{ $item->url }}" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
      <img src="{{ $image }}" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">
    </a>
  @endif
</article>

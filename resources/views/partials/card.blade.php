@php
    $item = $cluster->primary;
    $source = $sources[$item->source] ?? null;
    $image = $cluster->image();
    $share = 'https://wa.me/?text='.rawurlencode($item->title.' '.$item->url);
    $lead = $lead ?? false;
    $side = $side ?? false;
@endphp
<article class="card{{ $lead ? ' lead' : '' }}{{ $side ? ' side' : '' }}{{ $image ? '' : ' noimg' }}" data-sources="{{ implode(' ', $cluster->sources()) }}">
  @if ($image)
    <a class="thumb" href="{{ $item->url }}" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true">
      <img src="{{ $image }}" alt="" loading="{{ $lead ? 'eager' : 'lazy' }}" decoding="async" referrerpolicy="no-referrer" onerror="this.closest('.card').classList.add('noimg'); this.parentNode.remove()">
    </a>
  @endif
  <div class="card-body">
    <div class="kicker">
      <span class="kicker-source">{{ $source?->short ?? $item->source }}</span>
      <span aria-hidden="true">·</span>
      <time datetime="{{ $item->date()->toIso8601String() }}" data-rel>{{ $item->date()->format('d/m H:i') }}</time>
      @if ($source?->official)
        <span aria-hidden="true">·</span>
        <span class="official">oficial</span>
      @endif
    </div>
    <h3><a href="{{ $item->url }}" target="_blank" rel="noopener">{{ $item->title }}</a></h3>
    @if ($item->summary !== '')
      <p>{{ $item->summary }}</p>
    @endif
    <div class="card-foot">
      @if ($cluster->others)
        <span class="also">Também em:
          @foreach ($cluster->others as $other)
            <a href="{{ $other->url }}" target="_blank" rel="noopener">{{ ($sources[$other->source] ?? null)?->short ?? $other->source }}</a>@if (! $loop->last), @endif
          @endforeach
        </span>
      @endif
      <a class="share" href="{{ $share }}" target="_blank" rel="noopener">Compartilhar no WhatsApp</a>
    </div>
  </div>
</article>

{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{{ $site['name'] }}</title>
    <link>{{ $site['url'] }}/</link>
    <description>{{ $site['tagline'] }}</description>
    <language>pt-BR</language>
    <lastBuildDate>{{ $now->toRfc2822String() }}</lastBuildDate>
    <atom:link href="{{ $site['url'] }}/feed.xml" rel="self" type="application/rss+xml"/>
@foreach ($clusters as $cluster)
@php($item = $cluster->primary)
@php($source = $sources[$item->source] ?? null)
    <item>
      <title>{{ $item->title }}</title>
      <link>{{ $item->url }}</link>
      <guid isPermaLink="false">{{ $item->id() }}</guid>
      <pubDate>{{ $item->date()->toRfc2822String() }}</pubDate>
      <description>{{ $item->summary }}</description>
@if ($source)
      <source url="{{ $source->homepage }}">{{ $source->name }}</source>
@endif
    </item>
@endforeach
  </channel>
</rss>

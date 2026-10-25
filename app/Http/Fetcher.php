<?php

namespace App\Http;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

final class Fetcher
{
    /**
     * Baixa várias URLs em paralelo.
     *
     * @param  array<array-key, array{url: string, headers?: array<string, string>}>  $requests
     * @return array<array-key, FetchResult>
     */
    public function many(array $requests): array
    {
        $results = [];

        foreach (array_chunk($requests, max(1, (int) config('itape.http.concurrency')), true) as $chunk) {
            $responses = Http::pool(function (Pool $pool) use ($chunk) {
                foreach ($chunk as $key => $request) {
                    $this->configure($pool->as((string) $key), $request['headers'] ?? [])->get($request['url']);
                }
            });

            foreach ($chunk as $key => $request) {
                $results[$key] = $this->toResult($responses[$key] ?? null);
            }
        }

        return $results;
    }

    /**
     * @param  array<string, string>  $headers
     */
    public function get(string $url, array $headers = []): FetchResult
    {
        try {
            return $this->toResult($this->configure(Http::createPendingRequest(), $headers)->get($url));
        } catch (Throwable $e) {
            return FetchResult::failed($e->getMessage());
        }
    }

    /**
     * Lê só os primeiros $maxBytes da resposta e fecha a conexão. Usado no
     * sitemap do Correio, que tem vários MB e não aceita Range.
     *
     * @param  array<string, string>  $headers
     */
    public function head(string $url, int $maxBytes, array $headers = []): FetchResult
    {
        try {
            $response = $this->configure(Http::createPendingRequest(), $headers)
                ->withOptions(['stream' => true])
                ->get($url);

            $stream = $response->toPsrResponse()->getBody();
            $body = '';

            while (! $stream->eof() && strlen($body) < $maxBytes) {
                $body .= $stream->read(min(8192, $maxBytes - strlen($body)));
            }

            $stream->close();

            return new FetchResult(
                status: $response->status(),
                body: $body,
                etag: $response->header('ETag') ?: null,
                lastModified: $response->header('Last-Modified') ?: null,
            );
        } catch (Throwable $e) {
            return FetchResult::failed($e->getMessage());
        }
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function configure(PendingRequest $request, array $headers): PendingRequest
    {
        return $request
            ->withHeaders([
                'User-Agent' => config('itape.http.user_agent'),
                'Accept' => 'text/html,application/xhtml+xml,application/rss+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'pt-BR,pt;q=0.9',
                ...$headers,
            ])
            ->timeout((int) config('itape.http.timeout'))
            ->connectTimeout((int) config('itape.http.connect_timeout'));
    }

    private function toResult(mixed $response): FetchResult
    {
        if ($response instanceof Response) {
            return new FetchResult(
                status: $response->status(),
                body: $response->body(),
                etag: $response->header('ETag') ?: null,
                lastModified: $response->header('Last-Modified') ?: null,
            );
        }

        if ($response instanceof ConnectionException || $response instanceof Throwable) {
            return FetchResult::failed($response->getMessage());
        }

        return FetchResult::failed('Sem resposta');
    }
}

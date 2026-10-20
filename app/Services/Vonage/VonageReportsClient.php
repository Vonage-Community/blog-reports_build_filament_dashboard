<?php

namespace App\Services\Vonage;

use App\Enums\VonageProduct;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around the synchronous Vonage Reports API (`GET /v2/reports/records`).
 *
 * @see https://developer.vonage.com/en/api/reports
 */
class VonageReportsClient
{
    public function __construct(
        private ?string $apiKey,
        private ?string $apiSecret,
        private ?string $accountId,
        private string $baseUrl,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->apiKey) && filled($this->apiSecret) && filled($this->accountId);
    }

    /**
     * Stream every record for the given product and window, following `_links.next` cursors.
     *
     * @return LazyCollection<int, array<string, mixed>>
     */
    public function records(VonageProduct $product, CarbonInterface $from, CarbonInterface $to, ?string $direction = null): LazyCollection
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Vonage API credentials are not configured. Set VONAGE_API_KEY and VONAGE_API_SECRET.');
        }

        $query = array_filter([
            'account_id' => $this->accountId,
            'product' => $product->value,
            'direction' => $product->requiresDirection() ? ($direction ?? 'outbound') : null,
            'date_start' => $from->toIso8601ZuluString(),
            'date_end' => $to->toIso8601ZuluString(),
        ]);

        return LazyCollection::make(function () use ($query) {
            $response = $this->request()->get(rtrim($this->baseUrl, '/').'/records', $query)->throw();

            while (true) {
                foreach ($response->json('records', []) as $record) {
                    yield $record;
                }

                $next = $response->json('_links.next.href');

                if (blank($next)) {
                    return;
                }

                $response = $this->request()->get($next)->throw();
            }
        });
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth((string) $this->apiKey, (string) $this->apiSecret)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(30)
            ->retry([200, 1000, 3000], 0, fn (Throwable $exception): bool => $exception instanceof ConnectionException
                || ($exception instanceof RequestException
                    && ($exception->response->serverError() || $exception->response->status() === 429)));
    }
}

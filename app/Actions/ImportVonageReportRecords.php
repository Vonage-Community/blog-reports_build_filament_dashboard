<?php

namespace App\Actions;

use App\Enums\VonageProduct;
use App\Models\VonageReportRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

/**
 * Normalises raw Vonage Reports API records and upserts them into `vonage_report_records`.
 */
class ImportVonageReportRecords
{
    private const CHUNK_SIZE = 500;

    /**
     * @param  iterable<int, array<string, mixed>>  $records
     * @return int The number of records persisted.
     */
    public function handle(VonageProduct $product, iterable $records): int
    {
        $imported = 0;
        $buffer = [];

        foreach ($records as $record) {
            $row = $this->normalise($product, $record);

            if ($row === null) {
                continue;
            }

            $buffer[] = $row;

            if (count($buffer) >= self::CHUNK_SIZE) {
                $imported += $this->flush($buffer);
                $buffer = [];
            }
        }

        return $imported + $this->flush($buffer);
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    public function normalise(VonageProduct $product, array $record): ?array
    {
        $externalId = Arr::first(
            [$record['message_id'] ?? null, $record['message_uuid'] ?? null, $record['call_id'] ?? null, $record['uuid'] ?? null, $record['id'] ?? null],
            fn (mixed $value): bool => filled($value),
        );

        $occurredAt = $record['date_received'] ?? $record['date_start'] ?? $record['start_time'] ?? null;

        if ($externalId === null || $occurredAt === null) {
            return null;
        }

        $now = now();

        return [
            'product' => $product->value,
            'external_id' => (string) $externalId,
            'account_id' => $record['account_id'] ?? null,
            'direction' => $record['direction'] ?? null,
            'channel' => $record['channel'] ?? $record['provider'] ?? null,
            'from' => $record['from'] ?? null,
            'to' => $record['to'] ?? null,
            'status' => isset($record['status']) ? strtolower((string) $record['status']) : null,
            'error_code' => $this->nullableString($record['error_code'] ?? null),
            'error_description' => $record['error_code_description'] ?? $record['error_description'] ?? null,
            'country' => $record['country'] ?? null,
            'country_name' => $record['country_name'] ?? null,
            'network_name' => $record['network_name'] ?? null,
            'total_price' => $record['total_price'] ?? $record['price'] ?? null,
            'currency' => $record['currency'] ?? null,
            'latency_ms' => isset($record['latency']) ? (int) $record['latency'] : null,
            'duration_seconds' => isset($record['duration']) ? (int) $record['duration'] : null,
            'occurred_at' => Carbon::parse($occurredAt)->utc(),
            'finalized_at' => isset($record['date_finalized']) || isset($record['end_time'])
                ? Carbon::parse($record['date_finalized'] ?? $record['end_time'])->utc()
                : null,
            'payload' => json_encode($record),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function flush(array $rows): int
    {
        if ($rows === []) {
            return 0;
        }

        VonageReportRecord::query()->upsert(
            $rows,
            ['product', 'external_id'],
            array_diff(array_keys($rows[0]), ['product', 'external_id', 'created_at']),
        );

        return count($rows);
    }

    private function nullableString(mixed $value): ?string
    {
        return filled($value) ? (string) $value : null;
    }
}

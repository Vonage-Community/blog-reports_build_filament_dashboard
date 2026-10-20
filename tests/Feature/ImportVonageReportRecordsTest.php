<?php

use App\Actions\ImportVonageReportRecords;
use App\Enums\VonageProduct;
use App\Models\VonageReportRecord;

it('normalises SMS records and is idempotent on re-import', function () {
    $record = [
        'account_id' => 'abcd1234',
        'message_id' => 'msg-1',
        'direction' => 'outbound',
        'from' => 'Vonage',
        'to' => '447700900000',
        'country' => 'GB',
        'country_name' => 'United Kingdom',
        'network_name' => 'EE',
        'date_received' => '2026-10-01T14:26:41+01:00',
        'date_finalized' => '2026-10-01T14:26:42+01:00',
        'latency' => '1035',
        'status' => 'DELIVERED',
        'error_code' => '0',
        'error_code_description' => 'Delivered',
        'currency' => 'EUR',
        'total_price' => '0.03320000',
    ];

    $importer = app(ImportVonageReportRecords::class);

    expect($importer->handle(VonageProduct::Sms, [$record]))->toBe(1);

    $importer->handle(VonageProduct::Sms, [[...$record, 'status' => 'failed']]);

    $stored = VonageReportRecord::sole();

    expect($stored)
        ->product->toBe(VonageProduct::Sms)
        ->external_id->toBe('msg-1')
        ->status->toBe('failed')
        ->latency_ms->toBe(1035)
        ->total_price->toBe('0.03320000')
        ->and($stored->occurred_at->toIso8601ZuluString())->toBe('2026-10-01T13:26:41Z')
        ->and($stored->payload['to'])->toBe('447700900000');
});

it('maps voice call fields', function () {
    app(ImportVonageReportRecords::class)->handle(VonageProduct::VoiceCall, [[
        'call_id' => 'call-1',
        'start_time' => '2026-10-01T10:00:00Z',
        'end_time' => '2026-10-01T10:02:00Z',
        'duration' => '120',
        'status' => 'completed',
        'price' => '0.01',
        'total_price' => '0.02',
    ]]);

    expect(VonageReportRecord::sole())
        ->external_id->toBe('call-1')
        ->duration_seconds->toBe(120)
        ->total_price->toBe('0.02000000')
        ->finalized_at->toIso8601ZuluString()->toBe('2026-10-01T10:02:00Z');
});

it('skips records without an identifier or timestamp', function () {
    $imported = app(ImportVonageReportRecords::class)->handle(VonageProduct::Messages, [
        ['status' => 'delivered', 'date_received' => '2026-10-01T10:00:00Z'],
        ['message_uuid' => 'uuid-1'],
    ]);

    expect($imported)->toBe(0)
        ->and(VonageReportRecord::count())->toBe(0);
});

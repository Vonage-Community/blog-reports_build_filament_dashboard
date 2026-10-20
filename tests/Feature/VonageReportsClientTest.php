<?php

use App\Enums\VonageProduct;
use App\Services\Vonage\VonageReportsClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

function reportsClient(?string $key = 'key', ?string $secret = 'secret'): VonageReportsClient
{
    return new VonageReportsClient($key, $secret, 'acc123', 'https://api.nexmo.com/v2/reports');
}

it('follows next links until every page of records is consumed', function () {
    Http::fake([
        'api.nexmo.com/v2/reports/records?cursor=page2*' => Http::response([
            '_links' => ['self' => ['href' => 'x']],
            'records' => [['message_id' => 'b']],
        ]),
        'api.nexmo.com/v2/reports/records*' => Http::response([
            '_links' => ['next' => ['href' => 'https://api.nexmo.com/v2/reports/records?cursor=page2']],
            'records' => [['message_id' => 'a']],
        ]),
    ]);

    $records = reportsClient()
        ->records(VonageProduct::Sms, Carbon::parse('2026-10-01'), Carbon::parse('2026-10-02'), 'inbound')
        ->all();

    expect(array_column($records, 'message_id'))->toBe(['a', 'b']);

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Basic '.base64_encode('key:secret'))
        && ($request->data()['account_id'] ?? null) === 'acc123'
        && ($request->data()['product'] ?? null) === 'SMS'
        && ($request->data()['direction'] ?? null) === 'inbound'
        && ($request->data()['date_start'] ?? null) === '2026-10-01T00:00:00Z');
});

it('omits direction for voice records', function () {
    Http::fake(['api.nexmo.com/*' => Http::response(['records' => []])]);

    reportsClient()->records(VonageProduct::VoiceCall, now()->subDay(), now(), 'outbound')->all();

    Http::assertSent(fn (Request $request): bool => ! array_key_exists('direction', $request->data()));
});

it('throws when the API rejects the request', function () {
    Http::fake(['api.nexmo.com/*' => Http::response(['title' => 'Unauthorized'], 401)]);

    reportsClient()->records(VonageProduct::Sms, now()->subDay(), now())->all();
})->throws(RequestException::class);

it('refuses to call the API without credentials', function () {
    reportsClient(key: null)->records(VonageProduct::Sms, now()->subDay(), now())->all();
})->throws(RuntimeException::class, 'credentials are not configured');

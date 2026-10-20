<?php

use App\Models\VonageReportRecord;
use App\Services\Vonage\VonageReportsClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
    config()->set('services.vonage.key', 'key');
    config()->set('services.vonage.secret', 'secret');
    config()->set('services.vonage.account_id', 'acc123');
});

it('syncs the requested product and directions into the database', function () {
    Http::fake(fn (Request $request) => Http::response([
        'records' => [[
            'message_id' => 'msg-'.$request->data()['direction'],
            'direction' => $request->data()['direction'],
            'date_received' => '2026-10-01T10:00:00Z',
            'status' => 'delivered',
        ]],
    ]));

    $this->artisan('vonage:sync-reports', ['--product' => ['sms']])
        ->expectsOutputToContain('SMS outbound: imported 1 records.')
        ->expectsOutputToContain('SMS inbound: imported 1 records.')
        ->assertSuccessful();

    expect(VonageReportRecord::pluck('external_id')->sort()->values()->all())->toBe(['msg-inbound', 'msg-outbound']);
});

it('fails without calling the API when credentials are missing', function () {
    config()->set('services.vonage.key', null);
    $this->app->forgetInstance(VonageReportsClient::class);

    $this->artisan('vonage:sync-reports')->assertFailed();

    Http::assertNothingSent();
});

it('reports a failure but continues with other products when one request fails', function () {
    Http::fake(fn (Request $request) => $request->data()['product'] === 'SMS'
        ? Http::response([], 401)
        : Http::response(['records' => [['call_id' => 'c1', 'start_time' => '2026-10-01T10:00:00Z']]]));

    $this->artisan('vonage:sync-reports', ['--product' => ['SMS', 'VOICE-CALL'], '--direction' => ['outbound']])
        ->expectsOutputToContain('Voice: imported 1 records.')
        ->assertFailed();

    expect(VonageReportRecord::count())->toBe(1);
});

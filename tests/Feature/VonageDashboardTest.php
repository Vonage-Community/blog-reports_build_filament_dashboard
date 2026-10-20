<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\VonageReportRecords\Pages\ListVonageReportRecords;
use App\Filament\Resources\VonageReportRecords\Pages\ViewVonageReportRecord;
use App\Filament\Widgets\RecentFailuresTable;
use App\Filament\Widgets\ReportStatsOverview;
use App\Filament\Widgets\TrafficVolumeChart;
use App\Models\User;
use App\Models\VonageReportRecord;
use Livewire\Livewire;

it('redirects guests from the landing page to the login screen', function () {
    $this->get('/')->assertRedirect('/login');
});

it('lands authenticated users on the Vonage reports dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Vonage Reports');
});

it('renders the dashboard widgets with report data', function () {
    $this->actingAs(User::factory()->create());
    VonageReportRecord::factory()->count(3)->create(['occurred_at' => now()]);
    VonageReportRecord::factory()->voice()->create(['occurred_at' => now()]);
    $failure = VonageReportRecord::factory()->failed()->create(['occurred_at' => now(), 'to' => '447700911111']);

    Livewire::test(Dashboard::class)->assertOk();
    Livewire::test(ReportStatsOverview::class, ['pageFilters' => ['period' => 7]])
        ->assertOk()
        ->assertSee('Records')
        ->assertSee('80.0%');
    Livewire::test(TrafficVolumeChart::class, ['pageFilters' => ['period' => 7]])->assertOk();
    Livewire::test(RecentFailuresTable::class, ['pageFilters' => ['period' => 7]])
        ->assertCanSeeTableRecords([$failure])
        ->assertCountTableRecords(1);
});

it('excludes records outside the selected dashboard period', function () {
    $this->actingAs(User::factory()->create());
    $recent = VonageReportRecord::factory()->failed()->create(['occurred_at' => now()]);
    $old = VonageReportRecord::factory()->failed()->create(['occurred_at' => now()->subDays(20)]);

    Livewire::test(RecentFailuresTable::class, ['pageFilters' => ['period' => 7]])
        ->assertCanSeeTableRecords([$recent])
        ->assertCanNotSeeTableRecords([$old]);
});

it('lists and filters report records', function () {
    $this->actingAs(User::factory()->create());
    $sms = VonageReportRecord::factory()->create();
    $voice = VonageReportRecord::factory()->voice()->create();

    Livewire::test(ListVonageReportRecords::class)
        ->assertCanSeeTableRecords([$sms, $voice])
        ->filterTable('product', 'VOICE-CALL')
        ->assertCanSeeTableRecords([$voice])
        ->assertCanNotSeeTableRecords([$sms]);
});

it('shows a single report record', function () {
    $this->actingAs(User::factory()->create());
    $record = VonageReportRecord::factory()->create(['external_id' => 'msg-xyz']);

    Livewire::test(ViewVonageReportRecord::class, ['record' => $record->getRouteKey()])
        ->assertOk()
        ->assertSee('msg-xyz');
});

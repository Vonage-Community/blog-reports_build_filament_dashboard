<?php

namespace App\Providers;

use App\Services\Vonage\VonageReportsClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VonageReportsClient::class, fn (): VonageReportsClient => new VonageReportsClient(
            apiKey: config('services.vonage.key'),
            apiSecret: config('services.vonage.secret'),
            accountId: config('services.vonage.account_id'),
            baseUrl: config('services.vonage.reports_url'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

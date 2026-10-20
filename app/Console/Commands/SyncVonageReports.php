<?php

namespace App\Console\Commands;

use App\Actions\ImportVonageReportRecords;
use App\Enums\VonageProduct;
use App\Services\Vonage\VonageReportsClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

#[Signature('vonage:sync-reports
    {--product=* : Products to sync (SMS, MESSAGES, VOICE-CALL). Defaults to all}
    {--direction=* : Directions to sync for SMS/MESSAGES (inbound, outbound). Defaults to both}
    {--from= : Start of the window (any date string). Defaults to 24 hours ago}
    {--to= : End of the window (any date string). Defaults to now}')]
#[Description('Pull records from the Vonage Reports API and persist them locally')]
class SyncVonageReports extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(VonageReportsClient $client, ImportVonageReportRecords $importer): int
    {
        if (! $client->isConfigured()) {
            $this->components->error('Vonage API credentials are missing. Set VONAGE_API_KEY and VONAGE_API_SECRET in your .env file.');

            return self::FAILURE;
        }

        $to = Carbon::parse($this->option('to') ?? 'now');
        $from = Carbon::parse($this->option('from') ?? $to->copy()->subDay());

        $products = collect($this->option('product'))
            ->map(fn (string $product): VonageProduct => VonageProduct::from(strtoupper($product)))
            ->whenEmpty(fn () => collect(VonageProduct::cases()));

        $directions = $this->option('direction') ?: ['outbound', 'inbound'];
        $failed = false;

        foreach ($products as $product) {
            foreach ($product->requiresDirection() ? $directions : [null] as $direction) {
                $label = trim($product->label().' '.$direction);

                try {
                    $count = $importer->handle($product, $client->records($product, $from, $to, $direction));
                    $this->components->info("{$label}: imported {$count} records.");
                } catch (Throwable $exception) {
                    report($exception);
                    $failed = true;
                    $this->components->error("{$label}: {$exception->getMessage()}");
                }
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}

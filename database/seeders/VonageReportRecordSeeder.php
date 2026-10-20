<?php

namespace Database\Seeders;

use App\Actions\ImportVonageReportRecords;
use App\Enums\VonageProduct;
use App\Services\Vonage\VonageReportsClient;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Seeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Seeds demo data by faking the Vonage Reports API and consuming it through the
 * real client + importer, so the persisted rows match what a live sync produces.
 */
class VonageReportRecordSeeder extends Seeder
{
    private const DAYS = 30;

    private const PAGE_SIZE = 1000;

    private const ACCOUNT_ID = 'abcd1234';

    /**
     * @var list<array{code: string, name: string, networks: list<string>, prefix: string, sms: float, voice: float, weight: int}>
     */
    private const COUNTRIES = [
        ['code' => 'GB', 'name' => 'United Kingdom', 'networks' => ['Vodafone UK', 'EE', 'O2 UK', 'Three UK'], 'prefix' => '447', 'sms' => 0.0332, 'voice' => 0.0120, 'weight' => 30],
        ['code' => 'US', 'name' => 'United States', 'networks' => ['Verizon Wireless', 'T-Mobile US', 'AT&T Mobility'], 'prefix' => '1', 'sms' => 0.0079, 'voice' => 0.0098, 'weight' => 25],
        ['code' => 'DE', 'name' => 'Germany', 'networks' => ['Telekom.de', 'Vodafone.de', 'O2 Germany'], 'prefix' => '4915', 'sms' => 0.0790, 'voice' => 0.0310, 'weight' => 10],
        ['code' => 'FR', 'name' => 'France', 'networks' => ['Orange France', 'SFR', 'Bouygues Telecom'], 'prefix' => '336', 'sms' => 0.0720, 'voice' => 0.0270, 'weight' => 8],
        ['code' => 'ES', 'name' => 'Spain', 'networks' => ['Movistar', 'Vodafone ES', 'Orange ES'], 'prefix' => '346', 'sms' => 0.0650, 'voice' => 0.0240, 'weight' => 6],
        ['code' => 'IT', 'name' => 'Italy', 'networks' => ['TIM', 'Vodafone IT', 'WindTre'], 'prefix' => '393', 'sms' => 0.0700, 'voice' => 0.0260, 'weight' => 5],
        ['code' => 'NL', 'name' => 'Netherlands', 'networks' => ['KPN', 'VodafoneZiggo', 'Odido'], 'prefix' => '316', 'sms' => 0.0880, 'voice' => 0.0290, 'weight' => 4],
        ['code' => 'BR', 'name' => 'Brazil', 'networks' => ['Vivo', 'Claro BR', 'TIM Brasil'], 'prefix' => '5511', 'sms' => 0.0310, 'voice' => 0.0180, 'weight' => 5],
        ['code' => 'IN', 'name' => 'India', 'networks' => ['Jio', 'Airtel', 'Vi'], 'prefix' => '919', 'sms' => 0.0045, 'voice' => 0.0110, 'weight' => 4],
        ['code' => 'AU', 'name' => 'Australia', 'networks' => ['Telstra', 'Optus', 'TPG Telecom'], 'prefix' => '614', 'sms' => 0.0420, 'voice' => 0.0230, 'weight' => 3],
    ];

    /**
     * Seed the application's database.
     */
    public function run(ImportVonageReportRecords $importer): void
    {
        $datasets = [
            'SMS:outbound' => $this->smsRecords('outbound', 150),
            'SMS:inbound' => $this->smsRecords('inbound', 20),
            'MESSAGES:outbound' => $this->messagesRecords('outbound', 90),
            'MESSAGES:inbound' => $this->messagesRecords('inbound', 15),
            'VOICE-CALL:' => $this->voiceRecords(40),
        ];

        $baseUrl = 'https://api.nexmo.com/v2/reports';

        Http::preventStrayRequests();
        Http::fake([
            "{$baseUrl}/records*" => fn (Request $request) => $this->fakeResponse($request, $datasets, $baseUrl),
        ]);

        $client = new VonageReportsClient('demo-key', 'demo-secret', self::ACCOUNT_ID, $baseUrl);
        $to = now();
        $from = $to->copy()->subDays(self::DAYS);

        foreach ($datasets as $key => $records) {
            [$product, $direction] = explode(':', $key);
            $product = VonageProduct::from($product);

            $count = $importer->handle($product, $client->records($product, $from, $to, $direction ?: null));

            $this->command?->info("Imported {$count} {$product->label()} {$direction} records from the (faked) Vonage Reports API.");
        }
    }

    /**
     * Serve one cursor-paginated page in the same shape as `GET /v2/reports/records`.
     *
     * @param  array<string, list<array<string, mixed>>>  $datasets
     */
    private function fakeResponse(Request $request, array $datasets, string $baseUrl): PromiseInterface
    {
        $query = $request->data();
        $key = $query['product'].':'.($query['direction'] ?? '');
        $offset = (int) ($query['cursor'] ?? 0);
        $records = array_slice($datasets[$key] ?? [], $offset, self::PAGE_SIZE);
        $nextOffset = $offset + self::PAGE_SIZE;

        $links = ['self' => ['href' => $request->url()]];

        if ($nextOffset < count($datasets[$key] ?? [])) {
            $links['next'] = ['href' => $baseUrl.'/records?'.http_build_query([...$query, 'cursor' => $nextOffset])];
        }

        return Http::response([
            '_links' => $links,
            'request_status' => 'SUCCESS',
            'received_at' => now()->toIso8601ZuluString(),
            'items_count' => count($records),
            'records' => $records,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function smsRecords(string $direction, int $perDay): array
    {
        return $this->spread($perDay, function (Carbon $at) use ($direction): array {
            $country = $this->country();
            $status = $direction === 'inbound' ? 'delivered' : $this->weighted(['delivered' => 88, 'failed' => 5, 'expired' => 3, 'rejected' => 2, 'accepted' => 2]);
            [$errorCode, $errorDescription] = $this->smsError($status);
            $latency = $direction === 'inbound' ? null : fake()->numberBetween(250, $status === 'delivered' ? 6000 : 30000);
            $number = $this->msisdn($country['prefix']);

            return [
                'account_id' => self::ACCOUNT_ID,
                'message_id' => Str::uuid()->toString(),
                'client_ref' => fake()->optional(0.3)->bothify('order-####'),
                'direction' => $direction,
                'from' => $direction === 'outbound' ? fake()->randomElement(['Vonage', 'AcmeShop', '447700900001']) : $number,
                'to' => $direction === 'outbound' ? $number : '447700900001',
                'network' => (string) fake()->numberBetween(20000, 99999),
                'network_name' => fake()->randomElement($country['networks']),
                'country' => $country['code'],
                'country_name' => $country['name'],
                'date_received' => $at->toIso8601String(),
                'date_finalized' => $latency !== null ? $at->copy()->addMilliseconds($latency)->toIso8601String() : $at->toIso8601String(),
                'latency' => $latency !== null ? (string) $latency : null,
                'status' => $status,
                'error_code' => $errorCode,
                'error_code_description' => $errorDescription,
                'currency' => 'EUR',
                'total_price' => $direction === 'inbound' ? '0.00000000' : number_format($country['sms'] * fake()->randomElement([1, 1, 1, 2]), 8, '.', ''),
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagesRecords(string $direction, int $perDay): array
    {
        return $this->spread($perDay, function (Carbon $at) use ($direction): array {
            $country = $this->country();
            $channel = $this->weighted(['whatsapp' => 55, 'sms' => 20, 'rcs' => 10, 'viber_service' => 8, 'messenger' => 7]);
            $status = $direction === 'inbound' ? 'delivered' : $this->weighted(['read' => 45, 'delivered' => 40, 'submitted' => 5, 'rejected' => 4, 'undeliverable' => 6]);
            $latency = fake()->numberBetween(200, 5000);
            $number = $this->msisdn($country['prefix']);
            $failed = in_array($status, ['rejected', 'undeliverable'], true);

            return [
                'account_id' => self::ACCOUNT_ID,
                'message_uuid' => Str::uuid()->toString(),
                'direction' => $direction,
                'channel' => $channel,
                'from' => $direction === 'outbound' ? '447700900002' : $number,
                'to' => $direction === 'outbound' ? $number : '447700900002',
                'country' => $country['code'],
                'country_name' => $country['name'],
                'date_received' => $at->toIso8601String(),
                'date_finalized' => $at->copy()->addMilliseconds($latency)->toIso8601String(),
                'latency' => (string) $latency,
                'status' => $status,
                'error_code' => $failed ? (string) fake()->randomElement([1020, 1300, 1330]) : null,
                'error_code_description' => $failed ? fake()->randomElement(['Invalid recipient', 'Not part of the provider network', 'Outside the allowed window']) : null,
                'currency' => 'EUR',
                'total_price' => $direction === 'inbound' ? '0.00000000' : number_format(match ($channel) {
                    'whatsapp' => 0.0450,
                    'rcs' => 0.0300,
                    'sms' => $country['sms'],
                    default => 0.0150,
                }, 8, '.', ''),
            ];
        });
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function voiceRecords(int $perDay): array
    {
        return $this->spread($perDay, function (Carbon $at): array {
            $country = $this->country();
            $direction = fake()->boolean(70) ? 'outbound' : 'inbound';
            $status = $this->weighted(['completed' => 72, 'unanswered' => 10, 'busy' => 6, 'failed' => 5, 'timeout' => 4, 'cancelled' => 3]);
            $duration = $status === 'completed' ? fake()->numberBetween(8, 900) : 0;
            $number = $this->msisdn($country['prefix']);

            return [
                'account_id' => self::ACCOUNT_ID,
                'call_id' => Str::uuid()->toString(),
                'direction' => $direction,
                'from' => $direction === 'outbound' ? '447700900003' : $number,
                'to' => $direction === 'outbound' ? $number : '447700900003',
                'network_name' => fake()->randomElement($country['networks']),
                'country' => $country['code'],
                'country_name' => $country['name'],
                'start_time' => $at->toIso8601String(),
                'end_time' => $at->copy()->addSeconds($duration)->toIso8601String(),
                'duration' => (string) $duration,
                'status' => $status,
                'currency' => 'EUR',
                'price' => number_format($country['voice'], 8, '.', ''),
                'total_price' => number_format($country['voice'] * (int) ceil($duration / 60), 8, '.', ''),
            ];
        });
    }

    /**
     * Generate records across the last N days with a weekday/business-hours traffic shape.
     *
     * @param  callable(Carbon): array<string, mixed>  $makeRecord
     * @return list<array<string, mixed>>
     */
    private function spread(int $perDay, callable $makeRecord): array
    {
        $records = [];

        for ($day = self::DAYS; $day >= 0; $day--) {
            $date = now()->subDays($day)->startOfDay();
            $growth = 1 + ((self::DAYS - $day) / self::DAYS) * 0.35;
            $weekendFactor = $date->isWeekend() ? 0.55 : 1.0;
            $count = (int) round($perDay * $growth * $weekendFactor * fake()->randomFloat(2, 0.8, 1.2));

            for ($i = 0; $i < $count; $i++) {
                $at = $date->copy()
                    ->addHours($this->weighted([8 => 6, 9 => 9, 10 => 10, 11 => 10, 12 => 8, 13 => 8, 14 => 9, 15 => 9, 16 => 8, 17 => 7, 18 => 5, 19 => 4, 20 => 3, 21 => 2, 7 => 2]))
                    ->addMinutes(fake()->numberBetween(0, 59))
                    ->addSeconds(fake()->numberBetween(0, 59));

                if ($at->isFuture()) {
                    continue;
                }

                $records[] = $makeRecord($at);
            }
        }

        return $records;
    }

    /**
     * @return array{code: string, name: string, networks: list<string>, prefix: string, sms: float, voice: float, weight: int}
     */
    private function country(): array
    {
        $index = $this->weighted(array_map(fn (array $country): int => $country['weight'], self::COUNTRIES));

        return self::COUNTRIES[$index];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function smsError(string $status): array
    {
        return match ($status) {
            'failed' => fake()->randomElement([['6', 'Anti-Spam Rejection'], ['5', 'Destination Carrier Rejected'], ['1', 'Unknown']]),
            'expired' => ['3', 'Absent Subscriber Temporary'],
            'rejected' => fake()->randomElement([['29', 'Non White-listed Destination'], ['9', 'Illegal Number']]),
            'delivered' => ['0', 'Delivered'],
            default => [null, null],
        };
    }

    private function msisdn(string $prefix): string
    {
        return $prefix.fake()->numerify(str_repeat('#', 11 - strlen($prefix) + 1));
    }

    /**
     * Pick a key from a [key => weight] map.
     *
     * @template TKey of array-key
     *
     * @param  array<TKey, int>  $weights
     * @return TKey
     */
    private function weighted(array $weights): int|string
    {
        $roll = fake()->numberBetween(1, array_sum($weights));

        foreach ($weights as $key => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}

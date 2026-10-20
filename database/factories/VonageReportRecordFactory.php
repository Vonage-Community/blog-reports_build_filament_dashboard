<?php

namespace Database\Factories;

use App\Enums\VonageProduct;
use App\Models\VonageReportRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VonageReportRecord>
 */
class VonageReportRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $occurredAt = fake()->dateTimeBetween('-30 days');

        return [
            'product' => VonageProduct::Sms,
            'external_id' => fake()->uuid(),
            'account_id' => 'abcd1234',
            'direction' => 'outbound',
            'from' => 'Vonage',
            'to' => fake()->numerify('4477009#####'),
            'status' => 'delivered',
            'country' => 'GB',
            'country_name' => 'United Kingdom',
            'network_name' => 'Vodafone UK',
            'total_price' => fake()->randomFloat(4, 0.01, 0.08),
            'currency' => 'EUR',
            'latency_ms' => fake()->numberBetween(300, 4000),
            'occurred_at' => $occurredAt,
            'finalized_at' => $occurredAt,
            'payload' => [],
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'error_code' => '6',
            'error_description' => 'Anti-Spam Rejection',
        ]);
    }

    public function voice(): static
    {
        return $this->state(fn (): array => [
            'product' => VonageProduct::VoiceCall,
            'status' => 'completed',
            'latency_ms' => null,
            'duration_seconds' => fake()->numberBetween(5, 600),
        ]);
    }

    public function messages(string $channel = 'whatsapp'): static
    {
        return $this->state(fn (): array => [
            'product' => VonageProduct::Messages,
            'channel' => $channel,
        ]);
    }
}

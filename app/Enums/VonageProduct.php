<?php

namespace App\Enums;

/**
 * Products supported by the Vonage Reports API that this application ingests.
 */
enum VonageProduct: string
{
    case Sms = 'SMS';
    case Messages = 'MESSAGES';
    case VoiceCall = 'VOICE-CALL';

    public function label(): string
    {
        return match ($this) {
            self::Sms => 'SMS',
            self::Messages => 'Messages API',
            self::VoiceCall => 'Voice',
        };
    }

    /**
     * Whether the Reports API requires a `direction` parameter for this product.
     */
    public function requiresDirection(): bool
    {
        return $this !== self::VoiceCall;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $product): array => [$product->value => $product->label()])
            ->all();
    }
}

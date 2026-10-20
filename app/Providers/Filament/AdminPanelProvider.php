<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Vonage purple (#871FFF) at shade 600, with Tailwind Violet's lightness/chroma
     * curve at Vonage's hue. `Color::hex()` caps chroma and washes the purple out.
     *
     * @var array<int, string>
     */
    private const VONAGE_PURPLE = [
        50 => 'oklch(0.969 0.016 295.442)',
        100 => 'oklch(0.943 0.029 295.442)',
        200 => 'oklch(0.894 0.057 295.442)',
        300 => 'oklch(0.811 0.111 295.442)',
        400 => 'oklch(0.702 0.183 295.442)',
        500 => 'oklch(0.606 0.25 295.442)',
        600 => 'oklch(0.548 0.284 295.442)',
        700 => 'oklch(0.491 0.27 295.442)',
        800 => 'oklch(0.432 0.232 295.442)',
        900 => 'oklch(0.38 0.189 295.442)',
        950 => 'oklch(0.283 0.141 295.442)',
    ];

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->brandName('Vonage Reports')
            ->brandLogo(asset('images/vonage-logo-black.png'))
            ->darkModeBrandLogo(asset('images/vonage-logo-white.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/vonage-favicon.png'))
            ->colors([
                'primary' => self::VONAGE_PURPLE,
                'gray' => Color::Zinc,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): View => view('filament.brand-styles'))
            ->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn (): View => view('filament.sidebar-footer'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

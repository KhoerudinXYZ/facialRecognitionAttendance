<?php

namespace App\Providers;

use App\Contracts\WhatsAppGateway;
use App\Services\WhatsApp\LogWhatsAppGateway;
use App\View\Composers\NavigationReminderComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Belum ada akun penyedia WhatsApp API (Fonnte/WABlas/dst) yang
        // dipilih — begitu ada, ganti binding ini ke implementasi
        // sebenarnya (mis. FonnteWhatsAppGateway) tanpa menyentuh
        // AbsensiAlphaChecker sama sekali.
        $this->app->bind(WhatsAppGateway::class, LogWhatsAppGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', NavigationReminderComposer::class);
    }
}

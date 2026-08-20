<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Otp\Delivery\OtpDeliveryInterface::class, function ($app) {
            $mode = config('otp.delivery_mode', 'meta_whatsapp');
            $debug = config('app.debug', false);

            if ($mode === 'dev' && !$debug) {
                throw new \RuntimeException('CRITICAL: DEV OTP mode cannot be enabled when APP_DEBUG is disabled.');
            }

            return match ($mode) {
                'dev' => $app->make(\App\Services\Otp\Delivery\DevOtpDeliveryService::class),
                'meta_whatsapp' => $app->make(\App\Services\Otp\Delivery\MetaWhatsAppService::class),
                'waty_whatsapp' => $app->make(\App\Services\Otp\Delivery\WatyWhatsAppService::class),
                default => throw new \InvalidArgumentException("Unsupported OTP delivery mode: {$mode}"),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $mode = config('otp.delivery_mode', 'meta_whatsapp');
        $debug = config('app.debug', false);

        if ($mode === 'dev' && !$debug) {
            throw new \RuntimeException('CRITICAL: DEV OTP mode cannot be enabled when APP_DEBUG is disabled.');
        }

        \App\Models\Membership::observe(\App\Observers\MembershipObserver::class);
        
        // Share counts with admin sidebar
        \Illuminate\Support\Facades\View::composer('layouts.admin', \App\View\Composers\AdminSidebarComposer::class);

        // Share cart count with web header
        \Illuminate\Support\Facades\View::composer('layouts.web-app', \App\Http\View\Composers\WebLayoutComposer::class);

        // Register layouts.web-app view as a Blade component
        \Illuminate\Support\Facades\Blade::component('layouts.web-app', 'layouts.web-app');
        \Illuminate\Support\Facades\Blade::component('layouts.guest', 'layouts.guest');
    }
}


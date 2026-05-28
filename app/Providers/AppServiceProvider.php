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

            if (app()->environment('production') && $mode === 'dev') {
                throw new \RuntimeException('CRITICAL: DEV OTP mode cannot be enabled in production environments.');
            }

            return match ($mode) {
                'dev' => $app->make(\App\Services\Otp\Delivery\DevOtpDeliveryService::class),
                'meta_whatsapp' => $app->make(\App\Services\Otp\Delivery\MetaWhatsAppService::class),
                default => throw new \InvalidArgumentException("Unsupported OTP delivery mode: {$mode}"),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') && config('otp.delivery_mode') === 'dev') {
            throw new \RuntimeException('CRITICAL: DEV OTP mode cannot be enabled in production environments.');
        }

        \App\Models\Membership::observe(\App\Observers\MembershipObserver::class);
        
        // Share counts with admin sidebar
        \Illuminate\Support\Facades\View::composer('layouts.admin', \App\View\Composers\AdminSidebarComposer::class);

        // Share cart count with web header
        \Illuminate\Support\Facades\View::composer('layouts.web-app', \App\Http\View\Composers\WebLayoutComposer::class);

        // Register layouts.web-app view as a Blade component
        \Illuminate\Support\Facades\Blade::component('layouts.web-app', 'layouts.web-app');
    }
}


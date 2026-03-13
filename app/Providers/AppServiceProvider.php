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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Membership::observe(\App\Observers\MembershipObserver::class);
        
        // Share counts with admin sidebar
        \Illuminate\Support\Facades\View::composer('layouts.admin', \App\View\Composers\AdminSidebarComposer::class);

        // Share cart count with web header
        \Illuminate\Support\Facades\View::composer('layouts.web-app', \App\Http\View\Composers\WebLayoutComposer::class);
    }
}

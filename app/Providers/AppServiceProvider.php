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
                // CRM SHARED SIDEBAR LEAD COUNT V1 START
        \Illuminate\Support\Facades\View::composer(
            'partials.crm-sidebar',
            function ($view): void {
                $view->with(
                    'totalLeads',
                    \App\Models\Lead::query()->count()
                );
            }
        );
        // CRM SHARED SIDEBAR LEAD COUNT V1 END

//
    }
}

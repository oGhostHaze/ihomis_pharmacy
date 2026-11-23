<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Livewire\LifecycleManager::registerHydrationMiddleware([
            \App\Support\SubmissionNonce::class => \App\Support\SubmissionNonce::class,
        ]);
        \Livewire\LifecycleManager::registerInitialDehydrationMiddleware([
            \App\Support\SubmissionNonce::class => [\App\Support\SubmissionNonce::class, 'dehydrate'],
        ]);
    }
}

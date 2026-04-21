<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\MstInstansi;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Share $instansi with all PDF views
        View::composer(['modules.*-pdf', 'modules.*.*-pdf'], function ($view) {
            $view->with('instansi', MstInstansi::first());
        });
    }
}

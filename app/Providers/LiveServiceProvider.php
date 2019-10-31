<?php

namespace App\Providers;

use App\Http\Controllers\SoccerAPI\SoccerAPIController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Class LiveServiceProvider.
 */
class LiveServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        View::composer('layouts/default', function ($view) {
            $soccerAPIController = new SoccerAPIController();

            $count = $soccerAPIController->countLivescores();

            $view->with('live', $count);
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

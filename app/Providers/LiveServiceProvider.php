<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Http\Controllers\SoccerAPI\SoccerAPIController;

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

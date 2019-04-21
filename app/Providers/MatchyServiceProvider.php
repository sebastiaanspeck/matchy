<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class MatchyServiceProvider
 * @package App\Providers
 */
class MatchyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__.'/../../config';

        $this->mergeConfigFrom($configPath.'/config.php', 'preferences');

        $this->publishes([
            $configPath.'/config.php' => config_path('preferences.php'),
        ], 'config');
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

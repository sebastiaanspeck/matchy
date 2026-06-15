<?php

namespace App\Providers;

use App\Contracts\FootballApiProviderInterface;
use App\Services\FootballApi\ApiFootballProvider;
use App\Services\FootballApi\SportmonksProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->singleton(FootballApiProviderInterface::class, function () {
            return match (config('football-api.provider')) {
                'sportmonks' => new SportmonksProvider,
                default => new ApiFootballProvider,
            };
        });
    }
}

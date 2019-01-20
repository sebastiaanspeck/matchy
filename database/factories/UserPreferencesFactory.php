<?php

use Faker\Generator as Faker;
use Carbon\Carbon;

$factory->define(App\UserPreferences::class, function (Faker $faker) {

    $startYear = Carbon::now()->year;
    $endYear = Carbon::now()->addYear()->year;

    if($endYear != Carbon::now()->year) {
        $startYear -= 1;
        $endYear -= 1;
    }

    $currentSeason = $startYear.'/'.$endYear;

    return [
        'uuid' => Uuid::generate()->string,
        'name' => 'John Doe',
        'current_season' => $currentSeason,
    ];
});

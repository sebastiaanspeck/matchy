<?php

use App\Http\Controllers\PreferencesController;
use App\Http\Controllers\SoccerAPI\SoccerAPIController;
use Illuminate\Support\Facades\Route;
use Jenssegers\Agent\Agent;

Route::get('/', function () {
    $agent = new Agent;

    if ($agent->isDesktop()) {
        $deviceType = 'desktop';
    } elseif ($agent->isPhone()) {
        $deviceType = 'phone';
    } elseif ($agent->isTablet()) {
        $deviceType = 'tablet';
    } elseif ($agent->isRobot()) {
        $deviceType = 'robot';
    } else {
        $deviceType = 'other';
    }

    return view("{$deviceType}/home");
});

// Leagues
Route::get('/leagues', [SoccerAPIController::class, 'allLeagues'])->name('leagues');
Route::get('/leagues/{id}', [SoccerAPIController::class, 'leaguesDetails'])->name('leaguesDetails');

// Livescores
Route::get('/livescores/{type}', [SoccerAPIController::class, 'livescores'])->name('livescores');

// Fixtures
Route::get('/fixtures', [SoccerAPIController::class, 'fixturesByDate'])->name('fixturesByDate');
Route::get('/fixtures/{id}', [SoccerAPIController::class, 'fixturesDetails'])->name('fixturesDetails');

// Teams
Route::get('/teams/{id}', [SoccerAPIController::class, 'teamsDetails'])->name('teamsDetails');

// Favorites
Route::get('/favorite_teams', [SoccerAPIController::class, 'favoriteTeams'])->name('favoriteTeams');
Route::get('/favorite_leagues', [SoccerAPIController::class, 'favoriteLeagues'])->name('favoriteLeagues');

Route::get('/update_favorite_teams/{id}', [PreferencesController::class, 'setFavoriteTeams'])->name('setFavoriteTeams');
Route::get('/update_favorite_leagues/{id}', [PreferencesController::class, 'setFavoriteLeagues'])->name('setFavoriteLeagues');

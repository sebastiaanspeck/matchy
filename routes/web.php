<?php

use Jenssegers\Agent\Agent;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $deviceType = self::getDeviceType();

    return view("{$deviceType}/home");
});

// Leagues
Route::get('/leagues', 'SoccerAPI\SoccerAPIController@allLeagues')->name('leagues');
Route::get('/leagues/{id}', 'SoccerAPI\SoccerAPIController@leaguesDetails')->name('leaguesDetails');

// Livescores
Route::get('/livescores/{type}', 'SoccerAPI\SoccerAPIController@livescores')->name('livescores');

// Fixtures
Route::get('/fixtures', 'SoccerAPI\SoccerAPIController@fixturesByDate')->name('fixturesByDate');
Route::get('/fixtures/{id}', 'SoccerAPI\SoccerAPIController@fixturesDetails')->name('fixturesDetails');

// Teams
Route::get('/teams/{id}', 'SoccerAPI\SoccerAPIController@teamsDetails')->name('teamsDetails');

// Favorites
Route::get('/favorite_teams', 'SoccerAPI\SoccerAPIController@favoriteTeams')->name('favoriteTeams');
Route::get('/favorite_leagues', 'SoccerAPI\SoccerAPIController@favoriteLeagues')->name('favoriteLeagues');

public static function getDeviceType() {
    $agent = new Agent();
    if ($agent->isDesktop()) {
        return "desktop";
    } elseif ($agent->isPhone()) {
        return "phone";
    } elseif ($agent->isTablet()) {
        return "tablet";
    } elseif ($agent->isRobot()) {
        return "robot";
    }
    return "other";    
}    

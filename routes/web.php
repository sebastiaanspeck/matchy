<?php

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
    return view('home');
});

Route::get('/leagues', 'SoccerAPI\SoccerAPIController@viewAllLeagues')->name('leagues');

// Livescores
Route::get('/livescores/{type}', 'SoccerAPI\SoccerAPIController@livescores')->name('livescores');

// Fixtures
Route::get('/fixtures', 'SoccerAPI\SoccerAPIController@fixturesByDate')->name('fixturesByDate');
Route::get('/fixtures/{id}', 'SoccerAPI\SoccerAPIController@fixturesDetails')->name('fixturesDetails');

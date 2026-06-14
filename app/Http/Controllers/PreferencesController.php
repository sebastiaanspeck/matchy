<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Filebase\FilebaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class PreferencesController extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public function setFavoriteTeams($teamId)
    {
        $favorite_teams = $this->getFavoriteTeams();

        if (in_array($teamId, $favorite_teams)) {
            if (($key = array_search($teamId, $favorite_teams)) !== false) {
                unset($favorite_teams[$key]);
            }
        } else {
            $favorite_teams[] = $teamId;
        }

        if (array_key_exists(0, $favorite_teams) && $favorite_teams[0] === '') {
            unset($favorite_teams[0]);
        }

        FilebaseController::setField('favorite_teams', implode(',', $favorite_teams));

        return back();
    }

    public function getFavoriteTeams(): array
    {
        return FilebaseController::getField('favorite_teams');
    }

    public function setFavoriteLeagues($leagueId)
    {
        $favorite_leagues = $this->getFavoriteLeagues();

        if (in_array($leagueId, $favorite_leagues)) {
            if (($key = array_search($leagueId, $favorite_leagues)) !== false) {
                unset($favorite_leagues[$key]);
            }
        } else {
            $favorite_leagues[] = $leagueId;
        }

        if (array_key_exists(0, $favorite_leagues) && $favorite_leagues[0] === '') {
            unset($favorite_leagues[0]);
        }

        FilebaseController::setField('favorite_leagues', implode(',', $favorite_leagues));

        return back();
    }

    public function getFavoriteLeagues(): array
    {
        return FilebaseController::getField('favorite_leagues');
    }
}

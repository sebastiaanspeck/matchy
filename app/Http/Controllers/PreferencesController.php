<?php

namespace App\Http\Controllers;

use Filebase\Filesystem\FilesystemException;
use App\Http\Controllers\Filebase\FilebaseController;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;

/**
 * Class PreferencesController
 */
class PreferencesController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param $teamId
     * @return back
     * @throws FilesystemException
     */
    public function setFavoriteTeams($teamId)
    {
        $favorite_teams = $this->getFavoriteTeams();

        if(in_array($teamId, $favorite_teams)) {
            if (($key = array_search($teamId, $favorite_teams)) !== false) {
                unset($favorite_teams[$key]);
            }
        } else {
            $favorite_teams[] = $teamId;
        }

        if(array_key_exists(0, $favorite_teams)) {
            if($favorite_teams[0] === "") {
                unset($favorite_teams[0]);
            }
        }

        $favorite_teams = implode(',', $favorite_teams);

        FilebaseController::setField('favorite_teams', $favorite_teams);

        return back();
    }

    /**
     * @return array
     * @throws FilesystemException
     */
    public function getFavoriteTeams()
    {
        return FilebaseController::getField('favorite_teams');
    }

    /**
     * @param $leagueId
     * @return back
     * @throws FilesystemException
     */
    public function setFavoriteLeagues($leagueId)
    {
        $favorite_leagues = $this->getFavoriteLeagues();

        if(in_array($leagueId, $favorite_leagues)) {
            if (($key = array_search($leagueId, $favorite_leagues)) !== false) {
                unset($favorite_leagues[$key]);
            }
        } else {
            $favorite_leagues[] = $leagueId;
        }

        if(array_key_exists(0, $favorite_leagues)) {
            if($favorite_leagues[0] === "") {
                unset($favorite_leagues[0]);
            }
        }
        $favorite_leagues = implode(',', $favorite_leagues);

        FilebaseController::setField('favorite_leagues', $favorite_leagues);

        return back();
    }

    /**
     * @return array
     * @throws FilesystemException
     */
    public function getFavoriteLeagues()
    {
        return FilebaseController::getField('favorite_leagues');
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Filebase\FilebaseController;
use Filebase\Filesystem\FilesystemException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;

/**
 * Class PreferencesController.
 */
class PreferencesController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param $teamId
     *
     * @return RedirectResponse
     * @throws FilesystemException
     */
    public function setFavoriteTeams($teamId)
    {
        $favorite_teams = $this->setFavorite($teamId, $this->getFavoriteTeams());

        FilebaseController::setField('favorite_teams', $favorite_teams);

        return back();
    }

    /**
     * @throws FilesystemException
     *
     * @return array
     */
    public function getFavoriteTeams()
    {
        return FilebaseController::getField('favorite_teams');
    }

    /**
     * @param $leagueId
     *
     * @return RedirectResponse
     * @throws FilesystemException
     */
    public function setFavoriteLeagues($leagueId)
    {
        $favorite_leagues = $this->setFavorite($leagueId, $this->getFavoriteLeagues());

        FilebaseController::setField('favorite_leagues', $favorite_leagues);

        return back();
    }

    /**
     * @throws FilesystemException
     *
     * @return array
     */
    public function getFavoriteLeagues()
    {
        return FilebaseController::getField('favorite_leagues');
    }

    public function setFavorite($id, $favorites)
    {
        if (in_array($id, $favorites)) {
            if (($key = array_search($id, $favorites)) !== false) {
                unset($favorites[$key]);
            }
        } else {
            $favorites[] = $id;
        }

        if (array_key_exists(0, $favorites)) {
            if ($favorites[0] === '') {
                unset($favorites[0]);
            }
        }

        return implode(',', $favorites);
    }
}

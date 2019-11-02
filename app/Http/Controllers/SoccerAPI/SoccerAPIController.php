<?php

namespace App\Http\Controllers\SoccerAPI;

use App\Http\Controllers\Filebase\FilebaseController;
use Carbon\Carbon;
use DateTime;
use Exception;
use Filebase\Filesystem\FilesystemException;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;
use Jenssegers\Agent\Agent;
use Lang;
use Log;
use Psr\Http\Message\ResponseInterface;
use Sportmonks\SoccerAPI\Facades\SoccerAPI;

/**
 * Class SoccerAPIController.
 */
class SoccerAPIController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param Request $request
     *
     * @throws FilesystemException
     *
     * @return Factory|View
     */
    public function allLeagues(Request $request)
    {
        $deviceType = self::getDeviceType();

        $leagues = self::makeCall('leagues', 'country,season');

        if (!FilebaseController::getField('show_inactive_leagues')) {
            $currentYear = Carbon::now()->year;
            $season = FilebaseController::getField('season');

            foreach ($leagues as $key => $league) {
                if (!in_array($league->season->data->name, [$season, $currentYear])) {
                    unset($leagues[$key]);
                }
            }
        }

        usort($leagues, function ($item1, $item2) {
            if ($item1->country->data->name == $item2->country->data->name) {
                return $item1->name <=> $item2->name;
            }

            return $item1->country->data->name <=> $item2->country->data->name;
        });

        $paginatedData = self::addPagination($leagues, 20);

        $url = self::removePageParameter($request);

        $paginatedData->setPath($url);

        return view("{$deviceType}/leagues/leagues", ['leagues' => $paginatedData]);
    }

    /**
     * @param $leagueId
     * @param Request $request
     *
     * @return Factory|View
     */
    public function leaguesDetails($leagueId, Request $request)
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $league = self::makeCall('league_by_id', 'country,season', $leagueId)->data;

        $standingsRaw = self::makeCall('standings', 'standings.team', $league->current_season_id);

        $season = self::makeCall('season', 'upcoming.localTeam,upcoming.visitorTeam,upcoming.league,upcoming.stage,upcoming.round,results:order(starting_at|desc),results.localTeam,results.visitorTeam,results.league,results.round,results.stage', $league->current_season_id);

        $teams = self::makeCall('teams_by_season_id', 'country,coach,stats,venue', $league->current_season_id);

        usort($teams, function ($item1, $item2) {
            return $item1->name <=> $item2->name;
        });

        $topscorers = [];

        // check if league has topscorer_goals coverage or is a cup (the is_cup, excludes World Cup, Europa League,
        if ($league->coverage->topscorer_goals) {
            $topscorers = self::makeCall('topscorers', 'goalscorers.player,goalscorers.team', $league->current_season_id, null, null, null, null, false)->goalscorers->data;

            foreach ($topscorers as $key => $topscorer) {
                // remove all topscorers where stage_id is not the current_stage_id (like qualifying rounds before the actual season etc)
                if ($topscorer->stage_id != $league->current_stage_id) {
                    unset($topscorers[$key]);
                }
            }
            $topscorers = self::addPagination($topscorers, 10);
        } else {
            Log::debug('Missing topscorers for: '.$league->name);
        }

        $lastFixtures = [];
        $upcomingFixtures = [];
        $numberOfMatches = 10;

        if (!empty($season)) {
            $numberOfMatches = $request->query('matches', 10);

            $lastFixtures = $season->data->results->data;
            usort($lastFixtures, function ($item1, $item2) {
                if ($item1->league_id == $item2->league_id) {
                    if ($item1->time->starting_at->date_time == $item2->time->starting_at->date_time) {
                        return $item2->time->minute <=> $item1->time->minute;
                    }

                    return $item2->time->starting_at->date_time <=> $item1->time->starting_at->date_time;
                }

                return $item1->league_id <=> $item2->league_id;
            });
            $lastFixtures = self::addPagination($lastFixtures, $numberOfMatches);

            $upcomingFixtures = $season->data->upcoming->data;
            usort($upcomingFixtures, function ($item1, $item2) {
                if ($item1->league_id == $item2->league_id) {
                    if ($item1->time->starting_at->date_time == $item2->time->starting_at->date_time) {
                        return $item2->time->minute <=> $item1->time->minute;
                    }

                    return $item1->time->starting_at->date_time <=> $item2->time->starting_at->date_time;
                }

                return $item1->league_id <=> $item2->league_id;
            });
            $upcomingFixtures = self::addPagination($upcomingFixtures, $numberOfMatches);
        }

        return view("{$deviceType}/leagues/leagues_details", [
            'league'            => $league,
            'standings_raw'     => $standingsRaw,
            'last_fixtures'     => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'number_of_matches' => $numberOfMatches,
            'topscorers'        => $topscorers,
            'date_format'       => $dateFormat,
        ]);
    }

    /**
     * @param $type
     * @param Request $request
     *
     * @return Factory|View|string
     */
    public function livescores($type, Request $request)
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $leagues = '';

        if (!$request->query('leagues') == 'all') {
            $leagues = $request->query('leagues', '');
        }

        switch ($type) {
            case 'today':
                $livescores = self::makeCall('livescores', 'league,localTeam,visitorTeam,round,stage', $leagues);

                usort($livescores, function ($item1, $item2) {
                    if ($item1->league_id == $item2->league_id) {
                        if ($item1->time->starting_at->date_time == $item2->time->starting_at->date_time) {
                            return $item1->id <=> $item2->id;
                        }

                        return $item1->time->starting_at->date_time <=> $item2->time->starting_at->date_time;
                    }

                    return $item1->league_id <=> $item2->league_id;
                });

                return view("{$deviceType}/livescores/livescores_today", ['livescores' => $livescores, 'date_format' => $dateFormat]);
                break;
            case 'now':
                $livescores = self::makeCall('livescores/now', 'league,localTeam,visitorTeam,round,stage', $leagues);

                usort($livescores, function ($item1, $item2) {
                    if ($item1->league_id == $item2->league_id) {
                        if ($item1->time->starting_at->date_time == $item2->time->starting_at->date_time) {
                            return $item2->id <=> $item1->id;
                        }

                        return $item1->time->starting_at->date_time <=> $item2->time->starting_at->date_time;
                    }

                    return $item1->league_id <=> $item2->league_id;
                });

                return view("{$deviceType}/livescores/livescores_now", ['livescores' => $livescores, 'date_format' => $dateFormat]);
                break;
            default:
                return '';
        }
    }

    /**
     * @param Request $request
     *
     * @return Factory|View
     */
    public function fixturesByDate(Request $request)
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $date = self::getDateFromRequest($request);

        $leagues = '';

        if (!$request->query('leagues') == 'all') {
            $leagues = $request->query('leagues', '');
        }

        $fixtures = self::makeCall('fixtures_by_date', 'league,localTeam,visitorTeam,round,stage', $leagues, null, $date);

        usort($fixtures, function ($item1, $item2) {
            if ($item1->league_id == $item2->league_id) {
                if ($item1->time->starting_at->date_time == $item2->time->starting_at->date_time) {
                    return $item1->id <=> $item2->id;
                }

                return $item1->time->starting_at->date_time <=> $item2->time->starting_at->date_time;
            }

            return $item1->league_id <=> $item2->league_id;
        });

        return view("{$deviceType}/fixtures/fixtures_by_date", ['fixtures' => $fixtures, 'date' => $date, 'date_format' => $dateFormat]);
    }

    /**
     * @param $fixtureId
     *
     * @return Factory|View
     */
    public function fixturesDetails($fixtureId)
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $fixture = self::makeCall('fixture_by_id', 'localTeam,visitorTeam,lineup.player,bench.player,sidelined.player,stats,comments,highlights,league,season,referee,events,venue,localCoach,visitorCoach', $fixtureId)->data;

        $h2hFixtures = self::makeCall('h2h', 'localTeam,visitorTeam,league,season,round,stage', null, null, null, $fixture->localteam_id, $fixture->visitorteam_id, false);

        return view("{$deviceType}/fixtures/fixtures_details", ['fixture' => $fixture, 'h2h_fixtures' => $h2hFixtures, 'date_format' => $dateFormat]);
    }

    /**
     * @param $teamId
     * @param Request $request
     *
     * @return Factory|View
     */
    public function teamsDetails($teamId, Request $request)
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $team = self::makeCall('team_by_id', 'squad.player,coach,latest.league,latest.localTeam,latest.visitorTeam,latest.round,latest.stage,upcoming.league,upcoming.localTeam,upcoming.visitorTeam,upcoming.round,upcoming.stage', $teamId)->data;

        isset($team->coach) ? $coach = $team->coach->data : $coach = null;

        $numberOfMatches = $request->query('matches', 10);
        $lastFixtures = self::addPagination($team->latest->data, $numberOfMatches);
        $upcomingFixtures = self::addPagination($team->upcoming->data, $numberOfMatches);

        return view("{$deviceType}/teams/teams_details", [
            'team'              => $team,
            'coach'             => $coach,
            'last_fixtures'     => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'date_format'       => $dateFormat,
        ]);
    }

    /**
     * @param Request $request
     *
     * @throws FilesystemException
     *
     * @return Factory|View
     */
    public function favoriteTeams(Request $request)
    {
        $deviceType = self::getDeviceType();
        $favorite_teams = FilebaseController::getField('favorite_teams');

        if ($favorite_teams[0] === '') {
            return view("{$deviceType}/teams/favorite_teams", ['teams' => []]);
        }

        if (count($favorite_teams) == 1) {
            return redirect()->route('teamsDetails', ['id' => $favorite_teams[0]]);
        }

        $teams = [];

        foreach ($favorite_teams as $teamId) {
            $teams[] = self::makeCall('team_by_id', 'country,coach,venue', $teamId)->data;
        }

        usort($teams, function ($item1, $item2) {
            if ($item1->country->data->name == $item2->country->data->name) {
                return $item1->name <=> $item2->name;
            }

            return $item1->country->data->name <=> $item2->country->data->name;
        });

        $paginatedData = self::addPagination($teams, 20);

        $url = self::removePageParameter($request);

        $paginatedData->setPath($url);

        return view("{$deviceType}/teams/favorite_teams", [
            'teams' => $paginatedData,
        ]);
    }

    /**
     * @param Request $request
     *
     * @throws FilesystemException
     *
     * @return RedirectResponse
     */
    public function favoriteLeagues(Request $request)
    {
        $deviceType = self::getDeviceType();
        $favorite_leagues = FilebaseController::getField('favorite_leagues');

        if ($favorite_leagues[0] === '') {
            return view("{$deviceType}/leagues/favorite_leagues", ['leagues' => []]);
        }

        if (count($favorite_leagues) == 1) {
            return redirect()->route('leaguesDetails', ['id' => $favorite_leagues[0]]);
        }

        $leagues = [];

        foreach ($favorite_leagues as $leagueId) {
            $leagues[] = self::makeCall('league_by_id', 'country,season', $leagueId)->data;
        }

        if (!FilebaseController::getField('show_inactive_leagues')) {
            $currentYear = Carbon::now()->year;
            $season = FilebaseController::getField('season');

            foreach ($leagues as $key => $league) {
                if (!in_array($league->season->data->name, [$season, $currentYear])) {
                    unset($leagues[$key]);
                }
            }
        }

        if (count($leagues) == 1) {
            return redirect()->route('leaguesDetails', ['id' => $leagues[0]->id]);
        }

        usort($leagues, function ($item1, $item2) {
            if ($item1->country->data->name == $item2->country->data->name) {
                return $item1->name <=> $item2->name;
            }

            return $item1->country->data->name <=> $item2->country->data->name;
        });

        $paginatedData = self::addPagination($leagues, 20);

        $url = self::removePageParameter($request);

        $paginatedData->setPath($url);

        return view("{$deviceType}/leagues/favorite_leagues", ['leagues' => $paginatedData]);
    }

    /**
     * @return int
     */
    public function countLivescores()
    {
        $livescores = self::makeCall('livescores/now', null, null, null, null, null, null, false);

        $count = 0;

        if (count($livescores) >= 1) {
            foreach ($livescores as $livescore) {
                if (!in_array($livescore->time->status, ['NS', 'FT', 'FT_PEN', 'CANCL', 'POSTP', 'INT', 'ABAN', 'SUSP', 'AWARDED', 'DELAYED', 'TBA', 'WO', 'AU'])) {
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * @param $data
     * @param $perPage
     *
     * @return LengthAwarePaginator
     */
    public function addPagination($data, $perPage)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $dataCollection = collect($data);

        $currentPageData = $dataCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

        $paginatedData = new LengthAwarePaginator($currentPageData, count($dataCollection), $perPage);

        return $paginatedData;
    }

    /**
     * @param Request $request
     *
     * @return mixed|string
     */
    public function removePageParameter(Request $request)
    {
        // set url path for generated links
        $url = parse_url($request->fullUrl());

        if (isset($url['query'])) {
            parse_str($url['query'], $get);
            unset($get['page']);

            $url['query'] = http_build_query($get);
        }

        $url = http_build_url($url);

        return $url;
    }

    /**
     * @param $date
     * @param string $format
     *
     * @throws Exception
     *
     * @return bool
     */
    public function validateDate($date, $format = 'Y-m-d')
    {
        $dateTime = new DateTime();

        $validDate = $dateTime::createFromFormat($format, $date);

        return $validDate && $validDate->format($format) === $date;
    }

    /**
     * @param $transType
     * @param $transString
     *
     * @return array|Translator|null|string
     */
    public static function translateString($transType, $transString)
    {
        $logString = $transType.'.'.$transString;
        if (Lang::has($logString) && trans($logString) !== '') {
            if (trans($logString) == trans($logString, [], 'en') && app()->getLocale() !== 'en') {
                switch ($transType) {
                    case 'application':
                        Log::alert($transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php is the same as the one in en/'.$transType.'.php');
                        break;
                    case 'countries':
                        Log::critical($transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php is the same as the one in en/'.$transType.'.php');
                        break;
                    case 'injuries':
                        Log::notice($transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php is the same as the one in en/'.$transType.'.php');
                        break;
                }
            }

            return trans($logString);
        }

        $logString = 'Missing '.$transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php';
        switch ($transType) {
            case 'application':
                Log::alert($logString);
                break;
            case 'countries':
                Log::critical($logString);
                break;
            case 'cup_stages':
                Log::warning($logString);
                break;
            case 'injuries':
                Log::notice($logString);
                break;
            case 'leagues':
                Log::info($logString);
                break;
            case 'statistics':
                Log::debug($logString);
                break;
            default:
                Log::error('Missing error-level for: '.$transString);
                break;
        }

        return $transString;
    }

    /**
     * @param $country
     *
     * @return string
     */
    public static function getCountryFlag($country)
    {
        if (strpos($country, ' ') !== false) {
            $country = str_replace(' ', '-', $country);
        }

        switch ($country) {
            case null:
                $country = 'Unknown';
                break;
            case 'Northern-Ireland':
                $country = 'United-Kingdom';
                break;
        }

        if (!file_exists('images/flags/shiny/16/'.$country.'.png')) {
            Log::emergency('Missing flag for: '.$country);
            $country = 'Unknown';
        }

        return $country;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        switch (config('app.locale')) {
            case 'nl':
                $dateFormat = 'd-m-Y';
                break;
            case 'en':
                $dateFormat = 'Y-m-d';
                break;
            default:
                $dateFormat = 'Y-m-d';
                break;
        }

        return $dateFormat;
    }

    /**
     * @param Request $request
     *
     * @return array|null|string
     */
    public function getDateFromRequest(Request $request)
    {
        if ($request->has('day')) {
            if ($request->query('day') == 'yesterday') {
                return Carbon::now()->subDays(1)->toDateString();
            } elseif ($request->query('day') == 'tomorrow') {
                return Carbon::now()->addDays(1)->toDateString();
            } elseif ($request->query('day') == 'today') {
                return Carbon::now()->toDateString();
            }
        } elseif ($request->has('date')) {
            return $request->query('date');
        }

        return Carbon::now()->toDateString();
    }

    /**
     * @param string      $type
     * @param string|null $include
     * @param string|null $id
     * @param string|null $leagues
     * @param string|null $date
     * @param string|null $localteam_id
     * @param string|null $visitorteam_id
     * @param bool        $abort
     *
     * @return Exception|false|ClientException|mixed|ResponseInterface|string
     */
    public function makeCall(string $type, string $include = null, string $id = null, string $leagues = null, string $date = null, string $localteam_id = null, string $visitorteam_id = null, bool $abort = true)
    {
        switch ($type) {
            case 'livescores':
                $response = SoccerAPI::livescores()->setInclude($include)->setLeagues($leagues)->today();
                break;
            case 'livescores/now':
                $response = SoccerAPI::livescores()->setInclude($include)->setLeagues($leagues)->now();
                break;
            case 'leagues':
                $response = SoccerAPI::leagues()->setInclude($include)->all();
                break;
            case 'league_by_id':
                $response = SoccerAPI::leagues()->setInclude($include)->byId($id);
                break;
            case 'standings':
                $response = SoccerAPI::standings()->setInclude($include)->bySeasonId($id);
                break;
            case 'season':
                $response = SoccerAPI::seasons()->setInclude($include)->byId($id);
                break;
            case 'topscorers':
                $response = SoccerAPI::topscorers()->setInclude($include)->bySeasonId($id);
                break;
            case 'fixtures_by_date':
                $response = SoccerAPI::fixtures()->setInclude($include)->setLeagues($leagues)->byDate($date);
                break;
            case 'fixture_by_id':
                $response = SoccerAPI::fixtures()->setInclude($include)->byFixtureId($id);
                break;
            case 'h2h':
                $response = SoccerAPI::head2head()->setInclude($include)->betweenTeams($localteam_id, $visitorteam_id);
                break;
            case 'team_by_id':
                $response = SoccerAPI::teams()->setInclude($include)->byId($id);
                break;
            case 'teams_by_season_id':
                $response = SoccerAPI::teams()->setInclude($include)->allBySeasonId($id);
                break;
            default:
                $response = [];
                break;
        }

        if (isset($response->error_code)) {
            if ($abort) {
                abort($response->error_code, $response->error_message);
            }

            return [];
        }

        return $response;
    }

    /**
     * @param $file
     * @param $height
     * @param $width
     *
     * @return string
     */
    public static function getTeamLogo($file, $height, $width)
    {
        if ($file !== null) {
            $file = preg_replace("/cdn\.sportmonks/", 'sportmonks.gumlet', $file)."?height={$height}&width={$width}";
            $headers = get_headers($file);
            stripos($headers[0], '200 OK') ? $team_logo = $file : $team_logo = '/images/team_logos/16/Unknown.png';

            return $team_logo;
        }

        return '/images/team_logos/16/Unknown.png';
    }

    /**
     * @return string
     */
    public static function getDeviceType()
    {
        $agent = new Agent();

        if ($agent->isDesktop()) {
            return 'desktop';
        } elseif ($agent->isPhone()) {
            return 'phone';
        } elseif ($agent->isTablet()) {
            return 'tablet';
        } elseif ($agent->isRobot()) {
            return 'robot';
        }

        return 'other';

        // return $agent->deviceType(); // WHILE deviceType ISN'T IMPLEMENTED IN AGENT, WE USE THE CODE ABOVE
    }
}

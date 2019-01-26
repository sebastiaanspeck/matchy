<?php

namespace App\Http\Controllers\SoccerAPI;

use Carbon\Carbon;
use DateTime;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Log;
use Sportmonks\SoccerAPI\Facades\SoccerAPI;

/**
 * Class SoccerAPIController.
 */
class SoccerAPIController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param \Illuminate\Http\Request $request
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function allLeagues(Request $request)
    {
        $leagues = self::makeCall('leagues', 'country,season');

        if (!config('preferences.show_inactive_leagues')) {
            $currentYear = Carbon::now()->year;
            $season = config('preferences.season');

            foreach ($leagues as $key => $league) {
                if (!in_array($league->season->data->name, [$season, $currentYear])) {
                    unset($leagues[$key]);
                }
            }
        }

        usort($leagues, function ($item1, $item2) {
            if ($item1->country->data->name == $item2->country->data->name) {
                return $item1->id <=> $item2->id;
            }

            return $item1->country->data->name <=> $item2->country->data->name;
        });

        $paginatedData = self::addPagination($leagues, 20);

        $url = self::removePageParameter($request);

        $paginatedData->setPath($url);

        return view('leagues/leagues', ['leagues' => $paginatedData]);
    }

    /**
     * @param $leagueId
     * @param \Illuminate\Http\Request $request
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function leaguesDetails($leagueId, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $league = self::makeCall('league_by_id', 'country,season', $leagueId)->data;

        $standingsRaw = self::makeCall('standings', 'standings.team', $league->current_season_id);

        $season = self::makeCall('season', 'upcoming.localTeam,upcoming.visitorTeam,upcoming.league,upcoming.stage,upcoming.round,results:order(starting_at|desc),results.localTeam,results.visitorTeam,results.league,results.round,results.stage', $league->current_season_id);

        $teams = self::makeCall('teams_by_season_id', 'country,coach,stats,venue', $league->current_season_id);

        usort($teams, function ($item1, $item2) {
            if ($item1->country->data->name == $item2->country->data->name) {
                return $item1->name <=> $item2->name;
            }

            return $item1->country->data->name <=> $item2->country->data->name;
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

        return view('leagues/leagues_details', [
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
     * @param \Illuminate\Http\Request $request
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function livescores($type, Request $request)
    {
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
                            return $item2->time->minute <=> $item1->time->minute;
                        }

                        return $item1->time->starting_at->date_time <=> $item2->time->starting_at->date_time;
                    }

                    return $item1->league_id <=> $item2->league_id;
                });

                return view('livescores/livescores_today', ['livescores' => $livescores, 'date_format' => $dateFormat]);
                break;
            case 'now':
                $livescores = self::makeCall('livescores/now', 'league,localTeam,visitorTeam,round,stage', $leagues);

                usort($livescores, function ($item1, $item2) {
                    if ($item1->league_id == $item2->league_id) {
                        if ($item1->time->starting_at->date_time == $item2->time->starting_at->date_time) {
                            return $item2->time->minute <=> $item1->time->minute;
                        }

                        return $item1->time->starting_at->date_time <=> $item2->time->starting_at->date_time;
                    }

                    return $item1->league_id <=> $item2->league_id;
                });

                return view('livescores/livescores_now', ['livescores' => $livescores, 'date_format' => $dateFormat]);
                break;
            default:
                return '';
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function fixturesByDate(Request $request)
    {
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

        return view('fixtures/fixtures_by_date', ['fixtures' => $fixtures, 'date' => $date, 'date_format' => $dateFormat]);
    }

    /**
     * @param $fixtureId
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function fixturesDetails($fixtureId)
    {
        $dateFormat = self::getDateFormat();

        $fixture = self::makeCall('fixture_by_id', 'localTeam,visitorTeam,lineup.player,bench.player,sidelined.player,stats,comments,highlights,league,season,referee,events,venue,localCoach,visitorCoach', $fixtureId)->data;

        $h2hFixtures = self::makeCall('h2h', 'localTeam,visitorTeam,league,season,round,stage', null, null, null, $fixture->localteam_id, $fixture->visitorteam_id);

        return view('fixtures/fixtures_details', ['fixture' => $fixture, 'h2h_fixtures' => $h2hFixtures, 'date_format' => $dateFormat]);
    }

    /**
     * @param $teamId
     * @param \Illuminate\Http\Request $request
     *
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function teamsDetails($teamId, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $team = self::makeCall('team_by_id', 'squad.player,coach,latest.league,latest.localTeam,latest.visitorTeam,latest.round,latest.stage,upcoming.league,upcoming.localTeam,upcoming.visitorTeam,upcoming.round,upcoming.stage', $teamId)->data;

        $coach = $team->coach->data;

        $numberOfMatches = $request->query('matches', 10);
        $lastFixtures = self::addPagination($team->latest->data, $numberOfMatches);
        $upcomingFixtures = self::addPagination($team->upcoming->data, $numberOfMatches);

        return view('teams/teams_details', [
            'team'              => $team,
            'coach'             => $coach,
            'last_fixtures'     => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'date_format'       => $dateFormat,
        ]);
    }

    /**
     *
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
     * @return \Illuminate\Pagination\LengthAwarePaginator
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
     * @param \Illuminate\Http\Request $request
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
     * @throws \Exception
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
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    public static function translateString($transType, $transString)
    {
        $logString = $transType.'.'.$transString;
        if (\Lang::has($logString) && trans($logString) !== '') {
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

        switch ($transType) {
            case 'application':
                Log::alert('Missing '.$transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php');
                break;
            case 'countries':
                Log::critical('Missing '.$transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php');
                break;
            case 'cup_stages':
                Log::warning('Missing '.$transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php');
                break;
            case 'injuries':
                Log::notice('Missing '.$transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php');
                break;
            case 'leagues':
                Log::info('Missing '.$transType.' translation for: '.$transString.' in '.app()->getLocale().'/'.$transType.'.php');
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
     * @param \Illuminate\Http\Request $request
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
     *
     * @return \Exception|false|ClientException|mixed|\Psr\Http\Message\ResponseInterface|string
     */
    public function makeCall(string $type, string $include = null, string $id = null, string $leagues = null, string $date = null, string $localteam_id = null, string $visitorteam_id = null, bool $abort = true)
    {
        switch ($type) {
            case 'livescores':
                $response = SoccerAPI::livescores()->setInclude($include)->setLeagues($leagues)->today();
                break;
            case 'livescores/now':
                if ($include && $leagues) {
                    $response = SoccerAPI::livescores()->setInclude($include)->setLeagues($leagues)->now();
                    break;
                }
                $response = SoccerAPI::livescores()->now();
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
}

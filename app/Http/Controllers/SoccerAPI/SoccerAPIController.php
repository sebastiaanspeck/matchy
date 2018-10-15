<?php
/**
 * Created by PhpStorm.
 * User: sebastiaanspeck
 * Date: 24/09/2018
 * Time: 10:21
 */

namespace App\Http\Controllers\SoccerAPI;

use Illuminate\Routing\Controller as BaseController;
use Sportmonks\SoccerAPI\SoccerAPI;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use DateTime;

class SoccerAPIController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function allLeagues(Request $request)
    {
        $soccerAPI = new SoccerAPI();
        $include = 'country,season';

        $leagues = $soccerAPI->leagues()->setInclude($include)->all();

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function leaguesDetails($leagueId, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $includeLeague = 'country,season';
        $includeSeason = 'upcoming.localTeam,upcoming.visitorTeam,upcoming.league,results:order(starting_at|desc),results.localTeam,results.visitorTeam,results.league';
        $includeTopscorers = 'goalscorers.player,goalscorers.team';

        /* $includeTopscorersAggregated = 'aggregatedGoalscorers.player,aggregatedGoalscorers.team'; */

        $league = $soccerAPI->leagues()->setInclude($includeLeague)->byId($leagueId)->data;

        $excludedLeagues = [214, 1371, 24, 2, 5, 720, 1325, 1326, 307, 109, 390];

        $standingsRaw = $soccerAPI->standings()->bySeasonId($league->current_season_id);

        $season = $soccerAPI->seasons()->setInclude($includeSeason)->byId($league->current_season_id);

        $topscorers = [];

        if (!in_array($leagueId, $excludedLeagues)) {
            $topscorersDefault = $soccerAPI->topscorers()->setInclude($includeTopscorers)->bySeasonId($league->current_season_id)->goalscorers->data;

            /* cups don't work yet -> try: check with current_stage_id */
            if (count($topscorersDefault) > 0) {
                $topscorers = self::addPagination($topscorersDefault, 10);
                /* $topscorersAggregated = $soccerAPI->topscorers()->setInclude($includeTopscorersAggregated)->aggregatedBySeasonId($league->current_season_id)->aggregatedGoalscorers->data;
                 if(count($topscorersAggregated) > 0) {
                     $topscorers = $topscorersAggregated;
                 } else {
                     $topscorers = array();
                 }*/
            }
        }

        if (count($season) > 0) {
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
        } else {
            $lastFixtures = [];
            $upcomingFixtures = [];
            $numberOfMatches = 10;
        }

        return view('leagues/leagues_details', [
            'league' => $league,
            'standings_raw' => $standingsRaw,
            'last_fixtures' => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'number_of_matches' => $numberOfMatches,
            'topscorers' => $topscorers,
            'date_format' => $dateFormat
        ]);
    }

    /**
     * @param $type
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    function livescores($type, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'league,localTeam,visitorTeam';

        if ($request->query('leagues') == 'all') {
            $leagues = '';
        } else {
            $leagues = $request->query('leagues', '');
        }

        switch($type) {
            case('today'):
                $livescores = $soccerAPI->livescores()->setInclude($include)->setLeagues($leagues)->today();

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
            case('now'):
                $livescores = $soccerAPI->livescores()->setInclude($include)->setLeagues($leagues)->now();

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
     * @return int
     */
    function countLivescores()
    {
        $soccerAPI = new SoccerAPI();
        $livescores = $soccerAPI->livescores()->setInclude('league,localTeam,visitorTeam')->now();

        $count = 0;

        if (count($livescores) >= 1) {
            foreach ($livescores as $livescore) {
                if (in_array($livescore->time->status, ['NS', 'FT', 'FT_PEN', 'CANCL', 'POSTP', 'INT', 'ABAN', 'SUSP', 'AWARDED','DELAYED','TBA', 'WO', 'AU'])) {
                    continue;
                } else {
                    $count++;
                }
            }
        }

        return ($count);
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function fixturesByDate(Request $request)
    {
        $dateFormat = self::getDateFormat();

        $date = self::getDateFromRequest($request);

        if ($request->query('leagues') == 'all') {
            $leagues = '';
        } else {
            $leagues = $request->query('leagues', '');
        }
        $soccerAPI = new SoccerAPI();
        $include = 'league,localTeam,visitorTeam';

        /** @var Carbon $date */
        $fixtures = $soccerAPI->fixtures()->setInclude($include)->setLeagues($leagues)->byDate($date);

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function fixturesDetails($fixtureId)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'localTeam,visitorTeam,lineup.player,bench.player,sidelined.player,stats,comments,highlights,league,season,referee,events,venue,localCoach,visitorCoach';

        $fixture = $soccerAPI->fixtures()->setInclude($include)->byMatchId($fixtureId)->data;

        return view('fixtures/fixtures_details', ['fixture' => $fixture, 'date_format' => $dateFormat]);
    }

    /**
     * @param $teamId
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function teamsDetails($teamId, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'squad,coach,latest.league,latest.localTeam,latest.visitorTeam,upcoming.league,upcoming.localTeam,upcoming.visitorTeam';

        $team = $soccerAPI->teams()->setInclude($include)->byId($teamId)->data;

        $numberOfMatches = $request->query('matches', 10);
        $lastFixtures = self::addPagination($team->latest->data, $numberOfMatches);
        $upcomingFixtures = self::addPagination($team->upcoming->data, $numberOfMatches);

        return view('teams/teams_details', [
            'team' => $team,
            'last_fixtures' => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'date_format' => $dateFormat
        ]);
    }

    /**
     * @param $data
     * @param $perPage
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    function addPagination($data, $perPage)
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $dataCollection = collect($data);

        $currentPageData = $dataCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

        $paginatedData = new LengthAwarePaginator($currentPageData, count($dataCollection), $perPage);

        return $paginatedData;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed|string
     */
    function removePageParameter(Request $request)
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
     * @return bool
     */
    function validateDate($date, $format = 'Y-m-d')
    {
        $dateTime = new DateTime();

        $d = $dateTime::createFromFormat($format, $date);

        return $d && $d->format($format) === $date;
    }

    /**
     * @param $country
     * @return string
     */
    public static function getCountryFlag($country)
    {

        if (strpos($country, ' ') !== false) {
            $country = str_replace(' ', '-', $country);
        }

        switch ($country) {
            case('World'):
                $country = 'Unknown';
                break;
            case(null):
                $country = 'Unknown';
                break;
            case('Northern-Ireland'):
                $country = 'United-Kingdom';
                break;
        }

        if(!file_exists('images/flags/shiny/16/' . $country . '.png')) {
            error_log('Missing flag for: ' . $country);
            $country = 'Unknown';
        }

        return $country;
    }

    function getDateFormat()
    {
        switch(config('app.locale')) {
            case('nl'):
                $dateFormat = 'd-m-Y';
                break;
            case('en'):
                $dateFormat = 'Y-m-d';
                break;
            default:
                $dateFormat = 'Y-m-d';
                break;
        }

        return $dateFormat;

    }

    function getDateFromRequest(Request $request)
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
        } else {
            return Carbon::now()->toDateString();
        }
        
        return '';
    }
}

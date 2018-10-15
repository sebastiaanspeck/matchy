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

        usort($leagues, function ($a, $b) {
            if ($a->country->data->name == $b->country->data->name) {
                return $a->id <=> $b->id;
            }

            return $a->country->data->name <=> $b->country->data->name;
        });

        $paginated_data = self::addPagination($leagues, 20);

        $url = self::removePageParameter($request);

        $paginated_data->setPath($url);

        return view('leagues/leagues', ['leagues' => $paginated_data]);
    }

    /**
     * @param $league_id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function leaguesDetails($league_id, Request $request)
    {
        $date_format = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include_league = 'country,season';
        $include_season = 'upcoming.localTeam,upcoming.visitorTeam,upcoming.league,results:order(starting_at|desc),results.localTeam,results.visitorTeam,results.league';
        $include_topscorers = 'goalscorers.player,goalscorers.team';

        /* $include_topscorers_aggregated = 'aggregatedGoalscorers.player,aggregatedGoalscorers.team'; */

        $league = $soccerAPI->leagues()->setInclude($include_league)->byId($league_id)->data;

        $excluded_leagues = [214, 1371, 24, 2, 5, 720, 1325, 1326, 307, 109, 390];

        $standings_raw = $soccerAPI->standings()->bySeasonId($league->current_season_id);

        $season = $soccerAPI->seasons()->setInclude($include_season)->byId($league->current_season_id);

        $topscorers = [];

        if (!in_array($league_id, $excluded_leagues)) {
            $topscorers_default = $soccerAPI->topscorers()->setInclude($include_topscorers)->bySeasonId($league->current_season_id)->goalscorers->data;

            /* cups don't work yet -> try: check with current_stage_id */
            if (count($topscorers_default) > 0) {
                $topscorers = self::addPagination($topscorers_default, 10);
                /* $topscorers_aggregated = $soccerAPI->topscorers()->setInclude($include_topscorers_aggregated)->aggregatedBySeasonId($league->current_season_id)->aggregatedGoalscorers->data;
                 if(count($topscorers_aggregated) > 0) {
                     $topscorers = $topscorers_aggregated;
                 } else {
                     $topscorers = array();
                 }*/
            }
        }

        if (count($season) > 0) {
            $number_of_matches = $request->query('matches', 10);

            $last_fixtures = $season->data->results->data;
            usort($last_fixtures, function ($a, $b) {
                if ($a->league_id == $b->league_id) {
                    if ($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
                        return $b->time->minute <=> $a->time->minute;
                    }

                    return $b->time->starting_at->date_time <=> $a->time->starting_at->date_time;
                }

                return $a->league_id <=> $b->league_id;
            });
            $last_fixtures = self::addPagination($last_fixtures, $number_of_matches);


            $upcoming_fixtures = $season->data->upcoming->data;
            usort($upcoming_fixtures, function ($a, $b) {
                if ($a->league_id == $b->league_id) {
                    if ($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
                        return $b->time->minute <=> $a->time->minute;
                    }

                    return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
                }

                return $a->league_id <=> $b->league_id;
            });
            $upcoming_fixtures = self::addPagination($upcoming_fixtures, $number_of_matches);
        } else {
            $last_fixtures = [];
            $upcoming_fixtures = [];
            $number_of_matches = 10;
        }

        return view('leagues/leagues_details', [
            'league' => $league,
            'standings_raw' => $standings_raw,
            'last_fixtures' => $last_fixtures,
            'upcoming_fixtures' => $upcoming_fixtures,
            'number_of_matches' => $number_of_matches,
            'topscorers' => $topscorers,
            'date_format' => $date_format
        ]);
    }

    /**
     * @param $type
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    function livescores($type, Request $request)
    {
        $date_format = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'league,localTeam,visitorTeam';

        switch($type) {
            case('today'):
                $livescores = $soccerAPI->livescores()->setInclude($include)->today();

                usort($livescores, function ($a, $b) {
                    if ($a->league_id == $b->league_id) {
                        if ($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
                            return $b->time->minute <=> $a->time->minute;
                        }

                        return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
                    }

                    return $a->league_id <=> $b->league_id;
                });

                return view('livescores/livescores_today', ['livescores' => $livescores, 'date_format' => $date_format]);
                break;
            case('now'):
                $livescores = $soccerAPI->livescores()->setInclude($include)->now();

                usort($livescores, function ($a, $b) {
                    if ($a->league_id == $b->league_id) {
                        if ($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
                            return $b->time->minute <=> $a->time->minute;
                        }

                        return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
                    }

                    return $a->league_id <=> $b->league_id;
                });

                return view('livescores/livescores_now', ['livescores' => $livescores, 'date_format' => $date_format]);
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
        $date_format = self::getDateFormat();

        $date = self::getDateFromRequest($request);

        $soccerAPI = new SoccerAPI();
        $include = 'league,localTeam,visitorTeam';

        /** @var Carbon $date */
        $fixtures = $soccerAPI->fixtures()->setInclude($include)->byDate($date);

        usort($fixtures, function ($a, $b) {
            if ($a->league_id == $b->league_id) {
                if ($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
                    return $a->id <=> $b->id;
                }

                return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
            }

            return $a->league_id <=> $b->league_id;
        });

        return view('fixtures/fixtures_by_date', ['fixtures' => $fixtures, 'date' => $date, 'date_format' => $date_format]);
    }

    /**
     * @param $fixture_id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function fixturesDetails($fixture_id)
    {
        $date_format = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'localTeam,visitorTeam,lineup.player,bench.player,sidelined.player,stats,comments,highlights,league,season,referee,events,venue,localCoach,visitorCoach';

        $fixture = $soccerAPI->fixtures()->setInclude($include)->byMatchId($fixture_id)->data;

        return view('fixtures/fixtures_details', ['fixture' => $fixture, 'date_format' => $date_format]);
    }

    /**
     * @param $team_id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function teamsDetails($team_id, Request $request)
    {
        $date_format = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'squad,coach,latest.league,latest.localTeam,latest.visitorTeam,upcoming.league,upcoming.localTeam,upcoming.visitorTeam';

        $team = $soccerAPI->teams()->setInclude($include)->byId($team_id)->data;

        $number_of_matches = $request->query('matches', 10);
        $last_fixtures = self::addPagination($team->latest->data, $number_of_matches);
        $upcoming_fixtures = self::addPagination($team->upcoming->data, $number_of_matches);

        return view('teams/teams_details', [
            'team' => $team,
            'last_fixtures' => $last_fixtures,
            'upcoming_fixtures' => $upcoming_fixtures,
            'date_format' => $date_format
        ]);
    }

    /**
     * @param $data
     * @param $per_page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    function addPagination($data, $per_page)
    {
        $current_page = LengthAwarePaginator::resolveCurrentPage();

        $data_collection = collect($data);

        $current_page_data = $data_collection->slice(($current_page * $per_page) - $per_page, $per_page)->all();

        $paginated_data = new LengthAwarePaginator($current_page_data, count($data_collection), $per_page);

        return $paginated_data;
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
        $d = DateTime::createFromFormat($format, $date);

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
                $date_format = 'd-m-Y';
                break;
            case('en'):
                $date_format = 'Y-m-d';
                break;
            default:
                $date_format = 'Y-m-d';
                break;
        }

        return $date_format;

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

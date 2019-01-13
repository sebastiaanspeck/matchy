<?php

namespace App\Http\Controllers\SoccerAPI;

use Carbon\Carbon;
use DateTime;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Log;
use Sportmonks\SoccerAPI\SoccerAPI;

class SoccerAPIController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function allLeagues(Request $request)
    {
        $soccerAPI = new SoccerAPI();
        $include = 'country,season';

        $leagues = $soccerAPI->leagues()->setInclude($include)->all();

        $currentYear = Carbon::now()->year;
        $season = env('SEASON', $currentYear . '/' . Carbon::now()->addYear()->year);

        foreach ($leagues as $key => $league) {
            if (!in_array($league->season->data->name, [$season, $currentYear])) {
                unset($leagues[$key]);
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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function leaguesDetails($leagueId, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $includeLeague = 'country,season';
        $includeStandings = 'standings.team';
        $includeSeason = 'upcoming.localTeam,upcoming.visitorTeam,upcoming.league,upcoming.stage,upcoming.round,results:order(starting_at|desc),results.localTeam,results.visitorTeam,results.league,results.round,results.stage';
        $includeTopscorers = 'goalscorers.player,goalscorers.team';

        $league = $soccerAPI->leagues()->setInclude($includeLeague)->byId($leagueId)->data;

        $standingsRaw = $soccerAPI->standings()->setInclude($includeStandings)->bySeasonId($league->current_season_id);

        $season = $soccerAPI->seasons()->setInclude($includeSeason)->byId($league->current_season_id);

        $topscorers = [];

        // check if league has topscorer_goals coverage or is a cup (the is_cup, excludes World Cup, Europa League,
        if ($league->coverage->topscorer_goals) {
            $topscorers = $soccerAPI->topscorers()->setInclude($includeTopscorers)->bySeasonId($league->current_season_id)->goalscorers->data;

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
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
    public function livescores($type, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'league,localTeam,visitorTeam,round,stage';

        $leagues = '';

        if (!$request->query('leagues') == 'all') {
            $leagues = $request->query('leagues', '');
        }

        switch ($type) {
            case 'today':
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
            case 'now':
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
     * @param \Illuminate\Http\Request $request
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

        $soccerAPI = new SoccerAPI();
        $include = 'league,localTeam,visitorTeam,round,stage';

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
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function fixturesDetails($fixtureId)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $includeFixture = 'localTeam,visitorTeam,lineup.player,bench.player,sidelined.player,stats,comments,highlights,league,season,referee,events,venue,localCoach,visitorCoach';
        $includeHead2Head = 'localTeam,visitorTeam,league,season,round,stage';

        $fixture = $soccerAPI->fixtures()->setInclude($includeFixture)->byMatchId($fixtureId)->data;

        $h2hFixtures = $soccerAPI->head2head()->setInclude($includeHead2Head)->betweenTeams($fixture->localteam_id, $fixture->visitorteam_id);

        return view('fixtures/fixtures_details', ['fixture' => $fixture, 'h2h_fixtures' => $h2hFixtures, 'date_format' => $dateFormat]);
    }

    /**
     * @param $teamId
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function teamsDetails($teamId, Request $request)
    {
        $dateFormat = self::getDateFormat();

        $soccerAPI = new SoccerAPI();
        $include = 'squad,coach,latest.league,latest.localTeam,latest.visitorTeam,latest.round,latest.stage,upcoming.league,upcoming.localTeam,upcoming.visitorTeam,upcoming.round,upcoming.stage';

        $team = $soccerAPI->teams()->setInclude($include)->byId($teamId)->data;

        $numberOfMatches = $request->query('matches', 10);
        $lastFixtures = self::addPagination($team->latest->data, $numberOfMatches);
        $upcomingFixtures = self::addPagination($team->upcoming->data, $numberOfMatches);

        return view('teams/teams_details', [
            'team'              => $team,
            'last_fixtures'     => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'date_format'       => $dateFormat,
        ]);
    }

    /**
     * @return int
     */
    public function countLivescores()
    {
        $soccerAPI = new SoccerAPI();
        $livescores = $soccerAPI->livescores()->now();

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
     * @return bool
     * @throws \Exception
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
}

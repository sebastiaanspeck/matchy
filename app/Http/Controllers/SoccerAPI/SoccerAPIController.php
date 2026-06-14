<?php

namespace App\Http\Controllers\SoccerAPI;

use App\Http\Controllers\Filebase\FilebaseController;
use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Jenssegers\Agent\Agent;
use PyaeSoneAung\SportmonksFootballApi\Facades\SportmonksFootballApi;

class SoccerAPIController extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public function allLeagues(Request $request): Factory|View
    {
        $deviceType = self::getDeviceType();

        $leagues = self::makeCall('leagues', 'country;currentSeason');

        if (! FilebaseController::getField('show_inactive_leagues')) {
            $currentYear = Carbon::now()->year;
            $season = FilebaseController::getField('season');

            foreach ($leagues as $key => $league) {
                if (! in_array($league->currentSeason->data->name ?? '', [$season, $currentYear])) {
                    unset($leagues[$key]);
                }
            }
        }

        usort($leagues, function ($a, $b) {
            if ($a->country->data->name === $b->country->data->name) {
                return $a->name <=> $b->name;
            }

            return $a->country->data->name <=> $b->country->data->name;
        });

        $paginatedData = self::addPagination($leagues, 20);
        $paginatedData->setPath(self::removePageParameter($request));

        return view("{$deviceType}/leagues/leagues", ['leagues' => $paginatedData]);
    }

    public function leaguesDetails(int $leagueId, Request $request): Factory|View
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $league = self::makeCall('league_by_id', 'country;currentSeason', $leagueId);

        log::info('League details for: '.$league->current_season_id);

        $standingsRaw = self::groupStandings(
            self::makeCall('standings', 'participant;details.type;group;stage', $league->current_season_id ?? 0)
        );
        $lastFixtures = [];
        $upcomingFixtures = [];
        $numberOfMatches = $request->query('matches', 10);
        $topscorers = [];

        if (! empty($league->current_season_id)) {
            $allFixtures = self::fetchSeasonFixtures($league->current_season_id);
            $finishedStatuses = ['FT', 'AET', 'FT_PEN', 'ABAN', 'AWARDED'];

            $results = array_values(array_filter($allFixtures, fn ($f) => in_array($f->time->status ?? '', $finishedStatuses)));
            $upcoming = array_values(array_filter($allFixtures, fn ($f) => ! in_array($f->time->status ?? '', $finishedStatuses)));

            usort($results, fn ($a, $b) => $b->time->starting_at->date_time <=> $a->time->starting_at->date_time);
            usort($upcoming, fn ($a, $b) => $a->time->starting_at->date_time <=> $b->time->starting_at->date_time);

            $lastFixtures = self::addPagination($results, $numberOfMatches);
            $upcomingFixtures = self::addPagination($upcoming, $numberOfMatches);

            if (! empty($league->current_stage_id)) {
                $topscorersRaw = self::makeCall('topscorers', 'player;team', $league->current_season_id, null, null, null, false);

                foreach ($topscorersRaw as $key => $ts) {
                    if (($ts->stage_id ?? null) != $league->current_stage_id) {
                        unset($topscorersRaw[$key]);
                    }
                }

                $topscorers = self::addPagination($topscorersRaw, 10);
            }
        }

        return view("{$deviceType}/leagues/leagues_details", [
            'league' => $league,
            'standings_raw' => $standingsRaw,
            'last_fixtures' => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'number_of_matches' => $numberOfMatches,
            'topscorers' => $topscorers,
            'date_format' => $dateFormat,
        ]);
    }

    public function livescores(string $type, Request $request): Factory|View|string
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();
        switch ($type) {
            case 'today':
                $livescores = self::makeCall('livescores', 'league;participants;state;scores;round;stage');
                usort($livescores, function ($a, $b) {
                    if ($a->league_id === $b->league_id) {
                        if ($a->time->starting_at->date_time === $b->time->starting_at->date_time) {
                            return $a->id <=> $b->id;
                        }

                        return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
                    }

                    return $a->league_id <=> $b->league_id;
                });

                return view("{$deviceType}/livescores/livescores_today", ['livescores' => $livescores, 'date_format' => $dateFormat]);

            case 'now':
                $livescores = self::makeCall('livescores/now', 'league;participants;state;scores;round;stage');
                usort($livescores, function ($a, $b) {
                    if ($a->league_id === $b->league_id) {
                        if ($a->time->starting_at->date_time === $b->time->starting_at->date_time) {
                            return $b->id <=> $a->id;
                        }

                        return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
                    }

                    return $a->league_id <=> $b->league_id;
                });

                return view("{$deviceType}/livescores/livescores_now", ['livescores' => $livescores, 'date_format' => $dateFormat]);

            default:
                return '';
        }
    }

    public function fixturesByDate(Request $request): Factory|View
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();
        $date = self::getDateFromRequest($request);

        $fixtures = self::makeCall('fixtures_by_date', 'league;participants;state;scores;round;stage', null, $date);

        usort($fixtures, function ($a, $b) {
            if ($a->league_id === $b->league_id) {
                if ($a->time->starting_at->date_time === $b->time->starting_at->date_time) {
                    return $a->id <=> $b->id;
                }

                return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
            }

            return $a->league_id <=> $b->league_id;
        });

        return view("{$deviceType}/fixtures/fixtures_by_date", ['fixtures' => $fixtures, 'date' => $date, 'date_format' => $dateFormat]);
    }

    public function fixturesDetails(int $fixtureId): Factory|View
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();

        $fixture = self::makeCall('fixture_by_id', 'participants;state;scores;lineups.player;sidelined.player;statistics.type;comments;league;season;events;venue;coaches.coach;stage;round', $fixtureId);
        $h2hFixtures = self::makeCall('h2h', 'participants;league;season;state;scores;round;stage', null, null, $fixture->localteam_id ?? null, $fixture->visitorteam_id ?? null);

        return view("{$deviceType}/fixtures/fixtures_details", ['fixture' => $fixture, 'h2h_fixtures' => $h2hFixtures, 'date_format' => $dateFormat]);
    }

    public function teamsDetails(int $teamId, Request $request): Factory|View
    {
        $deviceType = self::getDeviceType();
        $dateFormat = self::getDateFormat();
        $numberOfMatches = $request->query('matches', 10);

        $team = self::makeCall('team_by_id', 'players;coaches.coach;latest.participants;latest.league;latest.state;latest.scores;latest.round;latest.stage;upcoming.participants;upcoming.league;upcoming.state;upcoming.scores;upcoming.round;upcoming.stage', $teamId);
        $coach = $team->coach->data ?? null;
        $lastFixtures = self::addPagination($team->latest->data ?? [], $numberOfMatches);
        $upcomingFixtures = self::addPagination($team->upcoming->data ?? [], $numberOfMatches);

        return view("{$deviceType}/teams/teams_details", [
            'team' => $team,
            'coach' => $coach,
            'last_fixtures' => $lastFixtures,
            'upcoming_fixtures' => $upcomingFixtures,
            'date_format' => $dateFormat,
        ]);
    }

    public function favoriteTeams(Request $request): Factory|View|RedirectResponse
    {
        $deviceType = self::getDeviceType();
        $favorite_teams = FilebaseController::getField('favorite_teams');

        if (empty($favorite_teams) || $favorite_teams[0] === '') {
            return view("{$deviceType}/teams/favorite_teams", ['teams' => []]);
        }

        if (count($favorite_teams) === 1) {
            return redirect()->route('teamsDetails', ['id' => $favorite_teams[0]]);
        }

        $teams = [];

        foreach ($favorite_teams as $teamId) {
            $teams[] = self::makeCall('team_by_id', 'country;coaches;venue', $teamId);
        }

        usort($teams, function ($a, $b) {
            if ($a->country->data->name === $b->country->data->name) {
                return $a->name <=> $b->name;
            }

            return $a->country->data->name <=> $b->country->data->name;
        });

        $paginatedData = self::addPagination($teams, 20);
        $paginatedData->setPath(self::removePageParameter($request));

        return view("{$deviceType}/teams/favorite_teams", ['teams' => $paginatedData]);
    }

    public function favoriteLeagues(Request $request): Factory|View|RedirectResponse
    {
        $deviceType = self::getDeviceType();
        $favorite_leagues = FilebaseController::getField('favorite_leagues');

        if (empty($favorite_leagues) || $favorite_leagues[0] === '') {
            return view("{$deviceType}/leagues/favorite_leagues", ['leagues' => []]);
        }

        if (count($favorite_leagues) === 1) {
            return redirect()->route('leaguesDetails', ['id' => $favorite_leagues[0]]);
        }

        $leagues = [];

        foreach ($favorite_leagues as $leagueId) {
            $leagues[] = self::makeCall('league_by_id', 'country;currentSeason', $leagueId);
        }

        if (! FilebaseController::getField('show_inactive_leagues')) {
            $currentYear = Carbon::now()->year;
            $season = FilebaseController::getField('season');

            foreach ($leagues as $key => $league) {
                if (! in_array($league->currentSeason->data->name ?? '', [$season, $currentYear])) {
                    unset($leagues[$key]);
                }
            }
        }

        if (count($leagues) === 1) {
            return redirect()->route('leaguesDetails', ['id' => $leagues[0]->id]);
        }

        usort($leagues, function ($a, $b) {
            if ($a->country->data->name === $b->country->data->name) {
                return $a->name <=> $b->name;
            }

            return $a->country->data->name <=> $b->country->data->name;
        });

        $paginatedData = self::addPagination($leagues, 20);
        $paginatedData->setPath(self::removePageParameter($request));

        return view("{$deviceType}/leagues/favorite_leagues", ['leagues' => $paginatedData]);
    }

    public function countLivescores(): int
    {
        $livescores = self::makeCall('livescores/now', 'state', null, null, null, null, false);
        $count = 0;

        foreach ($livescores as $livescore) {
            if (! in_array($livescore->time->status ?? '', [
                'NS', 'FT', 'FT_PEN', 'CANCL', 'POSTP', 'INT', 'ABAN', 'SUSP', 'AWARDED', 'DELAYED', 'TBA', 'WO', 'AU',
            ])) {
                $count++;
            }
        }

        return $count;
    }

    public function addPagination(array $data, int $perPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $dataCollection = collect($data);
        $currentPageData = $dataCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();

        return new LengthAwarePaginator($currentPageData, count($dataCollection), $perPage);
    }

    public function removePageParameter(Request $request): string
    {
        $url = parse_url($request->fullUrl());

        if (isset($url['query'])) {
            parse_str($url['query'], $get);
            unset($get['page']);
            $url['query'] = http_build_query($get);
        }

        return self::buildUrlFromParts($url);
    }

    private static function buildUrlFromParts(array $parts): string
    {
        $result = '';

        if (! empty($parts['scheme'])) {
            $result .= $parts['scheme'].'://';
        }

        if (! empty($parts['host'])) {
            $result .= $parts['host'];
        }

        if (! empty($parts['port'])) {
            $result .= ':'.$parts['port'];
        }

        if (! empty($parts['path'])) {
            $result .= $parts['path'];
        }

        if (isset($parts['query']) && $parts['query'] !== '') {
            $result .= '?'.$parts['query'];
        }

        if (! empty($parts['fragment'])) {
            $result .= '#'.$parts['fragment'];
        }

        return $result;
    }

    public function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        $validDate = DateTime::createFromFormat($format, $date);

        return $validDate && $validDate->format($format) === $date;
    }

    public static function translateString(string $transType, string $transString): string
    {
        $logString = $transType.'.'.$transString;

        if (Lang::has($logString) && trans($logString) !== '') {
            if (trans($logString) === trans($logString, [], 'en') && app()->getLocale() !== 'en') {
                match ($transType) {
                    'application' => Log::alert("{$transType} translation for: {$transString} in ".app()->getLocale()."/{$transType}.php is the same as en/{$transType}.php"),
                    'countries' => Log::critical("{$transType} translation for: {$transString} in ".app()->getLocale()."/{$transType}.php is the same as en/{$transType}.php"),
                    'injuries' => Log::notice("{$transType} translation for: {$transString} in ".app()->getLocale()."/{$transType}.php is the same as en/{$transType}.php"),
                    default => null,
                };
            }

            return trans($logString);
        }

        $logMessage = "Missing {$transType} translation for: {$transString} in ".app()->getLocale()."/{$transType}.php";

        match ($transType) {
            'application' => Log::alert($logMessage),
            'countries' => Log::critical($logMessage),
            'cup_stages' => Log::warning($logMessage),
            'injuries' => Log::notice($logMessage),
            'leagues' => Log::info($logMessage),
            'statistics' => Log::debug($logMessage),
            default => Log::error("Missing error-level for: {$transString}"),
        };

        return $transString;
    }

    public static function getCountryFlag(string $country): string
    {
        if (str_contains($country, ' ')) {
            $country = str_replace(' ', '-', $country);
        }

        $country = match ($country) {
            null, '' => 'Unknown',
            'Northern-Ireland' => 'United-Kingdom',
            default => $country,
        };

        if (! file_exists('images/flags/shiny/16/'.$country.'.png')) {
            Log::emergency('Missing flag for: '.$country);
            $country = 'Unknown';
        }

        return $country;
    }

    public function getDateFormat(): string
    {
        return match (config('app.locale')) {
            'nl' => 'd-m-Y',
            default => 'Y-m-d',
        };
    }

    public function getDateFromRequest(Request $request): string
    {
        if ($request->has('day')) {
            return match ($request->query('day')) {
                'yesterday' => Carbon::now()->subDays(1)->toDateString(),
                'tomorrow' => Carbon::now()->addDays(1)->toDateString(),
                default => Carbon::now()->toDateString(),
            };
        }

        if ($request->has('date')) {
            return $request->query('date');
        }

        return Carbon::now()->toDateString();
    }

    public static function getTeamLogo(?string $file, int $height, int $width): string
    {
        if ($file !== null) {
            $file = preg_replace("/cdn\.sportmonks/", 'sportmonks.gumlet', $file)."?height={$height}&width={$width}";
            $headers = @get_headers($file);

            if ($headers && stripos($headers[0], '200 OK') !== false) {
                return $file;
            }
        }

        return '/images/team_logos/16/Unknown.png';
    }

    public static function getDeviceType(): string
    {
        $agent = new Agent;

        if ($agent->isDesktop()) {
            return 'desktop';
        } elseif ($agent->isPhone()) {
            return 'phone';
        } elseif ($agent->isTablet()) {
            return 'tablet';
        } elseif ($agent->isRobot()) {
            return 'robot';
        }

        return 'desktop';
    }

    public function makeCall(
        string $type,
        ?string $include = null,
        mixed $id = null,
        ?string $date = null,
        mixed $localteam_id = null,
        mixed $visitorteam_id = null,
        bool $abort = true
    ): mixed {
        $localeQuery = array_filter(['locale' => config('sportmonks-football-api.locale', '')]);

        try {
            $resource = match ($type) {
                'livescores' => SportmonksFootballApi::fixture()->setInclude($include ?? '')->withQueries($localeQuery)->byDate(Carbon::now()->toDateString()),
                'livescores/now' => SportmonksFootballApi::livescore()->setInclude($include ?? '')->withQueries($localeQuery)->inplay(),
                'leagues' => SportmonksFootballApi::league()->setInclude($include ?? '')->withQueries($localeQuery)->all(),
                'league_by_id' => SportmonksFootballApi::league()->setInclude($include ?? '')->withQueries($localeQuery)->byId($id),
                'standings' => SportmonksFootballApi::standing()->setInclude($include ?? '')->withQueries($localeQuery)->bySeasonId($id),
                'fixtures_by_date' => SportmonksFootballApi::fixture()->setInclude($include ?? '')->withQueries($localeQuery)->byDate($date),
                'fixture_by_id' => SportmonksFootballApi::fixture()->setInclude($include ?? '')->withQueries($localeQuery)->byId($id),
                'h2h' => SportmonksFootballApi::fixture()->setInclude($include ?? '')->withQueries($localeQuery)->byHeadToHead($localteam_id, $visitorteam_id),
                'team_by_id' => SportmonksFootballApi::team()->setInclude($include ?? '')->withQueries($localeQuery)->byId($id),
                'topscorers' => SportmonksFootballApi::topscorer()->setInclude($include ?? '')->withQueries($localeQuery)->bySeasonId($id),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error('Sportmonks call API error: '.$e->getMessage());

            if ($abort) {
                abort(500, $e->getMessage());
            }

            return [];
        }

        if (is_array($resource)) {
            // V3-style error: {"message": "...", "code": 5001} — Sportmonks internal codes, no 'data' key
            if (isset($resource['message']) && ! isset($resource['data'])) {
                $smCode = $resource['code'] ?? 0;
                // Map Sportmonks internal codes to valid HTTP codes
                $httpCode = match (true) {
                    $smCode >= 100 && $smCode <= 599 => $smCode,
                    $smCode >= 5000 => 500,
                    $smCode >= 4000 => 403,
                    default => 500,
                };
                Log::error('Sportmonks API error: '.$resource['message'], ['sm_code' => $smCode, 'type' => $type]);

                if ($abort) {
                    abort($httpCode, $resource['message']);
                }

                return [];
            }

            $data = $resource['data'] ?? $resource;

            if (in_array($type, ['league_by_id', 'fixture_by_id', 'team_by_id'])) {
                // V3 returns a single object in 'data' for by-id calls; wrap so toObject can map it
                $items = is_array($data) && array_is_list($data) ? $data : (is_array($data) ? [$data] : []);

                return self::toObject($items)[0] ?? (object) [];
            }

            // For list endpoints: only iterate elements that are themselves arrays
            $items = is_array($data) && array_is_list($data) ? $data : [];

            return self::toObject($items);
        }

        return [];
    }

    private static function groupStandings(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $typeMap = [
            'OVERALL_MATCHES' => 'games_played',
            'OVERALL_WINS' => 'won',
            'OVERALL_DRAWS' => 'draw',
            'OVERALL_LOST' => 'lost',
            'OVERALL_SCORED' => 'goals_scored',
            'OVERALL_CONCEDED' => 'goals_against',
        ];

        // bucket key: group_id when present, otherwise stage_id
        $buckets = [];   // key → [rows]
        $bucketNames = [];   // key → group/stage display name
        $stageNames = [];   // key → stage name (for the caption logic)

        foreach ($rows as $row) {
            $groupId = $row->group_id ?? null;
            $stageId = $row->stage_id ?? 0;
            $key = $groupId !== null ? 'g_'.$groupId : 's_'.$stageId;

            // Capture group/stage name on first encounter
            if (! isset($bucketNames[$key])) {
                $bucketNames[$key] = $row->group->data->name  // e.g. "Group A"
                    ?? $row->stage->data->name                // e.g. "Regular Season"
                    ?? 'Group';
                $stageNames[$key] = $row->stage->data->name ?? 'Regular Season';
            }

            // Build overall stats from the standing details include
            $overall = new \stdClass;
            foreach ($row->details->data ?? [] as $detail) {
                $devName = $detail->type->data->developer_name ?? '';
                if (isset($typeMap[$devName])) {
                    $overall->{$typeMap[$devName]} = $detail->value ?? null;
                }
            }
            $row->overall = $overall;
            $row->team_id = $row->participant_id ?? null;
            $row->team_name = $row->participant->data->name ?? null;
            // $row->team is already aliased from participant by normaliseFields
            $row->recent_form = $row->form ?? '';
            $row->status = match ($row->result ?? '') {
                'up' => 'up',
                'down' => 'down',
                default => 'same',
            };

            $buckets[$key][] = $row;
        }

        // Sort buckets by display name so groups appear as A, B, C …
        uksort($buckets, fn ($a, $b) => strcmp($bucketNames[$a] ?? $a, $bucketNames[$b] ?? $b));

        $isMultiBucket = count($buckets) > 1;
        $result = [];

        foreach ($buckets as $key => $bucketRows) {
            usort($bucketRows, fn ($a, $b) => ($a->position ?? 0) <=> ($b->position ?? 0));

            $group = new \stdClass;
            $group->name = $isMultiBucket ? ($bucketNames[$key] ?? 'Group') : 'Regular Season';
            $group->stage_name = $stageNames[$key] ?? 'Regular Season';
            $group->standings = (object) ['data' => $bucketRows];
            $result[] = $group;
        }

        return $result;
    }

    private static function toObject(array $items): array
    {
        return array_values(array_map(
            [self::class, 'itemToObject'],
            array_filter($items, 'is_array')
        ));
    }

    private static function itemToObject(array $item): object
    {
        $obj = new \stdClass;

        foreach ($item as $key => $value) {
            if (is_array($value) && ! array_is_list($value)) {
                // associative array → nested object, wrapped in ->data for relation compatibility
                $obj->$key = (object) ['data' => self::itemToObject($value)];
            } elseif (is_array($value) && array_is_list($value)) {
                // indexed array → wrap each item and keep as ->data collection
                $obj->$key = (object) ['data' => self::toObject(
                    array_filter($value, 'is_array')
                )];
            } else {
                $obj->$key = $value;
            }
        }

        self::normaliseFields($obj);

        return $obj;
    }

    private static function normaliseFields(\stdClass $obj): void
    {
        // Season: V3 returns the include key as lowercase "currentseason" regardless of how it was requested
        if (! isset($obj->currentSeason) && isset($obj->currentseason)) {
            $obj->currentSeason = $obj->currentseason;
        }

        if (isset($obj->currentSeason) && ! isset($obj->season)) {
            $obj->season = $obj->currentSeason;
        }

        // current_season_id: extract from the included currentSeason data, or fall back to season_id
        if (! isset($obj->current_season_id)) {
            $seasonData = $obj->currentSeason->data ?? null;
            if (isset($seasonData->id)) {
                $obj->current_season_id = $seasonData->id;
            } elseif (isset($obj->season_id)) {
                $obj->current_season_id = $obj->season_id;
            }
        }

        // current_stage_id
        if (! isset($obj->current_stage_id) && isset($obj->stage_id)) {
            $obj->current_stage_id = $obj->stage_id;
        }

        if (! isset($obj->time) && isset($obj->starting_at)) {
            $state = $obj->state->data ?? null;
            $status = $state
                ? self::mapV3StateName($state->developer_name ?? $state->name ?? 'NS')
                : self::mapV3StateId($obj->state_id ?? 0);

            $obj->time = (object) [
                'status' => $status,
                'minute' => $obj->minute ?? null,
                'added_time' => $obj->injury_time ?? $obj->added_time ?? null,
                'injury_time' => $obj->injury_time ?? null,
                'starting_at' => (object) ['date_time' => $obj->starting_at],
            ];
        }

        if (! isset($obj->localTeam) && isset($obj->participants->data)) {
            $participants = $obj->participants->data;
            $home = null;
            $away = null;

            foreach ($participants as $p) {
                $location = $p->meta->data->location ?? ($p->location ?? null);
                if ($location === 'home') {
                    $home = $p;
                } elseif ($location === 'away') {
                    $away = $p;
                }
            }

            if ($home) {
                $obj->localTeam = (object) ['data' => $home];
                $obj->localteam_id = $home->id ?? null;
            }

            if ($away) {
                $obj->visitorTeam = (object) ['data' => $away];
                $obj->visitorteam_id = $away->id ?? null;
            }
        }

        if (isset($obj->scores->data) && is_array($obj->scores->data)) {
            $homeGoals = null;
            $awayGoals = null;
            $homePen = null;
            $awayPen = null;

            foreach ($obj->scores->data as $score) {
                // "description" is the direct field; fall back to the type relationship when the sub-include was requested
                $typeName = $score->description ?? $score->type->data->developer_name ?? $score->type->data->name ?? '';
                $goals = $score->score->data->goals ?? null;
                $part = $score->score->data->participant ?? '';

                if (strtoupper($typeName) === 'CURRENT') {
                    if ($part === 'home') {
                        $homeGoals = $goals;
                    } elseif ($part === 'away') {
                        $awayGoals = $goals;
                    }
                } elseif (in_array(strtoupper($typeName), ['PENALTY', 'PENALTY_SHOOTOUT'])) {
                    if ($part === 'home') {
                        $homePen = $goals;
                    } elseif ($part === 'away') {
                        $awayPen = $goals;
                    }
                }
            }

            $obj->scores = (object) [
                'localteam_score' => $homeGoals,
                'visitorteam_score' => $awayGoals,
                // ft_score is a formatted string used by the AET template: "2 - 1 (ET)"
                'ft_score' => ($homeGoals !== null && $awayGoals !== null) ? "$homeGoals - $awayGoals" : null,
                'localteam_pen_score' => $homePen,
                'visitorteam_pen_score' => $awayPen,
            ];
        }

        if (! isset($obj->logo_path) && isset($obj->image_path)) {
            $obj->logo_path = $obj->image_path;
        }

        if (! isset($obj->national_team)) {
            $obj->national_team = ($obj->type ?? '') === 'national';
        }

        // coverage: provide a default so views don't break
        if (! isset($obj->coverage)) {
            $obj->coverage = (object) ['topscorer_goals' => false];
        }

        if (! isset($obj->team) && isset($obj->participant->data)) {
            $obj->team = $obj->participant;
        }

        // coaches.coach sub-include returns pivot rows; extract the nested profile of the active coach.
        if (! isset($obj->coach) && isset($obj->coaches->data)) {
            $coaches = $obj->coaches->data;
            $active = null;
            foreach ($coaches as $c) {
                if ($c->active ?? false) {
                    $active = $c;
                    break;
                }
            }
            $pivot = $active ?? ($coaches[0] ?? null);
            $profile = $pivot->coach->data ?? $pivot;
            if ($profile && ! isset($profile->firstname) && isset($profile->display_name)) {
                $parts = explode(' ', $profile->display_name, 2);
                $profile->firstname = $parts[0] ?? '';
                $profile->lastname = $parts[1] ?? '';
            }
            $obj->coach = (object) ['data' => $profile];
        }

        if (! isset($obj->goals) && isset($obj->total)) {
            $obj->goals = $obj->total;
        }

        // lineups include: type_id 11 = starters, type_id 12 = bench
        if (! isset($obj->lineup) && isset($obj->lineups->data)) {
            $obj->lineup = (object) ['data' => array_values(array_filter($obj->lineups->data, fn ($e) => ($e->type_id ?? 0) === 11))];
            $obj->bench = (object) ['data' => array_values(array_filter($obj->lineups->data, fn ($e) => ($e->type_id ?? 0) === 12))];
        }

        if (! isset($obj->number) && isset($obj->jersey_number)) {
            $obj->number = $obj->jersey_number;
            if (! isset($obj->stats)) {
                $obj->stats = (object) [
                    'goals' => (object) ['scored' => 0],
                    'cards' => (object) ['yellowcards' => 0, 'redcards' => 0],
                ];
            }
        }

        // Fixture: ensure required collections are never null (prevents count(null) TypeError in views)
        if (isset($obj->starting_at)) {
            if (! isset($obj->lineup)) {
                $obj->lineup = (object) ['data' => []];
            }
            if (! isset($obj->bench)) {
                $obj->bench = (object) ['data' => []];
            }
            if (! isset($obj->sidelined)) {
                $obj->sidelined = (object) ['data' => []];
            }
            if (! isset($obj->comments)) {
                $obj->comments = (object) ['data' => []];
            }
            if (! isset($obj->events)) {
                $obj->events = (object) ['data' => []];
            }
            if (! isset($obj->stats)) {
                $obj->stats = (object) ['data' => []];
            }
            // V3 has no formations object
            if (! isset($obj->formations)) {
                $obj->formations = (object) ['localteam_formation' => null, 'visitorteam_formation' => null];
            }
        }

        // Fixture coaches: split into localCoach / visitorCoach keyed by participant_id
        if (! isset($obj->localCoach) && isset($obj->coaches->data) && isset($obj->localteam_id)) {
            foreach ($obj->coaches->data as $c) {
                $pid = $c->participant_id ?? null;
                if ($pid === $obj->localteam_id && ! isset($obj->localCoach)) {
                    $obj->localCoach = (object) ['data' => $c->coach->data ?? $c];
                } elseif ($pid === $obj->visitorteam_id && ! isset($obj->visitorCoach)) {
                    $obj->visitorCoach = (object) ['data' => $c->coach->data ?? $c];
                }
            }
        }

        // Events: guard — has minute + type_id but is not a fixture itself
        if (isset($obj->type_id, $obj->minute) && ! isset($obj->starting_at)) {
            if (! isset($obj->type)) {
                $obj->type = self::mapV3EventTypeId($obj->type_id);
            }
            if (! isset($obj->team_id) && isset($obj->participant_id)) {
                $obj->team_id = $obj->participant_id;
            }
            if (! isset($obj->player_name)) {
                $obj->player_name = $obj->player->data->common_name ?? $obj->player->data->display_name ?? '';
            }
            if (! isset($obj->related_player_name)) {
                $obj->related_player_name = isset($obj->related_player->data)
                    ? ($obj->related_player->data->common_name ?? $obj->related_player->data->display_name ?? null)
                    : null;
            }
            if (! isset($obj->extra_minute)) {
                $obj->extra_minute = null;
            }
        }

        if (! isset($obj->city) && isset($obj->city_id)) {
            $obj->city = '';
        }

        if (! isset($obj->reason) && isset($obj->player_id) && ! isset($obj->jersey_number)) {
            $obj->reason = $obj->category ?? $obj->description ?? '';
        }

        // Coach profile: ensure common_name and coach_id for view rendering
        if (isset($obj->firstname)) {
            if (! isset($obj->common_name)) {
                $obj->common_name = trim(($obj->firstname ?? '').' '.($obj->lastname ?? ''));
            }
            if (! isset($obj->coach_id) && isset($obj->id)) {
                $obj->coach_id = $obj->id;
            }
        }
    }

    private static function mapV3StateName(string $state): string
    {
        return match (strtoupper($state)) {
            'NS', 'NOT_STARTED' => 'NS',
            'INPLAY_1ST_HALF', 'LIVE' => 'LIVE',
            'HT', 'HALF_TIME' => 'HT',
            'INPLAY_2ND_HALF' => 'LIVE',
            'ET', 'EXTRA_TIME', 'INPLAY_ET', 'EXTRA_TIME_HALF_TIME', 'INPLAY_ET_2ND_HALF' => 'ET',
            'PEN_BREAK', 'PENALTIES', 'INPLAY_PENALTIES' => 'LIVE',
            'AET', 'AFTER_EXTRA_TIME' => 'AET',
            'FT_PEN', 'AFTER_PENALTIES', 'PEN_RESULT' => 'FT_PEN',
            'FT', 'FINISHED', 'AWARDED' => 'FT',
            'CANCL', 'CANCELLED' => 'CANCL',
            'POSTP', 'POSTPONED' => 'POSTP',
            'SUSP', 'SUSPENDED' => 'SUSP',
            'ABAN', 'ABANDONED' => 'ABAN',
            'TBA' => 'TBA',
            default => $state,
        };
    }

    private static function mapV3StateId(int $stateId): string
    {
        return match ($stateId) {
            1 => 'NS',
            2 => 'LIVE',
            3 => 'HT',
            4 => 'ET',
            5 => 'FT',
            6 => 'FT_PEN',
            7 => 'AET',
            8 => 'POSTP',
            9 => 'SUSP',
            10 => 'CANCL',
            11 => 'ABAN',
            13 => 'TBA',
            default => 'NS',
        };
    }

    private static function mapV3EventTypeId(int $typeId): string
    {
        return match ($typeId) {
            14 => 'goal',
            15 => 'redcard',
            16 => 'yellowcard',
            17 => 'yellowred',
            18 => 'substitution',
            19 => 'pen_shootout_goal',
            20 => 'pen_shootout_miss',
            21 => 'own_goal',
            default => 'unknown',
        };
    }

    private static function fetchSeasonFixtures(int|string $seasonId): array
    {
        $localeQuery = array_filter(['locale' => config('sportmonks-football-api.locale', '')]);

        try {
            $response = SportmonksFootballApi::schedule()->withQueries($localeQuery)->bySeasonId($seasonId);
        } catch (\Exception $e) {
            Log::error('Schedule API error: '.$e->getMessage());

            return [];
        }

        if (! is_array($response) || ! isset($response['data'])) {
            return [];
        }

        $fixtures = [];

        foreach ($response['data'] as $stage) {
            $stageArr = ['id' => $stage['id'] ?? null, 'name' => $stage['name'] ?? null];
            $rounds = $stage['rounds']['data'] ?? $stage['rounds'] ?? [];

            foreach ($rounds as $round) {
                $roundArr = ['id' => $round['id'] ?? null, 'name' => $round['name'] ?? null];
                $roundFixtures = $round['fixtures']['data'] ?? $round['fixtures'] ?? [];

                foreach ($roundFixtures as $fixture) {
                    $fixture['stage'] = $stageArr;
                    $fixture['round'] = $roundArr;
                    $fixtures[] = $fixture;
                }
            }
        }

        return self::toObject($fixtures);
    }
}

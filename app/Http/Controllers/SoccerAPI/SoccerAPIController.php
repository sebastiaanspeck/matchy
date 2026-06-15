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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Jenssegers\Agent\Agent;

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

        Log::info('League details for: '.$league->current_season_id);

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

            // Fetch topscorers (API-Football uses league+season, no stage filtering needed)
            $topscorersRaw = self::makeCall('topscorers', 'player;team', $league->current_season_id, null, null, null, false);

            if (! empty($topscorersRaw) && $league->current_stage_id !== null) {
                foreach ($topscorersRaw as $key => $ts) {
                    if (($ts->stage_id ?? null) != $league->current_stage_id) {
                        unset($topscorersRaw[$key]);
                    }
                }
            }

            $topscorers = self::addPagination($topscorersRaw, 10);
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

        $fixture = self::makeCall('fixture_by_id', 'participants;state;scores;lineups.player;sidelined.player;statistics.type;comments;league;season;events;venue;coaches;stage;round', $fixtureId);

        $h2hFixtures = [];
        if (! empty($fixture->localteam_id) && ! empty($fixture->visitorteam_id)) {
            $h2hFixtures = self::makeCall('h2h', 'participants;league;season;state;scores;round;stage', null, null, $fixture->localteam_id, $fixture->visitorteam_id, false);
        }

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
        if ($file !== null && $file !== '') {
            return $file;
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
        try {
            return match ($type) {
                'livescores' => $this->fetchFixtures(['date' => Carbon::now()->toDateString()]),
                'livescores/now' => $this->fetchFixtures(['live' => 'all']),
                'leagues' => $this->fetchLeagues(),
                'league_by_id' => $this->fetchLeagueById((int) $id),
                'standings' => $this->fetchStandings((string) $id),
                'fixtures_by_date' => $this->fetchFixtures(['date' => $date]),
                'fixture_by_id' => $this->fetchFixtureById((int) $id),
                'h2h' => $this->fetchH2H((int) $localteam_id, (int) $visitorteam_id),
                'team_by_id' => $this->fetchTeamById((int) $id, $include),
                'topscorers' => $this->fetchTopscorers((string) $id),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error('API-Football call error: '.$e->getMessage());

            if ($abort) {
                abort(500, $e->getMessage());
            }

            return [];
        }
    }

    private static function apiRequest(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders([
            'x-apisports-key' => config('api-football.api_key'),
        ])->get(config('api-football.base_url').$endpoint, $params);

        if (! $response->ok()) {
            Log::error('API-Football HTTP '.$response->status().' on '.$endpoint);

            return [];
        }

        $data = $response->json();

        if (! empty($data['errors'])) {
            $errStr = is_array($data['errors']) ? implode(', ', $data['errors']) : (string) $data['errors'];
            Log::error('API-Football errors on '.$endpoint.': '.$errStr);

            return [];
        }

        return $data['response'] ?? [];
    }

    private function fetchFixtures(array $params): array
    {
        $response = self::apiRequest('fixtures', $params);

        return array_map([self::class, 'mapFixture'], $response);
    }

    private function fetchLeagues(): array
    {
        $response = self::apiRequest('leagues', ['current' => 'true']);

        return array_map([self::class, 'mapLeague'], $response);
    }

    private function fetchLeagueById(int $id): \stdClass
    {
        $response = self::apiRequest('leagues', ['id' => $id]);

        return ! empty($response) ? self::mapLeague($response[0]) : (object) [];
    }

    private function fetchStandings(string $seasonId): array
    {
        [$leagueId, $year] = self::decodeSeasonId($seasonId);

        if ($leagueId === 0 || $year === 0) {
            return [];
        }

        $response = self::apiRequest('standings', ['league' => $leagueId, 'season' => $year]);

        if (empty($response)) {
            return [];
        }

        $standingGroups = $response[0]['league']['standings'] ?? [];
        $rows = [];
        $groupCounter = 1;
        $leagueName = $response[0]['league']['name'] ?? '';

        foreach ($standingGroups as $group) {
            $firstRow = $group[0] ?? [];
            $groupName = $firstRow['group'] ?? 'Regular Season';
            $isSingleGroup = ($groupName === $leagueName || $groupName === 'Regular Season' || $groupName === '');
            $groupId = $isSingleGroup ? null : $groupCounter++;

            foreach ($group as $row) {
                $rows[] = self::mapStandingRow($row, $groupId, $groupName);
            }
        }

        return $rows;
    }

    private function fetchFixtureById(int $id): \stdClass
    {
        $response = self::apiRequest('fixtures', ['id' => $id]);

        if (empty($response)) {
            return (object) [];
        }

        $fixture = self::mapFixture($response[0]);

        // Events
        $eventsRaw = self::apiRequest('fixtures/events', ['fixture' => $id]);
        $playerStats = self::buildPlayerStatsFromEvents($eventsRaw);
        $fixture->events = (object) ['data' => array_map([self::class, 'mapEvent'], $eventsRaw)];

        // Lineups (uses player stats derived from events to avoid an extra API call)
        $lineupsRaw = self::apiRequest('fixtures/lineups', ['fixture' => $id]);
        [$lineup, $bench, $localFormation, $visitorFormation, $localCoach, $visitorCoach] = self::mapLineups(
            $lineupsRaw,
            $fixture->localteam_id,
            $fixture->visitorteam_id,
            $playerStats
        );
        $fixture->lineup = (object) ['data' => $lineup];
        $fixture->bench = (object) ['data' => $bench];
        $fixture->formations = (object) ['localteam_formation' => $localFormation, 'visitorteam_formation' => $visitorFormation];
        $fixture->localCoach = (object) ['data' => $localCoach];
        $fixture->visitorCoach = (object) ['data' => $visitorCoach];

        // Statistics
        $statsRaw = self::apiRequest('fixtures/statistics', ['fixture' => $id]);
        $fixture->statistics = (object) ['data' => self::mapStatistics($statsRaw)];

        return $fixture;
    }

    private function fetchH2H(int $team1Id, int $team2Id): array
    {
        $response = self::apiRequest('fixtures/headtohead', ['h2h' => $team1Id.'-'.$team2Id]);

        return array_map([self::class, 'mapFixture'], $response);
    }

    private function fetchTeamById(int $id, ?string $include = null): \stdClass
    {
        $response = self::apiRequest('teams', ['id' => $id]);

        if (empty($response)) {
            return (object) [];
        }

        $team = self::mapTeamBasic($response[0]);

        // Coach
        $coaches = self::apiRequest('coachs', ['team' => $id]);
        $team->coach = (object) ['data' => ! empty($coaches) ? self::mapCoach($coaches[0]) : null];

        // Fixtures (only when the full team-detail include is requested)
        if ($include !== null && str_contains($include, 'latest')) {
            $latestRaw = self::apiRequest('fixtures', ['team' => $id, 'last' => 15]);
            $upcomingRaw = self::apiRequest('fixtures', ['team' => $id, 'next' => 15]);
            $team->latest = (object) ['data' => array_map([self::class, 'mapFixture'], $latestRaw)];
            $team->upcoming = (object) ['data' => array_map([self::class, 'mapFixture'], $upcomingRaw)];
        } else {
            $team->latest = (object) ['data' => []];
            $team->upcoming = (object) ['data' => []];
        }

        return $team;
    }

    private function fetchTopscorers(string $seasonId): array
    {
        [$leagueId, $year] = self::decodeSeasonId($seasonId);

        if ($leagueId === 0 || $year === 0) {
            return [];
        }

        $response = self::apiRequest('players/topscorers', ['league' => $leagueId, 'season' => $year]);

        return array_values(array_map(function ($ts, $index) {
            $obj = self::mapTopscorer($ts);
            $obj->position = $index + 1;

            return $obj;
        }, $response, array_keys($response)));
    }

    private static function fetchSeasonFixtures(int|string $seasonId): array
    {
        [$leagueId, $year] = self::decodeSeasonId((string) $seasonId);

        if ($leagueId === 0 || $year === 0) {
            return [];
        }

        try {
            $response = self::apiRequest('fixtures', ['league' => $leagueId, 'season' => $year]);
        } catch (\Exception $e) {
            Log::error('API-Football season fixtures error: '.$e->getMessage());

            return [];
        }

        return array_map([self::class, 'mapFixture'], $response);
    }

    private static function mapFixture(array $f): \stdClass
    {
        $fx = $f['fixture'] ?? [];
        $league = $f['league'] ?? [];
        $home = $f['teams']['home'] ?? [];
        $away = $f['teams']['away'] ?? [];
        $goals = $f['goals'] ?? [];
        $score = $f['score'] ?? [];

        $statusShort = $fx['status']['short'] ?? 'NS';
        $status = self::mapApiFootballStatus($statusShort);
        $elapsed = $fx['status']['elapsed'] ?? null;

        [$stageName, $roundName] = self::parseRoundString($league['round'] ?? null);

        $homeGoals = $goals['home'] ?? null;
        $awayGoals = $goals['away'] ?? null;
        $homePen = $score['penalty']['home'] ?? null;
        $awayPen = $score['penalty']['away'] ?? null;

        $homeObj = new \stdClass;
        $homeObj->id = $home['id'] ?? null;
        $homeObj->name = $home['name'] ?? '';
        $homeObj->logo_path = $home['logo'] ?? null;
        $homeObj->national_team = false;

        $awayObj = new \stdClass;
        $awayObj->id = $away['id'] ?? null;
        $awayObj->name = $away['name'] ?? '';
        $awayObj->logo_path = $away['logo'] ?? null;
        $awayObj->national_team = false;

        $leagueObj = new \stdClass;
        $leagueObj->id = $league['id'] ?? null;
        $leagueObj->name = $league['name'] ?? '';
        $leagueObj->image_path = $league['logo'] ?? null;
        $leagueObj->logo_path = $leagueObj->image_path;

        $timeObj = new \stdClass;
        $timeObj->status = $status;
        $timeObj->minute = $elapsed;
        $timeObj->added_time = null;
        $timeObj->injury_time = null;
        $timeObj->starting_at = (object) ['date_time' => $fx['date'] ?? ''];

        $scoresObj = new \stdClass;
        $scoresObj->localteam_score = $homeGoals;
        $scoresObj->visitorteam_score = $awayGoals;
        $scoresObj->ft_score = ($homeGoals !== null && $awayGoals !== null) ? "$homeGoals - $awayGoals" : null;
        $scoresObj->localteam_pen_score = $homePen;
        $scoresObj->visitorteam_pen_score = $awayPen;

        // Referee
        $refereeStr = $fx['referee'] ?? null;
        $refereeObj = null;
        if ($refereeStr) {
            $refereeObj = new \stdClass;
            $refereeObj->fullname = $refereeStr;
        }

        // Venue
        $venueArr = $fx['venue'] ?? null;
        $venueObj = null;
        if ($venueArr) {
            $venueObj = new \stdClass;
            $venueObj->id = $venueArr['id'] ?? null;
            $venueObj->name = $venueArr['name'] ?? '';
            $venueObj->city = $venueArr['city'] ?? '';
        }

        $obj = new \stdClass;
        $obj->id = $fx['id'] ?? null;
        $obj->league_id = $league['id'] ?? null;
        $obj->league = (object) ['data' => $leagueObj];
        $obj->localTeam = (object) ['data' => $homeObj];
        $obj->localteam_id = $homeObj->id;
        $obj->visitorTeam = (object) ['data' => $awayObj];
        $obj->visitorteam_id = $awayObj->id;
        $obj->time = $timeObj;
        $obj->scores = $scoresObj;
        $obj->referee = (object) ['data' => $refereeObj];
        $obj->venue = (object) ['data' => $venueObj];

        if ($roundName !== null) {
            $obj->round = (object) ['data' => (object) ['id' => null, 'name' => $roundName]];
        }
        $obj->stage = (object) ['data' => (object) ['id' => null, 'name' => $stageName]];

        // Defaults for detail fields (populated by fetchFixtureById when needed)
        $obj->lineup = (object) ['data' => []];
        $obj->bench = (object) ['data' => []];
        $obj->sidelined = (object) ['data' => []];
        $obj->comments = (object) ['data' => []];
        $obj->events = (object) ['data' => []];
        $obj->statistics = (object) ['data' => []];
        $obj->formations = (object) ['localteam_formation' => null, 'visitorteam_formation' => null];
        $obj->coverage = (object) ['topscorer_goals' => false];

        return $obj;
    }

    private static function mapLeague(array $l): \stdClass
    {
        $league = $l['league'] ?? [];
        $country = $l['country'] ?? [];
        $seasons = $l['seasons'] ?? [];

        $currentSeason = null;
        foreach ($seasons as $s) {
            if ($s['current'] ?? false) {
                $currentSeason = $s;
                break;
            }
        }
        if ($currentSeason === null && ! empty($seasons)) {
            $currentSeason = end($seasons);
        }

        $leagueId = $league['id'] ?? null;
        $seasonYear = $currentSeason['year'] ?? null;

        $countryObj = new \stdClass;
        $countryObj->name = $country['name'] ?? '';
        $countryObj->image_path = $country['flag'] ?? null;

        $seasonObj = new \stdClass;
        $seasonObj->id = ($leagueId !== null && $seasonYear !== null) ? self::encodeSeasonId($leagueId, $seasonYear) : null;
        $seasonObj->name = $seasonYear;

        $obj = new \stdClass;
        $obj->id = $leagueId;
        $obj->name = $league['name'] ?? '';
        $obj->image_path = $league['logo'] ?? null;
        $obj->logo_path = $obj->image_path;
        $obj->national_team = ($league['type'] ?? '') === 'Cup';
        $obj->country = (object) ['data' => $countryObj];
        $obj->currentSeason = (object) ['data' => $seasonObj];
        $obj->season = $obj->currentSeason;
        $obj->current_season_id = $seasonObj->id;
        $obj->current_stage_id = null;
        $obj->coverage = (object) ['topscorer_goals' => $currentSeason['coverage']['top_scorers'] ?? false];

        return $obj;
    }

    private static function mapTeamBasic(array $teamData): \stdClass
    {
        $team = $teamData['team'] ?? [];
        $venue = $teamData['venue'] ?? [];

        $countryObj = new \stdClass;
        $countryObj->name = $team['country'] ?? '';

        $obj = new \stdClass;
        $obj->id = $team['id'] ?? null;
        $obj->name = $team['name'] ?? '';
        $obj->logo_path = $team['logo'] ?? null;
        $obj->national_team = $team['national'] ?? false;
        $obj->founded = $team['founded'] ?? null;
        $obj->country = (object) ['data' => $countryObj];
        $obj->coverage = (object) ['topscorer_goals' => false];

        if (! empty($venue)) {
            $venueObj = new \stdClass;
            $venueObj->id = $venue['id'] ?? null;
            $venueObj->name = $venue['name'] ?? '';
            $venueObj->city = $venue['city'] ?? '';
            $venueObj->capacity = $venue['capacity'] ?? null;
            $obj->venue = (object) ['data' => $venueObj];
        } else {
            $obj->venue = (object) ['data' => null];
        }

        return $obj;
    }

    private static function mapCoach(array $coachData): ?\stdClass
    {
        if (empty($coachData)) {
            return null;
        }

        $nameParts = explode(' ', $coachData['name'] ?? '', 2);

        $obj = new \stdClass;
        $obj->id = $coachData['id'] ?? null;
        $obj->coach_id = $coachData['id'] ?? null;
        $obj->firstname = $nameParts[0] ?? '';
        $obj->lastname = $nameParts[1] ?? '';
        $obj->common_name = $coachData['name'] ?? '';
        $obj->display_name = $coachData['name'] ?? '';
        $obj->nationality = $coachData['nationality'] ?? 'Unknown';

        return $obj;
    }

    private static function mapStandingRow(array $row, ?int $groupId, string $groupName): \stdClass
    {
        $all = $row['all'] ?? [];
        $team = $row['team'] ?? [];

        $teamObj = new \stdClass;
        $teamObj->id = $team['id'] ?? null;
        $teamObj->name = $team['name'] ?? '';
        $teamObj->logo_path = $team['logo'] ?? null;

        $overallObj = new \stdClass;
        $overallObj->games_played = $all['played'] ?? null;
        $overallObj->won = $all['win'] ?? null;
        $overallObj->draw = $all['draw'] ?? null;
        $overallObj->lost = $all['lose'] ?? null;
        $overallObj->goals_scored = $all['goals']['for'] ?? null;
        $overallObj->goals_against = $all['goals']['against'] ?? null;

        $obj = new \stdClass;
        $obj->id = null;
        $obj->position = $row['rank'] ?? null;
        $obj->participant_id = $team['id'] ?? null;
        $obj->participant = (object) ['data' => $teamObj];
        $obj->team = $obj->participant;
        $obj->team_id = $team['id'] ?? null;
        $obj->team_name = $team['name'] ?? '';
        $obj->points = $row['points'] ?? null;
        $obj->goalsDiff = $row['goalsDiff'] ?? null;
        $obj->form = $row['form'] ?? '';
        $obj->result = $row['status'] ?? 'same';
        $obj->overall = $overallObj;
        $obj->details = (object) ['data' => []];
        $obj->group_id = $groupId;
        $obj->group = (object) ['data' => (object) ['id' => $groupId, 'name' => $groupId !== null ? $groupName : null]];
        $obj->stage_id = 1;
        $obj->stage = (object) ['data' => (object) ['id' => 1, 'name' => 'Regular Season']];

        return $obj;
    }

    private static function mapTopscorer(array $ts): \stdClass
    {
        $player = $ts['player'] ?? [];
        $stats = $ts['statistics'][0] ?? [];
        $team = $stats['team'] ?? [];

        $playerObj = new \stdClass;
        $playerObj->id = $player['id'] ?? null;
        $playerObj->common_name = $player['name'] ?? '';
        $playerObj->display_name = $player['name'] ?? '';
        $playerObj->nationality = $player['nationality'] ?? 'Unknown';

        $teamObj = new \stdClass;
        $teamObj->id = $team['id'] ?? null;
        $teamObj->name = $team['name'] ?? '';
        $teamObj->logo_path = $team['logo'] ?? null;

        $obj = new \stdClass;
        $obj->id = null;
        $obj->position = 0;
        $obj->player_id = $player['id'] ?? null;
        $obj->player = (object) ['data' => $playerObj];
        $obj->team = (object) ['data' => $teamObj];
        $obj->stage_id = null;
        $obj->goals = $stats['goals']['total'] ?? 0;
        $obj->total = $obj->goals;

        return $obj;
    }

    private static function mapEvent(array $e): \stdClass
    {
        $type = $e['type'] ?? '';
        $detail = $e['detail'] ?? '';

        $mappedType = match (true) {
            $type === 'subst' => 'substitution',
            $type === 'Goal' && $detail === 'Own Goal' => 'own_goal',
            $type === 'Goal' => 'goal',
            $type === 'Card' && $detail === 'Yellow Card' => 'yellowcard',
            $type === 'Card' && $detail === 'Red Card' => 'redcard',
            $type === 'Card' && $detail === 'Yellow Red Card' => 'yellowred',
            default => strtolower(str_replace(' ', '_', $type)),
        };

        $obj = new \stdClass;
        $obj->id = null;
        $obj->team_id = $e['team']['id'] ?? null;
        $obj->minute = $e['time']['elapsed'] ?? null;
        $obj->extra_minute = $e['time']['extra'] ?? null;
        $obj->player_name = $e['player']['name'] ?? '';
        $obj->related_player_name = $e['assist']['name'] ?? null;
        $obj->type = $mappedType;

        return $obj;
    }

    private static function buildPlayerStatsFromEvents(array $events): array
    {
        $stats = [];

        foreach ($events as $e) {
            $playerId = $e['player']['id'] ?? null;
            if (! $playerId) {
                continue;
            }

            if (! isset($stats[$playerId])) {
                $stats[$playerId] = ['goals' => 0, 'yellowcards' => 0, 'redcards' => 0];
            }

            $type = $e['type'] ?? '';
            $detail = $e['detail'] ?? '';

            if ($type === 'Goal' && $detail !== 'Own Goal') {
                $stats[$playerId]['goals']++;
            } elseif ($type === 'Card' && $detail === 'Yellow Card') {
                $stats[$playerId]['yellowcards']++;
            } elseif ($type === 'Card' && in_array($detail, ['Red Card', 'Yellow Red Card'])) {
                $stats[$playerId]['redcards']++;
            }
        }

        return $stats;
    }

    private static function mapLineups(array $lineups, ?int $homeTeamId, ?int $awayTeamId, array $playerStats = []): array
    {
        $allLineup = [];
        $allBench = [];
        $homeFormation = null;
        $awayFormation = null;
        $homeCoach = null;
        $awayCoach = null;

        foreach ($lineups as $teamLineup) {
            $teamId = $teamLineup['team']['id'] ?? null;
            $isHome = ($teamId === $homeTeamId);

            $formation = $teamLineup['formation'] ?? null;
            if ($isHome) {
                $homeFormation = $formation;
            } else {
                $awayFormation = $formation;
            }

            if (! empty($teamLineup['coach'])) {
                $coach = self::mapCoach($teamLineup['coach']);
                if ($isHome) {
                    $homeCoach = $coach;
                } else {
                    $awayCoach = $coach;
                }
            }

            foreach ($teamLineup['startXI'] ?? [] as $p) {
                $allLineup[] = self::mapLineupPlayer($p['player'] ?? [], $teamId, 11, $playerStats);
            }

            foreach ($teamLineup['substitutes'] ?? [] as $p) {
                $allBench[] = self::mapLineupPlayer($p['player'] ?? [], $teamId, 12, $playerStats);
            }
        }

        return [$allLineup, $allBench, $homeFormation, $awayFormation, $homeCoach, $awayCoach];
    }

    private static function mapLineupPlayer(array $player, ?int $teamId, int $typeId, array $playerStats = []): \stdClass
    {
        $playerId = $player['id'] ?? null;
        $stats = $playerStats[$playerId] ?? ['goals' => 0, 'yellowcards' => 0, 'redcards' => 0];

        $obj = new \stdClass;
        $obj->id = null;
        $obj->player_id = $playerId;
        $obj->number = $player['number'] ?? null;
        $obj->type_id = $typeId;
        $obj->team_id = $teamId;
        $obj->player_name = $player['name'] ?? '';
        $obj->stats = (object) [
            'goals' => (object) ['scored' => $stats['goals']],
            'cards' => (object) ['yellowcards' => $stats['yellowcards'], 'redcards' => $stats['redcards']],
        ];

        return $obj;
    }

    private static function mapStatistics(array $statsResponse): array
    {
        $statTypeMap = [
            'Shots on Goal' => ['id' => 1, 'dev_name' => 'SHOTS_ONGOAL'],
            'Shots off Goal' => ['id' => 2, 'dev_name' => 'SHOTS_OFFGOAL'],
            'Total Shots' => ['id' => 3, 'dev_name' => 'SHOTS_TOTAL'],
            'Blocked Shots' => ['id' => 4, 'dev_name' => 'SHOTS_BLOCKED'],
            'Shots insidebox' => ['id' => 5, 'dev_name' => 'SHOTS_INSIDEBOX'],
            'Shots outsidebox' => ['id' => 6, 'dev_name' => 'SHOTS_OUTSIDEBOX'],
            'Fouls' => ['id' => 7, 'dev_name' => 'FOULS'],
            'Corner Kicks' => ['id' => 8, 'dev_name' => 'CORNERS'],
            'Offsides' => ['id' => 9, 'dev_name' => 'OFFSIDES'],
            'Ball Possession' => ['id' => 10, 'dev_name' => 'POSSESSIONTIME'],
            'Yellow Cards' => ['id' => 11, 'dev_name' => 'YELLOWCARDS'],
            'Red Cards' => ['id' => 12, 'dev_name' => 'REDCARDS'],
            'Goalkeeper Saves' => ['id' => 13, 'dev_name' => 'SAVES'],
            'Total passes' => ['id' => 14, 'dev_name' => 'PASSES_TOTAL'],
            'Passes accurate' => ['id' => 15, 'dev_name' => 'PASSES_ACCURATE'],
            'Passes %' => ['id' => 16, 'dev_name' => 'PASSES_PERCENTAGE'],
        ];

        $result = [];

        foreach ($statsResponse as $teamStats) {
            $teamId = $teamStats['team']['id'] ?? null;

            foreach ($teamStats['statistics'] ?? [] as $stat) {
                $typeName = $stat['type'] ?? '';
                $typeInfo = $statTypeMap[$typeName] ?? null;

                if (! $typeInfo) {
                    continue;
                }

                $value = $stat['value'];
                if (is_string($value) && str_ends_with($value, '%')) {
                    $value = (float) rtrim($value, '%');
                }

                $typeObj = new \stdClass;
                $typeObj->developer_name = $typeInfo['dev_name'];
                $typeObj->name = $typeName;

                $statObj = new \stdClass;
                $statObj->type_id = $typeInfo['id'];
                $statObj->type = (object) ['data' => $typeObj];
                $statObj->participant_id = $teamId;
                $statObj->value = $value;

                $result[] = $statObj;
            }
        }

        return $result;
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

        $buckets = [];
        $bucketNames = [];
        $stageNames = [];

        foreach ($rows as $row) {
            $groupId = $row->group_id ?? null;
            $stageId = $row->stage_id ?? 0;
            $key = $groupId !== null ? 'g_'.$groupId : 's_'.$stageId;

            if (! isset($bucketNames[$key])) {
                $bucketNames[$key] = $row->group->data->name
                    ?? $row->stage->data->name
                    ?? 'Group';
                $stageNames[$key] = $row->stage->data->name ?? 'Regular Season';
            }

            // Build overall stats from details when present; otherwise use pre-built overall
            if (! empty($row->details->data ?? [])) {
                $overall = new \stdClass;
                foreach ($row->details->data as $detail) {
                    $devName = $detail->type->data->developer_name ?? '';
                    if (isset($typeMap[$devName])) {
                        $overall->{$typeMap[$devName]} = $detail->value ?? null;
                    }
                }
                $row->overall = $overall;
            } elseif (! isset($row->overall)) {
                $row->overall = new \stdClass;
            }

            $row->team_id = $row->participant_id ?? null;
            $row->team_name = $row->participant->data->name ?? null;
            $row->recent_form = $row->form ?? '';
            $row->status = match ($row->result ?? '') {
                'up' => 'up',
                'down' => 'down',
                default => 'same',
            };

            $buckets[$key][] = $row;
        }

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

    private static function encodeSeasonId(int $leagueId, int $year): string
    {
        return $leagueId.':'.$year;
    }

    private static function decodeSeasonId(string $seasonId): array
    {
        if (str_contains($seasonId, ':')) {
            $parts = explode(':', $seasonId, 2);

            return [(int) $parts[0], (int) $parts[1]];
        }

        return [0, 0];
    }

    private static function mapApiFootballStatus(string $short): string
    {
        return match ($short) {
            'NS' => 'NS',
            '1H' => 'LIVE',
            'HT' => 'HT',
            '2H' => 'LIVE',
            'ET' => 'ET',
            'BT' => 'BREAK',
            'P' => 'PEN_LIVE',
            'SUSP' => 'SUSP',
            'INT' => 'INT',
            'FT' => 'FT',
            'AET' => 'AET',
            'PEN' => 'FT_PEN',
            'PST' => 'POSTP',
            'CANC' => 'CANCL',
            'ABD' => 'ABAN',
            'AWD' => 'AWARDED',
            'WO' => 'WO',
            'LIVE' => 'LIVE',
            'TBD' => 'TBA',
            default => $short,
        };
    }

    private static function parseRoundString(?string $roundStr): array
    {
        if ($roundStr === null) {
            return ['Regular Season', null];
        }

        if (preg_match('/^(.*)\s-\s(\d+)$/', $roundStr, $m)) {
            return [trim($m[1]), $m[2]];
        }

        return [$roundStr, null];
    }
}

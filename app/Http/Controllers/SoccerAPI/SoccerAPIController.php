<?php

namespace App\Http\Controllers\SoccerAPI;

use App\Contracts\FootballApiProviderInterface;
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

class SoccerAPIController extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;

    public function __construct(private readonly FootballApiProviderInterface $provider)
    {
    }

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

        Log::info('League details for: '.($league->current_season_id ?? 'unknown'));

        $standingsRaw = self::groupStandings(
            self::makeCall('standings', 'participant;details.type;group;stage', $league->current_season_id ?? 0)
        );
        $lastFixtures = [];
        $upcomingFixtures = [];
        $numberOfMatches = $request->query('matches', 10);
        $topscorers = [];

        if (! empty($league->current_season_id)) {
            try {
                $allFixtures = $this->provider->fetchSeasonFixtures($league->current_season_id);
            } catch (\Exception $e) {
                Log::error('fetchSeasonFixtures failed: '.$e->getMessage());
                $allFixtures = [];
            }
            $finishedStatuses = ['FT', 'AET', 'FT_PEN', 'ABAN', 'AWARDED'];

            $results = array_values(array_filter($allFixtures, fn ($f) => in_array($f->time->status ?? '', $finishedStatuses)));
            $upcoming = array_values(array_filter($allFixtures, fn ($f) => ! in_array($f->time->status ?? '', $finishedStatuses)));

            usort($results, fn ($a, $b) => $b->time->starting_at->date_time <=> $a->time->starting_at->date_time);
            usort($upcoming, fn ($a, $b) => $a->time->starting_at->date_time <=> $b->time->starting_at->date_time);

            $lastFixtures = self::addPagination($results, $numberOfMatches);
            $upcomingFixtures = self::addPagination($upcoming, $numberOfMatches);

            $topscorersRaw = self::makeCall('topscorers', 'player;team', $league->current_season_id, null, null, null, false);

            $currentStageId = $league->current_stage_id ?? null;

            if (! empty($topscorersRaw) && $currentStageId !== null) {
                foreach ($topscorersRaw as $key => $ts) {
                    if (($ts->stage_id ?? null) !== $currentStageId) {
                        unset($topscorersRaw[$key]);
                    }
                }

                $position = 1;
                foreach ($topscorersRaw as $key => $ts) {
                    $topscorersRaw[$key]->position = $position++;
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
                'livescores' => $this->provider->fetchFixtures(['date' => Carbon::now()->toDateString()]),
                'livescores/now' => $this->provider->fetchFixtures(['live' => 'all']),
                'leagues' => $this->provider->fetchLeagues(),
                'league_by_id' => $this->provider->fetchLeagueById((int) $id),
                'standings' => $this->provider->fetchStandings((string) $id),
                'fixtures_by_date' => $this->provider->fetchFixtures(['date' => $date]),
                'fixture_by_id' => $this->provider->fetchFixtureById((int) $id),
                'h2h' => $this->provider->fetchH2H((int) $localteam_id, (int) $visitorteam_id),
                'team_by_id' => $this->provider->fetchTeamById((int) $id, $include),
                'topscorers' => $this->provider->fetchTopscorers((string) $id),
                default => [],
            };
        } catch (\Exception $e) {
            Log::error('Football API call error: '.$e->getMessage());

            if ($abort) {
                abort(500, $e->getMessage());
            }

            return [];
        }
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

    public static function getTeamLogo(?string $file, int $height = 16, int $width = 16): string
    {
        if ($file !== null && $file !== '') {
            if (str_contains($file, 'cdn.sportmonks')) {
                $file = preg_replace('/cdn\.sportmonks/', 'sportmonks.gumlet', $file)."?height={$height}&width={$width}";
            }

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
}

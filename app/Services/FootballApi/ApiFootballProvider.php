<?php

namespace App\Services\FootballApi;

use App\Contracts\FootballApiProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiFootballProvider implements FootballApiProviderInterface
{
    public function fetchFixtures(array $params): array
    {
        if (isset($params['live'])) {
            $response = $this->apiRequest('fixtures', ['live' => 'all']);
        } elseif (isset($params['team'])) {
            $response = $this->apiRequest('fixtures', $params);
        } else {
            $date = $params['date'] ?? Carbon::now()->toDateString();
            $response = $this->apiRequest('fixtures', ['date' => $date]);
        }

        return array_map([self::class, 'mapFixture'], $response);
    }

    public function fetchLeagues(): array
    {
        $response = $this->apiRequest('leagues', ['current' => 'true']);

        return array_map([self::class, 'mapLeague'], $response);
    }

    public function fetchLeagueById(int $id): \stdClass
    {
        $response = $this->apiRequest('leagues', ['id' => $id]);

        return ! empty($response) ? self::mapLeague($response[0]) : (object) [];
    }

    public function fetchStandings(string $seasonId): array
    {
        [$leagueId, $year] = self::decodeSeasonId($seasonId);

        if ($leagueId === 0 || $year === 0) {
            return [];
        }

        $response = $this->apiRequest('standings', ['league' => $leagueId, 'season' => $year]);

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

    public function fetchFixtureById(int $id): \stdClass
    {
        $response = $this->apiRequest('fixtures', ['id' => $id]);

        if (empty($response)) {
            return (object) [];
        }

        $fixture = self::mapFixture($response[0]);

        $base = config('football-api.api_football.base_url');
        $headers = ['x-apisports-key' => config('football-api.api_football.api_key')];

        [$eventsRes, $lineupsRes, $statsRes] = Http::pool(fn ($pool) => [
            $pool->withHeaders($headers)->get($base.'fixtures/events', ['fixture' => $id]),
            $pool->withHeaders($headers)->get($base.'fixtures/lineups', ['fixture' => $id]),
            $pool->withHeaders($headers)->get($base.'fixtures/statistics', ['fixture' => $id]),
        ]);

        $eventsRaw = $eventsRes->ok() ? ($eventsRes->json()['response'] ?? []) : [];
        $lineupsRaw = $lineupsRes->ok() ? ($lineupsRes->json()['response'] ?? []) : [];
        $statsRaw = $statsRes->ok() ? ($statsRes->json()['response'] ?? []) : [];

        $playerStats = self::buildPlayerStatsFromEvents($eventsRaw);
        $fixture->events = (object) ['data' => array_map([self::class, 'mapEvent'], $eventsRaw)];

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

        $fixture->statistics = (object) ['data' => self::mapStatistics($statsRaw)];

        return $fixture;
    }

    public function fetchH2H(int $team1Id, int $team2Id): array
    {
        $response = $this->apiRequest('fixtures/headtohead', ['h2h' => $team1Id.'-'.$team2Id]);

        return array_map([self::class, 'mapFixture'], $response);
    }

    public function fetchTeamById(int $id, ?string $include = null): \stdClass
    {
        $base = config('football-api.api_football.base_url');
        $headers = ['x-apisports-key' => config('football-api.api_football.api_key')];

        [$teamRes, $coachsRes] = Http::pool(fn ($pool) => [
            $pool->withHeaders($headers)->get($base.'teams', ['id' => $id]),
            $pool->withHeaders($headers)->get($base.'coachs', ['team' => $id]),
        ]);

        $teamData = $teamRes->ok() ? ($teamRes->json()['response'] ?? []) : [];
        $coachsData = $coachsRes->ok() ? ($coachsRes->json()['response'] ?? []) : [];

        if (empty($teamData)) {
            return (object) [];
        }

        $team = self::mapTeamBasic($teamData[0]);
        $team->coach = (object) ['data' => ! empty($coachsData) ? self::mapCoach($coachsData[0]) : null];

        if ($include !== null && str_contains($include, 'latest')) {
            [$latestRes, $upcomingRes] = Http::pool(fn ($pool) => [
                $pool->withHeaders($headers)->get($base.'fixtures', ['team' => $id, 'last' => 15]),
                $pool->withHeaders($headers)->get($base.'fixtures', ['team' => $id, 'next' => 15]),
            ]);
            $latestRaw = $latestRes->ok() ? ($latestRes->json()['response'] ?? []) : [];
            $upcomingRaw = $upcomingRes->ok() ? ($upcomingRes->json()['response'] ?? []) : [];
            $team->latest = (object) ['data' => array_map([self::class, 'mapFixture'], $latestRaw)];
            $team->upcoming = (object) ['data' => array_map([self::class, 'mapFixture'], $upcomingRaw)];
        } else {
            $team->latest = (object) ['data' => []];
            $team->upcoming = (object) ['data' => []];
        }

        return $team;
    }

    public function fetchTopscorers(string $seasonId): array
    {
        [$leagueId, $year] = self::decodeSeasonId($seasonId);

        if ($leagueId === 0 || $year === 0) {
            return [];
        }

        $response = $this->apiRequest('players/topscorers', ['league' => $leagueId, 'season' => $year]);

        return array_values(array_map(function ($ts, $index) {
            $obj = self::mapTopscorer($ts);
            $obj->position = $index + 1;

            return $obj;
        }, $response, array_keys($response)));
    }

    public function fetchSeasonFixtures(int|string $seasonId): array
    {
        [$leagueId, $year] = self::decodeSeasonId((string) $seasonId);

        if ($leagueId === 0 || $year === 0) {
            return [];
        }

        $response = $this->apiRequest('fixtures', ['league' => $leagueId, 'season' => $year]);

        return array_map([self::class, 'mapFixture'], $response);
    }

    private function apiRequest(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders([
            'x-apisports-key' => config('football-api.api_football.api_key'),
        ])->get(config('football-api.api_football.base_url').$endpoint, $params);
        // ConnectionException (network errors) intentionally propagates to makeCall()'s catch block

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

    private static function mapFixture(array $f): \stdClass
    {
        $fx = $f['fixture'] ?? [];
        $league = $f['league'] ?? [];
        $home = $f['teams']['home'] ?? [];
        $away = $f['teams']['away'] ?? [];
        $goals = $f['goals'] ?? [];
        $score = $f['score'] ?? [];

        $statusShort = $fx['status']['short'] ?? 'NS';
        $status = self::mapStatus($statusShort);
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

        $refereeStr = $fx['referee'] ?? null;
        $refereeObj = null;
        if ($refereeStr) {
            $refereeObj = new \stdClass;
            $refereeObj->fullname = $refereeStr;
        }

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
        $obj->national_team = false;
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
            if ($playerId === null) {
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
            $isHome = ($homeTeamId !== null && $teamId === $homeTeamId);

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

        Log::error('ApiFootballProvider: malformed seasonId — expected "leagueId:year", got: '.$seasonId);

        return [0, 0];
    }

    private static function mapStatus(string $short): string
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

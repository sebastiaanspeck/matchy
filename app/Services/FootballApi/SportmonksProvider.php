<?php

namespace App\Services\FootballApi;

use App\Contracts\FootballApiProviderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PyaeSoneAung\SportmonksFootballApi\Facades\SportmonksFootballApi;

class SportmonksProvider implements FootballApiProviderInterface
{
    public function fetchFixtures(array $params): array
    {
        try {
            if (isset($params['live'])) {
                $resource = SportmonksFootballApi::livescore()
                    ->setInclude('league;participants;state;scores;round;stage')
                    ->inplay();
            } else {
                $date = $params['date'] ?? Carbon::now()->toDateString();
                $resource = SportmonksFootballApi::fixture()
                    ->setInclude('league;participants;state;scores;round;stage')
                    ->byDate($date);
            }
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchFixtures error: '.$e->getMessage());

            return [];
        }

        return $this->processList($resource);
    }

    public function fetchLeagues(): array
    {
        try {
            $resource = SportmonksFootballApi::league()
                ->setInclude('country;currentSeason')
                ->all();
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchLeagues error: '.$e->getMessage());

            return [];
        }

        return $this->processList($resource);
    }

    public function fetchLeagueById(int $id): \stdClass
    {
        try {
            $resource = SportmonksFootballApi::league()
                ->setInclude('country;currentSeason')
                ->byId($id);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchLeagueById error: '.$e->getMessage());

            return (object) [];
        }

        return $this->processSingle($resource, 'league_by_id');
    }

    public function fetchStandings(string $seasonId): array
    {
        try {
            $resource = SportmonksFootballApi::standing()
                ->setInclude('participant;details.type;group;stage')
                ->bySeasonId($seasonId);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchStandings error: '.$e->getMessage());

            return [];
        }

        return $this->processList($resource);
    }

    public function fetchFixtureById(int $id): \stdClass
    {
        try {
            $resource = SportmonksFootballApi::fixture()
                ->setInclude('participants;state;scores;lineups.player;sidelined.player;statistics.type;comments;league;season;events;venue;coaches.coach;stage;round')
                ->byId($id);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchFixtureById error: '.$e->getMessage());

            return (object) [];
        }

        return $this->processSingle($resource, 'fixture_by_id');
    }

    public function fetchH2H(int $team1Id, int $team2Id): array
    {
        try {
            $resource = SportmonksFootballApi::fixture()
                ->setInclude('participants;league;season;state;scores;round;stage')
                ->byHeadToHead($team1Id, $team2Id);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchH2H error: '.$e->getMessage());

            return [];
        }

        return $this->processList($resource);
    }

    public function fetchTeamById(int $id, ?string $include = null): \stdClass
    {
        $sdkInclude = $include ?? 'country;coaches.coach;venue';

        try {
            $resource = SportmonksFootballApi::team()
                ->setInclude($sdkInclude)
                ->byId($id);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchTeamById error: '.$e->getMessage());

            return (object) [];
        }

        return $this->processSingle($resource, 'team_by_id');
    }

    public function fetchTopscorers(string $seasonId): array
    {
        try {
            $resource = SportmonksFootballApi::topscorer()
                ->setInclude('player;team')
                ->bySeasonId($seasonId);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchTopscorers error: '.$e->getMessage());

            return [];
        }

        return $this->processList($resource);
    }

    public function fetchSeasonFixtures(int|string $seasonId): array
    {
        try {
            $response = SportmonksFootballApi::schedule()->bySeasonId($seasonId);
        } catch (\Exception $e) {
            Log::error('Sportmonks fetchSeasonFixtures error: '.$e->getMessage());

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

        return $this->toObject($fixtures);
    }

    private function processSingle(mixed $resource, string $type): \stdClass
    {
        if (! is_array($resource)) {
            return (object) [];
        }

        if (isset($resource['message']) && ! isset($resource['data'])) {
            Log::error('Sportmonks API error: '.$resource['message'], ['type' => $type]);

            return (object) [];
        }

        $data = $resource['data'] ?? $resource;
        $items = is_array($data) && array_is_list($data) ? $data : (is_array($data) ? [$data] : []);

        return $this->toObject($items)[0] ?? (object) [];
    }

    private function processList(mixed $resource): array
    {
        if (! is_array($resource)) {
            return [];
        }

        if (isset($resource['message']) && ! isset($resource['data'])) {
            Log::error('Sportmonks API error: '.$resource['message']);

            return [];
        }

        $data = $resource['data'] ?? $resource;
        $items = is_array($data) && array_is_list($data) ? $data : [];

        return $this->toObject($items);
    }

    private function toObject(array $items): array
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
                $obj->$key = (object) ['data' => self::itemToObject($value)];
            } elseif (is_array($value) && array_is_list($value)) {
                $obj->$key = (object) ['data' => array_values(array_map(
                    [self::class, 'itemToObject'],
                    array_filter($value, 'is_array')
                ))];
            } else {
                $obj->$key = $value;
            }
        }

        self::normaliseFields($obj);

        return $obj;
    }

    private static function normaliseFields(\stdClass $obj): void
    {
        if (! isset($obj->currentSeason) && isset($obj->currentseason)) {
            $obj->currentSeason = $obj->currentseason;
        }

        if (isset($obj->currentSeason) && ! isset($obj->season)) {
            $obj->season = $obj->currentSeason;
        }

        if (! isset($obj->current_season_id)) {
            $seasonData = $obj->currentSeason->data ?? null;
            if (isset($seasonData->id)) {
                $obj->current_season_id = $seasonData->id;
            } elseif (isset($obj->season_id)) {
                $obj->current_season_id = $obj->season_id;
            }
        }

        if (! isset($obj->current_stage_id) && isset($obj->stage_id)) {
            $obj->current_stage_id = $obj->stage_id;
        }

        if (! isset($obj->time) && isset($obj->starting_at)) {
            $state = $obj->state->data ?? null;
            $status = $state
                ? self::mapStateName($state->developer_name ?? $state->name ?? 'NS')
                : self::mapStateId($obj->state_id ?? 0);

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

        if (! isset($obj->coverage)) {
            $obj->coverage = (object) ['topscorer_goals' => false];
        }

        if (! isset($obj->team) && isset($obj->participant->data)) {
            $obj->team = $obj->participant;
        }

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

        if (isset($obj->starting_at)) {
            foreach (['lineup', 'bench', 'sidelined', 'comments', 'events', 'stats'] as $field) {
                if (! isset($obj->$field)) {
                    $obj->$field = (object) ['data' => []];
                }
            }
            if (! isset($obj->formations)) {
                $obj->formations = (object) ['localteam_formation' => null, 'visitorteam_formation' => null];
            }
        }

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

        if (isset($obj->type_id, $obj->minute) && ! isset($obj->starting_at)) {
            if (! isset($obj->type)) {
                $obj->type = self::mapEventTypeId($obj->type_id);
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

        if (isset($obj->firstname)) {
            if (! isset($obj->common_name)) {
                $obj->common_name = trim(($obj->firstname ?? '').' '.($obj->lastname ?? ''));
            }
            if (! isset($obj->coach_id) && isset($obj->id)) {
                $obj->coach_id = $obj->id;
            }
        }
    }

    private static function mapStateName(string $state): string
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

    private static function mapStateId(int $stateId): string
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

    private static function mapEventTypeId(int $typeId): string
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
}

<?php

namespace App\Contracts;

interface FootballApiProviderInterface
{
    public function fetchFixtures(array $params): array;

    public function fetchLeagues(): array;

    public function fetchLeagueById(int $id): \stdClass;

    public function fetchStandings(string $seasonId): array;

    public function fetchFixtureById(int $id): \stdClass;

    public function fetchH2H(int $team1Id, int $team2Id): array;

    public function fetchTeamById(int $id, ?string $include = null): \stdClass;

    public function fetchTopscorers(string $seasonId): array;

    public function fetchSeasonFixtures(int|string $seasonId): array;
}

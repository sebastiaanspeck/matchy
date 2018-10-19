<?php

namespace App\Http\Requests;

use Sportmonks\SoccerAPI\SoccerAPIClient;

class CustomTopScorer extends SoccerAPIClient {
    public function aggregatedBySeasonId($seasonId)
    {
        return $this->callData('topscorers/season/' . $seasonId . '/aggregated');
    }
}
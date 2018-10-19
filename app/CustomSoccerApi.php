<?php

namespace App;

use Sportmonks\SoccerAPI\SoccerAPI;
use App\Http\Requests\CustomTopScorer;

class CustomSoccerApi extends SoccerAPI
{
	public function topscorers()
    {
        return new CustomTopScorer();
    }
}
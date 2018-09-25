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
use Carbon\Carbon;

class SoccerAPIController extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	function viewAllLeagues() {
		$soccerAPI = new SoccerAPI();
		$include = 'country,season';

		$leagues = $soccerAPI->leagues()->setInclude($include)->all();

		usort($leagues, function($a, $b) {
			if ($a->country->data->name == $b->country->data->name) {
				return $a->id <=> $b->id;
			}
			return $a->country->data->name <=> $b->country->data->name;
		});

		return view('leagues', ['leagues' => $leagues]);
	}

	function liveScores($type) {
		$soccerAPI = new SoccerAPI();
		$include = 'league,localTeam,visitorTeam';
		$leagues = '2,5,8,24,72,74,78,82,109,163,166,208,214,217,301,307,384,390,564,570,720,732,1325,1326,1353,1371';

		if($type == 'today') {
			$livescores = $soccerAPI->livescores()->setInclude($include)->setLeagues($leagues)->setTimezone('Europe/Amsterdam')->today();
			return view('livescores_today', ['livescores' => $livescores]);
		} elseif($type == 'now') {
			$livescores = $soccerAPI->livescores()->setInclude($include)->setLeagues($leagues)->setTimezone('Europe/Amsterdam')->now();
			return view('livescores_now', ['livescores' => $livescores]);
		} else {
			return '';
		}
	}

	function fixturesBetweenDates(Request $request) {
		$fromDate = $request->query('from', Carbon::now()->subDays(8)->toDateString());
		$toDate = $request->query('to', Carbon::now()->subDays(1)->toDateString());
		$leagues = $request->query('leagues');

		$soccerAPI = new SoccerAPI();
		$include = 'league,localTeam,visitorTeam';

		$fixtures = $soccerAPI->fixtures()->setInclude($include)->setLeagues($leagues)->setTimezone('Europe/Amsterdam')->betweenDates($fromDate, $toDate);

		usort($fixtures, function($a, $b) {
			if ($a->league_id == $b->league_id) {
				if($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
					return $a->id <=> $b->id;
				}
				return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
			}
			return $a->league_id <=> $b->league_id;
		});

		return view('fixtures_between', ['fixtures' => $fixtures, 'fromDate' => $fromDate, 'toDate' => $toDate]);
	}
}

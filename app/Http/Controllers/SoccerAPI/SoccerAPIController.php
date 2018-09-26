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
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use DateTime;

class SoccerAPIController extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

	function viewAllLeagues(Request $request) {
		$soccerAPI = new SoccerAPI();
		$include = 'country,season';

		$leagues = $soccerAPI->leagues()->setInclude($include)->all();

		usort($leagues, function($a, $b) {
			if ($a->country->data->name == $b->country->data->name) {
				return $a->id <=> $b->id;
			}
			return $a->country->data->name <=> $b->country->data->name;
		});

		$paginated_data = self::addPagination($leagues, 20);

		$url = self::removePageParameter($request);

		$paginated_data->setPath($url);

		return view('leagues', ['leagues' => $paginated_data]);
	}

	function livescores($type, Request $request) {
		$soccerAPI = new SoccerAPI();
		$include = 'league,localTeam,visitorTeam';

		if($request->query('leagues') == 'all') {
			$leagues = '';
		} else {
			$leagues = $request->query('leagues', '');
		}

		if($type == 'today') {
			$livescores = $soccerAPI->livescores()->setInclude($include)->setLeagues($leagues)->today();

			usort($livescores, function($a, $b) {
				if ($a->league_id == $b->league_id) {
					if($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
						return $b->time->minute <=> $a->time->minute;
					}
					return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
				}
				return $a->league_id <=> $b->league_id;
			});

			return view('livescores_today', ['livescores' => $livescores]);
		} elseif($type == 'now') {
			$livescores = $soccerAPI->livescores()->setInclude($include)->setLeagues($leagues)->now();

			usort($livescores, function($a, $b) {
				if ($a->league_id == $b->league_id) {
					if($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
						return $b->time->minute <=> $a->time->minute;
					}
					return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
				}
				return $a->league_id <=> $b->league_id;
			});

			return view('livescores_now', ['livescores' => $livescores]);
		} else {
			return '';
		}
	}

	function fixturesByDate(Request $request) {
		if($request->has('day')) {
			if ($request->query('day') == 'yesterday') {
				$fromDate = Carbon::now()->subDays(1)->toDateString();
				$toDate = Carbon::now()->subDays(1)->toDateString();
			} elseif ($request->query('day') == 'tomorrow') {
				$fromDate = Carbon::now()->addDays(1)->toDateString();
				$toDate = Carbon::now()->addDays(1)->toDateString();
			}
		} elseif($request->has('from') && self::validateDate($request->query('from')) == true && $request->has('to') && self::validateDate($request->query('to')) == true) {
			error_log("valid date");
			$fromDate = $request->query('from', Carbon::now()->subDays(1)->toDateString());
			$toDate = $request->query('to', Carbon::now()->subDays(1)->toDateString());
		} else {
			return view('fixtures_between', ['fixtures' => 'Error', 'fromDate' => '', 'toDate' => '']);
		}

		if($request->query('leagues') == 'all') {
			$leagues = '';
		} else {
			$leagues = $request->query('leagues', '');
		}
		$soccerAPI = new SoccerAPI();
		$include = 'league,localTeam,visitorTeam';

		$fixtures = $soccerAPI->fixtures()->setInclude($include)->setLeagues($leagues)->byDate($date);

		usort($fixtures, function($a, $b) {
			if ($a->league_id == $b->league_id) {
				if($a->time->starting_at->date_time == $b->time->starting_at->date_time) {
					return $a->id <=> $b->id;
				}
				return $a->time->starting_at->date_time <=> $b->time->starting_at->date_time;
			}
			return $a->league_id <=> $b->league_id;
		});

		return view('fixtures_by_date', ['fixtures' => $fixtures, 'date' => $date]);
	}

	function addPagination($data, $per_page) {
		$current_page = LengthAwarePaginator::resolveCurrentPage();

		$data_collection = collect($data);

		$current_page_data = $data_collection->slice(($current_page * $per_page) - $per_page, $per_page)->all();

		$paginated_data = new LengthAwarePaginator($current_page_data , count($data_collection), $per_page);

		return $paginated_data;
	}

	function removePageParameter(Request $request)
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

	function validateDate($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
		return $d && $d->format($format) === $date;
	}
}

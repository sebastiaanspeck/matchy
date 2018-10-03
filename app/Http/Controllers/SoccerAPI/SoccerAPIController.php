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
use GuzzleHttp;

class SoccerAPIController extends BaseController
{
	use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function allLeagues(Request $request) {
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

		return view('leagues/leagues', ['leagues' => $paginated_data]);
	}

    /**
     * @param $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function leaguesDetails($id, Request $request) {
		$soccerAPI = new SoccerAPI();
		$include = 'country,season';

		$league = $soccerAPI->leagues()->setInclude($include)->byId($id)->data;

		$standings_raw = $soccerAPI->standings()->bySeasonId($league->current_season_id);

		return view('leagues/leagues_details', ['league' => $league, 'standings_raw' => $standings_raw]);
	}

    /**
     * @param $type
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
     */
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

			return view('livescores/livescores_today', ['livescores' => $livescores]);
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

			return view('livescores/livescores_now', ['livescores' => $livescores]);
		} else {
			return '';
		}
	}

    /**
     * @return int
     */
    function countLivescores() {
    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function fixturesByDate(Request $request) {
		if($request->has('day')) {
			if ($request->query('day') == 'yesterday') {
				$date = Carbon::now()->subDays(1)->toDateString();
			} elseif ($request->query('day') == 'tomorrow') {
				$date = Carbon::now()->addDays(1)->toDateString();
			} elseif ($request->query('day') == 'today') {
				$date = Carbon::now()->toDateString();
			}
		} elseif($request->has('date')) {
			$date = $request->query('date');
		} else {
			$date = Carbon::now()->toDateString();
		}

		if($request->query('leagues') == 'all') {
			$leagues = '';
		} else {
			$leagues = $request->query('leagues', '');
		}
		$soccerAPI = new SoccerAPI();
		$include = 'league,localTeam,visitorTeam';

        /** @var Carbon $date */
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

		return view('fixtures/fixtures_by_date', ['fixtures' => $fixtures, 'date' => $date]);

	}

    /**
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function fixturesDetails($id) {
		$soccerAPI = new SoccerAPI();
		$include = 'league,localTeam,visitorTeam,events';

		$fixture = $soccerAPI->fixtures()->setInclude($include)->byMatchId($id)->data;

		return view('fixtures/fixtures_details', ['fixture' => $fixture]);
	}

    /**
     * @param $id
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    function teamsDetails($id, Request $request) {
    /**
     * @param $data
     * @param $per_page
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    function addPagination($data, $per_page) {
		$current_page = LengthAwarePaginator::resolveCurrentPage();

		$data_collection = collect($data);

		$current_page_data = $data_collection->slice(($current_page * $per_page) - $per_page, $per_page)->all();

		$paginated_data = new LengthAwarePaginator($current_page_data , count($data_collection), $per_page);

		return $paginated_data;
	}

    /**
     * @param \Illuminate\Http\Request $request
     * @return mixed|string
     */
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

    /**
     * @param $date
     * @param string $format
     * @return bool
     */
    function validateDate($date, $format = 'Y-m-d')
	{
		$d = DateTime::createFromFormat($format, $date);
		// The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
		return $d && $d->format($format) === $date;
	}

    /**
     * @param $country
     * @return string
     */
    public static function getAlpha2CountryCode($country) {
        if(strlen($country) == 3) {
            $response = self::APIResponse('https://restcountries.eu/rest/v2/alpha?codes=' . $country .  '&fields=name;alpha2Code;alpha3Code;flag');
        } else {
            $response = self::APIResponse('https://restcountries.eu/rest/v2/name/' . $country . '?fullText=true&fields=name;alpha2Code;alpha3Code;flag');
        }
        return $response[0]->alpha2Code;
    }

    /**
     * @param $url
     * @return mixed|string
     */
    static function APIResponse($url)
    {
        $client = new GuzzleHttp\Client();

        try {
            $response = $client->request('GET', $url);

            return json_decode($response->getBody()->getContents());
        } catch (GuzzleHttp\Exception\ClientException $e) {
            return "ClientException";
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            return "GuzzleException";
        }
    }
}

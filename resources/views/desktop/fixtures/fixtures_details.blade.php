@extends("layouts.default")

@section("style")
    .progress {
        margin-bottom: 0 !important;
    }
    
    .timeline {
        padding: 20px 0;
        position: relative;
    }
    .timeline-nodes {
        padding-bottom: 25px;
        position: relative;
    }
    .timeline-nodes-left {
        left: 100%;
        margin-bottom: 15px;
    }
    .timeline-nodes-right {
        right: 100%;
        margin-bottom: 15px;
        flex-direction: row-reverse;
    }
    .timeline span {
        padding: 5px 15px;
    }
    .timeline-content-span {
        font-weight: lighter;
        background: #0288d1;
    }
    .timeline p, .timeline time {
        color: #0288d1
    }
    .timeline::before {
        content: "";
        display: block;
        position: absolute;
        top: 0;
        left: 50%;
        width: 0;
        border-left: 2px dashed #0288d1;
        height: 100%;
        z-index: 1;
        transform: translateX(-50%);
    }
    .timeline-content {
        position: relative;
        box-shadow: 0px 3px 25px 0px rgba(10, 55, 90, 0.2);
        height: 33px;
    }
    .timeline-nodes-left span {
        text-align: right;
        float: right;
    }
    .timeline-nodes-right span {
        float: left;
    }
    .timeline-nodes-left .timeline-date {
        text-align: left;
    }
    .timeline-nodes-right .timeline-date {
        text-align: right;
    }
    .timeline-nodes-left .timeline-nodes-right .timeline-content::after {
        content: "";
        position: absolute;
        top: 5%;
        width: 0;
        border-left: 10px solid #0288d1;
        border-top: 10px solid transparent;
        border-bottom: 10px solid transparent;
    }
    .timeline-image {
        position: relative;
        z-index: 100;
    }
    .timeline-image::before {
        content: "";
        width: 32px;
        height: 32px;
        border: 2px solid #0288d1;
        border-radius: 50%;
        display: block;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%,-50%);
        background-color: #fff;
        z-index: 1;
    }
    .timeline-image img {
        position: relative;
        z-index: 100;
    }
@endsection

@section("content")
    <div class = "container">
        @php
            $league = $fixture->league->data;
            $homeTeam = $fixture->localTeam->data;
            $awayTeam = $fixture->visitorTeam->data;
            
            if($homeTeam->national_team == true) {
                $homeTeam->name = \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("countries", $homeTeam->name);
            }
            if($awayTeam->national_team == true) {
                $awayTeam->name = \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("countries", $awayTeam->name);
            }
            
            isset($fixture->lineup->data) ? $lineup = $fixture->lineup->data : $lineup = null;
            
            isset($fixture->bench->data) ? $bench = $fixture->bench->data : $bench = null;
            isset($fixture->sidelined->data) ? $sidelined = $fixture->sidelined->data : $sidelined = null;
            
            isset($fixture->events->data) ? $events = $fixture->events->data : $events = null;
            
            isset($fixture->localCoach->data) ? $localCoach = $fixture->localCoach->data : $localCoach = null;
            isset($fixture->visitorCoach->data) ? $visitorCoach = $fixture->visitorCoach->data : $localCoach = null;

            isset($fixture->stats->data) ? $stats = $fixture->stats->data : $stats = null;
            isset($fixture->comments->data) ? $comments = $fixture->comments->data : $comments = null;
            isset($fixture->highlights->data) ? $highlights = $fixture->highlights->data : $highlights = null;

            isset($fixture->referee->data) ? $referee = $fixture->referee->data : $referee = null;
            isset($fixture->venue->data) ? $venue = $fixture->venue->data : $venue = null;

            $favorite_teams = \App\Http\Controllers\Filebase\FilebaseController::getField('favorite_teams');
            $favorite_leagues = \App\Http\Controllers\Filebase\FilebaseController::getField('favorite_leagues');

            $favorite_homeTeam = "far";
            if (in_array($homeTeam->id, $favorite_teams)) {
                $favorite_homeTeam = "fas";
            }

            $favorite_awayTeam = "far";
            if (in_array($awayTeam->id, $favorite_teams)) {
                $favorite_awayTeam = "fas";
            }

            $favorite_league = "far";
            if (in_array($league->id, $favorite_leagues)) {
                $favorite_league = "fas";
            }
        @endphp
        <div id="heading" style="text-align: center">
            <h3><a href="{{ route("setFavoriteLeagues", ["id" => $league->id]) }}"><i class="{{ $favorite_league }} fa-star fa-fw fa-xs" aria-hidden="true" style="transform: translate(10%, -10%);"></i></a>&nbsp;<a href="{{ route("leaguesDetails", ["id" => $league->id]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a></h3>
                <table style="width:100%">
                    @if(@getimagesize($homeTeam->logo_path) && @getimagesize($awayTeam->logo_path))
                        <tr>
                            <td width="49%"><img style="max-height: 200px; max-width: 200px" alt="homeTeam-logo" src={{ $homeTeam->logo_path }}></td>
                            <td width="2%"><h3> - </h3></td>
                            <td width="49%"><img style="max-height: 200px; max-width: 200px" alt="awayTeam-logo" src={{ $awayTeam->logo_path }}></td>
                        </tr>
                    @endif
                    <tr style="height: 10px"></tr>
                    <tr>
                        <td width="49%" style="vertical-align: top"><h5><a href="{{ route("setFavoriteTeams", ["id" => $homeTeam->id]) }}"><i class="{{ $favorite_homeTeam }} fa-star fa-fw" aria-hidden="true"></i></a>&nbsp;<a href="{{ route("teamsDetails", ["id" => $homeTeam->id]) }}">{{ $homeTeam->name }}</a></h5></td>
                        <td></td>
                        <td width="49%" style="vertical-align: top"><h5><i class="{{ $favorite_awayTeam }} fa-star fa-fw" aria-hidden="true"></i>&nbsp;<a href =" {{route("teamsDetails", ["id" => $awayTeam->id])}} ">{{ $awayTeam->name }}</a></h5></td>
                    </tr>
                </table>

            @switch($fixture->time->status)
                @case("FT_PEN")
                    <p style="font-size: x-large; margin: 0;">{{ $fixture->scores->localteam_score }} - {{ $fixture->scores->visitorteam_score }}</p>
                    <p>
                    @if(isset($fixture->scores->localteam_pen_score) && isset($fixture->scores->visitorteam_pen_score))
                        ({{ $fixture->scores->localteam_pen_score }} - {{ $fixture->scores->visitorteam_pen_score }}) {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "penalties") }}
                    @endif
                    </p>
                    @break
                @case("AET")
                    <p style="font-size: x-large; margin: 0;"> {{$fixture->scores->ft_score}} (ET) </p>
                    @break
                @default
                    <p style="font-size: x-large; margin: 0;"> {{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} </p>
                    @break
            @endswitch
            @if(in_array($fixture->time->status, array("LIVE", "HT", "ET", "PEN_LIVE", "AET", "BREAK")))
                @if($fixture->time->status == "HT")
                    <p style="font-size: medium; margin: 0;"> {{$fixture->time->status}} </p>
                @elseif($fixture->time->minute == null && $fixture->time->added_time == null)
                    <p style="font-size: medium; margin: 0;"> {{$fixture->time->status}} - 0&apos;</p>
                @elseif($fixture->time->added_time == null)
                    <p style="font-size: medium; margin: 0;"> {{$fixture->time->status}} - {{$fixture->time->minute}}&apos;</p>
                @elseif(!$fixture->time->added_time == null)
                    <p style="font-size: medium; margin: 0;"> {{$fixture->time->status}} - {{$fixture->time->minute}}+{{$fixture->time->injury_time}}&apos;</p>
                @endif
            @else
                <p style="font-size: medium; margin: 0;"> {{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}} </p>
            @endif

            @if(isset($venue))
                <p style="margin: 0;">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Venue") }}: {{$venue->name}} @if($venue->city !== "")- {{$venue->city}}@endif </p>
            @endif
            @if(isset($referee) && !is_null($referee->fullname))
                <p>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Referee") }}: {{$referee->fullname}} </p>
            @endif
        </div>

        {{-- Nav tabs --}}
        <ul class="nav nav-tabs" id="nav_tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="events-tab" data-toggle="tab" href="#match_summary" role="tab" aria-controls="match_summary" aria-selected="true">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Match Summary") }}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="statistics-tab" data-toggle="tab" href="#statistics" role="tab" aria-controls="statistics" aria-selected="true">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Statistics") }}</a>
            </li>
            @if(count($lineup) > 1)
                <li class="nav-item">
                    <a class="nav-link" id="lineups-tab" data-toggle="tab" href="#lineups" role="tab" aria-controls="lineups" aria-selected="true">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Lineups") }}</a>
                </li>
            @endif
            @if(count($h2h_fixtures) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="head2head-tab" data-toggle="tab" href="#head2head" role="tab" aria-controls="head2head" aria-selected="true">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "H2H") }}</a>
                </li>
            @endif
        </ul>

        {{-- Tab panes --}}
        <div class="tab-content" id="tab_content">
            <div class="tab-pane fade show active" id="match_summary" role="tabpanel" aria-labelledby="match_summary-tab">
                @if(count($events) > 0)
                    <div class="timeline">
                        @foreach($events as $event)
                            @if(in_array($event->type, array("pen_shootout_goal", "pen_shootout_miss")))
                                @continue
                            @endif
                            @if($event->team_id == $homeTeam->id)
                                <div class="row no-gutters justify-content-end justify-content-md-around align-items-start timeline-nodes-left">
                            @else
                                <div class="row no-gutters justify-content-end justify-content-md-around align-items-start timeline-nodes-right">
                            @endif
                                    <div class="col-10 col-md-5 order-3 order-md-1 timeline-content">
                                        <span class="text-light timeline-content-span">{{$event->minute}}@if(!is_null($event->extra_minute))+{{$event->extra_minute}}@endif&apos;</span>
                                        @if($event->type == "substitution")
                                            <span>{{$event->player_name}} <img src="/images/events/substitution-in.svg" alt="substitution-in"> {{$event->related_player_name}} <img src="/images/events/substitution-out.svg" alt="substitution-out"></span>
                                        @else
                                            <span>{{$event->player_name}}</span>
                                        @endif
                                    </div>
                                    <div class="col-2 col-sm-1 px-md-3 order-1 timeline-image text-md-center">
                                        <img src="/images/events/{{$event->type}}.svg" class="img-fluid" alt="img">
                                    </div>
                                    <div class="col-10 col-md-5 order-1"></div>
                                </div>
                                
                        @endforeach
                    </div>
                @else
                    <span style="font-weight: bold">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Match Summary") }} @choice("application.msg_no_data", 1)</span>
                @endif
            </div>
            <div class="tab-pane fade" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
                @if(count($stats) > 0)
                    @php
                        $stats_keys = array("team_id" => "team_id", "shots-total" => trans("application.Total shots"), "shots-ongoal" => trans("application.Shots on goal"), "shots-offgoal" => trans("application.Shots of goal"), "shots-blocked" => trans("application.Blocked shots"), "shots-insidebox" => trans("application.Shots inside box"), "shots-outsidebox" => trans("application.Shots outside box"),
                        "passes-total" => trans("application.Total passes"), "passes-accurate" => trans("application.Accurate passes"), "passes-percentage" => trans("application.Passing percentage"),
                        "attacks-attacks" => trans("application.Total attacks"), "attacks-dangerous_attacks" => trans("application.Dangerous attacks"),
                        "fouls" => trans("application.Fouls"), "corners" => trans("application.Corners"), "offsides" => trans("application.Offside"), "possessiontime" => trans("application.Ball possession"),
                        "yellowcards" => trans("application.Yellow cards"), "redcards" => trans("application.Red cards"), "saves" => trans("application.Saves"), "substitutions" => trans("application.Substitutions"),
                        "goal_kick" => trans("application.Goal kicks"), "goal_attempts" => trans("application.Goal attempts"), "free_kick" => trans("application.Free kicks"), "throw_in" => trans("application.Throw-ins"), "ball_safe" => trans("application.Ball safe"));
                        $stats_array = array();
                        foreach($stats as $stat) {
                            foreach($stat as $key=>$value) {
                                if($key == "fixture_id") {
                                    continue;
                                }
                                if(is_object($value)) {
                                    foreach($value as $stat_key => $stat_value) {
                                        if(is_null($stat_value)) {
                                            continue;
                                        } else {
                                            $k = $stats_keys[$key . "-" . $stat_key];
                                            if(!isset($stats_array[$k])) {
                                                $stats_array[$k] = $stat_value;
                                            } elseif (is_array($stats_array[$k])) {
                                                $stats_array[$k][] = $stat_value;
                                            } else {
                                                $stats_array[$k] = [$stats_array[$k], $stat_value];
                                            }
                                        }

                                    }
                                } else {
                                    if(is_null($value)) {
                                        continue;
                                    } else {
                                        $k = $stats_keys[$key];
                                        if(!isset($stats_array[$k])) {
                                            $stats_array[$k] = $value;
                                        } elseif (is_array($stats_array[$k])) {
                                            $stats_array[$k][] = $value;
                                        } else {
                                            $stats_array[$k] = [$stats_array[$k], $value];
                                        }
                                    }

                                }
                            }
                        }
                        if($stats_array["team_id"][0] != $homeTeam->id) {
                            $home_team_i = 1;
                            $away_team_i = 0;
                        } else {
                            $home_team_i = 0;
                            $away_team_i = 1;
                        }
                        @endphp
                    <table class="table table-sm table-borderless">
                        @foreach ($stats_array as $label => $stat)
                            @if ($label == "team_id")
                                @continue
                            @else
                                @php
                                    $home_stat = $stat[$home_team_i];
                                    $away_stat = $stat[$away_team_i];

                                    $total_stat = $home_stat + $away_stat;

                                    if($home_stat == 0) {
                                        $home_stat_percentage = 0;
                                    } else {
                                        $home_stat_percentage = $home_stat / $total_stat * 100;
                                    }

                                    if($away_stat == 0) {
                                        $away_stat_percentage = 0;
                                    } else {
                                        $away_stat_percentage = $away_stat / $total_stat * 100;
                                    }


                                    if($home_stat < $away_stat) {
                                        $color_home = "bg-danger";
                                        $color_away = "bg-success";
                                    }
                                    elseif ($home_stat > $away_stat) {
                                        $color_home = "bg-success";
                                        $color_away = "bg-danger";
                                    } else {
                                        $color_home = "bg-primary";
                                        $color_away = "bg-primary";
                                    }
                                @endphp
                                    <tr>
                                        @if(in_array($label, array("Ball possession", "Passing percentage", "Balbezit")))
                                            @php
                                                $home_stat = $home_stat . "%";
                                                $away_stat = $away_stat . "%";
                                            @endphp
                                        @endif
                                        <td colspan="2" style="text-align: left">
                                            <span>{{$home_stat}}</span>
                                        </td>
                                        <td colspan="2" style="text-align: center">
                                            <span>{{$label}}</span>
                                        </td>
                                        <td colspan="2" style="text-align: right">
                                            <span>{{$away_stat}}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" style="width: 50%">
                                            <div class="progress" style="direction: rtl; height:10px">
                                                <div class="progress-bar {{$color_home}}" role="progressbar" style="width: {{$home_stat_percentage}}%;" aria-valuenow="{{$home_stat_percentage}}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                        <td colspan="3" style="width: 50%">
                                            <div class="progress" style="direction: ltr; height:10px">
                                                <div class="progress-bar {{$color_away}}" role="progressbar" style="width: {{$away_stat_percentage}}%;" aria-valuenow="{{$away_stat_percentage}}" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                    </tr>
                            @endif
                        @endforeach
                    </table>
                @else
                    <span style="font-weight: bold">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Statistics") }} @choice("application.msg_no_data", 2)</span>
                @endif
            </div>
            <div class="tab-pane fade" id="lineups" role="tabpanel" aria-labelledby="lineups-tab">
                @if(count($lineup) > 0)
                    <table class="table table-borderless table-sm">
                        @if($fixture->formations->localteam_formation && $fixture->formations->visitorteam_formation)
                        <tr style="text-align: center">
                            <td colspan="3">
                                <span>{{$fixture->formations->localteam_formation}}</span>
                            </td>
                            <td style="font-weight: bold">
                                <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Formation") }}</span>
                            </td>
                            <td colspan="3">
                                <span>{{$fixture->formations->visitorteam_formation}}</span>
                            </td>
                        </tr>
                        @endif
                        <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                            <td colspan="7">
                                <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Starting lineups") }}</span>
                            </td>
                        </tr>
                        @php
                            $home_lineup_players = new stdClass();
                            $away_lineup_players = new stdClass();
                            $counter_h = 0;
                            $counter_a = 0;
                            foreach($lineup as $val){
                                $val->team_id == $homeTeam->id ? $val->team = "home" : $val->team = "away";
                                if(isset($val->player->data)) {
                                    $player = ["player_id" => $val->player_id, "number" => $val->number, "common_name" => $val->player->data->common_name, "nationality" => $val->player->data->nationality, "stats" => $val->stats, "events" => array()];
                                } else {
                                    $player = ["player_id" => $val->player_id, "number" => $val->number, "common_name" => $val->player_name, "nationality" => "Unknown", "stats" => $val->stats, "events" => array()];
                                }
                                
                                if($val->stats->goals->scored != 0) {
                                    for($goals = 0; $goals < $val->stats->goals->scored; $goals++) {
                                        array_push($player['events'], "/images/events/goal.svg");
                                    }
                                }
                                if($val->stats->cards->yellowcards != 0) {
                                    for($yellowcards = 0; $yellowcards < $val->stats->cards->yellowcards; $yellowcards++) {
                                        array_push($player['events'], "/images/events/yellowcard.svg");
                                    }
                                }
                                if($val->stats->cards->redcards != 0) {
                                    for($redcards = 0; $redcards < $val->stats->cards->redcards; $redcards++) {
                                        array_push($player['events'], "/images/events/redcard.svg");
                                    }
                                }
                                $player['nationality'] = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($player['nationality']);
                                
                                if($player['nationality'] == "Unknown"){
                                    Log::emergency("Missing nationality for player with id: {$player['player_id']}");
                                }
                                
                                if($val->team == "home") {
                                    $home_lineup_players->{$counter_h} = (object) $player;
                                    $counter_h++;
                                } else {
                                    $away_lineup_players->{$counter_a} = (object) $player;
                                    $counter_a++;
                                }
                            }
                            $home_lineup_players = get_object_vars($home_lineup_players);
                            $away_lineup_players = get_object_vars($away_lineup_players);
                            
                            $counter_h >= $counter_a ? $counter = $counter_h : $counter = $counter_a;
                        @endphp
                        @for($index = 0; $index < $counter; $index++)
                            @php
                                if(isset($home_lineup_players[$index])) {
                                    $home_lineup_player = $home_lineup_players[$index];
                                }
                                if(isset($away_lineup_players[$index])) {
                                    $away_lineup_player = $away_lineup_players[$index];
                                }
                            @endphp
                            @if(isset($home_lineup_player) && isset($away_lineup_player))
                                <tr>
                                    <td style="width:1%">{{$home_lineup_player->number}}</td>
                                    <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_lineup_player->nationality}}.png" alt="homePlayer-nationality"></td>
                                    <td style="text-align: left">
                                        {{$home_lineup_player->common_name}}
                                        @foreach($home_lineup_player->events as $event)
                                            <img src="{{$event}}" alt="stat">
                                        @endforeach
                                    </td>
                                    <td></td>
                                    <td style="text-align: right">{{$away_lineup_player->common_name}}</td>
                                    <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_lineup_player->nationality}}.png" alt="awayPlayer-nationality"></td>
                                    <td style="width:1%">{{$away_lineup_player->number}}</td>
                                </tr>
                            @elseif(isset($home_lineup_player) && !isset($away_lineup_player))
                                <tr>
                                    <td style="width:1%">{{$home_lineup_player->number}}</td>
                                    <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_lineup_player->nationality}}.png" alt="homePlayer-nationality"></td>
                                    <td style="text-align: left">
                                        {{$home_lineup_player->common_name}}
                                        @foreach($home_lineup_player->events as $event)
                                            <img src="{{$event}}" alt="stat">
                                        @endforeach
                                    </td>
                                </tr>
                            @elseif(!isset($home_lineup_player) && isset($away_lineup_player))
                                <tr>
                                    <td colspan="4"></td>
                                    <td style="text-align: right">{{$away_lineup_player->common_name}}</td>
                                    <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_lineup_player->nationality}}.png" alt="awayPlayer-nationality"></td>
                                    <td style="width:1%">{{$away_lineup_player->number}}</td>
                                </tr>
                            @endif
                            @php unset($home_lineup_player); unset($away_lineup_player) @endphp
                        @endfor
                        @if(count($bench) > 0)
                            <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                                <td colspan="7">
                                    <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Substitutes") }}</span>
                                </td>
                            </tr>
                            @php
                                $home_bench_players = new stdClass();
                                $away_bench_players = new stdClass();
                                $counter_h = 0;
                                $counter_a = 0;
                                foreach($bench as $val){
                                    $val->team_id == $homeTeam->id ? $val->team = "home" : $val->team = "away";
                                    if(isset($val->player->data)) {
                                        $player = ["player_id" => $val->player_id, "number" => $val->number, "common_name" => $val->player->data->common_name, "nationality" => $val->player->data->nationality, "stats" => $val->stats, "events" => array()];
                                    } else {
                                        $player = ["player_id" => $val->player_id, "number" => $val->number, "common_name" => $val->player_name, "nationality" => "Unknown", "stats" => $val->stats, "events" => array()];
                                    }
                                    
                                    if($val->stats->goals->scored != 0) {
                                        for($goals = 0; $goals < $val->stats->goals->scored; $goals++) {
                                            array_push($player['events'], "/images/events/goal.svg");
                                        }
                                    }
                                    if($val->stats->cards->yellowcards != 0) {
                                        for($yellowcards = 0; $yellowcards < $val->stats->cards->yellowcards; $yellowcards++) {
                                            array_push($player['events'], "/images/events/yellowcard.svg");
                                        }
                                    }
                                    if($val->stats->cards->redcards != 0) {
                                        for($redcards = 0; $redcards < $val->stats->cards->redcards; $redcards++) {
                                            array_push($player['events'], "/images/events/redcard.svg");
                                        }
                                    }
                                    $player['nationality'] = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($player['nationality']);
                                    
                                    if($player['nationality'] == "Unknown"){
                                        Log::emergency("Missing nationality for player with id: {$player['player_id']}");
                                    }
                                    
                                    if($val->team == "home") {
                                        $home_bench_players->{$counter_h} = (object) $player;
                                        $counter_h++;
                                    } else {
                                        $away_bench_players->{$counter_a} = (object) $player;
                                        $counter_a++;
                                    }
                                }
                                $home_bench_players = get_object_vars($home_bench_players);
                                $away_bench_players = get_object_vars($away_bench_players);
                                
                                $counter_h >= $counter_a ? $counter = $counter_h : $counter = $counter_a;
                            @endphp
                            @for($index = 0; $index < $counter; $index++)
                                @php
                                    if(isset($home_bench_players[$index])) {
                                        $home_bench_player = $home_bench_players[$index];
                                    }
                                    if(isset($away_bench_players[$index])) {
                                        $away_bench_player = $away_bench_players[$index];
                                    }
                                @endphp
                                @if(isset($home_bench_player) && isset($away_bench_player))
                                    <tr>
                                        <td style="width:1%">{{$home_bench_player->number}}</td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_bench_player->nationality}}.png" alt="homePlayer-nationality"></td>
                                        <td style="text-align: left">
                                            {{$home_bench_player->common_name}}
                                            @foreach($home_bench_player->events as $event)
                                                <img src="{{$event}}" alt="stat">
                                            @endforeach
                                        </td>
                                        <td></td>
                                        <td style="text-align: right">{{$away_bench_player->common_name}}</td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_bench_player->nationality}}.png" alt="awayPlayer-nationality"></td>
                                        <td style="width:1%">{{$away_bench_player->number}}</td>
                                    </tr>
                                @elseif(isset($home_bench_player) && !isset($away_bench_player))
                                    <tr>
                                        <td style="width:1%">{{$home_bench_player->number}}</td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_bench_player->nationality}}.png" alt="homePlayer-nationality"></td>
                                        <td style="text-align: left">
                                            {{$home_bench_player->common_name}}
                                            @foreach($home_bench_player->events as $event)
                                                <img src="{{$event}}" alt="stat">
                                            @endforeach
                                        </td>
                                    </tr>
                                @elseif(!isset($home_bench_player) && isset($away_bench_player))
                                    <tr>
                                        <td colspan="4"></td>
                                        <td style="text-align: right">{{$away_bench_player->common_name}}</td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_bench_player->nationality}}.png" alt="awayPlayer-nationality"></td>
                                        <td style="width:1%">{{$away_bench_player->number}}</td>
                                    </tr>
                                @endif
                                @php unset($home_bench_player); unset($away_bench_player) @endphp
                            @endfor
                        @endif
                        @if(count($sidelined) > 0)
                            <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                                <td colspan="7">
                                    <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Missing players") }}</span>
                                </td>
                            </tr>
                            @php
                                $home_sidelined_players = new stdClass();
                                $away_sidelined_players = new stdClass();
                                $counter_h = 0;
                                $counter_a = 0;
                                foreach($sidelined as $val){
                                    $val->team_id == $homeTeam->id ? $val->team = "home" : $val->team = "away";
                                    $player = array("player_id" => $val->player_id, "common_name" => $val->player->data->common_name, "nationality" => $val->player->data->nationality, "reason" => $val->reason);
                                    
                                    $player['nationality'] = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($player['nationality']);
                                    
                                    if($player['nationality'] == "Unknown"){
                                        Log::emergency("Missing nationality for player with id: {$player['player_id']}");
                                    }
                                    
                                    if($val->team == "home") {
                                        $home_sidelined_players->{$counter_h} = (object) $player;
                                        $counter_h++;
                                    } else {
                                        $away_sidelined_players->{$counter_a} = (object) $player;
                                        $counter_a++;
                                    }
                                }
                                $home_sidelined_players = get_object_vars($home_sidelined_players);
                                $away_sidelined_players = get_object_vars($away_sidelined_players);

                                $counter_h >= $counter_a ? $counter = $counter_h : $counter = $counter_a;
                            @endphp
                            @for($index = 0; $index < $counter; $index++)
                                @php
                                    if(isset($home_sidelined_players[$index])) {
                                        $home_sidelined_player = $home_sidelined_players[$index];
                                    }
                                    if(isset($away_sidelined_players[$index])) {
                                        $away_sidelined_player = $away_sidelined_players[$index];
                                    }
                                @endphp
                                @if(isset($home_sidelined_player) && isset($away_sidelined_player))
                                    <tr>
                                        <td></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_sidelined_player->nationality}}.png" alt="homePlayer-nationality"></td>
                                        <td style="text-align: left">{{$home_sidelined_player->common_name}} <span style="color: #A9A9A9">({{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("injuries", $home_sidelined_player->reason) }})</span></td>
                                        <td></td>
                                        <td style="text-align: right">{{$away_sidelined_player->common_name}} <span style="color: #A9A9A9">({{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("injuries", $away_sidelined_player->reason) }})</span></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_sidelined_player->nationality}}.png" alt="awayPlayer-nationality"></td>
                                    </tr>
                                @elseif(isset($home_sidelined_player) && !isset($away_sidelined_player))
                                    <tr>
                                        <td></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_sidelined_player->nationality}}.png" alt="homePlayer-nationality"></td>
                                        <td style="text-align: left">{{$home_sidelined_player->common_name}} <span style="color: #A9A9A9">({{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("injuries", $home_sidelined_player->reason) }})</span></td>
                                    </tr>
                                @elseif(!isset($home_sidelined_player) && isset($away_sidelined_player))
                                    <tr>
                                        <td colspan="4"></td>
                                        <td style="text-align: right">{{$away_sidelined_player->common_name}} <span style="color: #A9A9A9">({{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("injuries", $away_sidelined_player->reason) }})</span></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_sidelined_player->nationality}}.png" alt="awayPlayer-nationality"></td>
                                    </tr>
                                @endif
                                @php unset($home_sidelined_player); unset($away_sidelined_player) @endphp
                            @endfor
                        @endif
                        @if(isset($localCoach) || isset($visitorCoach))
                            @php
                                $localCoach->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($localCoach->nationality);
                                $visitorCoach->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($visitorCoach->nationality);
                            
                                if($localCoach->nationality == "Unknown") {
                                    Log::emergency("Missing nationality for coach with id: " . $localCoach->coach_id);
                                }
                                
                                if($visitorCoach->nationality == "Unknown") {
                                    Log::emergency("Missing nationality for coach with id: " . $visitorCoach->coach_id);
                                }
                            @endphp
                        <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                            <td colspan="7">
                                <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Coaches") }}</span>
                            </td>
                        </tr>
                        <tr>
                            @if(isset($localCoach) && isset($visitorCoach))
                                <td style="width: 1%"></td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$localCoach->nationality}}.png" alt="localCoach-nationality"></td>
                                <td style="text-align: left">{{$localCoach->common_name}}</td>
                                <td></td>
                                <td style="text-align: right">{{$visitorCoach->common_name}}</td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$visitorCoach->nationality}}.png" alt="visitiorCoach-nationality"></td>
                                <td style="width: 1%"></td>
                            @elseif(isset($localCoach) && !isset($visitorCoach))
                                <td style="width: 1%"></td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$localCoach->nationality}}.png" alt="localCoach-nationality"></td>
                                <td style="text-align: left">{{$localCoach->common_name}}</td>
                            @elseif(!isset($localCoach) && isset($visitorCoach))
                                <td colspan="4"></td>
                                <td style="text-align: right">{{$visitorCoach->common_name}}</td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$visitorCoach->nationality}}.png" alt="visitorCoach-nationality"></td>
                                <td style="width: 1%"></td>
                            @endif
                        </tr>
                        @endif
                    </table>
                @else
                    <span style="font-weight: bold">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Lineups") }} @choice("application.msg_no_data", 2)</span>
                @endif
            </div>
            <div class="tab-pane fade" id="head2head" role="tabpanel" aria-labelledby="head2head-tab">
                @if(count($h2h_fixtures) > 0)
                    @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; $last_season_name = ''; @endphp
                    @foreach($h2h_fixtures as $h2h_fixture)
                        @php
                            $league = $h2h_fixture->league->data;
                            $homeTeam = $h2h_fixture->localTeam->data;
                            $awayTeam = $h2h_fixture->visitorTeam->data;
                            
                            if($homeTeam->national_team == true) {
                                $homeTeam->name = \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("countries", $homeTeam->name);
                            }
                            if($awayTeam->national_team == true) {
                                $awayTeam->name = \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("countries", $awayTeam->name);
                            }
                            
                            if(in_array($h2h_fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                                switch($h2h_fixture->time->status) {
                                    case("FT_PEN"):
                                        if($h2h_fixture->scores->localteam_pen_score > $h2h_fixture->scores->visitorteam_pen_score) {
                                            $homeTeamClass = "won-team";
                                            $awayTeamClass = "lost-team";
                                        } elseif($h2h_fixture->scores->localteam_pen_score == $h2h_fixture->scores->visitorteam_pen_score) {
                                            $homeTeamClass = $awayTeamClass = "draw-team";
                                        } elseif($h2h_fixture->scores->localteam_pen_score < $h2h_fixture->scores->visitorteam_pen_score) {
                                            $homeTeamClass = "lost-team";
                                            $awayTeamClass = "won-team";
                                        }
                                        break;
                                    default:
                                        if($h2h_fixture->scores->localteam_score > $h2h_fixture->scores->visitorteam_score) {
                                            $homeTeamClass = "won-team";
                                            $awayTeamClass = "lost-team";
                                        } elseif($h2h_fixture->scores->localteam_score == $h2h_fixture->scores->visitorteam_score) {
                                            $homeTeamClass = $awayTeamClass = "draw-team";
                                        } elseif($h2h_fixture->scores->localteam_score < $h2h_fixture->scores->visitorteam_score) {
                                            $homeTeamClass = "lost-team";
                                            $awayTeamClass = "won-team";
                                        }
                                        break;
                                }
                            } else {
                                $homeTeamClass = $awayTeamClass = "";
                            }
                            
                            switch($h2h_fixture->time->status) {
                                case("FT_PEN"):
                                    $scoreLine = $h2h_fixture->scores->localteam_score . " - " . $h2h_fixture->scores->visitorteam_score ."\n(" . $h2h_fixture->scores->localteam_pen_score . " - " . $h2h_fixture->scores->visitorteam_pen_score . ")";
                                    break;
                                case("AET"):
                                    $scoreLine = $h2h_fixture->scores->localteam_score . " - " . $h2h_fixture->scores->visitorteam_score . "\n(ET)";
                                    break;
                                case("NS"):
                                    $scoreLine = " - ";
                                    break;
                                default:
                                    $scoreLine = $h2h_fixture->scores->localteam_score . " - " . $h2h_fixture->scores->visitorteam_score;
                                    break;
                            }

                            $homeTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($homeTeam->logo_path, 16, 16);
                            $awayTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($awayTeam->logo_path, 16, 16);
                        @endphp
                        @if($h2h_fixture->league_id == $last_league_id)
                            @if(isset($h2h_fixture->round))
                                @if($last_round_id !== $h2h_fixture->round->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                            @if($h2h_fixture->stage->data->name !== "Regular Season")
                                                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $h2h_fixture->stage->data->name) }}
                                            @endif
                                            {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$h2h_fixture->round->data->name}}
                                            @if($h2h_fixture->season->data->name != $last_season_name)
                                                - {{ $h2h_fixture->season->data->name }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @elseif($last_stage_id !== $h2h_fixture->stage->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                        {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $h2h_fixture->stage->data->name) }}
                                        @if($h2h_fixture->season->data->name != $last_season_name)
                                            - {{ $h2h_fixture->season->data->name }}
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr>
                                <td scope="row">{{date($date_format . " H:i", strtotime($h2h_fixture->time->starting_at->date_time))}}</td>
                                {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class={{$homeTeamClass}}>{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                                {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                                <td scope="row" style="text-align: center">{!! nl2br(e($scoreLine)) !!}</td>
                                {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class={{$awayTeamClass}}><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                                <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
                            </tr>
                        @else
                            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                            <table class="table table-striped table-light table-sm" width="100%">
                                @if(isset($h2h_fixture->round))
                                    @if($last_round_id !== $h2h_fixture->round->data->name)
                                        <tr>
                                            <td style="font-weight: bold; text-align: center; background-color: #bdbdbd;" colspan="5">
                                                <a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a> -
                                                @if($h2h_fixture->stage->data->name !== "Regular Season")
                                                    {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $h2h_fixture->stage->data->name) }} -
                                                @endif
                                                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$h2h_fixture->round->data->name}}
                                                @if($h2h_fixture->season->data->name != $last_season_name || $h2h_fixture->league_id !== $last_league_id)
                                                    - {{ $h2h_fixture->season->data->name }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                @elseif($last_stage_id !== $h2h_fixture->stage->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #bdbdbd;" colspan="5">
                                            <a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a> -
                                            {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $h2h_fixture->stage->data->name) }}
                                            @if($h2h_fixture->season->data->name != $last_season_name || $h2h_fixture->league_id !== $last_league_id)
                                                - {{ $h2h_fixture->season->data->name }}
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                                <thead style="visibility: collapse">
                                    <tr>
                                        <th scope="col" width="20%"></th>
                                        <th scope="col" width="26.25%"></th>
                                        <th scope="col" width="7.5%"></th>
                                        <th scope="col" width="26.25%"></th>
                                        <th scope="col" width="20%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td scope="row">{{date($date_format . " H:i", strtotime($h2h_fixture->time->starting_at->date_time))}}</td>
                                    {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                    <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class={{$homeTeamClass}}>{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                                    {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                                    <td scope="row" style="text-align: center">{!! nl2br(e($scoreLine)) !!}</td>
                                    {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                    <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class={{$awayTeamClass}}><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                                    <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
                                </tr>
                                @endif
                                @php $last_league_id = $h2h_fixture->league_id; if(isset($h2h_fixture->round)) {$last_round_id = $h2h_fixture->round->data->name;} $last_stage_id = $h2h_fixture->stage->data->name; $last_season_name = $h2h_fixture->season->data->name; @endphp
                    @endforeach
                    </tbody>
                </table>
                @else
                    <span style="font-weight: bold">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "H2H") }} @choice("application.msg_no_data", 2)</span>
                @endif
            </div>
        </div>
        </div>
    </div>
@endsection
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
            $homeTeamId = $homeTeam->id;
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
        @endphp


        <div id="heading" style="text-align: center">
            <h1><a href=" {{route("leaguesDetails", ["id" => $league->id])}} "> {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }} </a></h1>
                <table style="width:100%">
                    <tr>
                        <td width="49%"><img style="max-height: 200px; max-width: 200px" alt="homeTeam-logo" src={{$homeTeam->logo_path}}></td>
                        <td width="2%"><h1> - </h1></td>
                        <td width="49%"><img style="max-height: 200px; max-width: 200px" alt="awayTeam-logo" src={{$awayTeam->logo_path}}></td>
                    </tr>
                    <tr style="height: 10px"></tr>
                    <tr>
                        <td width="49%" style="vertical-align: top"><h5><a href =" {{route("teamsDetails", ["id" => $homeTeam->id])}} "> {{$homeTeam->name}} </a></h5></td>
                        <td></td>
                        <td width="49%" style="vertical-align: top"><h5><a href =" {{route("teamsDetails", ["id" => $awayTeam->id])}} "> {{$awayTeam->name}} </a></h5></td>
                    </tr>
                </table>

            @switch($fixture->time->status)
                @case("FT_PEN")
                    <span style="font-size: x-large"> {{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} </span><br>
                    <span>
                    @if(isset($fixture->scores->localteam_pen_score) && isset($fixture->scores->visitorteam_pen_score))
                        ({{$fixture->scores->localteam_pen_score}} - {{$fixture->scores->visitorteam_pen_score}}) {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "penalties") }}
                    @endif
                    </span>
                    @break
                @case("AET")
                    <span style="font-size: x-large"> {{$fixture->scores->ft_score}} (ET) </span>
                    @break
                @default
                    <span style="font-size: x-large"> {{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} </span>
                    @break
            @endswitch
            <br>
            @if(in_array($fixture->time->status, array("LIVE", "HT", "ET", "PEN_LIVE", "AET", "BREAK")))
                @if($fixture->time->status == "HT")
                    <span style="font-size: medium"> {{$fixture->time->status}} </span>
                @elseif($fixture->time->minute == null && $fixture->time->added_time == null)
                    <span style="font-size: medium"> {{$fixture->time->status}} - 0&apos;</span>
                @elseif($fixture->time->added_time == null)
                    <span style="font-size: medium"> {{$fixture->time->status}} - {{$fixture->time->minute}}&apos;</span>
                @elseif(!$fixture->time->added_time == null)
                    <span style="font-size: medium"> {{$fixture->time->status}} - {{$fixture->time->minute}}+{{$fixture->time->injury_time}}&apos;</span>
                @endif
            @else
                <span style="font-size: medium"> {{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}} </span>
            @endif
            <br>

            @if(isset($venue))
                <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Venue") }}: {{$venue->name}} - {{$venue->city}} </span>
                <br>
            @endif
            @if(isset($referee))
                <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Referee") }}: {{$referee->fullname}} </span>
                <br>
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
                            @if($event->team_id == $homeTeamId)
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
                        @for($index = 0; $index < count($lineup)/2; $index++)
                            @php
                                $home_player = $lineup[$index];
                                $away_player = $lineup[$index+count($lineup)/2];

                                $home_player_stats = array();
                                $away_player_stats = array();

                                if($home_player->stats->goals->scored != 0) {
                                    for($goals = 0; $goals < $home_player->stats->goals->scored; $goals++) {
                                        array_push($home_player_stats, "/images/events/goal.svg");
                                    }
                                }
                                if($home_player->stats->cards->yellowcards != 0) {
                                    for($yellowcards = 0; $yellowcards < $home_player->stats->cards->yellowcards; $yellowcards++) {
                                        array_push($home_player_stats, "/images/events/yellowcard.svg");
                                    }
                                }
                                if($home_player->stats->cards->redcards != 0) {
                                    for($redcards = 0; $redcards < $home_player->stats->cards->redcards; $redcards++) {
                                        array_push($home_player_stats, "/images/events/redcard.svg");
                                    }
                                }

                                if($away_player->stats->goals->scored != 0) {
                                    for($goals = 0; $goals < $away_player->stats->goals->scored; $goals++) {
                                        array_push($away_player_stats, "/images/events/goal.svg");
                                    }
                                }
                                if($away_player->stats->cards->yellowcards != 0) {
                                    for($yellowcards = 0; $yellowcards < $away_player->stats->cards->yellowcards; $yellowcards++) {
                                        array_push($away_player_stats, "/images/events/yellowcard.svg");
                                    }
                                }
                                if($away_player->stats->cards->redcards != 0) {
                                    for($redcards = 0; $redcards < $away_player->stats->cards->redcards; $redcards++) {
                                        array_push($away_player_stats, "/images/events/redcard.svg");
                                    }
                                }

                                if(isset($home_player->player->data)) {
                                    $home_player_nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($home_player->player->data->nationality);
                                    $home_player_common_name = $home_player->player->data->common_name;
                                } else {
                                    $home_player_nationality = "Unknown";
                                    $home_player_common_name = $home_player->player_name;
                                }

                                if(isset($away_player->player->data)) {
                                    $away_player_nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($away_player->player->data->nationality);
                                    $away_player_common_name = $away_player->player->data->common_name;
                                } else {
                                    $away_player_nationality = "Unknown";
                                    $away_player_common_name = $away_player->player_name;
                                }
                                
                                if($home_player_nationality == "Unknown" && !isset($home_player->player->data)) {
                                    Log::emergency("Missing nationality for player with id: " . $home_player->player_id);
                                } elseif($away_player_nationality == "Unknown" && !isset($away_player->player->data)){
                                    Log::emergency("Missing nationality for player with id: " . $away_player->player_id);
                                }
                            @endphp
                            <tr>
                                @if(isset($home_player) && isset($away_player))
                                    <td style="width:1%">{{$home_player->number}}</td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png" alt="homePlayer-nationality"></td>
                                    <td style="text-align: left">
                                        {{$home_player_common_name}}
                                        @foreach($home_player_stats as $stat)
                                            <img src="{{$stat}}" alt="stat">
                                        @endforeach
                                    </td>
                                    <td style="width: 1%"></td>
                                    <td style="text-align: right">
                                        @foreach($away_player_stats as $stat)
                                            <img src="{{$stat}}" alt="stat">
                                        @endforeach
                                        {{$away_player_common_name}}
                                    </td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png" alt="awayPlayer-nationality"></td>
                                    <td style="width:1%">{{$away_player->number}}</td>
                                @elseif(isset($home_player) && !isset($away_player))
                                    <td style="width:1%">{{$home_player->number}}</td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png" alt="homePlayer-nationality"></td>
                                    <td style="text-align: left">
                                        {{$home_player_common_name}}
                                        @foreach($home_player_stats as $stat)
                                            <img src="{{$stat}}" alt="stat">
                                        @endforeach
                                    </td>
                                @elseif(!isset($home_player) && isset($away_player))
                                    <td colspan="5"></td>
                                    <td style="text-align: right">
                                        @foreach($away_player_stats as $stat)
                                            <img src="{{$stat}}" alt="stat">
                                        @endforeach
                                        {{$away_player_common_name}}
                                    </td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png" alt="awayPlayer-nationality"></td>
                                    <td style="width:1%">{{$away_player->number}}</td>
                                @endif
                            </tr>
                        @endfor
                        @if(count($bench) > 0)
                            <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                                <td colspan="7">
                                    <span>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Substitutes") }}</span>
                                </td>
                            </tr>
                            @for($index = 0; $index < count($bench)/2; $index++)
                                @php
                                    $home_player = $bench[$index];
                                    $away_player = $bench[$index+count($bench)/2];
    
                                    $home_player_stats = array();
                                    $away_player_stats = array();
                                    
                                    if($home_player->stats->goals->scored != 0) {
                                        for($goals = 0; $goals < $home_player->stats->goals->scored; $goals++) {
                                            array_push($home_player_stats, "/images/events/goal.svg");
                                        }
                                    }
                                    if($home_player->stats->cards->yellowcards != 0) {
                                        for($yellowcards = 0; $yellowcards < $home_player->stats->cards->yellowcards; $yellowcards++) {
                                            array_push($home_player_stats, "/images/events/yellowcard.svg");
                                        }
                                    }
                                    if($home_player->stats->cards->redcards != 0) {
                                        for($redcards = 0; $redcards < $home_player->stats->cards->redcards; $redcards++) {
                                            array_push($home_player_stats, "/images/events/redcard.svg");
                                        }
                                    }
    
                                    if($away_player->stats->goals->scored != 0) {
                                        for($goals = 0; $goals < $away_player->stats->goals->scored; $goals++) {
                                            array_push($away_player_stats, "/images/events/goal.svg");
                                        }
                                    }
                                    if($away_player->stats->cards->yellowcards != 0) {
                                        for($yellowcards = 0; $yellowcards < $away_player->stats->cards->yellowcards; $yellowcards++) {
                                            array_push($away_player_stats, "/images/events/yellowcard.svg");
                                        }
                                    }
                                    if($away_player->stats->cards->redcards != 0) {
                                        for($redcards = 0; $redcards < $away_player->stats->cards->redcards; $redcards++) {
                                            array_push($away_player_stats, "/images/events/redcard.svg");
                                        }
                                    }
                                    if(isset($home_player->player->data)) {
                                        $home_player_nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($home_player->player->data->nationality);
                                        $home_player_common_name = $home_player->player->data->common_name;
                                    } else {
                                        $home_player_nationality = "Unknown";
                                        $home_player_common_name = $home_player->player_name;
                                    }
    
                                    if(isset($away_player->player->data)) {
                                        $away_player_nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($away_player->player->data->nationality);
                                        $away_player_common_name = $away_player->player->data->common_name;
                                    } else {
                                        $away_player_nationality = "Unknown";
                                        $away_player_common_name = $away_player->player_name;
                                    }
                                    
                                    if($home_player_nationality == "Unknown") {
                                        Log::emergency("Missing nationality for player with id: " . $home_player->player_id);
                                    } elseif($away_player_nationality == "Unknown"){
                                        Log::emergency("Missing nationality for player with id: " . $away_player->player_id);
                                    }
                                @endphp
                                <tr>
                                    @if(isset($home_player) && isset($away_player))
                                        <td style="width:1%">{{$home_player->number}}</td>
                                        <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png" alt="homePlayer-nationality"></td>
                                        <td style="text-align: left; width:50%">
                                            {{$home_player_common_name}}
                                            @foreach($home_player_stats as $stat)
                                                <img src="{{$stat}}" alt="stat">
                                            @endforeach
                                        </td>
                                        <td style="width: 1%"></td>
                                        <td style="text-align: right; width: 50%">
                                            @foreach($away_player_stats as $stat)
                                                <img src="{{$stat}}" alt="stat">
                                            @endforeach
                                            {{$away_player_common_name}}
                                        </td>
                                        <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png" alt="awayPlayer-nationality"></td>
                                        <td style="width:1%">{{$away_player->number}}</td>
                                    @elseif(isset($home_player) && !isset($away_player))
                                        <td style="width:1%">{{$home_player->number}}</td>
                                        <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png" alt="homePlayer-nationality"></td>
                                        <td style="text-align: left; width:50%">
                                            {{$home_player_common_name}}
                                            @foreach($home_player_stats as $stat)
                                                <img src="{{$stat}}" alt="stat">
                                            @endforeach
                                        </td>
                                    @elseif(!isset($home_player) && isset($away_player))
                                        <td colspan="4"></td>
                                        <td style="text-align: right; width: 50%">
                                            @foreach($away_player_stats as $stat)
                                                <img src="{{$stat}}" alt="stat">
                                            @endforeach
                                            {{$away_player_common_name}}
                                        </td>
                                        <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png" alt="awayPlayer-nationality"></td>
                                        <td style="width:1%">{{$away_player->number}}</td>
                                    @endif
                                </tr>
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
                                        $home_sidelined_player->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($home_sidelined_player->nationality);
                                        
                                        if($home_sidelined_player->nationality == "Unknown") {
                                            Log::emergency("Missing nationality for player with id: " . $home_sidelined_player->player_id);
                                        }
                                    }
                                    if(isset($away_sidelined_players[$index])) {
                                        $away_sidelined_player = $away_sidelined_players[$index];
                                        $away_sidelined_player->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($away_sidelined_player->nationality);
                                        
                                        if($away_sidelined_player->nationality == "Unknown"){
                                            Log::emergency("Missing nationality for player with id: " . $home_sidelined_player->player_id);
                                        }
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
                                            $winningTeam = $homeTeam->name;
                                        } elseif($h2h_fixture->scores->localteam_pen_score == $h2h_fixture->scores->visitorteam_pen_score) {
                                            $winningTeam = "draw";
                                        } elseif($h2h_fixture->scores->localteam_pen_score < $h2h_fixture->scores->visitorteam_pen_score) {
                                            $winningTeam = $awayTeam->name;
                                        }
                                        break;
                                    default:
                                        if($h2h_fixture->scores->localteam_score > $h2h_fixture->scores->visitorteam_score) {
                                            $winningTeam = $homeTeam->name;
                                        } elseif($h2h_fixture->scores->localteam_score == $h2h_fixture->scores->visitorteam_score) {
                                            $winningTeam = "draw";
                                        } elseif($h2h_fixture->scores->localteam_score < $h2h_fixture->scores->visitorteam_score) {
                                            $winningTeam = $awayTeam->name;
                                        }
                                        break;
                                }
                            } else {
                                $winningTeam = "TBD";
                            }
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
                                {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                @switch($winningTeam)
                                    @case($homeTeam->name)
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class="won-team">{{$homeTeam->name}}</a></td>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class="lost-team">{{$awayTeam->name}}</a></td>
                                    @break
                                    @case($awayTeam->name)
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class="lost-team">{{$homeTeam->name}}</a></td>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class="won-team">{{$awayTeam->name}}</a></td>
                                    @break
                                    @case("draw")
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class="draw-team">{{$homeTeam->name}}</a></td>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class="draw-team">{{$awayTeam->name}}</a></td>
                                    @break
                                    @default
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}</a></td>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}">{{$awayTeam->name}}</a></td>
                                    @break
                                @endswitch
                    
                                {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                                @switch($h2h_fixture->time->status)
                                    @case("FT_PEN")
                                    <td scope="row">{{$h2h_fixture->scores->localteam_score}} - {{$h2h_fixture->scores->visitorteam_score}}
                                        @if(is_null($h2h_fixture->scores->localteam_pen_score) || is_null($h2h_fixture->scores->visitorteam_pen_score))
                                            (PEN)
                                        @else
                                             ({{$h2h_fixture->scores->localteam_pen_score}} - {{$h2h_fixture->scores->visitorteam_pen_score}})
                                        @endif
                                    </td>
                                    @break
                                    @case("AET")
                                    <td scope="row">{{$h2h_fixture->scores->localteam_score}} - {{$h2h_fixture->scores->visitorteam_score}} (ET)</td>
                                    @break
                                    @default
                                    <td scope="row">{{$h2h_fixture->scores->localteam_score}} - {{$h2h_fixture->scores->visitorteam_score}}</td>
                                    @break
                                @endswitch
                    
                                <td scope="row">{{date($date_format . " H:i", strtotime($h2h_fixture->time->starting_at->date_time))}}
                                    @if(in_array($h2h_fixture->time->status, array("LIVE", "HT", "ET")))
                                        <span class="live">{{ $h2h_fixture->time->status }}</span>
                                    @endif
                                </td>
                                <td scope="row"><a href="{{route("fixturesDetails", ["id" => $h2h_fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
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
                                        <th scope="col" width="35%"></th>
                                        <th scope="col" width="35%"></th>
                                        <th scope="col" width="10%"></th>
                                        <th scope="col" width="17%"></th>
                                        <th scope="col" width="3%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                    @switch($winningTeam)
                                        @case($homeTeam->name)
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class="won-team">{{$homeTeam->name}}</a></td>
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class="lost-team">{{$awayTeam->name}}</a></td>
                                        @break
                                        @case($awayTeam->name)
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class="lost-team">{{$homeTeam->name}}</a></td>
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class="won-team">{{$awayTeam->name}}</a></td>
                                        @break
                                        @case("draw")
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class="draw-team">{{$homeTeam->name}}</a></td>
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class="draw-team">{{$awayTeam->name}}</a></td>
                                        @break
                                        @default
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}</a></td>
                                        <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}">{{$awayTeam->name}}</a></td>
                                        @break
                                    @endswitch
                        
                                    {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                                    @switch($h2h_fixture->time->status)
                                        @case("FT_PEN")
                                        <td scope="row">{{$h2h_fixture->scores->localteam_score}} - {{$h2h_fixture->scores->visitorteam_score}}
                                            @if(is_null($h2h_fixture->scores->localteam_pen_score) || is_null($h2h_fixture->scores->visitorteam_pen_score))
                                                (PEN)
                                            @else
                                                 ({{$h2h_fixture->scores->localteam_pen_score}} - {{$h2h_fixture->scores->visitorteam_pen_score}})
                                            @endif
                                        </td>
                                        @break
                                        @case("AET")
                                        <td scope="row">{{$h2h_fixture->scores->localteam_score}} - {{$h2h_fixture->scores->visitorteam_score}} (ET)</td>
                                        @break
                                        @default
                                        <td scope="row">{{$h2h_fixture->scores->localteam_score}} - {{$h2h_fixture->scores->visitorteam_score}}</td>
                                        @break
                                    @endswitch
                                    <td scope="row">{{date($date_format . " H:i", strtotime($h2h_fixture->time->starting_at->date_time))}}</td>
                                    <td scope="row"><a href="{{route("fixturesDetails", ["id" => $h2h_fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
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
@endsection
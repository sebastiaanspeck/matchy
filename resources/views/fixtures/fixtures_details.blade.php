@extends("layouts.default")

@section("style")
    .progress {
        margin-bottom: 0 !important;
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
                $homeTeam->name = trans('countries.' . $homeTeam->name);
            }
            if($awayTeam->national_team == true) {
                $awayTeam->name = trans('countries.' . $awayTeam->name);
            }
            
            $events = $fixture->events->data;

            $lineup = $fixture->lineup->data;
            $bench = $fixture->bench->data;
            $sidelined = $fixture->sidelined->data;
            isset($fixture->localCoach->data) ? $localCoach = $fixture->localCoach->data : $localCoach = null;
            isset($fixture->visitorCoach->data) ? $visitorCoach = $fixture->visitorCoach->data : $localCoach = null;

            isset($fixture->stats->data) ? $stats = $fixture->stats->data :$stats = null;
            isset($fixture->comments->data) ? $comments = $fixture->comments->data : $comments = null;
            isset($fixture->highlights->data) ? $highlights = $fixture->highlights->data : $highlights = null;

            isset($fixture->referee->data) ? $referee = $fixture->referee->data : $referee = null;
            isset($fixture->venue->data) ? $venue = $fixture->venue->data : $venue = null;
        @endphp


        <div id="heading" style="text-align: center">
            <h1><a href=" {{route("leaguesDetails", ["id" => $league->id])}} "> @lang('leagues.' . $league->name) </a></h1>
                <table style="width:100%">
                    <tr>
                        <td width="49%"><img style="max-height: 200px; max-width: 200px" src={{$homeTeam->logo_path}}></td>
                        <td width="2%"><h1> - </h1></td>
                        <td width="49%"><img style="max-height: 200px; max-width: 200px" src={{$awayTeam->logo_path}}></td>
                    </tr>
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
                        ({{$fixture->scores->localteam_pen_score}} - {{$fixture->scores->visitorteam_pen_score}}) @lang('application.penalties')
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
                    <span style="font-size: medium"> {{$fixture->time->status}} - {{$fixture->time->minute}}&apos; +{{$fixture->time->injury_time}} </span>
                @endif
            @else
                <span style="font-size: medium"> {{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}} </span>
            @endif
            <br>

            @if(isset($venue))
                <span>@lang("application.Venue"): {{$venue->name}} - {{$venue->city}} </span>
                <br>
            @endif
            @if(isset($referee))
                <span>@lang("application.Referee"): {{$referee->fullname}} </span>
                <br>
            @endif
        </div>

        {{-- Nav tabs --}}
        <ul class="nav nav-tabs" id="nav_tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="events-tab" data-toggle="tab" href="#match_summary" role="tab" aria-controls="match_summary" aria-selected="true">@lang("application.Match Summary")</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="statistics-tab" data-toggle="tab" href="#statistics" role="tab" aria-controls="statistics" aria-selected="true">@lang("application.Statistics")</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="lineups-tab" data-toggle="tab" href="#lineups" role="tab" aria-controls="lineups" aria-selected="true">@lang("application.Lineups")</a>
            </li>
        </ul>

        {{-- Tab panes --}}
        <div class="tab-content" id="tab_content">
            <div class="tab-pane fade show active" id="match_summary" role="tabpanel" aria-labelledby="match_summary-tab">
                @if(count($events) > 0)
                    <table class="table table-sm table-borderless" align="center">
                        <tbody>
                        @foreach($events as $event)
                            @if(in_array($event->type, array("pen_shootout_goal", "pen_shootout_miss")))
                                @continue
                            @endif
                            <tr>
                            @if($event->team_id == $homeTeamId)
                                @if($event->type == "substitution")
                                    <td scope="row" style="text-align:right" width="1%"> {{$event->minute}}&apos;</td>
                                    <td scope="row" style="text-align:center" width="1%"><img src="/images/events/{{$event->type}}.svg"></td>
                                    <td scope="row" style="text-align:left" width="49%">{{$event->player_name}} <img src='/images/events/substitution-in.svg'> {{$event->related_player_name}} <img src='/images/events/substitution-out.svg'></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                @else
                                    <td scope="row" style="text-align:right" width="1%">{{$event->minute}}&apos;</td>
                                    <td scope="row" style="text-align:center" width="1%"><img src="/images/events/{{$event->type}}.svg"></td>
                                    <td scope="row" style="text-align:left" width="49%"> {{$event->player_name}} </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                @endif
                            @else
                                @if($event->type == "substitution")
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td scope="row" style="text-align:right" width="49%">{{$event->player_name}} <img src='/images/events/substitution-in.svg'> {{$event->related_player_name}} <img src='/images/events/substitution-out.svg'></td>
                                    <td scope="row" style="text-align:center" width="1%"><img src="/images/events/{{$event->type}}.svg"></td>
                                    <td scope="row" style="text-align:left" width="1%">{{$event->minute}}&apos;</td>
                                @else
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td scope="row" style="text-align:right" width="49%"> {{$event->player_name}} </td>
                                    <td scope="row" style="text-align:center" width="1%"><img src="/images/events/{{$event->type}}.svg"></td>
                                    <td scope="row" style="text-align:left" width="1%">{{$event->minute}}&apos;</td>
                                @endif
                            @endif
                            <tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <span style="font-weight: bold">@lang('application.Match Summary') @choice('application.msg_no_data', 1)</span>
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
                    <span style="font-weight: bold">@lang('application.Statistics') @choice('application.msg_no_data', 2)</span>
                @endif
            </div>
            <div class="tab-pane fade" id="lineups" role="tabpanel" aria-labelledby="lineups-tab">
                @if(count($lineup) > 0 && count($bench) > 0)
                    <table class="table table-borderless table-sm">
                        @if($fixture->formations->localteam_formation && $fixture->formations->visitorteam_formation)
                        <tr style="text-align: center">
                            <td colspan="3">
                                <span>{{$fixture->formations->localteam_formation}}</span>
                            </td>
                            <td style="font-weight: bold">
                                <span>@lang('application.Formation')</span>
                            </td>
                            <td colspan="3">
                                <span>{{$fixture->formations->visitorteam_formation}}</span>
                            </td>
                        </tr>
                        @endif
                        <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                            <td colspan="7">
                                <span>@lang('application.Starting lineups')</span>
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
                                
                                if($away_player_nationality == "Unknown") {
                                    \Illuminate\Support\Facades\Log::alert("Missing nationality for " . $away_player->player_id);
                                } elseif($home_player_nationality == "Unknown"){
                                    \Illuminate\Support\Facades\Log::alert("Missing nationality for " . $home_player->player_id);
                                }
                            @endphp
                            <tr>
                                @if(isset($home_player) && isset($away_player))
                                    <td style="width:1%">{{$home_player->number}}</td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png"></td>
                                    <td style="text-align: left">
                                        {{$home_player_common_name}}
                                        @foreach($home_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                    </td>
                                    <td style="width: 1%"></td>
                                    <td style="text-align: right">
                                        @foreach($away_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                        {{$away_player_common_name}}
                                    </td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png"></td>
                                    <td style="width:1%">{{$away_player->number}}</td>
                                @elseif(isset($home_player) && !isset($away_player))
                                    <td style="width:1%">{{$home_player->number}}</td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png"></td>
                                    <td style="text-align: left">
                                        {{$home_player_common_name}}
                                        @foreach($home_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                    </td>
                                @elseif(!isset($home_player) && isset($away_player))
                                    <td colspan="4"></td>
                                    <td style="width: 1%"></td>
                                    <td style="text-align: right">
                                        @foreach($away_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                        {{$away_player_common_name}}
                                    </td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png"></td>
                                    <td style="width:1%">{{$away_player->number}}</td>
                                @endif
                            </tr>
                        @endfor
                        <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                            <td colspan="7">
                                <span>@lang('application.Substitutes')</span>
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
                                
                                if($away_player_nationality == "Unknown") {
                                    \Illuminate\Support\Facades\Log::alert("Missing nationality for " . $away_player->player_id);
                                } elseif($home_player_nationality == "Unknown"){
                                    \Illuminate\Support\Facades\Log::alert("Missing nationality for " . $home_player->player_id);
                                }
                            @endphp
                            <tr>
                                @if(isset($home_player) && isset($away_player))
                                    <td style="width:1%">{{$home_player->number}}</td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png"></td>
                                    <td style="text-align: left; width:50%">
                                        {{$home_player_common_name}}
                                        @foreach($home_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                    </td>
                                    <td style="width: 1%"></td>
                                    <td style="text-align: right; width: 50%">
                                        @foreach($away_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                        {{$away_player_common_name}}
                                    </td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png"></td>
                                    <td style="width:1%">{{$away_player->number}}</td>
                                @elseif(isset($home_player) && !isset($away_player))
                                    <td style="width:1%">{{$home_player->number}}</td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$home_player_nationality}}.png"></td>
                                    <td style="text-align: left; width:50%">
                                        {{$home_player_common_name}}
                                        @foreach($home_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                @elseif(!isset($home_player) && isset($away_player))
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td style="text-align: right; width: 50%">
                                        @foreach($away_player_stats as $stat)
                                            <img src="{{$stat}}">
                                        @endforeach
                                        {{$away_player_common_name}}
                                    </td>
                                    <td style="width:1%"><img src="/images/flags/shiny/16/{{$away_player_nationality}}.png"></td>
                                    <td style="width:1%">{{$away_player->number}}</td>
                                @endif
                            </tr>
                        @endfor
                        @if(count($sidelined) > 0)
                            <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                                <td colspan="7">
                                    <span>@lang('application.Missing players')</span>
                                </td>
                            </tr>
                            @php
                                $home_sidelined_players = new stdClass();
                                $away_sidelined_players = new stdClass();
                                $counter_h = 0;
                                $counter_a = 0;
                                foreach($sidelined as $val){
                                    $val->team_id == $homeTeam->id ? $val->team = "home" : $val->team = "away";
                                    $player = array("common_name" => $val->player->data->common_name, "nationality" => $val->player->data->nationality, "reason" => $val->reason);
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
                                    }
                                    if(isset($away_sidelined_players[$index])) {
                                        $away_sidelined_player = $away_sidelined_players[$index];
                                        $away_sidelined_player->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($away_sidelined_player->nationality);
                                    }
                                @endphp
                                @if(isset($home_sidelined_player) && isset($away_sidelined_player))
                                    <tr>
                                        <td></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_sidelined_player->nationality}}.png"></td>
                                        <td style="text-align: left">{{$home_sidelined_player->common_name}} <span style="color: #A9A9A9">(@lang('injuries.' . $home_sidelined_player->reason))</span></td>
                                        <td></td>
                                        <td style="text-align: right">{{$away_sidelined_player->common_name}} <span style="color: #A9A9A9">(@lang('injuries.' . $away_sidelined_player->reason))</span></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_sidelined_player->nationality}}.png"></td>
                                    </tr>
                                @elseif(isset($home_sidelined_player) && !isset($away_sidelined_player))
                                    <tr>
                                        <td></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$home_sidelined_player->nationality}}.png"></td>
                                        <td style="text-align: left">{{$home_sidelined_player->common_name}} <span style="color: #A9A9A9">(@lang('injuries.' . $home_sidelined_player->reason))</span></td>
                                    </tr>
                                @elseif(!isset($home_sidelined_player) && isset($away_sidelined_player))
                                    <tr>
                                        <td colspan="4"></td>
                                        <td style="text-align: right">{{$away_sidelined_player->common_name}} <span style="color: #A9A9A9">(@lang('injuries.' . $away_sidelined_player->reason))</span></td>
                                        <td style="width: 1%"><img src="/images/flags/shiny/16/{{$away_sidelined_player->nationality}}.png"></td>
                                    </tr>
                                @endif
                                @php unset($home_sidelined_player); unset($away_sidelined_player) @endphp
                            @endfor
                        @endif
                        @if(isset($localCoach) || isset($visitorCoach))
                        <tr style="font-weight: bold; text-align: center; background: #D3D3D3">
                            <td colspan="7">
                                <span>@lang('application.Coaches')</span>
                            </td>
                        </tr>
                        <tr>
                            @if(isset($localCoach) && isset($visitorCoach))
                                <td style="width: 1%"></td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$localCoach->nationality}}.png"></td>
                                <td style="text-align: left">{{$localCoach->common_name}}</td>
                                <td></td>
                                <td style="text-align: right">{{$visitorCoach->common_name}}</td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$visitorCoach->nationality}}.png"></td>
                                <td style="width: 1%"></td>
                            @elseif(isset($localCoach) && !isset($visitorCoach))
                                <td style="width: 1%"></td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$localCoach->nationality}}.png"></td>
                                <td style="text-align: left">{{$localCoach->common_name}}</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            @elseif(!isset($localCoach) && isset($visitorCoach))
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td style="text-align: right">{{$visitorCoach->common_name}}</td>
                                <td style="width:1%"><img src="/images/flags/shiny/16/{{$visitorCoach->nationality}}.png"></td>
                                <td style="width: 1%"></td>
                            @endif
                        </tr>
                        @endif
                    </table>
                @else
                    <span style="font-weight: bold">@lang('application.Lineups') @choice('application.msg_no_data', 2)</span>
                @endif
            </div>
        </div>
    </div>
@endsection
@extends("layouts.default")

@section("content")
    <div class="container">
        <div id="heading" style="text-align: center">
            <table width="100%">
                <tr>
                    <td><h1>{{ Lang::has("leagues." . $league->name) ? trans("leagues." . $league->name) : Log::critical("Missing league translation for: " . $league->name)   . $league->name }} - {{$league->season->data->name}}</h1></td>
                </tr>
            </table>
        </div>

        {{-- Nav tabs  --}}
        <ul class="nav nav-tabs" id="nav_tabs" role="tablist">
            @if($last_fixtures->count() > 0)
            <li class="nav-item">
                <a class="nav-link active" id="last_fixtures-tab" data-toggle="tab" href="#last_fixtures" role="tab" aria-controls="last_fixtures" aria-selected="true">@choice("application.last fixtures", $last_fixtures->count())</a>
            </li>
            @endif
            @if($upcoming_fixtures->count() > 0)
                <li class="nav-item">
                    <a class="nav-link" id="upcoming_fixtures-tab" data-toggle="tab" href="#upcoming_fixtures" role="tab" aria-controls="upcoming_fixtures" aria-selected="false">@choice("application.upcoming fixtures", $upcoming_fixtures->count())</a>
                </li>
            @endif
            @if(count($standings_raw) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="standings-tab" data-toggle="tab" href="#standings" role="tab" aria-controls="standings" aria-selected="false">@lang("application.Standings")</a>
                </li>
            @endif
            @if(count($topscorers) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="topscorers-tab" data-toggle="tab" href="#topscorers" role="tab" aria-controls="topscorers" aria-selected="false">@lang("application.Top Scorers")</a>
                </li>
            @endif
        </ul>

        {{-- Tab panes --}}
        <div class="tab-content" id="tab_content">
            <div class="tab-pane fade show active" id="last_fixtures" role="tabpanel" aria-labelledby="last_fixtures-tab">
                @if($last_fixtures->count() > 0)
                    @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                    @foreach($last_fixtures as $last_fixture)
                        @php
                            $homeTeam = $last_fixture->localTeam->data;
                            $awayTeam = $last_fixture->visitorTeam->data;
                            
                            if($homeTeam->national_team == true) {
                                $homeTeam->name = trans("countries." . $homeTeam->name);
                            }
                            if($awayTeam->national_team == true) {
                                $awayTeam->name = trans("countries." . $awayTeam->name);
                            }
                            
                            if(strpos($homeTeam->name, "countries") !== false) {
                                Log::warning("Missing translation-string for: " . str_replace("countries.", "", $homeTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            } elseif(strpos($awayTeam->name, "countries") !== false) {
                                Log::warning("Missing translation-string for: " . str_replace("countries.", "", $awayTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            }
                            
                            if(in_array($last_fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                                switch($last_fixture->time->status) {
                                    case("FT_PEN"):
                                        if($last_fixture->scores->localteam_pen_score > $last_fixture->scores->visitorteam_pen_score) {
                                            $winningTeam = $homeTeam->name;
                                        } elseif($last_fixture->scores->localteam_pen_score == $last_fixture->scores->visitorteam_pen_score) {
                                            $winningTeam = "draw";
                                        } elseif($last_fixture->scores->localteam_pen_score < $last_fixture->scores->visitorteam_pen_score) {
                                            $winningTeam = $awayTeam->name;
                                        }
                                        break;
                                    default:
                                        if($last_fixture->scores->localteam_score > $last_fixture->scores->visitorteam_score) {
                                            $winningTeam = $homeTeam->name;
                                        } elseif($last_fixture->scores->localteam_score == $last_fixture->scores->visitorteam_score) {
                                            $winningTeam = "draw";
                                        } elseif($last_fixture->scores->localteam_score < $last_fixture->scores->visitorteam_score) {
                                            $winningTeam = $awayTeam->name;
                                        }
                                        break;
                                }
                            } else {
                                $winningTeam = "TBD";
                            }
                        @endphp
                        @if($last_fixture->league_id == $last_league_id)
                            @if(isset($last_fixture->round))
                                @if($last_round_id !== $last_fixture->round->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                            @if($last_fixture->stage->data->name !== "Regular Season")
                                                @lang("cup_stages." . $last_fixture->stage->data->name) -
                                            @endif
                                            @lang("application.Matchday") {{$last_fixture->round->data->name}}</td>
                                    </tr>
                                @endif
                            @elseif($last_stage_id !== $last_fixture->stage->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $last_fixture->stage->data->name)</td>
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
                                @switch($last_fixture->time->status)
                                    @case("FT_PEN")
                                        <td scope="row">{{$last_fixture->scores->localteam_score}} - {{$last_fixture->scores->visitorteam_score}}
                                            @if(is_null($last_fixture->scores->localteam_pen_score) || is_null($last_fixture->scores->visitorteam_pen_score))
                                                (PEN)
                                            @else
                                                 ({{$last_fixture->scores->localteam_pen_score}} - {{$last_fixture->scores->visitorteam_score}})
                                            @endif
                                        </td>
                                        @break
                                    @case("AET")
                                        <td scope="row">{{$last_fixture->scores->localteam_score}} - {{$last_fixture->scores->visitorteam_score}} (ET)</td>
                                        @break
                                    @default
                                        <td scope="row">{{$last_fixture->scores->localteam_score}} - {{$last_fixture->scores->visitorteam_score}}</td>
                                        @break
                                @endswitch
    
                                <td scope="row">{{date($date_format . " H:i", strtotime($last_fixture->time->starting_at->date_time))}}
                                    @if(in_array($last_fixture->time->status, array("LIVE", "HT", "ET")))
                                        <span class="live">{{ $last_fixture->time->status }}</span>
                                    @endif
                                </td>
                                <td scope="row"><a href= {{route("fixturesDetails", ["id" => $last_fixture->id])}}><i class="fa fa-info-circle"></i></a></td>
                            </tr>
                        @else
                            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                            <table class="table table-striped table-light table-sm" style="width:100%">
                                <thead style="visibility: collapse">
                                    <tr>
                                        <th scope="col" width="32%"></th>
                                        <th scope="col" width="32%"></th>
                                        <th scope="col" width="11%"></th>
                                        <th scope="col" width="17%"></th>
                                        <th scope="col" width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if(isset($last_fixture->round))
                                        @if($last_round_id !== $last_fixture->round->data->name)
                                            <tr>
                                                <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                                    @if($last_fixture->stage->data->name !== "Regular Season")
                                                        @lang("cup_stages." . $last_fixture->stage->data->name) -
                                                    @endif
                                                    @lang("application.Matchday") {{$last_fixture->round->data->name}}</td>
                                            </tr>
                                        @endif
                                    @elseif($last_stage_id !== $last_fixture->stage->data->name)
                                        <tr>
                                            <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $last_fixture->stage->data->name)</td>
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
                                        @switch($last_fixture->time->status)
                                            @case("FT_PEN")
                                                <td scope="row">{{$last_fixture->scores->localteam_score}} - {{$last_fixture->scores->visitorteam_score}} ({{$last_fixture->scores->localteam_pen_score}} - {{$last_fixture->scores->visitorteam_pen_score}})</td>
                                                @break
                                            @case("AET")
                                                <td scope="row">{{$last_fixture->scores->localteam_score}} - {{$last_fixture->scores->visitorteam_score}} (ET)</td>
                                                @break
                                            @default
                                                <td scope="row">{{$last_fixture->scores->localteam_score}} - {{$last_fixture->scores->visitorteam_score}}</td>
                                                @break
                                        @endswitch

                                        {{-- show date_time, if LIVE -> show LIVE after date_time --}}
                                        <td scope="row">{{date($date_format . " H:i", strtotime($last_fixture->time->starting_at->date_time))}}
                                            @if(in_array($last_fixture->time->status, array("LIVE", "HT", "ET")))
                                                <span class="live">{{ $last_fixture->time->status }}</span>
                                            @endif
                                        </td>
                                        {{-- show button to view fixtures-details --}}
                                        <td scope="row"><a href="{{route("fixturesDetails", ["id" => $last_fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
                                    </tr>
                        @endif
                        @php $last_league_id = $last_fixture->league_id; if(isset($last_fixture->round)) {$last_round_id = $last_fixture->round->data->name;} $last_stage_id = $last_fixture->stage->data->name; @endphp
                    @endforeach
                    </tbody>
                    </table>
                @endif
            </div>
            <div class="tab-pane fade" id="upcoming_fixtures" role="tabpanel" aria-labelledby="upcoming_fixtures-tab">
                @if($upcoming_fixtures->count() > 0)
                    @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                    @foreach($upcoming_fixtures as $upcoming_fixture)
                        @php
                            $homeTeam = $upcoming_fixture->localTeam->data;
                            $awayTeam = $upcoming_fixture->visitorTeam->data;
                            
                            if($homeTeam->national_team == true) {
                                $homeTeam->name = trans("countries." . $homeTeam->name);
                            }
                            if($awayTeam->national_team == true) {
                                $awayTeam->name = trans("countries." . $awayTeam->name);
                            }
                            
                            if(strpos($homeTeam->name, "countries") !== false) {
                                Log::warning("Missing translation-string for: " . str_replace("countries.", "", $homeTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            } elseif(strpos($awayTeam->name, "countries") !== false) {
                                Log::warning("Missing translation-string for: " . str_replace("countries.", "", $awayTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            }
                        @endphp
                        @if($upcoming_fixture->league_id == $last_league_id)
                            @if(isset($upcoming_fixture->round))
                                @if($last_round_id !== $upcoming_fixture->round->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                            @if($upcoming_fixture->stage->data->name !== "Regular Season")
                                                @lang("cup_stages." . $upcoming_fixture->stage->data->name) -
                                            @endif
                                            @lang("application.Matchday") {{$upcoming_fixture->round->data->name}}</td>
                                    </tr>
                                @endif
                            @elseif($last_stage_id !== $upcoming_fixture->stage->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $upcoming_fixture->stage->data->name)</td>
                                </tr>
                            @endif
                            <tr>
                                <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}</a></td>
                                <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}">{{$awayTeam->name}}</a></td>
                                <td scope="row">{{$upcoming_fixture->scores->localteam_score}} - {{$upcoming_fixture->scores->visitorteam_score}}</td>
    
                                <td scope="row">{{date($date_format . " H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}
                                    @if(in_array($upcoming_fixture->time->status, array("LIVE", "HT", "ET")))
                                        <span class="live">{{ $upcoming_fixture->time->status }}</span>
                                    @endif
                                </td>
                                <td scope="row"><a href= {{route("fixturesDetails", ["id" => $upcoming_fixture->id])}}><i class="fa fa-info-circle"></i></a></td>
                            </tr>
                        @else
                            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                            <table class="table table-striped table-light table-sm" style="width:100%">
                                @if(isset($upcoming_fixture->round))
                                    @if($last_round_id !== $upcoming_fixture->round->data->name)
                                        <tr>
                                            <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                                @if($upcoming_fixture->stage->data->name !== "Regular Season")
                                                    @lang("cup_stages." . $upcoming_fixture->stage->data->name) -
                                                @endif
                                                @lang("application.Matchday") {{$upcoming_fixture->round->data->name}}</td>
                                        </tr>
                                    @endif
                                @elseif($last_stage_id !== $upcoming_fixture->stage->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $upcoming_fixture->stage->data->name)</td>
                                    </tr>
                                @endif
                                <thead style="visibility: collapse">
                                    <tr>
                                        <th scope="col" width="32%"></th>
                                        <th scope="col" width="32%"></th>
                                        <th scope="col" width="11%"></th>
                                        <th scope="col" width="17%"></th>
                                        <th scope="col" width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}</a></td>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}">{{$awayTeam->name}}</a></td>
                                    <td scope="row">{{$upcoming_fixture->scores->localteam_score}} - {{$upcoming_fixture->scores->visitorteam_score}}</td>
    
                                    <td scope="row">{{date($date_format . " H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}
                                        @if(in_array($upcoming_fixture->time->status, array("LIVE", "HT", "ET")))
                                            <span class="live">{{ $upcoming_fixture->time->status }}</span>
                                        @endif
                                    </td>
                                    <td scope="row"><a href= {{route("fixturesDetails", ["id" => $upcoming_fixture->id])}}><i class="fa fa-info-circle"></i></a></td>
                                </tr>
                        @endif
                        @php $last_league_id = $upcoming_fixture->league_id; if(isset($upcoming_fixture->round)) {$last_round_id = $upcoming_fixture->round->data->name;} $last_stage_id = $upcoming_fixture->stage->data->name; @endphp
                    @endforeach
                    </tbody>
                    </table>
                @endif
            </div>
            <div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
                @if(count($standings_raw) > 0)
                    @foreach($standings_raw as $standings)
                        @php $standing = $standings->standings->data; @endphp
                        <table class="table table-light table-sm" style="width:100%">
                            @if(count($standings_raw) > 1)
                                <caption>
                                @if(strpos($standings->name, "Group") !== false)
                                    {{str_replace("Group", trans("application.Group"), $standings->name)}}
                                @else
                                    {{$standings->name}}
                                @endif
                                </caption>
                            @endif
                            <thead>
                                <tr>
                                    <th scope="col" width="1%">@lang("application.No.")</th>
                                    <th scope="col" width="35%">@lang("application.Team")</th>
                                    <th scope="col">@lang("application.Played")</th>
                                    <th scope="col">@lang("application.Won")</th>
                                    <th scope="col">@lang("application.Draw")</th>
                                    <th scope="col">@lang("application.Lost")</th>
                                    <th scope="col" colspan="2">@lang("application.Goals")</th>
                                    <th scope="col">@lang("application.Points")</th>
                                    <th scope="col" width="13%">@lang("application.Form")</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @foreach($standing as $team)
                                        @php
                                            if($team->team->data->national_team == true) {
                                                $team->team_name = trans("countries." . $team->team_name);
                                            }
                                            
                                            if(strpos($team->team_name, "countries") !== false) {
                                                Log::warning("Missing translation-string for: " . str_replace("countries.", "", $team->team_name) . " in " . app()->getLocale() . "/countries.php");
                                            }
                                        @endphp
                                        <tr>
                                            <td scope="row">{{$team->position}}</td>
                                            <td scope="row"><a href ="{{route("teamsDetails", ["id" => $team->team_id])}}">{{$team->team_name}}</a></td>
                                            <td scope="row">{{$team->overall->games_played}}</td>
                                            <td scope="row">{{$team->overall->won}}</td>
                                            <td scope="row">{{$team->overall->draw}}</td>
                                            <td scope="row">{{$team->overall->lost}}</td>
                                            <td scope="row">{{$team->overall->goals_scored}}:{{$team->overall->goals_against}}</td>
                                            <td scope="row">({{$team->total->goal_difference}})</td>
                                            <td scope="row">{{$team->points}}</td>
                                            @php $recent_forms = str_split($team->recent_form); @endphp
                                            <td scope="row">
                                                @foreach($recent_forms as $recent_form)
                                                    @switch($recent_form)
                                                        @case("W")
                                                            <span class="result-icon result-icon-w">@lang("application." . $recent_form)</span>
                                                            @break
                                                        @case("D")
                                                            <span class="result-icon result-icon-d">@lang("application." . $recent_form)</span>
                                                            @break
                                                        @case("L")
                                                            <span class="result-icon result-icon-l">@lang("application." . $recent_form)</span>
                                                            @break
                                                    @endswitch
                                                @endforeach
                                                @switch($team->status)
                                                    @case("same")
                                                        &nbsp;<i class="fa fa-caret-left"></i>
                                                        @break
                                                    @case("down")
                                                        &nbsp;<i class="fa fa-caret-down"></i>
                                                        @break
                                                    @case("up")
                                                        &nbsp;<i class="fa fa-caret-up"></i>
                                                        @break
                                                @endswitch
                                            </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endforeach
                @endif
            </div>
            <div class="tab-pane fade" id="topscorers" role="tabpanel" aria-labelledby="topscorers-tab">
                @if(count($topscorers) > 0)
                    <table class="table table-light table-striped table-sm" style="width: 100%">
                        <thead class="thead-dark">
                            <tr>
                                <th scope="col" width="1%">@lang("application.No.")</th>
                                <th scope="col">@lang("application.Player")</th>
                                <th scope="col">@lang("application.Team")</th>
                                <th scope="col">@lang("application.Goals")</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topscorers as $topscorer)
                                <tr>
                                    <td scope="row">{{$topscorer->position}}</td>
                                    @php
                                        $team = $topscorer->team->data;
                                        $player = $topscorer->player->data;
                                        
                                        if(isset($player->nationality)) {
                                            $player->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($player->nationality);
                                        } else {
                                            $player->nationality = "Unknown";
                                        }
                                        
                                        if($team->national_team == true) {
                                            $team->name = trans("countries." . $team->name);
                                        }
                                        
                                        if(strpos($team->name, "countries") !== false) {
                                            Log::warning("Missing translation-string for: " . str_replace("countries.", "", $team->name) . " in " . app()->getLocale() . "/countries.php");
                                        }
                                    @endphp
                                    <td scope="row"><img src="/images/flags/shiny/16/{{$player->nationality}}.png">&nbsp;&nbsp;{{$player->common_name}}</td>
                                    <td scope="row"><a href ="{{route("teamsDetails", ["id" => $team->id])}}">{{$team->name}}</a></td>
                                    <td scope="row">{{$topscorer->goals}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
@endsection
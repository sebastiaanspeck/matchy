@extends("layouts.default")

@section("content")
    <div class="container">
        @php
            $favorite_leagues = \App\Http\Controllers\Filebase\FilebaseController::getField('favorite_leagues');
            $favorite_league = "far";
            if (in_array($league->id, $favorite_leagues)) {
                $favorite_league = "fas";
            }
        @endphp

        <div id="heading" style="text-align: center">
            <table width="100%">
                <tr>
                    <td><h3><a href="{{ route("setFavoriteLeagues", ["id" => $league->id]) }}"><i class="{{ $favorite_league }} fa-star fa-fw fa-xs" aria-hidden="true" style="transform: translate(10%, -10%);"></i></a>&nbsp;{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }} - {{$league->season->data->name}}</h3></td>
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
                                Log::critical("Missing country translation for: " . str_replace("countries.", "", $homeTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            } elseif(strpos($awayTeam->name, "countries") !== false) {
                                Log::critical("Missing country translation for: " . str_replace("countries.", "", $awayTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            }
                            
                            if(in_array($last_fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                                switch($last_fixture->time->status) {
                                    case("FT_PEN"):
                                        if($last_fixture->scores->localteam_pen_score > $last_fixture->scores->visitorteam_pen_score) {
                                            $homeTeamClass = "won-team";
                                            $awayTeamClass = "lost-team";
                                        } elseif($last_fixture->scores->localteam_pen_score == $last_fixture->scores->visitorteam_pen_score) {
                                            $homeTeamClass = $awayTeamClass = "draw-team";
                                        } elseif($last_fixture->scores->localteam_pen_score < $last_fixture->scores->visitorteam_pen_score) {
                                            $homeTeamClass = "lost-team";
                                            $awayTeamClass = "won-team";
                                        }
                                        break;
                                    default:
                                        if($last_fixture->scores->localteam_score > $last_fixture->scores->visitorteam_score) {
                                            $homeTeamClass = "won-team";
                                            $awayTeamClass = "lost-team";
                                        } elseif($last_fixture->scores->localteam_score == $last_fixture->scores->visitorteam_score) {
                                            $homeTeamClass = $awayTeamClass = "draw-team";
                                        } elseif($last_fixture->scores->localteam_score < $last_fixture->scores->visitorteam_score) {
                                            $homeTeamClass = "lost-team";
                                            $awayTeamClass = "won-team";
                                        }
                                        break;
                                }
                            } else {
                                $homeTeamClass = $awayTeamClass = "";
                            }
                            
                            switch($last_fixture->time->status) {
                                case("FT_PEN"):
                                    $scoreLine = $last_fixture->scores->localteam_score . " - " . $last_fixture->scores->visitorteam_score ."\n(" . $last_fixture->scores->localteam_pen_score . " - " . $last_fixture->scores->visitorteam_pen_score . ")";
                                    break;
                                case("AET"):
                                    $scoreLine = $last_fixture->scores->localteam_score . " - " . $last_fixture->scores->visitorteam_score . "\n(ET)";
                                    break;
                                case("NS"):
                                    $scoreLine = " - ";
                                    break;
                                default:
                                    $scoreLine = $last_fixture->scores->localteam_score . " - " . $last_fixture->scores->visitorteam_score;
                                    break;
                            }

                            $homeTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($homeTeam->logo_path, 16, 16);
                            $awayTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($awayTeam->logo_path, 16, 16);
                        @endphp
                        @if($last_fixture->league_id == $last_league_id)
                            @if(isset($last_fixture->round))
                                @if($last_round_id !== $last_fixture->round->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                            @if($last_fixture->stage->data->name !== "Regular Season")
                                                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $last_fixture->stage->data->name) }} -
                                            @endif
                                            {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$last_fixture->round->data->name}}</td>
                                    </tr>
                                @endif
                            @elseif($last_stage_id !== $last_fixture->stage->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $last_fixture->stage->data->name)</td>
                                </tr>
                            @endif
                            <tr>
                                <td scope="row">{{date($date_format . " H:i", strtotime($last_fixture->time->starting_at->date_time))}}</td>
                                {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class={{$homeTeamClass}}>{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                                {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                                <td scope="row" style="text-align: center">{!! nl2br(e($scoreLine)) !!}</td>
                                {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class={{$awayTeamClass}}><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                                <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $last_fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
                            </tr>
                        @else
                            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                            <table class="table table-striped table-light table-sm" style="width:100%">
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
                                    @if(isset($last_fixture->round))
                                        @if($last_round_id !== $last_fixture->round->data->name)
                                            <tr>
                                                <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                                    @if($last_fixture->stage->data->name !== "Regular Season")
                                                        {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $last_fixture->stage->data->name) }} -
                                                    @endif
                                                    {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$last_fixture->round->data->name}}</td>
                                            </tr>
                                        @endif
                                    @elseif($last_stage_id !== $last_fixture->stage->data->name)
                                        <tr>
                                            <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $last_fixture->stage->data->name)</td>
                                        </tr>
                                    @endif
                                    <tr>
                                        <td scope="row">{{date($date_format . " H:i", strtotime($last_fixture->time->starting_at->date_time))}}</td>
                                        {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                        <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class={{$homeTeamClass}}>{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                                        {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                                        <td scope="row" style="text-align: center">{!! nl2br(e($scoreLine)) !!}</td>
                                        {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                                        <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class={{$awayTeamClass}}><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                                        <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $last_fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
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
                                Log::critical("Missing country translation for: " . str_replace("countries.", "", $homeTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            } elseif(strpos($awayTeam->name, "countries") !== false) {
                                Log::critical("Missing country translation for: " . str_replace("countries.", "", $awayTeam->name) . " in " . app()->getLocale() . "/countries.php");
                            }

                            $homeTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($homeTeam->logo_path, 16, 16);
                            $awayTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($awayTeam->logo_path, 16, 16);
                        @endphp
                        @if($upcoming_fixture->league_id == $last_league_id)
                            @if(isset($upcoming_fixture->round))
                                @if($last_round_id !== $upcoming_fixture->round->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                            @if($upcoming_fixture->stage->data->name !== "Regular Season")
                                                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $upcoming_fixture->stage->data->name) }} -
                                            @endif
                                            {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$upcoming_fixture->round->data->name}}</td>
                                    </tr>
                                @endif
                            @elseif($last_stage_id !== $upcoming_fixture->stage->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">@lang("cup_stages." . $upcoming_fixture->stage->data->name)</td>
                                </tr>
                            @endif
                            <tr>
                                <td scope="row">{{date($date_format . " H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}</td>
                                <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                                <td scope="row" style="text-align: center"> - </td>
                                <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}"><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                                <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $upcoming_fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
                            </tr>
                        @else
                            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                            <table class="table table-striped table-light table-sm" style="width:100%">
                                @if(isset($upcoming_fixture->round))
                                    @if($last_round_id !== $upcoming_fixture->round->data->name)
                                        <tr>
                                            <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                                @if($upcoming_fixture->stage->data->name !== "Regular Season")
                                                    {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $upcoming_fixture->stage->data->name) }} -
                                                @endif
                                                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$upcoming_fixture->round->data->name}}</td>
                                        </tr>
                                    @endif
                                @elseif($last_stage_id !== $upcoming_fixture->stage->data->name)
                                    <tr>
                                        <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $upcoming_fixture->stage->data->name) }}</td>
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
                                    <td scope="row">{{date($date_format . " H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}</td>
                                    <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                                    <td scope="row" style="text-align: center"> - </td>
                                    <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}"><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                                    <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $upcoming_fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
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
                        <table class="table table-light table-sm table-responsive">
                            @if(count($standings_raw) > 1)
                                <caption>
                                @if(strpos($standings->name, "Group") !== false)
                                    @if($standings->stage_name !== "Group Stage"){{$standings->stage_name}} - @endif {{str_replace("Group", trans("application.Group"), $standings->name)}}
                                @else
                                    {{ $standings->name }}
                                @endif
                                </caption>
                            @endif
                            <thead class="thead-dark">
                                <tr>
                                    <th scope="col" width="1%">#</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Team") }}</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "MP") }}</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "W") }}</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "D") }}</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "L") }}</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "G") }}</th>
                                    <th scope="col">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Pts") }}</th>
                                    <th scope="col" width="13%">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Form") }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @foreach($standing as $team)
                                        @php
                                            if($team->team->data->national_team == true) {
                                                $team->team_name = trans("countries." . $team->team_name);
                                            }
                                            
                                            if(strpos($team->team_name, "countries") !== false) {
                                                Log::critical("Missing country translation for: " . str_replace("countries.", "", $team->team_name) . " in " . app()->getLocale() . "/countries.php");
                                            }

                                            $teamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($team->team->data->logo_path, 16, 16);
                                        @endphp
                                        <tr>
                                            <td scope="row">{{ $team->position }}</td>
                                            <td scope="row"><a href ="{{route("teamsDetails", ["id" => $team->team_id])}}"><img src="{{ $teamLogo }}" alt="team_logo">&nbsp;{{$team->team_name}}</a></td>
                                            <td scope="row">{{ $team->overall->games_played }}</td>
                                            <td scope="row">{{ $team->overall->won}}</td>
                                            <td scope="row">{{ $team->overall->draw}}</td>
                                            <td scope="row">{{$team->overall->lost}}</td>
                                            <td scope="row">{{$team->overall->goals_scored}}:{{$team->overall->goals_against}}</td>
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
                                                        &nbsp;<i class="fa fa-caret-left" aria-hidden="true"></i>
                                                        @break
                                                    @case("down")
                                                        &nbsp;<i class="fa fa-caret-down" aria-hidden="true"></i>
                                                        @break
                                                    @case("up")
                                                        &nbsp;<i class="fa fa-caret-up" aria-hidden="true"></i>
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
                                            Log::critical("Missing country translation for: " . str_replace("countries.", "", $team->name) . " in " . app()->getLocale() . "/countries.php");
                                        }

                                        $teamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($team->logo_path, 16, 16);
                                    @endphp
                                    <td scope="row"><img src="/images/flags/shiny/16/{{$player->nationality}}.png" alt="countryflag">&nbsp;{{$player->common_name}}</td>
                                    <td scope="row"><a href ="{{route("teamsDetails", ["id" => $team->id])}}"><img src="{{ $teamLogo }}" alt="team_logo">&nbsp;{{$team->name}}</a></td>
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
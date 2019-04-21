@extends("layouts.default")

@section("content")
    <div class = "container">
        @if(isset($date))
            <h3 style="text-align: center">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Fixtures") }} - {{date($date_format, strtotime($date))}}</h3>
        @endif

        @if(count($fixtures) >= 1 && gettype($fixtures) == "array")
            @if(count($fixtures) >= 100)
                <p style="color:red">@lang("application.msg_too_much_results", ["count" => count($fixtures)])</p>
            @endif

            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; $favorite_leagues = \App\Http\Controllers\Filebase\FilebaseController::getField('favorite_leagues'); @endphp
            @foreach($fixtures as $fixture)
                @php
                    $league = $fixture->league->data;
                    $homeTeam = $fixture->localTeam->data;
                    $awayTeam = $fixture->visitorTeam->data;
                    
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
                    
                    if(in_array($fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                        switch($fixture->time->status) {
                            case("FT_PEN"):
                                if($fixture->scores->localteam_pen_score > $fixture->scores->visitorteam_pen_score) {
                                    $homeTeamClass = "won-team";
                                    $awayTeamClass = "lost-team";
                                } elseif($fixture->scores->localteam_pen_score == $fixture->scores->visitorteam_pen_score) {
                                    $homeTeamClass = $awayTeamClass = "draw-team";
                                } elseif($fixture->scores->localteam_pen_score < $fixture->scores->visitorteam_pen_score) {
                                    $homeTeamClass = "lost-team";
                                    $awayTeamClass = "won-team";
                                }
                                break;
                            default:
                                if($fixture->scores->localteam_score > $fixture->scores->visitorteam_score) {
                                    $homeTeamClass = "won-team";
                                    $awayTeamClass = "lost-team";
                                } elseif($fixture->scores->localteam_score == $fixture->scores->visitorteam_score) {
                                    $homeTeamClass = $awayTeamClass = "draw-team";
                                } elseif($fixture->scores->localteam_score < $fixture->scores->visitorteam_score) {
                                    $homeTeamClass = "lost-team";
                                    $awayTeamClass = "won-team";
                                }
                                break;
                        }
                    } else {
                        $homeTeamClass = $awayTeamClass = "";
                    }
                    
                    switch($fixture->time->status) {
                        case("FT_PEN"):
                            $scoreLine = $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score ."\n(" . $fixture->scores->localteam_pen_score . " - " . $fixture->scores->visitorteam_pen_score . ")";
                            break;
                        case("AET"):
                            $scoreLine = $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score . "\n(ET)";
                            break;
                        case("NS"):
                            $scoreLine = " - ";
                            break;
                        default:
                            $scoreLine = $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score;
                            break;
                    }

                    $homeTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($homeTeam->logo_path, 16, 16);
                    $awayTeamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($awayTeam->logo_path, 16, 16);

                    $favorite_league = "far";
                    if (in_array($league->id, $favorite_leagues)) {
                        $favorite_league = "fas";
                    }
                @endphp
                @if($fixture->league_id == $last_league_id)
                    @if(isset($fixture->round))
                        @if($last_round_id !== $fixture->round->data->name)
                            <tr>
                                <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">
                                    @if($fixture->stage->data->name !== "Regular Season")
                                        {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $fixture->stage->data->name) }} -
                                    @endif
                                    {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} - {{$fixture->round->data->name}}</td>
                            </tr>
                        @endif
                    @elseif($last_stage_id !== $fixture->stage->data->name)
                        <tr>
                            <td style="font-weight: bold; text-align: center; background-color: #d3d3d3;" colspan="5">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $fixture->stage->data->name) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td scope="row">{{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}}
                            @if(in_array($fixture->time->status, array("LIVE", "HT", "ET")))
                                <span class="live">{{ $fixture->time->status }}</span>
                            @endif
                        </td>
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
                    <table class="table table-striped table-light table-sm">
                        @if(isset($fixture->round))
                            @if($last_round_id !== $fixture->round->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #bdbdbd;" colspan="5">
                                        <a href="{{ route("setFavoriteLeagues", ["id" => $league->id]) }}"><i class="{{ $favorite_league }} fa-star fa-fw" aria-hidden="true"></i></a>&nbsp;
                                        <a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a> -
                                        @if($fixture->stage->data->name !== "Regular Season")
                                            {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $fixture->stage->data->name) }} -
                                        @endif
                                        {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$fixture->round->data->name}}</td>
                                </tr>
                            @endif
                        @elseif($last_stage_id !== $fixture->stage->data->name)
                            <tr>
                                <td style="font-weight: bold; text-align: center; background-color: #bdbdbd;" colspan="5">
                                    <a href="{{ route("setFavoriteLeagues", ["id" => $league->id]) }}"><i class="{{ $favorite_league }} fa-star fa-fw" aria-hidden="true"></i></a>&nbsp;
                                    <a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a> -
                                    {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $fixture->stage->data->name) }}
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
                            <td scope="row">{{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}}
                                @if(in_array($fixture->time->status, array("LIVE", "HT", "ET")))
                                    <span class="live">{{ $fixture->time->status }}</span>
                                @endif
                            </td>
                            {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                            <td scope="row" style="text-align: right"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}" class={{$homeTeamClass}}>{{$homeTeam->name}}&nbsp;<img src="{{ $homeTeamLogo }}" alt="team_logo"></a></td>
                            {{-- show score, if FT_PEN -> show penalty score, if AET -> show (ET) --}}
                            <td scope="row" style="text-align: center">{!! nl2br(e($scoreLine)) !!}</td>
                            {{-- show winning team in green, losing team in red, if draw, show both in orange --}}
                            <td scope="row" style="text-align: left"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}" class={{$awayTeamClass}}><img src="{{ $awayTeamLogo }}" alt="team_logo">&nbsp;{{$awayTeam->name}}</a></td>
                            <td scope="row" style="text-align: right"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle" aria-hidden="true" style="margin-right: 10px"></i></a></td>
                        </tr>
                @endif
                @php $last_league_id = $fixture->league_id; if(isset($fixture->round)) {$last_round_id = $fixture->round->data->name;} $last_stage_id = $fixture->stage->data->name; @endphp
            @endforeach
            </tbody>
        </table>
        @else
            <p>@lang("application.msg_no_matches_found", ["date" => date($date_format, strtotime($date))])</p>
        @endif
    </div>
@endsection
@extends("layouts.default")

@section("content")
    <div class = "container">
        @if(isset($date))
            <h1>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Fixtures") }} - {{date($date_format, strtotime($date))}}</h1>
        @endif

        @if(count($fixtures) >= 1 && gettype($fixtures) == "array")
            @if(count($fixtures) >= 100)
                <p style="color:red">@lang("application.msg_too_much_results", ["count" => count($fixtures)])</p>
            @endif

            @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
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
                                    $winningTeam = $homeTeam->name;
                                } elseif($fixture->scores->localteam_pen_score == $fixture->scores->visitorteam_pen_score) {
                                    $winningTeam = "draw";
                                } elseif($fixture->scores->localteam_pen_score < $fixture->scores->visitorteam_pen_score) {
                                    $winningTeam = $awayTeam->name;
                                }
                                break;
                            default:
                                if($fixture->scores->localteam_score > $fixture->scores->visitorteam_score) {
                                    $winningTeam = $homeTeam->name;
                                } elseif($fixture->scores->localteam_score == $fixture->scores->visitorteam_score) {
                                    $winningTeam = "draw";
                                } elseif($fixture->scores->localteam_score < $fixture->scores->visitorteam_score) {
                                    $winningTeam = $awayTeam->name;
                                }
                                break;
                        }
                    } else {
                        $winningTeam = "TBD";
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
                        @switch($fixture->time->status)
                            @case("FT_PEN")
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}}
                                    @if(is_null($fixture->scores->localteam_pen_score) || is_null($fixture->scores->visitorteam_pen_score))
                                        (PEN)
                                    @else
                                         ({{$fixture->scores->localteam_pen_score}} - {{$fixture->scores->visitorteam_pen_score}})
                                    @endif
                                </td>
                                @break
                            @case("AET")
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} (ET)</td>
                                @break
                            @default
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}}</td>
                                @break
                        @endswitch
    
                        <td scope="row">{{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}}
                            @if(in_array($fixture->time->status, array("LIVE", "HT", "ET")))
                                <span class="live">{{ $fixture->time->status }}</span>
                            @endif
                        </td>
                        <td scope="row"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
                    </tr>
                @else
                    @php $last_league_id = 0; $last_round_id = 0; $last_stage_id = 0; @endphp
                    <table class="table table-striped table-light table-sm" width="100%">
                        @if(isset($fixture->round))
                            @if($last_round_id !== $fixture->round->data->name)
                                <tr>
                                    <td style="font-weight: bold; text-align: center; background-color: #bdbdbd;" colspan="5">
                                        <a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a> -
                                        @if($fixture->stage->data->name !== "Regular Season")
                                            {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $fixture->stage->data->name) }} -
                                        @endif
                                        {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Matchday") }} {{$fixture->round->data->name}}</td>
                                </tr>
                            @endif
                        @elseif($last_stage_id !== $fixture->stage->data->name)
                            <tr>
                                <td style="font-weight: bold; text-align: center; background-color: #bdbdbd;" colspan="5"><a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a> - {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("cup_stages", $fixture->stage->data->name) }}</td>
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
                            @switch($fixture->time->status)
                                @case("FT_PEN")
                                    <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}}
                                    @if(is_null($fixture->scores->localteam_pen_score) || is_null($fixture->scores->visitorteam_pen_score))
                                         (PEN)
                                    @else
                                         ({{$fixture->scores->localteam_pen_score}} - {{$fixture->scores->visitorteam_pen_score}})
                                    @endif
                                    </td>
                                    @break
                                @case("AET")
                                    <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} (ET)</td>
                                    @break
                                @default
                                    <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}}</td>
                                    @break
                            @endswitch
    
                            <td scope="row">{{date($date_format . " H:i", strtotime($fixture->time->starting_at->date_time))}}
                                @if(in_array($fixture->time->status, array("LIVE", "HT", "ET")))
                                    <span class="live">{{ $fixture->time->status }}</span>
                                @endif
                            </td>
                            <td scope="row"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
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
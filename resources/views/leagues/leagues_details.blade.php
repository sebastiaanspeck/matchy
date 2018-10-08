@extends("layouts.default")

@section("content")
    <div class="container">
        <div id="heading" style="text-align: center">
            <table width="100%">
                <tr>
                    <td><h1>{{$league->name}} - {{$league->season->data->name}}</h1></td>
                </tr>
            </table>
        </div>

        {{-- Nav tabs  --}}
        <ul class="nav nav-tabs" id="nav_tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="last_fixtures-tab" data-toggle="tab" href="#last_fixtures" role="tab" aria-controls="last_fixtures" aria-selected="true">Last {{ $last_fixtures->count() }} fixtures</a>
            </li>
            @if($upcoming_fixtures->count() > 0)
                <li class="nav-item">
                    <a class="nav-link" id="upcoming_fixtures-tab" data-toggle="tab" href="#upcoming_fixtures" role="tab" aria-controls="upcoming_fixtures" aria-selected="false">Upcoming {{ $upcoming_fixtures->count() }} fixtures</a>
                </li>
            @endif
            @if(count($standings_raw) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="standings-tab" data-toggle="tab" href="#standings" role="tab" aria-controls="standings" aria-selected="false">Standings</a>
                </li>
            @endif
            @if(count($topscorers) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="topscorers-tab" data-toggle="tab" href="#topscorers" role="tab" aria-controls="topscorers" aria-selected="false">Topscorers</a>
                </li>
            @endif
        </ul>

        {{-- Tab panes --}}
        <div class="tab-content" id="tab_content">
            <div class="tab-pane fade show active" id="last_fixtures" role="tabpanel" aria-labelledby="last_fixtures-tab">
                @if($last_fixtures->count() > 0)
                    @php $last_league_id = 0; @endphp
                    @foreach($last_fixtures as $last_fixture)
                        @php
                            $homeTeam = $last_fixture->localTeam->data;
                            $awayTeam = $last_fixture->visitorTeam->data;
                            if($last_fixture->scores->localteam_score > $last_fixture->scores->visitorteam_score && in_array($last_fixture->time->status, array("FT", "AET", "FT_PEN"))) {
                                $winningTeam = $homeTeam->name;
                            } elseif ($last_fixture->scores->localteam_score == $last_fixture->scores->visitorteam_score && in_array($last_fixture->time->status, array("FT", "AET", "FT_PEN"))) {
                                $winningTeam = "draw";
                            } elseif ($last_fixture->scores->localteam_score < $last_fixture->scores->visitorteam_score && in_array($last_fixture->time->status, array("FT", "AET", "FT_PEN"))) {
                                $winningTeam = $awayTeam->name;
                            } else {
                                $winningTeam = "TBD";
                            }
                        @endphp
                        @if($last_fixture->league_id == $last_league_id)
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

                                @if($last_fixture->time->status == "LIVE")
                                    <td scope="row">{{date("Y-m-d H:i", strtotime($last_fixture->time->starting_at->date_time))}}<span style="color:#FF0000"> LIVE</span></td>
                                @else
                                    <td scope="row">{{date("Y-m-d H:i", strtotime($last_fixture->time->starting_at->date_time))}}</td>
                                @endif
                                <td scope="row"><a href= {{route("fixturesDetails", ["id" => $last_fixture->id])}}><i class="fa fa-info-circle"></i></a></td>
                            </tr>
                        @else
                            <table class="table table-striped table-light table-sm" style="width:100%">
                                <thead>
                                <tr>
                                    <th scope="col" width="32%">Home</th>
                                    <th scope="col" width="32%">Away</th>
                                    <th scope="col" width="11%">Score</th>
                                    <th scope="col" width="17%">Date</th>
                                    <th scope="col" width="5%">Info</th>
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
                                        @if($last_fixture->time->status == "LIVE")
                                            <td scope="row">{{date("Y-m-d H:i", strtotime($last_fixture->time->starting_at->date_time))}}<span style="color:#FF0000"> LIVE</span></td>
                                        @else
                                            <td scope="row">{{date("Y-m-d H:i", strtotime($last_fixture->time->starting_at->date_time))}}</td>
                                        @endif
                                        {{-- show button to view fixtures-details --}}
                                        <td scope="row"><a href="{{route("fixturesDetails", ["id" => $last_fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
                                    </tr>
                        @endif
                        @php $last_league_id = $last_fixture->league_id; @endphp
                    @endforeach
                    </tbody>
                    </table>
                @endif
            </div>
            <div class="tab-pane fade" id="upcoming_fixtures" role="tabpanel" aria-labelledby="upcoming_fixtures-tab">
                @if($upcoming_fixtures->count() > 0)
                    @php $last_league_id = 0; @endphp
                    @foreach($upcoming_fixtures as $upcoming_fixture)
                        @php
                            $league = $upcoming_fixture->league->data;
                            $homeTeam = $upcoming_fixture->localTeam->data;
                            $awayTeam = $upcoming_fixture->visitorTeam->data;
                        @endphp
                        @if($upcoming_fixture->league_id == $last_league_id)
                            <tr>
                                <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}</a></td>
                                <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}">{{$awayTeam->name}}</a></td>
                                <td scope="row">{{$upcoming_fixture->scores->localteam_score}} - {{$upcoming_fixture->scores->visitorteam_score}}</td>

                                @if($upcoming_fixture->time->status == "LIVE")
                                    <td scope="row">{{date("Y-m-d H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}<span style="color:#FF0000"> LIVE</span></td>
                                @else
                                    <td scope="row">{{date("Y-m-d H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}</td>
                                @endif
                                <td scope="row"><a href= {{route("fixturesDetails", ["id" => $upcoming_fixture->id])}}><i class="fa fa-info-circle"></i></a></td>
                            </tr>
                        @else
                            <table class="table table-striped table-light table-sm" style="width:100%">
                                <thead>
                                <tr>
                                    <th scope="col" width="32%">Home</th>
                                    <th scope="col" width="32%">Away</th>
                                    <th scope="col" width="11%">Score</th>
                                    <th scope="col" width="17%">Date</th>
                                    <th scope="col" width="5%">Info</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $homeTeam->id])}}">{{$homeTeam->name}}</a></td>
                                    <td scope="row"><a href="{{route("teamsDetails", ["id" => $awayTeam->id])}}">{{$awayTeam->name}}</a></td>
                                    <td scope="row">{{$upcoming_fixture->scores->localteam_score}} - {{$upcoming_fixture->scores->visitorteam_score}}</td>

                                    @if($upcoming_fixture->time->status == "LIVE")
                                        <td scope="row">{{date("Y-m-d H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}<span style="color:#FF0000"> LIVE</span></td>
                                    @else
                                        <td scope="row">{{date("Y-m-d H:i", strtotime($upcoming_fixture->time->starting_at->date_time))}}</td>
                                    @endif
                                    <td scope="row"><a href= {{route("fixturesDetails", ["id" => $upcoming_fixture->id])}}><i class="fa fa-info-circle"></i></a></td>
                                </tr>
                        @endif
                        @php $last_league_id = $upcoming_fixture->league_id; @endphp
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
                                <caption>{{$standings->name}}</caption>
                            @endif
                            <thead>
                                <tr>
                                    <th scope="col" width="1%">No.</th>
                                    <th scope="col" width="35%">Team name</th>
                                    <th scope="col">Played</th>
                                    <th scope="col">Won</th>
                                    <th scope="col">Draw</th>
                                    <th scope="col">Lost</th>
                                    <th scope="col">Goals</th>
                                    <th scope="col">Points</th>
                                    <th scope="col" width="24%">Form</th>
                                </tr>
                            </thead>
                            <tbody>
                                    @foreach($standing as $team)
                                        <tr>
                                            <td scope="row">{{$team->position}}</td>
                                            <td scope="row"><a href ="{{route("teamsDetails", ["id" => $team->team_id])}}">{{$team->team_name}}</a></td>
                                            <td scope="row">{{$team->overall->games_played}}</td>
                                            <td scope="row">{{$team->overall->won}}</td>
                                            <td scope="row">{{$team->overall->draw}}</td>
                                            <td scope="row">{{$team->overall->lost}}</td>
                                            <td scope="row">{{$team->overall->goals_scored}}:{{$team->overall->goals_against}} ({{$team->total->goal_difference}})</td>
                                            <td scope="row">{{$team->points}}</td>
                                            @php $recent_forms = str_split($team->recent_form); @endphp
                                            <td scope="row">
                                                @foreach($recent_forms as $recent_form)
                                                    @switch($recent_form)
                                                        @case("W")
                                                            <span class="result-icon result-icon-w">{{$recent_form}}</span>
                                                            @break
                                                        @case("D")
                                                            <span class="result-icon result-icon-d">{{$recent_form}}</span>
                                                            @break
                                                        @case("L")
                                                            <span class="result-icon result-icon-l">{{$recent_form}}</span>
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
                        <thead>
                            <tr>
                                <th scope="col" width="1%">No.</th>
                                <th scope="col">Player name</th>
                                <th scope="col">Team</th>
                                <th scope="col">Goals</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topscorers as $topscorer)
                                <tr>
                                    <td scope="row">{{$topscorer->position}}</td>
                                    @php
                                        if(isset($topscorer->player->data->nationality)) {
                                            $topscorer->player->data->nationality = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($topscorer->player->data->nationality);
                                        } else {
                                            $topscorer->player->data->nationality = "Unknown";
                                        }
                                    @endphp
                                    <td scope="row"><img src="/images/flags/shiny/16/{{$topscorer->player->data->nationality}}.png">&nbsp;&nbsp;{{$topscorer->player->data->common_name}}</td>
                                    <td scope="row"><a href ="{{route("teamsDetails", ["id" => $team->team_id])}}">{{$topscorer->team->data->name}}</a></td>
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
@extends("layouts.default")

@section("content")
    <div class = "container">
        <h1>@lang("application.Livescores") - {{date($date_format)}} </h1>

        @if(isset($livescores))
            @if(count($livescores) >= 1 && gettype($livescores) == "array")
                @if(count($livescores) >= 100)
                    <p style="color:red">@lang("application.msg_too_much_results", ["count" => count($livescores)])</p>
                @endif
                @php $last_league_id = 0; @endphp
                @foreach($livescores as $livescore)
                    @if(in_array($livescore->time->status, array("LIVE", "HT", "ET", "PEN_LIVE", "BREAK", "AU")))
                        @continue
                    @endif
                    @php
                        $league = $livescore->league->data;
                        $homeTeam = $livescore->localTeam->data;
                        $awayTeam = $livescore->visitorTeam->data;
                        if($livescore->scores->localteam_score > $livescore->scores->visitorteam_score && in_array($livescore->time->status,  array("FT", "AET", "FT_PEN"))) {
                            $winningTeam = $homeTeam->name;
                        } elseif ($livescore->scores->localteam_score == $livescore->scores->visitorteam_score && in_array($livescore->time->status,  array("FT", "AET", "FT_PEN"))) {
                            $winningTeam = "draw";
                        } elseif ($livescore->scores->localteam_score < $livescore->scores->visitorteam_score && in_array($livescore->time->status,  array("FT", "AET", "FT_PEN"))) {
                            $winningTeam = $awayTeam->name;
                        } else {
                            $winningTeam = "TBD";
                        }
                    @endphp
                    @if($livescore->league_id == $last_league_id)
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
                            @switch($livescore->time->status)
                                @case("FT_PEN")
                                    <td scope="row">{{$livescore->scores->localteam_score}} - {{$livescore->scores->visitorteam_score}} ({{$livescore->scores->localteam_pen_score}} - {{$livescore->scores->visitorteam_pen_score}})</td>
                                    @break
                                @case("AET")
                                    <td scope="row">{{$livescore->scores->localteam_score}} - {{$livescore->scores->visitorteam_score}} (ET)</td>
                                    @break
                                @default
                                    <td scope="row">{{$livescore->scores->localteam_score}} - {{$livescore->scores->visitorteam_score}}</td>
                                    @break
                            @endswitch
    
                            <td scope="row">{{date($date_format . " H:i", strtotime($livescore->time->starting_at->date_time))}}
                            @if($livescore->time->status == "LIVE")
                                <span style="color:#FF0000">LIVE</span>
                            @endif
                            </td>
                            <td scope="row"><a href="{{route("fixturesDetails", ["id" => $livescore->id])}}"><i class="fa fa-info-circle"></i></a></td>
                        </tr>
                    @else
                        <table class="table table-striped table-light table-sm" width="100%">
                            <caption><a href="{{route("leaguesDetails", ["id" => $league->id])}}" style="font-weight: bold">{{$league->name}}</a></caption>
                            <thead>
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
                                @switch($livescore->time->status)
                                    @case("FT_PEN")
                                        <td scope="row">{{$livescore->scores->localteam_score}} - {{$livescore->scores->visitorteam_score}} ({{$livescore->scores->localteam_pen_score}} - {{$livescore->scores->visitorteam_pen_score}})</td>
                                        @break
                                    @case("AET")
                                        <td scope="row">{{$livescore->scores->localteam_score}} - {{$livescore->scores->visitorteam_score}} (ET)</td>
                                        @break
                                    @default
                                        <td scope="row">{{$livescore->scores->localteam_score}} - {{$livescore->scores->visitorteam_score}}</td>
                                        @break
                                @endswitch

                                <td scope="row">{{date($date_format . " H:i", strtotime($livescore->time->starting_at->date_time))}}</td>

                                <td scope="row"><a href="{{route("fixturesDetails", ["id" => $livescore->id])}}"><i class="fa fa-info-circle"></i></a></td>
                            </tr>
                    @endif
                    @php $last_league_id = $livescore->league_id; @endphp
                @endforeach
                </tbody>
            </table>
            @else
                <p>@lang("application.msg_no_livescores_today")</p>
            @endif
        @endif
    </div>
@endsection
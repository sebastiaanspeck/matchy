@extends("layouts.default")

@section("content")
    <div class = "container">
        @if(isset($date))
            <h1>Fixtures - {{$date}}</h1>
        @else
            <h1>Fixtures - {{$fromDate}} - {{$toDate}}</h1>
        @endif

        @if(count($fixtures) >= 1 && gettype($fixtures) == "array")
            @if(count($fixtures) >= 100)
                <p style="color:red">We only show the first {{count($fixtures)}} results. The data shown might now be complete. We try to fix this in a later version.</p>
            @endif

            @php $last_league_id = 0; @endphp
            @foreach($fixtures as $fixture)
                @php
                    $league = $fixture->league->data;
                    $homeTeam = $fixture->localTeam->data;
                    $awayTeam = $fixture->visitorTeam->data;
                    if($fixture->scores->localteam_score > $fixture->scores->visitorteam_score && in_array($fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                        $winningTeam = $homeTeam->name;
                    } elseif ($fixture->scores->localteam_score == $fixture->scores->visitorteam_score && in_array($fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                        $winningTeam = "draw";
                    } elseif ($fixture->scores->localteam_score < $fixture->scores->visitorteam_score && in_array($fixture->time->status,  array("FT", "AET", "FT_PEN"))) {
                        $winningTeam = $awayTeam->name;
                    } else {
                        $winningTeam = "TBD";
                    }
                @endphp
                @if($fixture->league_id == $last_league_id)
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
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} ({{$fixture->scores->localteam_pen_score}} - {{$fixture->scores->visitorteam_pen_score}})</td>
                                @break
                            @case("AET")
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} (ET)</td>
                                @break
                            @default
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}}</td>
                                @break
                        @endswitch

                        @if($fixture->time->status == "LIVE")
                            <td scope="row">{{date("Y-m-d H:i", strtotime($fixture->time->starting_at->date_time))}}<span style="color:#FF0000">LIVE</span></td>
                        @else
                            <td scope="row">{{date("Y-m-d H:i", strtotime($fixture->time->starting_at->date_time))}}</td>
                        @endif
                        <td scope="row"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
                    </tr>
                @else
                    <table class="table table-striped table-light table-sm" width="100%">
                        <caption><a href="{{route("leaguesDetails", ["id" => $league->id])}}" style="font-weight: bold">{{$league->name}}</a></caption>
                        <thead>
                        <tr>
                            <th scope="col" width="35%">Home team</th>
                            <th scope="col" width="35%">Away team</th>
                            <th scope="col" width="10%">Score</th>
                            <th scope="col" width="17%">Date and time</th>
                            <th scope="col" width="3%">Info</th>
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
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} ({{$fixture->scores->localteam_pen_score}} - {{$fixture->scores->visitorteam_pen_score}})</td>
                                @break
                                @case("AET")
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}} (ET)</td>
                                @break
                                @default
                                <td scope="row">{{$fixture->scores->localteam_score}} - {{$fixture->scores->visitorteam_score}}</td>
                                @break
                            @endswitch

                            @if(in_array($fixture->time->status, array("LIVE", "HT")))
                                <td scope="row">{{date("Y-m-d H:i", strtotime($fixture->time->starting_at->date_time))}}<span style="color:#FF0000">{{$fixture->time->status}}</span></td>
                            @else
                                <td scope="row">{{date("Y-m-d H:i", strtotime($fixture->time->starting_at->date_time))}}</td>
                            @endif
                            <td scope="row"><a href="{{route("fixturesDetails", ["id" => $fixture->id])}}"><i class="fa fa-info-circle"></i></a></td>
                        </tr>
                @endif
                @php $last_league_id = $fixture->league_id; @endphp
            @endforeach
        @else
            <p>No matches found for {{$date}}</p>
        @endif
    </div>
@endsection
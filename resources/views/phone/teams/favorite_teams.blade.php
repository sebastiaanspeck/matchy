@extends("layouts.default")

@section("content")
    <div class = "container">
        <h3>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Favorite teams") }}</h3>

        @if(count($teams) >= 1)
            <table class="table table-striped table-light table-sm" width="100%">
                <thead class="thead-dark">
                <tr>
                    <th scope="col" width="50%">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Team name") }}</th>
                    <th scope="col" width="50%">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Country") }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($teams as $team)
                    @php
                        $country = $team->country->data;

                        $teamLogo = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getTeamLogo($team->logo_path);
                    @endphp
                    <tr>
                        <td scope="row" width="25%"><a href="{{route("teamsDetails", ["id" => $team->id])}}"><img src="{{ $teamLogo }}" alt="team_logo">&nbsp;{{ $team->name }}</a></td>
                        @php $country->flag = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($country->name); @endphp
                        <td scope="row" width="50%"><img src="/images/flags/shiny/16/{{$country->flag}}.png" alt="countryflag">&nbsp;{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("countries", $country->name) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div>
                {{$teams->links()}}
            </div>
        @else
            <span style="font-weight: bold">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "msg_no_favorite_teams") }}</span>
        @endif
    </div>
@endsection
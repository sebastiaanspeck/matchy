@extends("layouts.default")

@section("content")
    <div class = "container">
        <h3>{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Leagues") }}</h3>

        @if(count($leagues) >= 1)
            @php $favorite_leagues = \App\Http\Controllers\Filebase\FilebaseController::getField('favorite_leagues'); @endphp
            <table class="table table-striped table-light table-sm" width="100%">
                <thead class="thead-dark">
                    <tr>
                        <th scope="col" width="50%">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "League name") }}</th>
                        <th scope="col" width="50%">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Country") }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leagues as $league)
                        @php
                            $country = $league->country->data;
                            $favorite_league = "far";
                            if (in_array($league->id, $favorite_leagues)) {
                                $favorite_league = "fas";
                            }
                        @endphp
                        <tr>
                            <td scope="row" width="25%"><a href="{{ route("setFavoriteLeagues", ["id" => $league->id]) }}"><i class="{{ $favorite_league }} fa-star fa-fw" aria-hidden="true"></i></a>&nbsp;<a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("leagues", $league->name) }}</a></td>
                            @php $country->flag = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($country->name); @endphp
                            <td scope="row" width="50%"><img src="/images/flags/shiny/16/{{$country->flag}}.png" alt="countryflag">&nbsp;{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("countries", $country->name) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div>
                {{$leagues->links()}}
            </div>
        @else
            <p>{{$leagues}}</p>
        @endif
    </div>
@endsection
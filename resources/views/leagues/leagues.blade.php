@extends("layouts.default")

@section("content")
    <div class = "container">
        <h1>{{ Lang::has("application.Leagues") ? trans("application.Leagues") : Log::emergency("Missing application translation for: Leagues")
 . "Leagues" }}</h1>

        @if(count($leagues) >= 1)
            <table class="table table-striped table-light table-sm" width="100%">
                <thead>
                    <tr>
                        <th scope="col" width="50%">{{ Lang::has("application.League name") ? trans("application.League name") : Log::emergency("Missing application translation for: League name")
 . "League name" }}</th>
                        <th scope="col" width="50%">{{ Lang::has("application.Country") ? trans("application.Country") : Log::emergency("Missing application translation for: Country")
 . "Country" }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leagues as $league)
                        @php $country = $league->country->data; @endphp
                        <tr>
                            <td scope="row" width="25%"><a href="{{route("leaguesDetails", ["id" => $league->id])}}">{{ Lang::has("leagues." . $league->name) ? trans("leagues." . $league->name) : Log::critical("Missing league translation for: " . $league->name)   . $league->name }}</a></td>
                            @php $country->flag = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($country->name); @endphp
                            <td scope="row" width="50%"><img src="/images/flags/shiny/16/{{$country->flag}}.png">&nbsp;&nbsp;{{ Lang::has("countries." . $country->name) ? trans("countries." . $country->name) : Log::warning("Missing application translation for: " . $country->name)
 . $country->name }}</td>
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
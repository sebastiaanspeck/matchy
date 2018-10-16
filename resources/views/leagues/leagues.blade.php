@extends('layouts.default')

@section('content')
    <div class = "container">
        <h1>@lang('application.Leagues')</h1>

        @if(count($leagues) >= 1)
            @php
                $currentYear = \Carbon\Carbon::now()->year;
                $nextYear = \Carbon\Carbon::now()->addYear()->year;
                $season = $currentYear . "/" . $nextYear;
            @endphp
            <table class='table table-striped table-light table-sm' width='100%'>
                <thead>
                    <tr>
                        <th scope="col" width="50%">@lang('application.League name')</th>
                        <th scope="col" width="50%">@lang('application.Country')</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($leagues as $league)
                        @if(!in_array($league->season->data->name, array($season, $currentYear)))
                            @continue
                        @endif
                        @php $country = $league->country->data; @endphp
                        <tr>
                            <td scope="row" width="25%"><a href="{{route("leaguesDetails", ["id" => $league->id])}}">@lang('competitions.' . $league->name)</a></td>
                            @php $country->flag = \App\Http\Controllers\SoccerAPI\SoccerAPIController::getCountryFlag($country->name); @endphp
                            <td scope="row" width="50%"><img src="/images/flags/shiny/16/{{$country->flag}}.png">&nbsp;&nbsp;{{$country->name}}</td>
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
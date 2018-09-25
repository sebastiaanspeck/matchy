@extends('layouts.default')

@section('content')
    <div class = "container">

        <h1> Leagues </h1>

        @if(isset($leagues))
            @if(count($leagues) >= 2)
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>League name</th>
                        <th>Country</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($leagues as $league)
                        <tr>
                            {{--<td><a href="{{action('SoccerAPI\SoccerAPIController@viewLeagueDetails', ['league_id' => $project['id']])}}"> {{$league->'name'}} </a></td>--}}
                            <td> {{$league->name}} </td>
                            @php
                                if($league->country->data->extra != null) {
                                    $flag_code = strtolower($league->country->data->extra->iso);
                                    echo "<td>" . $league->country->data->name . "&nbsp;&nbsp; <img src=\"flags/" . $flag_code . ".gif\"> </td>";
                                } elseif ($league->country->data->name == "Europe") {
                                    echo "<td>" . $league->country->data->name . "&nbsp;&nbsp; <img src=\"flags/europeanunion.gif\"> </td>";
                                } else {
                                    echo "<td>" . $league->country->data->name . "</td>";
                                }
                            @endphp
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p> {{ $leagues }}</p>
            @endif
        @endif

    </div>
@endsection
@extends('layouts.default')

@section('content')
    <div class = "container">

        @php
            echo "<h1> Livescores - " . date("Y-m-d") . "</h1>";

            if(isset($livescores)) {
                if(count($livescores) >= 2) {
                    $last_league_id = 0;
                    foreach($livescores as $livescore) {
                        $league = $livescore->league->data;
                        $homeTeam = $livescore->localTeam->data;
                        $awayTeam = $livescore->visitorTeam->data;
                        if($livescore->league_id == $last_league_id) {
                            echo "<tr>";
                                echo "<td>" . $homeTeam->name . "</td>";
                                echo "<td>" . $awayTeam->name . "</td>";
                                echo "<td>" . $livescore->scores->ft_score . "</td>";
                                echo "<td>" . $livescore->time->starting_at->date_time . "</td>";
                            echo "</tr>";
                        } else {
                            echo "<table class='table table-responsive' width='100%'>";
                                echo "<br><h3>" . $league->name . "</h3>"; // add link to league-details page
                                echo "<col style='width:25%'>";
                                echo "<col style='width:25%'>";
                                echo "<col style='width:25%'>";
                                echo "<col style='width:25%'>";
                                echo "<thead>";
                                    echo "<tr>";
                                        echo "<th>Home team</th>";
                                        echo "<th>Away team</th>";
                                        echo "<th>Result</th>";
                                        echo "<th>Date and time</th>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                    echo "<tr>";
                                        echo "<td>" . $homeTeam->name . "</td>";
                                        echo "<td>" . $awayTeam->name . "</td>";
                                        echo "<td>" . $livescore->scores->ft_score . "</td>";
                                        echo "<td>" . $livescore->time->starting_at->date_time . "</td>";
                                    echo "<tr>";
                        }
                        $last_league_id = $livescore->league_id;
                    }
                } else {
                    echo "<p> No livescores for today.</p>";
                }
            }
        @endphp

    </div>
@endsection
@extends('layouts.default')

@section('content')
    <div class = "container">

        @php
            echo "<h1> Livescores - " . date("Y-m-d") . "</h1>";

            if(isset($livescores)) {
                if(count($livescores) >= 1 && gettype($livescores) == 'array') {
                    if(count($livescores) == 100) {
                        echo "<p style='color:red'> We only show the first 100 results. The data shown might now be complete. We try to fix this in a later version. </p>";
                    }
                    $last_league_id = 0;
                    foreach($livescores as $livescore) {
                    	if(in_array($livescore->time->status, array('LIVE', 'HT', 'ET', 'PEN_LIVE', 'AET', 'BREAK', 'AU'))) {
                    	    continue;
                    	}
                        $league = $livescore->league->data;
                        $homeTeam = $livescore->localTeam->data;
                        $awayTeam = $livescore->visitorTeam->data;
                        if($livescore->scores->localteam_score > $livescore->scores->visitorteam_score && $livescore->time->status == 'FT') {
                            $winningTeam = $homeTeam->name;
                        } elseif ($livescore->scores->localteam_score == $livescore->scores->visitorteam_score && $livescore->time->status == 'FT'){
                            $winningTeam = 'draw';
                        } elseif ($livescore->scores->localteam_score < $livescore->scores->visitorteam_score && $livescore->time->status == 'FT') {
                            $winningTeam = $awayTeam->name;
                        } else {
                            $winningTeam = 'TBD';
                        }
                        if($livescore->league_id == $last_league_id) {
                            echo "<tr>";
                                if($winningTeam == $homeTeam->name) {
                                    echo "<td scope='row' style='color:green'>" . $homeTeam->name . "</td>";
                                    echo "<td scope='row' style='color:red'>" . $awayTeam->name . "</td>";
                                } elseif($winningTeam == $awayTeam->name) {
                                    echo "<td scope='row' style='color:red'>" . $homeTeam->name . "</td>";
                                    echo "<td scope='row' style='color:green'>" . $awayTeam->name . "</td>";
                                } elseif($winningTeam == 'draw') {
                                    echo "<td scope='row' style='color:orange'>" . $homeTeam->name . "</td>";
                                    echo "<td scope='row' style='color:orange'>" . $awayTeam->name . "</td>";
                                } else {
                                    echo "<td scope='row'>" . $homeTeam->name . "</td>";
                                    echo "<td scope='row'>" . $awayTeam->name . "</td>";
                                }
                                echo "<td scope='row'>" . $livescore->scores->localteam_score . " - " . $livescore->scores->visitorteam_score . "</td>";
                                echo "<td scope='row'>" . date('Y-m-d H:i', strtotime($livescore->time->starting_at->date_time)) . "</td>";
                                echo "<td scope='row'><a href='#'>Details</a></td>"; //link to details page (fixtures/{id})
                            echo "</tr>";
                        } else {
                            echo "<table class='table table-striped table-light' width='100%'>";
                                echo "<br><h3>" . $league->name . "</h3>"; // add link to league-details page
                                echo "<thead>";
                                    echo "<tr>";
                                        echo "<th scope='col' width='35%'>Home team</th>";
                                        echo "<th scope='col' width='35%'>Away team</th>";
                                        echo "<th scope='col' width='10%'>Score</th>";
                                        echo "<th scope='col' width='15%'>Date and time</th>";
                                        echo "<th scope='col' width='5%'>Details</th>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                    echo "<tr>";
                                        if($winningTeam == $homeTeam->name) {
                                            echo "<td scope='row' style='color:green'>" . $homeTeam->name . "</td>";
                                            echo "<td scope='row' style='color:red'>" . $awayTeam->name . "</td>";
                                        } elseif($winningTeam == $awayTeam->name) {
                                            echo "<td scope='row' style='color:red'>" . $homeTeam->name . "</td>";
                                            echo "<td scope='row' style='color:green'>" . $awayTeam->name . "</td>";
                                        } elseif($winningTeam == 'draw') {
                                            echo "<td scope='row' style='color:orange'>" . $homeTeam->name . "</td>";
                                            echo "<td scope='row' style='color:orange'>" . $awayTeam->name . "</td>";
                                        } else {
                                            echo "<td scope='row'>" . $homeTeam->name . "</td>";
                                            echo "<td scope='row'>" . $awayTeam->name . "</td>";
                                        }
                                        echo "<td scope='row'>" . $livescore->scores->localteam_score . " - " . $livescore->scores->visitorteam_score . "</td>";
                                        echo "<td scope='row'>" . date('Y-m-d H:i', strtotime($livescore->time->starting_at->date_time)) . "</td>";
                                        echo "<td scope='row'><a href='#'>Details</a></td>"; //link to details page (fixtures/{id})
                                    echo "</tr>";
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
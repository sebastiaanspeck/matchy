@extends('layouts.default')

@section('content')
    <div class = "container">

        @php
            if($fromDate == $toDate && $fromDate != '' && $toDate != '') {
                echo "<h1> Fixtures - {$fromDate} </h1>";
            } elseif($fromDate == $toDate && $fromDate == '' && $toDate == ''){
                echo "<h1> Fixtures </h1>";
            } else {
                echo "<h1> Fixtures - {$fromDate} - {$toDate} </h1>";
            }

            if(isset($fixtures)) {
                if(count($fixtures) >= 1 && gettype($fixtures) == 'array') {
                    if(count($fixtures) == 100) {
                        echo "<p style='color:red'> We only show the first 100 results. The data shown might now be complete. We try to fix this in a later version. </p>";
                    }
                    $last_league_id = 0;
                    foreach($fixtures as $fixture) {
                        $league = $fixture->league->data;
                        $homeTeam = $fixture->localTeam->data;
                        $awayTeam = $fixture->visitorTeam->data;
                        if($fixture->scores->localteam_score > $fixture->scores->visitorteam_score && $fixture->time->status == 'FT') {
                            $winningTeam = $homeTeam->name;
                        } elseif ($fixture->scores->localteam_score == $fixture->scores->visitorteam_score && $fixture->time->status == 'FT'){
                            $winningTeam = 'draw';
                        } elseif ($fixture->scores->localteam_score < $fixture->scores->visitorteam_score && $fixture->time->status == 'FT') {
                            $winningTeam = $awayTeam->name;
                        } else {
                            $winningTeam = 'TBD';
                        }
                        if($fixture->league_id == $last_league_id) {
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
                                echo "<td scope='row'>" . $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score . "</td>";
                                echo "<td scope='row'>" . date('Y-m-d H:i', strtotime($fixture->time->starting_at->date_time)) . "</td>";
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
                                        echo "<td scope='row'>" . $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score . "</td>";
                                        echo "<td scope='row'>" . date('Y-m-d H:i', strtotime($fixture->time->starting_at->date_time)) . "</td>";
                                        echo "<td scope='row'><a href='#'>Details</a></td>"; //link to details page (fixtures/{id})
                                    echo "</tr>";
                        }
                        $last_league_id = $fixture->league_id;
                    }
                } else {
                    echo "<p> You made a invalid request. </p>";
                }

            }
        @endphp

    </div>
@endsection
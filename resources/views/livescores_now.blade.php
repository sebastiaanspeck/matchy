@extends('layouts.default')

@section('meta')
    <meta http-equiv="refresh" content="60">
@endsection

@section('content')
    <div class = "container">

        @php
            echo "<h1> Livescores in-play - " . date("Y-m-d") . "</h1>";
            echo "<p>Last update: " . date("Y-m-d H:i:s") . "</p>";
        
            if(isset($livescores)) {
                if(count($livescores) >= 1) {
                	if(count($livescores) == 100) {
                        echo "<p style='color:red'> We only show the first 100 results. The data shown might now be complete. We try to fix this in a later version. </p>";
                    }
                    $last_league_id = 0;
                    foreach($livescores as $livescore) {
                        if(in_array($livescore->time->status, array('NS', 'FT', 'FT_PEN', 'CANCL', 'POSTP', 'INT', 'ABAN', 'SUSP', 'AWARDED', 'DELAYED', 'TBA', 'WO', 'AU'))) {
                    	    continue;
                    	}
                        $league = $livescore->league->data;
                        $homeTeam = $livescore->localTeam->data;
                        $awayTeam = $livescore->visitorTeam->data;
                        if($livescore->league_id == $last_league_id) {
                            echo "<tr>";
                                echo "<td scope='row'>" . $homeTeam->name . "</td>";
                                echo "<td scope='row'>" . $awayTeam->name . "</td>";
                                echo "<td scope='row'>" . $livescore->scores->localteam_score . " - " . $livescore->scores->visitorteam_score . "</td>";
                                if(in_array($livescore->time->status, array('LIVE', 'HT', 'ET', 'PEN_LIVE', 'AET', 'BREAK'))) {
                                    if($livescore->time->status == 'HT') {
                                        echo "<td scope='row'>HT</td>";
                                    } elseif ($livescore->time->minute == 'None' && $livescore->time->added_time == 0) {
                                        echo "<td scope='row'>0&apos;</td>";
                                    } elseif(in_array($livescore->time->added_time, array(0, 'None'))) {
                                        echo "<td scope='row'>" . $livescore->time->minute . "&apos;</td>";
                                    } elseif(!in_array($livescore->time->added_time, array(0, 'None'))) {
                                        echo "<td scope='row'>" . $livescore->time->minute . "&apos;+" . $livescore->time->added_time . "</td>";
                                    }
                                } else {
                                    echo "<td scope='row'>" . date('Y-m-d H:i', strtotime($livescore->time->starting_at->date_time)) . "</td>";
                                }
                                echo "<td scope='row'><a href='#'>Details</a></td>"; //link to details page (fixtures/{id})
                            echo "</tr>";
                        } else {
                            echo "<table class='table table-striped table-light' width='100%'>";
                                echo "<br><h3>" . $league->name . "</h3>"; // add link to league-details page
                                echo "<thead>";
                                    echo "<tr>";
                                        echo "<th scope='col' width='37.5%'>Home team</th>";
                                        echo "<th scope='col' width='37.5%'>Away team</th>";
                                        echo "<th scope='col' width='10%'>Score</th>";
                                        echo "<th scope='col' width='10%'>Time</th>";
                                        echo "<th scope='col' width='5%'>Details</th>";
                                    echo "</tr>";
                                echo "</thead>";
                                echo "<tbody>";
                                    echo "<tr>";
                                        echo "<td scope='row'>" . $homeTeam->name . "</td>";
                                        echo "<td scope='row'>" . $awayTeam->name . "</td>";
                                        echo "<td scope='row'>" . $livescore->scores->localteam_score . " - " . $livescore->scores->visitorteam_score . "</td>";
                                        if(in_array($livescore->time->status, array('LIVE', 'HT', 'ET', 'PEN_LIVE', 'AET', 'BREAK'))) {
                                            if($livescore->time->status == 'HT') {
                                                echo "<td scope='row'>HT</td>";
                                            } elseif ($livescore->time->minute == 'None' && $livescore->time->added_time == 0) {
                                                echo "<td scope='row'>0'</td>";
                                            } elseif(in_array($livescore->time->added_time, array(0, 'None'))) {
                                                echo "<td scope='row'>" . $livescore->time->minute . "&apos;</td>";
                                            } elseif(!in_array($livescore->time->added_time, array(0, 'None'))) {
                                                echo "<td scope='row'>" . $livescore->time->minute . "&apos;+" . $livescore->time->added_time . "</td>";
                                            }
                                        } else {
                                            echo "<td scope='row'>" . date('Y-m-d H:i', strtotime($livescore->time->starting_at->date_time)) . "</td>";
                                        }
                                        echo "<td scope='row'><a href='#'>Details</a></td>"; //link to details page (fixtures/{id})
                                    echo "</tr>";
                        }
                        $last_league_id = $livescore->league_id;
                    }
                    if($last_league_id == 0) {
                        echo "<p> No livescores in-play right now.</p>";
                    }
                } else {
                    echo "<p> No livescores in-play right now.</p>";
                }
            }
        @endphp

    </div>
@endsection
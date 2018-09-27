@extends('layouts.default')

@section('style')
    .borderless td, .borderless th {
        border: none;
    }
@endsection

@section('content')
    <div class = "container">
        @php

            $league = $fixture->league->data;
            $homeTeam = $fixture->localTeam->data;
            $homeTeamId = $homeTeam->id;
            $awayTeam = $fixture->visitorTeam->data;
            $events = $fixture->events->data;

            echo "<div id='heading' style='text-align: center'>";
                echo "<h2><a href=" . route('leaguesDetails', ['id' => $league->id]) . ">" . $league->name . "</a></h2>";
                    echo "<table width='100%'>";
                        echo "<tr>";
                            echo "<td width='49%'><img src=" . $homeTeam->logo_path . "></td>";
                            echo "<td width='2%'><h1> - </h1></td>";
                            echo "<td width='49%'><img src=" . $awayTeam->logo_path . "></td>";
                        echo "</tr>";
                        echo "<tr>";
                            echo "<td width='49%' style='vertical-align: top'><h1>" . $homeTeam->name . "</h1></td>";
                            echo "<td></td>";
                            echo "<td width='49%' style='vertical-align: top'><h1>" . $awayTeam->name . "</h1></td>";
                        echo "</tr>";
                    echo "</table>";


                if($fixture->time->status == 'FT_PEN') {
                    echo "<h3>" . $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score . "</h3><h6>(" . $fixture->scores->localteam_pen_score . " - " . $fixture->scores->visitorteam_pen_score . ") penalties" ."</h6>";
                } elseif($fixture->time->status == 'AET') {
                    echo "<h3>" . $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score . " (ET) </h3>";
                } else {
                    echo "<h3>" . $fixture->scores->localteam_score . " - " . $fixture->scores->visitorteam_score . " </h3>";
                }

                if(in_array($fixture->time->status, array('LIVE', 'HT', 'ET', 'PEN_LIVE', 'AET', 'BREAK'))) {
                    if($fixture->time->status == 'HT') {
                        echo "<h6>" . $fixture->time->status . "</h6>";
                    } elseif ($fixture->time->minute == 'None' && $fixture->time->added_time == 0) {
                        echo "<td scope='row'>0&apos;</td>";
                    } elseif(in_array($fixture->time->added_time, array(0, 'None'))) {
                        echo "<h6>" . $fixture->time->status . " - " . $fixture->time->minute . "&apos;</h6>";
                    } elseif(!in_array($fixture->time->added_time, array(0, 'None'))) {
                        echo "<h6>" . $fixture->time->status . " - " . $fixture->time->minute . "&apos;+" . $fixture->time->added_time . "</h6>";
                    }
                } else {
                    echo "<h6>" . date('Y-m-d H:i', strtotime($fixture->time->starting_at->date_time)) . "</h6>";
                }

            echo "</div>";

            if(count($events) > 0) {
            	echo "<table class='table borderless table-light' align='center'>";
                    echo "<tbody>";
                        foreach($events as $event) {
                            if(in_array($event->type, array('pen_shootout_goal', 'pen_shootout_miss'))) {
                                continue;
                            }
                            echo "<tr>";
                                if($event->team_id == $homeTeamId) {
                                    if($event->type == 'substitution') {
                                        echo "<td scope='row' style='text-align:right' width='1%'>" . $event->minute . "&apos;</td>";
                                        echo "<td scope='row' style='text-align:center' width='1%'><img src='/images/events/" . $event->type . ".svg'></td>";
                                        echo "<td scope='row' style='text-align:left' width='49%'>" . $event->player_name . " (in) " . $event->related_player_name . " (out)" . "</td>";
                                        echo "<td></td>";
                                        echo "<td></td>";
                                        echo "<td></td>";
                                    } else {
                                        echo "<td scope='row' style='text-align:right' width='1%'>" . $event->minute . "&apos;</td>";
                                        echo "<td scope='row' style='text-align:center' width='1%'><img src='/images/events/" . $event->type . ".svg'></td>";
                                        echo "<td scope='row' style='text-align:left' width='49%'>" . $event->player_name . "</td>";
                                        echo "<td></td>";
                                        echo "<td></td>";
                                        echo "<td></td>";
                                    }
                                } else {
                                    if($event->type == 'substitution') {
                                        echo "<td></td>";
                                        echo "<td></td>";
                                        echo "<td></td>";
                                        echo "<td scope='row' style='text-align:right' width='49%'>" . $event->player_name . " (in) " . $event->related_player_name . " (out)</td>";
                                        echo "<td scope='row' style='text-align:center' width='1%'><img src='/images/events/" . $event->type . ".svg'></td>";
                                        echo "<td scope='row' style='text-align:left' width='1%'>" . $event->minute ."&apos;</td>";
                                    } else {
                                        echo "<td></td>";
                                        echo "<td></td>";
                                        echo "<td></td>";
                                        echo "<td scope='row' style='text-align:right' width='49%'>" . $event->player_name . "</td>";
                                        echo "<td scope='row' style='text-align:center' width='1%'><img src='/images/events/" . $event->type . ".svg'></td>";
                                        echo "<td scope='row' style='text-align:left' width='1%'>" . $event->minute ."&apos;</td>";
                                    }
                                }
                            echo "<tr>";
                        }
                    echo "</tbody>";
                echo "</table>";
            }
        
        @endphp
    </div>
@endsection
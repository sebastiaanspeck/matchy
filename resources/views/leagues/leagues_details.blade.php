@extends('layouts.default')
@section('style')
    .result-icon {
        display: inline-block;
        padding: 8px;
        font-size: .75rem;
        line-height: 1.3;
        font-weight: 500;
        color: #fff;
        text-transform: uppercase;
        width: 30px;
        height: 30px;
        text-align: center;
        margin-right: 4px;
    }
    .result-icon-w {
        background-color: #83cd7b;
    }
    .result-icon-l {
        background-color: #dc656a;
    }
    .result-icon-d {
        background-color: #f0be4b;
    }

    .div-toggle:after{
        content: "\f107";
        font-family: FontAwesome;
    }

    .div-toggle.collapsed:after {
        content: "\f105";
    }
@endsection
        {{-- Nav tabs  --}}
        <ul class="nav nav-tabs" id="nav_tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="last_fixtures-tab" data-toggle="tab" href="#last_fixtures" role="tab" aria-controls="last_fixtures" aria-selected="true">Last {{ $last_fixtures->count() }} fixtures</a>
            </li>
            @if($upcoming_fixtures->count() > 0)
                <li class="nav-item">
                    <a class="nav-link" id="upcoming_fixtures-tab" data-toggle="tab" href="#upcoming_fixtures" role="tab" aria-controls="upcoming_fixtures" aria-selected="false">Upcoming {{ $upcoming_fixtures->count() }} fixtures</a>
                </li>
            @endif
            @if(count($standings_raw) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="standings-tab" data-toggle="tab" href="#standings" role="tab" aria-controls="standings" aria-selected="false">Standings</a>
                </li>
            @endif
            @if(count($topscorers) > 0)
                <li class="nav-item">
                    <a class="nav-link" id="topscorers-tab" data-toggle="tab" href="#topscorers" role="tab" aria-controls="topscorers" aria-selected="false">Topscorers</a>
                </li>
            @endif
        </ul>

@section('content')
    <div class = "container">
        @php
        {{-- Tab panes --}}
        <div class="tab-content" id="tab_content">
            <div class="tab-pane fade show active" id="last_fixtures" role="tabpanel" aria-labelledby="last_fixtures-tab">

            echo "<div id='heading' style='text-align: center'>";
                echo "<h2>" . $league->name . "</h2>";
            echo "</div>";
/*            if(count($last_fixtures) > 0) {
                echo "<h3><a href='#last_fixtures' data-toggle='collapse'>Last 10 fixtures</a></h3>";
                    echo "<div id='standings' class='collapse show'>";

            }*/

            if(count($standings_raw) > 0) {
                $standings_legend = array();
                echo "<h3><a href='#standings' class='div-toggle' data-toggle='collapse'>Standings&nbsp;&nbsp;</a></h3>";
                echo "<div id='standings' class='collapse show'>";
                foreach($standings_raw as $standings) {
                	$standing = $standings->standings->data;
                	if(count($standings_raw) > 1) {
                	    echo "<h5>" . $standings->name . "</h5>";
                	}
                	echo "<table class='table table-light'>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th scope='col'>Nr.</th>";
                                echo "<th scope='col' width='35%'>Team name</th>";
                                echo "<th scope='col'>Played</th>";
                                echo "<th scope='col'>Won</th>";
                                echo "<th scope='col'>Draw</th>";
                                echo "<th scope='col'>Lost</th>";
                                echo "<th scope='col'>Goals</th>";
                                echo "<th scope='col'>Points</th>";
                                echo "<th scope='col'>Form</th>";
                                // 24
                            echo "</tr>";
                        echo "<thead>";
                        echo "<tbody>";
                            foreach($standing as $team) {
                                if(strpos($team->result, 'Champions League (Group Stage)') !== false || strpos($team->result, 'Champions League (Play Offs)') !== false || strpos($team->result, 'Championship') !== false && strpos($team->result, 'Relegation') === false || $team->result == "Promotion") {
                                    echo "<tr class='bg-primary'>";
                                    if(!in_array($team->result, $standings_legend)) {
                                        $standings_legend[$team->result] = 'bg-primary';
                                    }
                                }
                                elseif(strpos($team->result, 'Champions League (Qualification)') !== false) {
                                    echo "<tr class='table-primary'>";
                                    if(!array_key_exists($team->result, $standings_legend)) {
                                        $standings_legend[$team->result] = 'table-primary';
                                    }
                                } elseif(strpos($team->result, 'Europa League') !== false || $team->result == "Promotion Play-off") {
                                    echo "<tr class='table-warning'>";
                                    if(!array_key_exists($team->result, $standings_legend)) {
                                        $standings_legend[$team->result] = 'table-warning';
                                    }
                                } elseif(strpos($team->result, '(Relegation)') !== false) {
                                    echo "<tr class='table-danger'>";
                                    if(!array_key_exists($team->result, $standings_legend)) {
                                        $standings_legend[$team->result] = 'table-danger';
                                    }
                                } elseif(strpos($team->result, 'Relegation') !== false) {
                                    echo "<tr class='bg-danger'>";
                                    if(!array_key_exists($team->result, $standings_legend)) {
                                        $standings_legend[$team->result] = 'bg-danger';
                                    }
                                }
                                echo "<td scope='row'>" . $team->position . "</td>";
                                echo "<td scope='row'>" . $team->team_name . "</td>";
                                echo "<td scope='row'>" . $team->overall->games_played . "</td>";
                                echo "<td scope='row'>" . $team->overall->won . "</td>";
                                echo "<td scope='row'>" . $team->overall->draw . "</td>";
                                echo "<td scope='row'>" . $team->overall->lost . "</td>";
                                echo "<td scope='row'>" . $team->overall->goals_scored . ":" . $team->overall->goals_against . " (" . $team->total->goal_difference . ")</td>";
                                echo "<td scope='row'>" . $team->points . "</td>";
                                $recent_forms = str_split($team->recent_form);
                                echo "<td scope='row'>";
                                foreach($recent_forms as $recent_form) {
                                    switch($recent_form) {
                                        case('W'):
                                            echo "<span class='result-icon result-icon-w'>" . $recent_form . "</span>";
                                            break;
                                        case('D'):
                                            echo "<span class='result-icon result-icon-d'>" . $recent_form . "</span>";
                                            break;
                                        case('L'):
                                            echo "<span class='result-icon result-icon-l'>" . $recent_form . "</span>";
                                            break;
                                    }
                                }
                                switch($team->status) {
                                        case('same'):
                                            echo "&nbsp;<i class='fa fa-caret-left'></i>";
                                            break;
                                        case('down'):
                                            echo "&nbsp;<i class='fa fa-caret-down'></i>";
                                            break;
                                        case('up'):
                                            echo "&nbsp;<i class='fa fa-caret-up'></i>";
                                            break;
                                    }
                                echo "</td>";
                                echo "</tr>";
            </div>
            <div class="tab-pane fade" id="upcoming_fixtures" role="tabpanel" aria-labelledby="upcoming_fixtures-tab">
                            }
                        echo "</tbody>";
                    echo "</table>";
                }
                if(count($standings_legend) > 0) {
                	echo "<table>";
                        echo "<thead>";
                            echo "<tr>";
                                echo "<th scope='col'>Legend</th>";
                            echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        foreach($standings_legend as $legend_key => $legend_item) {
                            echo "<tr>";
                                echo "<td scope='row' class=". $legend_item . ">" . $legend_key . "</td>";
                            echo "</tr>";
                        }
                        echo "</tbody>";
                    echo "</table>";
                }


                echo "</div>";
            }


        @endphp
            </div>
            <div class="tab-pane fade" id="topscorers" role="tabpanel" aria-labelledby="topscorers-tab">
            </div>
        </div>
    </div>
@endsection
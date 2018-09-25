@extends('layouts.default')

@section('content')
    <div class = "container">

        @php
            echo "<h1> Leagues </h1>";

            if(isset($leagues)) {
                if(count($leagues) >= 1) {
                    echo "<table class='table table-striped table-light' width='100%'>";
                    echo "<thead>";
                        echo "<tr>";
                            echo "<th scope='col' width='50%'>League name</th>";
                            echo "<th scope='col' width='50%'>Country</th>";
                        echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    foreach($leagues as $league) {
                    	echo "<tr>";
                            echo "<td scope='row' width='50%'>" . $league->name . "</td>";
                            if($league->country->data->extra != null) {
                                $flag_code = strtolower($league->country->data->extra->iso);
                                echo "<td scope='row' width='50%'> " . $league->country->data->name . "&nbsp;&nbsp; <img src=\"flags/" . $flag_code . ".gif\"> </td>";
                            } elseif ($league->country->data->name == "Europe") {
                                echo "<td scope='row' width='50%'>" . $league->country->data->name . "&nbsp;&nbsp; <img src=\"flags/europeanunion.gif\"> </td>";
                            } else {
                                echo "<td scope='row' width='50%'>" . $league->country->data->name . "</td>";
                            }
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                    echo "<div>";
                        echo $leagues->links();
                    echo "</div>";
                } else {
                    echo "<p> {{ $leagues }}</p>";
                }
            }

        @endphp

    </div>
@endsection
<!DOCTYPE html>
<html lang="{{ str_replace("_", "-", app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    @yield("meta")
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Matchy">
    <meta name="author" content="Sebastiaan Speck">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config("app.name") }}</title>

    <!-- Scripts -->
    <script src="{{ asset("js/app.js") }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

    <!-- Styles -->
    <link href="{{ asset("css/app.css") }}" rel="stylesheet">
    <link href="{{ asset("css/style.css") }}" rel="stylesheet">
    
    <style>
        @yield("style")
    </style>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark p-1">
        <!--<nav class="navbar navbar-expand-md navbar-light navbar-laravel fixed-top">-->
            <a class="navbar-brand" href="{{ url("/") }}">
                <i class="fa fa-futbol-o fa-fw" aria-hidden="true"></i>&nbsp;{{ config("app.name") }}
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav mr-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFixtures" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Fixtures") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownFixtures">
                            <a class="dropdown-item" href="{{ route("fixturesByDate", ["day" => "yesterday"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Yesterday") }}</a>
                            <a class="dropdown-item" href="{{ route("fixturesByDate", ["day" => "tomorrow"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Tomorrow") }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLivescores" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Livescores") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownLivescores">
                            <a class="dropdown-item" href="{{ route("livescores", ["type" => "now"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Livescores") }} -  {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "now") }} <span class="badge badge-danger">{{$live}}</span></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route("livescores", ["type" => "today"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Livescores") }} - {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "today") }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLeagues" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Leagues") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownLeagues">
                            <a class="dropdown-item" href="{{ route("leagues") }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "All leagues") }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFavorites" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Favorites") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownFavorites">
                            <a class="dropdown-item" href="{{ route('favoriteTeams') }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "My favorite teams") }}</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "My favorite leagues") }}</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </header>
    
    <main role="main" class="container-fluid main-container">
        @yield("content")
    </main>
    
    <footer class="main-footer">
        <div class="container-fluid">
            <p class="text-muted pull-left">
                Matchy - <span class="badge badge-success"> {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "version") }} 1.5.0</span> - <a href="https://github.com/sebastiaanspeck/matchy" class="text-muted" target="_blank">Github Repo <i class="fa fa-github" aria-hidden="true"></i></a>
            </p>
            <p class="text-muted pull-right">
                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Created with") }} <i class="fa fa-code" aria-hidden="true"></i> & <i class="fa fa-coffee" aria-hidden="true"></i>
            </p>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
</body>
</html>
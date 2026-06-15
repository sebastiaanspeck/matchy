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

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.1/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

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
            <a class="navbar-brand" href="{{ url("/") }}">
                <i class="fas fa-futbol fa-fw" aria-hidden="true"></i>&nbsp;{{ config("app.name") }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse" aria-controls="navbarCollapse" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFixtures" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Fixtures") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownFixtures">
                            <a class="dropdown-item" href="{{ route("fixturesByDate", ["day" => "yesterday"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Yesterday") }}</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route("fixturesByDate", ["day" => "today"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Today") }}</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route("fixturesByDate", ["day" => "tomorrow"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Tomorrow") }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLivescores" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Livescores") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownLivescores">
                            <a class="dropdown-item" href="{{ route("livescores", ["type" => "now"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Livescores") }} -  {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "now") }} <span class="badge bg-danger">{{$live}}</span></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route("livescores", ["type" => "today"]) }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Livescores") }} - {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "today") }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLeagues" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Leagues") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownLeagues">
                            <a class="dropdown-item" href="{{ route("leagues") }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "All leagues") }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFavorites" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Favorites") }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownFavorites">
                            <a class="dropdown-item" href="{{ route('favoriteTeams') }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "My favorite teams") }}</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('favoriteLeagues') }}">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "My favorite leagues") }}</a>
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
        <div class="d-flex justify-content-between px-4">
            <p class="text-muted mb-0">
                Matchy - <span class="badge bg-success">{{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "version") }} 2.0.1</span> - <a href="https://github.com/sebastiaanspeck/matchy" class="text-muted" target="_blank">Github Repo&nbsp;<i class="fab fa-github fa-fw"></i></a>
            </p>
            <p class="text-muted mb-0">
                {{ \App\Http\Controllers\SoccerAPI\SoccerAPIController::translateString("application", "Created with") }}&nbsp;<i class="fa fa-code fa-fw" aria-hidden="true"></i>&nbsp;&&nbsp;<i class="fa fa-coffee fa-fw" aria-hidden="true"></i>
            </p>
        </div>
    </footer>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
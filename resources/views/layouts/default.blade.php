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

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset("css/app.css") }}" rel="stylesheet">
    
    <style>
        html {
            position: relative;
            min-height: 100%;
        }

        body {
            margin-bottom: 60px;
            padding-top: 60px;
            overflow-y: scroll;
        }

        .container-fluid {
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
            margin-right: auto;
            margin-left: auto;
        }
        
        .main-container {
            padding-top: 10px;
            padding-bottom: 10px;
        }
        
        .main-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 60px;
            line-height: 60px;
            background-color: #E8EAF6;
        }
        
        .main-footer p {
            margin-bottom: 0;
        }
        
        /*
         * Navbar
         */
        
        .navbar-brand {
            padding: .75rem 1rem;
            font-size: 1rem;
        }
        
        .navbar-nav .nav-link {
            padding-right: .5rem;
            padding-left: .5rem;
        }
        
        a:link {
            color: black;
            background-color: transparent;
            text-decoration:none;
        }
        a:visited {
            color: black;
            background-color: transparent;
            text-decoration:none;
        }
        a:hover {
            color: grey;
        }
        
        caption {
            caption-side: top;
        }

        .won-team:link {
            color: green;
            background-color: transparent;
            text-decoration:none;
        }
        .won-team:visited {
            color: green;
            background-color: transparent;
            text-decoration:none;
        }
        .won-team:hover {
            color: #32B232;
        }

        .lost-team:link {
            color: #ff0000;
            background-color: transparent;
            text-decoration:none;
        }
        .lost-team:visited {
            color: #ff0000;
            background-color: transparent;
            text-decoration:none;
        }
        .lost-team:hover {
            color: #FF5050;
        }

        .draw-team:link {
            color: #FF8040;
            background-color: transparent;
            text-decoration:none;
        }
        .draw-team:visited {
            color: #FF8040;
            background-color: transparent;
            text-decoration:none;
        }
        .draw-team:hover {
            color: #FFB272;
        }

        .result-icon {
            display: inline-block;
            padding: 4px;
            font-size: .75rem;
            line-height: 1.3;
            font-weight: 500;
            color: #fff;
            text-transform: uppercase;
            width: 20px;
            height: 20px;
            text-align: center;
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

        .live {
            animation: blinker 1.5s linear infinite;
            color: #ff0000;
        }

        @keyframes blinker {
            50% {
                opacity: 0;
            }
        }

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
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFixtures" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ Lang::has("application.Fixtures") ? trans("application.Fixtures") : Log::emergency("Missing application translation for: Fixtures") . "Fixtures" }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownFixtures">
                            <a class="dropdown-item" href="{{route("fixturesByDate", ["day" => "yesterday"])}}">{{ Lang::has("application.Yesterday") ? trans("application.Yesterday") : Log::emergency("Missing application translation for: Yesterday") . "Yesterday" }}</a>
                            <a class="dropdown-item" href="{{route("fixturesByDate", ["day" => "tomorrow"])}}">{{ Lang::has("application.Tomorrow") ? trans("application.Tomorrow") : Log::emergency("Missing application translation for: Tomorrow") . "Tomorrow" }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLivescores" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ Lang::has("application.Livescores") ? trans("application.Livescores") : Log::emergency("Missing application translation for: Livescores") . "Livescores" }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownLivescores">
                            <a class="dropdown-item" href="{{route("livescores", ["type" => "now"])}}">{{ Lang::has("application.Livescores") ? trans("application.Livescores") : Log::emergency("Missing application translation for: Livescores") . "Livescores" }} -  {{ Lang::has("application.now") ? trans("application.now") : Log::emergency("Missing application translation for: now") . "now" }} <span class="badge badge-danger">{{$live}}</span></a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{route("livescores", ["type" => "today"])}}">{{ Lang::has("application.Livescores") ? trans("application.Livescores") : Log::emergency("Missing application translation for: Livescores") . "Livescores" }} - {{ Lang::has("application.today") ? trans("application.today") : Log::emergency("Missing application translation for: today") . "today" }}</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLeagues" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">{{ Lang::has("application.Leagues") ? trans("application.Leagues") : Log::emergency("Missing application translation for: Leagues") . "Leagues" }}</a>
                        <div class="dropdown-menu" aria-labelledby="navbarDropdownLeagues">
                            <a class="dropdown-item" href="{{route("leagues")}}">{{ Lang::has("application.All leagues") ? trans("application.All leagues") : Log::emergency("Missing application translation for: All leagues") . "All leagues" }}</a>
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
                Matchy - <span class="badge badge-success">{{ trans("application.version") }} 1.5.0</span> - <a href="https://github.com/sebastiaanspeck/matchy" class="text-muted" target="_blank">Github Repo <i class="fa fa-github" aria-hidden="true"></i></a>
            </p>
            <p class="text-muted pull-right">
                {{ trans("application.Created with") }} <i class="fa fa-code" aria-hidden="true"></i> & <i class="fa fa-coffee" aria-hidden="true"></i>
            </p>
        </div>
    </footer>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
</body>
</html>
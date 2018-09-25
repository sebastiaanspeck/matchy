<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @yield('style')
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-lg navbar-light navbar-laravel">
        <a class="navbar-brand" href="{{ url('/') }}">
            {{ config('app.name') }}
        </a>

{{--        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownFixtures" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Fixtures
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdownFixtures">
                        <a class="dropdown-item" href="{{'/fixtures_between'}}">Fixture between two dates</a>
                        <a class="dropdown-item" href="#">Fixture between two dates for team</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Fixture for specific date</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Fixture by id</a>
                        <a class="dropdown-item" href="#">Multiple fixtures by id</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLivescores" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Livescores
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdownLivescores">
                        <a class="dropdown-item" href="{{'/livescores/now'}}">Livescores - now</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{'/livescores/today'}}">Livescores - today</a>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownLeagues" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Leagues
                    </a>
                    <div class="dropdown-menu" aria-labelledby="navbarDropdownLeagues">
                        <a class="dropdown-item" href="{{'/leagues'}}">All leagues</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">League by id</a>
                    </div>
                </li>
            </ul>
        </div>--}}
    </nav>
    <main class="py-4">
        @yield('content')
    </main>
</div>
</body>
</html>
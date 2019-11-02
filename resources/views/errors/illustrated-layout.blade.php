<!doctype html>
<html lang="en">
<head>
    <title>@yield('title')</title>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

    <style>
        body, html {
            font-family: 'Nunito', sans-serif;
            height: 100%;
        }

        * {
            margin: 0;
            padding: 0;
        }
        .imgbox {
            display: grid;
            height: 100%;
        }
        .center-fit {
            max-width: 100%;
            max-height: 100vh;
            margin: auto;
        }

        .container {
            position: relative;
            text-align: center;
            color: white;
        }

        .error_code {
            font-weight: 900;
            font-size: 5rem;
            color: #22292f;

            position: absolute;
            top: 175px;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .error_message {
            font-weight: 300;
            font-size: 1.5rem;
            color: #606f7b;

            position: absolute;
            bottom: 300px;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .error_button {
            font-size: 1.2rem;

            position: absolute;
            bottom: 225px;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body>
<div class="imgbox container">
    <img src="{{ asset('images/error.png') }}" alt="error" class="center-fit">
    <h1 class="error_code">@yield('code', __('Oh no'))</h1>
    <h1 class="error_message">@yield('message')</h1>

    <a href="{{ url('/') }}">
        <button class="btn btn-info error_button">
            {{ __('Go Home') }}
        </button>
    </a>
</div>
</body>
</html>
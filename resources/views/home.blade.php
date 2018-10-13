@extends('layouts.default')

@section('style')
    .container {
        text-align: center;
    }

    .div-centered {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .title {
        font-size: 84px;
    }

    .links > a {
        color: #636b6f;
        padding: 0 25px;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: .1rem;
        text-decoration: none;
        text-transform: uppercase;
    }
@endsection

@section('content')
    <div class="container">
        <div class="div-centered">
            <div class="title">{{ config('app.name') }}</div>

            <div class="links">
                <a href="{{route('leagues')}}">@lang('application.leagues')</a>
                <a href="{{route('livescores', ['type' => 'today'])}}">@lang('application.today')</a>
                <a href="{{route('livescores', ['type' => 'now'])}}">@lang('application.live')</a>
            </div>
        </div>
    </div>
@endsection
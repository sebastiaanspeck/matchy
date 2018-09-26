@extends('layouts.default')

@section('style')
    .container {
        text-align: center;
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
    .m-b-md {
        margin-bottom: 30px;
    }
    .div-centered {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
@endsection

@section('content')
    <div class="container">
        <div class="div-centered">
            <div class="title m-b-md">
                {{ config('app.name') }}
            </div>

            <div class="links">
                <a href="{{route('leagues')}}"> Leagues </a>
                <a href="{{route('fixturesByDate', ['day' => 'today'])}}"> Today's fixtures </a>
            </div>
        </div>
    </div>
@endsection
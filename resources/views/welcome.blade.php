<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script src="https://js.pusher.com/5.0/pusher.min.js"></script>
    <script>

        //            // Enable pusher logging - don't include this in production
        //            Pusher.logToConsole = true;
        //            var token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOlwvXC9hcGkubW1hbnRvdS5jblwvYXBpXC91c2VyXC9zaWduSW4iLCJpYXQiOjE1NjgxMDQ3MzcsImV4cCI6MTU2ODcwOTUzNywibmJmIjoxNTY4MTA0NzM3LCJqdGkiOiJoOEtHWmxXWTlGdGo4Y3N2Iiwic3ViIjoyNzQ4NywicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.815Yppk4QTEtujhT8oPeV9r8Tc6dQD1Ge5wShxnH6xk';
        //            var pusher = new Pusher('0e2ff9b4f17cd14e87c9', {
        //                cluster: 'ap1',
        //                auth: {
        //                    // params: { token: token },
        //                    headers: { 'Authorization': 'bearer '+token }
        //                },
        //                authEndpoint:'api/test/auth',
        //            });
        //            var channel_27487 = pusher.subscribe('private-App.Models.User.27487');
        //            channel_27487.bind("comment-like", function(data) {
        //                console.log(data);
        //            });
        //            var channel_27486 = pusher.subscribe('testchannel');
        //            channel_27486.bind("testevent", function(data) {
        //                console.log(data);
        //            });
    </script>

    <title>Laravel</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'Raleway', sans-serif;
            font-weight: 100;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<h1>Pusher Test</h1>
<p>
    Try publishing an event to channel <code>my-channel</code>
    with event name <code>my-event</code>.
</p>


<div class="flex-center position-ref full-height">
    @if (Route::has('login'))
        <div class="top-right links">
            @auth
                <a href="{{ url('/home') }}">Home</a>
            @else
                <a href="{{ route('login') }}">Login</a>
                <a href="{{ route('register') }}">Register</a>
            @endauth
        </div>
    @endif

    <div class="content">
        <div class="title m-b-md">
            Laravel
        </div>

        <div class="links">
            <a href="https://laravel.com/docs">Documentation</a>
            <a href="https://laracasts.com">Laracasts</a>
            <a href="https://laravel-news.com">News</a>
            <a href="https://forge.laravel.com">Forge</a>
            <a href="https://github.com/laravel/laravel">GitHub</a>
        </div>
    </div>
</div>
</body>

</html>

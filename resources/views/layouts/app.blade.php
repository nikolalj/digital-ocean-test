<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Icecast Server Setup</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ elixir("css/all.css") }}">
</head>

<body id="app-layout" style="padding: 20px;">

    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">

                <!-- Contents -->
                @yield('content')

            </div>
        </div>
    </div>

    <!-- JavaScripts -->
    <script src="{{ elixir("js/all.js") }}"></script>

    <!-- Scripts -->
    @yield('scripts')

</body>
</html>

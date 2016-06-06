var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    mix.sass([
        'app.scss'
    ], 'resources/assets/css/app.css');

    mix.styles([
        'lib/bootstrap.min.css',
        'app.css',
    ],'public/css/all.css');

    mix.scripts([
        'lib/jquery.min.js',
        'lib/bootstrap.min.js',
    ],'public/js/all.js');

    mix.version(['css/all.css', 'js/all.js']);

});

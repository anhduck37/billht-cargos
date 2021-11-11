const mix = require('laravel-mix');

mix.styles([
    'resources/css/all.min.css',
    'resources/css/ionicons.min.css',
    'resources/css/adminlte.css',
    'resources/css/select2.min.css',
    //'resources/css/daterangepicker-bs3.css',
    'resources/css/toastr.min.css',
    'resources/daterangepicker/daterangepicker.css'
], 'public/css/main.css');
mix.copy('resources/js/jquery.min.js', 'public/js/jquery.js');
mix.copy('resources/js/bootstrap.bundle.min.js', 'public/js/bootstrap.js');
mix.copy('resources/js/select2.min.js', 'public/js/select2.min.js');
mix.copy('resources/js/moment.min.js', 'public/js/moment.js');
mix.copy('resources/js/toastr.min.js', 'public/js/toastr.js');
mix.copy('resources/daterangepicker/daterangepicker.js', 'public/js/daterangepicker.js');
//mix.copy('resources/js/daterangepicker.js', 'public/js/daterangepicker.js');
mix.copy('resources/css/fonts/webfonts/*', 'public/webfonts/');
mix.js('resources/js/adminlte.js', 'public/js/adminlte.js');
mix.version()
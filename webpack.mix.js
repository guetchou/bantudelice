const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | JavaScript is bundled by webpack. The legacy /css/app.css endpoint is a
 | small compatibility bridge that imports the active modern design system.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .copy('resources/css/app.css', 'public/css/app.css');

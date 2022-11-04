const mix = require('laravel-mix');
require('laravel-mix-tailwind')

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.setPublicPath('public')

mix.js('resources/js/app.js', 'public/js').vue({ version: 3 })
    .sass('resources/sass/app.scss', 'public/css')
    .tailwind('tailwind.config.js')
    .version();


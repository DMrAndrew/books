const mix = require('laravel-mix');
const webpackConfig = require('./webpack.config');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your theme assets. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
let filename = process.env.NODE_ENV === 'production' ? 'echo.min.js' : 'echo-dev.min.js';
mix.webpackConfig(webpackConfig)
    .options({ processCssUrls: false })
    .js('assets/js/echo.js','assets/js/'+filename)
;

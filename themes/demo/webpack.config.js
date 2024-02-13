const webpack = require('webpack');
const dotenv = require('dotenv')

const env = dotenv.config({path: '../../.env'}).parsed;

const envKeys = Object.keys(env)
    .filter(e => e.startsWith('PUSHER'))
    .reduce((prev, next) => {
        prev[`process.env.${next}`] = JSON.stringify(env[next]);
        return prev;
    }, {});

module.exports = {
    devtool: 'inline-source-map',
    plugins: [
        new webpack.DefinePlugin(envKeys)

    ],
    externals: {
        // Use external version of jQuery
        jquery: 'jQuery'
    },
};

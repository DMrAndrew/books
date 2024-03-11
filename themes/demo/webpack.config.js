const webpack = require('webpack');
const dotenv = require('dotenv')

const isProduction = process.env.NODE_ENV === 'production'
const path = './vue-chat/.env' + (isProduction ? '.production' : '')
const env = dotenv.config({path: path}).parsed;
const envKeys = Object.keys(env)
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

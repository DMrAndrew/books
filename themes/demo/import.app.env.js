const webpack = require('webpack');
const dotenv = require('dotenv')

function extract(path = './../../.env') {
    const env = dotenv.config({path: path}).parsed;

    const keys_allowed = [
        'PUSHER_APP_KEY',
        'APP_SCHEMA',
        'APP_HOST',
        'APP_PORT',
        'WEBSOCKETS_LISTEN_PORT',
        'PUSHER_CLUSTER',
        'PUSHER_APP_ID'
    ]
    const envKeys = Object.keys(env ? env : {})
        .filter(e => keys_allowed.find(k => k == e))
        .reduce((prev, next) => {
            prev[`process.env.${next}`] = JSON.stringify(env[next]);
            return prev;
        }, {});
    return {
        plugin:new webpack.DefinePlugin(envKeys),
        values:envKeys
    }
}

module.exports = {
    extract
}
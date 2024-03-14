const {defineConfig} = require('@vue/cli-service')
const {extract} = require("../import.app.env");
const isProduction = () => process.env.NODE_ENV === 'production';
const AppEnv = extract('./../../../.env')
const common = {
    transpileDependencies: true,
    configureWebpack: {
        plugins: [
            AppEnv.plugin
        ]

    },
}

const production = {
    publicPath: './themes/demo/partials/chat',
    outputDir: '../partials/chat',
    indexPath: 'meta.htm',
    assetsDir: './assets/'
};

const [host, port] = Object.values(AppEnv.values).map(val => JSON.parse(val));
const proxy = ["http://", host, ":" + port].join("")
const dev = {
    devServer: {
        host: host,
        port: 8099,
        proxy: proxy
    },
};
module.exports = defineConfig(Object.assign(
    common,
    isProduction() ? production : dev
))

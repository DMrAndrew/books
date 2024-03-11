const {defineConfig} = require('@vue/cli-service')
const isProduction = () => process.env.NODE_ENV === 'production';

const common = {
    transpileDependencies: true
}

const production = {
    publicPath: './themes/demo/partials/chat',
    outputDir: '../partials/chat',
    indexPath: 'meta.htm',
    assetsDir: './assets/'
};


const dev = {
    devServer: {
        host: '127.0.0.1',
        port: 80,
        proxy:'http://127.0.0.1:8000'
    }
};
module.exports = defineConfig(Object.assign(
    common,
    isProduction() ? production : dev
))

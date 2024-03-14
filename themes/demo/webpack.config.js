const {extract} = require('./import.app.env')

module.exports = {
    devtool: 'inline-source-map',
    plugins: [
        extract().plugin
    ],
};

// webpack.config.js
const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require("mini-css-extract-plugin");


module.exports = {
    plugins: [new MiniCssExtractPlugin(
        {filename : '[name].css'}
    )],
    context: __dirname,
    // entry: './resources/js/index.js',
    entry: {
        index: './resources/js/index.js'
        // about: './src/about.js'
    },
    output: {
        path: path.resolve( __dirname, 'public/builds' ),
        filename: '[name].js',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                // use: ["css-loader"],
            },
            {
                test: /\.css$/i,
                exclude: /node_modules/,
                use: [MiniCssExtractPlugin.loader,"css-loader"],
            },
        ]
    }
};

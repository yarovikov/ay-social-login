const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FixStyleOnlyEntriesPlugin = require('webpack-fix-style-only-entries');
const path = require('path');

module.exports = {
    watch: true,
    mode: 'development',
    entry: {
        main: path.resolve(__dirname, 'assets/main.js'),
        admin: path.resolve(__dirname, 'assets/styles/admin.scss'),
    },
    output: {
        path: path.resolve(__dirname, 'assets'),
        filename: '[name].min.js',
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: 'babel-loader',
                    },
                ],
            },
            {
                test: /\.svg/,
                loader: 'url-loader?limit=100000',
            },
            {
                test: /\.(woff(2)?|ttf|eot)(\?v=\d+\.\d+\.\d+)?$/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: 'fonts/[name].[ext]',
                            loader:
                                'url-loader?limit=10000&mimetype=application/font-woff',
                        },
                    },
                ],
            },
            {
                test: /\.scss/,
                exclude: /node_modules/,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader',
                    {
                        loader: 'postcss-loader',
                        options: {config: {path: 'postcss.config.js'}},
                    },
                ],
            },
        ],
    },
    plugins: [
        new FixStyleOnlyEntriesPlugin({silent: true}),
        new MiniCssExtractPlugin({filename: '[name].min.css'}),
    ],
};

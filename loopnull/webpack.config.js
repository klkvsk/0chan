module.exports = {
    entry: './src/main.js',
    output: {
        path: './www',
        filename: 'app.js',
    },
    module: {
        loaders: [{
            test: /\.js$/,
            exclude: /node_modules/,
            loader: 'babel-loader',
        }]
    }
}

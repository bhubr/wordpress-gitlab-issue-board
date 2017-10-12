const webpack = require('webpack');
const UglifyJSPlugin = require('uglifyjs-webpack-plugin');

// Some stuff that helped me get through Webpack config:
// https://github.com/davidgovea/webpack-intro/issues/2
// https://github.com/webpack-contrib/imports-loader
// https://stackoverflow.com/questions/38164102/change-hard-coded-url-constants-for-different-environments-via-webpack

module.exports = {
  context: __dirname + '/angular-src',
  entry: {
    app: './wp-gitlab-issue-board.js',
    vendor: [
      'angular',
      '@uirouter/angularjs',
      'ng-lodash',
      'angular-dragdrop'
    ]
  },
  output: {
    path: __dirname + '/js',
    filename: 'wpglib-app.bundle.js'
  },
  plugins: [
    new webpack.optimize.CommonsChunkPlugin({ name: "vendor", filename: "wpglib-vendor.bundle.js" }),
    // new UglifyJSPlugin()
  ],

  cache: false
};
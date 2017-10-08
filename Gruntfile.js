var fs = require('fs');
var webpackConfig = require('./webpack.config');

module.exports = function(grunt) {

  grunt.initConfig({

    cssmin: {
      target: {
        files: [
          {
            expand: true,
            cwd: __dirname,
            src: ['css/wpglib-styles.css'],
            dest: '',
            ext: '.min.css'
          }
        ]
      }
    },
    compress: {
      main: {
        options: {
          archive: 'gitlab-issue-board-{version}.zip'
        },
        files: [
          {
            src: [
              'js/*',
              'css/*.min.css',
              'images/*',
              'templates/*',
              'README.md',
              '*.php'
            ],
            dest: 'gitlab-issue-board'
          }, // includes files in path
        ]
      }
    },
    webpack: {
      options: {
        stats: !process.env.NODE_ENV || process.env.NODE_ENV === 'development'
      },
      prod: webpackConfig,
      dev: webpackConfig,
      // dev: Object.assign({ watch: true }, webpackConfig)
    },
    watch: {
      files: ['angular-src/*', 'angular-src/**/*'],
      tasks: ['webpack']
    }
  });

  grunt.loadNpmTasks('grunt-contrib-cssmin');
  grunt.loadNpmTasks('grunt-contrib-compress');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-webpack');

  grunt.registerTask('get-theme-version', function () {
    var themeStyle = fs.readFileSync(__dirname + '/gitlab-issue-board.php').toString();
    var versionRegex = /Version\: ([0-9\.]+)/g;
    var matches = versionRegex.exec(themeStyle);
    var version = matches[1];
    var archiveName = grunt.config('compress.main.options.archive');
    grunt.config('compress.main.options.archive', archiveName.replace('{version}', version));
    grunt.log.ok();
  });

  grunt.registerTask('default', ['webpack', 'cssmin', 'get-theme-version', 'compress']);

};
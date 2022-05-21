/**
 * Laravel mix - HuH WebTrees MultiTreeView
 *
 * Output:
 * 		- dist
 *      - huhwt-mult-tv
 *          - app
 *              - Http
 *                  - RequestHandlers
 *              - Module
 *                  - InteractiveTree
 *              - Services
 *          - resources
 *              - css (minified)
 *              - js (minified)
 *              - views
 *              - lang
 *                  - de
 *        autoload.php
 *        module.php
 *        LICENSE.md
 *        README.md
 *        README.de.md
 *
 */

let mix = require('laravel-mix');
let config = require('./webpack.mix.config');
require('laravel-mix-clean');

const version  = '2.1.1.0';
const dist_dir = 'dist/huhwt-mtv';
const dist_root = 'dist';

//https://github.com/gregnb/filemanager-webpack-plugin
const FileManagerPlugin = require('filemanager-webpack-plugin');

mix
    .setPublicPath('./dist')
    .copy(config.build_dir + '/css/huhwt.min.css', dist_dir + '/resources/css/huhwt.min.css')
    .copyDirectory(config.public_dir + '/views', dist_dir + '/resources/views')
    .copyDirectory(config.app_dir, dist_dir)
    .copy(config.dev_dir + '/js/huhwt-treeview.js', dist_dir + '/resources/js/huhwt.min.js')
    .copy(config.dev_dir + '/lang/de/messages.po', dist_dir + '/resources/lang/de/messages.po')
    .copy(config.dev_dir + '/lang/nl/messages.po', dist_dir + '/resources/lang/nl/messages.po')
    .copy('autoload.php', dist_dir)
    .copy('module.php', dist_dir)
    .copy('MultTreeView.php', dist_dir)
    .copy('latest-version.txt', dist_dir)
    .copy('LICENSE.md', dist_dir)
    .copy('README.md', dist_dir)
    .copy('README.de.md', dist_dir)
    .webpackConfig({
        plugins: [
          new FileManagerPlugin({
            onEnd: {
                archive: [
                    { source: './dist', destination: './dist/huhwt-mtv-' + version + '.zip'}
                  ]
            }
          })
        ]
    })
    .clean();

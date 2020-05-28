/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2014-2018 (original work) Open Assessment Technologies SA;
 */

/**
 * configure the extension bundles
 * @param {Object} grunt
 * @author Bertrand Chevrier <bertrand@taotesting.com>
 */
module.exports = function(grunt) {
    'use strict';

    const sassConfig = grunt.config('sass') || {};
    const runnerScssPath = path.join(root, 'node_modules/@oat-sa/tao-test-runner-qti/scss');

    grunt.config.merge({
        sass : {
            taomediamanager: {
                files : [
                    {
                        dest : path.join(root, 'css/media.css'),
                        src : path.join(root, 'scss/media.scss')
                    }, {
                        dest : path.join(root, 'css/passage-creator.css'),
                        src : path.join(root, 'scss/passage-creator.scss')
                    }
                ]
            },
        },
        bundle : {
            taomediamanager : {
                options : {
                    extension : 'taoMediaManager',
                    dependencies : ['taoItems', 'taoQtiItem'],
                    outputDir : 'loader',
                    paths: require('./paths.json'),
                    bundles : [{
                        name : 'taoMediaManager',
                        default : true,
                        bootstrap : true,
                        babel: true
                    }]
                }
            }
        }
    });

    // bundle task
    grunt.registerTask('taomediamanagerbundle', ['bundle:taomediamanager']);
};

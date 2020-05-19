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
 * Copyright (c) 2020 Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'lodash',
    'ui/component',
    'core/pluginLoader',
    'taoMediaManager/qtiCreator/passageCreator',
    'taoMediaManager/qtiCreator/editor/areaBroker',
    'tpl!taoMediaManager/qtiCreator/component/tpl/passageAuthoring',
    'css!taoMediaManagerCss/passage-creator.css'
], function (
    _,
    componentFactory,
    pluginLoaderFactory,
    passageCreatorFactory,
    areaBrokerFactory,
    componentTpl
) {
    'use strict';

    const defaultPlugins = [{
        module: 'taoMediaManager/qtiCreator/plugins/content/title',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'content'
    }, {
        module: 'taoMediaManager/qtiCreator/plugins/navigation/back',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'panel'
    }, {
        module: 'taoMediaManager/qtiCreator/plugins/menu/save',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'panel'
    }, {
        module: 'taoMediaManager/qtiCreator/plugins/menu/preview',
        bundle: 'taoMediaManager/loader/taoMediaManager.min',
        category: 'panel'
    }];
    /**
     * Embeds the assets creator UI in a component
     *
     * @example
     *  const container = $();
     *  const config = {
     *      plugins: [],
     *      properties: {
     *          uri: 'http://item#rdf-123',
     *          baseUrl: 'http://foo/bar/',
     *          // ...
     *      }
     *  };
     *  const component = passageAuthoringFactory(container, config)
     *      .on('ready', function onComponentReady() {
     *          // ...
     *      });
     *
     * @param {HTMLElement|String} container
     * @param {Object} config - The setup for the item creator
     * @param {Object} config.properties - The list of properties expected by the item creator
     * @param {Object} config.properties.uri - The URI of the item to author
     * @param {Object} config.properties.baseUrl - The base URL to retrieve the assets
     * @param {String} config.properties.passageDataUrl - URL for getting item data (passed through to itemCreator)
     * @param {Object[]} [config.plugins] - Additional plugins to load
     * @returns {component}
     * @fires ready - When the component is ready to work
     */
    function passageAuthoringFactory(container, config = {}) {
        let areaBroker;
        let passageCreator;

        const plugins = Array.isArray(config.plugins) ? config.plugins : [];
        config.plugins = [...defaultPlugins, ...plugins];

        const pluginLoader = pluginLoaderFactory();

        const api = {
            /**
             * Gets access to the area broker
             * @returns {areaBroker}
             */
            getAreaBroker() {
                return areaBroker;
            }
        };

        const passageAuthoring = componentFactory(api)
            // set the component's layout
            .setTemplate(componentTpl)

            // auto render on init
            .on('init', function () {
                // load plugins dynamically
                _.forEach(this.getConfig().plugins, plugin => {
                    if (plugin && plugin.module) {
                        if (plugin.exclude) {
                            pluginLoader.remove(plugin.module);
                        } else {
                            pluginLoader.add(plugin);
                        }
                    }
                });

                // load the plugins, then render the item creator
                pluginLoader.load()
                    .then(() => this.render(container))
                    .catch(err => this.trigger('error', err));
            })

            // renders the component
            .on('render', function () {
                const $container = this.getElement();
                areaBroker = areaBrokerFactory($container, {
                    'menu': $container.find('.menu'),
                    'menuLeft': $container.find('.menu-left'),
                    'menuRight': $container.find('.menu-right'),
                    'contentCreatorPanel': $container.find('#item-editor-panel'),
                    'editorBar': $container.find('#item-editor-panel .item-editor-bar'),
                    'title': $container.find('#item-editor-panel .item-editor-bar h1'),
                    'toolbar': $container.find('#item-editor-panel .item-editor-bar #toolbar-top'),
                    'interactionPanel': $container.find('#item-editor-interaction-bar'),
                    'propertyPanel': $container.find('#item-editor-item-widget-bar'),
                    'itemPanel': $container.find('#item-editor-scroll-inner'),
                    'itemPropertyPanel': $container.find('#sidebar-right-item-properties'),
                    'itemStylePanel': $container.find('#item-style-editor-bar'),
                    'modalContainer': $container.find('#modal-container'),
                    'elementPropertyPanel': $container.find('#item-editor-body-element-property-bar .panel')
                });

                passageCreator = passageCreatorFactory(this.getConfig(), areaBroker, pluginLoader.getPlugins())
                    .spread(this, 'error success ready')
                    .on('init', function () {
                        this.render();
                    })
                    .init();
            })

            .on('destroy', function () {
                if (passageCreator) {
                    passageCreator.destroy();
                }
                passageCreator = null;
                areaBroker = null;
            });

        // initialize the component with the provided config
        // defer the call to allow to listen to the init event
        _.defer(() => passageAuthoring.init(config));

        return passageAuthoring;
    }

    return passageAuthoringFactory;
});
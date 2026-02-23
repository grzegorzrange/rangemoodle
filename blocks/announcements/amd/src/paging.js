// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AJAX pagination for the announcements block.
 *
 * @module     block_announcements/paging
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/templates'], function(Ajax, Templates) {

    /**
     * Load a page via AJAX and re-render the block content.
     *
     * @param {HTMLElement} container
     * @param {number} directionId
     * @param {number} page
     */
    var loadPage = function(container, directionId, page) {
        container.classList.add('block-announcements-loading');

        var request = Ajax.call([{
            methodname: 'block_announcements_get_page',
            args: {directionid: directionId, page: page},
        }]);

        request[0].then(function(response) {
            var tiles = response.tiles.map(function(tile) {
                return {
                    name: tile.name,
                    text: tile.text,
                    date: tile.date,
                    attachcount: tile.attachcount,
                    hasattachments: tile.hasattachments,
                    viewurl: tile.viewurl,
                };
            });

            var pages = [];
            for (var i = 0; i < response.totalpages; i++) {
                pages.push({
                    pagenum: i + 1,
                    page: i,
                    active: (i === response.currentpage),
                });
            }

            var templateData = {
                hastiles: tiles.length > 0,
                tiles: tiles,
                haspagination: response.totalpages > 1,
                pages: pages,
            };

            return Templates.renderForPromise('block_announcements/content', templateData);
        }).then(function(result) {
            container.innerHTML = result.html;
            Templates.runTemplateJS(result.js);
            container.classList.remove('block-announcements-loading');
            return;
        }).catch(function() {
            container.classList.remove('block-announcements-loading');
        });
    };

    return {
        /**
         * Initialise AJAX paging.
         *
         * @param {number} directionId Active direction ID.
         * @param {number} currentPage Initial page number.
         */
        init: function(directionId, currentPage) {
            var container = document.querySelector('[data-region="block-announcements"]');
            if (!container) {
                return;
            }

            container.addEventListener('click', function(e) {
                var pageLink = e.target.closest('[data-page]');
                if (!pageLink) {
                    return;
                }
                e.preventDefault();
                var page = parseInt(pageLink.dataset.page, 10);
                loadPage(container, directionId, page);
            });
        }
    };
});

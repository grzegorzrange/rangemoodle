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
 * Sidebar module for local_dashboard.
 *
 * On desktop: adds class to push main content to the right.
 * On mobile: injects menu items into the existing Boost primary drawer.
 *
 * @module     local_dashboard/sidebar
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var SELECTORS = {
        SIDEBAR: '#local-dashboard-sidebar',
        BOOST_DRAWER_CONTENT: '#theme_boost-drawers-primary .drawercontent',
        MOBILE_SECTION_ID: 'local-dashboard-mobile-section'
    };

    /**
     * Inject sidebar items into the Boost mobile drawer.
     */
    var injectIntoBoostDrawer = function() {
        var sidebar = document.querySelector(SELECTORS.SIDEBAR);
        var drawerContent = document.querySelector(SELECTORS.BOOST_DRAWER_CONTENT);

        if (!sidebar || !drawerContent) {
            return;
        }

        // Don't inject twice.
        if (document.getElementById(SELECTORS.MOBILE_SECTION_ID)) {
            return;
        }

        // Create a section with our menu items.
        var section = document.createElement('div');
        section.id = SELECTORS.MOBILE_SECTION_ID;
        section.className = 'mt-2 border-top pt-2';

        // Copy sidebar content (admin items + user items) into the drawer section.
        var items = sidebar.querySelectorAll(':scope > .sidebar-section, :scope > ul.list-unstyled');
        items.forEach(function(item) {
            var clone = item.cloneNode(true);
            // Make hidden elements (like admin quick items) visible in mobile drawer.
            clone.classList.remove('d-none');
            clone.removeAttribute('aria-hidden');
            if (clone.id) {
                clone.id = clone.id + '-mobile';
            }
            var links = clone.querySelectorAll('.sidebar-link');
            links.forEach(function(link) {
                var isActive = link.classList.contains('active');
                link.className = 'list-group-item list-group-item-action d-flex align-items-center';
                if (isActive) {
                    link.classList.add('active');
                }
            });
            section.appendChild(clone);
        });

        drawerContent.appendChild(section);
    };

    return {
        /**
         * Initialize the sidebar.
         */
        init: function() {
            // Mobile: inject into Boost drawer.
            injectIntoBoostDrawer();
        }
    };
});

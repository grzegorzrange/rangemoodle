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
 * Archive format courseindex enhancements:
 * - Section titles are non-clickable (no navigation to section.php).
 * - Activity clicks scroll to matching element in main content.
 * - On mobile the drawer is hidden and replaced by a dropdown button
 *   showing the full course structure.
 *
 * @module     format_archive/courseindexscroll
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    var SELECTORS = {
        DRAWER: '#theme_boost-drawers-courseindex',
        COURSEINDEX: '#course-index',
        SECTION_TITLE_LINK: 'a.courseindex-link[data-action="togglecourseindexsection"]',
        CM_LINK: '[data-for="cm"] a.courseindex-link',
        SECTION_LIST: '.archive.section-list',
        CM_ITEM: '[data-for="cmitem"]',
        ACTIVITY_NAME: '.activityname a',
        CM_CONTAINER: '[data-for="cm"]',
        MOBILE_BTN_ID: 'archive-courseindex-dropdown-btn',
        MOBILE_PANEL_ID: 'archive-courseindex-dropdown-panel'
    };

    /**
     * Replace section title <a> with <span> to make them non-clickable.
     *
     * @param {HTMLElement} container
     */
    var disableSectionLinks = function(container) {
        var links = container.querySelectorAll(SELECTORS.SECTION_TITLE_LINK);
        links.forEach(function(link) {
            var span = document.createElement('span');
            for (var i = 0; i < link.attributes.length; i++) {
                var attr = link.attributes[i];
                if (attr.name !== 'href') {
                    span.setAttribute(attr.name, attr.value);
                }
            }
            span.innerHTML = link.innerHTML;
            span.style.cursor = 'default';
            link.parentNode.replaceChild(span, link);
        });
    };

    /**
     * Scroll to an activity in the main content area.
     *
     * @param {string} cmId Course module ID.
     */
    var scrollToActivity = function(cmId) {
        var sectionList = document.querySelector(SELECTORS.SECTION_LIST);
        if (!sectionList) {
            return;
        }
        var targetItem = sectionList.querySelector(SELECTORS.CM_ITEM + '[data-id="' + cmId + '"]');
        if (!targetItem) {
            return;
        }
        var targetLink = targetItem.querySelector(SELECTORS.ACTIVITY_NAME);
        var scrollTarget = targetLink || targetItem;

        scrollTarget.scrollIntoView({behavior: 'smooth', block: 'center'});

        targetItem.style.transition = 'background-color 0.3s ease';
        targetItem.style.backgroundColor = '#fff3cd';
        setTimeout(function() {
            targetItem.style.backgroundColor = '';
            setTimeout(function() {
                targetItem.style.transition = '';
            }, 300);
        }, 1500);
    };

    /**
     * Attach click handler on a container to intercept activity link clicks.
     *
     * @param {HTMLElement} container
     */
    var attachScrollHandlers = function(container) {
        container.addEventListener('click', function(e) {
            var link = e.target.closest(SELECTORS.CM_LINK);
            if (!link) {
                return;
            }
            var cmItem = link.closest(SELECTORS.CM_CONTAINER);
            if (!cmItem) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            scrollToActivity(cmItem.dataset.id);
        });
    };

    /**
     * Clone the courseindex content into the mobile dropdown panel.
     * Clones the full HTML structure so the user sees the complete course tree.
     *
     * @param {HTMLElement} courseindex The #course-index element.
     * @returns {HTMLElement} The cloned content wrapper.
     */
    var cloneCourseindexContent = function(courseindex) {
        var wrapper = document.createElement('div');
        wrapper.className = 'archive-ci-panel-content';

        // Clone all children of courseindex.
        var children = courseindex.children;
        for (var i = 0; i < children.length; i++) {
            var clone = children[i].cloneNode(true);
            // Rename IDs to avoid duplicates but keep collapse working.
            var allWithId = clone.querySelectorAll('[id]');
            allWithId.forEach(function(el) {
                el.id = 'mobile-' + el.id;
            });
            if (clone.id) {
                clone.id = 'mobile-' + clone.id;
            }
            // Update collapse href/aria-controls to match new IDs.
            var collapseLinks = clone.querySelectorAll('[data-bs-toggle="collapse"], [href^="#courseindexcollapse"]');
            collapseLinks.forEach(function(link) {
                var href = link.getAttribute('href');
                if (href && href.charAt(0) === '#') {
                    link.setAttribute('href', '#mobile-' + href.substring(1));
                }
                var controls = link.getAttribute('aria-controls');
                if (controls) {
                    link.setAttribute('aria-controls', 'mobile-' + controls);
                }
            });
            var collapseTargets = clone.querySelectorAll('[aria-labelledby]');
            collapseTargets.forEach(function(el) {
                var labelledby = el.getAttribute('aria-labelledby');
                if (labelledby) {
                    el.setAttribute('aria-labelledby', 'mobile-' + labelledby);
                }
            });

            // Remove lock icons and "Wyróżnione" badges.
            var removeEls = clone.querySelectorAll('.courseindex-locked, .current-badge');
            removeEls.forEach(function(el) {
                el.remove();
            });

            // Collapse all sections by default.
            var openSections = clone.querySelectorAll('.courseindex-item-content.collapse.show');
            openSections.forEach(function(section) {
                section.classList.remove('show');
            });
            var expandedChevrons = clone.querySelectorAll('.courseindex-chevron:not(.collapsed)');
            expandedChevrons.forEach(function(chevron) {
                chevron.classList.add('collapsed');
                var link = chevron.closest('a');
                if (link) {
                    link.setAttribute('aria-expanded', 'false');
                }
            });

            wrapper.appendChild(clone);
        }

        // Disable section links in the cloned content.
        disableSectionLinks(wrapper);

        return wrapper;
    };

    /**
     * Create the mobile dropdown button and panel with full courseindex.
     *
     * @param {HTMLElement} drawer
     */
    var setupMobileDropdown = function(drawer) {
        var courseindex = drawer.querySelector(SELECTORS.COURSEINDEX);
        if (!courseindex) {
            return;
        }

        // Create the toggle button.
        var btn = document.createElement('button');
        btn.id = SELECTORS.MOBILE_BTN_ID;
        btn.className = 'archive-ci-dropdown-btn btn btn-outline-secondary';
        btn.type = 'button';
        btn.innerHTML = '<i class="fa fa-list-ul me-2"></i>' +
            '<span class="archive-ci-dropdown-btn-text">Nawigacja</span>' +
            '<i class="fa fa-chevron-down ms-2 archive-ci-chevron"></i>';

        // Create backdrop.
        var backdrop = document.createElement('div');
        backdrop.className = 'archive-ci-backdrop';

        // Create panel with full courseindex clone.
        var panel = document.createElement('div');
        panel.id = SELECTORS.MOBILE_PANEL_ID;
        panel.className = 'archive-ci-dropdown-panel';
        var content = cloneCourseindexContent(courseindex);
        panel.appendChild(content);

        // Insert into page before the main content.
        var mainContent = document.querySelector('#page-content') || document.querySelector('#region-main');
        if (!mainContent) {
            return;
        }
        var dropdownWrapper = document.createElement('div');
        dropdownWrapper.className = 'archive-ci-dropdown-wrapper d-lg-none';
        dropdownWrapper.appendChild(btn);
        dropdownWrapper.appendChild(panel);
        dropdownWrapper.appendChild(backdrop);
        mainContent.parentNode.insertBefore(dropdownWrapper, mainContent);

        var isOpen = false;

        var togglePanel = function() {
            isOpen = !isOpen;
            panel.classList.toggle('open', isOpen);
            backdrop.classList.toggle('open', isOpen);
            btn.classList.toggle('open', isOpen);
        };

        var closePanel = function() {
            if (!isOpen) {
                return;
            }
            isOpen = false;
            panel.classList.remove('open');
            backdrop.classList.remove('open');
            btn.classList.remove('open');
        };

        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            togglePanel();
        });

        backdrop.addEventListener('click', closePanel);

        // Intercept activity clicks in the panel — scroll to activity and close.
        attachScrollHandlers(panel);
        panel.addEventListener('click', function(e) {
            var cmLink = e.target.closest(SELECTORS.CM_LINK);
            if (cmLink) {
                closePanel();
            }
        });

        // Watch for courseindex mutations (reactive updates) and rebuild panel content.
        var observer = new MutationObserver(function() {
            var wasOpen = panel.classList.contains('open');
            // Replace panel content.
            panel.innerHTML = '';
            var newContent = cloneCourseindexContent(courseindex);
            panel.appendChild(newContent);
            if (wasOpen) {
                panel.classList.add('open');
            }
        });
        observer.observe(courseindex, {childList: true, subtree: true, characterData: true});
    };

    return {
        /**
         * Initialise.
         */
        init: function() {
            if (!document.body.classList.contains('format-archive')) {
                return;
            }

            var drawer = document.querySelector(SELECTORS.DRAWER);
            if (!drawer) {
                return;
            }

            // Add "Menu kursu" header to drawerheader and remove close button.
            var drawerHeader = drawer.querySelector('.drawerheader');
            if (drawerHeader && !drawerHeader.querySelector('.archive-menu')) {
                // Remove close button.
                var closeBtn = drawerHeader.querySelector('button[data-action="closedrawer"]');
                if (closeBtn) {
                    closeBtn.remove();
                }
                // Insert "Menu kursu" at the beginning of drawerheader.
                var menuHeader = document.createElement('div');
                menuHeader.className = 'archive-menu';
                menuHeader.textContent = 'Menu kursu';
                drawerHeader.insertBefore(menuHeader, drawerHeader.firstChild);
            }

            // Wait for courseindex to be rendered (it may load asynchronously).
            var waitForCourseindex = function() {
                var courseindex = drawer.querySelector(SELECTORS.COURSEINDEX);
                if (courseindex && courseindex.children.length > 0) {
                    // 1. Make section titles non-clickable everywhere.
                    disableSectionLinks(drawer);

                    // 2. Attach scroll handlers for activity links in the drawer.
                    attachScrollHandlers(drawer);

                    // 3. Set up mobile dropdown.
                    setupMobileDropdown(drawer);

                    // Re-disable section links after reactive updates.
                    var sectionObserver = new MutationObserver(function() {
                        disableSectionLinks(drawer);
                    });
                    sectionObserver.observe(courseindex, {childList: true, subtree: true});
                } else {
                    setTimeout(waitForCourseindex, 200);
                }
            };
            waitForCourseindex();
        }
    };
});

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
 * Simple collapse/expand all fix for archive format.
 * Directly toggles Bootstrap collapse on section content elements.
 *
 * @module     format_archive/collapsefix
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    return {
        init: function() {
            if (!document.body.classList.contains('format-archive')) {
                return;
            }

            // Wait for the button to exist.
            var setup = function() {
                var btn = document.getElementById('collapsesections');
                if (!btn) {
                    setTimeout(setup, 300);
                    return;
                }

                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();

                    // Check current state: if .collapseall is visible => we should collapse.
                    var collapseSpan = btn.querySelector('.collapseall');
                    var expandSpan = btn.querySelector('.expandall');
                    var shouldCollapse = collapseSpan && window.getComputedStyle(collapseSpan).display !== 'none';

                    // Find all section content collapse elements.
                    var sections = document.querySelectorAll('[id^="coursecontentcollapseid"]');

                    sections.forEach(function(section) {
                        if (shouldCollapse) {
                            section.classList.remove('show');
                        } else {
                            section.classList.add('show');
                        }
                    });

                    // Update all section togglers (the chevrons).
                    var togglers = document.querySelectorAll('[id^="collapsesectionid"]');
                    togglers.forEach(function(toggler) {
                        if (shouldCollapse) {
                            toggler.classList.add('collapsed');
                            toggler.setAttribute('aria-expanded', 'false');
                        } else {
                            toggler.classList.remove('collapsed');
                            toggler.setAttribute('aria-expanded', 'true');
                        }
                    });

                    // Toggle button text visibility.
                    if (shouldCollapse) {
                        btn.classList.add('collapsed');
                        btn.setAttribute('aria-expanded', 'false');
                    } else {
                        btn.classList.remove('collapsed');
                        btn.setAttribute('aria-expanded', 'true');
                    }
                });
            };

            setup();
        }
    };
});

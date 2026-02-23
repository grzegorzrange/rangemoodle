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
 * Inject internal test status badges next to activity names.
 *
 * @module     local_support/internaltest_badges
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    var STATUS_LABELS = {
        notdone: 'Niezrobiony',
        passed: 'Zaliczony',
        failed: 'Niezaliczony'
    };

    var STATUS_CLASSES = {
        notdone: 'badge bg-secondary',
        passed: 'badge bg-success',
        failed: 'badge bg-danger'
    };

    var AVAILABILITY_LABELS = {
        active: 'Aktywny',
        inactive: 'Nieaktywny'
    };

    var AVAILABILITY_CLASSES = {
        active: 'badge bg-primary',
        inactive: 'badge bg-warning text-dark'
    };

    return {
        /**
         * Initialise badges.
         *
         * @param {Array} statuses Array of {cmid, status, availability} objects.
         */
        init: function(statuses) {
            if (!statuses || !statuses.length) {
                return;
            }

            var inject = function() {
                statuses.forEach(function(item) {
                    var cmEl = document.querySelector('li.activity[data-id="' + item.cmid + '"]');
                    if (!cmEl) {
                        return;
                    }
                    // Avoid duplicates.
                    if (cmEl.querySelector('.internaltest-badge')) {
                        return;
                    }
                    var nameArea = cmEl.querySelector('.activity-name-area');
                    if (!nameArea) {
                        return;
                    }

                    // Availability badge (Aktywny / Nieaktywny).
                    if (item.availability) {
                        var availBadge = document.createElement('span');
                        availBadge.className = AVAILABILITY_CLASSES[item.availability]
                            + ' internaltest-badge internaltest-availability-badge mt-1 mr-1';
                        availBadge.style.fontSize = '0.75em';
                        availBadge.style.alignSelf = 'flex-start';
                        availBadge.textContent = AVAILABILITY_LABELS[item.availability];
                        nameArea.appendChild(availBadge);
                    }

                    // Completion status badge (Niezrobiony / Zaliczony / Niezaliczony).
                    var badge = document.createElement('span');
                    badge.className = STATUS_CLASSES[item.status] + ' internaltest-badge mt-1';
                    badge.style.fontSize = '0.75em';
                    badge.style.alignSelf = 'flex-start';
                    badge.textContent = STATUS_LABELS[item.status];
                    nameArea.appendChild(badge);
                });
            };

            // Run immediately and also after a short delay for reactive rendering.
            if (document.readyState === 'complete' || document.readyState === 'interactive') {
                inject();
                setTimeout(inject, 500);
            } else {
                document.addEventListener('DOMContentLoaded', function() {
                    inject();
                    setTimeout(inject, 500);
                });
            }
        }
    };
});

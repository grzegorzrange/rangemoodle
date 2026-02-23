<?php
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
 * Hook callbacks for local_support.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_support;

/**
 * Handles custom file override logic.
 */
class hook_callbacks {

    /**
     * Check if the current script has a custom override in /custom/ directory.
     * If so, include the override file and terminate.
     *
     * The after_config hook fires at the very end of lib/setup.php (line ~1210),
     * AFTER $PAGE, $USER, $SESSION, $OUTPUT are fully initialized.
     * We must declare them as global so the included file inherits them
     * (include runs in the method's scope, not global scope).
     *
     * @param \core\hook\after_config $hook
     */
    public static function check_file_override(\core\hook\after_config $hook): void {
        global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION, $COURSE, $SITE, $FULLME, $ME, $SCRIPT;

        if (empty($_SERVER['SCRIPT_FILENAME'])) {
            return;
        }

        $scriptpath = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME']));
        $dirroot = str_replace('\\', '/', $CFG->dirroot);

        // Only handle scripts within the Moodle directory.
        if (strpos($scriptpath, $dirroot . '/') !== 0) {
            return;
        }

        $relativepath = substr($scriptpath, strlen($dirroot));

        // Never override files inside /custom/ itself or /local/support/.
        if (strpos($relativepath, '/custom/') === 0 || strpos($relativepath, '/local/support/') === 0) {
            return;
        }

        $customfile = $dirroot . '/custom' . $relativepath;

        if (file_exists($customfile)) {
            include($customfile);
            die();
        }
    }

    /**
     * Inject head HTML — replaces legacy local_support_before_standard_html_head().
     *
     * Adds Google Fonts, redirects blocked URLs, injects internal test badges.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function inject_head_html(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $CFG, $PAGE;

        // Redirect non-admin users from blocked URLs to /my/.
        if (isloggedin() && !isguestuser() && !is_siteadmin()) {
            $blockedurls = [
                '/grade/report/overview/index.php',
                '/calendar/view.php',
                '/user/profile.php',
                'user/files.php',
                '/reportbuilder/index.php',
                '/user/preferences.php',
            ];
            $currentpath = $PAGE->url->get_path();
            foreach ($blockedurls as $blocked) {
                if (strpos($currentpath, $blocked) !== false) {
                    redirect(new \moodle_url('/my/'));
                }
            }
        }

        // On course view pages, inject internal test status badges.
        $pagetype = $PAGE->pagetype ?? '';
        if (strpos($pagetype, 'course-view-') === 0 && isloggedin() && !isguestuser()) {
            require_once($CFG->dirroot . '/local/support/lib.php');
            \local_support_inject_internaltest_badges();
        }

        $hook->add_html(
            '<link rel="preconnect" href="https://fonts.googleapis.com">' .
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' .
            '<link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">'
        );
    }
}

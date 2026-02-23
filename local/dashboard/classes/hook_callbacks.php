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
 * Hook callbacks for local_dashboard.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * Hook callback handlers for the dashboard sidebar and direction redirect.
 */
class hook_callbacks {

    /**
     * Redirect non-admin users who have no active direction to the selection page.
     * If user has exactly one direction, auto-select it.
     *
     * Runs on after_config hook - before any output, so redirect() works reliably.
     *
     * @param \core\hook\after_config $hook
     */
    public static function redirect_if_no_direction(\core\hook\after_config $hook): void {
        global $SESSION, $USER, $CFG;

        // Skip CLI, AJAX, and web service requests.
        if (defined('CLI_SCRIPT') || defined('AJAX_SCRIPT') || defined('WS_SERVER')
            || during_initial_install()) {
            return;
        }

        // Must be logged in (non-guest, non-admin).
        if (!isloggedin() || isguestuser() || is_siteadmin()) {
            return;
        }

        // Already have an active direction? Nothing to do.
        if (!empty($SESSION->active_direction_id)) {
            return;
        }

        // Don't redirect if we're already on the selection page or login pages.
        $scriptname = !empty($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        if (strpos($scriptname, '/local/dashboard/') !== false
            || strpos($scriptname, '/login/') !== false
            || strpos($scriptname, '/install') !== false
            || strpos($scriptname, '/admin/') !== false
            || strpos($scriptname, '/lib/') !== false
            || strpos($scriptname, '/pluginfile.php') !== false
            || strpos($scriptname, '/webservice/') !== false
            || strpos($scriptname, '/tokenpluginfile.php') !== false
            || strpos($scriptname, '/draftfile.php') !== false) {
            return;
        }

        // Get user's directions.
        try {
            $directions = \local_recruitment\recruitment::get_user_directions($USER->id);
        } catch (\Exception $e) {
            return;
        }

        if (empty($directions)) {
            return;
        }

        // Auto-select if only one direction.
        if (count($directions) == 1) {
            $first = reset($directions);
            $SESSION->active_direction_id = $first->id;
            return;
        }

        // Multiple directions - redirect to selection page.
        redirect(new \moodle_url('/local/dashboard/index.php', ['change' => 1]));
    }

    /**
     * Inject the sidebar HTML into pages when a direction is selected.
     *
     * Desktop: sidebar is rendered as a fixed element on the left.
     * Mobile: JS copies the menu items into the existing Boost primary drawer.
     *
     * @param \core\hook\output\before_standard_top_of_body_html_generation $hook
     */
    public static function inject_sidebar(
        \core\hook\output\before_standard_top_of_body_html_generation $hook
    ): void {
        global $SESSION, $PAGE, $OUTPUT, $USER;

        // Only for logged-in users.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Don't inject on the dashboard direction selection page itself.
        $currenturl = $PAGE->url->get_path();
        if ($currenturl === '/local/dashboard/index.php' || $currenturl === '/local/dashboard/') {
            return;
        }

        // Don't inject on login, admin upgrade, or install pages.
        if (strpos($currenturl, '/login/') !== false ||
            strpos($currenturl, '/install') !== false) {
            return;
        }

        $isadmin = is_siteadmin();
        $hasdirection = !empty($SESSION->active_direction_id);

        // Show sidebar only if direction is selected or user is admin.
        if (!$hasdirection && !$isadmin) {
            return;
        }

        $directionid = $hasdirection ? (int)$SESSION->active_direction_id : null;

        // Determine active page from URL.
        $activepage = '';
        $path = $PAGE->url->get_path();
        $pagemap = [
            // User pages.
            '/my/' => 'dashboard',
            '/my/index.php' => 'dashboard',
            '/local/dashboard/mydata.php' => 'mydata',
            '/local/schedule/view.php' => 'schedule',
            '/local/financial/view.php' => 'financial',
            '/local/organizational/view.php' => 'organizational',
            // Admin pages.
            '/local/recruitment/index.php' => 'admin_recruitment',
            '/local/recruitment/edit.php' => 'admin_recruitment',
            '/local/recruitment/delete.php' => 'admin_recruitment',
            '/local/recruitment/courses.php' => 'admin_recruitment',
            '/local/recruitment/course_edit.php' => 'admin_recruitment',
            '/local/recruitment/course_delete.php' => 'admin_recruitment',
            '/local/dashboard/announcements.php' => 'admin_announcements',
            '/local/dashboard/announcement_edit.php' => 'admin_announcements',
            '/local/dashboard/announcement_view.php' => 'admin_announcements',
            '/local/schedule/index.php' => 'admin_schedule',
            '/local/schedule/edit.php' => 'admin_schedule',
            '/local/schedule/delete.php' => 'admin_schedule',
            '/local/schedule/notify_history.php' => 'admin_schedule',
            '/local/financial/index.php' => 'admin_financial',
            '/local/financial/edit.php' => 'admin_financial',
            '/local/financial/delete.php' => 'admin_financial',
            '/local/financial/notify_history.php' => 'admin_financial',
            '/local/organizational/index.php' => 'admin_organizational',
            '/local/organizational/edit.php' => 'admin_organizational',
            '/local/organizational/delete.php' => 'admin_organizational',
            '/local/organizational/notify_history.php' => 'admin_organizational',
            '/local/activityreport/index.php' => 'admin_activityreport',
            '/local/userprogress/index.php' => 'admin_userprogress',
            '/local/logreport/index.php' => 'admin_logreport',
        ];
        foreach ($pagemap as $urlpath => $key) {
            if ($path === $urlpath) {
                $activepage = $key;
                break;
            }
        }
        // Course pages — detect archive/exam/tests by matching direction course IDs.
        if (empty($activepage) && strpos($path, '/course/view.php') !== false && $directionid) {
            $courseid = $PAGE->url->get_param('id');
            if ($courseid) {
                global $DB;
                $direction = $DB->get_record('local_recruitment_course', ['id' => $directionid]);
                if ($direction) {
                    if ((int)$courseid === (int)($direction->archive_course ?? 0)) {
                        $activepage = 'archive';
                    } else if ((int)$courseid === (int)($direction->preparation_course ?? 0)) {
                        $activepage = 'exam';
                    } else if ((int)$courseid === (int)($direction->quizes_course ?? 0)) {
                        $activepage = 'tests';
                    }
                }
            }
        }

        $sidebar = new \local_dashboard\output\sidebar($activepage, $directionid);
        $sidebarhtml = $OUTPUT->render_from_template(
            'local_dashboard/sidebar',
            $sidebar->export_for_template($OUTPUT)
        );

        // Hide primary navigation for non-admin users.
        if (!$isadmin) {
            $sidebarhtml .= '<style>' .
                '.primary-navigation { display: none !important; }' .
                '#carousel-item-main a.dropdown-item[href*="user/preferences.php"] { display: none !important; }' .
                '#carousel-item-main a.dropdown-item[href*="user/preferences.php"] + .dropdown-divider { display: none !important; }' .
                '</style>';
        }

        // Add body class via inline script before any rendering to avoid visual jump.
        // Cannot use $PAGE->add_body_class() here as output may have started.
        $sidebarhtml .= '<script>document.body.classList.add("has-dashboard-sidebar");</script>';

        $hook->add_html($sidebarhtml);

        // Load sidebar JS (handles mobile drawer injection).
        $PAGE->requires->js_call_amd('local_dashboard/sidebar', 'init');
    }

    /**
     * Inject head HTML — replaces legacy local_dashboard_before_standard_html_head().
     *
     * Validates active direction and redirects if needed.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function inject_head_html(
        \core\hook\output\before_standard_head_html_generation $hook
    ): void {
        global $SESSION, $USER, $PAGE;

        if (!isloggedin() || isguestuser() || is_siteadmin()) {
            return;
        }

        // Already have an active direction — verify user still belongs to it.
        if (!empty($SESSION->active_direction_id)) {
            try {
                $directions = \local_recruitment\recruitment::get_user_directions((int)$USER->id);
            } catch (\Exception $e) {
                return;
            }
            $found = false;
            foreach ($directions as $d) {
                if ((int)$d->id === (int)$SESSION->active_direction_id) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                return;
            }
            // Direction no longer valid for this user — reset.
            unset($SESSION->active_direction_id);
        }

        // Don't redirect if we're already on the selection page or certain paths.
        $path = $PAGE->url->get_path();
        if (strpos($path, '/local/dashboard/') !== false
            || strpos($path, '/login/') !== false
            || strpos($path, '/admin/') !== false) {
            return;
        }

        try {
            $directions = \local_recruitment\recruitment::get_user_directions((int)$USER->id);
        } catch (\Exception $e) {
            return;
        }

        if (empty($directions)) {
            return;
        }

        if (count($directions) == 1) {
            $first = reset($directions);
            $SESSION->active_direction_id = $first->id;
            return;
        }

        // Multiple directions - redirect to selection page.
        redirect(new \moodle_url('/local/dashboard/index.php', ['change' => 1]));
    }
}

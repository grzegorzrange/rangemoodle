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
 * Sidebar renderable for the dashboard.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Sidebar menu data.
 */
class sidebar implements renderable, templatable {

    /** @var string Current active page key. */
    protected string $activepage;

    /** @var int|null Current direction ID. */
    protected ?int $directionid;

    /**
     * Constructor.
     *
     * @param string $activepage The key of the active page.
     * @param int|null $directionid The current direction ID.
     */
    public function __construct(string $activepage = 'dashboard', ?int $directionid = null) {
        $this->activepage = $activepage;
        $this->directionid = $directionid;
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $DB;

        $isadmin = is_siteadmin();

        // Resolve course URLs for the current direction.
        $archiveurl = '#';
        $examurl = '#';
        $testsurl = '#';
        if ($this->directionid) {
            $direction = $DB->get_record('local_recruitment_course', ['id' => $this->directionid]);
            if ($direction) {
                if (!empty($direction->archive_course)) {
                    $archiveurl = (new \moodle_url('/course/view.php', ['id' => $direction->archive_course]))->out(false);
                }
                if (!empty($direction->preparation_course)) {
                    $examurl = (new \moodle_url('/course/view.php', ['id' => $direction->preparation_course]))->out(false);
                }
                if (!empty($direction->quizes_course)) {
                    $testsurl = (new \moodle_url('/course/view.php', ['id' => $direction->quizes_course]))->out(false);
                }
            }
        }

        // User menu items — only for non-admin users.
        $menuitems = [];
        if (!$isadmin) {
            $menuitems = [
                [
                    'key' => 'dashboard',
                    'text' => get_string('dashboard', 'local_dashboard'),
                    'icon' => 'fa-gauge-high',
                    'url' => (new \moodle_url('/my/'))->out(false),
                ],
                [
                    'key' => 'schedule',
                    'text' => get_string('schedule', 'local_dashboard'),
                    'icon' => 'fa-calendar-days',
                    'url' => (new \moodle_url('/local/schedule/view.php'))->out(false),
                ],
                [
                    'key' => 'archive',
                    'text' => get_string('lecturearchive', 'local_dashboard'),
                    'icon' => 'fa-box-archive',
                    'url' => $archiveurl,
                ],
                [
                    'key' => 'exam',
                    'text' => get_string('exampreparation', 'local_dashboard'),
                    'icon' => 'fa-graduation-cap',
                    'url' => $examurl,
                ],
                [
                    'key' => 'tests',
                    'text' => get_string('internaltests', 'local_dashboard'),
                    'icon' => 'fa-clipboard-check',
                    'url' => $testsurl,
                ],
                [
                    'key' => 'financial',
                    'text' => get_string('financialmatters', 'local_dashboard'),
                    'icon' => 'fa-money-bill-wave',
                    'url' => (new \moodle_url('/local/financial/view.php'))->out(false),
                ],
                [
                    'key' => 'organizational',
                    'text' => get_string('organizationalmatters', 'local_dashboard'),
                    'icon' => 'fa-building',
                    'url' => (new \moodle_url('/local/organizational/view.php'))->out(false),
                ],
                [
                    'key' => 'mydata',
                    'text' => get_string('mydata', 'local_dashboard'),
                    'icon' => 'fa-user',
                    'url' => (new \moodle_url('/local/dashboard/mydata.php'))->out(false),
                ],
            ];

            // Mark active page.
            foreach ($menuitems as &$item) {
                $item['active'] = ($item['key'] === $this->activepage);
            }
        }

        // Admin items — only for admins.
        $adminitems = [];
        if ($isadmin) {
            $adminitems = [
                [
                    'key' => 'admin_recruitment',
                    'text' => get_string('recruitments', 'local_dashboard'),
                    'icon' => 'fa-users-gear',
                    'url' => (new \moodle_url('/local/recruitment/index.php'))->out(false),
                ],
                [
                    'key' => 'admin_announcements',
                    'text' => get_string('dashboardmanagement', 'local_dashboard'),
                    'icon' => 'fa-sliders',
                    'url' => (new \moodle_url('/local/dashboard/announcements.php'))->out(false),
                ],
                [
                    'key' => 'admin_schedule',
                    'text' => get_string('schedulemanagement', 'local_dashboard'),
                    'icon' => 'fa-calendar-days',
                    'url' => (new \moodle_url('/local/schedule/index.php'))->out(false),
                ],
                [
                    'key' => 'admin_financial',
                    'text' => get_string('financialmanagement', 'local_dashboard'),
                    'icon' => 'fa-money-bill-wave',
                    'url' => (new \moodle_url('/local/financial/index.php'))->out(false),
                ],
                [
                    'key' => 'admin_organizational',
                    'text' => get_string('organizationalmanagement', 'local_dashboard'),
                    'icon' => 'fa-building',
                    'url' => (new \moodle_url('/local/organizational/index.php'))->out(false),
                ],
                [
                    'key' => 'admin_activityreport',
                    'text' => get_string('activityreport', 'local_dashboard'),
                    'icon' => 'fa-chart-line',
                    'url' => (new \moodle_url('/local/activityreport/index.php'))->out(false),
                ],
                [
                    'key' => 'admin_userprogress',
                    'text' => get_string('userprogress', 'local_dashboard'),
                    'icon' => 'fa-tasks',
                    'url' => (new \moodle_url('/local/userprogress/index.php'))->out(false),
                ],
                [
                    'key' => 'admin_logreport',
                    'text' => get_string('logreport', 'local_dashboard'),
                    'icon' => 'fa-file-lines',
                    'url' => (new \moodle_url('/local/logreport/index.php'))->out(false),
                ],
            ];

            // Mark active admin page.
            foreach ($adminitems as &$item) {
                $item['active'] = ($item['key'] === $this->activepage);
            }
        }

        // Admin quick-access items (dashboard + site admin).
        $adminquickitems = [];
        if ($isadmin) {
            $adminquickitems = [
                [
                    'key' => 'dashboard',
                    'text' => get_string('dashboard', 'local_dashboard'),
                    'icon' => 'fa-house',
                    'url' => (new \moodle_url('/my/'))->out(false),
                    'active' => ($this->activepage === 'dashboard'),
                ],
                [
                    'key' => 'siteadmin',
                    'text' => get_string('administration', 'local_dashboard'),
                    'icon' => 'fa-gear',
                    'url' => (new \moodle_url('/admin/search.php'))->out(false),
                    'active' => false,
                ],
            ];
        }

        return [
            'menuitems' => $menuitems,
            'hasmenuitems' => !empty($menuitems),
            'adminitems' => $adminitems,
            'hasadminitems' => !empty($adminitems),
            'adminquickitems' => $adminquickitems,
            'hasadminquickitems' => !empty($adminquickitems),
        ];
    }
}

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
 * External function to get a page of announcements.
 *
 * @package    block_announcements
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_announcements\external;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/externallib.php');

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

/**
 * Returns a page of announcements for the active direction.
 */
class get_page extends external_api {

    /**
     * Parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'directionid' => new external_value(PARAM_INT, 'Direction ID'),
            'page' => new external_value(PARAM_INT, 'Page number (0-based)', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $directionid
     * @param int $page
     * @return array
     */
    public static function execute(int $directionid, int $page = 0): array {
        $params = self::validate_parameters(self::execute_parameters(), [
            'directionid' => $directionid,
            'page' => $page,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);

        $perpage = 3;
        $result = \local_dashboard\announcement::get_for_direction($params['directionid'], $params['page'], $perpage);
        $total = $result['total'];
        $totalpages = (int)ceil($total / $perpage);

        $tiles = [];
        foreach ($result['records'] as $record) {
            $plaintext = html_to_text($record->message, 0, false);
            if (\core_text::strlen($plaintext) > 200) {
                $plaintext = \core_text::substr($plaintext, 0, 200) . '...';
            }

            $attachcount = \local_dashboard\announcement::count_attachments($record->id, $context);
            $viewurl = new \moodle_url('/local/dashboard/announcement_view.php', ['id' => $record->id]);

            $tiles[] = [
                'name' => format_string($record->name),
                'text' => $plaintext,
                'date' => userdate($record->timecreated, get_string('strftimedaydatetime', 'langconfig')),
                'attachcount' => (int)$attachcount,
                'hasattachments' => $attachcount > 0,
                'viewurl' => $viewurl->out(false),
            ];
        }

        return [
            'tiles' => $tiles,
            'totalpages' => $totalpages,
            'currentpage' => $params['page'],
        ];
    }

    /**
     * Return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'tiles' => new external_multiple_structure(
                new external_single_structure([
                    'name' => new external_value(PARAM_TEXT, 'Announcement name'),
                    'text' => new external_value(PARAM_RAW, 'Announcement text preview'),
                    'date' => new external_value(PARAM_TEXT, 'Formatted date'),
                    'attachcount' => new external_value(PARAM_INT, 'Number of attachments'),
                    'hasattachments' => new external_value(PARAM_BOOL, 'Has attachments'),
                    'viewurl' => new external_value(PARAM_URL, 'View URL'),
                ])
            ),
            'totalpages' => new external_value(PARAM_INT, 'Total pages'),
            'currentpage' => new external_value(PARAM_INT, 'Current page'),
        ]);
    }
}

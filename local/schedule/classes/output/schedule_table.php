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
 * Schedule table for admin listing.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_schedule\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Table class for displaying schedules.
 */
class schedule_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \moodle_url $url
     */
    public function __construct(string $uniqueid, \moodle_url $url) {
        global $DB;

        parent::__construct($uniqueid);
        $this->baseurl = $url;

        $columns = ['directionname', 'name', 'message', 'actions'];
        $headers = [
            get_string('direction', 'local_schedule'),
            get_string('schedulename', 'local_schedule'),
            get_string('schedulecontent', 'local_schedule'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($url);
        $this->collapsible(false);
        $this->sortable(true, 'name', SORT_ASC);
        $this->pageable(true);
        $this->no_sorting('actions');
        $this->no_sorting('message');

        $concat = $DB->sql_concat('r.name', "' → '", 'rc.name');
        $this->set_sql(
            "s.id, s.name, s.message, s.messageformat, s.directionid, s.timecreated,
             {$concat} AS directionname",
            '{local_schedule} s
             JOIN {local_recruitment_course} rc ON rc.id = s.directionid
             JOIN {local_recruitment} r ON r.id = rc.recruitmentid',
            '1=1'
        );
    }

    /**
     * Format the message column - truncated preview.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_message(\stdClass $row): string {
        $text = html_to_text($row->message, 0, false);
        if (\core_text::strlen($text) > 100) {
            $text = \core_text::substr($text, 0, 100) . '...';
        }
        return s($text);
    }

    /**
     * Format the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions(\stdClass $row): string {
        global $OUTPUT;

        $actions = '';

        // Edit.
        $editurl = new \moodle_url('/local/schedule/edit.php', ['id' => $row->id]);
        $actions .= \html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        $actions .= ' ';

        // Delete.
        $deleteurl = new \moodle_url('/local/schedule/delete.php', ['id' => $row->id, 'sesskey' => sesskey()]);
        $actions .= \html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        $actions .= ' ';

        // Send notification.
        $notifyurl = new \moodle_url('/local/schedule/notify.php', ['id' => $row->id, 'sesskey' => sesskey()]);
        $actions .= \html_writer::link($notifyurl,
            $OUTPUT->pix_icon('t/email', get_string('sendnotification', 'local_schedule')));
        $actions .= ' ';

        // Notification history.
        $historyurl = new \moodle_url('/local/schedule/notify_history.php', ['id' => $row->id]);
        $actions .= \html_writer::link($historyurl,
            $OUTPUT->pix_icon('t/log', get_string('notifyhistory', 'local_schedule')));

        return $actions;
    }
}

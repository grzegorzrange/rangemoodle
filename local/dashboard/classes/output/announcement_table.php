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
 * Announcement table for admin listing.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

use local_dashboard\announcement;

/**
 * Table class for displaying announcements.
 */
class announcement_table extends \table_sql {

    /** @var \context_system */
    protected $context;

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \moodle_url $url
     */
    public function __construct(string $uniqueid, \moodle_url $url) {
        parent::__construct($uniqueid);
        $this->baseurl = $url;
        $this->context = \context_system::instance();

        $columns = ['directionname', 'name', 'message', 'hasattachments', 'actions'];
        $headers = [
            get_string('direction', 'local_dashboard'),
            get_string('announcementname', 'local_dashboard'),
            get_string('announcementtext', 'local_dashboard'),
            get_string('attachments', 'local_dashboard'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($url);
        $this->collapsible(false);
        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->pageable(true);
        $this->no_sorting('actions');
        $this->no_sorting('hasattachments');
        $this->no_sorting('message');

        $this->set_sql(
            "a.id, a.name, a.message, a.messageformat, a.directionid, a.notificationsent,
             a.notificationsenttime, a.visible, a.timecreated,
             CONCAT(r.name, ' — ', rc.name) AS directionname",
            '{local_dashboard_announce} a
             JOIN {local_recruitment_course} rc ON rc.id = a.directionid
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
     * Format the attachments column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_hasattachments(\stdClass $row): string {
        $count = announcement::count_attachments($row->id, $this->context);
        return $count > 0 ? get_string('yes') . " ($count)" : get_string('no');
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
        $editurl = new \moodle_url('/local/dashboard/announcement_edit.php', ['id' => $row->id]);
        $actions .= \html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));
        $actions .= ' ';

        // Delete.
        $deleteurl = new \moodle_url('/local/dashboard/announcement_delete.php', ['id' => $row->id, 'sesskey' => sesskey()]);
        $actions .= \html_writer::link($deleteurl, $OUTPUT->pix_icon('t/delete', get_string('delete')));
        $actions .= ' ';

        // Visibility toggle.
        if ($row->visible) {
            $toggleurl = new \moodle_url('/local/dashboard/announcements.php', [
                'toggleid' => $row->id,
                'sesskey' => sesskey(),
            ]);
            $actions .= \html_writer::link($toggleurl, $OUTPUT->pix_icon('t/hide', get_string('hide')));
        } else {
            $toggleurl = new \moodle_url('/local/dashboard/announcements.php', [
                'toggleid' => $row->id,
                'sesskey' => sesskey(),
            ]);
            $actions .= \html_writer::link($toggleurl, $OUTPUT->pix_icon('t/show', get_string('show')));
        }
        $actions .= ' ';

        // Send notification.
        if (!$row->notificationsent) {
            $notifyurl = new \moodle_url('/local/dashboard/announcement_notify.php', [
                'id' => $row->id,
                'sesskey' => sesskey(),
            ]);
            $actions .= \html_writer::link($notifyurl,
                $OUTPUT->pix_icon('t/email', get_string('sendnotification', 'local_dashboard')));
        } else {
            $senttime = userdate($row->notificationsenttime, get_string('strftimedatetimeshort', 'langconfig'));
            $actions .= \html_writer::tag('span',
                $OUTPUT->pix_icon('t/check', get_string('notificationsent', 'local_dashboard', $senttime)),
                ['class' => 'text-muted']
            );
        }

        return $actions;
    }
}

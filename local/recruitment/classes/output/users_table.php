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
 * Users table for a direction.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Table class for displaying users within a direction.
 */
class users_table extends \table_sql {

    /** @var int Direction ID. */
    protected int $directionid;

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \moodle_url $url
     * @param int $directionid
     */
    public function __construct(string $uniqueid, \moodle_url $url, int $directionid) {
        parent::__construct($uniqueid);
        $this->directionid = $directionid;
        $this->baseurl = $url;

        $columns = ['username', 'firstname', 'lastname', 'email', 'declaration', 'notified', 'actions'];
        $headers = [
            get_string('username'),
            get_string('firstname'),
            get_string('lastname'),
            get_string('email'),
            get_string('declaration', 'local_recruitment'),
            get_string('notificationstatus', 'local_recruitment'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($url);
        $this->collapsible(false);
        $this->sortable(true, 'lastname', SORT_ASC);
        $this->pageable(true);
        $this->no_sorting('actions');

        $this->set_sql(
            'ru.id, ru.declaration, ru.notified, ru.timenotified, ru.userid, ru.directionid,
             u.username, u.firstname, u.lastname, u.email',
            '{local_recruitment_user} ru
             JOIN {user} u ON u.id = ru.userid',
            'ru.directionid = :directionid',
            ['directionid' => $directionid]
        );
    }

    /**
     * Format the declaration column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_declaration(\stdClass $row): string {
        if (!empty($row->declaration)) {
            return '<span class="badge badge-success bg-success">' .
                get_string('declarationyes', 'local_recruitment') . '</span>';
        }
        return '<span class="badge badge-secondary bg-secondary">' .
            get_string('declarationno', 'local_recruitment') . '</span>';
    }

    /**
     * Format the notified column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_notified(\stdClass $row): string {
        if (!empty($row->notified)) {
            $date = userdate($row->timenotified, get_string('strftimedatetimeshort', 'langconfig'));
            return '<span class="badge badge-success bg-success">' .
                get_string('notifiedyes', 'local_recruitment') . '</span>' .
                '<br><small class="text-muted">' . $date . '</small>';
        }
        return '<span class="badge badge-secondary bg-secondary">' .
            get_string('notifiedno', 'local_recruitment') . '</span>';
    }

    /**
     * Format the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions(\stdClass $row): string {
        global $OUTPUT;

        // Only show "Odznacz deklarację" when declaration is NOT set (0).
        // Once set to 1, the action disappears (irreversible).
        if (!empty($row->declaration)) {
            return '';
        }

        $seturl = new \moodle_url('/local/recruitment/users.php', [
            'did' => $row->directionid,
            'setdeclaration' => $row->id,
            'sesskey' => sesskey(),
        ]);
        $icon = $OUTPUT->pix_icon('t/check', get_string('setdeclaration', 'local_recruitment'));
        return \html_writer::link($seturl, $icon . ' ' . get_string('setdeclaration', 'local_recruitment'), [
            'class' => 'btn btn-sm btn-outline-success',
        ]);
    }
}

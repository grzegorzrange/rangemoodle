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
 * Recruitment table for listing.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Table class for displaying recruitments.
 */
class recruitment_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \moodle_url $url
     */
    public function __construct(string $uniqueid, \moodle_url $url) {
        parent::__construct($uniqueid);
        $this->baseurl = $url;

        $columns = ['name', 'recruitmentdate', 'coursecount', 'actions'];
        $headers = [
            get_string('recruitmentname', 'local_recruitment'),
            get_string('recruitmentdate', 'local_recruitment'),
            get_string('assignedcourses', 'local_recruitment'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($url);
        $this->collapsible(false);
        $this->sortable(true, 'name', SORT_ASC);
        $this->pageable(true);
        $this->no_sorting('actions');
        $this->no_sorting('coursecount');

        $this->set_sql(
            'r.id, r.name, r.recruitmentdate,
             (SELECT COUNT(*) FROM {local_recruitment_course} rc WHERE rc.recruitmentid = r.id) AS coursecount',
            '{local_recruitment} r',
            '1=1'
        );
    }

    /**
     * Format the recruitment date column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_recruitmentdate(\stdClass $row): string {
        return userdate($row->recruitmentdate, get_string('strftimedate', 'langconfig'));
    }

    /**
     * Format the course count column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursecount(\stdClass $row): string {
        return (string)$row->coursecount;
    }

    /**
     * Format the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions(\stdClass $row): string {
        global $OUTPUT;

        $enterurl = new \moodle_url('/local/recruitment/courses.php', ['rid' => $row->id]);
        $editurl = new \moodle_url('/local/recruitment/edit.php', ['id' => $row->id]);
        $deleteurl = new \moodle_url('/local/recruitment/delete.php', ['id' => $row->id, 'sesskey' => sesskey()]);

        $entericon = $OUTPUT->pix_icon('t/viewdetails', get_string('enter', 'local_recruitment'));
        $editicon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete'));

        $actions = \html_writer::link($enterurl, $entericon);
        $actions .= ' ';
        $actions .= \html_writer::link($editurl, $editicon);
        $actions .= ' ';
        $actions .= \html_writer::link($deleteurl, $deleteicon);

        return $actions;
    }
}

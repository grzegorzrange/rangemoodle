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
 * Direction table for listing within a recruitment.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Table class for displaying directions within a recruitment.
 */
class direction_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \moodle_url $url
     * @param int $recruitmentid
     */
    public function __construct(string $uniqueid, \moodle_url $url, int $recruitmentid) {
        parent::__construct($uniqueid);
        $this->baseurl = $url;

        $columns = ['name', 'basecategoryname', 'copystatus', 'archivecoursename', 'preparationcoursename',
                     'quizescoursename', 'cohortname', 'actions'];
        $headers = [
            get_string('directionname', 'local_recruitment'),
            get_string('basecategory', 'local_recruitment'),
            get_string('copystatus', 'local_recruitment'),
            get_string('archivecourse', 'local_recruitment'),
            get_string('preparationcourse', 'local_recruitment'),
            get_string('quizescourse', 'local_recruitment'),
            get_string('cohort', 'local_recruitment'),
            get_string('actions'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($url);
        $this->collapsible(false);
        $this->sortable(true, 'name', SORT_ASC);
        $this->pageable(true);
        $this->no_sorting('actions');
        $this->no_sorting('basecategoryname');
        $this->no_sorting('archivecoursename');
        $this->no_sorting('preparationcoursename');
        $this->no_sorting('quizescoursename');
        $this->no_sorting('cohortname');
        $this->no_sorting('copystatus');

        $this->set_sql(
            'rc.id, rc.recruitmentid, rc.name, rc.basecategoryid, rc.copystatus,
             rc.archive_course, rc.preparation_course, rc.quizes_course, rc.cohortid,
             cat.name AS basecategoryname,
             c1.fullname AS archivecoursename, c2.fullname AS preparationcoursename,
             c3.fullname AS quizescoursename,
             co.name AS cohortname',
            '{local_recruitment_course} rc
             LEFT JOIN {course_categories} cat ON cat.id = rc.basecategoryid
             LEFT JOIN {course} c1 ON c1.id = rc.archive_course
             LEFT JOIN {course} c2 ON c2.id = rc.preparation_course
             LEFT JOIN {course} c3 ON c3.id = rc.quizes_course
             LEFT JOIN {cohort} co ON co.id = rc.cohortid',
            'rc.recruitmentid = :recruitmentid',
            ['recruitmentid' => $recruitmentid]
        );
    }

    /**
     * Format the copy status column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_copystatus(\stdClass $row): string {
        if (empty($row->copystatus)) {
            return '<span class="badge badge-warning bg-warning text-dark">'
                . '<i class="fa fa-spinner fa-spin"></i> '
                . get_string('copyinprogress', 'local_recruitment')
                . '</span>';
        }
        return '<span class="badge badge-success bg-success">'
            . '<i class="fa fa-check"></i> '
            . get_string('copydone', 'local_recruitment')
            . '</span>';
    }

    /**
     * Format the base category column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_basecategoryname(\stdClass $row): string {
        return $row->basecategoryname ?? '-';
    }

    /**
     * Format the archive course column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_archivecoursename(\stdClass $row): string {
        return $row->archivecoursename ?? '-';
    }

    /**
     * Format the preparation course column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_preparationcoursename(\stdClass $row): string {
        return $row->preparationcoursename ?? '-';
    }

    /**
     * Format the quizes course column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_quizescoursename(\stdClass $row): string {
        return $row->quizescoursename ?? '-';
    }

    /**
     * Format the cohort column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_cohortname(\stdClass $row): string {
        return $row->cohortname ?? '-';
    }

    /**
     * Format the actions column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_actions(\stdClass $row): string {
        global $OUTPUT;

        $usersurl = new \moodle_url('/local/recruitment/users.php', ['did' => $row->id]);
        $editurl = new \moodle_url('/local/recruitment/course_edit.php', ['id' => $row->id]);
        $deleteurl = new \moodle_url('/local/recruitment/course_delete.php', ['id' => $row->id, 'sesskey' => sesskey()]);

        $usersicon = $OUTPUT->pix_icon('t/cohort', get_string('users', 'local_recruitment'));
        $editicon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $deleteicon = $OUTPUT->pix_icon('t/delete', get_string('delete'));

        $actions = \html_writer::link($usersurl, $usersicon);
        $actions .= ' ';
        $actions .= \html_writer::link($editurl, $editicon);
        $actions .= ' ';
        $actions .= \html_writer::link($deleteurl, $deleteicon);

        return $actions;
    }
}

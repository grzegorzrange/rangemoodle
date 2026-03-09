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
 * Table displaying direction courses of a given type.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Table displaying direction courses of a given type.
 */
class course_overview_table extends \table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid
     * @param \moodle_url $url
     * @param string $coursetype One of 'archiwum', 'przygotowanie', 'testy'.
     */
    public function __construct(string $uniqueid, \moodle_url $url, string $coursetype) {
        global $DB;

        parent::__construct($uniqueid);
        $this->baseurl = $url;

        $columns = ['directionname', 'coursename'];
        $headers = [
            get_string('directionname', 'local_recruitment'),
            get_string('course'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($url);
        $this->column_style('directionname', 'width', '50%');
        $this->column_style('coursename', 'width', '50%');
        $this->collapsible(false);
        $this->sortable(true, 'directionname', SORT_ASC);
        $this->pageable(true);
        $this->no_sorting('coursename');

        $coursecol = self::get_course_column($coursetype);
        $concat = $DB->sql_concat('r.name', "' → '", 'rc.name');

        $this->set_sql(
            "rc.id, {$concat} AS directionname, c.id AS courseid, c.fullname AS coursename",
            "{local_recruitment_course} rc
             JOIN {local_recruitment} r ON r.id = rc.recruitmentid
             JOIN {course} c ON c.id = rc.{$coursecol}",
            "rc.{$coursecol} IS NOT NULL"
        );
    }

    /**
     * Map type keyword to DB column.
     *
     * @param string $type
     * @return string
     */
    public static function get_course_column(string $type): string {
        $map = [
            'archiwum' => 'archive_course',
            'przygotowanie' => 'preparation_course',
            'testy' => 'quizes_course',
        ];
        return $map[$type] ?? 'archive_course';
    }

    /**
     * Format direction name column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_directionname(\stdClass $row): string {
        return format_string($row->directionname);
    }

    /**
     * Format course name as clickable link.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_coursename(\stdClass $row): string {
        if (empty($row->courseid) || empty($row->coursename)) {
            return '-';
        }
        $url = new \moodle_url('/course/view.php', ['id' => $row->courseid]);
        return \html_writer::link($url, format_string($row->coursename));
    }
}

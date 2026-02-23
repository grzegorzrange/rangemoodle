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
 * User progress table.
 *
 * @package    local_userprogress
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_userprogress\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

use table_sql;
use moodle_url;

/**
 * Table displaying user progress across directions.
 */
class userprogress_table extends table_sql {

    /**
     * Constructor.
     *
     * @param string $uniqueid Unique ID for the table.
     * @param moodle_url $baseurl Base URL for the page.
     * @param array $filters Active filters.
     */
    public function __construct(string $uniqueid, moodle_url $baseurl, array $filters = []) {
        parent::__construct($uniqueid);

        $this->define_columns([
            'firstname',
            'lastname',
            'email',
            'directionname',
            'archive_percent',
            'modules_percent',
            'test_status',
            'lastactivity',
        ]);

        $this->define_headers([
            get_string('firstname', 'local_userprogress'),
            get_string('lastname', 'local_userprogress'),
            get_string('email', 'local_userprogress'),
            get_string('recruitment', 'local_userprogress'),
            get_string('archivepercent', 'local_userprogress'),
            get_string('modulespercent', 'local_userprogress'),
            get_string('teststatus', 'local_userprogress'),
            get_string('lastactivity', 'local_userprogress'),
        ]);

        $this->sortable(true, 'lastname', SORT_ASC);

        $this->collapsible(false);
        $this->define_baseurl($baseurl);
        $this->set_attribute('class', 'generaltable generalbox');

        $this->setup_sql($filters);
    }

    /**
     * Set up the SQL query with filters.
     *
     * Completion percentages and last activity are computed as SQL subqueries
     * so that all columns are sortable.
     *
     * @param array $filters Active filters.
     */
    protected function setup_sql(array $filters): void {
        global $DB;

        $concat_direction = $DB->sql_concat('r.name', "' → '", 'rc.name');

        // Subquery: archive completion percentage (uses rc.archive_course directly).
        $archive_sub = "(
            SELECT COALESCE(ROUND(
                SUM(CASE WHEN cmc1.completionstate > 0 THEN 1 ELSE 0 END) * 100.0 /
                NULLIF(COUNT(cm1.id), 0)
            , 1), 0)
            FROM {course_modules} cm1
            LEFT JOIN {course_modules_completion} cmc1
                ON cmc1.coursemoduleid = cm1.id AND cmc1.userid = u.id
            WHERE cm1.course = rc.archive_course
            AND cm1.completion > 0
            AND cm1.deletioninprogress = 0
        )";

        // Subquery: modules (preparation) completion percentage.
        $modules_sub = "(
            SELECT COALESCE(ROUND(
                SUM(CASE WHEN cmc2.completionstate > 0 THEN 1 ELSE 0 END) * 100.0 /
                NULLIF(COUNT(cm2.id), 0)
            , 1), 0)
            FROM {course_modules} cm2
            LEFT JOIN {course_modules_completion} cmc2
                ON cmc2.coursemoduleid = cm2.id AND cmc2.userid = u.id
            WHERE cm2.course = rc.preparation_course
            AND cm2.completion > 0
            AND cm2.deletioninprogress = 0
        )";

        // Subquery: test completion percentage.
        $test_sub = "(
            SELECT COALESCE(ROUND(
                SUM(CASE WHEN cmc3.completionstate > 0 THEN 1 ELSE 0 END) * 100.0 /
                NULLIF(COUNT(cm3.id), 0)
            , 1), 0)
            FROM {course_modules} cm3
            LEFT JOIN {course_modules_completion} cmc3
                ON cmc3.coursemoduleid = cm3.id AND cmc3.userid = u.id
            WHERE cm3.course = rc.quizes_course
            AND cm3.completion > 0
            AND cm3.deletioninprogress = 0
        )";

        // Subquery: last activity timestamp.
        $lastactivity_sub = "(
            SELECT MAX(l1.timecreated)
            FROM {logstore_standard_log} l1
            WHERE l1.userid = u.id
            AND l1.courseid IN (rc.archive_course, rc.preparation_course, rc.quizes_course)
        )";

        $fields = "cm.id, u.id AS userid, u.firstname, u.lastname, u.email,
                   rc.id AS directionid,
                   rc.archive_course, rc.preparation_course, rc.quizes_course,
                   {$concat_direction} AS directionname,
                   {$archive_sub} AS archive_percent,
                   {$modules_sub} AS modules_percent,
                   {$test_sub} AS test_status,
                   {$lastactivity_sub} AS lastactivity";

        $from = "{cohort_members} cm
                 JOIN {user} u ON u.id = cm.userid
                 JOIN {local_recruitment_course} rc ON rc.cohortid = cm.cohortid
                 JOIN {local_recruitment} r ON r.id = rc.recruitmentid";

        $where = "u.deleted = 0";
        $params = [];

        if (!empty($filters['firstname'])) {
            $where .= " AND " . $DB->sql_like('u.firstname', ':firstname', false);
            $params['firstname'] = '%' . $DB->sql_like_escape($filters['firstname']) . '%';
        }

        if (!empty($filters['lastname'])) {
            $where .= " AND " . $DB->sql_like('u.lastname', ':lastname', false);
            $params['lastname'] = '%' . $DB->sql_like_escape($filters['lastname']) . '%';
        }

        if (!empty($filters['email'])) {
            $where .= " AND " . $DB->sql_like('u.email', ':email', false);
            $params['email'] = '%' . $DB->sql_like_escape($filters['email']) . '%';
        }

        if (!empty($filters['recruitmentid'])) {
            $where .= " AND r.id = :rid";
            $params['rid'] = $filters['recruitmentid'];
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql(
            "SELECT COUNT(cm.id)
               FROM {cohort_members} cm
               JOIN {user} u ON u.id = cm.userid
               JOIN {local_recruitment_course} rc ON rc.cohortid = cm.cohortid
               JOIN {local_recruitment} r ON r.id = rc.recruitmentid
              WHERE {$where}",
            $params
        );
    }

    /**
     * Render archive percentage column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_archive_percent($row): string {
        if ($row->archive_percent === null) {
            return '-';
        }
        return round((float)$row->archive_percent, 1) . '%';
    }

    /**
     * Render modules percentage column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_modules_percent($row): string {
        if ($row->modules_percent === null) {
            return '-';
        }
        return round((float)$row->modules_percent, 1) . '%';
    }

    /**
     * Render test status column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_test_status($row): string {
        if ($row->test_status === null) {
            return '-';
        }
        return round((float)$row->test_status, 1) . '%';
    }

    /**
     * Render last activity column.
     *
     * Shows course name, module name and date of the last activity.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_lastactivity($row): string {
        global $DB;

        if (empty($row->lastactivity)) {
            return '-';
        }

        // Find the actual log record matching this timestamp for details.
        $courseids = [];
        foreach (['archive_course', 'preparation_course', 'quizes_course'] as $field) {
            if (!empty($row->$field)) {
                $courseids[] = (int)$row->$field;
            }
        }

        if (empty($courseids)) {
            return userdate($row->lastactivity);
        }

        list($insql, $inparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
        $inparams['userid'] = (int)$row->userid;

        $sql = "SELECT l.id, l.courseid, l.timecreated, l.objecttable, l.objectid,
                       c.fullname AS coursename
                  FROM {logstore_standard_log} l
                  JOIN {course} c ON c.id = l.courseid
                 WHERE l.userid = :userid
                   AND l.courseid {$insql}
              ORDER BY l.timecreated DESC";

        $records = $DB->get_records_sql($sql, $inparams, 0, 1);

        if (empty($records)) {
            return userdate($row->lastactivity);
        }

        $record = reset($records);

        // Try to get module name.
        $modulename = '-';
        if (!empty($record->objectid)) {
            try {
                $modinfo = get_fast_modinfo($record->courseid);
                if (isset($modinfo->cms[$record->objectid])) {
                    $modulename = $modinfo->cms[$record->objectid]->name;
                }
            } catch (\Throwable $e) {
                // Ignore.
            }
        }

        $a = new \stdClass();
        $a->course = $record->coursename;
        $a->module = $modulename;
        $a->date = userdate($record->timecreated, get_string('strftimedatetimeshort', 'langconfig'));

        return get_string('courseinfo', 'local_userprogress', $a);
    }
}

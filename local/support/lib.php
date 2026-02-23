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
 * Library functions for local_support.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Inject internal test status badges on course view pages.
 *
 * For each quiz in the current course that is marked as internal test,
 * determines the user's status (not attempted / passed / failed) and
 * passes that data to a JS module that renders badges next to activity names.
 */
function local_support_inject_internaltest_badges() {
    global $DB, $USER, $PAGE, $COURSE;

    if (empty($COURSE->id) || $COURSE->id <= 1) {
        return;
    }

    // Get all quiz course modules in this course that are internal tests.
    $sql = "SELECT cm.id AS cmid, q.id AS quizid, q.name, q.grade AS maxgrade, q.sumgrades,
                   q.timeopen, q.timeclose
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module AND m.name = 'quiz'
              JOIN {quiz} q ON q.id = cm.instance
              JOIN {quizaccess_internaltest} qi ON qi.quizid = q.id AND qi.internaltest = 1
             WHERE cm.course = :courseid AND cm.deletioninprogress = 0";
    $quizzes = $DB->get_records_sql($sql, ['courseid' => $COURSE->id]);

    if (empty($quizzes)) {
        return;
    }

    // Get grade pass for each quiz from grade_items.
    $gradepassmap = [];
    $quizids = array_column(array_values($quizzes), 'quizid');
    if (!empty($quizids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($quizids, SQL_PARAMS_NAMED);
        $inparams['courseid'] = $COURSE->id;
        $gradeitems = $DB->get_records_sql(
            "SELECT iteminstance, gradepass FROM {grade_items}
              WHERE itemmodule = 'quiz' AND courseid = :courseid AND iteminstance $insql",
            $inparams
        );
        foreach ($gradeitems as $gi) {
            $gradepassmap[$gi->iteminstance] = (float)$gi->gradepass;
        }
    }

    // For each internal test quiz, determine the user's status.
    $statuses = [];
    foreach ($quizzes as $quiz) {
        // Get best finished attempt for this user.
        $bestattempt = $DB->get_record_sql(
            "SELECT id, sumgrades
               FROM {quiz_attempts}
              WHERE quiz = :quizid AND userid = :userid AND state = 'finished'
              ORDER BY sumgrades DESC
              LIMIT 1",
            ['quizid' => $quiz->quizid, 'userid' => $USER->id]
        );

        if (!$bestattempt) {
            $status = 'notdone';
        } else {
            // Rescale attempt grade to quiz grade scale.
            $attemptgrade = 0;
            if ($quiz->sumgrades > 0) {
                $attemptgrade = ($bestattempt->sumgrades / $quiz->sumgrades) * $quiz->maxgrade;
            }
            $gradepass = $gradepassmap[$quiz->quizid] ?? 0;
            if ($gradepass > 0) {
                $status = ($attemptgrade >= $gradepass) ? 'passed' : 'failed';
            } else {
                // No passing grade set — any finished attempt is "passed".
                $status = 'passed';
            }
        }

        // Determine active/inactive based on timeopen/timeclose.
        $now = time();
        $isactive = true;
        if (!empty($quiz->timeopen) && $now < $quiz->timeopen) {
            $isactive = false;
        }
        if (!empty($quiz->timeclose) && $now > $quiz->timeclose) {
            $isactive = false;
        }

        $statuses[] = [
            'cmid' => (int)$quiz->cmid,
            'status' => $status,
            'availability' => $isactive ? 'active' : 'inactive',
        ];
    }

    if (!empty($statuses)) {
        $PAGE->requires->js_call_amd('local_support/internaltest_badges', 'init', [$statuses]);
    }
}

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

namespace quizaccess_internaltest;

defined('MOODLE_INTERNAL') || die();

use mod_quiz\quiz_attempt;

/**
 * Event observer for internal test quiz submissions.
 *
 * @package    quizaccess_internaltest
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle quiz attempt submitted event.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function attempt_graded(\mod_quiz\event\attempt_graded $event) {
        global $DB;

        $quizid = $event->other['quizid'];

        // Check if this quiz is marked as internal test.
        $isinternaltest = $DB->get_field('quizaccess_internaltest', 'internaltest', ['quizid' => $quizid]);
        if (empty($isinternaltest)) {
            return;
        }

        // Already processed?
        $attemptid = $event->objectid;
        if ($DB->record_exists('quizaccess_inttest_results', ['attemptid' => $attemptid])) {
            return;
        }

        // Load the attempt object.
        $attemptobj = quiz_attempt::create($attemptid);
        $quiz = $attemptobj->get_quiz();
        $userid = $event->relateduserid;
        $user = $DB->get_record('user', ['id' => $userid], 'id, username, firstname, lastname');

        if (!$user) {
            return;
        }

        // Get course info.
        $cm = get_coursemodule_from_instance('quiz', $quizid);
        $course = $DB->get_record('course', ['id' => $cm->course], 'id, fullname');

        // Get recruitment info — find which recruitment this course belongs to.
        $recruitment = self::get_recruitment_for_course($cm->course);

        // Extract questions and answers (stored separately, not sent to API).
        $questions = [];
        $slots = $attemptobj->get_slots();
        $correctcount = 0;
        $totalcount = 0;
        foreach ($slots as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            $mark = $qa->get_mark();
            $maxmark = $qa->get_max_mark();

            $totalcount++;
            if ($mark !== null && $maxmark > 0 && (float)$mark >= (float)$maxmark) {
                $correctcount++;
            }

            $questions[] = [
                'slot' => $slot,
                'question_text' => $qa->get_question_summary(),
                'user_response' => $qa->get_response_summary(),
                'correct_answer' => $qa->get_right_answer_summary(),
                'mark' => $mark,
                'max_mark' => $maxmark,
            ];
        }

        // Calculate score.
        $sumgrades = $attemptobj->get_sum_marks();
        $maxgrade = $quiz->sumgrades;
        $percentagescore = ($maxgrade > 0) ? round(($sumgrades / $maxgrade) * 100, 2) : 0;

        // Build summary JSON payload (sent to API — no per-question data).
        $payload = [
            'username' => $user->username,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'quiz_id' => (int)$quizid,
            'quiz_name' => $quiz->name,
            'course_id' => (int)$cm->course,
            'course_name' => $course ? $course->fullname : '',
            'recruitment_id' => $recruitment ? (int)$recruitment->id : null,
            'recruitment_name' => $recruitment ? $recruitment->name : null,
            'direction_id' => $recruitment ? (int)$recruitment->direction_id : null,
            'direction_name' => $recruitment ? $recruitment->direction_name : null,
            'attempt_id' => (int)$attemptid,
            'timestamp' => time(),
            'correct_answers' => $correctcount,
            'total_questions' => $totalcount,
            'percentage_score' => $percentagescore,
            'sum_grades' => $sumgrades,
            'max_grades' => $maxgrade,
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Build per-question JSON (stored in DB only, not sent to API).
        $questionsjson = json_encode($questions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Store in results table.
        $recordid = $DB->insert_record('quizaccess_inttest_results', (object)[
            'quizid' => $quizid,
            'attemptid' => $attemptid,
            'userid' => $userid,
            'jsondata' => $json,
            'questionsdata' => $questionsjson,
            'timecreated' => time(),
            'sent' => 0,
        ]);

        // Send summary JSON to Make.com webhook.
        $webhookurl = 'https://hook.eu1.make.com/mbap9pmyic7du9q1dcadqbet6wx4tmu6';
        $curl = new \curl();
        $curl->setHeader(['Content-Type: application/json']);
        $response = $curl->post($webhookurl, $json);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        if ($httpcode >= 200 && $httpcode < 300) {
            $DB->set_field('quizaccess_inttest_results', 'sent', 1, ['id' => $recordid]);
        } else {
            debugging('Webhook send failed for attempt ' . $attemptid .
                '. HTTP ' . $httpcode . ': ' . $response, DEBUG_DEVELOPER);
        }
    }

    /**
     * Find the recruitment and direction for a given course.
     *
     * The copied courses are linked to directions via cohort enrolment sync.
     * We find the cohort enrolled in this course, then match it to a direction.
     *
     * @param int $courseid
     * @return object|null Object with id, name, direction_id, direction_name or null.
     */
    private static function get_recruitment_for_course(int $courseid): ?object {
        global $DB;

        // Find cohort enrolment for this course → match to direction's cohortid.
        $sql = "SELECT rc.id AS direction_id, rc.name AS direction_name,
                       r.id, r.name
                  FROM {enrol} e
                  JOIN {local_recruitment_course} rc ON rc.cohortid = e.customint1
                  JOIN {local_recruitment} r ON r.id = rc.recruitmentid
                 WHERE e.courseid = :courseid
                   AND e.enrol = 'cohort'
                   AND e.status = 0
                 LIMIT 1";

        return $DB->get_record_sql($sql, ['courseid' => $courseid]) ?: null;
    }
}

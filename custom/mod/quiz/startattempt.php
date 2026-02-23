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
 * Start a new quiz attempt — custom override with training mode support.
 *
 * When training mode is enabled and numquestions is provided, the attempt
 * layout is modified to include only N randomly selected questions.
 *
 * @package   mod_quiz
 * @copyright 2009 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

// Get submitted parameters.
$id = required_param('cmid', PARAM_INT); // Course module id
$forcenew = optional_param('forcenew', false, PARAM_BOOL); // Used to force a new preview
$page = optional_param('page', -1, PARAM_INT); // Page to jump to in the attempt.
$numquestions = optional_param('numquestions', 0, PARAM_INT); // Training mode: number of questions.

$quizobj = quiz_settings::create_for_cmid($id, $USER->id);

// This script should only ever be posted to, so set page URL to the view page.
$PAGE->set_url($quizobj->view_url());
// During quiz attempts, the browser back/forwards buttons should force a reload.
$PAGE->set_cacheable(false);

// Check login and sesskey.
require_login($quizobj->get_course(), false, $quizobj->get_cm());
require_sesskey();
$PAGE->set_heading($quizobj->get_course()->fullname);

// Training mode: sync quiz slots with question bank entries.
// Adds new questions from the bank that don't have slots yet, so the quiz
// always reflects the current state of the question bank.
$quiz = $quizobj->get_quiz();
if (!empty($quiz->training) && $numquestions > 0) {
    $quizcontext = $quizobj->get_context();

    // Get all question bank entries from categories in this quiz context.
    $bankentries = $DB->get_records_sql(
        "SELECT qbe.id, qv.questionid
           FROM {question_bank_entries} qbe
           JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
           JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
                AND qv.version = (
                    SELECT MAX(qv2.version)
                      FROM {question_versions} qv2
                     WHERE qv2.questionbankentryid = qbe.id
                )
          WHERE qc.contextid = :contextid
       ORDER BY qbe.id",
        ['contextid' => $quizcontext->id]
    );

    // Find which bank entries already have a slot via question_references.
    $existingentryids = $DB->get_fieldset_sql(
        "SELECT qr.questionbankentryid
           FROM {question_references} qr
          WHERE qr.component = 'mod_quiz'
            AND qr.questionarea = 'slot'
            AND qr.usingcontextid = :contextid",
        ['contextid' => $quizcontext->id]
    );
    $existingmap = array_flip($existingentryids);

    // Filter to only new entries.
    $newentries = [];
    foreach ($bankentries as $entry) {
        if (!isset($existingmap[$entry->id])) {
            $newentries[] = $entry;
        }
    }

    if (!empty($newentries)) {
        // Get current max slot number.
        $maxslot = (int) $DB->get_field_sql(
            "SELECT COALESCE(MAX(slot), 0) FROM {quiz_slots} WHERE quizid = :quizid",
            ['quizid' => $quiz->id]
        );
        $maxpage = (int) $DB->get_field_sql(
            "SELECT COALESCE(MAX(page), 0) FROM {quiz_slots} WHERE quizid = :quizid",
            ['quizid' => $quiz->id]
        );

        $slot = $maxslot + 1;
        $page = $maxpage > 0 ? $maxpage : 0;
        $questionsperpage = !empty($quiz->questionsperpage) ? (int)$quiz->questionsperpage : 1;

        foreach ($newentries as $entry) {
            $slotrecord = new stdClass();
            $slotrecord->quizid = $quiz->id;
            $slotrecord->slot = $slot;
            $slotrecord->page = $page + 1;
            $slotrecord->requireprevious = 0;
            $slotrecord->maxmark = 1.0000000;
            $slotrecord->displaynumber = null;
            $slotid = $DB->insert_record('quiz_slots', $slotrecord);

            $ref = new stdClass();
            $ref->usingcontextid = $quizcontext->id;
            $ref->component = 'mod_quiz';
            $ref->questionarea = 'slot';
            $ref->itemid = $slotid;
            $ref->questionbankentryid = $entry->id;
            $ref->version = null; // Always use latest version.
            $DB->insert_record('question_references', $ref);

            $slot++;
            if (($slot - 1) % $questionsperpage === 0) {
                $page++;
            }
        }

        // Update sumgrades to match all slots.
        $newsumgrades = $DB->get_field_sql(
            "SELECT SUM(maxmark) FROM {quiz_slots} WHERE quizid = :quizid",
            ['quizid' => $quiz->id]
        );
        $DB->set_field('quiz', 'sumgrades', $newsumgrades, ['id' => $quiz->id]);

        // Reload quiz object so it picks up the new slots and sumgrades.
        $quizobj = quiz_settings::create_for_cmid($id, $USER->id);
        $quiz = $quizobj->get_quiz();
    }
}

// If no questions have been set up yet redirect to edit.php or display an error.
if (!$quizobj->has_questions()) {
    if ($quizobj->has_capability('mod/quiz:manage')) {
        redirect($quizobj->edit_url());
    } else {
        throw new \moodle_exception('cannotstartnoquestions', 'quiz', $quizobj->view_url());
    }
}

// Create an object to manage all the other (non-roles) access rules.
$timenow = time();
$accessmanager = $quizobj->get_access_manager($timenow);

// Validate permissions for creating a new attempt and start a new preview attempt if required.
list($currentattemptid, $attemptnumber, $lastattempt, $messages, $page) =
    quiz_validate_new_attempt($quizobj, $accessmanager, $forcenew, $page, true);

// Check access.
if (!$quizobj->is_preview_user() && $messages) {
    $output = $PAGE->get_renderer('mod_quiz');
    throw new \moodle_exception('attempterror', 'quiz', $quizobj->view_url(),
            $output->access_messages($messages));
}

if ($accessmanager->is_preflight_check_required($currentattemptid)) {
    // Need to do some checks before allowing the user to continue.
    $mform = $accessmanager->get_preflight_check_form(
            $quizobj->start_attempt_url($page), $currentattemptid);

    if ($mform->is_cancelled()) {
        $accessmanager->back_to_view_page($PAGE->get_renderer('mod_quiz'));

    } else if (!$mform->get_data()) {

        // Form not submitted successfully, re-display it and stop.
        $PAGE->set_url($quizobj->start_attempt_url($page));
        $PAGE->set_title($quizobj->get_quiz_name());
        $accessmanager->setup_attempt_page($PAGE);
        $output = $PAGE->get_renderer('mod_quiz');
        if (empty($quizobj->get_quiz()->showblocks)) {
            $PAGE->blocks->show_only_fake_blocks();
        }

        echo $output->start_attempt_page($quizobj, $mform);
        die();
    }

    // Pre-flight check passed.
    $accessmanager->notify_preflight_check_passed($currentattemptid);
}

if (!$currentattemptid || $lastattempt->state == quiz_attempt::NOT_STARTED) {
    $attempt = quiz_prepare_and_start_new_attempt($quizobj, $attemptnumber, $lastattempt);
} else {
    $attempt = $lastattempt;
}

// Training mode: limit the number of questions in the attempt layout.
$quiz = $quizobj->get_quiz();
if (!empty($quiz->training) && $numquestions > 0 && !empty($attempt->layout)) {
    // Parse layout: comma-separated slot numbers with 0 as page breaks.
    $layoutparts = explode(',', $attempt->layout);
    $allslots = [];
    foreach ($layoutparts as $part) {
        $part = (int) trim($part);
        if ($part > 0) {
            $allslots[] = $part;
        }
    }

    // Only limit if there are more questions than requested.
    if (count($allslots) > $numquestions) {
        // Shuffle and pick N random slots, then sort to maintain order.
        shuffle($allslots);
        $selectedslots = array_slice($allslots, 0, $numquestions);
        sort($selectedslots);

        // Rebuild layout with page breaks based on quiz's questionsperpage setting.
        $questionsperpage = !empty($quiz->questionsperpage) ? (int) $quiz->questionsperpage : 1;
        $newlayout = [];
        $count = 0;
        foreach ($selectedslots as $slot) {
            if ($count > 0 && $count % $questionsperpage === 0) {
                $newlayout[] = 0; // Page break.
            }
            $newlayout[] = $slot;
            $count++;
        }
        $newlayout[] = 0; // Final page break.

        $newlayoutstr = implode(',', $newlayout);
        $DB->set_field('quiz_attempts', 'layout', $newlayoutstr, ['id' => $attempt->id]);
        $attempt->layout = $newlayoutstr;
    }
}

if ($attempt->state === quiz_attempt::OVERDUE) {
    redirect($quizobj->summary_url($attempt->id));
} else {
    redirect($quizobj->attempt_url($attempt->id, $page));
}

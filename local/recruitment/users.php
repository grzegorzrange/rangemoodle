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
 * Users list for a direction.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

$did = required_param('did', PARAM_INT);
$setdeclaration = optional_param('setdeclaration', 0, PARAM_INT);

admin_externalpage_setup('local_recruitment');

$direction = $DB->get_record('local_recruitment_course', ['id' => $did], '*', MUST_EXIST);
$recruitment = $DB->get_record('local_recruitment', ['id' => $direction->recruitmentid], '*', MUST_EXIST);

$pageurl = new moodle_url('/local/recruitment/users.php', ['did' => $did]);

// Handle set declaration (irreversible: set to 1 + send notifications).
if ($setdeclaration && confirm_sesskey()) {
    $record = $DB->get_record('local_recruitment_user', ['id' => $setdeclaration, 'directionid' => $did], '*', MUST_EXIST);

    // Only process if declaration is currently 0.
    if (empty($record->declaration)) {
        $now = time();
        $record->declaration = 1;
        $record->timemodified = $now;
        $DB->update_record('local_recruitment_user', $record);

        // Send email + SMS notification if not yet notified.
        if (empty($record->notified)) {
            $user = $DB->get_record('user', ['id' => $record->userid], '*', MUST_EXIST);
            $noreplyuser = \core_user::get_noreply_user();

            $subject = get_string('examregistrationsubject', 'local_recruitment');
            $messagetext = get_string('examregistrationbody', 'local_recruitment', (object)[
                'direction' => $direction->name,
                'recruitment' => $recruitment->name,
            ]);
            $smstext = get_string('examregistrationsms', 'local_recruitment', (object)[
                'direction' => $direction->name,
                'recruitment' => $recruitment->name,
            ]);

            // Send Moodle message (email).
            $message = new \core\message\message();
            $message->component = 'local_recruitment';
            $message->name = 'exam_registration';
            $message->userfrom = $noreplyuser;
            $message->userto = $user;
            $message->subject = $subject;
            $message->fullmessage = $messagetext;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = nl2br(s($messagetext));
            $message->smallmessage = $subject;
            $message->notification = 1;

            try {
                message_send($message);
            } catch (\Exception $e) {
                debugging('Failed to send email to user ' . $user->id . ': ' . $e->getMessage(), DEBUG_NORMAL);
            }

            // Send SMS.
            if (class_exists('\local_support\sms_service') && !empty($user->phone1)) {
                \local_support\sms_service::send(
                    $user, $smstext, 'local_recruitment', 'exam_registration_sms', (int)$direction->id
                );
            }

            // Mark as notified.
            $DB->update_record('local_recruitment_user', (object)[
                'id' => $record->id,
                'notified' => 1,
                'timenotified' => $now,
            ]);
        }
    }

    redirect($pageurl, get_string('declarationset', 'local_recruitment'), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_url($pageurl);
$pagetitle = get_string('users', 'local_recruitment') . ': ' . format_string($direction->name);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

// Header buttons.
$importurl = new moodle_url('/local/recruitment/users_import.php', ['did' => $did]);
$exporturl = new moodle_url('/local/recruitment/users_export.php', ['did' => $did]);
$backurl = new moodle_url('/local/recruitment/courses.php', ['rid' => $direction->recruitmentid]);

echo html_writer::start_div('mb-3');
echo html_writer::link($importurl, get_string('importusers', 'local_recruitment'), [
    'class' => 'btn btn-primary mr-2',
]);
echo html_writer::link($exporturl, get_string('exportusers', 'local_recruitment'), [
    'class' => 'btn btn-outline-primary mr-2',
]);
echo html_writer::link($backurl, get_string('backtousers', 'local_recruitment'), [
    'class' => 'btn btn-secondary',
]);
echo html_writer::end_div();

// Display users table.
$table = new \local_recruitment\output\users_table('local-recruitment-users', $pageurl, $did);
$table->out(50, true);

echo $OUTPUT->footer();

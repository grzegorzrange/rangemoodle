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
 * Adhoc task: send declaration notification (email + SMS + WP sync).
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Sends notification to a single user after declaration is set.
 */
class send_declaration_notification extends \core\task\adhoc_task {

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/cohort/lib.php');

        $data = $this->get_custom_data();

        $record = $DB->get_record('local_recruitment_user', ['id' => $data->recordid]);
        if (!$record) {
            return;
        }

        // Skip if already notified.
        if (!empty($record->notified)) {
            return;
        }

        $user = $DB->get_record('user', ['id' => $record->userid]);
        if (!$user) {
            return;
        }

        $direction = $DB->get_record('local_recruitment_course', ['id' => $record->directionid]);
        if (!$direction) {
            return;
        }

        $recruitment = $DB->get_record('local_recruitment', ['id' => $direction->recruitmentid]);
        if (!$recruitment) {
            return;
        }

        $now = time();
        $noreplyuser = \core_user::get_noreply_user();

        $subject = get_string('examregistrationsubject', 'local_recruitment');
        $messagetext = get_string('examregistrationbody', 'local_recruitment', (object)[
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
            mtrace('Failed to send email to user ' . $user->id . ': ' . $e->getMessage());
        }

        // Send SMS.
        if (class_exists('\local_support\sms_service') && !empty($user->phone1)) {
            $smstext = get_string('examregistrationsms', 'local_recruitment', (object)[
                'direction' => $direction->name,
                'recruitment' => $recruitment->name,
            ]);
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

        // Send user data to WordPress.
        if (!empty($data->wp_sync) && class_exists('\local_support\wp_sync_service')) {
            \local_support\wp_sync_service::send($user, 'declaration_set');
        }
    }
}

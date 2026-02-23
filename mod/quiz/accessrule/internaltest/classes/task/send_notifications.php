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

namespace quizaccess_internaltest\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to send email notifications for internal tests.
 *
 * Sends 3 types of notifications:
 * - 7 days before the test opens
 * - When the test becomes available
 * - 24 hours before the test closes
 *
 * @package    quizaccess_internaltest
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_notifications extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('taskname', 'quizaccess_internaltest');
    }

    public function execute() {
        global $DB;

        $now = time();

        // Get all quizzes marked as internal test.
        $internaltests = $DB->get_records('quizaccess_internaltest', ['internaltest' => 1]);
        if (empty($internaltests)) {
            return;
        }

        foreach ($internaltests as $it) {
            $quiz = $DB->get_record('quiz', ['id' => $it->quizid]);
            if (!$quiz) {
                continue;
            }

            $cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course);
            if (!$cm) {
                continue;
            }

            // Parse availability dates from the course module.
            $dates = self::parse_availability_dates($cm->availability);
            if (!$dates['from'] && !$dates['until']) {
                continue;
            }

            $course = $DB->get_record('course', ['id' => $quiz->course]);
            if (!$course) {
                continue;
            }

            // Check each notification type.
            $notifications = [];

            // 7 days before open.
            if ($dates['from']) {
                $sevendays = $dates['from'] - (7 * DAYSECS);
                if ($now >= $sevendays && $now < $dates['from']) {
                    $notifications[] = '7days_before';
                }
            }

            // On open.
            if ($dates['from'] && $now >= $dates['from']) {
                $notifications[] = 'on_open';
            }

            // 24h before close.
            if ($dates['until']) {
                $onedaybefore = $dates['until'] - DAYSECS;
                if ($now >= $onedaybefore && $now < $dates['until']) {
                    $notifications[] = '24h_before_close';
                }
            }

            foreach ($notifications as $notiftype) {
                // Check if already sent.
                if ($DB->record_exists('quizaccess_inttest_notif', [
                    'quizid' => $quiz->id,
                    'notiftype' => $notiftype,
                ])) {
                    continue;
                }

                // Send to all enrolled users.
                $context = \context_course::instance($course->id);
                $users = get_enrolled_users($context, '', 0, 'u.*', null, 0, 0, true);

                foreach ($users as $user) {
                    self::send_notification($user, $quiz, $course, $cm, $notiftype, $dates);
                    self::send_sms_notification($user, $quiz, $course, $notiftype, $dates);
                }

                // Record that notification was sent.
                $DB->insert_record('quizaccess_inttest_notif', (object)[
                    'quizid' => $quiz->id,
                    'notiftype' => $notiftype,
                    'timesent' => $now,
                ]);

                mtrace("  Internal test notification '{$notiftype}' sent for quiz {$quiz->id} ({$quiz->name})");
            }
        }
    }

    /**
     * Parse availability JSON to extract date conditions.
     *
     * @param string|null $availability The availability JSON string.
     * @return array ['from' => int|null, 'until' => int|null]
     */
    private static function parse_availability_dates(?string $availability): array {
        $dates = ['from' => null, 'until' => null];
        if (empty($availability)) {
            return $dates;
        }

        $data = json_decode($availability);
        if (!$data) {
            return $dates;
        }

        self::walk_availability_tree($data, $dates);
        return $dates;
    }

    /**
     * Recursively walk the availability tree to find date conditions.
     *
     * @param object $node
     * @param array &$dates
     */
    private static function walk_availability_tree($node, array &$dates): void {
        // Check if this node is a date condition.
        if (isset($node->type) && $node->type === 'date') {
            if (isset($node->d) && isset($node->t)) {
                if ($node->d === '>=') {
                    $dates['from'] = (int)$node->t;
                } else if ($node->d === '<') {
                    $dates['until'] = (int)$node->t;
                }
            }
            return;
        }

        // If it has children, recurse into them.
        if (isset($node->c) && is_array($node->c)) {
            foreach ($node->c as $child) {
                self::walk_availability_tree($child, $dates);
            }
        }
    }

    /**
     * Send a notification to a user.
     *
     * @param object $user
     * @param object $quiz
     * @param object $course
     * @param object $cm
     * @param string $notiftype
     * @param array $dates
     */
    private static function send_notification($user, $quiz, $course, $cm, string $notiftype, array $dates): void {
        $a = new \stdClass();
        $a->quizname = format_string($quiz->name);
        $a->coursename = format_string($course->fullname);
        $a->url = (new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]))->out(false);

        switch ($notiftype) {
            case '7days_before':
                $a->opendate = userdate($dates['from']);
                $subject = get_string('notification_subject_7days', 'quizaccess_internaltest', $a);
                $body = get_string('notification_body_7days', 'quizaccess_internaltest', $a);
                break;
            case 'on_open':
                $subject = get_string('notification_subject_open', 'quizaccess_internaltest', $a);
                $body = get_string('notification_body_open', 'quizaccess_internaltest', $a);
                break;
            case '24h_before_close':
                $a->closedate = userdate($dates['until']);
                $subject = get_string('notification_subject_closing', 'quizaccess_internaltest', $a);
                $body = get_string('notification_body_closing', 'quizaccess_internaltest', $a);
                break;
            default:
                return;
        }

        $message = new \core\message\message();
        $message->component = 'quizaccess_internaltest';
        $message->name = 'internaltest_reminder';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '<p>' . nl2br(s($body)) . '</p>';
        $message->smallmessage = $subject;
        $message->notification = 1;
        $message->contexturl = $a->url;
        $message->contexturlname = $a->quizname;

        $result = message_send($message);
        if (!$result) {
            debugging("Failed to send internal test notification to user {$user->id} ({$user->email}) for quiz {$quiz->id}", DEBUG_NORMAL);
        }
    }

    /**
     * Send an SMS notification to a user about an internal test.
     *
     * @param object $user
     * @param object $quiz
     * @param object $course
     * @param string $notiftype
     * @param array $dates
     */
    private static function send_sms_notification($user, $quiz, $course, string $notiftype, array $dates): void {
        $quizname = format_string($quiz->name);
        $coursename = format_string($course->fullname);

        switch ($notiftype) {
            case '7days_before':
                $opendate = userdate($dates['from'], get_string('strftimedaydatetime', 'langconfig'));
                $text = get_string('sms_7days', 'quizaccess_internaltest', (object)[
                    'quizname' => $quizname,
                    'coursename' => $coursename,
                    'opendate' => $opendate,
                ]);
                break;
            case 'on_open':
                $text = get_string('sms_open', 'quizaccess_internaltest', (object)[
                    'quizname' => $quizname,
                    'coursename' => $coursename,
                ]);
                break;
            case '24h_before_close':
                $closedate = userdate($dates['until'], get_string('strftimedaydatetime', 'langconfig'));
                $text = get_string('sms_closing', 'quizaccess_internaltest', (object)[
                    'quizname' => $quizname,
                    'coursename' => $coursename,
                    'closedate' => $closedate,
                ]);
                break;
            default:
                return;
        }

        \local_support\sms_service::send($user, $text, 'quizaccess_internaltest', 'sms_' . $notiftype, (int)$quiz->id);
    }
}

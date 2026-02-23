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
 * Universal SMS sending service via SerwerSMS.pl API.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_support;

defined('MOODLE_INTERNAL') || die();

/**
 * SMS service that sends messages via SerwerSMS.pl.
 *
 * Usage:
 *   \local_support\sms_service::send($user, 'Your message text here');
 *   \local_support\sms_service::send($user, 'Message', 'quiz', 'internaltest_reminder', $cmid);
 */
class sms_service {

    /** @var string API endpoint. */
    private const API_URL = 'https://api2.serwersms.pl/messages/send_sms.json';

    /**
     * Send an SMS to a user.
     *
     * Checks if the user has a phone number. If not, silently returns false.
     * Logs the result in logstore_standard_log and local_sms_history.
     *
     * @param object $user Moodle user object (must have id, phone1, firstname, lastname).
     * @param string $text SMS message content.
     * @param string $component Component triggering the SMS (for logging), e.g. 'quizaccess_internaltest'.
     * @param string $action Action name (for logging), e.g. 'sms_sent'.
     * @param int $relatedid Optional related object ID (for logging), e.g. quiz cm id.
     * @return bool True if SMS was sent successfully, false otherwise.
     */
    public static function send($user, string $text, string $component = 'local_support',
            string $action = 'sms_sent', int $relatedid = 0): bool {
        global $DB;

        // Check phone number.
        $phone = self::normalize_phone($user->phone1 ?? '');
        if (empty($phone)) {
            return false;
        }

        // Get API credentials from settings.
        $token = get_config('local_support', 'sms_api_token');
        $sender = get_config('local_support', 'sms_sender');

        if (empty($token)) {
            debugging('SMS service: API token not configured in local_support settings.', DEBUG_NORMAL);
            return false;
        }

        if (empty($sender)) {
            $sender = 'INFO';
        }

        // Build POST data - phone must be sent as phone[] array.
        $postdata = [
            'phone[]' => $phone,
            'text' => $text,
            'sender' => $sender,
            'details' => 'true',
        ];

        // Send via cURL.
        $curl = new \curl();
        $curl->setHeader([
            'Authorization: Bearer ' . $token,
        ]);
        $response = $curl->post(self::API_URL, $postdata);
        $httpcode = $curl->get_info()['http_code'] ?? 0;

        $result = json_decode($response);
        $success = ($httpcode >= 200 && $httpcode < 300 && !empty($result->success));

        // Store in SMS history table.
        $DB->insert_record('local_sms_history', (object)[
            'userid' => $user->id,
            'phone' => $phone,
            'message' => $text,
            'component' => $component,
            'success' => $success ? 1 : 0,
            'response' => mb_substr($response, 0, 1000),
            'timecreated' => time(),
        ]);

        // Log in logstore_standard_log.
        $eventdata = [
            'context' => \context_system::instance(),
            'relateduserid' => $user->id,
            'other' => [
                'phone' => $phone,
                'component' => $component,
                'success' => $success,
                'message' => mb_substr($text, 0, 200),
            ],
        ];
        if ($relatedid) {
            $eventdata['objectid'] = $relatedid;
        }
        $event = \local_support\event\sms_sent::create($eventdata);
        $event->trigger();

        if (!$success) {
            debugging('SMS send failed for user ' . $user->id . ' (' . $phone . '). ' .
                'HTTP ' . $httpcode . ': ' . $response, DEBUG_NORMAL);
        }

        return $success;
    }

    /**
     * Normalize a phone number to international format.
     *
     * @param string $phone Raw phone number.
     * @return string Normalized phone number or empty if invalid.
     */
    private static function normalize_phone(string $phone): string {
        // Strip all non-digit characters except leading +.
        $phone = trim($phone);
        if (empty($phone)) {
            return '';
        }

        $hasplus = (strpos($phone, '+') === 0);
        $digits = preg_replace('/[^0-9]/', '', $phone);

        if (empty($digits)) {
            return '';
        }

        // If already has international prefix.
        if ($hasplus) {
            return '+' . $digits;
        }

        // Polish numbers: 9 digits without prefix -> add +48.
        if (strlen($digits) === 9) {
            return '+48' . $digits;
        }

        // Already has country code (e.g. 48500600700).
        if (strlen($digits) > 9) {
            return '+' . $digits;
        }

        return '';
    }
}

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
 * WordPress sync service — sends user data to WordPress via REST API.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_support;

defined('MOODLE_INTERNAL') || die();

/**
 * Service for sending user data to WordPress when declaration flag changes.
 */
class wp_sync_service {

    /**
     * Send user data to WordPress.
     *
     * @param object $user Moodle user object (must have id, username, firstname, lastname, email).
     * @param string $action Action description for logging (e.g. 'declaration_set', 'declaration_unset').
     * @return bool True if sent successfully.
     */
    public static function send($user, string $action = 'declaration_changed'): bool {
        $endpoint = get_config('local_support', 'wp_sync_endpoint');
        $secret = get_config('local_support', 'wp_sync_token');

        if (empty($endpoint) || empty($secret)) {
            debugging('WordPress sync: endpoint or token not configured in local_support settings.', DEBUG_NORMAL);
            return false;
        }

        // Build email — if user has no email, generate a placeholder.
        $email = $user->email;
        if (empty($email)) {
            $email = 'user_email' . $user->id . '@exampleusermail.com';
        }

        $data = [
            'username'  => $user->username,
            'firstname' => $user->firstname,
            'lastname'  => $user->lastname,
            'email'     => $email,
            'moodle_id' => $user->id
        ];

        $body = json_encode($data, JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $body, $secret);

        $curl = new \curl();

        $curl->setHeader([
            'Content-Type: application/json',
            'X-Signature: ' . $signature,
        ]);

        $options = [
            'CURLOPT_CONNECTTIMEOUT' => 5,   // max czas na nawiązanie połączenia
            'CURLOPT_TIMEOUT' => 10,         // max czas całego requestu
            'CURLOPT_RETURNTRANSFER' => true
        ];

        $response = $curl->post($endpoint, $body, $options);
        $info = $curl->get_info();
        $httpcode = $info['http_code'] ?? 0;

        $success = ($httpcode >= 200 && $httpcode < 300);

        // Log in logstore_standard_log.
        $event = \local_support\event\wp_sync_sent::create([
            'context' => \context_system::instance(),
            'relateduserid' => $user->id,
            'other' => [
                'action' => $action,
                'success' => $success,
                'httpcode' => $httpcode,
                'endpoint' => $endpoint,
                'payload' => $body,
                'response' => mb_substr($response, 0, 500),
            ],
        ]);
        $event->trigger();

        if (!$success) {
            debugging('WordPress sync failed for user ' . $user->id .
                '. HTTP ' . $httpcode . ': ' . $response, DEBUG_NORMAL);
        }

        return $success;
    }
}

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
 * English strings for local_support.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Custom File Support';
$string['fallbackemail'] = 'Fallback email for password reset';
$string['fallbackemail_desc'] = 'If a user enters an email address that does not exist in the system during password reset, the reset email will be sent to this address instead. Leave empty to disable.';
$string['peselnotfound'] = 'User with this PESEL number does not exist.';
$string['fallbackemailsubject'] = 'Password reset attempt for unknown email: {$a}';
$string['fallbackemailbody'] = 'Someone tried to reset a password using email address: {$a}, but this email does not exist in the system.';
$string['fallbackemailbody_html'] = '<p>Someone tried to reset a password using email address: <strong>{$a}</strong>, but this email does not exist in the system.</p>';
$string['emailpasswordconfirmmaybesent'] = 'If there is an account associated with this information, an email with instructions has been sent.';
$string['blockedurls'] = 'Blocked URLs for non-admin users';
$string['blockedurls_desc'] = 'Enter one URL path per line. Non-admin users visiting pages matching these paths will be redirected to /my/. Use partial paths, e.g. /grade/report/overview/index.php';
$string['sms_heading'] = 'SMS settings (SerwerSMS.pl)';
$string['sms_heading_desc'] = 'Configuration for the SMS sending service via SerwerSMS.pl API.';
$string['sms_api_token'] = 'API Token';
$string['sms_api_token_desc'] = 'Bearer API token from SerwerSMS.pl (Client Panel > Interface Settings > HTTPS API > API Tokens).';
$string['sms_sender'] = 'SMS Sender name';
$string['sms_sender_desc'] = 'Sender name displayed on the SMS (must be registered in SerwerSMS.pl).';
$string['event_sms_sent'] = 'SMS sent';
$string['internaltest_notdone'] = 'Not done';
$string['internaltest_passed'] = 'Passed';
$string['internaltest_failed'] = 'Failed';
$string['internaltest_active'] = 'Active';
$string['internaltest_inactive'] = 'Inactive';

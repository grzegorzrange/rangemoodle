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
 * Admin settings for local_support.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_support', get_string('pluginname', 'local_support'));

    $settings->add(new admin_setting_configtext(
        'local_support/fallbackemail',
        get_string('fallbackemail', 'local_support'),
        get_string('fallbackemail_desc', 'local_support'),
        '',
        PARAM_EMAIL
    ));

    // SMS settings (SerwerSMS.pl).
    $settings->add(new admin_setting_heading(
        'local_support/sms_heading',
        get_string('sms_heading', 'local_support'),
        get_string('sms_heading_desc', 'local_support')
    ));

    $settings->add(new admin_setting_configtext(
        'local_support/sms_api_token',
        get_string('sms_api_token', 'local_support'),
        get_string('sms_api_token_desc', 'local_support'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_support/sms_sender',
        get_string('sms_sender', 'local_support'),
        get_string('sms_sender_desc', 'local_support'),
        'INFO',
        PARAM_ALPHANUMEXT
    ));

    $ADMIN->add('localplugins', $settings);
}

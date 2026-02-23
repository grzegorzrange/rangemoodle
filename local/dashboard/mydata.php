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
 * My data view page.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/dashboard/mydata.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mydata', 'local_dashboard'));
$PAGE->set_heading(get_string('mydata', 'local_dashboard'));

$user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('mydata', 'local_dashboard'));

$editurl = new moodle_url('/local/dashboard/mydata_edit.php');

$templatedata = [
    'firstname' => s($user->firstname),
    'lastname' => s($user->lastname),
    'email' => s($user->email),
    'phone' => s($user->phone1),
    'pesel' => s($user->username),
    'editurl' => $editurl->out(false),
    'str_firstname' => get_string('firstname'),
    'str_lastname' => get_string('lastname'),
    'str_email' => get_string('email'),
    'str_phone' => get_string('phone', 'local_dashboard'),
    'str_pesel' => get_string('pesel', 'local_dashboard'),
    'str_edit' => get_string('editmydata', 'local_dashboard'),
];
echo $OUTPUT->render_from_template('local_dashboard/mydata', $templatedata);

echo $OUTPUT->footer();

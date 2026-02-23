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
 * Edit my data page.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$pageurl = new moodle_url('/local/dashboard/mydata_edit.php');
$returnurl = new moodle_url('/local/dashboard/mydata.php');
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('editmydata', 'local_dashboard'));
$PAGE->set_heading(get_string('editmydata', 'local_dashboard'));

$user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);

$form = new \local_dashboard\form\mydata_form($pageurl);
$form->set_data(['email' => $user->email, 'phone1' => $user->phone1]);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    $updateuser = new stdClass();
    $updateuser->id = $USER->id;
    $updateuser->email = trim($data->email);
    $updateuser->phone1 = trim($data->phone1);
    $updateuser->timemodified = time();

    $DB->update_record('user', $updateuser);

    // Update session.
    $USER->email = $updateuser->email;
    $USER->phone1 = $updateuser->phone1;

    \core\notification::success(get_string('changessaved'));
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editmydata', 'local_dashboard'));
$form->display();
echo $OUTPUT->footer();

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
 * CSV import page for users in a direction.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$did = required_param('did', PARAM_INT);

admin_externalpage_setup('local_recruitment');

$direction = $DB->get_record('local_recruitment_course', ['id' => $did], '*', MUST_EXIST);
$recruitment = $DB->get_record('local_recruitment', ['id' => $direction->recruitmentid], '*', MUST_EXIST);

$pageurl = new moodle_url('/local/recruitment/users_import.php', ['did' => $did]);
$returnurl = new moodle_url('/local/recruitment/users.php', ['did' => $did]);

$PAGE->set_url($pageurl);
$pagetitle = get_string('importusers', 'local_recruitment') . ': ' . format_string($direction->name);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$form = new \local_recruitment\form\import_users_form($pageurl, null, 'post');
$form->set_data(['did' => $did]);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    // Get the uploaded file content.
    $content = $form->get_file_content('csvfile');

    if ($content === false || empty(trim($content))) {
        redirect($returnurl, get_string('error'), null, \core\output\notification::NOTIFY_ERROR);
    }

    $result = \local_recruitment\recruitment::import_users_csv($did, $content);

    $a = new stdClass();
    $a->created = $result['created'];
    $a->updated = $result['updated'];
    $a->errors = count($result['errors']);
    $a->notified = $result['notified'];

    $message = get_string('userimported', 'local_recruitment', $a);

    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $error) {
            $message .= '<br>' . s($error);
        }
    }

    redirect($returnurl, $message, null,
        empty($result['errors']) ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);

// Download sample CSV link.
$sampleurl = new moodle_url('/local/recruitment/sample_csv.php');
echo html_writer::div(
    html_writer::link($sampleurl, get_string('downloadcsvsample', 'local_recruitment'), [
        'class' => 'btn btn-outline-secondary mb-3',
    ])
);

$form->display();

echo html_writer::div(
    html_writer::link($returnurl, get_string('backtousers', 'local_recruitment'), [
        'class' => 'btn btn-secondary mt-3',
    ])
);

echo $OUTPUT->footer();

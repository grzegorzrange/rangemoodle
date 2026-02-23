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
 * Create/edit schedule page.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_schedule');

$context = context_system::instance();
$pageurl = new moodle_url('/local/schedule/edit.php', $id ? ['id' => $id] : []);
$returnurl = new moodle_url('/local/schedule/index.php');

$PAGE->set_url($pageurl);

if ($id) {
    $record = $DB->get_record('local_schedule', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editschedule', 'local_schedule'));
    $PAGE->set_heading(get_string('editschedule', 'local_schedule'));
} else {
    $record = new stdClass();
    $record->id = 0;
    $PAGE->set_title(get_string('addschedule', 'local_schedule'));
    $PAGE->set_heading(get_string('addschedule', 'local_schedule'));
}

// Prepare editor data.
$editoroptions = \local_schedule\schedule::editor_options($context);

if ($id) {
    $record = file_prepare_standard_editor(
        $record, 'message', $editoroptions, $context,
        'local_schedule', 'schedule', $record->id
    );
}

$form = new \local_schedule\form\schedule_form($pageurl, ['context' => $context]);
$form->set_data($record);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if ($data->id) {
        \local_schedule\schedule::update($data, $context);

        // Log edit event.
        $event = \local_schedule\event\schedule_updated::create([
            'context' => $context,
            'objectid' => $data->id,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        // Send notification if checkbox was checked.
        if (!empty($data->sendnotification)) {
            \local_schedule\schedule::send_notification($data->id);
        }

        \core\notification::success(get_string('schedulesaved', 'local_schedule'));
    } else {
        $newid = \local_schedule\schedule::create($data, $context);

        // Log create event.
        $event = \local_schedule\event\schedule_created::create([
            'context' => $context,
            'objectid' => $newid,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        // Send notification if checkbox was checked.
        if (!empty($data->sendnotification)) {
            \local_schedule\schedule::send_notification($newid);
        }

        \core\notification::success(get_string('schedulesaved', 'local_schedule'));
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editschedule', 'local_schedule') : get_string('addschedule', 'local_schedule'));
$form->display();
echo $OUTPUT->footer();

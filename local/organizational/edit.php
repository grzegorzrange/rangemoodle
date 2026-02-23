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
 * Create/edit organizational matter page.
 *
 * @package    local_organizational
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_organizational');

$context = context_system::instance();
$pageurl = new moodle_url('/local/organizational/edit.php', $id ? ['id' => $id] : []);
$returnurl = new moodle_url('/local/organizational/index.php');

$PAGE->set_url($pageurl);

if ($id) {
    $record = $DB->get_record('local_organizational', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editorganizational', 'local_organizational'));
    $PAGE->set_heading(get_string('editorganizational', 'local_organizational'));
} else {
    $record = new stdClass();
    $record->id = 0;
    $PAGE->set_title(get_string('addorganizational', 'local_organizational'));
    $PAGE->set_heading(get_string('addorganizational', 'local_organizational'));
}

$editoroptions = \local_organizational\organizational::editor_options($context);

if ($id) {
    $record = file_prepare_standard_editor(
        $record, 'message', $editoroptions, $context,
        'local_organizational', 'organizational', $record->id
    );
}

$form = new \local_organizational\form\organizational_form($pageurl, ['context' => $context]);
$form->set_data($record);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if ($data->id) {
        \local_organizational\organizational::update($data, $context);

        $event = \local_organizational\event\organizational_updated::create([
            'context' => $context,
            'objectid' => $data->id,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        if (!empty($data->sendnotification)) {
            \local_organizational\organizational::send_notification($data->id);
        }

        \core\notification::success(get_string('organizationalsaved', 'local_organizational'));
    } else {
        $newid = \local_organizational\organizational::create($data, $context);

        $event = \local_organizational\event\organizational_created::create([
            'context' => $context,
            'objectid' => $newid,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        if (!empty($data->sendnotification)) {
            \local_organizational\organizational::send_notification($newid);
        }

        \core\notification::success(get_string('organizationalsaved', 'local_organizational'));
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editorganizational', 'local_organizational') : get_string('addorganizational', 'local_organizational'));
$form->display();
echo $OUTPUT->footer();

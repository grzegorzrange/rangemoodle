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
 * Create/edit financial matter page.
 *
 * @package    local_financial
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_financial');

$context = context_system::instance();
$pageurl = new moodle_url('/local/financial/edit.php', $id ? ['id' => $id] : []);
$returnurl = new moodle_url('/local/financial/index.php');

$PAGE->set_url($pageurl);

if ($id) {
    $record = $DB->get_record('local_financial', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editfinancial', 'local_financial'));
    $PAGE->set_heading(get_string('editfinancial', 'local_financial'));
} else {
    $record = new stdClass();
    $record->id = 0;
    $PAGE->set_title(get_string('addfinancial', 'local_financial'));
    $PAGE->set_heading(get_string('addfinancial', 'local_financial'));
}

$editoroptions = \local_financial\financial::editor_options($context);

if ($id) {
    $record = file_prepare_standard_editor(
        $record, 'message', $editoroptions, $context,
        'local_financial', 'financial', $record->id
    );
}

$form = new \local_financial\form\financial_form($pageurl, ['context' => $context]);
$form->set_data($record);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if ($data->id) {
        \local_financial\financial::update($data, $context);

        $event = \local_financial\event\financial_updated::create([
            'context' => $context,
            'objectid' => $data->id,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        if (!empty($data->sendnotification)) {
            \local_financial\financial::send_notification($data->id);
        }

        \core\notification::success(get_string('financialsaved', 'local_financial'));
    } else {
        $newid = \local_financial\financial::create($data, $context);

        $event = \local_financial\event\financial_created::create([
            'context' => $context,
            'objectid' => $newid,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        if (!empty($data->sendnotification)) {
            \local_financial\financial::send_notification($newid);
        }

        \core\notification::success(get_string('financialsaved', 'local_financial'));
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editfinancial', 'local_financial') : get_string('addfinancial', 'local_financial'));
$form->display();
echo $OUTPUT->footer();

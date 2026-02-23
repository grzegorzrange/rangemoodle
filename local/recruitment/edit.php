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
 * Create/edit recruitment page.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_recruitment');

$pageurl = new moodle_url('/local/recruitment/edit.php', $id ? ['id' => $id] : []);
$returnurl = new moodle_url('/local/recruitment/index.php');

$PAGE->set_url($pageurl);

if ($id) {
    $record = $DB->get_record('local_recruitment', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editrecruitment', 'local_recruitment'));
    $PAGE->set_heading(get_string('editrecruitment', 'local_recruitment'));
} else {
    $record = new stdClass();
    $record->id = 0;
    $PAGE->set_title(get_string('addrecruitment', 'local_recruitment'));
    $PAGE->set_heading(get_string('addrecruitment', 'local_recruitment'));
}

$form = new \local_recruitment\form\recruitment_form($pageurl);
$form->set_data($record);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if ($data->id) {
        \local_recruitment\recruitment::update($data);

        $event = \local_recruitment\event\recruitment_updated::create([
            'context' => context_system::instance(),
            'objectid' => $data->id,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        \core\notification::success(get_string('recruitmentsaved', 'local_recruitment'));
    } else {
        $newid = \local_recruitment\recruitment::create($data);

        $event = \local_recruitment\event\recruitment_created::create([
            'context' => context_system::instance(),
            'objectid' => $newid,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        \core\notification::success(get_string('recruitmentsaved', 'local_recruitment'));
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editrecruitment', 'local_recruitment') : get_string('addrecruitment', 'local_recruitment'));
$form->display();
echo $OUTPUT->footer();

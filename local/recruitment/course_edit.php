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
 * Create/edit direction (kurs/kierunek) within a recruitment.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);
$rid = optional_param('rid', 0, PARAM_INT);

admin_externalpage_setup('local_recruitment');

if ($id) {
    $record = $DB->get_record('local_recruitment_course', ['id' => $id], '*', MUST_EXIST);
    $rid = $record->recruitmentid;
    $recruitment = $DB->get_record('local_recruitment', ['id' => $rid], '*', MUST_EXIST);
    $pagetitle = get_string('editdirection', 'local_recruitment');
} else {
    if (!$rid) {
        throw new moodle_exception('missingparam', '', '', 'rid');
    }
    $recruitment = $DB->get_record('local_recruitment', ['id' => $rid], '*', MUST_EXIST);
    $record = new stdClass();
    $record->id = 0;
    $record->recruitmentid = $rid;
    $pagetitle = get_string('adddirection', 'local_recruitment');
}

$returnurl = new moodle_url('/local/recruitment/courses.php', ['rid' => $rid]);
$pageurl = new moodle_url('/local/recruitment/course_edit.php', $id ? ['id' => $id] : ['rid' => $rid]);

$PAGE->set_url($pageurl);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$form = new \local_recruitment\form\direction_form($pageurl, ['id' => $id]);
$form->set_data($record);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if ($data->id) {
        \local_recruitment\recruitment::update_direction($data);

        $event = \local_recruitment\event\recruitment_updated::create([
            'context' => context_system::instance(),
            'objectid' => $data->id,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        \core\notification::success(get_string('directionsaved', 'local_recruitment'));
    } else {
        $newid = \local_recruitment\recruitment::create_direction($data);

        $event = \local_recruitment\event\recruitment_created::create([
            'context' => context_system::instance(),
            'objectid' => $newid,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        \core\notification::success(get_string('directionsaved', 'local_recruitment'));
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle . ' (' . format_string($recruitment->name) . ')');
$form->display();
echo $OUTPUT->footer();

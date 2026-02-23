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
 * Delete direction (kurs/kierunek) page.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('local_recruitment');

$record = $DB->get_record('local_recruitment_course', ['id' => $id], '*', MUST_EXIST);
$returnurl = new moodle_url('/local/recruitment/courses.php', ['rid' => $record->recruitmentid]);

if ($confirm && confirm_sesskey()) {
    $event = \local_recruitment\event\recruitment_deleted::create([
        'context' => context_system::instance(),
        'objectid' => $id,
        'other' => ['name' => $record->name],
    ]);
    $event->trigger();

    \local_recruitment\recruitment::delete_direction($id);
    \core\notification::success(get_string('directiondeleted', 'local_recruitment'));
    redirect($returnurl);
}

$PAGE->set_url(new moodle_url('/local/recruitment/course_delete.php', ['id' => $id]));
$PAGE->set_title(get_string('deletedirection', 'local_recruitment'));
$PAGE->set_heading(get_string('deletedirection', 'local_recruitment'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletedirection', 'local_recruitment'));

$confirmurl = new moodle_url('/local/recruitment/course_delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm(
    get_string('confirmdeletedirection', 'local_recruitment', $record->name),
    $confirmurl,
    $returnurl
);

echo $OUTPUT->footer();

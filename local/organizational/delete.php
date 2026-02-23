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
 * Delete organizational matter page.
 *
 * @package    local_organizational
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('local_organizational');

$context = context_system::instance();
$returnurl = new moodle_url('/local/organizational/index.php');
$record = $DB->get_record('local_organizational', ['id' => $id], '*', MUST_EXIST);

if ($confirm && confirm_sesskey()) {
    $event = \local_organizational\event\organizational_deleted::create([
        'context' => $context,
        'objectid' => $id,
        'other' => ['name' => $record->name],
    ]);
    $event->trigger();

    \local_organizational\organizational::delete($id, $context);
    \core\notification::success(get_string('organizationaldeleted', 'local_organizational'));
    redirect($returnurl);
}

$PAGE->set_url(new moodle_url('/local/organizational/delete.php', ['id' => $id]));
$PAGE->set_title(get_string('deleteorganizational', 'local_organizational'));
$PAGE->set_heading(get_string('deleteorganizational', 'local_organizational'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteorganizational', 'local_organizational'));

$confirmurl = new moodle_url('/local/organizational/delete.php', [
    'id' => $id,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo $OUTPUT->confirm(
    get_string('confirmdeleteorganizational', 'local_organizational', $record->name),
    $confirmurl,
    $returnurl
);

echo $OUTPUT->footer();

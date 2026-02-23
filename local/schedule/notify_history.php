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
 * Notification history for a schedule.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

$id = required_param('id', PARAM_INT);

admin_externalpage_setup('local_schedule');

$schedule = $DB->get_record('local_schedule', ['id' => $id], '*', MUST_EXIST);

$pageurl = new moodle_url('/local/schedule/notify_history.php', ['id' => $id]);
$returnurl = new moodle_url('/local/schedule/index.php');

$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('notifyhistory', 'local_schedule'));
$PAGE->set_heading(get_string('notifyhistoryfor', 'local_schedule', $schedule->name));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('notifyhistoryfor', 'local_schedule', $schedule->name));

// Build table.
$table = new flexible_table('local-schedule-notify-history');
$table->define_columns(['timecreated', 'sentby', 'recipientcount']);
$table->define_headers([
    get_string('date'),
    get_string('sentby', 'local_schedule'),
    get_string('recipientcount', 'local_schedule'),
]);
$table->define_baseurl($pageurl);
$table->sortable(false);
$table->setup();

$records = $DB->get_records('local_schedule_notify_hist', ['scheduleid' => $id], 'timecreated DESC');

foreach ($records as $rec) {
    $sender = $DB->get_record('user', ['id' => $rec->usercreated]);
    $sendername = $sender ? fullname($sender) : '-';

    $table->add_data([
        userdate($rec->timecreated, get_string('strftimedatetimeshort', 'langconfig')),
        $sendername,
        $rec->recipientcount,
    ]);
}

$table->finish_output();

echo html_writer::div(
    html_writer::link($returnurl, get_string('back'), ['class' => 'btn btn-secondary']),
    'mt-3'
);

echo $OUTPUT->footer();

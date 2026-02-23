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
 * Schedule admin list page.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_schedule');

$pageurl = new moodle_url('/local/schedule/index.php');

$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('pluginname', 'local_schedule'));
$PAGE->set_heading(get_string('pluginname', 'local_schedule'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('schedules', 'local_schedule'));

// Add new schedule button.
$addurl = new moodle_url('/local/schedule/edit.php');
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addschedule', 'local_schedule'), 'get'),
    'mb-3'
);

// Display schedule table.
$table = new \local_schedule\output\schedule_table('local-schedule-list', $pageurl);
$table->out(50, true);

echo $OUTPUT->footer();

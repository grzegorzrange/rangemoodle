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
 * View schedule for the active direction.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/schedule/view.php', $id ? ['id' => $id] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('schedule', 'local_schedule'));
$PAGE->set_heading(get_string('schedule', 'local_schedule'));

$isadmin = is_siteadmin();

if ($id && $isadmin) {
    // Admin viewing a specific record by ID.
    $schedule = $DB->get_record('local_schedule', ['id' => $id], '*', MUST_EXIST);
} else {
    // Regular user or admin without ID — use session direction.
    $directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;

    if (!$isadmin) {
        if (!$directionid) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('viewschedule', 'local_schedule'));
        }
        if (!\local_schedule\schedule::user_has_access($directionid, $USER->id)) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('viewschedule', 'local_schedule'));
        }
    }

    if (!$directionid) {
        redirect(new moodle_url('/local/dashboard/index.php', ['change' => 1]));
    }

    $schedule = \local_schedule\schedule::get_for_direction($directionid);
}

echo $OUTPUT->header();

if (!$schedule) {
    echo $OUTPUT->notification(get_string('noschedule', 'local_schedule'), 'info');
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->heading(format_string($schedule->name));

// Rewrite pluginfile URLs for the message.
$message = file_rewrite_pluginfile_urls(
    $schedule->message,
    'pluginfile.php',
    $context->id,
    'local_schedule',
    'schedule',
    $schedule->id
);
echo format_text($message, $schedule->messageformat, ['context' => $context]);

echo $OUTPUT->footer();

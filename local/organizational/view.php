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
 * View organizational matters for the active direction.
 *
 * @package    local_organizational
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/organizational/view.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('organizationalmatters', 'local_organizational'));
$PAGE->set_heading(get_string('organizationalmatters', 'local_organizational'));

$isadmin = is_siteadmin();
$directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;

if (!$isadmin) {
    if (!$directionid) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('vieworganizational', 'local_organizational'));
    }
    if (!\local_organizational\organizational::user_has_access($directionid, $USER->id)) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('vieworganizational', 'local_organizational'));
    }
}

echo $OUTPUT->header();

if (!$directionid) {
    echo $OUTPUT->notification(get_string('norecruitmentselected', 'local_organizational'), 'info');
    echo $OUTPUT->footer();
    die();
}

$organizational = \local_organizational\organizational::get_for_direction($directionid);

if (!$organizational) {
    echo $OUTPUT->notification(get_string('noorganizational', 'local_organizational'), 'info');
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->heading(format_string($organizational->name));

$message = file_rewrite_pluginfile_urls(
    $organizational->message,
    'pluginfile.php',
    $context->id,
    'local_organizational',
    'organizational',
    $organizational->id
);
echo format_text($message, $organizational->messageformat, ['context' => $context]);

echo $OUTPUT->footer();

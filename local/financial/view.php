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
 * View financial matters for the active direction.
 *
 * @package    local_financial
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/financial/view.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('financialmatters', 'local_financial'));
$PAGE->set_heading(get_string('financialmatters', 'local_financial'));

$isadmin = is_siteadmin();
$directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;

if (!$isadmin) {
    if (!$directionid) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('viewfinancial', 'local_financial'));
    }
    if (!\local_financial\financial::user_has_access($directionid, $USER->id)) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('viewfinancial', 'local_financial'));
    }
}

echo $OUTPUT->header();

if (!$directionid) {
    echo $OUTPUT->notification(get_string('norecruitmentselected', 'local_financial'), 'info');
    echo $OUTPUT->footer();
    die();
}

$financial = \local_financial\financial::get_for_direction($directionid);

if (!$financial) {
    echo $OUTPUT->notification(get_string('nofinancial', 'local_financial'), 'info');
    echo $OUTPUT->footer();
    die();
}

echo $OUTPUT->heading(format_string($financial->name));

$message = file_rewrite_pluginfile_urls(
    $financial->message,
    'pluginfile.php',
    $context->id,
    'local_financial',
    'financial',
    $financial->id
);
echo format_text($message, $financial->messageformat, ['context' => $context]);

echo $OUTPUT->footer();

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

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/organizational/view.php', $id ? ['id' => $id] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('organizationalmatters', 'local_organizational'));
$PAGE->set_heading(get_string('organizationalmatters', 'local_organizational'));

$isadmin = is_siteadmin();

if ($id && $isadmin) {
    // Admin viewing a specific record by ID.
    $organizational = $DB->get_record('local_organizational', ['id' => $id], '*', MUST_EXIST);
} else {
    // Regular user or admin without ID — use session direction.
    $directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;

    if (!$isadmin) {
        if (!$directionid) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('vieworganizational', 'local_organizational'));
        }
        if (!\local_organizational\organizational::user_has_access($directionid, $USER->id)) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('vieworganizational', 'local_organizational'));
        }
    }

    if (!$directionid) {
        redirect(new moodle_url('/local/dashboard/index.php', ['change' => 1]));
    }

    $organizational = \local_organizational\organizational::get_for_direction($directionid);
}

echo $OUTPUT->header();

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

// Display attachments.
$attachments = \local_organizational\organizational::get_attachments($organizational->id, $context);
if (!empty($attachments)) {
    echo $OUTPUT->heading(get_string('attachments', 'local_organizational'), 4);
    echo '<ul class="list-unstyled">';
    foreach ($attachments as $file) {
        $fileurl = moodle_url::make_pluginfile_url(
            $context->id, 'local_organizational', 'attachment', $organizational->id,
            $file->get_filepath(), $file->get_filename(), true
        );
        $icon = $OUTPUT->pix_icon(file_file_icon($file), $file->get_filename());
        echo '<li>' . $icon . ' ' . html_writer::link($fileurl, $file->get_filename()) . '</li>';
    }
    echo '</ul>';
}

echo $OUTPUT->footer();

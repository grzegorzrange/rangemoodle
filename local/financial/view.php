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

$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/financial/view.php', $id ? ['id' => $id] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('financialmatters', 'local_financial'));
$PAGE->set_heading(get_string('financialmatters', 'local_financial'));

$isadmin = is_siteadmin();

if ($id && $isadmin) {
    // Admin viewing a specific record by ID.
    $financial = $DB->get_record('local_financial', ['id' => $id], '*', MUST_EXIST);
} else {
    // Regular user or admin without ID — use session direction.
    $directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;

    if (!$isadmin) {
        if (!$directionid) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('viewfinancial', 'local_financial'));
        }
        if (!\local_financial\financial::user_has_access($directionid, $USER->id)) {
            throw new \moodle_exception('nopermissions', 'error', '', get_string('viewfinancial', 'local_financial'));
        }
    }

    if (!$directionid) {
        redirect(new moodle_url('/local/dashboard/index.php', ['change' => 1]));
    }

    $financial = \local_financial\financial::get_for_direction($directionid);
}

echo $OUTPUT->header();

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

// Display attachments.
$attachments = \local_financial\financial::get_attachments($financial->id, $context);
if (!empty($attachments)) {
    echo $OUTPUT->heading(get_string('attachments', 'local_financial'), 4);
    echo '<ul class="list-unstyled">';
    foreach ($attachments as $file) {
        $fileurl = moodle_url::make_pluginfile_url(
            $context->id, 'local_financial', 'attachment', $financial->id,
            $file->get_filepath(), $file->get_filename(), true
        );
        $icon = $OUTPUT->pix_icon(file_file_icon($file), $file->get_filename());
        echo '<li>' . $icon . ' ' . html_writer::link($fileurl, $file->get_filename()) . '</li>';
    }
    echo '</ul>';
}

echo $OUTPUT->footer();

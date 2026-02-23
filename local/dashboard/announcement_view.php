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
 * View a single announcement (full page).
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$id = required_param('id', PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/dashboard/announcement_view.php', ['id' => $id]));
$PAGE->set_pagelayout('standard');

$record = $DB->get_record('local_dashboard_announce', ['id' => $id], '*', MUST_EXIST);

// Non-admin users can only see visible announcements from their active direction.
if (!is_siteadmin()) {
    if (!$record->visible) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('viewannouncement', 'local_dashboard'));
    }
    $activedirectionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;
    if (!$activedirectionid || $activedirectionid != $record->directionid) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('viewannouncement', 'local_dashboard'));
    }
}

$PAGE->set_title($record->name);
$PAGE->set_heading($record->name);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($record->name));

// Rewrite pluginfile URLs for the message.
$message = file_rewrite_pluginfile_urls(
    $record->message,
    'pluginfile.php',
    $context->id,
    'local_dashboard',
    'announcement',
    $record->id
);
$formattedmessage = format_text($message, $record->messageformat, ['context' => $context]);

// Collect attachments.
$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'local_dashboard', 'announcement_attachments', $record->id, 'filename', false);

$attachments = [];
foreach ($files as $file) {
    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename()
    );
    $attachments[] = ['url' => $url->out(false), 'filename' => $file->get_filename()];
}

$templatedata = [
    'message' => $formattedmessage,
    'hasattachments' => !empty($attachments),
    'attachments' => $attachments,
    'str_attachments' => get_string('attachments', 'local_dashboard'),
    'backurl' => (new moodle_url('/my/'))->out(false),
    'str_back' => get_string('back'),
];
echo $OUTPUT->render_from_template('local_dashboard/announcement_view', $templatedata);

echo $OUTPUT->footer();

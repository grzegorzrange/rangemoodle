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
 * Create/edit announcement page.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_dashboard_announcements');

$context = context_system::instance();
$pageurl = new moodle_url('/local/dashboard/announcement_edit.php', $id ? ['id' => $id] : []);
$returnurl = new moodle_url('/local/dashboard/announcements.php');

$PAGE->set_url($pageurl);

if ($id) {
    $record = $DB->get_record('local_dashboard_announce', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editannouncement', 'local_dashboard'));
    $PAGE->set_heading(get_string('editannouncement', 'local_dashboard'));
} else {
    $record = new stdClass();
    $record->id = 0;
    $record->visible = 1;
    $PAGE->set_title(get_string('addannouncement', 'local_dashboard'));
    $PAGE->set_heading(get_string('addannouncement', 'local_dashboard'));
}

// Prepare editor data.
$editoroptions = \local_dashboard\announcement::editor_options($context);
$attachmentoptions = \local_dashboard\announcement::attachment_options();

if ($id) {
    $record = file_prepare_standard_editor(
        $record, 'message', $editoroptions, $context,
        'local_dashboard', 'announcement', $record->id
    );
    $draftitemid = file_get_submitted_draft_itemid('attachments');
    file_prepare_draft_area(
        $draftitemid, $context->id, 'local_dashboard',
        'announcement_attachments', $record->id, $attachmentoptions
    );
    $record->attachments = $draftitemid;
}

$form = new \local_dashboard\form\announcement_form($pageurl, ['context' => $context, 'id' => $id]);
$form->set_data($record);

if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    if ($data->id) {
        \local_dashboard\announcement::update($data, $context);

        // Log edit event.
        $event = \local_dashboard\event\announcement_updated::create([
            'context' => $context,
            'objectid' => $data->id,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        \core\notification::success(get_string('announcementsaved', 'local_dashboard'));
    } else {
        $newid = \local_dashboard\announcement::create($data, $context);

        // Log create event.
        $event = \local_dashboard\event\announcement_created::create([
            'context' => $context,
            'objectid' => $newid,
            'other' => ['name' => $data->name],
        ]);
        $event->trigger();

        // Send notification if checkbox was checked.
        if (!empty($data->sendnotification)) {
            \local_dashboard\announcement::send_notification($newid);
        }

        \core\notification::success(get_string('announcementsaved', 'local_dashboard'));
    }
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($id ? get_string('editannouncement', 'local_dashboard') : get_string('addannouncement', 'local_dashboard'));
$form->display();
echo $OUTPUT->footer();

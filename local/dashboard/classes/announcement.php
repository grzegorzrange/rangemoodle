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
 * Announcement API.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard;

defined('MOODLE_INTERNAL') || die();

/**
 * API class for announcement management.
 */
class announcement {

    /** @var string Table name. */
    const TABLE = 'local_dashboard_announce';

    /**
     * Create a new announcement.
     *
     * @param \stdClass $data Form data.
     * @param \context $context The context for file storage.
     * @return int The ID of the created announcement.
     */
    public static function create(\stdClass $data, \context $context): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->name = $data->name;
        $record->message = '';
        $record->messageformat = $data->message_editor['format'];
        $record->directionid = $data->directionid;
        $record->visible = !empty($data->visible) ? 1 : 0;
        $record->notificationsent = 0;
        $record->timecreated = time();
        $record->usercreated = $USER->id;
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        $id = $DB->insert_record(self::TABLE, $record);

        // Save editor files.
        $draftitemid = $data->message_editor['itemid'];
        $record->id = $id;
        $record->message = file_save_draft_area_files(
            $draftitemid,
            $context->id,
            'local_dashboard',
            'announcement',
            $id,
            self::editor_options($context),
            $data->message_editor['text']
        );
        $DB->update_record(self::TABLE, $record);

        // Save attachment files.
        if (!empty($data->attachments)) {
            file_save_draft_area_files(
                $data->attachments,
                $context->id,
                'local_dashboard',
                'announcement_attachments',
                $id,
                self::attachment_options()
            );
        }

        return $id;
    }

    /**
     * Update an existing announcement.
     *
     * @param \stdClass $data Form data.
     * @param \context $context The context for file storage.
     */
    public static function update(\stdClass $data, \context $context): void {
        global $DB, $USER;

        $record = $DB->get_record(self::TABLE, ['id' => $data->id], '*', MUST_EXIST);

        $record->name = $data->name;
        $record->messageformat = $data->message_editor['format'];
        $record->directionid = $data->directionid;
        $record->visible = !empty($data->visible) ? 1 : 0;
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        // Save editor files.
        $record->message = file_save_draft_area_files(
            $data->message_editor['itemid'],
            $context->id,
            'local_dashboard',
            'announcement',
            $record->id,
            self::editor_options($context),
            $data->message_editor['text']
        );

        $DB->update_record(self::TABLE, $record);

        // Save attachment files.
        if (isset($data->attachments)) {
            file_save_draft_area_files(
                $data->attachments,
                $context->id,
                'local_dashboard',
                'announcement_attachments',
                $record->id,
                self::attachment_options()
            );
        }
    }

    /**
     * Delete an announcement and its files.
     *
     * @param int $id Announcement ID.
     * @param \context $context The context for file storage.
     */
    public static function delete(int $id, \context $context): void {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_dashboard', 'announcement', $id);
        $fs->delete_area_files($context->id, 'local_dashboard', 'announcement_attachments', $id);

        $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Toggle visibility of an announcement.
     *
     * @param int $id Announcement ID.
     */
    public static function toggle_visibility(int $id): void {
        global $DB, $USER;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        $record->visible = $record->visible ? 0 : 1;
        $record->timemodified = time();
        $record->usermodified = $USER->id;
        $DB->update_record(self::TABLE, $record);
    }

    /**
     * Send notification emails to all users in the direction's cohort.
     *
     * @param int $id Announcement ID.
     */
    public static function send_notification(int $id): void {
        global $DB, $USER;

        $announce = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        if ($announce->notificationsent) {
            return;
        }

        // Get the direction's cohort and send to its members.
        $direction = $DB->get_record('local_recruitment_course', ['id' => $announce->directionid]);
        if (empty($direction) || empty($direction->cohortid)) {
            return;
        }

        $supportuser = \core_user::get_support_user();
        $members = $DB->get_records('cohort_members', ['cohortid' => $direction->cohortid]);
        foreach ($members as $member) {
            $user = $DB->get_record('user', ['id' => $member->userid]);
            if (!$user || $user->deleted || $user->suspended) {
                continue;
            }

            $subject = get_string('notificationsubject', 'local_dashboard', $announce->name);
            $messagetext = get_string('notificationbody', 'local_dashboard', (object)[
                'name' => $announce->name,
                'message' => html_to_text($announce->message),
            ]);
            $messagehtml = get_string('notificationbody_html', 'local_dashboard', (object)[
                'name' => $announce->name,
                'message' => $announce->message,
            ]);

            $result = email_to_user($user, $supportuser, $subject, $messagetext, $messagehtml);
            if (!$result) {
                debugging("Failed to send announcement email to user {$user->id} ({$user->email})", DEBUG_NORMAL);
                \core\notification::add(
                    "Failed to send email to: {$user->email}",
                    \core\notification::WARNING
                );
            }
        }

        // Mark as sent.
        $announce->notificationsent = 1;
        $announce->notificationsenttime = time();
        $announce->timemodified = time();
        $announce->usermodified = $USER->id;
        $DB->update_record(self::TABLE, $announce);
    }

    /**
     * Get visible announcements for a given direction, ordered by newest first.
     *
     * @param int $directionid
     * @param int $page Page number (0-based).
     * @param int $perpage Items per page.
     * @return array ['records' => array, 'total' => int]
     */
    public static function get_for_direction(int $directionid, int $page = 0, int $perpage = 3): array {
        global $DB;

        $total = $DB->count_records(self::TABLE, ['directionid' => $directionid, 'visible' => 1]);
        $records = $DB->get_records(
            self::TABLE,
            ['directionid' => $directionid, 'visible' => 1],
            'timecreated DESC',
            '*',
            $page * $perpage,
            $perpage
        );

        return ['records' => $records, 'total' => $total];
    }

    /**
     * Count attachment files for an announcement.
     *
     * @param int $id Announcement ID.
     * @param \context $context
     * @return int
     */
    public static function count_attachments(int $id, \context $context): int {
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_dashboard', 'announcement_attachments', $id, 'filename', false);
        return count($files);
    }

    /**
     * Editor options for the message field.
     *
     * @param \context $context
     * @return array
     */
    public static function editor_options(\context $context): array {
        return [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => 0,
            'context' => $context,
            'noclean' => true,
        ];
    }

    /**
     * Attachment file manager options.
     *
     * @return array
     */
    public static function attachment_options(): array {
        return [
            'maxfiles' => 20,
            'maxbytes' => 0,
            'subdirs' => 0,
        ];
    }
}

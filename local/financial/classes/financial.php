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
 * Financial matters API.
 *
 * @package    local_financial
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_financial;

defined('MOODLE_INTERNAL') || die();

/**
 * API class for financial matters management.
 */
class financial {

    /** @var string Table name. */
    const TABLE = 'local_financial';

    /** @var string Notification history table. */
    const NOTIFY_TABLE = 'local_financial_notify_hist';

    /**
     * Create a new financial matter.
     *
     * @param \stdClass $data Form data.
     * @param \context $context The context for file storage.
     * @return int The ID of the created record.
     */
    public static function create(\stdClass $data, \context $context): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->name = $data->name;
        $record->message = '';
        $record->messageformat = $data->message_editor['format'];
        $record->directionid = $data->directionid;
        $record->timecreated = time();
        $record->usercreated = $USER->id;
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        $id = $DB->insert_record(self::TABLE, $record);

        $record->id = $id;
        $record->message = file_save_draft_area_files(
            $data->message_editor['itemid'],
            $context->id,
            'local_financial',
            'financial',
            $id,
            self::editor_options($context),
            $data->message_editor['text']
        );
        $DB->update_record(self::TABLE, $record);

        return $id;
    }

    /**
     * Update an existing financial matter.
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
        $record->timemodified = time();
        $record->usermodified = $USER->id;

        $record->message = file_save_draft_area_files(
            $data->message_editor['itemid'],
            $context->id,
            'local_financial',
            'financial',
            $record->id,
            self::editor_options($context),
            $data->message_editor['text']
        );

        $DB->update_record(self::TABLE, $record);
    }

    /**
     * Delete a financial matter and its files and notification history.
     *
     * @param int $id Record ID.
     * @param \context $context The context for file storage.
     */
    public static function delete(int $id, \context $context): void {
        global $DB;

        $fs = get_file_storage();
        $fs->delete_area_files($context->id, 'local_financial', 'financial', $id);

        $DB->delete_records(self::NOTIFY_TABLE, ['financialid' => $id]);
        $DB->delete_records(self::TABLE, ['id' => $id]);
    }

    /**
     * Send notification to all users in the direction's cohort.
     *
     * @param int $id Financial matter ID.
     * @return int Number of recipients.
     */
    public static function send_notification(int $id): int {
        global $DB, $USER;

        $financial = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        $direction = $DB->get_record('local_recruitment_course', ['id' => $financial->directionid], '*', MUST_EXIST);

        if (empty($direction->cohortid)) {
            return 0;
        }

        $members = $DB->get_records('cohort_members', ['cohortid' => $direction->cohortid]);
        $supportuser = \core_user::get_support_user();
        $count = 0;

        foreach ($members as $member) {
            $user = $DB->get_record('user', ['id' => $member->userid]);
            if (!$user || $user->deleted || $user->suspended) {
                continue;
            }

            $subject = get_string('notificationsubject', 'local_financial', $financial->name);
            $messagetext = get_string('notificationbody', 'local_financial', (object)[
                'name' => $financial->name,
                'message' => html_to_text($financial->message),
            ]);
            $messagehtml = get_string('notificationbody_html', 'local_financial', (object)[
                'name' => $financial->name,
                'message' => $financial->message,
            ]);

            $result = email_to_user($user, $supportuser, $subject, $messagetext, $messagehtml);
            if (!$result) {
                debugging("Failed to send financial email to user {$user->id} ({$user->email})", DEBUG_NORMAL);
            }
            $count++;
        }

        $hist = new \stdClass();
        $hist->financialid = $id;
        $hist->usercreated = $USER->id;
        $hist->timecreated = time();
        $hist->recipientcount = $count;
        $DB->insert_record(self::NOTIFY_TABLE, $hist);

        return $count;
    }

    /**
     * Get financial matter for a given direction.
     *
     * @param int $directionid
     * @return \stdClass|false
     */
    public static function get_for_direction(int $directionid) {
        global $DB;
        return $DB->get_record(self::TABLE, ['directionid' => $directionid]);
    }

    /**
     * Check if user is member of direction's cohort.
     *
     * @param int $directionid
     * @param int $userid
     * @return bool
     */
    public static function user_has_access(int $directionid, int $userid): bool {
        global $DB;

        $direction = $DB->get_record('local_recruitment_course', ['id' => $directionid]);
        if (!$direction || empty($direction->cohortid)) {
            return false;
        }

        return $DB->record_exists('cohort_members', [
            'cohortid' => $direction->cohortid,
            'userid' => $userid,
        ]);
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
}

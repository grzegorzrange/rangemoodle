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
 * Recruitment API.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/cohort/lib.php');

/**
 * API class for recruitment management.
 */
class recruitment {

    /**
     * Create a new recruitment (parent container — name + date only).
     *
     * @param \stdClass $data Form data with name, recruitmentdate.
     * @return int The ID of the created recruitment.
     */
    public static function create(\stdClass $data): int {
        global $DB, $USER;

        $record = new \stdClass();
        $record->name = $data->name;
        $record->recruitmentdate = $data->recruitmentdate;
        $record->timecreated = time();
        $record->timemodified = time();
        $record->usercreated = $USER->id;

        return $DB->insert_record('local_recruitment', $record);
    }

    /**
     * Update an existing recruitment (name + date only).
     *
     * @param \stdClass $data Form data with id, name, recruitmentdate.
     */
    public static function update(\stdClass $data): void {
        global $DB;

        $record = $DB->get_record('local_recruitment', ['id' => $data->id], '*', MUST_EXIST);

        $record->name = $data->name;
        $record->recruitmentdate = $data->recruitmentdate;
        $record->timemodified = time();

        $DB->update_record('local_recruitment', $record);
    }

    /**
     * Delete a recruitment and all its directions (and their cohorts).
     *
     * @param int $id Recruitment ID.
     */
    public static function delete(int $id): void {
        global $DB;

        $DB->get_record('local_recruitment', ['id' => $id], '*', MUST_EXIST);

        // Delete all directions for this recruitment.
        $directions = $DB->get_records('local_recruitment_course', ['recruitmentid' => $id]);
        foreach ($directions as $dir) {
            self::delete_direction($dir->id);
        }

        $DB->delete_records('local_recruitment', ['id' => $id]);
    }

    /**
     * Create a new direction (kurs/kierunek) within a recruitment.
     * Creates cohort, copies all courses from selected base category, adds cohort sync enrolment.
     *
     * @param \stdClass $data Form data with recruitmentid, name, basecategoryid.
     * @return int The ID of the created direction.
     */
    public static function create_direction(\stdClass $data): int {
        global $DB, $USER;

        $recruitment = $DB->get_record('local_recruitment', ['id' => $data->recruitmentid], '*', MUST_EXIST);

        // Create cohort with mix of recruitment name + direction name.
        $cohortidnumber = self::generate_cohort_idnumber($recruitment->name, $data->name);

        $cohort = new \stdClass();
        $cohort->contextid = \context_system::instance()->id;
        $cohort->name = $cohortidnumber;
        $cohort->idnumber = $cohortidnumber;
        $cohort->component = '';
        $cohort->description = get_string('cohortfor_direction', 'local_recruitment', (object)[
            'recruitment' => $recruitment->name,
            'direction' => $data->name,
        ]);
        $cohort->descriptionformat = FORMAT_PLAIN;
        $cohortid = cohort_add_cohort($cohort);

        $record = new \stdClass();
        $record->recruitmentid = $data->recruitmentid;
        $record->name = $data->name;
        $record->basecategoryid = !empty($data->basecategoryid) ? (int)$data->basecategoryid : null;
        $record->cohortid = $cohortid;
        $record->timecreated = time();
        $record->timemodified = time();
        $record->usercreated = $USER->id;

        // Set copystatus to 0 (copying) if we have a base category to copy from.
        $record->copystatus = !empty($data->basecategoryid) ? 0 : 1;

        $directionid = $DB->insert_record('local_recruitment_course', $record);

        // Create target category as subcategory of 'utworzone_kierunki'.
        $parentcatid = $DB->get_field('course_categories', 'id', ['idnumber' => 'utworzone_kierunki']);
        if (!$parentcatid) {
            // Create parent category if it doesn't exist.
            $parentcat = \core_course_category::create((object) [
                'name' => 'Utworzone kierunki',
                'idnumber' => 'utworzone_kierunki',
                'parent' => 0,
                'visible' => 1,
            ]);
            $parentcatid = $parentcat->id;
        }

        $targetcatname = $recruitment->name . ' - ' . $data->name;
        $targetcat = \core_course_category::create((object) [
            'name' => $targetcatname,
            'parent' => $parentcatid,
            'visible' => 1,
        ]);

        // Update direction with target category.
        $DB->update_record('local_recruitment_course', (object) [
            'id' => $directionid,
            'target_categoryid' => $targetcat->id,
        ]);

        // Queue async course copying if there's a base category.
        if (!empty($data->basecategoryid)) {
            $task = new \local_recruitment\task\copy_direction_courses();
            $task->set_custom_data((object) [
                'directionid' => $directionid,
                'basecategoryid' => (int)$data->basecategoryid,
                'targetcategoryid' => $targetcat->id,
                'recruitmentname' => $recruitment->name,
                'cohortid' => $cohortid,
            ]);
            $task->set_userid($USER->id);
            \core\task\manager::queue_adhoc_task($task);
        }

        return $directionid;
    }

    /**
     * Update an existing direction (name only; cohort name is updated to match).
     *
     * @param \stdClass $data Form data with id, name.
     */
    public static function update_direction(\stdClass $data): void {
        global $DB;

        $record = $DB->get_record('local_recruitment_course', ['id' => $data->id], '*', MUST_EXIST);
        $recruitment = $DB->get_record('local_recruitment', ['id' => $record->recruitmentid], '*', MUST_EXIST);

        $record->name = $data->name;
        $record->timemodified = time();

        $DB->update_record('local_recruitment_course', $record);

        // Update cohort name to match.
        if ($record->cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $record->cohortid]);
            if ($cohort) {
                $cohortidnumber = self::generate_cohort_idnumber($recruitment->name, $data->name);
                $cohort->name = $cohortidnumber;
                $cohort->idnumber = $cohortidnumber;
                $cohort->description = get_string('cohortfor_direction', 'local_recruitment', (object)[
                    'recruitment' => $recruitment->name,
                    'direction' => $data->name,
                ]);
                $cohort->timemodified = time();
                cohort_update_cohort($cohort);
            }
        }
    }

    /**
     * Delete a direction and its associated cohort.
     *
     * @param int $id Direction ID.
     */
    public static function delete_direction(int $id): void {
        global $DB;

        $record = $DB->get_record('local_recruitment_course', ['id' => $id], '*', MUST_EXIST);

        if ($record->cohortid) {
            $cohort = $DB->get_record('cohort', ['id' => $record->cohortid]);
            if ($cohort) {
                cohort_delete_cohort($cohort);
            }
        }

        $DB->delete_records('local_recruitment_course', ['id' => $id]);
    }

    /**
     * Get all directions for a recruitment.
     *
     * @param int $recruitmentid
     * @return array
     */
    public static function get_directions(int $recruitmentid): array {
        global $DB;
        return $DB->get_records('local_recruitment_course', ['recruitmentid' => $recruitmentid], 'name ASC');
    }

    /**
     * Get all directions a user belongs to (via cohort membership).
     * Returns direction records with recruitment info.
     *
     * @param int $userid
     * @return array
     */
    public static function get_user_directions(int $userid): array {
        global $DB;

        $sql = "SELECT rc.*, r.name AS recruitmentname, r.recruitmentdate
                  FROM {local_recruitment_course} rc
                  JOIN {cohort_members} cm ON cm.cohortid = rc.cohortid
                  JOIN {local_recruitment} r ON r.id = rc.recruitmentid
                 WHERE cm.userid = :userid
              ORDER BY r.recruitmentdate DESC, rc.name ASC";

        return $DB->get_records_sql($sql, ['userid' => $userid]);
    }

    /**
     * Duplicate a course to a target category without user data.
     *
     * @param int $sourcecourseid Source course ID.
     * @param string $fullname Full name for the new course.
     * @param string $shortname Short name for the new course (must be unique).
     * @param int $categoryid Target category ID.
     * @return int New course ID.
     */
    public static function duplicate_course(int $sourcecourseid, string $fullname, string $shortname, int $categoryid): int {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        // Ensure shortname is unique.
        $baseshort = $shortname;
        $counter = 1;
        while ($DB->record_exists('course', ['shortname' => $shortname])) {
            $shortname = $baseshort . '_' . $counter;
            $counter++;
        }

        $backupsettings = [
            'activities' => 1,
            'blocks' => 1,
            'filters' => 1,
            'users' => 0,
            'role_assignments' => 0,
            'comments' => 0,
            'userscompletion' => 0,
            'logs' => 0,
            'grade_histories' => 0,
        ];

        // Backup the source course.
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE, $sourcecourseid, \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $USER->id
        );

        foreach ($backupsettings as $name => $value) {
            $setting = $bc->get_plan()->get_setting($name);
            if ($setting) {
                $setting->set_value($value);
            }
        }

        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();

        $bc->execute_plan();
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $bc->destroy();

        // Extract if needed.
        if (!file_exists($backupbasepath . "/moodle_backup.xml")) {
            $file->extract_to_pathname(get_file_packer('application/vnd.moodle.backup'), $backupbasepath);
        }

        // Create new course and restore.
        $newcourseid = \restore_dbops::create_new_course($fullname, $shortname, $categoryid);

        $rc = new \restore_controller(
            $backupid, $newcourseid,
            \backup::INTERACTIVE_NO, \backup::MODE_SAMESITE, $USER->id, \backup::TARGET_NEW_COURSE
        );

        foreach ($backupsettings as $name => $value) {
            $setting = $rc->get_plan()->get_setting($name);
            if ($setting->get_status() == \backup_setting::NOT_LOCKED) {
                $setting->set_value($value);
            }
        }

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
                $rc->destroy();
                throw new \moodle_exception('backupprecheckerrors', 'webservice', '',
                    implode(', ', $precheckresults['errors']));
            }
        }

        $rc->execute_plan();
        $rc->destroy();

        // Set the correct fullname and shortname.
        $course = $DB->get_record('course', ['id' => $newcourseid], '*', MUST_EXIST);
        $course->fullname = $fullname;
        $course->shortname = $shortname;
        $course->visible = 1;
        $DB->update_record('course', $course);

        // Cleanup.
        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }
        $file->delete();

        return $newcourseid;
    }

    /**
     * Add cohort sync enrolment method to a course.
     *
     * @param int $courseid The course to add the enrolment method to.
     * @param int $cohortid The cohort to sync.
     */
    public static function add_cohort_sync(int $courseid, int $cohortid): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/enrol/cohort/locallib.php');

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);

        $enrolplugin = enrol_get_plugin('cohort');
        $enrolplugin->add_instance($course, [
            'customint1' => $cohortid,
            'roleid' => $studentrole->id,
        ]);
    }

    /**
     * Get subcategories of a category identified by its idnumber.
     *
     * @param string $parentidnumber The idnumber of the parent category.
     * @return array id => name
     */
    public static function get_subcategories_of(string $parentidnumber): array {
        global $DB;

        $parentid = $DB->get_field('course_categories', 'id', ['idnumber' => $parentidnumber]);
        if (!$parentid) {
            return [];
        }

        return $DB->get_records_menu('course_categories', ['parent' => $parentid], 'name ASC', 'id, name');
    }

    /**
     * Import users from CSV content into a direction.
     *
     * CSV format: username;firstname;lastname;email;declaration
     * Separator: semicolon. declaration values: tak/nie.
     * Sends email + SMS notification to newly imported users (once per direction).
     *
     * @param int $directionid Direction ID.
     * @param string $content CSV file content.
     * @return array ['created' => int, 'updated' => int, 'errors' => string[], 'notified' => int]
     */
    public static function import_users_csv(int $directionid, string $content): array {
        global $DB, $USER, $CFG;

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        $direction = $DB->get_record('local_recruitment_course', ['id' => $directionid], '*', MUST_EXIST);
        $recruitment = $DB->get_record('local_recruitment', ['id' => $direction->recruitmentid], '*', MUST_EXIST);

        $result = ['created' => 0, 'updated' => 0, 'errors' => [], 'notified' => 0];
        $now = time();
        $userstonotify = [];

        // Parse CSV lines.
        $lines = preg_split('/\r?\n/', trim($content));
        if (empty($lines)) {
            return $result;
        }

        // Check if first line is a header.
        $firstline = strtolower(trim($lines[0]));
        if (strpos($firstline, 'username') !== false) {
            array_shift($lines);
        }

        foreach ($lines as $linenum => $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $csvline = $linenum + 2; // +2 because 1-based + header.
            $parts = str_getcsv($line, ';');

            if (count($parts) < 5) {
                $a = new \stdClass();
                $a->line = $csvline;
                $a->message = 'Not enough columns (expected 5, got ' . count($parts) . ')';
                $result['errors'][] = get_string('importerror', 'local_recruitment', $a);
                continue;
            }

            $username = trim($parts[0]);
            $firstname = trim($parts[1]);
            $lastname = trim($parts[2]);
            $email = trim($parts[3]);
            $declarationstr = strtolower(trim($parts[4]));

            if (empty($username) || empty($firstname) || empty($lastname)) {
                $a = new \stdClass();
                $a->line = $csvline;
                $a->message = 'Username, firstname and lastname are required';
                $result['errors'][] = get_string('importerror', 'local_recruitment', $a);
                continue;
            }

            $declaration = ($declarationstr === 'tak' || $declarationstr === '1' || $declarationstr === 'yes') ? 1 : 0;

            // Find or create user.
            $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0]);

            if (!$user) {
                // Create new user.
                $newuser = new \stdClass();
                $newuser->username = $username;
                $newuser->firstname = $firstname;
                $newuser->lastname = $lastname;
                $newuser->email = !empty($email) ? $email : '';
                $newuser->auth = 'manual';
                $newuser->confirmed = 1;
                $newuser->mnethostid = $CFG->mnet_localhost_id;
                $newuser->password = generate_password(12);

                try {
                    $newuser->id = user_create_user($newuser, true, false);
                    $user = $DB->get_record('user', ['id' => $newuser->id]);
                    $result['created']++;
                } catch (\Exception $e) {
                    $a = new \stdClass();
                    $a->line = $csvline;
                    $a->message = $e->getMessage();
                    $result['errors'][] = get_string('importerror', 'local_recruitment', $a);
                    continue;
                }
            }

            // Insert or update local_recruitment_user record.
            $existing = $DB->get_record('local_recruitment_user', [
                'userid' => $user->id,
                'directionid' => $directionid,
            ]);

            if ($existing) {
                $existing->declaration = $declaration;
                $existing->timemodified = $now;
                $DB->update_record('local_recruitment_user', $existing);
                $result['updated']++;

                // Only notify if not yet notified for this direction.
                if (empty($existing->notified)) {
                    $userstonotify[] = ['user' => $user, 'recordid' => $existing->id];
                }
            } else {
                $record = new \stdClass();
                $record->userid = $user->id;
                $record->directionid = $directionid;
                $record->recruitmentid = $direction->recruitmentid;
                $record->declaration = $declaration;
                $record->notified = 0;
                $record->timenotified = 0;
                $record->timecreated = $now;
                $record->usercreated = $USER->id;
                $record->timemodified = $now;
                $recordid = $DB->insert_record('local_recruitment_user', $record);
                $result['created']++;

                $userstonotify[] = ['user' => $user, 'recordid' => $recordid];
            }

            // Add user to direction's cohort.
            if ($direction->cohortid) {
                if (!$DB->record_exists('cohort_members', [
                    'cohortid' => $direction->cohortid,
                    'userid' => $user->id,
                ])) {
                    cohort_add_member($direction->cohortid, $user->id);
                }
            }
        }

        // Send notifications to all newly imported (not yet notified) users.
        $notifiedcount = self::send_import_notifications($userstonotify, $direction, $recruitment);
        $result['notified'] = $notifiedcount;

        return $result;
    }

    /**
     * Send email + SMS notifications to imported users.
     *
     * @param array $userstonotify Array of ['user' => user object, 'recordid' => int].
     * @param \stdClass $direction Direction record.
     * @param \stdClass $recruitment Recruitment record.
     * @return int Number of users notified.
     */
    protected static function send_import_notifications(array $userstonotify, \stdClass $direction, \stdClass $recruitment): int {
        global $DB, $CFG;

        if (empty($userstonotify)) {
            return 0;
        }

        $count = 0;
        $now = time();
        $noreplyuser = \core_user::get_noreply_user();

        $subject = get_string('examregistrationsubject', 'local_recruitment');
        $messagetext = get_string('examregistrationbody', 'local_recruitment', (object)[
            'direction' => $direction->name,
            'recruitment' => $recruitment->name,
        ]);
        $smstext = get_string('examregistrationsms', 'local_recruitment', (object)[
            'direction' => $direction->name,
            'recruitment' => $recruitment->name,
        ]);

        foreach ($userstonotify as $entry) {
            $user = $entry['user'];
            $recordid = $entry['recordid'];

            // Send Moodle message (email).
            $message = new \core\message\message();
            $message->component = 'local_recruitment';
            $message->name = 'exam_registration';
            $message->userfrom = $noreplyuser;
            $message->userto = $user;
            $message->subject = $subject;
            $message->fullmessage = $messagetext;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = nl2br(s($messagetext));
            $message->smallmessage = $subject;
            $message->notification = 1;

            try {
                message_send($message);
            } catch (\Exception $e) {
                debugging('Failed to send email to user ' . $user->id . ': ' . $e->getMessage(), DEBUG_NORMAL);
            }

            // Send SMS via local_support sms_service.
            if (class_exists('\local_support\sms_service')) {
                // Ensure user has phone1 loaded.
                $fulluser = $DB->get_record('user', ['id' => $user->id]);
                if ($fulluser && !empty($fulluser->phone1)) {
                    \local_support\sms_service::send($fulluser, $smstext, 'local_recruitment', 'exam_registration_sms', (int)$direction->id);
                }
            }

            // Mark as notified.
            $DB->update_record('local_recruitment_user', (object)[
                'id' => $recordid,
                'notified' => 1,
                'timenotified' => $now,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Export users of a direction as CSV content.
     *
     * @param int $directionid Direction ID.
     * @return string CSV content.
     */
    public static function export_users_csv(int $directionid): string {
        global $DB;

        $sql = "SELECT ru.id, u.username, u.firstname, u.lastname, u.email, u.phone1,
                       ru.declaration, ru.notified, ru.timenotified
                  FROM {local_recruitment_user} ru
                  JOIN {user} u ON u.id = ru.userid
                 WHERE ru.directionid = :directionid
              ORDER BY u.lastname ASC, u.firstname ASC";

        $records = $DB->get_records_sql($sql, ['directionid' => $directionid]);

        $lines = [];
        $lines[] = 'username;firstname;lastname;email;phone;declaration;notified;timenotified';

        foreach ($records as $rec) {
            $declaration = $rec->declaration ? 'tak' : 'nie';
            $notified = $rec->notified ? 'tak' : 'nie';
            $timenotified = $rec->timenotified ? userdate($rec->timenotified, '%Y-%m-%d %H:%M') : '';

            $lines[] = implode(';', [
                $rec->username,
                $rec->firstname,
                $rec->lastname,
                $rec->email,
                $rec->phone1 ?? '',
                $declaration,
                $notified,
                $timenotified,
            ]);
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * Generate a cohort idnumber from recruitment name and direction name.
     * E.g. "ELE.02 - 02.2026" + "Technik informatyk" => "ELE02_02_2026_Technik_informatyk"
     *
     * @param string $recruitmentname
     * @param string $directionname
     * @return string
     */
    public static function generate_cohort_idnumber(string $recruitmentname, string $directionname = ''): string {
        $combined = $recruitmentname;
        if (!empty($directionname)) {
            $combined .= '_' . $directionname;
        }
        $idnumber = str_replace(['.', ' - ', ' '], ['', '_', '_'], $combined);
        $idnumber = preg_replace('/[^a-zA-Z0-9_]/', '', $idnumber);
        $idnumber = preg_replace('/_+/', '_', $idnumber);
        return trim($idnumber, '_');
    }
}

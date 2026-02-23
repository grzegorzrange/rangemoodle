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
 * Ad hoc task for copying courses when creating a direction.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad hoc task that copies courses from a base category to the target category
 * for a newly created direction.
 */
class copy_direction_courses extends \core\task\adhoc_task {

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();

        $directionid = $data->directionid;
        $basecategoryid = $data->basecategoryid;
        $targetcategoryid = $data->targetcategoryid;
        $recruitmentname = $data->recruitmentname;
        $cohortid = $data->cohortid;

        // Verify direction still exists.
        $direction = $DB->get_record('local_recruitment_course', ['id' => $directionid]);
        if (!$direction) {
            mtrace("Direction {$directionid} no longer exists, skipping.");
            return;
        }

        mtrace("Starting course copy for direction {$directionid} ({$direction->name})...");

        $archivecourse = null;
        $preparationcourse = null;
        $quizescourse = null;
        $newcourseids = [];

        $sourcecourses = $DB->get_records('course', ['category' => $basecategoryid]);

        foreach ($sourcecourses as $sc) {
            $idnumber = strtolower($sc->idnumber ?? '');

            if (strpos($idnumber, 'archiwum') !== false) {
                $fullname = 'Archiwum wykładów';
                $shortname = 'Archiwum wykładów ' . $recruitmentname;
                $newid = \local_recruitment\recruitment::duplicate_course(
                    $sc->id, $fullname, $shortname, $targetcategoryid
                );
                $archivecourse = $newid;
                mtrace("  Copied archive course: {$newid}");
            } else if (strpos($idnumber, 'przygotowanie') !== false) {
                $fullname = 'Przygotowanie do egzaminu';
                $shortname = 'Przygotowanie do egzaminu ' . $recruitmentname;
                $newid = \local_recruitment\recruitment::duplicate_course(
                    $sc->id, $fullname, $shortname, $targetcategoryid
                );
                $preparationcourse = $newid;
                mtrace("  Copied preparation course: {$newid}");
            } else if (strpos($idnumber, 'testy') !== false) {
                $fullname = 'Testy wewnętrzne';
                $shortname = 'Testy wewnętrzne ' . $recruitmentname;
                $newid = \local_recruitment\recruitment::duplicate_course(
                    $sc->id, $fullname, $shortname, $targetcategoryid
                );
                $quizescourse = $newid;
                mtrace("  Copied tests course: {$newid}");
            } else {
                // Copy other courses with original name.
                $newid = \local_recruitment\recruitment::duplicate_course(
                    $sc->id, $sc->fullname, $sc->shortname . '_' . $directionid, $targetcategoryid
                );
                mtrace("  Copied other course: {$newid}");
            }
            $newcourseids[] = $newid;
        }

        // Update direction with course IDs and mark as done.
        $DB->update_record('local_recruitment_course', (object) [
            'id' => $directionid,
            'archive_course' => $archivecourse,
            'preparation_course' => $preparationcourse,
            'quizes_course' => $quizescourse,
            'copystatus' => 1,
            'timemodified' => time(),
        ]);

        // Add cohort sync enrolment to each copied course.
        foreach ($newcourseids as $newcourseid) {
            \local_recruitment\recruitment::add_cohort_sync($newcourseid, $cohortid);
        }

        mtrace("Course copy completed for direction {$directionid}.");
    }
}

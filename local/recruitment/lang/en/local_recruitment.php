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
 * English strings for local_recruitment.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Recruitment Management';
$string['recruitments'] = 'Recruitments';
$string['addrecruitment'] = 'Add recruitment';
$string['editrecruitment'] = 'Edit recruitment';
$string['deleterecruitment'] = 'Delete recruitment';
$string['recruitmentname'] = 'Name';
$string['recruitmentdate'] = 'Recruitment date';
$string['basecategory'] = 'Base category';
$string['archivecourse'] = 'Archive course';
$string['preparationcourse'] = 'Preparation course';
$string['quizescourse'] = 'Internal tests course';
$string['cohort'] = 'Cohort';
$string['choose'] = '-- Choose --';
$string['confirmdelete'] = 'Are you sure you want to delete recruitment "{$a}"? All directions and their cohorts will be deleted.';
$string['recruitmentsaved'] = 'Recruitment saved successfully.';
$string['recruitmentdeleted'] = 'Recruitment deleted successfully.';
$string['norecruitments'] = 'No recruitments found.';
$string['cohortfor'] = 'Cohort for recruitment: {$a}';
$string['cohortfor_direction'] = 'Cohort for: {$a->recruitment} - {$a->direction}';
$string['privacy:metadata'] = 'The recruitment plugin does not store personal data directly. User assignments are managed through cohort memberships.';
$string['eventrecruitmentcreated'] = 'Recruitment created';
$string['eventrecruitmentupdated'] = 'Recruitment updated';
$string['eventrecruitmentdeleted'] = 'Recruitment deleted';
$string['assignedcourses'] = 'Assigned courses';
$string['enter'] = 'Enter';
$string['directions'] = 'Courses (directions)';
$string['directionname'] = 'Course (direction) name';
$string['adddirection'] = 'Add course';
$string['editdirection'] = 'Edit course (direction)';
$string['deletedirection'] = 'Delete course (direction)';
$string['confirmdeletedirection'] = 'Are you sure you want to delete course (direction) "{$a}" and its cohort?';
$string['directionsaved'] = 'Course (direction) saved successfully.';
$string['directiondeleted'] = 'Course (direction) deleted successfully.';
$string['backtorecruitments'] = 'Back to recruitments';
$string['copystatus'] = 'Copy status';
$string['copyinprogress'] = 'Copying in progress...';
$string['copydone'] = 'Done';
$string['users'] = 'Users';
$string['importusers'] = 'Import users';
$string['downloadcsvsample'] = 'Download sample CSV';
$string['declaration'] = 'Declaration';
$string['declarationyes'] = 'Yes';
$string['declarationno'] = 'No';
$string['toggledeclaration'] = 'Toggle declaration';
$string['removeuser'] = 'Remove user';
$string['confirmremoveuser'] = 'Are you sure you want to remove user "{$a}" from this direction?';
$string['userimported'] = 'Users imported successfully. Created: {$a->created}, Updated: {$a->updated}, Errors: {$a->errors}, Notified: {$a->notified}.';
$string['importerror'] = 'Error on line {$a->line}: {$a->message}';
$string['backtousers'] = 'Back to users';
$string['csvformat'] = 'CSV format: username;firstname;lastname;email;declaration (tak/nie)';
$string['exportusers'] = 'Export users';
$string['setdeclaration'] = 'Mark declaration';
$string['confirmsetdeclaration'] = 'Are you sure you want to mark the declaration for user "{$a}"? This action is irreversible and will send a notification.';
$string['declarationset'] = 'Declaration has been marked and notification sent.';
$string['notificationstatus'] = 'Notification';
$string['notifiedyes'] = 'Sent';
$string['notifiedno'] = 'Not sent';
$string['examregistrationsubject'] = 'Exam registration is now available';
$string['examregistrationbody'] = 'Hello, exam registration for the course {$a->direction} (recruitment: {$a->recruitment}) is now available. Please log in to the platform.';
$string['examregistrationsms'] = 'Exam registration for {$a->direction} ({$a->recruitment}) is now available. Log in to the platform.';
$string['messageprovider:exam_registration'] = 'Exam registration notification';

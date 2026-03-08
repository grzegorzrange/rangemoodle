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
 * English language strings for local_activityreport.
 *
 * @package    local_activityreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Activity Report';
$string['activityreport'] = 'Activity Report';
$string['filter_firstname'] = 'First name';
$string['filter_lastname'] = 'Last name';
$string['filter_email'] = 'Email';
$string['filter_eventname'] = 'Event name';
$string['filter'] = 'Filter';
$string['resetfilters'] = 'Reset';
$string['eventname'] = 'Event name';
$string['description'] = 'Description';
$string['timecreated'] = 'Time';
$string['nologsfound'] = 'No log entries found.';
$string['firstname'] = 'First name';
$string['lastname'] = 'Last name';
$string['email'] = 'Email';
$string['filter_description'] = 'Description';
$string['filter_datefrom'] = 'Date from';
$string['filter_dateto'] = 'Date to';
$string['privacy:metadata'] = 'The Activity Report plugin does not store any personal data. It only reads existing log data.';

// Event descriptions.
$string['eventdesc_user_loggedin'] = 'User {$a->user} logged in to the platform.';
$string['eventdesc_course_module_viewed'] = 'User {$a->user} viewed module "{$a->module}" in course "{$a->course}".';
$string['eventdesc_course_module_completion_updated'] = 'User {$a->user} updated completion of module "{$a->module}" in course "{$a->course}".';
$string['eventdesc_course_completed'] = 'User {$a->user} completed course "{$a->course}".';
$string['eventdesc_attempt_started'] = 'User {$a->user} started an attempt at quiz "{$a->module}" in course "{$a->course}".';
$string['eventdesc_attempt_submitted'] = 'User {$a->user} submitted an attempt at quiz "{$a->module}" in course "{$a->course}".';
$string['eventdesc_attempt_reviewed'] = 'User {$a->user} reviewed quiz attempt "{$a->module}" in course "{$a->course}".';
$string['eventdesc_attempt_viewed'] = 'User {$a->user} viewed quiz attempt "{$a->module}" in course "{$a->course}".';

// Event names (fallback).
$string['eventname_user_loggedin'] = 'User logged in';
$string['eventname_course_module_viewed'] = 'Course module viewed';
$string['eventname_course_module_completion_updated'] = 'Module completion updated';
$string['eventname_course_completed'] = 'Course completed';
$string['eventname_attempt_started'] = 'Quiz attempt started';
$string['eventname_attempt_submitted'] = 'Quiz attempt submitted';
$string['eventname_attempt_reviewed'] = 'Quiz attempt reviewed';
$string['eventname_attempt_viewed'] = 'Quiz attempt viewed';

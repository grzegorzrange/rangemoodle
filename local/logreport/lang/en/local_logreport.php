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
 * English language strings for local_logreport.
 *
 * @package    local_logreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Log Report';
$string['logreport'] = 'Log Report';
$string['eventname'] = 'Event name';
$string['description'] = 'Description';
$string['timecreated'] = 'Date';
$string['filter_eventname'] = 'Event name';
$string['filter_description'] = 'Description';
$string['filter_datefrom'] = 'Date from';
$string['filter_dateto'] = 'Date to';
$string['filter'] = 'Filter';
$string['resetfilters'] = 'Reset';
$string['privacy:metadata'] = 'The Log Report plugin does not store any personal data. It only reads existing log data.';

// Generic event description templates.
$string['eventdesc_generic'] = 'User {$a->user} performed action "{$a->action}" on "{$a->target}" (component: {$a->component}).';
$string['eventdesc_generic_course'] = 'User {$a->user} performed action "{$a->action}" on "{$a->target}" in course "{$a->course}" (component: {$a->component}).';
$string['eventdesc_generic_module'] = 'User {$a->user} performed action "{$a->action}" on module "{$a->module}" in course "{$a->course}" (component: {$a->component}).';
$string['eventdesc_questions_imported'] = 'User {$a->user} imported questions to the question bank.';

// CRUD action translations.
$string['crud_c'] = 'created';
$string['crud_r'] = 'viewed';
$string['crud_u'] = 'updated';
$string['crud_d'] = 'deleted';

// Common event descriptions for local plugins.
$string['eventdesc_created'] = 'User {$a->user} created {$a->target} (component: {$a->component}).';
$string['eventdesc_updated'] = 'User {$a->user} updated {$a->target} (component: {$a->component}).';
$string['eventdesc_deleted'] = 'User {$a->user} deleted {$a->target} (component: {$a->component}).';
$string['eventdesc_viewed'] = 'User {$a->user} viewed {$a->target} (component: {$a->component}).';

// Component name translations.
$string['component_local_recruitment'] = 'Recruitment';
$string['component_local_dashboard'] = 'Dashboard / Announcements';
$string['component_local_financial'] = 'Financial matters';
$string['component_local_organizational'] = 'Organizational matters';
$string['component_local_schedule'] = 'Schedule';
$string['component_core'] = 'System';

// Target translations.
$string['target_recruitment'] = 'recruitment';
$string['target_announcement'] = 'announcement';
$string['target_financial'] = 'financial matter';
$string['target_organizational'] = 'organizational matter';
$string['target_schedule'] = 'schedule';
$string['target_course'] = 'course';
$string['target_user'] = 'user';
$string['target_role'] = 'role';
$string['target_category'] = 'category';
$string['target_cohort'] = 'cohort';
$string['target_questions'] = 'questions';

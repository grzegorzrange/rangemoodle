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
 * English strings for local_financial.
 *
 * @package    local_financial
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Financial Matters Management';
$string['financialmatters'] = 'Financial matters';
$string['addfinancial'] = 'Add financial matter';
$string['editfinancial'] = 'Edit financial matter';
$string['deletefinancial'] = 'Delete financial matter';
$string['viewfinancial'] = 'View financial matters';
$string['financialname'] = 'Name';
$string['financialcontent'] = 'Content';
$string['recruitment'] = 'Recruitment';
$string['direction'] = 'Course (direction)';
$string['sendnotification'] = 'Send notification after saving';
$string['financialsaved'] = 'Financial matter saved successfully.';
$string['financialdeleted'] = 'Financial matter deleted successfully.';
$string['confirmdeletefinancial'] = 'Are you sure you want to delete financial matter "{$a}"?';
$string['notificationsendsuccess'] = 'Notification sent to {$a} users.';
$string['notificationsubject'] = 'Financial matters updated: {$a}';
$string['notificationbody'] = 'Financial matters have been updated: {$a->name}

{$a->message}';
$string['notificationbody_html'] = '<p>Financial matters have been updated: <strong>{$a->name}</strong></p>{$a->message}';
$string['notifyhistory'] = 'Notification history';
$string['notifyhistoryfor'] = 'Notification history: {$a}';
$string['sentby'] = 'Sent by';
$string['recipientcount'] = 'Recipients';
$string['nofinancial'] = 'No financial matters available for this recruitment.';
$string['norecruitmentselected'] = 'No recruitment selected.';
$string['financial:manage'] = 'Manage financial matters';
$string['financial:view'] = 'View financial matters';
$string['eventfinancialcreated'] = 'Financial matter created';
$string['eventfinancialupdated'] = 'Financial matter updated';
$string['eventfinancialdeleted'] = 'Financial matter deleted';
$string['privacy:metadata'] = 'The financial matters plugin does not store personal data directly.';

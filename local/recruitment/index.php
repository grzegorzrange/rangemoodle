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
 * Recruitment list page.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_recruitment');

$pageurl = new moodle_url('/local/recruitment/index.php');

$PAGE->set_url($pageurl);
$PAGE->set_title(get_string('pluginname', 'local_recruitment'));
$PAGE->set_heading(get_string('pluginname', 'local_recruitment'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('recruitments', 'local_recruitment'));

// Add new recruitment button.
$addurl = new moodle_url('/local/recruitment/edit.php');
echo html_writer::div(
    $OUTPUT->single_button($addurl, get_string('addrecruitment', 'local_recruitment'), 'get'),
    'mb-3'
);

// Display recruitment table.
$table = new \local_recruitment\output\recruitment_table('local-recruitment-list', $pageurl);
$table->out(50, true);

echo $OUTPUT->footer();

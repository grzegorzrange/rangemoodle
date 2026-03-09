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
 * Admin page: Internal tests courses overview.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_recruitment_internaltests');

$pageurl = new moodle_url('/local/recruitment/internaltests.php');
$PAGE->set_url($pageurl);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('internaltests_overview', 'local_recruitment'));

echo $OUTPUT->heading(get_string('basecourses', 'local_recruitment'), 4);
$basetable = new \local_recruitment\output\course_base_table('recruitment-internaltests-base', $pageurl, 'testy');
$basetable->out(50, true);

echo html_writer::tag('hr', '');

echo $OUTPUT->heading(get_string('directioncourses', 'local_recruitment'), 4);
$table = new \local_recruitment\output\course_overview_table('recruitment-internaltests-dir', $pageurl, 'testy');
$table->out(50, true);

echo $OUTPUT->footer();

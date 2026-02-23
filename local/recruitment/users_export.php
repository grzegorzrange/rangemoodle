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
 * Export users list as CSV.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
require_capability('local/recruitment:manage', context_system::instance());

$did = required_param('did', PARAM_INT);

$direction = $DB->get_record('local_recruitment_course', ['id' => $did], '*', MUST_EXIST);
$recruitment = $DB->get_record('local_recruitment', ['id' => $direction->recruitmentid], '*', MUST_EXIST);

$content = \local_recruitment\recruitment::export_users_csv($did);

$filename = clean_filename($recruitment->name . '_' . $direction->name . '_users.csv');

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($content));

echo $content;
exit;

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
 * Admin settings for local_recruitment.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_recruitment',
        get_string('pluginname', 'local_recruitment'),
        new moodle_url('/local/recruitment/index.php'),
        'local/recruitment:manage'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_recruitment_archives',
        get_string('archives_overview', 'local_recruitment'),
        new moodle_url('/local/recruitment/archives.php'),
        'local/recruitment:manage'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_recruitment_preparation',
        get_string('preparation_overview', 'local_recruitment'),
        new moodle_url('/local/recruitment/preparation.php'),
        'local/recruitment:manage'
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_recruitment_internaltests',
        get_string('internaltests_overview', 'local_recruitment'),
        new moodle_url('/local/recruitment/internaltests.php'),
        'local/recruitment:manage'
    ));
}

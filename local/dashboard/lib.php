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
 * Library functions for local_dashboard.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Render active course info inside #usernavigation in the navbar.
 *
 * @param \renderer_base $renderer
 * @return string HTML to inject.
 */
function local_dashboard_render_navbar_output($renderer) {
    global $SESSION, $DB, $USER;

    if (!isloggedin() || isguestuser() || is_siteadmin()) {
        return '';
    }

    $directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;
    if (!$directionid) {
        return '';
    }

    $direction = $DB->get_record('local_recruitment_course', ['id' => $directionid]);
    if (!$direction) {
        return '';
    }

    $recruitment = $DB->get_record('local_recruitment', ['id' => $direction->recruitmentid]);
    $displayname = format_string($recruitment->name . ' → ' . $direction->name);

    $label = get_string('activecourse', 'local_dashboard');

    // Only show the change button if user has more than one direction.
    $directions = \local_recruitment\recruitment::get_user_directions((int)$USER->id);
    $btnhtml = '';
    if (count($directions) > 1) {
        $changeurl = new \moodle_url('/local/dashboard/index.php', ['change' => 1]);
        $btntext = get_string('changecourse', 'local_dashboard');
        $btnhtml = '<a href="' . $changeurl->out(false) . '" class="btn btn-sm btn-change-recruitment">' . $btntext . '</a>';
    }

    return '<div class="d-flex align-items-center me-2">' .
        '<span class="d-none d-sm-inline small me-2">' . $label . ' <strong>' . $displayname . '</strong></span>' .
        $btnhtml .
        '</div>';
}

/**
 * Serve files for the local_dashboard plugin.
 *
 * @param stdClass $course Course object.
 * @param stdClass $cm Course module object.
 * @param context $context Context object.
 * @param string $filearea File area.
 * @param array $args Extra arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool False if file not found.
 */
function local_dashboard_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea === 'announcement' || $filearea === 'announcement_attachments') {
        $itemid = array_shift($args);
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_dashboard', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    return false;
}

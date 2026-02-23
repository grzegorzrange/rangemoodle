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
 * Main dashboard page - direction selection.
 *
 * After login, users land here. If they have multiple directions, they choose one.
 * After selection, they are redirected to /my/ (standard Moodle dashboard).
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$directionid = optional_param('did', 0, PARAM_INT);
$change = optional_param('change', 0, PARAM_BOOL);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/dashboard/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('selectrecruitment', 'local_dashboard'));
$PAGE->set_heading(get_string('selectrecruitment', 'local_dashboard'));

// Get user's directions (with recruitment info).
$directions = \local_recruitment\recruitment::get_user_directions($USER->id);
$isadmin = is_siteadmin();

// If a direction was selected, verify the user belongs to it, then store in session.
if ($directionid) {
    // Admin can select any direction; regular users must be a member of the direction's cohort.
    $valid = false;
    if ($isadmin) {
        $valid = $DB->record_exists('local_recruitment_course', ['id' => $directionid]);
    } else {
        foreach ($directions as $d) {
            if ((int)$d->id === $directionid) {
                $valid = true;
                break;
            }
        }
    }
    if ($valid) {
        $SESSION->active_direction_id = $directionid;
        redirect(new moodle_url('/my/'));
    }
}

// Auto-redirect logic only when not explicitly changing.
if (!$change) {
    // If single direction, auto-select and redirect to /my/.
    if (count($directions) == 1) {
        $first = reset($directions);
        $SESSION->active_direction_id = $first->id;
        redirect(new moodle_url('/my/'));
    }

    // Admin with no directions - redirect to /my/ directly.
    if ($isadmin && empty($directions)) {
        $SESSION->active_direction_id = 0;
        redirect(new moodle_url('/my/'));
    }
}

// If not admin and no directions, show message.
if (empty($directions) && !$isadmin) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('norecruitments', 'local_dashboard'), 'info');
    echo $OUTPUT->footer();
    die();
}

// Multiple directions - show selection screen.
$templatedata = [];
foreach ($directions as $d) {
    $templatedata[] = [
        'id' => $d->id,
        'name' => format_string($d->recruitmentname . ' → ' . $d->name),
        'recruitmentdate' => userdate($d->recruitmentdate, get_string('strftimedate', 'langconfig')),
        'url' => (new moodle_url('/local/dashboard/index.php', ['did' => $d->id]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_dashboard/select', [
    'recruitments' => $templatedata,
]);
echo $OUTPUT->footer();

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
 * Log report main page.
 *
 * @package    local_logreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_logreport');

$filtereventname   = optional_param('filter_eventname', '', PARAM_TEXT);
$filterdescription = optional_param('filter_description', '', PARAM_TEXT);
$filterdatefrom    = optional_param('filter_datefrom', '', PARAM_TEXT);
$filterdateto      = optional_param('filter_dateto', '', PARAM_TEXT);
$resetfilters      = optional_param('resetfilters', 0, PARAM_BOOL);

if ($resetfilters) {
    $filtereventname   = '';
    $filterdescription = '';
    $filterdatefrom    = '';
    $filterdateto      = '';
}

// Convert date strings to timestamps.
$datefromts = 0;
$datetots = 0;
if (!empty($filterdatefrom)) {
    $datefromts = strtotime($filterdatefrom);
    if ($datefromts === false) {
        $datefromts = 0;
    }
}
if (!empty($filterdateto)) {
    $datetots = strtotime($filterdateto . ' 23:59:59');
    if ($datetots === false) {
        $datetots = 0;
    }
}

$filters = [
    'filter_eventname'   => $filtereventname,
    'filter_description' => $filterdescription,
    'filter_datefrom'    => $datefromts,
    'filter_dateto'      => $datetots,
];

$urlparams = [
    'filter_eventname'   => $filtereventname,
    'filter_description' => $filterdescription,
    'filter_datefrom'    => $filterdatefrom,
    'filter_dateto'      => $filterdateto,
];

$baseurl = new moodle_url('/local/logreport/index.php', array_filter($urlparams));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('logreport', 'local_logreport'));

// Render filter form.
$filterdata = [
    'actionurl' => (new moodle_url('/local/logreport/index.php'))->out(false),
    'reseturl' => (new moodle_url('/local/logreport/index.php'))->out(false),
    'fields' => [
        ['id' => 'filter_eventname', 'name' => 'filter_eventname', 'label' => get_string('filter_eventname', 'local_logreport'), 'type' => 'text', 'value' => s($filtereventname)],
        ['id' => 'filter_description', 'name' => 'filter_description', 'label' => get_string('filter_description', 'local_logreport'), 'type' => 'text', 'value' => s($filterdescription)],
        ['id' => 'filter_datefrom', 'name' => 'filter_datefrom', 'label' => get_string('filter_datefrom', 'local_logreport'), 'type' => 'date', 'value' => s($filterdatefrom)],
        ['id' => 'filter_dateto', 'name' => 'filter_dateto', 'label' => get_string('filter_dateto', 'local_logreport'), 'type' => 'date', 'value' => s($filterdateto)],
    ],
    'str_filter' => get_string('filter', 'local_logreport'),
    'str_reset' => get_string('resetfilters', 'local_logreport'),
];
echo $OUTPUT->render_from_template('local_logreport/filter_form', $filterdata);

// Render the table.
$table = new \local_logreport\output\logreport_table('local_logreport', $filters);
$table->define_baseurl($baseurl);
$table->out(50, true);

echo $OUTPUT->footer();

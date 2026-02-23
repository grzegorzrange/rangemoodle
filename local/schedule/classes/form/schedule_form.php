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
 * Schedule form.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_schedule\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use local_schedule\schedule;

/**
 * Form for creating/editing a schedule.
 */
class schedule_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $context = $this->_customdata['context'];

        // Hidden ID.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Name (required).
        $mform->addElement('text', 'name', get_string('schedulename', 'local_schedule'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Message - HTML editor (required).
        $mform->addElement(
            'editor',
            'message_editor',
            get_string('schedulecontent', 'local_schedule'),
            ['rows' => 15],
            schedule::editor_options($context)
        );
        $mform->setType('message_editor', PARAM_RAW);
        $mform->addRule('message_editor', get_string('required'), 'required', null, 'client');

        // Direction (required).
        $directions = $DB->get_records_sql(
            'SELECT rc.id, ' . $DB->sql_concat('r.name', "' → '", 'rc.name') . ' AS fullname
               FROM {local_recruitment_course} rc
               JOIN {local_recruitment} r ON r.id = rc.recruitmentid
              ORDER BY r.name, rc.name'
        );
        $options = [];
        foreach ($directions as $d) {
            $options[$d->id] = format_string($d->fullname);
        }
        $mform->addElement('select', 'directionid', get_string('direction', 'local_schedule'), $options);
        $mform->setType('directionid', PARAM_INT);
        $mform->addRule('directionid', get_string('required'), 'required', null, 'client');

        // Send notification checkbox.
        $mform->addElement('advcheckbox', 'sendnotification', get_string('sendnotification', 'local_schedule'));
        $mform->setDefault('sendnotification', 0);

        $this->add_action_buttons();
    }

    /**
     * Validate the form data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty(trim($data['name']))) {
            $errors['name'] = get_string('required');
        }

        return $errors;
    }
}

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
 * Announcement form.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use local_dashboard\announcement;

/**
 * Form for creating/editing an announcement.
 */
class announcement_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $isediting = !empty($this->_customdata['id']);

        // Hidden ID for editing.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Name (required).
        $mform->addElement('text', 'name', get_string('announcementname', 'local_dashboard'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Message - HTML editor (required).
        $mform->addElement(
            'editor',
            'message_editor',
            get_string('announcementtext', 'local_dashboard'),
            ['rows' => 15],
            announcement::editor_options($context)
        );
        $mform->setType('message_editor', PARAM_RAW);
        $mform->addRule('message_editor', get_string('required'), 'required', null, 'client');

        // Direction (required).
        $sql = "SELECT rc.id, CONCAT(r.name, ' — ', rc.name) AS fullname
                  FROM {local_recruitment_course} rc
                  JOIN {local_recruitment} r ON r.id = rc.recruitmentid
                 ORDER BY r.name ASC, rc.name ASC";
        $directions = $DB->get_records_sql($sql);
        $options = [];
        foreach ($directions as $d) {
            $options[$d->id] = format_string($d->fullname);
        }
        $mform->addElement('select', 'directionid', get_string('direction', 'local_dashboard'), $options);
        $mform->setType('directionid', PARAM_INT);
        $mform->addRule('directionid', get_string('required'), 'required', null, 'client');

        // Attachments (optional).
        $mform->addElement(
            'filemanager',
            'attachments',
            get_string('attachments', 'local_dashboard'),
            null,
            announcement::attachment_options()
        );

        // Send notification checkbox (only on create).
        if (!$isediting) {
            $mform->addElement('advcheckbox', 'sendnotification', get_string('sendnotification', 'local_dashboard'));
            $mform->setDefault('sendnotification', 0);
        }

        // Visible checkbox.
        $mform->addElement('advcheckbox', 'visible', get_string('visible', 'local_dashboard'));
        $mform->setDefault('visible', 1);

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

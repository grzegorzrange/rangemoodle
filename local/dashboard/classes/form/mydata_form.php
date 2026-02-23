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
 * My data edit form.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_dashboard\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Form for editing user email and phone.
 */
class mydata_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'email', get_string('email'), ['size' => 40]);
        $mform->setType('email', PARAM_EMAIL);

        $mform->addElement('text', 'phone1', get_string('phone', 'local_dashboard'), ['size' => 20]);
        $mform->setType('phone1', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        global $DB, $CFG, $USER;

        $errors = parent::validation($data, $files);

        // Email validation.
        $email = trim($data['email']);
        if (!empty($email)) {
            if (!validate_email($email)) {
                $errors['email'] = get_string('invalidemail');
            } else if (empty($CFG->allowaccountssameemail)) {
                $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                $params = [
                    'email' => $email,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'userid' => $USER->id,
                ];
                if ($DB->record_exists_select('user', $select, $params)) {
                    $errors['email'] = get_string('emailexists');
                }
            }
        }

        return $errors;
    }
}

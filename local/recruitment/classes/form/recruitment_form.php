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
 * Recruitment form (name + date only).
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

/**
 * Form for creating/editing a recruitment.
 */
class recruitment_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // Hidden ID for editing.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('recruitmentname', 'local_recruitment'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Recruitment end date (month/year only).
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $months[$m] = userdate(mktime(0, 0, 0, $m, 1, 2000), '%B');
        }
        $currentyear = (int)date('Y');
        $years = [];
        for ($y = $currentyear; $y <= $currentyear + 10; $y++) {
            $years[$y] = $y;
        }
        $dategroup = [];
        $dategroup[] = $mform->createElement('select', 'recruitmentmonth', '', $months);
        $dategroup[] = $mform->createElement('select', 'recruitmentyear', '', $years);
        $mform->addGroup($dategroup, 'recruitmentdategroup', get_string('recruitmentdate', 'local_recruitment'), ' ', false);
        $mform->addGroupRule('recruitmentdategroup', [
            'recruitmentmonth' => [[get_string('required'), 'required', null, 'client']],
            'recruitmentyear' => [[get_string('required'), 'required', null, 'client']],
        ]);

        $this->add_action_buttons();
    }

    /**
     * Set form data — convert recruitmentdate timestamp to month/year selects.
     *
     * @param \stdClass|array $data
     */
    public function set_data($data) {
        $data = (object)$data;
        if (!empty($data->recruitmentdate)) {
            $data->recruitmentmonth = (int)date('n', $data->recruitmentdate);
            $data->recruitmentyear = (int)date('Y', $data->recruitmentdate);
        } else {
            $data->recruitmentmonth = (int)date('n');
            $data->recruitmentyear = (int)date('Y');
        }
        parent::set_data($data);
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

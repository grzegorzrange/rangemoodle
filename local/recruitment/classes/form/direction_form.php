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
 * Direction (kurs/kierunek) form.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\form;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/formslib.php');

use local_recruitment\recruitment;

/**
 * Form for creating/editing a direction within a recruitment.
 */
class direction_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // Hidden ID for editing.
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        // Hidden recruitment ID.
        $mform->addElement('hidden', 'recruitmentid', 0);
        $mform->setType('recruitmentid', PARAM_INT);

        // Direction name.
        $mform->addElement('text', 'name', get_string('directionname', 'local_recruitment'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Base category (subcategory of kategorie_bazowe).
        $basecategories = recruitment::get_subcategories_of('kategorie_bazowe');
        $basecategories = [0 => get_string('choose', 'local_recruitment')] + $basecategories;
        $mform->addElement('select', 'basecategoryid', get_string('basecategory', 'local_recruitment'), $basecategories);
        $mform->setType('basecategoryid', PARAM_INT);

        // Freeze base category when editing (courses already copied).
        if (!empty($this->_customdata['id'])) {
            $mform->freeze('basecategoryid');
        }

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

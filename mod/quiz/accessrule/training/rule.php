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

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * Quiz access rule that adds a "Training" setting to the quiz form.
 *
 * @package    quizaccess_training
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_training extends access_rule_base {

    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        return null;
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('selectyesno', 'training',
                get_string('training', 'quizaccess_training'));
        $mform->addHelpButton('training', 'training', 'quizaccess_training');
        $mform->setDefault('training', 0);
    }

    public static function save_settings($quiz) {
        global $DB;
        $record = $DB->get_record('quizaccess_training', ['quizid' => $quiz->id]);
        if (!empty($quiz->training)) {
            if ($record) {
                $record->training = 1;
                $DB->update_record('quizaccess_training', $record);
            } else {
                $DB->insert_record('quizaccess_training', (object)[
                    'quizid' => $quiz->id,
                    'training' => 1,
                ]);
            }
        } else {
            if ($record) {
                $DB->delete_records('quizaccess_training', ['id' => $record->id]);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_training', ['quizid' => $quiz->id]);
    }

    public static function get_settings_sql($quizid) {
        return [
            'COALESCE(qat.training, 0) AS training',
            'LEFT JOIN {quizaccess_training} qat ON qat.quizid = quiz.id',
            [],
        ];
    }
}

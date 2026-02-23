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
 * Quiz access rule that adds an "Internal test" setting to the quiz form.
 *
 * @package    quizaccess_internaltest
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_internaltest extends access_rule_base {

    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        return null;
    }

    public static function add_settings_form_fields(
            mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        $mform->addElement('selectyesno', 'internaltest',
                get_string('internaltest', 'quizaccess_internaltest'));
        $mform->addHelpButton('internaltest', 'internaltest', 'quizaccess_internaltest');
        $mform->setDefault('internaltest', 0);
    }

    public static function save_settings($quiz) {
        global $DB;
        $record = $DB->get_record('quizaccess_internaltest', ['quizid' => $quiz->id]);
        if (!empty($quiz->internaltest)) {
            if ($record) {
                $record->internaltest = 1;
                $DB->update_record('quizaccess_internaltest', $record);
            } else {
                $DB->insert_record('quizaccess_internaltest', (object)[
                    'quizid' => $quiz->id,
                    'internaltest' => 1,
                ]);
            }
        } else {
            if ($record) {
                $DB->delete_records('quizaccess_internaltest', ['id' => $record->id]);
            }
        }
    }

    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('quizaccess_internaltest', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_inttest_notif', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_inttest_results', ['quizid' => $quiz->id]);
    }

    public static function get_settings_sql($quizid) {
        return [
            'COALESCE(qait.internaltest, 0) AS internaltest',
            'LEFT JOIN {quizaccess_internaltest} qait ON qait.quizid = quiz.id',
            [],
        ];
    }
}

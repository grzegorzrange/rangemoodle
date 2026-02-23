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
 * Question type class for the filepicker question type.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');

/**
 * The filepicker question type.
 * File upload only - submission = auto-graded as complete.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_filepicker extends question_type {

    public function is_manual_graded() {
        return false;
    }

    public function response_file_areas() {
        return ['attachments'];
    }

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('qtype_filepicker_options',
                ['questionid' => $question->id], '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_defaults_for_new_questions(stdClass $fromform): void {
        parent::save_defaults_for_new_questions($fromform);
        $this->set_default_value('attachments', $fromform->attachments);
        $this->set_default_value('attachmentsrequired', $fromform->attachmentsrequired);
        $this->set_default_value('maxbytes', $fromform->maxbytes);
    }

    public function save_question_options($formdata) {
        global $DB;

        $options = $DB->get_record('qtype_filepicker_options', ['questionid' => $formdata->id]);
        if (!$options) {
            $options = new stdClass();
            $options->questionid = $formdata->id;
            $options->id = $DB->insert_record('qtype_filepicker_options', $options);
        }

        // Hardcode: no inline text.
        $options->responseformat = 'noinline';
        $options->responserequired = 0;
        $options->responsefieldlines = 0;
        $options->minwordlimit = null;
        $options->maxwordlimit = null;

        // File attachment settings.
        $options->attachments = $formdata->attachments;
        $options->attachmentsrequired = $formdata->attachmentsrequired;
        $options->filetypeslist = $formdata->filetypeslist ?? null;
        $options->maxbytes = $formdata->maxbytes ?? 0;

        // Empty but required by schema.
        $options->graderinfo = '';
        $options->graderinfoformat = FORMAT_HTML;
        $options->responsetemplate = '';
        $options->responsetemplateformat = FORMAT_HTML;

        $DB->update_record('qtype_filepicker_options', $options);
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->responseformat = 'noinline';
        $question->responserequired = 0;
        $question->responsefieldlines = 0;
        $question->minwordlimit = null;
        $question->maxwordlimit = null;
        $question->attachments = $questiondata->options->attachments;
        $question->attachmentsrequired = $questiondata->options->attachmentsrequired;
        $question->graderinfo = '';
        $question->graderinfoformat = 0;
        $question->responsetemplate = '';
        $question->responsetemplateformat = 0;
        $filetypesutil = new \core_form\filetypes_util();
        $question->filetypeslist = $filetypesutil->normalize_file_types($questiondata->options->filetypeslist);
        $question->maxbytes = $questiondata->options->maxbytes;
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('qtype_filepicker_options', ['questionid' => $questionid]);
        parent::delete_question($questionid, $contextid);
    }

    /**
     * @return array the choices that should be offered for the number of attachments.
     */
    public function attachment_options() {
        return [
            1 => '1',
            2 => '2',
            3 => '3',
            -1 => get_string('unlimited'),
        ];
    }

    /**
     * @return array the choices that should be offered for the number of required attachments.
     */
    public function attachments_required_options() {
        return [
            1 => '1',
            2 => '2',
            3 => '3',
        ];
    }

    /**
     * @return array the choices that should be offered for the maximum file sizes.
     */
    public function max_file_size_options() {
        global $CFG, $COURSE;
        return get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes);
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
    }
}

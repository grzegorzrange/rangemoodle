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
 * Filepicker question definition class.
 *
 * A question type that only accepts file uploads.
 * Uploading a file counts as completing the question (auto-graded as full marks).
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/questionbase.php');

/**
 * Represents a filepicker question.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_filepicker_question extends question_graded_automatically {

    /** @var int Number of attachments allowed. -1 = unlimited. */
    public $attachments;

    /** @var int Number of required attachments. */
    public $attachmentsrequired;

    /** @var int Maximum file size in bytes. */
    public $maxbytes;

    /** @var array Accepted file types. */
    public $filetypeslist;

    // These properties are needed for DB compatibility but not used functionally.
    public $responseformat = 'noinline';
    public $responserequired = 0;
    public $responsefieldlines = 0;
    public $minwordlimit = null;
    public $maxwordlimit = null;
    public $graderinfo = '';
    public $graderinfoformat = 0;
    public $responsetemplate = '';
    public $responsetemplateformat = 0;

    public function get_expected_data() {
        return ['attachments' => question_attempt::PARAM_FILES];
    }

    public function summarise_response(array $response) {
        if (isset($response['attachments']) && $response['attachments'] instanceof question_response_files) {
            $attachedfiles = [];
            foreach ($response['attachments']->get_files() as $file) {
                $attachedfiles[] = $file->get_filename() . ' (' . display_size($file->get_filesize()) . ')';
            }
            if ($attachedfiles) {
                return get_string('attachedfiles', 'qtype_filepicker', implode(', ', $attachedfiles));
            }
        }
        return '';
    }

    public function un_summarise_response(string $summary) {
        return [];
    }

    public function get_correct_response() {
        return null;
    }

    public function is_complete_response(array $response) {
        $hasattachments = isset($response['attachments'])
            && $response['attachments'] instanceof question_response_files;

        if (!$hasattachments) {
            return false;
        }

        // Check file types if restricted.
        $filetypesutil = new \core_form\filetypes_util();
        $allowlist = $filetypesutil->normalize_file_types($this->filetypeslist);
        if (!empty($allowlist)) {
            foreach ($response['attachments']->get_files() as $file) {
                if (!$filetypesutil->is_allowed_file_type($file->get_filename(), $allowlist)) {
                    return false;
                }
            }
        }

        $attachcount = count($response['attachments']->get_files());
        return $attachcount >= $this->attachmentsrequired;
    }

    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('mustrequire', 'qtype_filepicker');
    }

    public function is_gradable_response(array $response) {
        if (isset($response['attachments']) && $response['attachments'] instanceof question_response_files) {
            return count($response['attachments']->get_files()) > 0;
        }
        return false;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
            $prevresponse, $newresponse, 'attachments');
    }

    /**
     * Grade the response. File submission = full marks.
     *
     * @param array $response
     * @return array [fraction, state]
     */
    public function grade_response(array $response) {
        if ($this->is_complete_response($response)) {
            return [1, question_state::$gradedright];
        }
        return [0, question_state::$gradedwrong];
    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {
        if ($component == 'question' && $filearea == 'response_attachments') {
            return true;
        }
        return parent::check_file_access($qa, $options, $component,
                $filearea, $args, $forcedownload);
    }

    public function get_question_definition_for_external_rendering(question_attempt $qa, question_display_options $options) {
        return [
            'attachments' => $this->attachments,
            'attachmentsrequired' => $this->attachmentsrequired,
            'maxbytes' => $this->maxbytes,
            'filetypeslist' => $this->filetypeslist,
        ];
    }
}

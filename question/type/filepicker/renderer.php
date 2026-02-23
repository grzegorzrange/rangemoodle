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
 * Filepicker question renderer class.
 *
 * Simplified to only show file upload controls - no text input areas.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generates the output for filepicker questions.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_filepicker_renderer extends qtype_renderer {

    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {
        $question = $qa->get_question();

        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa),
                ['class' => 'qtext']);

        $result .= html_writer::start_tag('div', ['class' => 'ablock']);

        // File upload area.
        if (empty($options->readonly)) {
            $files = $this->files_input($qa, $question->attachments, $options);
        } else {
            $files = $this->files_read_only($qa, $options);
        }
        $result .= html_writer::tag('div', $files, ['class' => 'attachments']);

        // Validation error.
        if ($qa->get_state() == question_state::$invalid) {
            $step = $qa->get_last_step_with_qt_var('attachments');
            $result .= html_writer::nonempty_tag('div',
                $question->get_validation_error($step->get_qt_data()), ['class' => 'validationerror']);
        }

        $result .= html_writer::end_tag('div');

        return $result;
    }

    /**
     * Displays attached files when the question is in read-only mode.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) {
        $files = $qa->get_last_qt_files('attachments', $options->context->id);
        $filelist = [];

        foreach ($files as $file) {
            $out = html_writer::link($qa->get_response_file_url($file),
                $this->output->pix_icon(file_file_icon($file), get_mimetype_description($file),
                    'moodle', ['class' => 'icon']) . ' ' . s($file->get_filename()));
            $filelist[] = html_writer::tag('li', $out, ['class' => 'mb-2']);
        }

        $labelbyid = $qa->get_qt_field_name('attachments') . '_label';
        $fileslabel = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_filepicker'));
        $output = html_writer::tag('h4', $fileslabel, ['id' => $labelbyid, 'class' => 'visually-hidden']);
        $output .= html_writer::tag('ul', implode($filelist), [
            'aria-labelledby' => $labelbyid,
            'class' => 'list-unstyled m-0',
        ]);
        return $output;
    }

    /**
     * Displays the file upload input control.
     */
    public function files_input(question_attempt $qa, $numallowed,
            question_display_options $options) {
        global $CFG, $COURSE;
        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $numallowed;
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid(
                'attachments', $options->context->id);
        $pickeroptions->context = $options->context;
        $pickeroptions->return_types = FILE_INTERNAL | FILE_CONTROLLED_LINK;
        $pickeroptions->accepted_types = $qa->get_question()->filetypeslist;

        $fm = new form_filemanager($pickeroptions);
        $fm->options->maxbytes = get_user_max_upload_file_size(
            $this->page->context,
            $CFG->maxbytes,
            $COURSE->maxbytes,
            $qa->get_question()->maxbytes
        );
        $filesrenderer = $this->page->get_renderer('core', 'files');

        $text = '';
        if (!empty($qa->get_question()->filetypeslist)) {
            $text = html_writer::tag('p', get_string('acceptedfiletypes', 'qtype_filepicker'));
            $filetypesutil = new \core_form\filetypes_util();
            $filetypes = $qa->get_question()->filetypeslist;
            $filetypedescriptions = $filetypesutil->describe_file_types($filetypes);
            $text .= $this->render_from_template('core_form/filetypes-descriptions', $filetypedescriptions);
        }

        $output = html_writer::start_tag('fieldset');
        $fileslabel = $options->add_question_identifier_to_label(get_string('answerfiles', 'qtype_filepicker'));
        $output .= html_writer::tag('legend', $fileslabel, ['class' => 'visually-hidden']);
        $output .= $filesrenderer->render($fm);
        $output .= html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => $qa->get_qt_field_name('attachments'),
            'value' => $pickeroptions->itemid,
        ]);
        $output .= $text;
        $output .= html_writer::end_tag('fieldset');

        return $output;
    }
}

/**
 * A base class to abstract out the differences between different type of
 * response format. Kept for compatibility but only noinline is used.
 */
abstract class qtype_filepicker_format_renderer_base extends plugin_renderer_base {
    protected $displayoptions;

    public function set_displayoptions(question_display_options $displayoptions): void {
        $this->displayoptions = $displayoptions;
    }

    abstract public function response_area_read_only($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    abstract public function response_area_input($name, question_attempt $qa,
            question_attempt_step $step, $lines, $context);

    abstract protected function class_name();
}

/**
 * No inline response renderer - the only format used by filepicker.
 */
class qtype_filepicker_format_noinline_renderer extends qtype_filepicker_format_renderer_base {
    protected function class_name() {
        return 'qtype_filepicker_noinline';
    }

    public function response_area_read_only($name, $qa, $step, $lines, $context) {
        return '';
    }

    public function response_area_input($name, $qa, $step, $lines, $context) {
        return '';
    }
}

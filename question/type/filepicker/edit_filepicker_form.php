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
 * Defines the editing form for the filepicker question type.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Filepicker question type editing form.
 * Simplified to only accept file uploads.
 *
 * @package    qtype_filepicker
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_filepicker_edit_form extends question_edit_form {

    protected function definition_inner($mform) {
        $qtype = question_bank::get_qtype('filepicker');

        $mform->addElement('header', 'responseoptions', get_string('responseoptions', 'qtype_filepicker'));
        $mform->setExpanded('responseoptions');

        // Hidden fields - hardcode no inline text.
        $mform->addElement('hidden', 'responseformat', 'noinline');
        $mform->setType('responseformat', PARAM_ALPHA);
        $mform->addElement('hidden', 'responserequired', 0);
        $mform->setType('responserequired', PARAM_INT);
        $mform->addElement('hidden', 'responsefieldlines', 0);
        $mform->setType('responsefieldlines', PARAM_INT);

        // Number of allowed attachments.
        $mform->addElement('select', 'attachments',
                get_string('allowattachments', 'qtype_filepicker'), $qtype->attachment_options());
        $mform->setDefault('attachments', $this->get_default_value('attachments', -1));

        // Number of required attachments.
        $mform->addElement('select', 'attachmentsrequired',
                get_string('attachmentsrequired', 'qtype_filepicker'), $qtype->attachments_required_options());
        $mform->setDefault('attachmentsrequired', $this->get_default_value('attachmentsrequired', 1));
        $mform->addHelpButton('attachmentsrequired', 'attachmentsrequired', 'qtype_filepicker');

        // Accepted file types.
        $mform->addElement('filetypes', 'filetypeslist', get_string('acceptedfiletypes', 'qtype_filepicker'));
        $mform->addHelpButton('filetypeslist', 'acceptedfiletypes', 'qtype_filepicker');

        // Maximum file size.
        $mform->addElement('select', 'maxbytes', get_string('maxbytes', 'qtype_filepicker'), $qtype->max_file_size_options());
        $mform->setDefault('maxbytes', $this->get_default_value('maxbytes', 0));

        // Hidden graderinfo and responsetemplate fields - required by DB schema.
        $mform->addElement('hidden', 'graderinfo[text]', '');
        $mform->setType('graderinfo[text]', PARAM_RAW);
        $mform->addElement('hidden', 'graderinfo[format]', FORMAT_HTML);
        $mform->setType('graderinfo[format]', PARAM_INT);
        $mform->addElement('hidden', 'responsetemplate[text]', '');
        $mform->setType('responsetemplate[text]', PARAM_RAW);
        $mform->addElement('hidden', 'responsetemplate[format]', FORMAT_HTML);
        $mform->setType('responsetemplate[format]', PARAM_INT);
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);

        if (empty($question->options)) {
            return $question;
        }

        $question->responseformat = 'noinline';
        $question->responserequired = 0;
        $question->responsefieldlines = 0;
        $question->attachments = $question->options->attachments;
        $question->attachmentsrequired = $question->options->attachmentsrequired;
        $question->filetypeslist = $question->options->filetypeslist;
        $question->maxbytes = $question->options->maxbytes;

        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        // Must allow at least one attachment.
        if (empty($fromform['attachments'])) {
            $errors['attachments'] = get_string('mustattach', 'qtype_filepicker');
        }

        // Must require at least one attachment.
        if (empty($fromform['attachmentsrequired'])) {
            $errors['attachmentsrequired'] = get_string('mustrequire', 'qtype_filepicker');
        }

        // Cannot require more attachments than allowed.
        if ($fromform['attachments'] > 0 && $fromform['attachments'] < $fromform['attachmentsrequired']) {
            $errors['attachmentsrequired'] = get_string('mustrequirefewer', 'qtype_filepicker');
        }

        return $errors;
    }

    public function qtype() {
        return 'filepicker';
    }
}

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
 * Declaration block.
 *
 * @package    block_declaration
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Block class for declaration link.
 */
class block_declaration extends block_base {

    /**
     * Init block.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_declaration');
    }

    /**
     * Get content.
     *
     * @return stdClass|null
     */
    public function get_content() {
        global $DB, $USER, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        if (!isloggedin() || isguestuser()) {
            return $this->content;
        }

        $directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;
        if (empty($directionid)) {
            return $this->content;
        }

        // Check if user has declaration=1 for the active direction.
        $record = $DB->get_record('local_recruitment_user', [
            'userid' => $USER->id,
            'directionid' => $directionid,
        ]);

        if (!$record || empty($record->declaration)) {
            return $this->content;
        }

        // Get the direction's basecategoryid and its idnumber.
        $direction = $DB->get_record('local_recruitment_course', ['id' => $directionid]);
        if (!$direction || empty($direction->basecategoryid)) {
            return $this->content;
        }

        $category = $DB->get_record('course_categories', ['id' => $direction->basecategoryid]);
        if (!$category) {
            return $this->content;
        }

        $catidnumber = $category->idnumber;
        $linkurl = 'https://egzaminy.webrange0.usermd.net/wybor-egzaminow/?recruitment=' . urlencode($catidnumber);

        $this->content->text = html_writer::link(
            $linkurl,
            get_string('gotodeclaration', 'block_declaration'),
            ['class' => 'btn btn-primary btn-block w-100', 'target' => '_blank']
        );

        return $this->content;
    }

    /**
     * Applicable formats.
     *
     * @return array
     */
    public function applicable_formats() {
        return [
            'my' => true,
            'site-index' => true,
            'all' => false,
        ];
    }

    /**
     * Allow multiple instances.
     *
     * @return bool
     */
    public function instance_allow_multiple() {
        return false;
    }
}

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
 * Change recruitment block - provides a button to switch active recruitment.
 *
 * @package    block_changerecruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_changerecruitment extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_changerecruitment');
    }

    public function applicable_formats() {
        return ['my' => true, 'site-index' => true];
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $url = new moodle_url('/local/dashboard/index.php', ['change' => 1]);

        $this->content->text = html_writer::div(
            html_writer::link(
                $url,
                get_string('changerecruitment', 'block_changerecruitment'),
                ['class' => 'btn btn-outline-primary btn-block w-100']
            ),
            'text-center p-2'
        );

        return $this->content;
    }
}

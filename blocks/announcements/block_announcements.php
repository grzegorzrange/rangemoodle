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
 * Announcements block - displays announcements for the active recruitment.
 *
 * @package    block_announcements
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_announcements extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_announcements');
    }

    public function applicable_formats() {
        return ['my' => true, 'site-index' => true];
    }

    public function get_content() {
        global $SESSION, $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        // Get active direction ID from session.
        $directionid = !empty($SESSION->active_direction_id) ? (int)$SESSION->active_direction_id : 0;

        if (!$directionid) {
            $this->content->text = get_string('noactiverecruitment', 'block_announcements');
            return $this->content;
        }

        $page = 0;
        $perpage = 3;
        $context = context_system::instance();

        $result = \local_dashboard\announcement::get_for_direction($directionid, $page, $perpage);
        $records = $result['records'];
        $total = $result['total'];

        if (empty($records)) {
            $this->content->text = get_string('noannouncements', 'block_announcements');
            return $this->content;
        }

        $tiles = [];
        foreach ($records as $record) {
            $plaintext = html_to_text($record->message, 0, false);
            if (core_text::strlen($plaintext) > 200) {
                $plaintext = core_text::substr($plaintext, 0, 200) . '...';
            }

            $attachcount = \local_dashboard\announcement::count_attachments($record->id, $context);

            $viewurl = new moodle_url('/local/dashboard/announcement_view.php', ['id' => $record->id]);

            $tiles[] = [
                'name' => format_string($record->name),
                'text' => s($plaintext),
                'date' => userdate($record->timecreated, get_string('strftimedaydatetime', 'langconfig')),
                'attachcount' => $attachcount,
                'hasattachments' => $attachcount > 0,
                'viewurl' => $viewurl->out(false),
            ];
        }

        $totalpages = ceil($total / $perpage);
        $templatedata = [
            'tiles' => $tiles,
            'hastiles' => !empty($tiles),
        ];

        if ($totalpages > 1) {
            $pages = [];
            for ($i = 0; $i < $totalpages; $i++) {
                $pages[] = [
                    'pagenum' => $i + 1,
                    'page' => $i,
                    'active' => ($i == $page),
                ];
            }
            $templatedata['haspagination'] = true;
            $templatedata['pages'] = $pages;
        }

        $innerhtml = $OUTPUT->render_from_template('block_announcements/content', $templatedata);
        $this->content->text = '<div data-region="block-announcements">' . $innerhtml . '</div>';

        // Load AJAX paging JS.
        $PAGE->requires->js_call_amd('block_announcements/paging', 'init', [$directionid, $page]);

        return $this->content;
    }
}

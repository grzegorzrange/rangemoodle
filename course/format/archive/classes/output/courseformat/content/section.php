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
 * Contains the default section controls output class.
 *
 * @package   format_archive
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_archive\output\courseformat\content;

use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section as section_base;
use stdClass;

/**
 * Base class to render a course section.
 *
 * @package   format_archive
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /** @var course_format the course format */
    protected $format;

    /**
     * Sections are collapsed by default in archive format.
     * If the user has explicitly expanded a section, respect that preference.
     *
     * @return bool
     */
    protected function is_section_collapsed(): bool {
        global $PAGE;

        // If user explicitly expanded via URL parameter, show expanded.
        $expandsection = $PAGE->url->get_param('expandsection');
        if ($expandsection !== null && $this->section->section == $expandsection) {
            return false;
        }

        // Check user preferences — if user explicitly expanded, respect it.
        $preferences = $this->format->get_sections_preferences();
        if (isset($preferences[$this->section->id])) {
            $sectionpreferences = $preferences[$this->section->id];
            // If contentcollapsed is explicitly set to false, the user expanded it.
            if (isset($sectionpreferences->contentcollapsed) && empty($sectionpreferences->contentcollapsed)) {
                return false;
            }
        }

        // Default: collapsed.
        return true;
    }

    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;

        $data = parent::export_for_template($output);

        if (!$this->format->get_sectionnum() && !$this->section->get_component_instance()) {
            $addsectionclass = $format->get_output_classname('content\\addsection');
            $addsection = new $addsectionclass($format, $this->section);
            $data->numsections = $addsection->export_for_template($output);
            $data->insertafter = true;
        }

        return $data;
    }
}

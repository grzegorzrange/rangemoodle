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
 * Recruitment deleted event.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_recruitment\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event fired when a recruitment is deleted.
 */
class recruitment_deleted extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_recruitment';
    }

    public static function get_name() {
        return get_string('eventrecruitmentdeleted', 'local_recruitment');
    }

    public function get_description() {
        $name = $this->other['name'] ?? '';
        return "The user with id '{$this->userid}' deleted recruitment '{$name}' with id '{$this->objectid}'.";
    }
}

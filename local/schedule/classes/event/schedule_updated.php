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
 * Schedule updated event.
 *
 * @package    local_schedule
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_schedule\event;

defined('MOODLE_INTERNAL') || die();

class schedule_updated extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_schedule';
    }

    public static function get_name() {
        return get_string('eventscheduleupdated', 'local_schedule');
    }

    public function get_description() {
        $name = $this->other['name'] ?? '';
        return "The user with id '{$this->userid}' updated schedule '{$name}' with id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/schedule/edit.php', ['id' => $this->objectid]);
    }
}

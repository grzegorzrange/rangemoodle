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
 * WordPress sync event.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_support\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when user data is sent to WordPress.
 */
class wp_sync_sent extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('event_wp_sync_sent', 'local_support');
    }

    public function get_description() {
        $success = !empty($this->other['success']) ? 'successfully' : 'unsuccessfully';
        $action = $this->other['action'] ?? 'unknown';
        $endpoint = $this->other['endpoint'] ?? 'unknown';
        return "User data {$success} sent to WordPress for user {$this->relateduserid} " .
            "(action: {$action}, endpoint: {$endpoint}). Payload: " . ($this->other['payload'] ?? '');
    }

    public static function get_other_mapping() {
        return false;
    }
}

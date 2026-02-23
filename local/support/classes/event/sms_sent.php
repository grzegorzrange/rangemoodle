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
 * SMS sent event.
 *
 * @package    local_support
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_support\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when an SMS is sent via sms_service.
 */
class sms_sent extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return get_string('event_sms_sent', 'local_support');
    }

    public function get_description() {
        $success = $this->other['success'] ? 'successfully' : 'unsuccessfully';
        $phone = $this->other['phone'] ?? '?';
        $component = $this->other['component'] ?? 'unknown';
        return "SMS {$success} sent to user {$this->relateduserid} (phone: {$phone}) " .
            "by component '{$component}': " . ($this->other['message'] ?? '');
    }

    public static function get_other_mapping() {
        return false;
    }
}

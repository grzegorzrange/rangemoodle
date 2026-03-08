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
 * Log report table definition.
 *
 * Shows:
 * - Content changes in local plugins (create/update/delete events).
 * - All site-admin actions on the platform.
 * - Quiz question import events.
 *
 * @package    local_logreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_logreport\output;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->libdir . '/tablelib.php');

/**
 * Table class for the log report.
 */
class logreport_table extends \table_sql {

    /** @var array Filter values. */
    private array $filters;

    /**
     * Constructor.
     *
     * @param string $uniqueid Unique ID for the table.
     * @param array $filters Associative array of filter values.
     */
    public function __construct(string $uniqueid, array $filters = []) {
        parent::__construct($uniqueid);
        $this->filters = $filters;

        $columns = ['eventname', 'description', 'timecreated'];
        $headers = [
            get_string('eventname', 'local_logreport'),
            get_string('description', 'local_logreport'),
            get_string('timecreated', 'local_logreport'),
        ];

        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->sortable(true, 'timecreated', SORT_DESC);

        $this->collapsible(false);
        $this->pageable(true);

        $this->set_attribute('class', 'generaltable generalbox');

        $this->setup_sql();
    }

    /**
     * Build and set the SQL query with filters.
     */
    private function setup_sql(): void {
        global $CFG, $DB;

        // Build a sortable description proxy from component + action + target.
        $descriptionexpr = $DB->sql_concat('l.component', "' '", 'l.action', "' '", 'l.target');

        $fields = "l.id, l.eventname, l.component, l.action, l.target, l.objecttable, l.objectid,
                   l.crud, l.edulevel, l.anonymous,
                   l.contextid, l.contextlevel, l.contextinstanceid, l.userid, l.courseid,
                   l.relateduserid, l.other, l.timecreated, l.origin, l.ip,
                   u.firstname, u.lastname,
                   {$descriptionexpr} AS description";

        $from = "{logstore_standard_log} l LEFT JOIN {user} u ON u.id = l.userid";

        // Build the WHERE clause combining all three scopes:
        // 1. Local plugin content change events (component starts with 'local_').
        // 2. All admin user actions.
        // 3. Quiz question import events.
        $conditions = [];
        $params = [];

        // Scope 1: local plugin events.
        $locallike = $DB->sql_like('l.component', ':localcomp', false);
        $conditions[] = $locallike;
        $params['localcomp'] = 'local_%';

        // Scope 2: admin user actions.
        $adminids = explode(',', $CFG->siteadmins);
        $adminids = array_filter(array_map('intval', $adminids));
        if (!empty($adminids)) {
            list($adminsql, $adminparams) = $DB->get_in_or_equal($adminids, SQL_PARAMS_NAMED, 'adm');
            $conditions[] = "l.userid {$adminsql}";
            $params = array_merge($params, $adminparams);
        }

        // Scope 3: question import events.
        $conditions[] = "l.eventname = :qimport";
        $params['qimport'] = '\\core\\event\\questions_imported';

        $where = "(" . implode(" OR ", $conditions) . ")";

        // Apply user filters.
        if (!empty($this->filters['filter_eventname'])) {
            $where .= " AND " . $DB->sql_like('l.eventname', ':filter_eventname', false);
            $params['filter_eventname'] = '%' . $DB->sql_like_escape($this->filters['filter_eventname']) . '%';
        }

        if (!empty($this->filters['filter_description'])) {
            $where .= " AND " . $DB->sql_like($descriptionexpr, ':filter_description', false);
            $params['filter_description'] = '%' . $DB->sql_like_escape($this->filters['filter_description']) . '%';
        }

        if (!empty($this->filters['filter_datefrom'])) {
            $where .= " AND l.timecreated >= :datefrom";
            $params['datefrom'] = (int)$this->filters['filter_datefrom'];
        }

        if (!empty($this->filters['filter_dateto'])) {
            $where .= " AND l.timecreated <= :dateto";
            $params['dateto'] = (int)$this->filters['filter_dateto'];
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM {logstore_standard_log} l LEFT JOIN {user} u ON u.id = l.userid WHERE $where", $params);
    }

    /**
     * Restore an event object from a log row.
     *
     * @param \stdClass $row The log row.
     * @return \core\event\base|null
     */
    private function restore_event(\stdClass $row): ?\core\event\base {
        try {
            $extra = [
                'origin' => $row->origin ?? '',
                'ip' => $row->ip ?? '',
                'realuserid' => 0,
            ];

            $other = $row->other ?? null;
            if ($other === null || $other === '' || $other === 'N;') {
                $otherdata = null;
            } else if (preg_match('~^[aOibs][:;]~', $other)) {
                $otherdata = @unserialize($other, ['allowed_classes' => [\stdClass::class]]);
            } else {
                $otherdata = json_decode($other, true);
            }

            // Build $data with only the keys that event::restore() expects.
            $data = [
                'eventname' => $row->eventname,
                'component' => $row->component ?? '',
                'action' => $row->action ?? '',
                'target' => $row->target ?? '',
                'objecttable' => $row->objecttable ?? '',
                'objectid' => $row->objectid ?? null,
                'crud' => $row->crud ?? 'r',
                'edulevel' => $row->edulevel ?? 0,
                'contextid' => $row->contextid ?? 0,
                'contextlevel' => $row->contextlevel ?? 0,
                'contextinstanceid' => $row->contextinstanceid ?? 0,
                'userid' => $row->userid ?? 0,
                'courseid' => $row->courseid ?? 0,
                'relateduserid' => $row->relateduserid ?? null,
                'anonymous' => $row->anonymous ?? 0,
                'other' => $otherdata,
                'timecreated' => $row->timecreated ?? 0,
            ];

            return \core\event\base::restore($data, $extra);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** @var array Component name translation cache. */
    private static array $componentcache = [
        'local_recruitment' => 'component_local_recruitment',
        'local_dashboard' => 'component_local_dashboard',
        'local_financial' => 'component_local_financial',
        'local_organizational' => 'component_local_organizational',
        'local_schedule' => 'component_local_schedule',
        'core' => 'component_core',
    ];

    /** @var array Target translation cache. */
    private static array $targetcache = [
        'recruitment' => 'target_recruitment',
        'announcement' => 'target_announcement',
        'financial' => 'target_financial',
        'organizational' => 'target_organizational',
        'schedule' => 'target_schedule',
        'course' => 'target_course',
        'user' => 'target_user',
        'role' => 'target_role',
        'category' => 'target_category',
        'cohort' => 'target_cohort',
        'questions' => 'target_questions',
    ];

    /**
     * Translate component name.
     *
     * @param string $component
     * @return string
     */
    private function translate_component(string $component): string {
        if (isset(self::$componentcache[$component])) {
            return get_string(self::$componentcache[$component], 'local_logreport');
        }
        // Try to get a human-readable name from Moodle.
        try {
            return \core_component::get_component_string($component);
        } catch (\Throwable $e) {
            return $component;
        }
    }

    /**
     * Translate target name.
     *
     * @param string $target
     * @return string
     */
    private function translate_target(string $target): string {
        if (isset(self::$targetcache[$target])) {
            return get_string(self::$targetcache[$target], 'local_logreport');
        }
        return $target;
    }

    /**
     * Translate CRUD action.
     *
     * @param string $crud
     * @return string
     */
    private function translate_crud(string $crud): string {
        $map = ['c' => 'crud_c', 'r' => 'crud_r', 'u' => 'crud_u', 'd' => 'crud_d'];
        if (isset($map[$crud])) {
            return get_string($map[$crud], 'local_logreport');
        }
        return $crud;
    }

    /**
     * Build Polish description for a log row.
     *
     * @param \stdClass $row
     * @return string
     */
    private function build_description(\stdClass $row): string {
        global $DB;

        $username = '';
        if (!empty($row->firstname) || !empty($row->lastname)) {
            $username = trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? ''));
        }
        if (empty($username) && !empty($row->userid)) {
            $username = get_string('unknownuser') . " (ID: {$row->userid})";
        }

        // Special case: question import.
        if ($row->eventname === '\\core\\event\\questions_imported') {
            $a = (object)['user' => $username];
            return get_string('eventdesc_questions_imported', 'local_logreport', $a);
        }

        $component = $this->translate_component($row->component ?? '');
        $target = $this->translate_target($row->target ?? '');
        $action = $this->translate_crud($row->crud ?? 'r');

        $a = (object)[
            'user' => $username,
            'action' => $action,
            'target' => $target,
            'component' => $component,
            'course' => '',
            'module' => '',
        ];

        // Get course name if available.
        if (!empty($row->courseid) && $row->courseid > 1) {
            $coursename = $DB->get_field('course', 'fullname', ['id' => $row->courseid]);
            if ($coursename) {
                $a->course = $coursename;
            }
        }

        // Get module name if in module context.
        if (!empty($row->contextinstanceid) && !empty($row->contextlevel) && $row->contextlevel == CONTEXT_MODULE) {
            $cm = $DB->get_record('course_modules', ['id' => $row->contextinstanceid], 'id, instance, module');
            if ($cm) {
                $moduletype = $DB->get_field('modules', 'name', ['id' => $cm->module]);
                if ($moduletype) {
                    $name = $DB->get_field($moduletype, 'name', ['id' => $cm->instance]);
                    if ($name) {
                        $a->module = $name;
                    }
                }
            }
        }

        // For local plugin CRUD events, use specific templates.
        if (strpos($row->component ?? '', 'local_') === 0) {
            $crud = $row->crud ?? 'r';
            $crudmap = ['c' => 'eventdesc_created', 'u' => 'eventdesc_updated', 'd' => 'eventdesc_deleted', 'r' => 'eventdesc_viewed'];
            if (isset($crudmap[$crud])) {
                return get_string($crudmap[$crud], 'local_logreport', $a);
            }
        }

        // Generic templates.
        if (!empty($a->module)) {
            return get_string('eventdesc_generic_module', 'local_logreport', $a);
        }
        if (!empty($a->course)) {
            return get_string('eventdesc_generic_course', 'local_logreport', $a);
        }
        return get_string('eventdesc_generic', 'local_logreport', $a);
    }

    /**
     * Render the event name column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_eventname(\stdClass $row): string {
        $event = $this->restore_event($row);
        if ($event) {
            try {
                return $event->get_name();
            } catch (\Throwable $e) {
                // Fall through.
            }
        }
        return str_replace('\\', ' \\ ', ltrim($row->eventname, '\\'));
    }

    /**
     * Render the description column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_description(\stdClass $row): string {
        $desc = $this->build_description($row);
        $desc = strip_tags($desc);
        if (\core_text::strlen($desc) > 300) {
            $desc = \core_text::substr($desc, 0, 300) . '...';
        }
        return $desc;
    }

    /**
     * Render the time column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated(\stdClass $row): string {
        return userdate($row->timecreated);
    }
}

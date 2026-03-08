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
 * Activity report table definition.
 *
 * @package    local_activityreport
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_activityreport\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table class for the activity report, extending table_sql.
 */
class activityreport_table extends \table_sql {

    /** @var array Event class names to include in the report. */
    private const EVENT_WHITELIST = [
        '\\core\\event\\user_loggedin',
        '\\mod_resource\\event\\course_module_viewed',
        '\\mod_page\\event\\course_module_viewed',
        '\\mod_url\\event\\course_module_viewed',
        '\\mod_folder\\event\\course_module_viewed',
        '\\mod_book\\event\\course_module_viewed',
        '\\mod_lesson\\event\\course_module_viewed',
        '\\core\\event\\course_module_completion_updated',
        '\\core\\event\\course_completed',
        '\\mod_quiz\\event\\attempt_started',
        '\\mod_quiz\\event\\attempt_submitted',
        '\\mod_quiz\\event\\attempt_reviewed',
        '\\mod_quiz\\event\\attempt_viewed',
    ];

    /**
     * Mapping from event action key to lang string identifier.
     * The key is derived from the last part of the event class name.
     */
    private const EVENT_DESC_MAP = [
        'user_loggedin' => 'eventdesc_user_loggedin',
        'course_module_viewed' => 'eventdesc_course_module_viewed',
        'course_module_completion_updated' => 'eventdesc_course_module_completion_updated',
        'course_completed' => 'eventdesc_course_completed',
        'attempt_started' => 'eventdesc_attempt_started',
        'attempt_submitted' => 'eventdesc_attempt_submitted',
        'attempt_reviewed' => 'eventdesc_attempt_reviewed',
        'attempt_viewed' => 'eventdesc_attempt_viewed',
    ];

    /** @var array Mapping from event action key to lang string for event name. */
    private const EVENT_NAME_MAP = [
        'user_loggedin' => 'eventname_user_loggedin',
        'course_module_viewed' => 'eventname_course_module_viewed',
        'course_module_completion_updated' => 'eventname_course_module_completion_updated',
        'course_completed' => 'eventname_course_completed',
        'attempt_started' => 'eventname_attempt_started',
        'attempt_submitted' => 'eventname_attempt_submitted',
        'attempt_reviewed' => 'eventname_attempt_reviewed',
        'attempt_viewed' => 'eventname_attempt_viewed',
    ];

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

        $columns = ['firstname', 'lastname', 'email', 'eventname', 'description', 'timecreated'];
        $headers = [
            get_string('firstname', 'local_activityreport'),
            get_string('lastname', 'local_activityreport'),
            get_string('email', 'local_activityreport'),
            get_string('eventname', 'local_activityreport'),
            get_string('description', 'local_activityreport'),
            get_string('timecreated', 'local_activityreport'),
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
        global $DB;

        // Build event whitelist params.
        $eventparams = [];
        $eventplaceholders = [];
        foreach (self::EVENT_WHITELIST as $i => $eventclass) {
            $paramname = 'evt' . $i;
            $eventplaceholders[] = ':' . $paramname;
            $eventparams[$paramname] = $eventclass;
        }
        $eventin = implode(', ', $eventplaceholders);

        // Use sql_concat to build a sortable description proxy from component + action + target.
        $descriptionexpr = $DB->sql_concat('l.component', "' '", 'l.action', "' '", 'l.target');

        $fields = "l.id, l.eventname, l.component, l.action, l.target, l.objecttable, l.objectid,
                   l.crud, l.edulevel, l.anonymous,
                   l.contextid, l.contextlevel, l.contextinstanceid, l.userid, l.courseid,
                   l.relateduserid, l.other, l.timecreated, l.origin, l.ip,
                   u.firstname, u.lastname, u.email,
                   {$descriptionexpr} AS description";

        $from = "{logstore_standard_log} l JOIN {user} u ON u.id = l.userid";

        $where = "l.eventname IN ($eventin)";
        $params = $eventparams;

        // Apply text filters.
        $filtermap = [
            'filter_firstname' => 'u.firstname',
            'filter_lastname'  => 'u.lastname',
            'filter_email'     => 'u.email',
            'filter_eventname' => 'l.eventname',
        ];

        foreach ($filtermap as $filterkey => $dbfield) {
            if (!empty($this->filters[$filterkey])) {
                $paramname = $filterkey;
                $where .= " AND " . $DB->sql_like($dbfield, ':' . $paramname, false);
                $params[$paramname] = '%' . $DB->sql_like_escape($this->filters[$filterkey]) . '%';
            }
        }

        // Description filter — search across component, action, target.
        if (!empty($this->filters['filter_description'])) {
            $desclike = $DB->sql_like($descriptionexpr, ':filter_description', false);
            $where .= " AND " . $desclike;
            $params['filter_description'] = '%' . $DB->sql_like_escape($this->filters['filter_description']) . '%';
        }

        // Date filters.
        if (!empty($this->filters['filter_datefrom'])) {
            $where .= " AND l.timecreated >= :datefrom";
            $params['datefrom'] = (int)$this->filters['filter_datefrom'];
        }
        if (!empty($this->filters['filter_dateto'])) {
            $where .= " AND l.timecreated <= :dateto";
            $params['dateto'] = (int)$this->filters['filter_dateto'];
        }

        $this->set_sql($fields, $from, $where, $params);
        $this->set_count_sql("SELECT COUNT(1) FROM {logstore_standard_log} l JOIN {user} u ON u.id = l.userid WHERE $where", $params);
    }

    /**
     * Restore an event object from a log row.
     *
     * @param \stdClass $row The log row.
     * @return \core\event\base|null The restored event, or null on failure.
     */
    private function restore_event(\stdClass $row): ?\core\event\base {
        try {
            $extra = [
                'origin' => $row->origin ?? '',
                'ip' => $row->ip ?? '',
                'realuserid' => 0,
            ];

            // Decode 'other' field.
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

    /**
     * Extract the event action key from the full eventname class.
     *
     * E.g. '\\core\\event\\user_loggedin' => 'user_loggedin'
     *      '\\mod_quiz\\event\\attempt_started' => 'attempt_started'
     *      '\\mod_resource\\event\\course_module_viewed' => 'course_module_viewed'
     *
     * @param string $eventname
     * @return string
     */
    private function get_event_key(string $eventname): string {
        $parts = explode('\\', trim($eventname, '\\'));
        return end($parts);
    }

    /**
     * Get user fullname, course name, and module name for description placeholders.
     *
     * @param \stdClass $row
     * @return object {user, course, module}
     */
    private function get_description_params(\stdClass $row): object {
        global $DB;

        $a = new \stdClass();
        $a->user = trim(($row->firstname ?? '') . ' ' . ($row->lastname ?? ''));
        $a->course = '';
        $a->module = '';

        // Get course name.
        if (!empty($row->courseid)) {
            $course = $DB->get_field('course', 'fullname', ['id' => $row->courseid]);
            if ($course) {
                $a->course = $course;
            }
        }

        // Get module name from context.
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

        return $a;
    }

    /**
     * Render the event name column.
     *
     * @param \stdClass $row The table row data.
     * @return string
     */
    public function col_eventname(\stdClass $row): string {
        $key = $this->get_event_key($row->eventname);
        if (isset(self::EVENT_NAME_MAP[$key])) {
            return get_string(self::EVENT_NAME_MAP[$key], 'local_activityreport');
        }
        // Fallback: try the event's own get_name().
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
     * @param \stdClass $row The table row data.
     * @return string
     */
    public function col_description(\stdClass $row): string {
        $key = $this->get_event_key($row->eventname);
        if (isset(self::EVENT_DESC_MAP[$key])) {
            $a = $this->get_description_params($row);
            $desc = get_string(self::EVENT_DESC_MAP[$key], 'local_activityreport', $a);
            if (\core_text::strlen($desc) > 200) {
                $desc = \core_text::substr($desc, 0, 200) . '...';
            }
            return $desc;
        }
        // Fallback to event's own description.
        $event = $this->restore_event($row);
        if ($event) {
            try {
                $desc = $event->get_description();
                $desc = strip_tags($desc);
                if (\core_text::strlen($desc) > 200) {
                    $desc = \core_text::substr($desc, 0, 200) . '...';
                }
                return $desc;
            } catch (\Throwable $e) {
                // Fall through.
            }
        }
        return '';
    }

    /**
     * Render the time column.
     *
     * @param \stdClass $row The table row data.
     * @return string
     */
    public function col_timecreated(\stdClass $row): string {
        return userdate($row->timecreated);
    }
}

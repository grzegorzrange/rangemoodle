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
                   l.contextid, l.contextlevel, l.contextinstanceid, l.userid, l.courseid,
                   l.relateduserid, l.other, l.timecreated, l.origin, l.ip,
                   {$descriptionexpr} AS description";

        $from = "{logstore_standard_log} l";

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
        $this->set_count_sql("SELECT COUNT(1) FROM {logstore_standard_log} l WHERE $where", $params);
    }

    /**
     * Restore an event object from a log row.
     *
     * @param \stdClass $row The log row.
     * @return \core\event\base|null
     */
    private function restore_event(\stdClass $row): ?\core\event\base {
        try {
            $data = (array) $row;
            unset($data['description']);

            $extra = [
                'origin' => $row->origin ?? '',
                'ip' => $row->ip ?? '',
                'realuserid' => 0,
            ];

            $other = $row->other ?? null;
            if ($other === null || $other === '' || $other === 'N;') {
                $data['other'] = null;
            } else if (preg_match('~^[aOibs][:;]~', $other)) {
                $data['other'] = @unserialize($other, ['allowed_classes' => [\stdClass::class]]);
            } else {
                $data['other'] = json_decode($other, true);
            }

            return \core\event\base::restore($data, $extra);
        } catch (\Throwable $e) {
            return null;
        }
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
        $event = $this->restore_event($row);
        if ($event) {
            try {
                $desc = $event->get_description();
                $desc = strip_tags($desc);
                if (\core_text::strlen($desc) > 300) {
                    $desc = \core_text::substr($desc, 0, 300) . '...';
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
     * @param \stdClass $row
     * @return string
     */
    public function col_timecreated(\stdClass $row): string {
        return userdate($row->timecreated);
    }
}

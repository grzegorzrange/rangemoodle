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
 * Upgrade steps for local_dashboard.
 *
 * @package    local_dashboard
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_dashboard plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool
 */
function xmldb_local_dashboard_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026021300) {
        // Define table local_dashboard_announce.
        $table = new xmldb_table('local_dashboard_announce');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('messageformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('recruitmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('notificationsent', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('notificationsenttime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('visible', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('recruitmentid_fk', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2026021300, 'local', 'dashboard');
    }

    if ($oldversion < 2026022100) {
        $table = new xmldb_table('local_dashboard_announce');

        // Add directionid field.
        $field = new xmldb_field('directionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'recruitmentid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Migrate: for each announcement, find a direction in its recruitment.
        $announces = $DB->get_records('local_dashboard_announce');
        foreach ($announces as $ann) {
            $direction = $DB->get_record('local_recruitment_course', ['recruitmentid' => $ann->recruitmentid], 'id', IGNORE_MULTIPLE);
            if ($direction) {
                $DB->set_field('local_dashboard_announce', 'directionid', $direction->id, ['id' => $ann->id]);
            }
        }

        // Make directionid NOT NULL now that data is migrated.
        $field = new xmldb_field('directionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $dbman->change_field_notnull($table, $field);

        // Add foreign key.
        $key = new xmldb_key('directionid_fk', XMLDB_KEY_FOREIGN, ['directionid'], 'local_recruitment_course', ['id']);
        $dbman->add_key($table, $key);

        // Drop old recruitmentid foreign key and field.
        $key = new xmldb_key('recruitmentid_fk', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);
        $dbman->drop_key($table, $key);

        $field = new xmldb_field('recruitmentid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026022100, 'local', 'dashboard');
    }

    return true;
}

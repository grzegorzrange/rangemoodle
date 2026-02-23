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
 * Upgrade steps for local_recruitment.
 *
 * @package    local_recruitment
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_recruitment_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026021400) {
        // Create "Kursy" category if it doesn't exist.
        if (!$DB->record_exists('course_categories', ['idnumber' => 'kursy'])) {
            $category = new stdClass();
            $category->name = 'Kursy';
            $category->idnumber = 'kursy';
            $category->parent = 0;
            $category->sortorder = 999;
            $category->visible = 1;
            core_course_category::create($category);
        }

        // Create "Archiwa" category if it doesn't exist.
        if (!$DB->record_exists('course_categories', ['idnumber' => 'archiwa'])) {
            $category = new stdClass();
            $category->name = 'Archiwa';
            $category->idnumber = 'archiwa';
            $category->parent = 0;
            $category->sortorder = 1000;
            $category->visible = 1;
            core_course_category::create($category);
        }

        upgrade_plugin_savepoint(true, 2026021400, 'local', 'recruitment');
    }

    if ($oldversion < 2026021501) {
        // Add basetestid column to local_recruitment.
        $table = new xmldb_table('local_recruitment');
        $field = new xmldb_field('basetestid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'basearchiveid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add foreign key for basetestid.
        $key = new xmldb_key('basetestid', XMLDB_KEY_FOREIGN, ['basetestid'], 'course', ['id']);
        $dbman->add_key($table, $key);

        // Create "Testy wewnętrzne bazowe" category.
        if (!$DB->record_exists('course_categories', ['idnumber' => 'testy_wewnetrzne_bazowe'])) {
            $category = new stdClass();
            $category->name = 'Testy wewnętrzne bazowe';
            $category->idnumber = 'testy_wewnetrzne_bazowe';
            $category->parent = 0;
            $category->sortorder = 1001;
            $category->visible = 1;
            core_course_category::create($category);
        }

        // Create "Testy wewnętrzne" category (target for copies).
        if (!$DB->record_exists('course_categories', ['idnumber' => 'testy_wewnetrzne'])) {
            $category = new stdClass();
            $category->name = 'Testy wewnętrzne';
            $category->idnumber = 'testy_wewnetrzne';
            $category->parent = 0;
            $category->sortorder = 1002;
            $category->visible = 1;
            core_course_category::create($category);
        }

        upgrade_plugin_savepoint(true, 2026021501, 'local', 'recruitment');
    }

    if ($oldversion < 2026021600) {
        // === Step 1: Create local_recruitment_course table ===
        $table = new xmldb_table('local_recruitment_course');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('recruitmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('basecourseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('basearchiveid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('basetestid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('recruitmentid', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);
        $table->add_key('basecourseid', XMLDB_KEY_FOREIGN, ['basecourseid'], 'course', ['id']);
        $table->add_key('basearchiveid', XMLDB_KEY_FOREIGN, ['basearchiveid'], 'course', ['id']);
        $table->add_key('basetestid', XMLDB_KEY_FOREIGN, ['basetestid'], 'course', ['id']);
        $table->add_key('cohortid', XMLDB_KEY_FOREIGN, ['cohortid'], 'cohort', ['id']);
        $table->add_key('usercreated', XMLDB_KEY_FOREIGN, ['usercreated'], 'user', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // === Step 2: Migrate existing recruitment data to directions ===
        $recruitmentsrc = new xmldb_table('local_recruitment');
        $oldfield = new xmldb_field('basecourseid');
        if ($dbman->field_exists($recruitmentsrc, $oldfield)) {
            $recruitments = $DB->get_records('local_recruitment');
            foreach ($recruitments as $rec) {
                // Only migrate if there's actual data to migrate.
                if (!empty($rec->basecourseid) || !empty($rec->basearchiveid) ||
                    !empty($rec->basetestid) || !empty($rec->cohortid)) {

                    $direction = new stdClass();
                    $direction->recruitmentid = $rec->id;
                    $direction->name = $rec->name;
                    $direction->basecourseid = !empty($rec->basecourseid) ? $rec->basecourseid : null;
                    $direction->basearchiveid = !empty($rec->basearchiveid) ? $rec->basearchiveid : null;
                    $direction->basetestid = !empty($rec->basetestid) ? $rec->basetestid : null;
                    $direction->cohortid = !empty($rec->cohortid) ? $rec->cohortid : null;
                    $direction->timecreated = $rec->timecreated;
                    $direction->timemodified = $rec->timemodified;
                    $direction->usercreated = $rec->usercreated;

                    $directionid = $DB->insert_record('local_recruitment_course', $direction);

                    // === Step 3: Update schedule/financial/organizational to point to direction ===
                    // These tables currently have recruitmentid pointing to the recruitment.
                    // We need to update them to point to the new direction ID.
                    $DB->execute(
                        "UPDATE {local_schedule} SET recruitmentid = :directionid WHERE recruitmentid = :recruitmentid",
                        ['directionid' => $directionid, 'recruitmentid' => $rec->id]
                    );
                    $DB->execute(
                        "UPDATE {local_financial} SET recruitmentid = :directionid WHERE recruitmentid = :recruitmentid",
                        ['directionid' => $directionid, 'recruitmentid' => $rec->id]
                    );
                    $DB->execute(
                        "UPDATE {local_organizational} SET recruitmentid = :directionid WHERE recruitmentid = :recruitmentid",
                        ['directionid' => $directionid, 'recruitmentid' => $rec->id]
                    );

                    // === Step 4: Update course shortnames from rek_{rec.id}_ to dir_{direction_id}_ ===
                    $oldprefix = 'rek_' . $rec->id . '_';
                    $newprefix = 'dir_' . $directionid . '_';
                    foreach (['kurs', 'archiwum', 'test'] as $type) {
                        $oldshort = $oldprefix . $type;
                        $newshort = $newprefix . $type;
                        $course = $DB->get_record('course', ['shortname' => $oldshort]);
                        if ($course) {
                            $course->shortname = $newshort;
                            $DB->update_record('course', $course);
                        }
                    }
                }
            }
        }

        // === Step 5: Rename recruitmentid to directionid in schedule/financial/organizational ===
        // Schedule.
        $scheduletable = new xmldb_table('local_schedule');
        $schedulefield = new xmldb_field('recruitmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($scheduletable, $schedulefield)) {
            // Drop old FK first.
            $oldkey = new xmldb_key('recruitmentid_fk', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);
            $dbman->drop_key($scheduletable, $oldkey);
            // Rename field.
            $dbman->rename_field($scheduletable, $schedulefield, 'directionid');
            // Add new FK.
            $newkey = new xmldb_key('directionid_fk', XMLDB_KEY_FOREIGN, ['directionid'], 'local_recruitment_course', ['id']);
            $dbman->add_key($scheduletable, $newkey);
        }

        // Financial.
        $financialtable = new xmldb_table('local_financial');
        $financialfield = new xmldb_field('recruitmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($financialtable, $financialfield)) {
            $oldkey = new xmldb_key('recruitmentid_fk', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);
            $dbman->drop_key($financialtable, $oldkey);
            $dbman->rename_field($financialtable, $financialfield, 'directionid');
            $newkey = new xmldb_key('directionid_fk', XMLDB_KEY_FOREIGN, ['directionid'], 'local_recruitment_course', ['id']);
            $dbman->add_key($financialtable, $newkey);
        }

        // Organizational.
        $orgtable = new xmldb_table('local_organizational');
        $orgfield = new xmldb_field('recruitmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($orgtable, $orgfield)) {
            $oldkey = new xmldb_key('recruitmentid_fk', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);
            $dbman->drop_key($orgtable, $oldkey);
            $dbman->rename_field($orgtable, $orgfield, 'directionid');
            $newkey = new xmldb_key('directionid_fk', XMLDB_KEY_FOREIGN, ['directionid'], 'local_recruitment_course', ['id']);
            $dbman->add_key($orgtable, $newkey);
        }

        // === Step 6: Drop old columns from local_recruitment ===
        $rectab = new xmldb_table('local_recruitment');

        // Drop foreign keys first.
        $keys_to_drop = [
            new xmldb_key('basecourseid', XMLDB_KEY_FOREIGN, ['basecourseid'], 'course', ['id']),
            new xmldb_key('basearchiveid', XMLDB_KEY_FOREIGN, ['basearchiveid'], 'course', ['id']),
            new xmldb_key('basetestid', XMLDB_KEY_FOREIGN, ['basetestid'], 'course', ['id']),
            new xmldb_key('cohortid', XMLDB_KEY_FOREIGN, ['cohortid'], 'cohort', ['id']),
        ];
        foreach ($keys_to_drop as $key) {
            $dbman->drop_key($rectab, $key);
        }

        // Drop fields.
        $fields_to_drop = ['basecourseid', 'basearchiveid', 'basetestid', 'cohortid'];
        foreach ($fields_to_drop as $fname) {
            $field = new xmldb_field($fname);
            if ($dbman->field_exists($rectab, $field)) {
                $dbman->drop_field($rectab, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2026021600, 'local', 'recruitment');
    }

    if ($oldversion < 2026022200) {
        $table = new xmldb_table('local_recruitment_course');

        // Create "Kategorie bazowe" parent category if it doesn't exist.
        if (!$DB->record_exists('course_categories', ['idnumber' => 'kategorie_bazowe'])) {
            core_course_category::create((object) [
                'name' => 'Kategorie bazowe',
                'idnumber' => 'kategorie_bazowe',
                'parent' => 0,
                'visible' => 1,
            ]);
        }

        // Create "Utworzone kierunki" parent category for target categories.
        if (!$DB->record_exists('course_categories', ['idnumber' => 'utworzone_kierunki'])) {
            core_course_category::create((object) [
                'name' => 'Utworzone kierunki',
                'idnumber' => 'utworzone_kierunki',
                'parent' => 0,
                'visible' => 1,
            ]);
        }

        // Add new columns.
        $newfields = [
            'basecategoryid' => new xmldb_field('basecategoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'name'),
            'archive_course' => new xmldb_field('archive_course', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'basecategoryid'),
            'preparation_course' => new xmldb_field('preparation_course', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'archive_course'),
            'quizes_course' => new xmldb_field('quizes_course', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'preparation_course'),
            'target_categoryid' => new xmldb_field('target_categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'quizes_course'),
        ];
        foreach ($newfields as $fname => $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Migrate existing data: look up copied courses by shortname pattern and store IDs.
        $directions = $DB->get_records('local_recruitment_course');
        foreach ($directions as $dir) {
            $update = new stdClass();
            $update->id = $dir->id;
            $changed = false;

            $prefix = 'dir_' . $dir->id . '_';
            $archiveid = $DB->get_field('course', 'id', ['shortname' => $prefix . 'archiwum']);
            $kursid = $DB->get_field('course', 'id', ['shortname' => $prefix . 'kurs']);
            $testid = $DB->get_field('course', 'id', ['shortname' => $prefix . 'test']);

            if ($archiveid) {
                $update->archive_course = $archiveid;
                $changed = true;
            }
            if ($kursid) {
                $update->preparation_course = $kursid;
                $changed = true;
            }
            if ($testid) {
                $update->quizes_course = $testid;
                $changed = true;
            }

            if ($changed) {
                $DB->update_record('local_recruitment_course', $update);
            }
        }

        // Drop old foreign keys.
        $oldkeys = [
            new xmldb_key('basecourseid', XMLDB_KEY_FOREIGN, ['basecourseid'], 'course', ['id']),
            new xmldb_key('basearchiveid', XMLDB_KEY_FOREIGN, ['basearchiveid'], 'course', ['id']),
            new xmldb_key('basetestid', XMLDB_KEY_FOREIGN, ['basetestid'], 'course', ['id']),
        ];
        foreach ($oldkeys as $key) {
            $dbman->drop_key($table, $key);
        }

        // Drop old columns.
        foreach (['basecourseid', 'basearchiveid', 'basetestid'] as $fname) {
            $field = new xmldb_field($fname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        // Add new foreign keys.
        $newkeys = [
            new xmldb_key('basecategoryid', XMLDB_KEY_FOREIGN, ['basecategoryid'], 'course_categories', ['id']),
            new xmldb_key('archive_course', XMLDB_KEY_FOREIGN, ['archive_course'], 'course', ['id']),
            new xmldb_key('preparation_course', XMLDB_KEY_FOREIGN, ['preparation_course'], 'course', ['id']),
            new xmldb_key('quizes_course', XMLDB_KEY_FOREIGN, ['quizes_course'], 'course', ['id']),
            new xmldb_key('target_categoryid', XMLDB_KEY_FOREIGN, ['target_categoryid'], 'course_categories', ['id']),
        ];
        foreach ($newkeys as $key) {
            $dbman->add_key($table, $key);
        }

        upgrade_plugin_savepoint(true, 2026022200, 'local', 'recruitment');
    }

    if ($oldversion < 2026022201) {
        $table = new xmldb_table('local_recruitment_course');

        // Add copystatus column: 0 = copying in progress, 1 = done.
        $field = new xmldb_field('copystatus', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'cohortid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026022201, 'local', 'recruitment');
    }

    if ($oldversion < 2026022301) {
        // Create local_recruitment_user table.
        $table = new xmldb_table('local_recruitment_user');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('directionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('recruitmentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('declaration', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('usercreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('directionid_fk', XMLDB_KEY_FOREIGN, ['directionid'], 'local_recruitment_course', ['id']);
        $table->add_key('recruitmentid_fk', XMLDB_KEY_FOREIGN, ['recruitmentid'], 'local_recruitment', ['id']);

        $table->add_index('userid_directionid_uq', XMLDB_INDEX_UNIQUE, ['userid', 'directionid']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Migrate existing cohort members into local_recruitment_user with declaration=0.
        $directions = $DB->get_records('local_recruitment_course');
        foreach ($directions as $dir) {
            if (empty($dir->cohortid)) {
                continue;
            }
            $members = $DB->get_records('cohort_members', ['cohortid' => $dir->cohortid]);
            foreach ($members as $member) {
                if ($DB->record_exists('local_recruitment_user', [
                    'userid' => $member->userid,
                    'directionid' => $dir->id,
                ])) {
                    continue;
                }
                $record = new stdClass();
                $record->userid = $member->userid;
                $record->directionid = $dir->id;
                $record->recruitmentid = $dir->recruitmentid;
                $record->declaration = 0;
                $record->timecreated = time();
                $record->usercreated = 0;
                $record->timemodified = time();
                $DB->insert_record('local_recruitment_user', $record);
            }
        }

        upgrade_plugin_savepoint(true, 2026022301, 'local', 'recruitment');
    }

    if ($oldversion < 2026022302) {
        $table = new xmldb_table('local_recruitment_user');

        // Add notified field.
        $field = new xmldb_field('notified', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'declaration');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add timenotified field.
        $field = new xmldb_field('timenotified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'notified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026022302, 'local', 'recruitment');
    }

    return true;
}

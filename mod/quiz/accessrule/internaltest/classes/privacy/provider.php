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

namespace quizaccess_internaltest\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy provider for quizaccess_internaltest.
 *
 * @package    quizaccess_internaltest
 * @copyright  2026 Custom Development
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \mod_quiz\privacy\quizaccess_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('quizaccess_inttest_results', [
            'userid' => 'privacy:metadata:quizaccess_inttest_results:userid',
            'jsondata' => 'privacy:metadata:quizaccess_inttest_results:jsondata',
            'timecreated' => 'privacy:metadata:quizaccess_inttest_results:timecreated',
        ], 'privacy:metadata:quizaccess_inttest_results');
        return $collection;
    }

    public static function export_quizaccess_user_data(\core_privacy\local\request\approved_contextlist $contextlist, int $quizid, array $featureexportdata): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $results = $DB->get_records('quizaccess_inttest_results', [
            'quizid' => $quizid,
            'userid' => $userid,
        ]);
        if ($results) {
            $data = [];
            foreach ($results as $result) {
                $data[] = [
                    'attemptid' => $result->attemptid,
                    'timecreated' => \core_privacy\local\request\transform::datetime($result->timecreated),
                    'sent' => $result->sent ? 'Yes' : 'No',
                ];
            }
            writer::with_context(\context_module::instance(
                get_coursemodule_from_instance('quiz', $quizid)->id
            ))->export_related_data([], 'quizaccess_internaltest', (object)['results' => $data]);
        }
    }

    public static function delete_quizaccess_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('quiz', $context->instanceid);
        if ($cm) {
            $DB->delete_records('quizaccess_inttest_results', ['quizid' => $cm->instance]);
        }
    }

    public static function delete_quizaccess_data_for_user(\core_privacy\local\request\approved_contextlist $contextlist): void {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $cm = get_coursemodule_from_id('quiz', $context->instanceid);
            if ($cm) {
                $DB->delete_records('quizaccess_inttest_results', [
                    'quizid' => $cm->instance,
                    'userid' => $userid,
                ]);
            }
        }
    }

    public static function delete_quizaccess_data_for_users(\core_privacy\local\request\approved_userlist $userlist): void {
        global $DB;
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $cm = get_coursemodule_from_id('quiz', $context->instanceid);
        if (!$cm) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['quizid'] = $cm->instance;
        $DB->delete_records_select('quizaccess_inttest_results',
            "quizid = :quizid AND userid $insql", $params);
    }
}

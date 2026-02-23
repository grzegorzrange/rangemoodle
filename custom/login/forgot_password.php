<?php
// This file is part of Moodle - http://moodle.org/
//
// Custom override of /login/forgot_password.php
// Changes: uses custom form with PESEL label, custom password reset logic with fallback email.
//
// @package    core
// @subpackage auth
// @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// Config is already loaded by the hook system.
global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION, $COURSE;

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot . '/login/lib.php');
// CUSTOM: Load our custom form with PESEL label.
require_once(__DIR__ . '/forgot_password_form.php');
require_once($CFG->dirroot . '/login/set_password_form.php');

$token = optional_param('token', false, PARAM_ALPHANUM);

$PAGE->set_url('/login/forgot_password.php');
$systemcontext = context_system::instance();
$PAGE->set_context($systemcontext);

$strforgotten = get_string('passwordforgotten');

$PAGE->set_pagelayout('login');
$PAGE->set_title($strforgotten);
$PAGE->set_heading($COURSE->fullname);

if (!empty($CFG->forgottenpasswordurl)) {
    redirect($CFG->forgottenpasswordurl);
}

if (isloggedin() and !isguestuser()) {
    redirect($CFG->wwwroot.'/index.php', get_string('loginalready'), 5);
}

// Fetch the token from the session.
$tokeninsession = false;
if (!empty($SESSION->password_reset_token)) {
    $token = $SESSION->password_reset_token;
    unset($SESSION->password_reset_token);
    $tokeninsession = true;
}

if (empty($token)) {
    // CUSTOM: Use our custom password reset request handler with fallback email.
    custom_process_password_reset_request();
} else {
    if (!$tokeninsession && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $SESSION->password_reset_token = $token;
        redirect($CFG->wwwroot . '/login/forgot_password.php');
    } else {
        core_login_process_password_set($token);
    }
}

/**
 * Custom password reset request handler with fallback email support.
 */
function custom_process_password_reset_request() {
    global $OUTPUT, $PAGE, $CFG, $DB;

    $mform = new login_forgot_password_form();

    if ($mform->is_cancelled()) {
        redirect(get_login_url());

    } else if ($data = $mform->get_data()) {

        $username = $email = '';
        if (!empty($data->username)) {
            $username = $data->username;
        } else {
            $email = $data->email;
        }

        // CUSTOM: Check if we need fallback email logic.
        if (!empty($email)) {
            // Check if email exists in DB.
            $sql = "SELECT *
                      FROM {user}
                     WHERE " . $DB->sql_equal('email', ':email1', false, true) . "
                       AND id IN (SELECT id
                                    FROM {user}
                                   WHERE mnethostid = :mnethostid
                                     AND deleted = 0
                                     AND suspended = 0
                                     AND " . $DB->sql_equal('email', ':email2', false, false) . ")";
            $params = [
                'email1' => $email,
                'email2' => $email,
                'mnethostid' => $CFG->mnet_localhost_id,
            ];
            $userbyemail = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

            if (!$userbyemail) {
                // Email not found - check for fallback email setting.
                $fallbackemail = get_config('local_support', 'fallbackemail');
                if (!empty($fallbackemail)) {
                    // Send a notification to the fallback email about the attempt.
                    $supportuser = \core_user::get_support_user();
                    $fakeuser = new stdClass();
                    $fakeuser->id = -99;
                    $fakeuser->email = $fallbackemail;
                    $fakeuser->firstname = 'Admin';
                    $fakeuser->lastname = '';
                    $fakeuser->firstnamephonetic = '';
                    $fakeuser->lastnamephonetic = '';
                    $fakeuser->middlename = '';
                    $fakeuser->alternatename = '';
                    $fakeuser->maildisplay = 1;
                    $fakeuser->mailformat = 1;
                    $fakeuser->deleted = 0;
                    $fakeuser->suspended = 0;
                    $fakeuser->auth = 'manual';
                    $fakeuser->username = 'fallback';

                    $subject = get_string('fallbackemailsubject', 'local_support', $email);
                    $messagetext = get_string('fallbackemailbody', 'local_support', $email);
                    $messagehtml = get_string('fallbackemailbody_html', 'local_support', $email);

                    email_to_user($fakeuser, $supportuser, $subject, $messagetext, $messagehtml);
                }

                // Show generic message (don't reveal that email doesn't exist).
                core_login_post_forgot_password_requests($data);
                echo $OUTPUT->header();
                notice(get_string('emailpasswordconfirmmaybesent'), $CFG->wwwroot.'/index.php');
                die;
            }
        }

        // Standard processing for existing users.
        list($status, $notice, $url) = core_login_process_password_reset($username, $email);

        core_login_post_forgot_password_requests($data);

        echo $OUTPUT->header();
        notice($notice, $url);
        die;
    }

    // DISPLAY FORM.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('passwordforgotteninstructions2'), 'generalbox boxwidthnormal boxaligncenter');
    $mform->display();

    echo $OUTPUT->footer();
}

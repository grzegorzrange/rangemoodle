<?php
// This file is part of Moodle - http://moodle.org/
//
// Custom override of /login/index.php
// Changes: "username" label replaced with "PESEL",
// custom error message when user does not exist.
//
// @package    core
// @subpackage auth
// @copyright  1999 onwards Martin Dougiamas  http://dougiamas.com
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

// Config is already loaded by the hook system.
global $CFG, $DB, $PAGE, $OUTPUT, $USER, $SESSION, $COURSE, $SITE;

require_once($CFG->dirroot . '/login/lib.php');

redirect_if_major_upgrade_required();

$testsession = optional_param('testsession', 0, PARAM_INT);
$anchor      = optional_param('anchor', '', PARAM_RAW);
$loginredirect = optional_param('loginredirect', 1, PARAM_BOOL);

$resendconfirmemail = optional_param('resendconfirmemail', false, PARAM_BOOL);

if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
    $wantsurl    = optional_param('wantsurl', '', PARAM_LOCALURL);
    if ($wantsurl !== '') {
        $SESSION->wantsurl = (new moodle_url($wantsurl))->out(false);
    }
}

$context = context_system::instance();
$PAGE->set_url("$CFG->wwwroot/login/index.php");
$PAGE->set_context($context);
$PAGE->set_pagelayout('login');
$PAGE->set_cacheable(false);

/// Initialize variables
$errormsg = '';
$infomsg = '';
$errorcode = 0;

// login page requested session test
if ($testsession) {
    if ($testsession == $USER->id) {
        if (isset($SESSION->wantsurl)) {
            $urltogo = $SESSION->wantsurl;
        } else {
            $urltogo = $CFG->wwwroot.'/';
        }
        unset($SESSION->wantsurl);
        redirect($urltogo);
    } else {
        $errormsg = get_string("cookiesnotenabled");
        $errorcode = 1;
    }
}

/// Check for timed out sessions
if (!empty($SESSION->has_timed_out)) {
    $session_has_timed_out = true;
    unset($SESSION->has_timed_out);
} else {
    $session_has_timed_out = false;
}

$frm  = false;
$user = false;

$authsequence = get_enabled_auth_plugins();
foreach($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $authplugin->loginpage_hook();
}


/// Define variables used in page
$site = get_site();

$PAGE->navbar->ignore_active();
$loginsite = get_string("loginsite");
$PAGE->navbar->add($loginsite);

if ($user !== false or $frm !== false or $errormsg !== '') {
    // some auth plugin already supplied full user, fake form data or prevented user login with error message

} else if (!empty($SESSION->wantsurl) && file_exists($CFG->dirroot.'/login/weblinkauth.php')) {
    include($CFG->dirroot.'/login/weblinkauth.php');
    if (function_exists('weblink_auth')) {
        $user = weblink_auth($SESSION->wantsurl);
    }
    if ($user) {
        $frm->username = $user->username;
    } else {
        $frm = data_submitted();
    }

} else {
    $frm = data_submitted();
}

// Restore the #anchor to the original wantsurl.
if ($anchor && isset($SESSION->wantsurl) && strpos($SESSION->wantsurl, '#') === false) {
    $wantsurl = new moodle_url($SESSION->wantsurl);
    $wantsurl->set_anchor(substr($anchor, 1));
    $SESSION->wantsurl = $wantsurl->out();
}

/// Check if the user has actually submitted login data to us

if ($frm and isset($frm->username)) {                             // Login WITH cookies

    $frm->username = trim(core_text::strtolower($frm->username));

    if (is_enabled_auth('none') ) {
        if ($frm->username !== core_user::clean_field($frm->username, 'username')) {
            // CUSTOM: Changed error message to reference PESEL.
            $errormsg = 'PESEL: '.get_string("invalidusername");
            $errorcode = 2;
            $user = null;
        }
    }

    if ($user) {
        // The auth plugin has already provided the user via the loginpage_hook() called above.
    } else if (($frm->username == 'guest') and empty($CFG->guestloginbutton)) {
        $user = false;
        $frm = false;
    } else {
        if (empty($errormsg)) {
            $logintoken = isset($frm->logintoken) ? $frm->logintoken : '';
            $loginrecaptcha = login_captcha_enabled() ? $frm->{'g-recaptcha-response'} ?? '' : false;
            $user = authenticate_user_login($frm->username, $frm->password, false, $errorcode, $logintoken, $loginrecaptcha);
        }
    }

    // Intercept 'restored' users to provide them with info & reset password
    if (!$user and $frm and is_restored_user($frm->username)) {
        $PAGE->set_title(get_string('restoredaccount'));
        $PAGE->set_heading($site->fullname);
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('restoredaccount'));
        echo $OUTPUT->box(get_string('restoredaccountinfo'), 'generalbox boxaligncenter');
        require_once($CFG->dirroot . '/login/restored_password_form.php');
        $form = new login_forgot_password_form('forgot_password.php', array('username' => $frm->username));
        $form->display();
        echo $OUTPUT->footer();
        die;
    }

    if ($user) {

        // language setup
        if (isguestuser($user)) {
            unset($user->lang);

        } else if (!empty($user->lang)) {
            unset($SESSION->lang);
        }

        if (empty($user->confirmed)) {
            $PAGE->set_title(get_string("mustconfirm"));
            $PAGE->set_heading($site->fullname);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string("mustconfirm"));
            if ($resendconfirmemail) {
                if (!send_confirmation_email($user)) {
                    echo $OUTPUT->notification(get_string('emailconfirmsentfailure'), \core\output\notification::NOTIFY_ERROR);
                } else {
                    echo $OUTPUT->notification(get_string('emailconfirmsentsuccess'), \core\output\notification::NOTIFY_SUCCESS);
                }
            }
            echo $OUTPUT->box(get_string("emailconfirmsent", "", s($user->email)), "generalbox boxaligncenter");
            $resendconfirmurl = new moodle_url('/login/index.php',
                [
                    'username' => $frm->username,
                    'password' => $frm->password,
                    'resendconfirmemail' => true,
                    'logintoken' => \core\session\manager::get_login_token()
                ]
            );
            echo $OUTPUT->single_button($resendconfirmurl, get_string('emailconfirmationresend'));
            echo $OUTPUT->footer();
            die;
        }

    /// Let's get them all set up.
        complete_user_login($user);

        \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

        // sets the username cookie
        if (!empty($CFG->nolastloggedin)) {
            // do not store last logged in user in cookie

        } else if (empty($CFG->rememberusername)) {
            set_moodle_cookie('');

        } else {
            set_moodle_cookie($USER->username);
        }

        $urltogo = core_login_get_return_url();

    /// check if user password has expired
        $userauth = get_auth_plugin($USER->auth);
        if (!isguestuser() and !empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
            $externalchangepassword = false;
            if ($userauth->can_change_password()) {
                $passwordchangeurl = $userauth->change_password_url();
                if (!$passwordchangeurl) {
                    $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
                } else {
                    $externalchangepassword = true;
                }
            } else {
                $passwordchangeurl = $CFG->wwwroot.'/login/change_password.php';
            }
            $days2expire = $userauth->password_expire($USER->username);
            $PAGE->set_title($loginsite);
            $PAGE->set_heading("$site->fullname");
            if (intval($days2expire) > 0 && intval($days2expire) < intval($userauth->config->expiration_warning)) {
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordwillexpire', 'auth', $days2expire), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            } elseif (intval($days2expire) < 0 ) {
                if ($externalchangepassword) {
                    require_logout();
                } else {
                    set_user_preference('auth_forcepasswordchange', 1, $USER);
                }
                echo $OUTPUT->header();
                echo $OUTPUT->confirm(get_string('auth_passwordisexpired', 'auth'), $passwordchangeurl, $urltogo);
                echo $OUTPUT->footer();
                exit;
            }
        }

        // Discard any errors before the last redirect.
        unset($SESSION->loginerrormsg);
        unset($SESSION->logininfomsg);

        unset($SESSION->loginredirect);

        // test the session actually works by redirecting to self
        $SESSION->wantsurl = $urltogo;
        redirect(new moodle_url(get_login_url(), array('testsession'=>$USER->id)));

    } else {
        if (empty($errormsg)) {
            if ($errorcode == AUTH_LOGIN_UNAUTHORISED) {
                $errormsg = get_string("unauthorisedlogin", "", $frm->username);
            } else if ($errorcode == AUTH_LOGIN_FAILED_RECAPTCHA) {
                $errormsg = get_string('missingrecaptchachallengefield');
            } else {
                // CUSTOM: Check if user exists to provide PESEL-specific error.
                $userexists = $DB->record_exists('user', [
                    'username' => $frm->username,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'deleted' => 0,
                ]);
                if (!$userexists) {
                    $errormsg = get_string('peselnotfound', 'local_support');
                } else {
                    $errormsg = get_string("invalidlogin");
                }
                $errorcode = 3;
            }
        }
    }
}

/// Detect problems with timedout sessions
if ($session_has_timed_out and !data_submitted()) {
    $errormsg = get_string('sessionerroruser', 'error');
    $errorcode = 4;
}

/// First, let's remember where the user was trying to get to before they got here

if (empty($SESSION->wantsurl)) {
    $SESSION->wantsurl = null;
    $referer = get_local_referer(false);
    if ($referer &&
            $referer != $CFG->wwwroot &&
            $referer != $CFG->wwwroot . '/' &&
            $referer != $CFG->wwwroot . '/login/' &&
            strpos($referer, $CFG->wwwroot . '/login/?') !== 0 &&
            strpos($referer, $CFG->wwwroot . '/login/index.php') !== 0) {
        $SESSION->wantsurl = $referer;
    }
}

// Check if loginredirect is set in the SESSION.
if ($errorcode && isset($SESSION->loginredirect)) {
    $loginredirect = $SESSION->loginredirect;
}
$SESSION->loginredirect = $loginredirect;

/// Redirect to alternative login URL if needed
if (!empty($CFG->alternateloginurl) && $loginredirect) {
    $loginurl = new moodle_url($CFG->alternateloginurl);

    $loginurlstr = $loginurl->out(false);

    if ($SESSION->wantsurl != '' && strpos($SESSION->wantsurl, $loginurlstr) === 0) {
        $SESSION->wantsurl = null;
    }

    if ($errorcode) {
        $loginurl->param('errorcode', $errorcode);
    }

    redirect($loginurl->out(false));
}

/// Generate the login page with forms

if (!isset($frm) or !is_object($frm)) {
    $frm = new stdClass();
}

if (empty($frm->username) && $authsequence[0] != 'shibboleth') {
    if (!empty($_GET["username"])) {
        $frm->username = clean_param($_GET["username"], PARAM_RAW);
    } else {
        $frm->username = get_moodle_cookie();
    }

    $frm->password = "";
}

if (!empty($SESSION->loginerrormsg) || !empty($SESSION->logininfomsg)) {
    $errormsg = $SESSION->loginerrormsg ?? '';
    $infomsg = $SESSION->logininfomsg ?? '';
    unset($SESSION->loginerrormsg);
    unset($SESSION->logininfomsg);

} else if ($testsession) {
    unset($SESSION->loginerrormsg);
    unset($SESSION->logininfomsg);

} else if ($errormsg or !empty($frm->password)) {
    if ($errormsg) {
        $SESSION->loginerrormsg = $errormsg;
    }

    $loginurl = new moodle_url('/login/index.php');
    $loginurl->param('loginredirect', $SESSION->loginredirect);

    redirect($loginurl->out(false));
}

$PAGE->set_title($loginsite);
$PAGE->set_heading("$site->fullname");

echo $OUTPUT->header();

if (isloggedin() and !isguestuser()) {
    echo $OUTPUT->box_start();
    $logout = new single_button(new moodle_url('/login/logout.php', array('sesskey'=>sesskey(),'loginpage'=>1)), get_string('logout'), 'post');
    $continue = new single_button(new moodle_url('/'), get_string('cancel'), 'get');
    echo $OUTPUT->confirm(get_string('alreadyloggedin', 'error', fullname($USER)), $logout, $continue);
    echo $OUTPUT->box_end();
} else {
    $loginform = new \core_auth\output\login($authsequence, $frm->username);
    $loginform->set_error($errormsg);
    $loginform->set_info($infomsg);

    // CUSTOM: Render the form and replace "username" labels with "PESEL".
    $html = $OUTPUT->render($loginform);
    $usernamestr = get_string('username');
    $usernameemailstr = get_string('usernameemail');
    $html = str_replace(
        [$usernameemailstr, $usernamestr],
        ['PESEL', 'PESEL'],
        $html
    );
    echo $html;
}

echo $OUTPUT->footer();

<?php
// This file is part of Moodle - http://moodle.org/
//
// Custom override of /user/editadvanced_form.php
// Changes: "username" label replaced with "PESEL",
// username validation skipped if not provided (empty PESEL allowed).
//
// @copyright 1999 Martin Dougiamas  http://dougiamas.com
// @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
// @package core_user

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/lib/formslib.php');
require_once($CFG->dirroot.'/user/lib.php');

/**
 * Class user_editadvanced_form.
 */
class user_editadvanced_form extends moodleform {

    /**
     * Define the form.
     */
    public function definition() {
        global $USER, $CFG, $COURSE;

        $mform = $this->_form;
        $editoroptions = null;
        $filemanageroptions = null;

        if (!is_array($this->_customdata)) {
            throw new coding_exception('invalid custom data for user_edit_form');
        }
        $editoroptions = $this->_customdata['editoroptions'];
        $filemanageroptions = $this->_customdata['filemanageroptions'];
        $user = $this->_customdata['user'];
        $userid = $user->id;

        $strgeneral  = get_string('general');
        $strrequired = get_string('required');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', core_user::get_property_type('id'));
        $mform->addElement('hidden', 'course', $COURSE->id);
        $mform->setType('course', PARAM_INT);

        $mform->addElement('header', 'moodle', $strgeneral);

        $auths = core_component::get_plugin_list('auth');
        $enabled = get_string('pluginenabled', 'core_plugin');
        $disabled = get_string('plugindisabled', 'core_plugin');
        $authoptions = array($enabled => array(), $disabled => array());
        $cannotchangepass = array();
        $cannotchangeusername = array();
        foreach ($auths as $auth => $unused) {
            $authinst = get_auth_plugin($auth);

            if (!$authinst->is_internal()) {
                $cannotchangeusername[] = $auth;
            }

            $passwordurl = $authinst->change_password_url();
            if (!($authinst->can_change_password() && empty($passwordurl))) {
                if ($userid < 1 and $authinst->is_internal()) {
                    // Need to set password initially.
                } else {
                    $cannotchangepass[] = $auth;
                }
            }
            if (is_enabled_auth($auth)) {
                $authoptions[$enabled][$auth] = get_string('pluginname', "auth_{$auth}");
            } else {
                $authoptions[$disabled][$auth] = get_string('pluginname', "auth_{$auth}");
            }
        }

        $purpose = user_edit_map_field_purpose($userid, 'username');
        // CUSTOM: Changed label from get_string('username') to 'PESEL'.
        $mform->addElement('text', 'username', 'PESEL', 'size="20"' . $purpose);
        $mform->addHelpButton('username', 'username', 'auth');
        $mform->setType('username', PARAM_RAW);

        if ($userid !== -1) {
            $mform->disabledIf('username', 'auth', 'in', $cannotchangeusername);
        }

        $mform->addElement('selectgroups', 'auth', get_string('chooseauthmethod', 'auth'), $authoptions);
        $mform->addHelpButton('auth', 'chooseauthmethod', 'auth');

        $mform->addElement('advcheckbox', 'suspended', get_string('suspended', 'auth'));
        $mform->addHelpButton('suspended', 'suspended', 'auth');

        $mform->addElement('checkbox', 'createpassword', get_string('createpassword', 'auth'));
        $mform->disabledIf('createpassword', 'auth', 'in', $cannotchangepass);

        if (!empty($CFG->passwordpolicy)) {
            $mform->addElement('static', 'passwordpolicyinfo', '', print_password_policy());
        }

        $purpose = user_edit_map_field_purpose($userid, 'password');
        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword'),
            'maxlength="'.MAX_PASSWORD_CHARACTERS.'" size="20"' . $purpose);
        $mform->addRule('newpassword', get_string('maximumchars', '', MAX_PASSWORD_CHARACTERS),
            'maxlength', MAX_PASSWORD_CHARACTERS, 'client');
        $mform->addHelpButton('newpassword', 'newpassword');
        $mform->setType('newpassword', core_user::get_property_type('password'));
        $mform->disabledIf('newpassword', 'createpassword', 'checked');

        $mform->disabledIf('newpassword', 'auth', 'in', $cannotchangepass);

        if ($userid and empty($CFG->passwordchangetokendeletion)) {
            if ($tokens = webservice::get_active_tokens($userid)) {
                $services = '';
                foreach ($tokens as $token) {
                    $services .= format_string($token->servicename) . ',';
                }
                $services = get_string('userservices', 'webservice', rtrim($services, ','));
                $mform->addElement('advcheckbox', 'signoutofotherservices', get_string('signoutofotherservices'), $services);
                $mform->addHelpButton('signoutofotherservices', 'signoutofotherservices');
                $mform->disabledIf('signoutofotherservices', 'newpassword', 'eq', '');
                $mform->setDefault('signoutofotherservices', 1);
            }
        }

        $mform->addElement('advcheckbox', 'preference_auth_forcepasswordchange', get_string('forcepasswordchange'));
        $mform->addHelpButton('preference_auth_forcepasswordchange', 'forcepasswordchange');
        $mform->disabledIf('preference_auth_forcepasswordchange', 'createpassword', 'checked');

        useredit_shared_definition($mform, $editoroptions, $filemanageroptions, $user);

        profile_definition($mform, $userid);

        if ($userid == -1) {
            $btnstring = get_string('createuser');
        } else {
            $btnstring = get_string('updatemyprofile');
        }

        $this->add_action_buttons(true, $btnstring);

        $this->set_data($user);
    }

    /**
     * Extend the form definition after data has been parsed.
     */
    public function definition_after_data() {
        global $USER, $CFG, $DB, $OUTPUT;

        $mform = $this->_form;

        foreach (useredit_get_required_name_fields() as $field) {
            $mform->applyFilter($field, 'trim');
        }

        if ($userid = $mform->getElementValue('id')) {
            $user = $DB->get_record('user', array('id' => $userid));
        } else {
            $user = false;
        }

        if ($userid == $USER->id) {
            $mform->hardFreeze('auth');
            $mform->hardFreeze('preference_auth_forcepasswordchange');
        }

        if (!empty($USER->newadminuser)) {
            $mform->addRule('newpassword', get_string('required'), 'required', null, 'client');
            if ($mform->elementExists('suspended')) {
                $mform->removeElement('suspended');
            }
        }

        if ($userid > 0) {
            if ($mform->elementExists('createpassword')) {
                $mform->removeElement('createpassword');
            }
        }

        if ($user and is_mnet_remote_user($user)) {
            if ($mform->elementExists('suspended')) {
                $mform->removeElement('suspended');
            }
        }
        if ($user and ($user->id == $USER->id or is_siteadmin($user))) {
            if ($mform->elementExists('suspended')) {
                $mform->hardFreeze('suspended');
            }
        }

        if (empty($USER->newadminuser)) {
            if ($user) {
                $context = context_user::instance($user->id, MUST_EXIST);
                $fs = get_file_storage();
                $hasuploadedpicture = ($fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.png') || $fs->file_exists($context->id, 'user', 'icon', 0, '/', 'f2.jpg'));
                if (!empty($user->picture) && $hasuploadedpicture) {
                    $imagevalue = $OUTPUT->user_picture($user, array('courseid' => SITEID, 'size' => 64));
                } else {
                    $imagevalue = get_string('none');
                }
            } else {
                $imagevalue = get_string('none');
            }
            $imageelement = $mform->getElement('currentpicture');
            $imageelement->setValue($imagevalue);

            if ($user && $mform->elementExists('deletepicture') && !$hasuploadedpicture) {
                $mform->removeElement('deletepicture');
            }
        }

        if ($mform->elementExists('theme') && $mform->isSubmitted()) {
            $theme = $mform->getSubmitValue('theme');
            if (!empty($user) && ($theme != $user->theme)) {
                theme_delete_used_in_context_cache($theme, $user->theme);
            }
        }

        profile_definition_after_data($mform, $userid);
    }

    /**
     * Validate the form data.
     */
    public function validation($usernew, $files) {
        global $CFG, $DB;

        $usernew = (object)$usernew;
        $usernew->username = trim($usernew->username);

        $user = $DB->get_record('user', array('id' => $usernew->id));
        $err = array();

        if (!$user and !empty($usernew->createpassword)) {
            if ($usernew->suspended) {
                $err['suspended'] = get_string('error');
            }
        } else {
            if (!empty($usernew->newpassword)) {
                $errmsg = '';
                if (!check_password_policy($usernew->newpassword, $errmsg, $usernew)) {
                    $err['newpassword'] = $errmsg;
                }
            } else if (!$user) {
                $auth = get_auth_plugin($usernew->auth);
                if ($auth->is_internal()) {
                    $err['newpassword'] = get_string('required');
                }
            }
        }

        // CUSTOM: Skip PESEL (username) validation if it's empty and we're editing an existing user.
        if (empty($usernew->username)) {
            if (!$user) {
                // New user - username is still required.
                $err['username'] = get_string('required');
            }
            // For existing user: skip validation if PESEL is empty.
        } else if (!$user or $user->username !== $usernew->username) {
            if ($DB->record_exists('user', array('username' => $usernew->username, 'mnethostid' => $CFG->mnet_localhost_id))) {
                $err['username'] = get_string('usernameexists');
            }
            if ($usernew->username !== core_text::strtolower($usernew->username)) {
                $err['username'] = get_string('usernamelowercase');
            } else {
                if ($usernew->username !== core_user::clean_field($usernew->username, 'username')) {
                    $err['username'] = get_string('invalidusername');
                }
            }
        }

        if (!$user or (isset($usernew->email) && $user->email !== $usernew->email)) {
            if (!empty($usernew->email) && !validate_email($usernew->email)) {
                $err['email'] = get_string('invalidemail');
            } else if (!empty($usernew->email) && empty($CFG->allowaccountssameemail)) {
                $select = $DB->sql_equal('email', ':email', false) . ' AND mnethostid = :mnethostid AND id <> :userid';
                $params = array(
                    'email' => $usernew->email,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'userid' => $usernew->id
                );
                if ($DB->record_exists_select('user', $select, $params)) {
                    $err['email'] = get_string('emailexists');
                }
            }
        }

        $err += profile_validation($usernew, $files);

        if (count($err) == 0) {
            return true;
        } else {
            return $err;
        }
    }
}

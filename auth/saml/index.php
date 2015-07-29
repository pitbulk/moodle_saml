<?php

define('SAML_INTERNAL', 1);

    try{

        // In order to avoid session problems we first do the SAML issues and then
        // we log in and register the attributes of user, but we need to read the value of the $CFG->dataroot
        $dataroot = null;
        if (file_exists('../../config.php')) {
            $config_content =  file_get_contents('../../config.php');

            $matches = array();
            if (preg_match("/[\$]CFG->dataroot[\s]*[\=][\s]*'([\w\/\-\_]*)'/i", $config_content, $matches)) {
                $dataroot = $matches[1];
            }
        }

        // We read saml parameters from a config file instead from the database
        // due we can not operate with the moodle database without load all
        // moodle session issue.
        if(isset($dataroot) && file_exists($dataroot.'/saml_config.php')) {
            $contentfile = file_get_contents($dataroot.'/saml_config.php');
        }
        else if (file_exists('saml_config.php')) {
            $contentfile = file_get_contents('saml_config.php');
        } else {
            throw(new Exception('SAML config params are not set.'));
        }

    	$saml_param = json_decode($contentfile);

        if(!file_exists($saml_param->samllib.'/_autoload.php')) {
            throw(new Exception('simpleSAMLphp lib loader file does not exist: '.$saml_param->samllib.'/_autoload.php'));
        }
        include_once($saml_param->samllib.'/_autoload.php');
        $as = new SimpleSAML_Auth_Simple($saml_param->sp_source);

        if(isset($_GET["logout"])) {
            if(isset($_SERVER['SCRIPT_URI'])) {
                $urltogo = $_SERVER['SCRIPT_URI'];
                $urltogo = str_replace('auth/saml/index.php', '', $urltogo);
            }
            else if(isset($_SERVER['HTTP_REFERER'])) {
                $urltogo = $_SERVER['HTTP_REFERER'];
            }
            else{
                $urltogo = '/';
            }

            if($saml_param->dosinglelogout) {
                $as->logout($urltogo);
                assert("FALSE"); // The previous line issues a redirect
            } else {
                header('Location: '.$urltogo);
                exit();
            }
        }

        $as->requireAuth();
        $valid_saml_session = $as->isAuthenticated();
        $saml_attributes = $as->getAttributes();
    } catch (Exception $e) {
        session_write_close();
        require_once('../../config.php');
        require_once('error.php');

        global $err, $PAGE, $OUTPUT;
        $PAGE->set_url('/auth/saml/index.php');
        $PAGE->set_context(CONTEXT_SYSTEM::instance());

        $pluginconfig = get_config('auth/saml');
        $urltogo = $CFG->wwwroot;
        if($CFG->wwwroot[strlen($CFG->wwwroot)-1] != '/') {
            $urltogo .= '/';
        }

        $err['login'] = $e->getMessage();
        auth_saml_log_error('Moodle SAML module:'. $err['login'], $pluginconfig->samllogfile);;
        auth_saml_error($err['login'], $urltogo, $pluginconfig->samllogfile);
    }

    // Now we close simpleSAMLphp session
    session_write_close();

    // We load all moodle config and libs
    require_once('../../config.php');
    require_once('error.php');

    global $CFG, $USER, $SAML_COURSE_INFO, $SESSION, $err, $DB, $PAGE;

    $PAGE->set_url('/auth/saml/index.php');
    $PAGE->set_context(CONTEXT_SYSTEM::instance());

    $urltogo = $CFG->wwwroot;
    if($CFG->wwwroot[strlen($CFG->wwwroot)-1] != '/') {
        $urltogo .= '/';
    }

     // set return rul from wantsurl
     if(isset($_REQUEST['wantsurl'])) {
        $urltogo = $_REQUEST['wantsurl'];
     }

    // Get the plugin config for saml
    $pluginconfig = get_config('auth/saml');

    if (!$valid_saml_session) {
	    // Not valid session. Ship user off to Identity Provider
        unset($USER);
        try {
            $as = new SimpleSAML_Auth_Simple($saml_param->sp_source);
            $as->requireAuth();
        } catch (Exception $e) {
            $err['login'] = $e->getMessage();
            auth_saml_error($err['login'], $urltogo, $pluginconfig->samllogfile);
        }
    } else {
        // Valid session. Register or update user in Moodle, log him on, and redirect to Moodle front
        if (isset($pluginconfig->samlhookfile) && $pluginconfig->samlhookfile != '') {
            include_once($pluginconfig->samlhookfile);
        }

        if (function_exists('saml_hook_attribute_filter')) {
            saml_hook_attribute_filter($saml_attributes);
        }

        // We require the plugin to know that we are now doing a saml login in hook puser_login
        $SESSION->auth_saml_login = TRUE;

        // Make variables accessible to saml->get_userinfo. Information will be
        // requested from authenticate_user_login -> create_user_record / update_user_record
        $SESSION->auth_saml_login_attributes = $saml_attributes;

        if (isset($pluginconfig->username) && $pluginconfig->username != '') {
            $username_field = $pluginconfig->username;
        } else {
            $username_field = 'eduPersonPrincipalName';
        }

        if(!isset($saml_attributes[$username_field])) {
            $err['login'] = get_string("auth_saml_username_not_found", "auth_saml", $username_field);
            auth_saml_error($err['login'], '?logout', $pluginconfig->samllogfile);
        }
        $username = $saml_attributes[$username_field][0];
        $username = trim(core_text::strtolower($username));

        $saml_courses = array();
        if($pluginconfig->supportcourses != 'nosupport' && isset($pluginconfig->samlcourses)) {
            if(!isset($saml_attributes[$pluginconfig->samlcourses])) {
                $err['login'] = get_string("auth_saml_courses_not_found", "auth_saml", $pluginconfig->samlcourses);
                auth_saml_error($err['login'], '?logout', $pluginconfig->samllogfile);
            }
            $saml_courses = $saml_attributes[$pluginconfig->samlcourses];
        }

        // Obtain the course_mapping. Now $USER->mapped_courses have the mapped courses and $USER->mapped_roles the roles
        if($pluginconfig->supportcourses != 'nosupport' ) {
            $any_course_active = false;
            include_once('course_mapping.php');
            $SAML_COURSE_INFO->mapped_roles = $mapped_roles;
            $SAML_COURSE_INFO->mapped_courses = $mapped_courses;
        }

        // Check if user exist
        $user_exists = $DB->get_record("user", array("username" => $username));
        
        if (function_exists('saml_hook_user_exists')) {
            $user_exists = $user_exists && saml_hook_user_exists($username, $saml_attributes, $user_exists);
        }

        $authorize_user = true;
        $authorize_error = '';

        // If user not exist in Moodle and not valid course active
        if(!$user_exists && (isset($any_course_active) && !$any_course_active)) {
            $authorize_error = get_string("auth_saml_not_authorize", "auth_saml", $username);
            $authorize_user = false;
        }

        if (function_exists('saml_hook_authorize_user')) {
            $result = saml_hook_authorize_user($username, $saml_attributes, $authorize_user);
            if ($result !== true) {
                $authorize_user = false;
                $authorize_error = $result;
            }
        }

        if (!$authorize_user) {
            $err['login'] = "<p>" . $authorize_error . "</p>";
	    auth_saml_error($err, '?logout', $pluginconfig->samllogfile);
        }
        
        // Just passes time as a password. User will never log in directly to moodle with this password anyway or so we hope?
        $user = authenticate_user_login($username, time());
        if ($user === false) {
            $err['login'] = get_string("auth_saml_error_authentication_process", "auth_saml", $username);
            auth_saml_error($err['login'], '?logout', $pluginconfig->samllogfile);
        }

        // Complete the user login sequence
        $user = get_complete_user_data('id', $user->id);
        if ($user === false) {
            $err['login'] = get_string("auth_saml_error_complete_user_data", "auth_saml", $username);
            auth_saml_error($err['login'], '?logout', $pluginconfig->samllogfile);
        }

        $USER = complete_user_login($user);

        if (function_exists('saml_hook_post_user_created')) {
            saml_hook_post_user_created($USER);
        }

        if (isset($SESSION->wantsurl) && !empty($SESSION->wantsurl)) {
             $urltogo = $SESSION->wantsurl;
        }

        $USER->loggedin = true;
        $USER->site = $CFG->wwwroot;
        set_moodle_cookie($USER->username);

        if(isset($err) && !empty($err)) {
            auth_saml_error($err, $urltogo, $pluginconfig->samllogfile);
        }
        redirect($urltogo);
    }

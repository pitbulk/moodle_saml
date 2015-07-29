<?php
/**
 * @author Erlend Strømsvik - Ny Media AS
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth/saml
 * @version 1.0
 *
 * Authentication Plugin: SAML based SSO Authentication
 *
 * Authentication using SAML2 with SimpleSAMLphp.
 *
 * Based on plugins made by Sergio Gómez (moodle_ssp) and Martin Dougiamas (Shibboleth).
 *
 * 2008-10  Created
 * 2009-07  added new configuration options.  Tightened up the session handling
**/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');

/**
 * SimpleSAML authentication plugin.
**/
class auth_plugin_saml extends auth_plugin_base {

    /**
    * Constructor.
    */
    function auth_plugin_saml() {
		$this->authtype = 'saml';
		$this->config = get_config('auth/saml');
    }

    /**
    * Returns true if the username and password work and false if they are
    * wrong or don't exist.
    *
    * @param string $username The username (with system magic quotes)
    * @param string $password The password (with system magic quotes)
    * @return bool Authentication success or failure.
    */
    function user_login($username, $password) {
        global $SESSION;
	    // if true, user_login was initiated by saml/index.php
	    if(isset($SESSION->auth_saml_login) && $SESSION->auth_saml_login) {
	        unset($SESSION->auth_saml_login);
	        return TRUE;
	    }

	    return FALSE;
    }


    /**
    * Returns the user information for 'external' users. In this case the
    * attributes provided by Identity Provider
    *
    * @return array $result Associative array of user data
    */
    function get_userinfo($username) {
        global $SESSION;
	    if($login_attributes = $SESSION->auth_saml_login_attributes) {
	        $attributemap = $this->get_attributes();
	        $result = array();

	        foreach ($attributemap as $key => $value) {
		        if(isset($login_attributes[$value]) && $attribute = $login_attributes[$value][0]) {
		            $result[$key] = $attribute;
		        } else {
		            $result[$key] = '';
		        }
	        }
	        unset($SESSION->auth_saml_login_attributes);

	        $result["username"] = $username;
	        return $result;
	    }

	    return FALSE;
    }

    /*
    * Returns array containg attribute mappings between Moodle and Identity Provider.
    */
    function get_attributes() {
	    $configarray = (array) $this->config;

        if(isset($this->userfields)) {
            $fields = $this->userfields;
        }
        else {
        	$fields = array("firstname", "lastname", "email", "phone1", "phone2",
			    "department", "address", "city", "country", "description",
			    "idnumber", "lang", "guid");
        }

	    $moodleattributes = array();
	    foreach ($fields as $field) {
	        if (isset($configarray["field_map_$field"])) {
		        $moodleattributes[$field] = $configarray["field_map_$field"];
	        }
	    }

	    return $moodleattributes;
    }

    /**
    * Returns true if this authentication plugin is 'internal'.
    *
    * @return bool
    */
    function is_internal() {
	    return false;
    }

    /**
    * Returns true if this authentication plugin can change the user's
    * password.
    *
    * @return bool
    */
    function can_change_password() {
	    return false;
    }

    function pre_loginpage_hook() {
        // If Force Login is on then we can safely jump directly to the SAML IdP
        if (isset($this->config->autologin) && $this->config->autologin) {
            global $CFG, $SESSION;
            $samlurl = $CFG->wwwroot.'/auth/saml/index.php?wantsurl=' . urlencode($SESSION->wantsurl);
            redirect($samlurl);
        }
    }

    function loginpage_hook() {
	    global $CFG;

        if (empty($CFG->alternateloginurl) && !(isset($_GET['saml']) && $_GET['saml'] === 'false')) {
            $CFG->alternateloginurl = $CFG->wwwroot.'/auth/saml/login.php';
        }

	    // Prevent username from being shown on login page after logout
	    $CFG->nolastloggedin = true;
    }

    function logoutpage_hook() {
        global $CFG;

	    if(isset($this->config->dosinglelogout) && $this->config->dosinglelogout) {
	        set_moodle_cookie('nobody');
	        require_logout();
	        redirect($CFG->wwwroot.'/auth/saml/index.php?logout=1');
	    }
    }

    /**
    * Prints a form for configuring this authentication plugin.
    *
    * This function is called from admin/auth.php, and outputs a full page with
    * a form for configuring this plugin.
    *
    * @param array $page An object containing all the data for this page.
    */

    function config_form($config, $err, $user_fields) {
	    global $CFG, $DB;

        $dbman = $DB->get_manager();

        $table_course_mapping = $this->get_course_mapping_xmldb();
        $table_role_mapping = $this->get_role_mapping_xmldb();        

	    if(isset($config->supportcourses) &&  $config->supportcourses == 'internal') {
	        if(!$dbman->table_exists($table_course_mapping)) {
		        $this->create_course_mapping_db($DB, $err);
	        }
	        if(!$dbman->table_exists($table_role_mapping)) {
		        $this->create_role_mapping_db($DB, $err);
	        }
	    }

	    $course_mapping = array();
	    $role_mapping = array();
	    if($dbman->table_exists($table_course_mapping)) {
	        require_once ($CFG->dirroot . "/auth/saml/courses.php");
	        $course_mapping = get_course_mapping($DB, $err);
	    }
	    if($dbman->table_exists($table_role_mapping)) {
	        require_once ($CFG->dirroot . "/auth/saml/roles.php");
	        $role_mapping = get_role_mapping($DB, $err);
	    }
	    require_once ($CFG->dirroot . "/auth/saml/config.php");
    }

    /**
     * A chance to validate form data, and last chance to
     * do stuff before it is inserted in config_plugin
     */
    function validate_form($form, &$err) {

	    if(!isset($form->auth_saml_db_reset) && !isset($form->initialize_roles)) {
	        if (!isset ($form->samllib) || !file_exists($form->samllib.'/_autoload.php')) {
		        $err['samllib'] = get_string('auth_saml_errorbadlib', 'auth_saml', $form->samllib);
	        }

            if (isset($form->samlhookfile) && $form->samlhookfile != '' && !file_exists($form->samlhookfile)) {
		        $err['samlhookerror'] = get_string('auth_saml_errorbadhook', 'auth_saml', $form->samlhookfile);
	        }

	        if ($form->supportcourses == 'external') {
		        if ($form->externalcoursemappingdsn == '' || $form->externalcoursemappingsql == '' || $form->externalrolemappingdsn == '' || $form->externalrolemappingsql == '') {   
		            $err['samlexternal'] = get_string('auth_saml_errorsamlexternal', 'auth_saml', $form->samllib);
		        }		 
	        }
	        else if($form->supportcourses == 'internal') {

		        if(!isset($form->deletecourses)) {
		            $lms_course_form_id = array();
		            $saml_course_form_id = array();
		            if (isset($form->update_courses_id)) {
			            foreach ($form->update_courses_id as $course_id) {
			                $course = $form->{'course_' . $course_id};
			                if (!empty($course[1]) && !empty($course[2])) {			    
				                $lms_course_form_id[$course_id] = $course[0];
				                $saml_course_form_id[$course_id] = $course[1] . '_' . $course[2];
			                }
			                else {
				                $err['missed_course_mapping'][$course_id] = $course[0];
			                }
			            }
		            }
		            if (isset($form->new_courses_total)) {
			            for ($i = 0; $i <= $form->new_courses_total; $i++) {
			                $new_course = $form->{'new_course' . $i};
			                if (!empty($new_course[1]) && !empty($new_course[2])) {
				                $lms_course_form_id[$i] = $new_course[0];
				                $saml_course_form_id[$i] = $new_course[1] . '_' . $new_course[2];
			                }
			            }		 
		            }
		            //Comment the next line if you want let duplicate lms mapping
		            $err['course_mapping']['lms'] = array_diff_key($lms_course_form_id, array_unique($lms_course_form_id));

		            $err['course_mapping']['saml'] = array_diff_key($saml_course_form_id, array_unique($saml_course_form_id));
		            if (empty($err['course_mapping']['lms']) && empty($err['course_mapping']['saml'])) {
			            unset($err['course_mapping']);
		            }
		        }

		        if(!isset($form->deleteroles)) {
		            $lms_role_form_id = array();
		            $saml_role_form_id = array();
		            if (isset($form->update_roles_id)) {
			        foreach ($form->update_roles_id as $role_id) {
			            $role = $form->{'role_' . $role_id};
			            if (!empty($role[0]) && !empty($role[1])) {
				            $lms_role_form_id[] = $role[0];
				            $saml_role_form_id[] = $role[1];
			            }
			            else {
				            if(!isset($form->deleteroles)) {
				                $err['missed_role_mapping'][$role_id] = $role[0];
				            }
			            }
			        }
		            }
		            if (isset($form->new_roles_total)) {
			            for ($i=0; $i <= $form->new_roles_total; $i++) {
			                $new_course = $form->{'new_role' . $i};
			                if (!empty($new_role[1])) {
				                $lms_role_form_id[] = $new_role[0];
				                $saml_role_form_id[] = $new_role[1];
			                }
			            }
		            }
		            //$err['role_mapping']['lms'] = array_diff_key($lms_role_form_id, array_unique($lms_role_form_id));
		            $err['role_mapping']['saml'] = array_diff_key($saml_role_form_id, array_unique($saml_role_form_id));
		        }

		        if (empty($err['role_mapping']['lms']) && empty($err['role_mapping']['saml'])) {
		            unset($err['role_mapping']);
		        }
            }
	    }
    }

    /**
    * Processes and stores configuration data for this authentication plugin.
    *
    *
    * @param object $config Configuration object
    */
    function process_config($config) {
        global $err, $DB, $CFG;

        $dbman = $DB->get_manager();

	    if(isset($config->auth_saml_db_reset)) {
	        $sql = "DELETE FROM ".$CFG->prefix."config_plugins WHERE plugin = 'auth/saml';";
            try {
	            $DB->execute($sql);
            }
            catch (Exception $e) {
                $err['reset'] = get_string("auth_saml_db_reset_error", "auth_saml");
		        return false;
	        }
	        header('Location: ' . $CFG->wwwroot . '/admin/auth_config.php?auth=saml');
	        exit();
	    }

	    if(isset($config->initialize_roles)) {
	        global $CFG;
	        $this->initialize_roles($DB, $err);
	        header('Location: ' . $CFG->wwwroot . '/admin/auth_config.php?auth=saml#rolemapping');
	        exit();
	    }

        // SAML parameters are in the config variable due all form data is there.
        // We create a new variable and set the values there.
        $saml_param = new stdClass();

	    if (!isset ($config->samllib)) {
	        $saml_param->samllib = '';
	    }
        else {
            $saml_param->samllib = $config->samllib;
        }
	    if (!isset ($config->sp_source)) {
	        $saml_param->sp_source = 'saml';
	    }
        else {
            $saml_param->sp_source = $config->sp_source;
        }
	    if (!isset ($config->dosinglelogout)) {
	        $saml_param->dosinglelogout = false;
	    }
        else {
            $saml_param->dosinglelogout = $config->dosinglelogout;
        }

	    // set to defaults if undefined
	    if (!isset ($config->username)) {
	        $config->username = 'eduPersonPrincipalName';
	    }
        if (!isset ($config->supportcourses)) {
	        $config->supportcourses = 'nosupport';
	    }
	    if (!isset ($config->syncusersfrom)) {
	        $config->syncusersfrom = '';
	    }
	    if (!isset ($config->samlcourses)) {
	        $config->samlcourses = 'schacUserStatus';
	    }
	    if (!isset ($config->samllogoimage) || $config->samllogoimage == NULL) {
	        $config->samllogoimage = 'logo.gif';
	    }
	    if (!isset ($config->samllogoinfo)) {
	        $config->samllogoinfo = 'SAML login';
	    }
	    if (!isset ($config->autologin)) { 
            $config->autologin = false; 
        }
	    if (!isset ($config->samllogfile)) {
	        $config->samllogfile = '';
	    }
        if (!isset ($config->samlhookfile)) {
            $config->samlhookfile = $CFG->dirroot . '/auth/saml/custom_hook.php';
        }
	    if (!isset ($config->moodlecoursefieldid)) {
	        $config->moodlecoursefieldid = 'shortname';
	    }
	    if (!isset ($config->ignoreinactivecourses)) {
	        $config->ignoreinactivecourses = '';
	    }
	    if (!isset ($config->externalcoursemappingdsn)) {
	        $config->externalcoursemappingdsn = ''; 
	    }
	    if (!isset ($config->externalrolemappingdsn)) {
	        $config->externalrolemappingdsn = ''; 
	    }
	    if (!isset ($config->externalcoursemappingsql)) {
	        $config->externalcoursemappingsql = ''; 
	    }
	    if (!isset ($config->externalrolemappingsql)) {
	        $config->externalrolemappingsql = ''; 
	    }

        // Save saml settings in a file
    	$saml_param_encoded = json_encode($saml_param);
        file_put_contents($CFG->dataroot.'/saml_config.php', $saml_param_encoded);

        // Also adding this parameters in database but no need it really.
	    set_config('samllib',	      $saml_param->samllib,	'auth/saml');
	    set_config('sp_source',  $saml_param->sp_source,	'auth/saml');
	    set_config('dosinglelogout',  $saml_param->dosinglelogout,	'auth/saml');

        // Save plugin settings
	    set_config('username',	      $config->username,	'auth/saml');
	    set_config('supportcourses',  $config->supportcourses,	'auth/saml');
	    set_config('syncusersfrom',   $config->syncusersfrom,	'auth/saml');
	    set_config('samlcourses',     $config->samlcourses,	'auth/saml');
	    set_config('samllogoimage',   $config->samllogoimage,	'auth/saml');
	    set_config('samllogoinfo',    $config->samllogoinfo,	'auth/saml');
	    set_config('autologin',       $config->autologin,  'auth/saml');
	    set_config('samllogfile',         $config->samllogfile,	'auth/saml');
	    set_config('samlhookfile',        $config->samlhookfile,	'auth/saml');
	    set_config('moodlecoursefieldid',   $config->moodlecoursefieldid,   'auth/saml');
	    set_config('ignoreinactivecourses', $config->ignoreinactivecourses, 'auth/saml');

	    if($config->supportcourses == 'external') {
	        set_config('externalcoursemappingdsn',  $config->externalcoursemappingdsn,	'auth/saml');
	        set_config('externalrolemappingdsn',    $config->externalrolemappingdsn,	'auth/saml');
	        set_config('externalcoursemappingsql',  $config->externalcoursemappingsql,	'auth/saml');
	        set_config('externalrolemappingsql',    $config->externalrolemappingsql,	'auth/saml');
	    }
	    else if($config->supportcourses == 'internal') {

            $table_course_mapping = $this->get_course_mapping_xmldb();
            $table_role_mapping = $this->get_role_mapping_xmldb();

	        if(!$dbman->table_exists($table_course_mapping)) {
                $this->create_course_mapping_db($DB, $err);
		    }
	        if(!$dbman->table_exists($table_role_mapping)) {
                $this->create_role_mapping_db($DB, $err);
		    }

		    //COURSE MAPPINGS
		    //Delete mappings
		    if (isset($config->deletecourses)) {
		        if(isset($config->course_mapping_id)) {
			        foreach ($config->course_mapping_id as $course => $value) {
			            $sql = "DELETE FROM ".$DB->get_prefix() ."course_mapping WHERE course_mapping_id='". $value ."'";
			            try {
                            $DB->execute($sql);
                        }
                        catch (Exception $e) {
				            $err['course_mapping_db'][] = get_string("auth_saml_error_executing", "auth_saml").$sql;
			            }
			        }
		        }
		    } else {
		        //Update mappings
		        if (isset($config->update_courses_id) && empty($err['course_mapping'])) {
			        foreach($config->update_courses_id as $course_id) {
			            $course = $config->{'course_' . $course_id};
			            $sql = "UPDATE ".$DB->get_prefix() ."course_mapping SET lms_course_id='".$course[0]."', saml_course_id='".$course[1]."', saml_course_period='".$course[2]."' where course_mapping_id='". $course_id ."'";
                        try {
    			            $DB->execute($sql);
                        }
                        catch (Exception $e) {
				            $err['course_mapping_db'][] = get_string("auth_saml_error_executing", "auth_saml").$sql;
			            }
			        }
		        }

		        //New courses mapping
		        if (isset($config->new_courses_total) && empty($err['course_mapping'])) {
			        for ($i = 0; $i <= $config->new_courses_total; $i++) {
			            $new_course = $config->{'new_course' . $i};
			            if (!empty($new_course[1]) && !empty($new_course[2])) {
				            $sql = "INSERT INTO ".$DB->get_prefix() ."course_mapping (lms_course_id, saml_course_id, saml_course_period) values('".$new_course[0]."', '".$new_course[1]."', '".$new_course[2]."')";
                            try {
        			            $DB->execute($sql);
                            }
                            catch (Exception $e) {
				                $err['course_mapping_db'][] = get_string("auth_saml_error_executing", "auth_saml").$sql;
				            }
			            }
			        }
		        }
		    }
		    //END-COURSE MAPPINGS

		    //ROLE MAPPINGS
		    //Deleting roles
		    if (isset($config->deleteroles)) {
		        if(isset($config->role_mapping_id)) {
     		        foreach ($config->role_mapping_id as $role => $value) {
			            $sql = "DELETE FROM ".$DB->get_prefix() ."role_mapping WHERE saml_role='" . $value . "'";
                        try {
    			            $DB->execute($sql);
                        }
                        catch (Exception $e) {
			                $err['role_mapping_db'][] = get_string("auth_saml_error_executing", "auth_saml").$sql;
			            }
			        }
		        }
		    } 
            else {
		        //Updating roles
		        if (isset($config->update_roles_id) && empty($err['roles_mapping'])) {
			        foreach($config->update_roles_id as $role_id) {
			            $role = $config->{'role_' . $role_id};
			            $sql = "UPDATE ".$DB->get_prefix() ."role_mapping SET lms_role='" . $role[0] . "', saml_role='" . $role[1] . "' where saml_role='" . $role_id . "'"; 
                        try {
    			            $DB->execute($sql);
                        }
                        catch (Exception $e) {
				            $err['role_mapping_db'][] = get_string("auth_saml_error_executing", "auth_saml").$sql;
			            }
			        }
		        }
		        //New roles mapping
		        if (isset($config->new_roles_total) && empty($err['roles_mapping'])) {
			        for ($i = 0; $i <= $config->new_roles_total; $i++) {
			            $new_role = $config->{'new_role' . $i};
			            if (!empty($new_role[0]) && !empty($new_role[1])) {
				            $sql = "INSERT INTO ".$DB->get_prefix() ."role_mapping (lms_role, saml_role) values('".$new_role[0]."', '".$new_role[1]."')";
                            try {
        			            $DB->execute($sql);
                            }
                            catch (Exception $e) {
				                $err['role_mapping_db'][] = get_string("auth_saml_error_executing", "auth_saml").$sql;
				            }
			            }
			        }
		        }
		    }

		    if(isset($err['role_mapping_db']) || isset($err['course_mapping_db'])) {
		        return false;
		    }
	
		    //END-COURSE MAPPINGS
	    }
	    return true;
    }

    /**
    * Cleans and returns first of potential many values (multi-valued attributes)
    *
    * @param string $string Possibly multi-valued attribute from Identity Provider
    */
    function get_first_string($string) {
	    $list = split( ';', $string);
	    $clean_string = trim($list[0]);

	    return $clean_string;
    }


  /**
    * Create course_mapping table in Moodle database.
    *
    */
    function create_course_mapping_db($DB, &$err) {

        $table = $this->get_course_mapping_xmldb();

        $sucess = false;
        try {
            $dbman = $DB->get_manager();
            $dbman->create_table($table);
	        echo '<span class="notifysuccess">';
	        print_string("auth_saml_sucess_creating_course_mapping", "auth_saml");
	        echo '</span><br>';
            $sucess = true;
        }
        catch (Exception $e) {
            $err['course_mapping_db'][] = get_string("auth_saml_error_creating_course_mapping", "auth_saml");
        }
        return $sucess;
    }

    /**
    * Create role_mapping table in Moodle database.
    *
    */
    function create_role_mapping_db($DB, &$err) {

        $table = $this->get_role_mapping_xmldb();

        $sucess = false;
        try {
            $dbman = $DB->get_manager();
            $dbman->create_table($table);
	        echo '<span class="notifysuccess">';
	        print_string("auth_saml_sucess_creating_role_mapping", "auth_saml");
	        echo '</span><br>';	
        }
        catch (Exception $e) {
	        $err['role_mapping_db'][] = get_string("auth_saml_error_creating_role_mapping", "auth_saml");
        }
        return $sucess;
    }

    function get_course_mapping_xmldb() {

        $table = new xmldb_table('course_mapping');   

        $table->add_field('course_mapping_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
		$table->add_field('saml_course_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('saml_course_period', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('lms_course_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('course_mapping_id'));

        return $table;
    }

    function get_role_mapping_xmldb() {
        $table = new xmldb_table('role_mapping');

		$table->add_field('saml_role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('lms_role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('saml_role'));

        return $table;
    }


    function initialize_roles($DB, &$err) {

	    $sqls = array();
	    $sqls[] = "DELETE FROM role_mapping;";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('editingteacher','teacher')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('editingteacher','instructor')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('editingteacher','mentor')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('student','student')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('student','learner')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('user','member')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('admin','admin')";

        $sucess = true;
	    foreach($sqls as $sql) {
            try {
	            $DB->execute($sql);
            }
            catch (Exception $e) {
    		    $err['role_mapping_db'][] = get_string("auth_saml_error_creating_role_mapping", "auth_saml");
                $sucess = false;
    		    break;
	        }
	    }
	    return $sucess;
    }	   
}

<?php
/**
 * SAML enrolment plugin implementation.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_saml_plugin extends enrol_plugin {


    /**
     * Returns localised name of enrol instance
     *
     * @param object $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid) and $role = $DB->get_record('role', array('id'=>$instance->roleid))) {
                $context = context_course::instance($instance->courseid);
                $role = ' (' . role_get_name($role, $context) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_'.$enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    public function roles_protected() {
        // users may tweak the roles later
        return false;
    }

    public function allow_unenrol(stdClass $instance) {
        // users with unenrol cap may unenrol other users
        return true;
    }

    public function allow_manage(stdClass $instance) {
        // users with manage cap may tweak period and status
        return true;
    }

    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Sets up navigation entries.
     *
     * @param object $instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        if ($instance->enrol !== 'saml') {
             throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        if (has_capability('enrol/saml:config', $context)) {
            $managelink = new moodle_url('/enrol/saml/edit.php', array('courseid'=>$instance->courseid, 'id'=>$instance->id));
            $instancesnode->add($this->get_instance_name($instance), $managelink, navigation_node::TYPE_SETTING);
        }
    }

    /**
     * Returns edit icons for the page with list of instances
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        if ($instance->enrol !== 'saml') {
            throw new coding_exception('invalid enrol instance!');
        }
        $context = context_course::instance($instance->courseid);

        $icons = array();

        if (has_capability('enrol/saml:manage', $context)) {
            $managelink = new moodle_url("/enrol/saml/manage.php", array('enrolid'=>$instance->id));
            $icons[] = $OUTPUT->action_icon($managelink, new pix_icon('i/users', get_string('enrolusers', 'enrol_saml'), 'core', array('class'=>'iconsmall')));
        }
        if (has_capability('enrol/saml:config', $context)) {
            $editlink = new moodle_url("/enrol/saml/edit.php", array('courseid'=>$instance->courseid));
            $icons[] = $OUTPUT->action_icon($editlink, new pix_icon('i/edit', get_string('edit'), 'core', array('class'=>'icon')));
        }

        return $icons;
    }

    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        global $DB;

        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) or !has_capability('enrol/saml:config', $context)) {
            return NULL;
        }

        if ($DB->record_exists('enrol', array('courseid'=>$courseid, 'enrol'=>'saml'))) {
            return NULL;
        }

        return new moodle_url('/enrol/saml/edit.php', array('courseid'=>$courseid));
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param object $course
     * @return int id of new instance, null if can not be created
     */
    public function add_default_instance($course) {
        $fields = array('status'=>$this->get_config('status'), 'enrolperiod'=>$this->get_config('enrolperiod', 0), 'roleid'=>$this->get_config('roleid', 0));
        return $this->add_instance($course, $fields);
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = NULL) {
        global $DB;

        if ($DB->record_exists('enrol', array('courseid'=>$course->id, 'enrol'=>'saml'))) {
            // only one instance allowed, sorry
            return NULL;
        }

        return parent::add_instance($course, $fields);
    }

    public function get_instance($course) {
        $enrolinstances = enrol_get_instances($course->id, true);
        $instance = null;
        foreach ($enrolinstances as $courseenrolinstance) {
          if ($courseenrolinstance->enrol == "saml") {
              $instance = $courseenrolinstance;
              break;
          }
        }
        return $instance;
    }

    public function get_or_create_instance($course) {
        $instance = $this->get_instance($course);
        if (empty($instance)) {
            $instance = $this->add_instance($course);
        }
        return $instance;
    }

    public function sync_user_enrolments($user) {
        // Configuration is in the auth/saml config file. (Not in the enrol/saml)
        $pluginconfig = get_config('auth/saml');
        
        global $DB, $SAML_COURSE_INFO, $err;

        if($pluginconfig->supportcourses != 'nosupport' ) {

	        if(!isset($pluginconfig->moodlecoursefieldid)) {
		        $pluginconfig->moodlecoursefieldid = 'shortname';
	        }
	        try {
                $plugin = enrol_get_plugin('saml');
		        foreach($SAML_COURSE_INFO->mapped_roles as $role) {		       
		            $moodle_role = $DB->get_record("role", array("shortname" =>$role));
		            if($moodle_role) {
			            $new_course_ids_with_role = array();
			            $delete_course_ids_with_role = array();
			            if (isset($SAML_COURSE_INFO->mapped_courses[$role])) {
			                if(isset($SAML_COURSE_INFO->mapped_courses[$role]['active'])) {
				                $new_course_ids_with_role = array_keys($SAML_COURSE_INFO->mapped_courses[$role]['active']);
			                }
			                if(isset($SAML_COURSE_INFO->mapped_courses[$role]['inactive'])) {
				                $delete_course_ids_with_role = array_keys($SAML_COURSE_INFO->mapped_courses[$role]['inactive']);
			                }
			            }
			            if(!$pluginconfig->ignoreinactivecourses) {
			                foreach($delete_course_ids_with_role as $course_identify) {
				                if($course = $DB->get_record("course", array($pluginconfig->moodlecoursefieldid => $course_identify))) {
                                    $instance = $plugin->get_or_create_instance($course);
                                    if(!empty($instance)) {
                                        $plugin->unenrol_user($instance, $user->id);
                                    }				                }
			                }
			            }
			            foreach($new_course_ids_with_role as $course_identify) {
			                if($course = $DB->get_record("course", array($pluginconfig->moodlecoursefieldid => $course_identify))) {
                                $instance = $plugin->get_or_create_instance($course);
                                if(empty($instance)) {
                                    $err['enrollment'][] = get_string("error_instance_creation", "role_saml", $role, $course->id);
                                }
                                else {
                                    $plugin->enrol_user($instance, $user->id, $moodle_role->id, 0, 0, 0); // last parameter (status) 0->active  1->suspended                        
                                }
			                }    
			            }
		            }
		            else {
			            $err['enrollment'][] = get_string("auth_saml_error_role_not_found", "auth_saml", $role);
		            }
		        }
	        }
	        catch (Exception $e) {
		        $err['enrollment'][] = $e->getMessage();
	        }
	        unset($SAML_COURSE_INFO->mapped_courses);
	        unset($SAML_COURSE_INFO->mapped_roles);
	    }
    }
}

/**
 * Indicates API features that the enrol plugin supports.
 *
 * @param string $feature
 * @return mixed True if yes (some features may use other values)
 */
function enrol_saml_supports($feature) {
    switch($feature) {
        case ENROL_RESTORE_TYPE: return ENROL_RESTORE_EXACT;

        default: return null;
    }
}

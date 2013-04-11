<?php
function get_moodle_courses() {
    $moodle_courses = array();
    foreach(get_courses() as $course) {
        if(isset($config->moodlecoursefieldid) && $config->moodlecoursefieldid == 'idnumber') {
            $course_identify = $course->idnumber;
        }
        else {
            $course_identify = $course->shortname;
        }
        $moodle_courses[] = $course_identify;
    }
    return $moodle_courses;
}

function get_course_mapping(&$DB, &$err) {

    $course_mapping = array();
    $fields = 'course_mapping_id, saml_course_id, saml_course_period , lms_course_id';
    $rs = $DB->get_records_select('course_mapping', '', null, '', $fields, 0, 0);

    if ($rs === false){
        $err['course_mapping_db'][] = get_string("auth_saml_error_executing_course_mapping_query", "auth_saml");
    } else {

        //creating the courses mapping
        foreach ($rs as $tuple){
            $tuple = array_change_key_case((array)$tuple,CASE_LOWER);
            $course_mapping[$tuple['lms_course_id']] = array(
                'course_mapping_id' => $tuple['course_mapping_id'],
                'saml_course_id' => $tuple['saml_course_id'],
                'saml_course_period' => $tuple['saml_course_period'],
            );
        }
		unset($rs);
    }

    return $course_mapping;
}

function get_course_mapping_for_sync(&$err, $config) {

    $course_mapping = array();
    if($config->supportcourses == 'external') {
        require_once ("DBNewConnection.php"); 
        $DB_mapping = DBNewConnection($config->externalcoursemappingdsn);
        $rs = false;
        if($DB_mapping) {
            $DB_mapping->SetFetchMode(ADODB_FETCH_ASSOC);
            $rs = $DB_mapping->Execute($config->externalcoursemappingsql);
            if($rs !==false) {
                $res_array = $rs->GetAll();
            }            
            $DB_mapping->Disconnect();
        }
    }
    else {
        global $DB;
            $fields = 'course_mapping_id, saml_course_id, saml_course_period , lms_course_id';
            $rs = $DB->get_records_select('course_mapping', '', null, '', $fields, 0, 0);
            if($rs !==false) {
                $res_array = $rs;
            }
    }
    if ($rs === false){
        $err['course_mapping_db'][] = get_string("auth_saml_error_executing_course_mapping_query", "auth_saml");
    } else {
        //creating the courses mapping
        foreach ($res_array as $tuple) {
            $tuple = array_change_key_case((array)$tuple,CASE_LOWER);
            if(empty($tuple['saml_course_id']) || empty($tuple['saml_course_period']) || empty($tuple['lms_course_id']))  {
                $err['role_mapping_db'][] = "<p>" . get_string("auth_saml_error_attribute_course_mapping", "auth_saml") . "</p><p>saml_course_id:" . $tuple['saml_course_id'] . " saml_course_period: " . $tuple['saml_course_period'] . " lms_course_id:" . $tuple['lms_course_id'] . "</p>";
            }
            else {
                if(isset($course_mapping[$tuple['saml_course_id']][$tuple['saml_course_period']])) {
                    $err['course_mapping_db'][] = get_string('auth_saml_duplicated_saml_data', "auth_saml").' saml_course_id:'.$tuple['saml_course_id'].' saml_course_period:'.$tuple['saml_course_period'];
                }
                else {
            $course_mapping[$tuple['saml_course_id']][$tuple['saml_course_period']] = $tuple['lms_course_id']; 
                }
            }		
        }
	    unset($res_array);
	    unset($rs);
    }
    return $course_mapping;
}


function print_course_mapping_options($course_mapping, $config, $err) {

    if(isset($err['course_mapping_db'])) {
        foreach ($err['course_maping_db'] as $value) {
           echo '<tr><td colspan="4" style="color: red;text-align:center">';
           echo $value;  
           echo '</td></tr>';         
        }
    }

    if (array_key_exists('course_mapping', $err)) {
        echo '<tr><td colspan="4" style="color: red;text-align:center">';
        if (!empty($err['course_mapping']['saml'])) {
            echo "<p>" . get_string("auth_saml_duplicated_saml_data", "auth_saml") . implode(', ', $err['course_mapping']['saml']) . "</p>";
        }
        if (!empty($err['course_mapping']['lms'])) {
            echo get_string("auth_saml_duplicated_lms_data", "auth_saml") . implode(', ', $err['course_mapping']['lms']);
        }	
        echo '</td></tr>';
    }
    if (array_key_exists('missed_course_mapping', $err)) {
        echo '<tr><td colspan="4" style="color: red;text-align:center">';
        echo get_string("auth_saml_missed_data", "auth_saml") . implode(', ', array_unique($err['missed_course_mapping']));
        echo '</td></tr>';
    }

    echo '<tr><td colspan="2" style="padding-left: 44px;">Moodle Course Id</td><td>SAML Course Id</td><td>SAML Course Period</td></tr>';

    //if this is a GET (no errors) read the values from the database

    $new_courses_total = optional_param('new_courses_total', FALSE, PARAM_INT);
    $read_from_db_only = ($new_courses_total === FALSE);

    $moodle_courses = get_moodle_courses();
    foreach ($moodle_courses as $mcourse) {
        if (array_key_exists($mcourse, $course_mapping)) {
            $course_mapping_id = $course_mapping[$mcourse]['course_mapping_id'];
            $saml_course_id = $course_mapping[$mcourse]['saml_course_id'];
            $saml_course_period = $course_mapping[$mcourse]['saml_course_period'];

            $course_param = optional_param_array('course_' . $course_mapping_id, array(), PARAM_ALPHANUMEXT);

	        echo '<tr '.((isset($err['course_mapping']['lms']) && in_array($course_param[0], $err['course_mapping']['lms']))
            || (isset($err['course_mapping']['saml']) && in_array($course_param[1].'_'.$course_param[2], $err['course_mapping']['saml']))
            || (isset($err['missed_course_mapping']) && array_key_exists($course_mapping_id, $err['missed_course_mapping']))
            ? 'style="background:red;"' : '').'>';

            echo '<td colspan="2"><input style="margin-right: 20px;" type="checkbox"';
			echo 'name="course_mapping_id[]" value="'.$course_mapping_id.'">';
            echo('<input type="hidden" name="update_courses_id[]" value="' . $course_mapping_id . '">');
            echo '<select name="course_'. $course_mapping_id .'[]" >';
            foreach ($moodle_courses as $mcourse2) {
                echo '<option value="'. $mcourse2 .'" '.((!$read_from_db_only && $course_param[0] == $mcourse2) || ($read_from_db_only && $mcourse2 == $mcourse) ? 'selected="selected"' : '') .' >'.$mcourse2.'</option>';
            }
            echo '</select></td>';
            $course_name = ($read_from_db_only ? $saml_course_id : $course_param[1]);
            $course_period = ($read_from_db_only ? $saml_course_period : $course_param[2]);
            echo '<td><input type="text" name="course_'. $course_mapping_id .'[]" value="' . $course_name . '" /></td>';
            echo '<td><input type="text" name="course_'. $course_mapping_id .'[]" value="' . $course_period . '" /></td>';
        	echo '</tr>';
        }
    }

    //New mappings
    echo '<tr><td colspan="4"><hr /></td></tr>';
    $i = 0;
    if ($read_from_db_only) {
	    while ($i <= $new_courses_total) {

            $new_course_param = optional_param_array('new_course_' . $i, array(), PARAM_ALPHANUMEXT);

		    echo '<tr '.((empty($new_course_param[1]) && empty($new_course_param[2]))? 'style="display:none;"' : ((isset($err['course_mapping']['lms']) && in_array($new_course_param[0], $err['course_mapping']['lms'])) 
            || (isset($err['course_mapping']['saml']) && in_array($new_course_param[1].'_'.$new_course_param[2], $err['course_mapping']['saml'])) ? 'style="background:red;"' : '')) .' >';
	            echo '<td colspan="2" style="padding-left: 38px;"><select id="newcourse_select" name="new_course' . $i . '[]">';
	            foreach ($moodle_courses as $mcourse) {
	                $is_selected = $new_course_param[0] === $mcourse; 
	                echo '<option value="'. $mcourse .'" ' . ($is_selected ? 'selected="selected"' : '') . ' >'.$mcourse.'</option>';
	            }
	            echo '</select>';
	            echo '<input id="new_courses_total" type="hidden" name="new_courses_total" value="' . $i . '" /></td>';
	            echo '<td><input id="newcourse_saml_id" type="text" name="new_course' . $i . '[]" value="' . $new_course_param[1] . '" /></td>';
	            echo '<td><input id="newcourse_saml_period" type="text" name="new_course' . $i . '[]" value="'. $new_course_param[2] . '" /></td>'; 
	            echo '</tr>';
		    $i++;
	    }
    }

    echo '<tr><td colspan="2" style="padding-left: 38px;"><select id="newcourse_select" name="new_course' . $i . '[]">';
    foreach ($moodle_courses as $mcourse) {
        echo '<option value="' . $mcourse . '"  >' . $mcourse . '</option>';
    }
    echo '</select>';
    echo '<input id="new_courses_total" type="hidden" name="new_courses_total" value="' . $i . '" /></td>';
    echo '<td><input id="newcourse_saml_id" type="text" name="new_course' . $i . '[]" value="" /></td>';
    echo '<td><input id="newcourse_saml_period" type="text" name="new_course' . $i . '[]" value="" />'; 
    echo '<input type="button" name="new" value="+" onclick="addNewField(\'newcourses\',\'new_course\',\'course\')" /></td></tr>';
}

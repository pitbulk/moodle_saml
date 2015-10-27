<?php

    $role_mapping = array();
    $course_mapping = array();
    try {
        $config = get_config('auth/saml');
        require_once ("roles.php");
        require_once ("courses.php");
        $role_mapping = get_role_mapping_for_sync($err, $config);
        $course_mapping = get_course_mapping_for_sync($err, $config);
    }
    catch(Exception $e) {
        print_error('Caught exception while mapping: '.  $e->getMessage(). "\n");
    }

    $mapped_roles = array_unique(array_values($role_mapping));
    $mapped_courses = array();

    foreach($saml_courses as $key => $course) {
        if(preg_match('/urn:mace:terena.org:schac:userStatus:(.+):(.+):(.+):(.+):(.+):(.+)/', $course, $regs)) {
            list($match, $country, $domain, $course_id, $period, $role, $status) = $regs;
            $mapped_role = !empty($role_mapping[$role]) ? $role_mapping[$role] : $role;
            $mapped_course_id = ($config->moodlecoursefieldid == 'idnumber') ? $course_id : $course_mapping[$course_id][$period];
            $mapped_courses[$mapped_role][$status][$mapped_course_id] = array(
                'country' => $country,
                'domain' => $domain,
                'course_id' => $mapped_course_id,
                'period' => $period,
                'role' => $mapped_role,
                'status' => $status,
            );
            if(!$any_course_active && $status == 'active') {
                  $any_course_active = true;
            }
        }
    }

    unset($saml_courses);

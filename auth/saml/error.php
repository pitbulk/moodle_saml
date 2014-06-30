<?php

function saml_error($err, $urltogo=false, $logfile='') {
    global $CFG, $PAGE, $OUTPUT;
    
    if(!isset($CFG->debugdisplay) || !$CFG->debugdisplay) {
        $debug = false;
    }
    else {
        $debug = true;
    }
    
    if($urltogo!=false) {
        $site = get_site();
        if($site === false || !isset($site->fullname)) {
            $site_name = '';
        }
        else {
            $site_name = $site->fullname;
        }
        $PAGE->set_title($site_name .':Error SAML Login');

        echo $OUTPUT->header();


        echo '<div style="margin:20px;font-weight: bold; color: red;">';
    }
    if(is_array($err)) {
        foreach($err as $key => $messages) {
            if(!is_array($messages)) {
                if($urltogo!=false && ($debug || $key == 'course_enrollment')) {
                    echo $messages;
                }
                $msg = 'Moodle SAML module: '.$key.': '.$messages;
                log_saml_error($msg, $logfile);
            }
            else {
                foreach($messages as $message) {
                    if($urltogo!=false && ($debug || $key == 'course_enrollment')) {
                        echo $message.'<br>';
                    }
                    $msg = 'Moodle SAML module: '.$key.': '.$message;
                    log_saml_error($msg, $logfile);
                }
            }
            echo '<br>';
        }
    }
    else {
        if($urltogo!=false) {
            echo $err;
        }
        $msg = 'Moodle SAML module: login: '.$err;
        log_saml_error($msg, $logfile);
    }
    if($urltogo!=false) {
        echo '</div>';
        $OUTPUT->continue_button($urltogo);
        if($debug) {
            print_string("auth_saml_disable_debugdisplay", "auth_saml");
        }
        $OUTPUT->footer();
        exit();
    }
}

function log_saml_error($msg, $logfile) {
    global $CFG;
    // 0 - message  is sent to PHP's system logger, using the Operating System's system logging mechanism or a file.
    // 3 - message  is appended to the file destination
    $destination = '';
    $error_log_type = 0;    
    if(isset($logfile) && !empty($logfile)) {
        if (substr($logfile, 0) == '/') {
            $destination = $logfile;
        }
        else {
            $destination = $CFG->dataroot . '/' . $logfile;
        }
        $error_log_type = 3;
        $msg = decorate_saml_log($msg);
    }
    error_log($msg, $error_log_type, $destination);
}


function decorate_saml_log($msg) {    
    return $msg = date('D M d H:i:s  Y').' [client '.$_SERVER['REMOTE_ADDR'].'] [error] '.$msg."\r\n";
}

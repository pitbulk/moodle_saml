<?php

/**
 * Place the config file in the data directory if it's not already there.
 */
function auth_saml_copy_config_file() {
    global $CFG;
    
    $source      = $CFG->dirroot.'/auth/saml/saml_config.php';
    $destination = $CFG->dataroot.'/saml_config.php';
    
    if (!file_exists($destination) && !copy($source, $destination)) {
        $a = (object)array('source' => $source, 'destination' => $destination);
        throw new moodle_exception('failedtocopyconfig', 'auth_saml', null, $a);
    }
}

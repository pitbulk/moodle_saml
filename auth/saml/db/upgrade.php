<?php

function xmldb_auth_saml_upgrade($oldversion) {
    require_once 'upgradelib.php';
    
    if ($oldversion < 2015061000) {    
        auth_saml_copy_config_file();
    }
    
    return true;
}

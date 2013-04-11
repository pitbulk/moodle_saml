<?php
function DBNewConnection($dsn) {
    global $CFG;
    require_once ($CFG->libdir.'/adodb/adodb.inc.php');

    $cdata = explode('://', $dsn);
    $scheme = $cdata[0];

    if ($scheme === 'sqlite') {
        $path = $cdata[1];
        $mapping_db = ADONewConnection('sqlite');
        $conn = $mapping_db->PConnect($path);
    } else {
        $mapping_db = ADONewConnection($dsn);
    }
    return $mapping_db;
}

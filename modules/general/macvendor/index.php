<?php
if (cfr('MAC')) {
    $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    if ($altercfg['MACVEN_ENABLED']) {
    if (wf_CheckGet(array('mac','username'))) {
        $mac=$_GET['mac'];
        $login=$_GET['username'];
        $vendor='<h3><center>'.zb_MacVendorLookup($mac).'</center></h3>';
        $result=$vendor;
        print($result);
       
    } 
    } else {
        print(__('This module is disabled'));
    }
    
} else {
     print(__('Access denied'));
}

die();
?>
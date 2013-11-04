<?php
if (cfr('MAC')) {
    $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    if ($altercfg['MACVEN_ENABLED']) {
    if (wf_CheckGet(array('mac','username'))) {
        $mac=$_GET['mac'];
        $login=$_GET['username'];
        $vendor=  wf_tag('h3').  wf_tag('center').zb_MacVendorLookup($mac).  wf_tag('center',true).wf_tag('h3',true);
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
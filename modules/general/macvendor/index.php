<?php
if (cfr('MAC')) {
    $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    if ($altercfg['MACVEN_ENABLED']) {
    if (wf_CheckGet(array('mac'))) {
        $mac=$_GET['mac'];
        $vendor=zb_MacVendorLookup($mac);
        if (!wf_CheckGet(array('raw'))) {
            $vendor=  wf_tag('h3').  wf_tag('center').$vendor.  wf_tag('center',true).wf_tag('h3',true);
        } 
        print($vendor);
       
    } 
    } else {
        print(__('This module is disabled'));
    }
    
} else {
     print(__('Access denied'));
}

die();
?>
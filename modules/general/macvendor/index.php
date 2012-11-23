<?php
if (cfr('MAC')) {
    $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    if ($altercfg['MACVEN_ENABLED']) {
    if (wf_CheckGet(array('mac','username'))) {
        $mac=$_GET['mac'];
        $login=$_GET['username'];
        $vendor='<h3><center>'.zb_MacVendorLookup($mac).'</center></h3>';
        $result=$vendor;
        //$result=wf_Plate($vendor, '500px', '100px', 'glamour').'<div style="clear:both;"></div>';
        //show_window(__('Device vendor'),$result);
        //show_window('',web_UserControls($login));
        print($result);
       
    } 
    } else {
       // show_error(__('This module is disabled'));
        print(__('This module is disabled'));
    }
    
} else {
     //show_error(__('Access denied'));
     print(__('Access denied'));
}

die();
?>
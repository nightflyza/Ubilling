<?php
if (cfr('SIGREQ')) {
    

    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if ($alterconf['SIGREQ_ENABLED']) {
        
    

    
    //set request done
    if (isset($_GET['reqdone'])) {
        zb_SigreqsSetDone($_GET['reqdone']);
        rcms_redirect("?module=sigreq");
    }
    
    //set request undone
     if (isset($_GET['requndone'])) {
         zb_SigreqsSetUnDone($_GET['requndone']);
         rcms_redirect("?module=sigreq");
     }
     
     //delete request
    if (isset($_GET['deletereq'])) {
        zb_SigreqsDeleteReq($_GET['deletereq']);
        rcms_redirect("?module=sigreq");
    }
    
    
    
    if (isset($_GET['showreq'])) {
        web_SigreqsShowReq($_GET['showreq']);
    } else {
        web_SigreqsShowAll();
    }
    
    } else {
         show_window(__('Error'),__('This module disabled'));
    }
    
    
    
} else {
      show_error(__('You cant control this module'));
}

?>

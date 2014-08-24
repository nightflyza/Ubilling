<?php

if (cfr('SIGREQ')) {


    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['SIGREQ_ENABLED']) {
        
        $signups = new SignupRequests();
            //requests management
            //set request done
            if (isset($_GET['reqdone'])) {
                $signups->setDone($_GET['reqdone']);
                rcms_redirect("?module=sigreq");
            }

            //set request undone
            if (isset($_GET['requndone'])) {
                $signups->setUnDone($_GET['requndone']);
                rcms_redirect("?module=sigreq");
            }

            //delete request
            if (isset($_GET['deletereq'])) {
                $signups->deleteReq($_GET['deletereq']);
                rcms_redirect("?module=sigreq");
            }

            if (wf_CheckGet(array('showreq'))) {
                //shows selected signup request by its ID
                $signups->showRequest($_GET['showreq']);
            } else {
                //display signup requests list
                $signups->renderList();
            }

        
    } else {
        show_window(__('Error'), __('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

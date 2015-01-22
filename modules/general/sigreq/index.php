<?php

if (cfr('SIGREQ')) {


    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['SIGREQ_ENABLED']) {
        //Main sigreq management
        if (!wf_CheckGet(array('settings'))) {

            $signups = new SignupRequests();
            //requests management
            //set request done
            if (isset($_GET['reqdone'])) {
                $signups->setDone($_GET['reqdone']);
                //update notification area
                      $darkVoid=new DarkVoid();
                      $darkVoid->flushCache();
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
                if (!wf_CheckGet(array('calendarview'))) {
                    //display signup requests list
                    $signups->renderList();
                } else {
                    //display signup requests calendar
                     $signups->renderCalendar();
                }
            }
        } else {
            //signup requests service configuration
            $signupConf = new SignupConfig;

            //save config request
            if (wf_CheckPost(array('changesettings'))) {
                $signupConf->save();
                rcms_redirect('?module=sigreq&settings=true');
            }
            show_window(__('Settings'), $signupConf->renderForm());
        }
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

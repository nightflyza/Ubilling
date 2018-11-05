<?php

if (cfr('SIGREQ')) {
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['SIGREQ_ENABLED']) {
        //Main sigreq management
        if (!wf_CheckGet(array('settings'))) {

            $signups = new SignupRequests();
            //requests management
            //set request as done
            if (isset($_GET['reqdone'])) {
                if (cfr('SIGREQEDIT')) {
                    $signups->setDone($_GET['reqdone']);
                    //update notification area
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    rcms_redirect("?module=sigreq");
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQ CLOSE RIGHTS FAIL [' . $_GET['reqdone'] . ']');
                }
            }

            //set request as undone
            if (isset($_GET['requndone'])) {
                if (cfr('SIGREQEDIT')) {
                    $signups->setUnDone($_GET['requndone']);
                    //update notification area
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    rcms_redirect("?module=sigreq");
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQ OPEN RIGHTS FAIL [' . $_GET['requndone'] . ']');
                }
            }


            //delete request
            if (isset($_GET['deletereq'])) {
                if (cfr('SIGREQDELETE')) {
                    $signups->deleteReq($_GET['deletereq']);
                    rcms_redirect("?module=sigreq");
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQ DELETE RIGHTS FAIL [' . $_GET['deletereq'] . ']');
                }
            }

            if (wf_CheckGet(array('showreq'))) {
                //shows selected signup request by its ID
                $signups->showRequest($_GET['showreq']);
            } else {
                if (!wf_CheckGet(array('calendarview'))) {
                    if (wf_CheckGet(array('ajlist'))) {
                        $signups->renderAjListData();
                    }
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
                if (cfr('SIGREQCONF')) {
                    $signupConf->save();
                    rcms_redirect('?module=sigreq&settings=true');
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQCONF RIGHTS FAIL');
                }
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

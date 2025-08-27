<?php

if (cfr('SIGREQ')) {
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['SIGREQ_ENABLED']) {
        //Main sigreq management
        if (!ubRouting::checkGet('settings')) {

            $signups = new SignupRequests();
            //requests management
            //set request as done
            if (ubRouting::checkGet('reqdone')) {
                if (cfr('SIGREQEDIT')) {
                    $signups->setDone(ubRouting::get('reqdone', 'int'));
                    //update notification area
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    ubRouting::nav("?module=sigreq");
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQ CLOSE RIGHTS FAIL [' . ubRouting::get('reqdone', 'int') . ']');
                }
            }

            //set request as undone
            if (ubRouting::checkGet('requndone')) {
                if (cfr('SIGREQEDIT')) {
                    $signups->setUnDone(ubRouting::get('requndone', 'int'));
                    //update notification area
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    ubRouting::nav("?module=sigreq");
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQ OPEN RIGHTS FAIL [' . ubRouting::get('requndone', 'int') . ']');
                }
            }

            //delete request
            if (ubRouting::checkGet('deletereq')) {
                if (cfr('SIGREQDELETE')) {
                    $signups->deleteReq(ubRouting::get('deletereq', 'int'));
                    ubRouting::nav("?module=sigreq");
                } else {
                    show_error(__('Access denied'));
                    log_register('SIGREQ DELETE RIGHTS FAIL [' . ubRouting::get('deletereq', 'int') . ']');
                }
            }

            if (ubRouting::checkGet('showreq')) {
                //shows selected signup request by its ID
                $signups->showRequest(ubRouting::get('showreq', 'int'));
            } else {
                if (!ubRouting::checkGet('calendarview')) {
                    if (ubRouting::checkGet('ajlist')) {
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
            if (ubRouting::checkPost('changesettings')) {
                if (cfr('SIGREQCONF')) {
                    $signupConf->save();
                    ubRouting::nav('?module=sigreq&settings=true');
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


<?php

if (cfr('SENDDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SENDDOG_ENABLED']) {
        if ( $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')
             and !wf_CheckGet(array('showmisc')) and !wf_CheckPost(array('editconfig')) ) {

            $sendDog = new SendDogAdvanced();

            if ( wf_CheckGet(array('ajax')) ) {
                $smsServicesData = $sendDog->getSmsServicesConfigData();
                $sendDog->renderJSON($smsServicesData);
            }

            if (isset($_POST['edittelegrambottoken'])) {
                $sendDog->editTelegramBotToken($_POST['edittelegrambottoken']);
                rcms_redirect($sendDog->getBaseUrl());
            }

            if ( wf_CheckPost(array('smssrvcreate')) ) {
                if ( wf_CheckPost(array('smssrvname')) ) {
                    $newServiceName = $_POST['smssrvname'];
                    $foundSrvId = $sendDog->checkServiceNameExists($newServiceName);

                    if ( empty($foundSrvId) ) {
                        $alphaName = (wf_CheckPost(array('smssrvalphaaslogin'))) ? $_POST['smssrvlogin'] : $_POST['smssrvalphaname'];

                        $sendDog->addSmsService($newServiceName, $_POST['smssrvlogin'], $_POST['smssrvpassw'],
                                                $_POST['smssrvurlip'], $_POST['smssrvapikey'], $alphaName,
                                                $_POST['smssrvapiimplementation'], $_POST['smssrvdefault']);
                        die();
                    } else {
                        $errormes = $sendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('SMS service with such name already exists with ID: ') . $foundSrvId,
                                                                                                'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                        die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                    }
                }

                die(wf_modalAutoForm(__('Add SMS service'), $sendDog->renderAddForm($_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
            }

            if ( wf_CheckPost(array('action')) ) {
                if ($_POST['action'] == 'RefreshBindingsCache') {
                    $sendDog->getSmsQueueInstance()->smsDirections->refreshCacheForced();
                    $messageWindow = $sendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('SMS services cache bindings updated succesfuly'),
                                                                                                'success', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm('', $messageWindow, $_POST['modalWindowId'], '', true));
                }

                if ( wf_CheckPost(array('smssrvid')) ) {
                    $smsServiceId = $_POST['smssrvid'];

                    if ($_POST['action'] == 'editSMSSrv') {
                        if ( wf_CheckPost(array('smssrvname')) ) {
                            $foundSrvId = $sendDog->checkServiceNameExists($_POST['smssrvname'], $smsServiceId);

                            if ( empty($foundSrvId) ) {
                                $alphaName = (wf_CheckPost(array('smssrvalphaaslogin'))) ? $_POST['smssrvlogin'] : $_POST['smssrvalphaname'];

                                $sendDog->editSmsService($smsServiceId, $_POST['smssrvname'], $_POST['smssrvlogin'], $_POST['smssrvpassw'],
                                                         $_POST['smssrvurlip'], $_POST['smssrvapikey'], $alphaName,
                                                         $_POST['smssrvapiimplementation'], $_POST['smssrvdefault']);
                                die();
                            } else {
                                $errormes = $sendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('SMS service with such name already exists with ID: ') . $foundSrvId,
                                                                                                        'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"' );
                                die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                            }
                        }

                        die(wf_modalAutoForm(__('Edit SMS service'), $sendDog->renderEditForm($smsServiceId, $_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['ModalWBID'], true));
                    }

                    if ($_POST['action'] == 'deleteSMSSrv') {
                        if ( wf_CheckPost(array('smssrvid')) ) {
                            if ( !$sendDog->checkSmsServiceProtected($_POST['smssrvid']) ) {
                                $sendDog->deleteSmsService($_POST['smssrvid']);
                                die();
                            } else {
                                $errormes = $sendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('Can not remove SMS which has existing relations on users or other entities'),
                                                                                                        'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"' );
                                die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                            }
                        }
                    }

                    if ( wf_CheckPost(array('SMSAPIName')) ) {
                        $smsServiceApiName = $_POST['SMSAPIName'];
                        $smsServiceId = $_POST['smssrvid'];
                        include ($sendDog::API_IMPL_PATH . $smsServiceApiName . '.php');
                        $tmpApiObj = new $smsServiceApiName($smsServiceId);

                        switch ($_POST['action']) {
                            case 'getBalance':
                                $tmpApiObj->getBalance();
                                break;

                            case 'getSMSQueue':
                                $tmpApiObj->getSMSQueue();
                                break;
                        }

                        // in case if getBalance() or getSMSQueue() method is not implemented or whatever
                        // in most cases next line would never be executed
                        die();
                    }
                }
            }

            $inputs  = $sendDog->renderTelegramConfigInputs();
            $inputs .= wf_Submit(__('Save'));
            $inputs .= wf_delimiter();
            $form = wf_Form('', 'POST', $inputs, 'glamour') . wf_delimiter();

            show_window(__('Telegram'), $form);

            $lnkId = wf_InputId();
            $cacheLnkId = wf_InputId();
            $addServiceJS = wf_tag('script', false, '', 'type="text/javascript"');
            $addServiceJS .= wf_JSAjaxModalOpener($sendDog::URL_ME, array('smssrvcreate' => 'true'), $lnkId, false, 'POST');
            $addServiceJS .= wf_JSAjaxModalOpener($sendDog::URL_ME, array('action' => 'RefreshBindingsCache'), $cacheLnkId, false,'POST');
            $addServiceJS .= wf_tag('script', true);

            show_window(__('SMS services'), wf_Link('#', web_add_icon() . ' ' . __('Add SMS service'), false, 'ubButton', 'id="' . $lnkId . '"')
                                            . wf_Link('#', wf_img('skins/refresh.gif') . ' ' . __('Refresh SMS services bindings cache'), false, 'ubButton', 'id="' . $cacheLnkId . '"')
                                            . wf_delimiter() . $addServiceJS . $sendDog->renderJQDT());
            zb_BillingStats(true);
        } else {
            $sendDog = new SendDog();

            //editing config
            if (wf_CheckPost(array('editconfig'))) {
                $sendDog->saveConfig();
                rcms_redirect($sendDog->getBaseUrl());
            }

            if (!wf_CheckGet(array('showmisc'))) {
                //render config interface
                show_window(__('SendDog configuration'), $sendDog->renderConfigForm());
            } else {
                //render SMS queue
                $smsQueue = $_GET['showmisc'];
                switch ($smsQueue) {
                    case 'tsms':
                        show_window(__('View SMS sending queue'), $sendDog->renderTurboSMSQueue());
                        break;
                    case 'smsflybalance':
                        show_window(__('SMS-Fly') . ' ' . __('Balance'), $sendDog->renderSmsflyBalance());
                        break;
                    case 'redsmsbalance':
                        show_window(__('RED-SMS') . ' ' . __('Balance'), $sendDog->renderRedsmsBalance());
                        break;
                    case 'smspilotbalance':
                        show_window(__('SMSPILOT') . ' ' . __('Balance'), $sendDog->renderSMSPILOTBalance());
                        break;
                    case 'telegramcontacts':
                        show_window(__('Telegram bot contacts'), $sendDog->renderTelegramContacts());
                        break;
                }
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>

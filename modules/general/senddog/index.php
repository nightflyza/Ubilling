<?php

if (cfr('SENDDOG')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SENDDOG_ENABLED']) {
        if ( $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')
             and !wf_CheckGet(array('showmisc')) and !wf_CheckPost(array('editconfig')) ) {

            $SendDog = new SendDogAdvanced();

            if ( wf_CheckGet(array('ajax')) ) {
                $SMSServicesData = $SendDog->getSMSServicesConfigData();
                $SendDog->renderJSON($SMSServicesData);
            }

            if ( wf_CheckPost(array('smssrvcreate')) ) {
                if ( wf_CheckPost(array('smssrvname')) ) {
                    $NewSrvName = $_POST['smssrvname'];
                    $FoundSrvID = $SendDog->checkServiceNameExists($NewSrvName);

                    if ( empty($FoundSrvID) ) {
                        $AlphaName = (wf_CheckPost(array('smssrvalphaaslogin'))) ? $_POST['smssrvlogin'] : $_POST['smssrvalphaname'];

                        $SendDog->addSMSService($NewSrvName, $_POST['smssrvlogin'], $_POST['smssrvpassw'],
                                                $_POST['smssrvurlip'], $_POST['smssrvapikey'], $AlphaName,
                                                $_POST['smssrvapiimplementation'], $_POST['smssrvdefault']);
                        die();
                    } else {
                        $errormes = $SendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('SMS service with such name already exists with ID: ') . $FoundSrvID,
                                                                                                'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                        die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                    }
                }

                die(wf_modalAutoForm(__('Add SMS service'), $SendDog->renderAddForm($_POST['ModalWID']), $_POST['ModalWID'], $_POST['ModalWBID'], true));
            }

            if ( wf_CheckPost(array('action')) ) {
                if ( wf_CheckPost(array('smssrvid')) ) {
                    $SMSSrvID = $_POST['smssrvid'];

                    if ($_POST['action'] == 'editSMSSrv') {
                        if ( wf_CheckPost(array('smssrvname')) ) {
                            $FoundSrvID = $SendDog->checkServiceNameExists($_POST['smssrvname'], $SMSSrvID);

                            if ( empty($FoundSrvID) ) {
                                $AlphaName = (wf_CheckPost(array('smssrvalphaaslogin'))) ? $_POST['smssrvlogin'] : $_POST['smssrvalphaname'];

                                $SendDog->editSMSService($SMSSrvID, $_POST['smssrvname'], $_POST['smssrvlogin'], $_POST['smssrvpassw'],
                                                         $_POST['smssrvurlip'], $_POST['smssrvapikey'], $AlphaName,
                                                         $_POST['smssrvapiimplementation'], $_POST['smssrvdefault']);
                                die();
                            } else {
                                $errormes = $SendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('SMS service with such name already exists with ID: ') . $FoundSrvID,
                                                                                                        'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"' );
                                die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                            }
                        }

                        die(wf_modalAutoForm(__('Edit SMS service'), $SendDog->renderEditForm($SMSSrvID, $_POST['ModalWID']), $_POST['ModalWID'], $_POST['ModalWBID'], true));
                    }

                    if ($_POST['action'] == 'deleteSMSSrv') {
                        if ( wf_CheckPost(array('smssrvid')) ) {
                            if ( !$SendDog->checkSMSSrvProtected($_POST['smssrvid']) ) {
                                $SendDog->deleteSMSService($_POST['smssrvid']);
                                die();
                            } else {
                                $errormes = $SendDog->getUbillingMsgHelperInstance()->getStyledMessage( __('Can not remove SMS which has existing relations on users or other entities'),
                                                                                                        'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"' );
                                die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                            }
                        }
                    }

                    if ( wf_CheckPost(array('SMSAPIName')) ) {
                        $SMSSrvAPIName = $_POST['SMSAPIName'];
                        $SMSSrvID = $_POST['smssrvid'];
                        include ($SendDog::API_IMPL_PATH . $SMSSrvAPIName . '.php');
                        $tmpApiObj = new $SMSSrvAPIName($SMSSrvID);

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

            $inputs  = $SendDog->renderTelegramConfigInputs();
            $inputs .= wf_Submit(__('Save'));
            $inputs .= wf_delimiter();
            $form = wf_Form('', 'POST', $inputs, 'glamour') . wf_delimiter();

            show_window(__('Telegram'), $form);

            $LnkID = wf_InputId();
            $AddSrvJS = wf_tag('script', false, '', 'type="text/javascript"');
            $AddSrvJS .= '
                            $(\'#' . $LnkID . '\').click(function(evt) {
                                $.ajax({
                                    type: "POST",
                                    url: "' . $SendDog::URL_ME .'",
                                    data: { 
                                            smssrvcreate:true,                                                                                                                                                                
                                            ModalWID:"dialog-modal_' . $LnkID . '", 
                                            ModalWBID:"body_dialog-modal_' . $LnkID . '"                                                        
                                           },
                                    success: function(result) {
                                                $(document.body).append(result);
                                                $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
                                             }
                                });
        
                                evt.preventDefault();
                                return false;
                            });
                        ';
            $AddSrvJS .= wf_tag('script', true);

            show_window(__('SMS services'), wf_Link('#', web_add_icon() . __('Add SMS service'), false, 'ubButton', 'id="' . $LnkID . '"')
                                            . wf_delimiter() . $AddSrvJS . $SendDog->renderJQDT());
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

<?php

if (cfr('SENDDOG')) {
    if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
        if ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')
                and ! ubRouting::checkGet('showmisc') and ! ubRouting::checkPost('editconfig')) {

            $sendDog = new SendDogAdvanced();

            if (ubRouting::checkGet('ajax')) {
                $smsServicesData = $sendDog->getSmsServicesConfigData();
                $sendDog->renderJSON($smsServicesData);
            }

            if (ubRouting::checkPost('edittelegrambottoken', false)) {
                $sendDog->editTelegramBotToken(ubRouting::post('edittelegrambottoken'));
                rcms_redirect($sendDog->getBaseUrl());
            }

            if (ubRouting::checkPost('editsmtphost', false)) {
                $smtpAuth = (ubRouting::checkPost('editsmtpuseauth', false)) ? wf_getBoolFromVar(ubRouting::post('editsmtpuseauth')) : false;

                $sendDog->editPHPMailerConfig(ubRouting::post('editsmtpdebug'), ubRouting::post('editsmtphost'), ubRouting::post('editsmtpport'), ubRouting::post('editsmtpsecure'), ubRouting::post('editsmtpuser'), ubRouting::post('editsmtppasswd'), ubRouting::post('editsmtpdefaultfrom'), $smtpAuth, ubRouting::post('editattachpath'));

                rcms_redirect($sendDog->getBaseUrl());
            }

            if (ubRouting::checkPost('smssrvcreate')) {
                if (ubRouting::checkPost('smssrvname')) {
                    $newServiceName = ubRouting::post('smssrvname');
                    $foundSrvId = $sendDog->checkServiceNameExists($newServiceName);

                    if (empty($foundSrvId)) {
                        $alphaName = (ubRouting::checkPost('smssrvalphaaslogin')) ? ubRouting::post('smssrvlogin') : ubRouting::post('smssrvalphaname');

                        $sendDog->addSmsService($newServiceName, ubRouting::post('smssrvlogin'), ubRouting::post('smssrvpassw'), ubRouting::post('smssrvurlip'), ubRouting::post('smssrvapikey'), $alphaName, ubRouting::post('smssrvapiimplementation'), ubRouting::post('smssrvdefault'));
                        die();
                    } else {
                        $errormes = $sendDog->getUBMsgHelperInstance()->getStyledMessage(__('SMS service with such name already exists with ID: ') . $foundSrvId, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                        die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                    }
                }

                die(wf_modalAutoForm(__('Add SMS service'), $sendDog->renderAddForm(ubRouting::post('modalWindowId')), ubRouting::post('modalWindowId'), ubRouting::post('modalWindowBodyId'), true));
            }

            if (ubRouting::checkPost('action')) {
                if (ubRouting::post('action') == 'RefreshBindingsCache') {
                    $sendDog->getSmsQueueInstance()->smsDirections->refreshCacheForced();
                    $messageWindow = $sendDog->getUBMsgHelperInstance()->getStyledMessage(__('SMS services cache bindings updated succesfuly'), 'success', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm('', $messageWindow, ubRouting::post('modalWindowId'), '', true));
                }

                if (ubRouting::checkPost('smssrvid')) {
                    $smsServiceId = ubRouting::post('smssrvid');

                    if (ubRouting::post('action') == 'editSMSSrv') {
                        if (ubRouting::checkPost('smssrvname')) {
                            $foundSrvId = $sendDog->checkServiceNameExists(ubRouting::post('smssrvname'), $smsServiceId);

                            if (empty($foundSrvId)) {
                                $alphaName = (ubRouting::checkPost('smssrvalphaaslogin')) ? ubRouting::post('smssrvlogin') : ubRouting::post('smssrvalphaname');

                                $sendDog->editSmsService($smsServiceId, ubRouting::post('smssrvname'), ubRouting::post('smssrvlogin'), ubRouting::post('smssrvpassw'), ubRouting::post('smssrvurlip'), ubRouting::post('smssrvapikey'), $alphaName, ubRouting::post('smssrvapiimplementation'), ubRouting::post('smssrvdefault'));
                                die();
                            } else {
                                $errormes = $sendDog->getUBMsgHelperInstance()->getStyledMessage(__('SMS service with such name already exists with ID: ') . $foundSrvId, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                                die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                            }
                        }

                        die(wf_modalAutoForm(__('Edit SMS service'), $sendDog->renderEditForm($smsServiceId, ubRouting::post('modalWindowId')), ubRouting::post('modalWindowId'), ubRouting::post('ModalWBID'), true));
                    }

                    if (ubRouting::post('action') == 'deleteSMSSrv') {
                        if (ubRouting::checkPost('smssrvid')) {
                            if (!$sendDog->checkSmsServiceProtected(ubRouting::post('smssrvid'))) {
                                $sendDog->deleteSmsService(ubRouting::post('smssrvid'));
                                die();
                            } else {
                                $errormes = $sendDog->getUBMsgHelperInstance()->getStyledMessage(__('Can not remove SMS which has existing relations on users or other entities'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                                die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                            }
                        }
                    }

                    if (ubRouting::checkPost('SMSAPIName')) {
                        $smsServiceApiName = ubRouting::post('SMSAPIName');
                        $smsServiceId = ubRouting::post('smssrvid');
                        include_once ($sendDog::API_IMPL_PATH . $smsServiceApiName . '.php');
                        $tmpApiObj = new $smsServiceApiName($smsServiceId);

                        switch (ubRouting::post('action')) {
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

            $inputs = $sendDog->renderTelegramConfigInputs();
            $inputs .= wf_Submit(__('Save'));
            $inputs .= wf_delimiter();
            $form = wf_Form('', 'POST', $inputs, 'glamour') . wf_delimiter();

            show_window(__('Telegram'), $form);

            if ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_PHPMAILER_ON')) {
                show_window(__('Mail'), $sendDog->renderPHPMailerConfigInputs());
            }

            $lnkId = wf_InputId();
            $cacheLnkId = wf_InputId();
            $addServiceJS = wf_tag('script', false, '', 'type="text/javascript"');
            $addServiceJS .= wf_JSAjaxModalOpener($sendDog::URL_ME, array('smssrvcreate' => 'true'), $lnkId, false, 'POST');
            $addServiceJS .= wf_JSAjaxModalOpener($sendDog::URL_ME, array('action' => 'RefreshBindingsCache'), $cacheLnkId, false, 'POST');
            $addServiceJS .= wf_tag('script', true);

            show_window(__('SMS services'), wf_Link('#', web_add_icon() . ' ' . __('Add SMS service'), false, 'ubButton', 'id="' . $lnkId . '"')
                    . wf_Link('#', wf_img('skins/refresh.gif') . ' ' . __('Refresh SMS services bindings cache'), false, 'ubButton', 'id="' . $cacheLnkId . '"')
                    . wf_delimiter() . $addServiceJS . $sendDog->renderJQDT());
            zb_BillingStats(true);
        } else {
            $sendDog = new SendDog();

            //editing config
            if (ubRouting::checkPost('editconfig')) {
                $sendDog->saveConfig();
                ubRouting::nav($sendDog->getBaseUrl());
            }

            if (!ubRouting::checkGet('showmisc')) {
                //render config interface
                show_window(__('SendDog configuration'), $sendDog->renderConfigForm());
            } else {
                //render services misc data
                
                $renderMiscInfo = ubRouting::get('showmisc');
                switch ($renderMiscInfo) {
                    case 'telegramcontacts':
                        show_window(__('Telegram bot contacts'), $sendDog->renderTelegramContacts());
                        break;
                    default :
                        $sendDog->renderBalanceInfo($renderMiscInfo);
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

<?php

if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
    if (cfr('SENDDOG')) {
        $phpMailerOn = ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')
                and $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_PHPMAILER_ON'));

        $messagesQueue = new MessagesQueue();
        show_window('', $messagesQueue->renderPanel($phpMailerOn));

        //rendering json data with queue list
        if (ubRouting::checkGet('ajaxsms')) {
            $messagesQueue->renderSMSAjaxQueue();
        }

        //SMS messages queue management
        if (ubRouting::checkGet('showqueue')) {
            //rendering email queue json
            if (ubRouting::checkGet('ajaxmail')) {
                $messagesQueue->renderEmailAjaxQueue();
            }

            //creating new email in queue
            if (ubRouting::get('showqueue') == 'email') {
                if (ubRouting::checkPost(array('newemailaddress', 'newemailmessage'))) {
                    $emailSendResult = $messagesQueue->createEmail(ubRouting::post('newemailaddress'), ubRouting::post('newemailsubj'), ubRouting::post('newemailmessage'));
                    if (empty($emailSendResult)) {
                        ubRouting::nav($messagesQueue::URL_ME . '&showqueue=email');
                    } else {
                        show_error($emailSendResult);
                    }
                }

                //delete some email from queue
                if (ubRouting::checkGet('deleteemail')) {
                    $deletionResult = $messagesQueue->deleteEmail(ubRouting::get('deleteemail'));
                    if ($deletionResult == 0) {
                        log_register('UEML DELETE EMAIL `' . ubRouting::get('deleteemail') . '`');
                        ubRouting::nav($messagesQueue::URL_ME . '&showqueue=email');
                    } else {
                        if ($deletionResult == 2) {
                            show_error(__('Not existing item'));
                        }

                        if ($deletionResult == 1) {
                            show_error(__('Permission denied'));
                        }
                    }
                }

                //render emails queue
                show_window(__('Emails in queue') . ' ' . $messagesQueue->emailCreateForm(), $messagesQueue->renderEmailQueue());
            }

            //rendering PHPMail queue json
            if (ubRouting::checkGet('ajaxphpmail')) {
                $messagesQueue->renderPHPMailAjaxQueue();
            }

            //creating new PHPMail in queue
            if (ubRouting::get('showqueue') == 'phpmail') {
                if (ubRouting::checkPost(array('newemailaddress', 'newemailmessage'))) {
                    $bodyAsHTML = (ubRouting::checkPost('newmailbodyashtml', false)) ? wf_getBoolFromVar(ubRouting::post('newmailbodyashtml')) : false;

                    if (isset($_FILES['newmailattach'])) {
                        $attachPath = $messagesQueue->uploadAttach();
                    } else {
                        $attachPath = '';
                    }

                    $emailSendResult = $messagesQueue->createPHPMail(ubRouting::post('newemailaddress'), ubRouting::post('newemailsubj'), ubRouting::post('newemailmessage'), $attachPath, $bodyAsHTML, ubRouting::post('newemailfrom'));

                    if (empty($emailSendResult)) {
                        ubRouting::nav($messagesQueue::URL_ME . '&showqueue=phpmail');
                    } else {
                        show_error($emailSendResult);
                    }
                }

                //delete some PHPMail from queue
                if (ubRouting::checkGet('deletephpmail')) {
                    $deletionResult = $messagesQueue->deletePHPMail(ubRouting::get('deletephpmail'));
                    if ($deletionResult == 0) {
                        log_register('UPHPEML DELETE EMAIL `' . ubRouting::get('deletephpmail') . '`');
                        ubRouting::nav($messagesQueue::URL_ME . '&showqueue=phpmail');
                    } else {
                        if ($deletionResult == 2) {
                            show_error(__('Not existing item'));
                        }

                        if ($deletionResult == 1) {
                            show_error(__('Permission denied'));
                        }
                    }
                }

                //render PHPMail emails queue
                show_window(__('Emails in queue') . ' ' . $messagesQueue->phpMailCreateForm(), $messagesQueue->renderPHPMailQueue());
            }

            if (ubRouting::get('showqueue') == 'telegram') {
                //rendering telegram queue json data
                if (ubRouting::checkGet('ajaxtelegram')) {
                    $messagesQueue->renderTelegramAjaxQueue();
                }

                //creating new telegram message in queue
                if (ubRouting::checkPost('newtelegramchatid')) {
                    $telegramSendResult = $messagesQueue->createTelegram(ubRouting::post('newtelegramchatid'), ubRouting::post('newtelegrammessage'));
                    if (empty($telegramSendResult)) {
                        ubRouting::nav($messagesQueue::URL_ME . '&showqueue=telegram');
                    } else {
                        show_error($telegramSendResult);
                    }
                }

                //delete some telegram message from queue
                if (ubRouting::checkGet('deletetelegram')) {
                    $deletionResult = $messagesQueue->deleteTelegram(ubRouting::get('deletetelegram'));
                    if ($deletionResult == 0) {
                        log_register('UTLG DELETE MESSAGE `' . ubRouting::get('deletetelegram') . '`');
                        ubRouting::nav($messagesQueue::URL_ME . '&showqueue=telegram');
                    } else {
                        if ($deletionResult == 2) {
                            show_error(__('Not existing item'));
                        }

                        if ($deletionResult == 1) {
                            show_error(__('Permission denied'));
                        }
                    }
                }

                //render telegram queue
                show_window(__('Telegram messages queue') . ' ' . $messagesQueue->telegramCreateForm(), $messagesQueue->renderTelegramQueue());
            }
        } else {
            if (ubRouting::checkPost(array('newsmsnumber', 'newsmsmessage'))) {
                $smsSendResult = $messagesQueue->createSMS(ubRouting::post('newsmsnumber'), ubRouting::post('newsmsmessage'));
                if (empty($smsSendResult)) {
                    ubRouting::nav($messagesQueue::URL_ME);
                } else {
                    show_error($smsSendResult);
                }
            }
            //deleting SMS from queue
            if (ubRouting::checkGet('deletesms')) {
                $deletionResult = $messagesQueue->deleteSms(ubRouting::get('deletesms'));
                if ($deletionResult == 0) {
                    log_register('USMS DELETE MESSAGE `' . ubRouting::get('deletesms') . '`');
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    ubRouting::nav($messagesQueue::URL_ME);
                } else {
                    if ($deletionResult == 2) {
                        show_error(__('Not existing item'));
                    }

                    if ($deletionResult == 1) {
                        show_error(__('Permission denied'));
                    }
                }
            }

            //flushing all SMS queue
            if (ubRouting::checkGet($messagesQueue::ROUTE_SMSFLUSH)) {
                $messagesQueue->flushSmsQueue();
                ubRouting::nav($messagesQueue::URL_ME);
            }

            //render sms queue and some controls
            $smsControls = $messagesQueue->smsCreateForm();
            if (cfr('ROOT')) {
                $smsQueueCount = $messagesQueue->getSmsQueueCount();

                if ($smsQueueCount) {
                    //cleanup controls
                    $messages = new UbillingMessageHelper();
                    $flushUrl = $messagesQueue::URL_ME . '&' . $messagesQueue::ROUTE_SMSFLUSH . '=true';
                    $flushNotice = __('Flush all queue') . '? ' . $messages->getDeleteAlert();
                    $smsControls .= wf_ConfirmDialog($flushUrl, wf_img('skins/icon_cleanup.png', __('Flush all queue')), $flushNotice, '', $messagesQueue::URL_ME);
                }
            }

            show_window(__('SMS in queue') . ' ' . $smsControls, $messagesQueue->renderSmsQueue());
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>
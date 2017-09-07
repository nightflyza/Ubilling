<?php

$altCfg = $ubillingConfig->getAlter();

if ($altCfg['SENDDOG_ENABLED']) {
    if (cfr('SENDDOG')) {

        $messagesQueue = new MessagesQueue();
        show_window('', $messagesQueue->renderPanel());

        //SMS messages queue management
        if (!wf_CheckGet(array('showqueue'))) {
            if (wf_CheckPost(array('newsmsnumber', 'newsmsmessage'))) {
                $smsSendResult = $messagesQueue->createSMS($_POST['newsmsnumber'], $_POST['newsmsmessage']);
                if (empty($smsSendResult)) {
                    rcms_redirect($messagesQueue::URL_ME);
                } else {
                    show_error($smsSendResult);
                }
            }

            if (wf_CheckGet(array('deletesms'))) {
                $deletionResult = $messagesQueue->deleteSms($_GET['deletesms']);
                if ($deletionResult == 0) {
                    log_register('USMS DELETE MESSAGE `' . $_GET['deletesms'] . '`');
                    $darkVoid = new DarkVoid();
                    $darkVoid->flushCache();
                    rcms_redirect($messagesQueue::URL_ME);
                } else {
                    if ($deletionResult == 2) {
                        show_error(__('Not existing item'));
                    }

                    if ($deletionResult == 1) {
                        show_error(__('Permission denied'));
                    }
                }
            }

            //render sms queue
            show_window(__('SMS in queue') . ' ' . $messagesQueue->smsCreateForm(), $messagesQueue->renderSmsQueue());
        } else {
            if ($_GET['showqueue'] == 'email') {
                if (wf_CheckPost(array('newemailaddress', 'newemailmessage'))) {
                    $emailSendResult = $messagesQueue->createEmail($_POST['newemailaddress'], $_POST['newemailsubj'], $_POST['newemailmessage']);
                    if (empty($emailSendResult)) {
                        rcms_redirect($messagesQueue::URL_ME . '&showqueue=email');
                    } else {
                        show_error($emailSendResult);
                    }
                }

                if (wf_CheckGet(array('deleteemail'))) {
                    $deletionResult = $messagesQueue->deleteEmail($_GET['deleteemail']);
                    if ($deletionResult == 0) {
                        log_register('UEML DELETE EMAIL `' . $_GET['deleteemail'] . '`');
                        rcms_redirect($messagesQueue::URL_ME . '&showqueue=email');
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

            if ($_GET['showqueue'] == 'telegram') {
                if (wf_CheckPost(array('newtelegramchatid'))) {
                    $telegramSendResult = $messagesQueue->createTelegram($_POST['newtelegramchatid'], $_POST['newtelegrammessage']);
                    if (empty($telegramSendResult)) {
                        rcms_redirect($messagesQueue::URL_ME . '&showqueue=telegram');
                    } else {
                        show_error($telegramSendResult);
                    }
                }

                if (wf_CheckGet(array('deletetelegram'))) {
                    $deletionResult = $messagesQueue->deleteTelegram($_GET['deletetelegram']);
                    if ($deletionResult == 0) {
                        log_register('UTLG DELETE MESSAGE `' . $_GET['deletetelegram'] . '`');
                        rcms_redirect($messagesQueue::URL_ME . '&showqueue=telegram');
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
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>
<?php

/*
 * SendDog queues processing
 */
if ($_GET['action'] == 'senddog') {
    if ($alterconf['SENDDOG_ENABLED']) {
        if ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
            $runSendDog = new SendDogAdvanced();
        } else {
            $runSendDog = new SendDog();
        }

        if (isset($_GET['param']) && ($_GET['param'] == 'chkmsgstatuses')) {
            if ($ubillingConfig->getAlterParam('SMS_HISTORY_ON')) {
                if ($ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED')) {
                    $runSendDog->smsProcessing(true);
                } else {
                    $runSendDog->smsHistoryProcessing();
                }
                die('OK:SENDDOG SMS STATUS CHECK PROCESSED');
            } else {
                die('OK:SENDDOG SMS HISTORY DISABLED');
            }
        }

        $sendDogTelegram = $runSendDog->telegramProcessing();
        $sendDogEmail = $runSendDog->emailProcessing();
        $sendDogSms = $runSendDog->smsProcessing();
        die('OK:SENDDOG SMS `' . $sendDogSms . '` TLG `' . $sendDogTelegram . '` EML `' . $sendDogEmail . '`');
    } else {
        die('ERROR:SENDDOG_DISABLED');
    }
}
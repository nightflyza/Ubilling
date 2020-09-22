<?php

/*
 * SendDog queues processing
 */
if (ubRouting::get('action') == 'senddog') {
    if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
        $sendDogAdvOn = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
        $phpMailerOn  = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_PHPMAILER_ON');

        if ($sendDogAdvOn) {
            $runSendDog = new SendDogAdvanced();
        } else {
            $runSendDog = new SendDog();
        }

        if (ubRouting::get('param') == 'chkmsgstatuses') {
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

        if ($sendDogAdvOn and $phpMailerOn) {
            $sendDogEmailPMailer = $runSendDog->phpMailProcessing();
        }

        die('OK:SENDDOG SMS `' . $sendDogSms . '` TLG `' . $sendDogTelegram . '` EML `' . $sendDogEmail . '`' . '` PHPEML `' . $sendDogEmailPMailer . '`');
    } else {
        die('ERROR:SENDDOG_DISABLED');
    }
}
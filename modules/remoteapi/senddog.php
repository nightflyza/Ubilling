<?php

/*
 * SendDog queues processing
 */
if (ubRouting::get('action') == 'senddog') {
    if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
        $sendDogAdvOn = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_ENABLED');
        $phpMailerOn = $ubillingConfig->getAlterParam('SMS_SERVICES_ADVANCED_PHPMAILER_ON');
        $parallelMode = ($ubillingConfig->getAlterParam('SENDDOG_PARALLEL_MODE')) ? true : false;
        $dogWalkingAllowed = ($parallelMode) ? false : true;
        $sendDogPidFile = SendDog::PID_PATH;

        if ($parallelMode) {
            // no another dog walking here
            if (!file_exists($sendDogPidFile)) {
                $dogWalkingAllowed = true;
            }
        }

        if ($dogWalkingAllowed) {
            // dog walking has begun 
            file_put_contents($sendDogPidFile, curdatetime());

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
                    //the dog check msg statuses and finished walking
                    if (file_exists($sendDogPidFile)) {
                        unlink($sendDogPidFile);
                    }
                    die('OK:SENDDOG SMS STATUS CHECK PROCESSED');
                } else {
                    //walking the dog suddenly stopped 
                    if (file_exists($sendDogPidFile)) {
                        unlink($sendDogPidFile);
                    }
                    die('OK:SENDDOG SMS HISTORY DISABLED');
                }
            }

            $sendDogTelegram = $runSendDog->telegramProcessing();
            $sendDogEmail = $runSendDog->emailProcessing();
            $sendDogSms = $runSendDog->smsProcessing();
            $sendDogEmailPMailer = '';

            if ($sendDogAdvOn and $phpMailerOn) {
                $sendDogEmailPMailer = ' PHPEML `' . $runSendDog->phpMailProcessing() . '`';
            }

            //the dog's walk is over
            if (file_exists($sendDogPidFile)) {
                unlink($sendDogPidFile);
            }

            die('OK:SENDDOG SMS `' . $sendDogSms . '` TLG `' . $sendDogTelegram . '` EML `' . $sendDogEmail . '`' . $sendDogEmailPMailer);
        } else {
            //Who Let The Dogs Out!
            die('WARNING:SENDDOG_ALREADY_RUNING');
        }
    } else {
        die('ERROR:SENDDOG_DISABLED');
    }
}
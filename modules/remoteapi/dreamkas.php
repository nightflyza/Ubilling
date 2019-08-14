<?php

// Dreamkas webhooks processing
if ($_GET['action'] == 'dreamkas') {
    if ($ubillingConfig->getAlterParam('DREAMKAS_ENABLED')) {
        if (isset($HTTP_RAW_POST_DATA)) {
            $DreamKas = new DreamKas();
            $DreamKas->processWebhookRequest($HTTP_RAW_POST_DATA, $_GET['param']);
        } else {
            log_register('DREAMKAS WEBHOOK: empty $HTTP_RAW_POST_DATA - no data to process');
        }
    }
}

              
<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('DREAMKAS_ENABLED') and $darkVoidContext['ubConfig']->getAlterParam('DREAMKAS_NOTIFICATIONS_ENABLED')) {
    $dsNotifyFront = new DreamKasNotifications();
    $result .= $dsNotifyFront->renderWidget();
}

return ($result);

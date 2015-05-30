<?php

if (cfr('CAP')) {
    if (isset($_GET['username'])) {
        $login = $_GET['username'];
        $alterconfig = $ubillingConfig->getAlter();
        if ($alterconfig['CAP_ENABLED']) {

            $raskolnikov = new CrimeAndPunishment();
            $raskolnikov->setLogin($login);
            show_window(__('Crime and punishment'), $raskolnikov->renderReport());
            show_window('', web_UserControls($login));
        } else {
            show_error(__('This module disabled'));
        }
    } else {
        show_error(__('Strange exeption'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
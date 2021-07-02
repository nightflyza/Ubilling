<?php

if (ubRouting::get('action') == 'karmaguard') {
    if ($alterconf['KARMA_CONTROL']) {
        $badKarma = new BadKarma();
        $badKarma->runMassReset(true);
    } else {
        die('ERROR:KARMA_CONTROL_DISABLED');
    }
}
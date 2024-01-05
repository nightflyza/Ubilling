<?php

if (ubRouting::get('action') == 'setculpa') {
    if (@$alterconf['MEACULPA_ENABLED']) {
        if (ubRouting::checkGet(array('login', 'culpa'))) {
            $login = ubRouting::get('login');
            $culpa = ubRouting::get('culpa');
            $meaCulpa = new MeaCulpa();
            $setResult = $meaCulpa->set($login, $culpa);
            if ($setResult) {
                die('OK:MEACULPA');
            } else {
                die('FAIL:MEACULPA');
            }
        }
    } else {
        die('ERROR:MEACULPA_DISABLED');
    }
}
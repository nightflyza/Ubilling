<?php

//perform autocrediting
if (ubRouting::get('action') == 'autocredit') {
    if (@$alterconf['AUTOCREDIT_CFID']) {
        if (date("d") == date("t")) {
            //last day of month
            $autocredit = new AutoCredit();
            $autocreditResult = $autocredit->processing('cf');
            die('OK:AUTOCREDIT ' . $autocreditResult);
        } else {
            die('SKIP:AUTOCREDIT');
        }
    } else {
        die('ERROR:AUTOCREDIT DISABLED');
    }
}
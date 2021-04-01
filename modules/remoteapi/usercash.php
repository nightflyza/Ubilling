<?php

if (ubRouting::get('action') == 'usercash') {
    if (ubRouting::checkGet(array('login', 'summ'))) {
        /**
         * 
         *    )" .
         *   /    \      (\-./
         *   /     |    _/ o. \
         *   |      | .-"      y)-
         *   |      |/       _/ \
         *   \     /j   _".\(@)
         *   \   ( |    `.''  )
         *   \  _`-     |   /
         *   "  `-._  <_ (
         *   `-.,),)
         *  ^^ ETO BELOCHKA! ^^
         */
        $userLogin = ubRouting::get('login');
        $summ = ubRouting::get('summ');
        $cashType = (ubRouting::get('ct', 'int')) ? (ubRouting::get('ct', 'int')) : 1;
        $operation = (ubRouting::checkGet('op')) ? ubRouting::get('op') : 'add';
        $note = (ubRouting::checkGet('note')) ? ubRouting::get('note') : '';
        if (zb_checkMoney($summ)) {
            $allUsers = zb_UserGetAllDataCache();
            if (isset($allUsers[$userLogin])) {
                zb_CashAdd($userLogin, $summ, $operation, $cashType, $note);
                die('OK:USERCASH');
            } else {

                die('ERROR:WRONG_LOGIN');
            }
        } else {
            die('ERROR:DIRTY_MONEY');
        }
    } else {
        die('ERROR:NO_PARAMS');
    }
}
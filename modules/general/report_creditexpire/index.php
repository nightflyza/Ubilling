<?php

if (cfr('REPORTCREXP')) {


    /*
     * Credit expire report base class
     */

    class ReportCreditExpire {

        protected $expires = array();
        protected $forever = array();

        public function __construct() {
            $this->loadExpires();
            $this->loadForeverCredits();
        }

        /*
         * loads all users with credit expiration set into private prop
         * 
         * @return void
         */

        protected function loadExpires() {
            $query = "SELECT `login`,`Tariff`,`Cash`,`Credit`,`CreditExpire` from `users` WHERE `CreditExpire`!='0'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                $this->expires = $all;
            }
        }

        /*
         * loads all users with credit and no expiration set into private prop
         * 
         * @return void
         */

        protected function loadForeverCredits() {
            $query = "SELECT `login`,`Tariff`,`Cash`,`Credit`,`CreditExpire` from `users` WHERE `CreditExpire`='0' AND `Credit`!='0'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                $this->forever = $all;
            }
        }

        /*
         * Flushes user credit to zero value
         * 
         * @param string $login Existing user login
         */

        public function flushCredit($login) {
            global $billing;
            $login = mysql_real_escape_string($login);
            $credit = 0;
            $billing->setcredit($login, $credit);
            log_register('CHANGE FIX Credit (' . $login . ') ON ' . $credit);
        }

        /*
         * renders report by some array of user logins
         * 
         * @param array $creditUserList 
         * 
         * @return string
         */

        protected function render($creditUserList) {
            $counter=0;
            $allrealnames = zb_UserGetAllRealnames();
            $alladdress = zb_AddressGetFulladdresslistCached();

            $cells = wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Credit'));
            $cells.= wf_TableCell(__('Credit expire'));
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('Real name'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($creditUserList)) {
                foreach ($creditUserList as $io => $eachuser) {
                    $cells = wf_TableCell($eachuser['login']);
                    $cells.= wf_TableCell($eachuser['Tariff']);
                    $cells.= wf_TableCell($eachuser['Cash']);
                    $cells.= wf_TableCell($eachuser['Credit']);
                    if ($eachuser['CreditExpire'] != '0') {
                        $expireDate = date("Y-m-d", $eachuser['CreditExpire']);
                    } else {
                        $expireDate = __('Forever and ever');
                    }
                    $cells.= wf_TableCell($expireDate);
                    $cells.= wf_TableCell(@$alladdress[$eachuser['login']]);
                    $cells.= wf_TableCell(@$allrealnames[$eachuser['login']]);
                    $actlinks = '';
                    if (cfr('CREDIT')) {
                        $actlinks.=wf_Link('?module=report_creditexpire&fastfix=' . $eachuser['login'], wf_img('skins/icon_repair.gif', __('Fix')), false, '');
                    }
                    $actlinks.= wf_Link('?module=userprofile&username=' . $eachuser['login'], web_profile_icon(), false, '');
                    $actlinks.= wf_Link('?module=creditexpireedit&username=' . $eachuser['login'], wf_img('skins/icon_calendar.gif', __('Change') . ' ' . __('Credit expire')), false, '');

                    $cells.= wf_TableCell($actlinks);
                    $rows.= wf_TableRow($cells, 'row3');
                    $counter++;
                }
            }
            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.= wf_tag('b').__('Total').': '.$counter.wf_tag('b',true);

            return($result);
        }
        
        /*
         * public renderer for users credit expires
         * 
         * @return string
         */
        
        public function renderExpires() {
            $result=$this->render($this->expires);
            return ($result);
        }
        
         /*
         * public renderer for users with no credit expires
         * 
         * @return string
         */
        
        public function renderForever() {
            $result=$this->render($this->forever);
            return ($result);
        }


    }
    
    
    /*
     * controller
     */
    
    
    $creditReport=new ReportCreditExpire();
    
    //credit fast cleanup
    if (wf_CheckGet(array('fastfix'))) {
        $creditReport->flushCredit($_GET['fastfix']);
        rcms_redirect('?module=report_creditexpire');
    }

    show_window(__('Users with their credits expires'), $creditReport->renderExpires());
    show_window(__('Users credit limit which has no expiration date'), $creditReport->renderForever());


    
} else {
    show_error(__('You cant control this module'));
}
?>

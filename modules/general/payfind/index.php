<?php

if (cfr('PAYFIND')) {

    /*
     * Returns all of known payment systems percents
     * 
     * @return array
     */

    function zb_PaySysPercentGetAll() {
        $result = array();
        $data_raw = zb_StorageGet('PAYSYSPC');
        if (!empty($data_raw)) {
            //unpack data
            $data_raw = base64_decode($data_raw);
            $result = unserialize($data_raw);
        } else {
            //first usage
            $newdata = serialize($result);
            $newdata = base64_encode($newdata);
            zb_StorageSet('PAYSYSPC', $newdata);
            log_register("PAYSYSPC CREATE EMPTY");
        }

        return ($result);
    }

    /*
     * Adds new payment system data to database
     * 
     * @param $mark     identifying text of payment system
     * @param $name     human-readable name of payment system
     * @param $percent  percent withholding payment system
     * 
     * @return void
     */

    function zb_PaySysPercentAdd($mark, $name, $percent) {
        $mark = mysql_real_escape_string($mark);
        $name = mysql_real_escape_string($name);
        $percent = vf($percent, 3);
        $olddata = zb_PaySysPercentGetAll();
        $newdata = $olddata;

        if (!isset($olddata[$mark]['name'])) {
            $newdata[$mark]['name'] = $name;
            $newdata[$mark]['percent'] = $percent;
            $newdata = serialize($newdata);
            $newdata = base64_encode($newdata);
            zb_StorageSet('PAYSYSPC', $newdata);
            log_register("PAYSYSPC ADD `" . $mark . ":" . $name . ":" . $percent . "`");
        }
    }

    /*
     * Removes payment system data from database
     * 
     * @param $mark     identifying text of payment system
     * 
     * @return void
     */

    function zb_PaySysPercentDelete($mark) {
        $mark = mysql_real_escape_string($mark);
        $olddata = zb_PaySysPercentGetAll();
        $newdata = $olddata;
        if (isset($newdata[$mark])) {
            unset($newdata[$mark]);
            $newdata = serialize($newdata);
            $newdata = base64_encode($newdata);
            zb_StorageSet('PAYSYSPC', $newdata);
            log_register("PAYSYSPC DELETE `" . $mark . "`");
        }
    }

    /*
     * Show payment system create and deletion form
     * 
     * @return string
     */

    function web_PaySysForm() {
        $allpaysys = zb_PaySysPercentGetAll();

        $inputs = wf_TextInput('newmarker', __('Payment system marker'), '', true, '10');
        $inputs.= wf_TextInput('newname', __('Payment system name'), '', true, '10');
        $inputs.= wf_TextInput('newpercent', __('Percent withholding payment system'), '', true, '4');
        $inputs.= wf_Submit(__('Save'));
        $form = wf_Form("", "POST", $inputs, 'glamour');
        $result = $form;
        $result.= wf_Link("?module=payfind", __('Back'), true, 'ubButton');

        if (!empty($allpaysys)) {

            $cells = wf_TableCell(__('Marker'));
            $cells.= wf_TableCell(__('Name'));
            $cells.= wf_TableCell(__('Percent'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allpaysys as $marker => $each) {
                $cells = wf_TableCell($marker);
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell($each['percent']);
                $cells.= wf_TableCell(wf_JSAlert("?module=payfind&confpaysys=true&delete=" . $marker, web_delete_icon(), __('Removing this may lead to irreparable results')));
                $rows.= wf_TableRow($cells, 'row3');
            }
            $result.=wf_TableBody($rows, '100%', '0', 'sortable');
        }
        return ($result);
    }

    /*
     * Returns payment system selector - used in search form
     * 
     * @return string
     */

    function web_PaySysPercentSelector() {
        $allpaysys = zb_PaySysPercentGetAll();
        $prepared = array();
        if (!empty($allpaysys)) {
            foreach ($allpaysys as $marker => $each) {
                $prepared[$marker] = $each['name'];
            }
        }
        $result = wf_Selector('paysys', $prepared, __('Payment system'), '', false);
        return ($result);
    }

    /*
     * Returns payment search form
     * 
     * @return string
     */

    function web_PayFindForm() {
        $curdate = curdate();
        $yesterday = date("Y-m-d", time() - 60 * 60 * 24);

        $inputs = __('Date');
        $inputs.= wf_DatePickerPreset('datefrom', $yesterday) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', $curdate) . ' ' . __('To');
        $inputs.= wf_delimiter();
        $inputs.= wf_CheckInput('type_payid', '', false, false);
        $inputs.= wf_TextInput('payid', __('Search by payment ID'), '', true, '10');
        $inputs.= wf_CheckInput('type_contract', '', false, false);
        $inputs.= wf_TextInput('contract', __('Search by users contract'), '', true, '10');
        $inputs.= wf_CheckInput('type_login', '', false, false);
        $inputs.= wf_TextInput('login', __('Search by users login'), '', true, '10');
        $inputs.= wf_CheckInput('type_cashtype', '', false, false);
        $inputs.= web_CashTypeSelector() . wf_tag('label', false, '', 'for="cashtype"') . __('Search by cash type') . wf_tag('label', true) . wf_tag('br');
        $inputs.= wf_CheckInput('type_paysys', '', false, false);
        $inputs.= web_PaySysPercentSelector();
        $inputs.= wf_Link("?module=payfind&confpaysys=true", __('Settings')) . wf_tag('br');
        $inputs.= wf_HiddenInput('dosearch', 'true');
        $inputs.= wf_Submit(__('Search'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        $result.= wf_Link("?module=report_finance", __('Back'), true, 'ubButton');

        return ($result);
    }

    /*
     * Execute search with prepared options and shows search results
     * 
     * @return void
     */

    function web_PaymentSearch($markers) {
        $query = "SELECT * from `payments`";
        $query.=$markers;
        $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
        $allpayments = simple_queryall($query);
        $allcontracts = zb_UserGetAllContracts();
        $allcontracts = array_flip($allcontracts);
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $alltypes = zb_CashGetAllCashTypes();
        $allservicenames = zb_VservicesGetAllNamesLabeled();
        $allpaysyspercents=  zb_PaySysPercentGetAll();

        $totalsumm = 0;
        $paysyssumm = 0;
        $profitsumm = 0;
        $totalcount=0;

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('PS%'));
        $cells.= wf_TableCell(__('Profit'));
        $cells.= wf_TableCell(__('Login'));
        $cells.= wf_TableCell(__('Contract'));
        $cells.= wf_TableCell(__('Full address'));
        $cells.= wf_TableCell(__('Real Name'));
        $cells.= wf_TableCell(__('Payment type'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Admin'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['summ']);
                //detecting paymentsystem and calc percent
                if (isset($allpaysyspercents[$each['note']])) {
                    $currPc=$allpaysyspercents[$each['note']]['percent'];
                    $rawSumm=$each['summ'];
                    $paySysPc=($rawSumm/100)*$currPc;
                    $ourProfit=$rawSumm-$paySysPc;
                } else {
                    $paySysPc=0;
                    $ourProfit=$each['summ'];
                }
                $cells.= wf_TableCell($paySysPc);
                $cells.= wf_TableCell($ourProfit);
                
                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, ''));
                $cells.= wf_TableCell(@$allcontracts[$each['login']]);
                $cells.= wf_TableCell(@$alladdress[$each['login']]);
                $cells.= wf_TableCell(@$allrealnames[$each['login']]);
                $cells.= wf_TableCell(@__($alltypes[$each['cashtypeid']]));
                //payment notes translation
                if ($altercfg['TRANSLATE_PAYMENTS_NOTES']) {
                    $paynote = zb_TranslatePaymentNote($each['note'], $allservicenames);
                } else {
                    $paynote = $each['note'];
                }
                $cells.= wf_TableCell($paynote);
                $cells.= wf_TableCell($each['admin']);
                $rows.= wf_TableRow($cells, 'row3');
                
                //calculating totals
                if ($each['summ']>0) {
                    $totalsumm=$totalsumm+$each['summ'];
                    $totalcount++;
                }
                
                if ($paySysPc>0) {
                    $paysyssumm=$paysyssumm+$paySysPc;
                }
                
                if ($ourProfit>0) {
                    $profitsumm=$profitsumm+$ourProfit;
                }
                
            }
            
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        //additional total counters
        $result.=wf_tag('div', false, 'glamour').__('Count').': '.$totalcount.wf_tag('div',true);
        $result.=wf_tag('div', false, 'glamour').__('Total payments').': '.$totalsumm.wf_tag('div',true);
        $result.=wf_tag('div', false, 'glamour').__('Payment systems %').': '.$paysyssumm.wf_tag('div',true);
        $result.=wf_tag('div', false, 'glamour').__('Our final profit').': '.$profitsumm.wf_tag('div',true);
        
        show_window(__('Payments found'), $result);
    }

    /*
     * Interfaces
     */

    if (!wf_CheckGet(array('confpaysys'))) {
        show_window(__('Payment search'), web_PayFindForm());
    } else {
        show_window(__('Payment systems'), web_PaySysForm());
    }

    /*
     * Controller section
     */

    //Payment systems configuration
    //adding payment system
    if (wf_CheckPost(array('newmarker', 'newname', 'newpercent'))) {
        zb_PaySysPercentAdd($_POST['newmarker'], $_POST['newname'], $_POST['newpercent']);
        rcms_redirect("?module=payfind&confpaysys=true");
    }
    //removing payment system
    if (wf_CheckGet(array('delete'))) {
        zb_PaySysPercentDelete($_GET['delete']);
        rcms_redirect("?module=payfind&confpaysys=true");
    }



    //Search

    $markers = '';

    //date search
    if (wf_CheckPost(array('datefrom', 'dateto'))) {
        $datefrom = mysql_real_escape_string($_POST['datefrom']);
        $dateto = mysql_real_escape_string($_POST['dateto']);
        $markers.="WHERE `date` BETWEEN '" . $datefrom . "' AND '" . $dateto . "' ";
    }

    //payment id search
    if (wf_CheckPost(array('type_payid', 'payid'))) {
        $payid = vf($_POST['payid'], 3);
        $markers.="AND `id`='" . $payid . "' ";
    }

    //contract search
    if (wf_CheckPost(array('type_contract', 'contract'))) {
        $contract = mysql_real_escape_string($_POST['contract']);
        $allcontracts = zb_UserGetAllContracts();
        if (!empty($allcontracts)) {
            if (isset($allcontracts[$contract])) {
                $contractlogin = $allcontracts[$contract];
                $markers.="AND `login`='" . $contractlogin . "' ";
            }
        }
    }

    //login payment search
    if (wf_CheckPost(array('type_login', 'login'))) {
        $userlogin = mysql_real_escape_string($_POST['login']);
        $markers.="AND `login`='" . $userlogin . "' ";
    }

    //cashtype search
    if (wf_CheckPost(array('type_cashtype', 'cashtype'))) {
        $cashtype = vf($_POST['cashtype'], 3);
        $markers.="AND `cashtypeid`='" . $cashtype . "' ";
    }

    //payment system search
    if (wf_CheckPost(array('type_paysys', 'paysys'))) {
        $cashtype = mysql_real_escape_string($_POST['paysys']);
        $markers.="AND `note` LIKE '" . $cashtype . "' ";
    }

    //executing search
    if (wf_CheckPost(array('dosearch'))) {
        web_PaymentSearch($markers);
    }
} else {
    show_error(__('Access denied'));
}
?>

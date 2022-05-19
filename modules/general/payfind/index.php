<?php

if (cfr('PAYFIND')) {

    /**
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

    /**
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
        $percent = mysql_real_escape_string($percent);
        if ($percent == '') {
            $percent = 0;
        }
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

    /**
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

    /**
     * Show payment system create and deletion form
     * 
     * @return string
     */
    function web_PaySysForm() {
        $allpaysys = zb_PaySysPercentGetAll();

        $inputs = wf_TextInput('newmarker', __('Payment system marker'), '', true, '10');
        $inputs .= wf_TextInput('newname', __('Payment system name'), '', true, '10');
        $inputs .= wf_TextInput('newpercent', __('Percent withholding payment system'), '', true, '4');
        $inputs .= wf_Submit(__('Save'));
        $form = wf_Form("", "POST", $inputs, 'glamour');
        $result = $form;


        if (!empty($allpaysys)) {

            $cells = wf_TableCell(__('Marker'));
            $cells .= wf_TableCell(__('Name'));
            $cells .= wf_TableCell(__('Percent'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($allpaysys as $marker => $each) {
                $cells = wf_TableCell($marker);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell($each['percent']);
                $cells .= wf_TableCell(wf_JSAlert("?module=payfind&confpaysys=true&delete=" . $marker, web_delete_icon(), __('Removing this may lead to irreparable results')));
                $rows .= wf_TableRow($cells, 'row3');
            }
            $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        }


        $result .= wf_BackLink("?module=payfind");
        $result .= wf_delimiter(1);
        return ($result);
    }

    /**
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

    /**
     * Returns available cashier accounts selector
     * 
     * @return string
     */
    function web_PayFindCashierSelector() {
        $alladmins = rcms_scandir(USERS_PATH);
        $adminlist = array();
        @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
        $result = '';
        if (!empty($alladmins)) {
            foreach ($alladmins as $nu => $login) {
                $administratorName = (isset($employeeLogins[$login])) ? $employeeLogins[$login] : $login;
                $adminlist[$login] = $administratorName;
            }
            $adminlist['openpayz'] = __('OpenPayz');
            $result = wf_Selector('cashier', $adminlist, __('Cashier'), '', true, true);
        }
        return ($result);
    }

    /**
     * Returns available tags selector
     * 
     * @return string
     */
    function web_PayFindTagidSelector() {
        $query = "SELECT `id`,`tagname` from `tagtypes`";
        $result = '';
        $tags = array();
        $alltags = simple_queryall($query);
        if (!empty($alltags)) {
            foreach ($alltags as $io => $eachtag) {
                $tags[$eachtag['id']] = $eachtag['tagname'];
            }
        }
        $result = wf_Selector('tagid', $tags, __('Tags'), '', true, true);
        return($result);
    }

    /**
     * extracts all user logins by tagid in SQL WHERE accessible format
     * 
     * @param $tagid int existing tag ID
     * 
     * @return string
     */
    function zb_PayFindExtractByTagId($tagid) {
        $tagid = vf($tagid, 3);
        $query = "SELECT `login`,`tagid` from `tags` WHERE `tagid`='" . $tagid . "';";
        $alltagged = simple_queryall($query);
        $result = ' AND `login` IN (';

        if (!empty($alltagged)) {
            foreach ($alltagged as $io => $each) {
                $result .= "'" . $each['login'] . "',";
            }
            $result = rtrim($result, ',');
        } else {
            $result .= "'" . zb_rand_string('12') . "'";
        }

        $result .= ') ';
        return ($result);
    }

    /**
     * Returns search table selector
     * 
     * @return string
     */
    function web_PayFindTableSelect() {
        if (wf_CheckPost(array('searchtable'))) {
            $selected = $_POST['searchtable'];
        } else {
            $selected = '';
        }

        $params = array(
            "payments" => __('Finance report'),
            "corrections" => __('Correct saldo')
        );

        $result = wf_Selector('searchtable', $params, __('Search into'), $selected, false);
        return ($result);
    }

    /**
     * Returns payment search form
     * 
     * @return string
     */
    function web_PayFindForm() {
        //try to save calendar states
        if (wf_CheckPost(array('datefrom', 'dateto'))) {
            $curdate = $_POST['dateto'];
            $yesterday = $_POST['datefrom'];
        } else {
            $curdate = date("Y-m-d", time() + 60 * 60 * 24);
            $yesterday = curdate();
        }

        $inputs = __('Date');
        $inputs .= wf_DatePickerPreset('datefrom', $yesterday) . ' ' . __('From');
        $inputs .= wf_DatePickerPreset('dateto', $curdate) . ' ' . __('To');
        $inputs .= wf_delimiter();
        $inputs .= wf_CheckInput('type_payid', '', false, false);
        $inputs .= wf_TextInput('payid', __('Search by payment ID'), '', true, '10');
        $inputs .= wf_CheckInput('type_contract', '', false, false);
        $inputs .= wf_TextInput('contract', __('Search by users contract'), '', true, '10');
        $inputs .= wf_CheckInput('type_login', '', false, false);
        $inputs .= wf_TextInput('login', __('Search by users login'), '', true, '10');
        $inputs .= wf_CheckInput('type_loginwildcard', '', false, false);
        $inputs .= wf_TextInput('loginwildcard', __('Login contains'), '', true, '10');
        $inputs .= wf_CheckInput('type_summ', '', false, false);
        $inputs .= wf_TextInput('summ', __('Search by payment sum'), '', true, '10');
        $inputs .= wf_CheckInput('type_payidenc', '', false, false);
        $inputs .= wf_TextInput('payidenc', __('IDENC'), '', true, '10');
        $inputs .= wf_CheckInput('type_summgreater', '', false, false);
        $inputs .= wf_TextInput('paysummgreater', __('Payment summ greater then'), '', true, '10');
        $inputs .= wf_CheckInput('type_notescontains', '', false, false);
        $inputs .= wf_TextInput('paynotescontains', __('Notes contains'), '', true, '10');
        $inputs .= wf_CheckInput('type_cashtype', '', false, false);
        $inputs .= web_CashTypeSelector() . wf_tag('label', false, '', 'for="cashtype"') . __('Search by cash type') . wf_tag('label', true) . wf_tag('br');
        $inputs .= wf_CheckInput('type_cashier', '', false, false);
        $inputs .= web_PayFindCashierSelector();
        $inputs .= wf_CheckInput('type_tagid', '', false, false);
        $inputs .= web_PayFindTagidSelector();
        $inputs .= wf_CheckInput('type_paysys', '', false, false);
        $inputs .= web_PaySysPercentSelector();
        $inputs .= wf_Link("?module=payfind&confpaysys=true", __('Settings')) . wf_tag('br');
        $inputs .= wf_CheckInput('type_city', '', false, false);
        $inputs .= web_CitySelector() . ' ' . __('City') . wf_delimiter(0);
        $inputs .= wf_CheckInput('type_address', '', false, false);
        $inputs .= wf_TextInput('payaddrcontains', __('Address contains'), '', true, 20);
        $inputs .= wf_CheckInput('type_contragent', '', false, false);
        $inputs .= zb_ContrAhentSelectPreset() . ' ' . __('Service provider') . wf_delimiter(0);
        $inputs .= wf_CheckInput('only_positive', __('Show only positive payments'), true, false);
        $inputs .= wf_CheckInput('numeric_notes', __('Show payments with numeric notes'), true, false);
        $inputs .= wf_CheckInput('numericonly_notes', __('Show payments with only numeric notes'), true, false);
        $inputs .= wf_nbsp(8) . web_PayFindTableSelect() . wf_delimiter();
        $inputs .= wf_HiddenInput('dosearch', 'true');
        $inputs .= wf_Submit(__('Search'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_delimiter(0);
        $result .= wf_BackLink("?module=report_finance");

        return ($result);
    }

    /**
     * Execute search with prepared options and shows search results
     * 
     * @return void
     */
    function web_PaymentSearch($markers, $joins = '') {
        global $ubillingConfig;
        $altercfg = $ubillingConfig->getAlter();
        if (wf_CheckPost(array('searchtable'))) {
            if ($_POST['searchtable'] == 'payments') {
                $table = 'payments';
            }

            if ($_POST['searchtable'] == 'corrections') {
                $table = 'paymentscorr';
            }
        } else {
            $table = 'payments';
        }
        $query = "SELECT * from `" . $table . "`";

        $query .= $joins . $markers;

        $csvdata = '';
        $allpayments = simple_queryall($query);
        if ($altercfg['FINREP_CONTRACT']) {
            $allcontracts = zb_UserGetAllContracts();
            $allcontracts = array_flip($allcontracts);
        }
        if ($altercfg['FINREP_TARIFF']) {
            $alltariffs = zb_TariffsGetAllUsers();
        }
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $alltypes = zb_CashGetAllCashTypes();
        $allservicenames = zb_VservicesGetAllNamesLabeled();
        $allpaysyspercents = zb_PaySysPercentGetAll();

        $totalsumm = 0;
        $paysyssumm = 0;
        $profitsumm = 0;
        $totalcount = 0;

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Date'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('PS%'));
        $cells .= wf_TableCell(__('Profit'));
        $cells .= wf_TableCell(__('Login'));
        if ($altercfg['FINREP_CONTRACT']) {
            $cells .= wf_TableCell(__('Contract'));
        }
        $cells .= wf_TableCell(__('Full address'));
        $cells .= wf_TableCell(__('Real Name'));
        if ($altercfg['FINREP_TARIFF']) {
            $cells .= wf_TableCell(__('Tariff'));
        }
        $cells .= wf_TableCell(__('Payment type'));
        $cells .= wf_TableCell(__('Notes'));
        $cells .= wf_TableCell(__('Admin'));
        $rows = wf_TableRow($cells, 'row1');

        //address contains payments prefilter
        if (ubRouting::checkPost('type_address', 'payaddrcontains')) {
            $addressFilter = ubRouting::post('payaddrcontains', 'mres');
            if (!empty($allpayments)) {
                foreach ($allpayments as $io => $each) {
                    $eachUserAddress = (isset($alladdress[$each['login']])) ? $alladdress[$each['login']] : '';
                    if (!ispos($eachUserAddress, $addressFilter)) {
                        unset($allpayments[$io]);
                    }
                }
            }
        }

        if (!empty($allpayments)) {
            if ($altercfg['FINREP_TARIFF']) {
                $csvTariffColumn = ';' . __('Tariff');
            } else {
                $csvTariffColumn = '';
            }
            $csvdata .= __('ID') . ';' . __('Date') . ';' . __('Cash') . ';' . __('PS%') . ';' . __('Profit') . ';' . __('Login') . ';' . __('Full address') . ';' . __('Real Name') . $csvTariffColumn . ';' . __('Payment type') . ';' . __('Notes') . ';' . __('Admin') . "\n";
            foreach ($allpayments as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['date']);
                $cells .= wf_TableCell($each['summ']);
                //detecting paymentsystem and calc percent
                if (isset($allpaysyspercents[$each['note']])) {
                    $currPc = $allpaysyspercents[$each['note']]['percent'];
                    $rawSumm = $each['summ'];
                    $paySysPc = ($rawSumm / 100) * $currPc;
                    $ourProfit = $rawSumm - $paySysPc;
                } else {
                    $paySysPc = 0;
                    $ourProfit = $each['summ'];
                }
                $cells .= wf_TableCell($paySysPc);
                $cells .= wf_TableCell($ourProfit);

                $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, ''));
                if ($altercfg['FINREP_CONTRACT']) {
                    $cells .= wf_TableCell(@$allcontracts[$each['login']]);
                }
                @$paymentRealname = $allrealnames[$each['login']];
                @$paymentCashType = __($alltypes[$each['cashtypeid']]);
                @$paymentAddress = $alladdress[$each['login']];
                ;
                $cells .= wf_TableCell($paymentAddress);
                $cells .= wf_TableCell($paymentRealname);
                if ($altercfg['FINREP_TARIFF']) {
                    @$userTariff = $alltariffs[$each['login']];
                    $cells .= wf_TableCell($userTariff);
                    $csvTariff = ';' . $userTariff;
                } else {
                    $csvTariff = '';
                }
                $cells .= wf_TableCell($paymentCashType);
                //payment notes translation
                if ($altercfg['TRANSLATE_PAYMENTS_NOTES']) {
                    $paynote = zb_TranslatePaymentNote($each['note'], $allservicenames);
                } else {
                    $paynote = $each['note'];
                }
                $cells .= wf_TableCell($paynote);
                $cells .= wf_TableCell($each['admin']);
                $rows .= wf_TableRow($cells, 'row3');

                //calculating totals
                if ($each['summ'] > 0) {
                    $totalsumm = $totalsumm + $each['summ'];
                    $totalcount++;
                }

                if ($paySysPc > 0) {
                    $paysyssumm = $paysyssumm + $paySysPc;
                }

                if ($ourProfit > 0) {
                    $profitsumm = $profitsumm + $ourProfit;
                }
                $csvSumm = str_replace('.', ',', $each['summ']);
                $csvdata .= $each['id'] . ';' . $each['date'] . ';' . $csvSumm . ';' . $paySysPc . ';' . $ourProfit . ';' . $each['login'] . ';' . $paymentAddress . ';' . $paymentRealname . $csvTariff . ';' . $paymentCashType . ';' . $paynote . ';' . $each['admin'] . "\n";
            }
        }
        //saving report for future download
        if (!empty($csvdata)) {
            $csvSaveName = 'exports/payfind_' . date("Y-m-d_H_i_s") . '.csv';
            $csvSaveNameEnc = base64_encode($csvSaveName);
            file_put_contents($csvSaveName, $csvdata);
            $csvDownloadLink = wf_Link('?module=payfind&downloadcsv=' . $csvSaveNameEnc, wf_img('skins/excel.gif', __('Export')), false);
        } else {
            $csvDownloadLink = '';
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');

        //additional total counters
        $result .= wf_tag('div', false, 'glamour') . __('Count') . ': ' . $totalcount . wf_tag('div', true);
        $result .= wf_tag('div', false, 'glamour') . __('Total payments') . ': ' . $totalsumm . wf_tag('div', true);
        $result .= wf_tag('div', false, 'glamour') . __('Payment systems %') . ': ' . $paysyssumm . wf_tag('div', true);
        $result .= wf_tag('div', false, 'glamour') . __('Our final profit') . ': ' . $profitsumm . wf_tag('div', true);
        $result .= wf_CleanDiv();

        show_window(__('Payments found') . ' ' . $csvDownloadLink, $result);
    }

    /*
     * Interfaces
     */

    if (!wf_CheckGet(array('confpaysys'))) {
        show_window(__('Payment search'), web_PayFindForm());
        zb_BillingStats(true);
    } else {
        show_window(__('Payment systems'), web_PaySysForm());
    }

    /*
     * Controller section
     */


    //downloading report as csv
    if (wf_CheckGet(array('downloadcsv'))) {
        zb_DownloadFile(base64_decode($_GET['downloadcsv']), 'excel');
    }

    //Payment systems configuration
    //adding payment system
    if (wf_CheckPost(array('newmarker', 'newname'))) {
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
    $joins = '';

    //date search
    if (wf_CheckPost(array('datefrom', 'dateto'))) {
        $datefrom = mysql_real_escape_string($_POST['datefrom']);
        $dateto = mysql_real_escape_string($_POST['dateto']);
        $markers .= "WHERE `date` BETWEEN '" . $datefrom . "' AND '" . $dateto . "' ";
    }

    //payment id search
    if (wf_CheckPost(array('type_payid', 'payid'))) {
        $payid = vf($_POST['payid'], 3);
        $markers .= "AND `id`='" . $payid . "' ";
    }

    //contract search
    if (wf_CheckPost(array('type_contract', 'contract'))) {
        $contract = mysql_real_escape_string($_POST['contract']);
        $allcontracts = zb_UserGetAllContracts();
        if (!empty($allcontracts)) {
            if (isset($allcontracts[$contract])) {
                $contractlogin = $allcontracts[$contract];
                $markers .= "AND `login`='" . $contractlogin . "' ";
            }
        }
    }

    //login payment search
    if (wf_CheckPost(array('type_login', 'login'))) {
        $userlogin = mysql_real_escape_string($_POST['login']);
        $markers .= "AND `login`='" . $userlogin . "' ";
    }

    //not strict login search
    if (wf_CheckPost(array('type_loginwildcard', 'loginwildcard'))) {
        $userloginW = mysql_real_escape_string($_POST['loginwildcard']);
        $markers .= "AND `login` LIKE '%" . $userloginW . "%' ";
    }

    //payment sum  search
    if (wf_CheckPost(array('type_summ', 'summ'))) {
        $summ = mysql_real_escape_string($_POST['summ']);
        $markers .= "AND `summ`='" . $summ . "' ";
    }

    //cashtype search
    if (wf_CheckPost(array('type_cashtype', 'cashtype'))) {
        $cashtype = vf($_POST['cashtype'], 3);
        $markers .= "AND `cashtypeid`='" . $cashtype . "' ";
    }

    //cashiers search
    if (wf_CheckPost(array('type_cashier', 'cashier'))) {
        $cashierLogin = mysql_real_escape_string($_POST['cashier']);
        $markers .= "AND `admin`='" . $cashierLogin . "' ";
    }

    //payment system search
    if (wf_CheckPost(array('type_paysys', 'paysys'))) {
        $cashtype = mysql_real_escape_string($_POST['paysys']);
        $markers .= "AND `note` LIKE '" . $cashtype . "' ";
    }

    //only positive payments search
    if (isset($_POST['only_positive'])) {
        $markers .= "AND `summ` >'0' ";
    }

    //payments with numeric notes
    if (isset($_POST['numeric_notes'])) {
        $markers .= "AND  `note` >0 ";
    }

    //payments only with numeric notes
    if (isset($_POST['numericonly_notes'])) {
        $markers .= "AND  `note`  REGEXP '^[0-9]+$' ";
    }

    //tagtype search
    if (wf_CheckPost(array('type_tagid', 'tagid'))) {
        $markers .= zb_PayFindExtractByTagId($_POST['tagid']);
    }

    //idenc search
    if (wf_CheckPost(array('type_payidenc', 'payidenc'))) {
        $payidenc = vf($_POST['payidenc']);
        $payidNormal = zb_NumUnEncode($payidenc);
        $markers .= "AND `id`='" . $payidNormal . "' ";
    }

    //summ is greater search
    if (wf_CheckPost(array('type_summgreater', 'paysummgreater'))) {
        $markers .= "AND `summ` > " . mysql_real_escape_string($_POST['paysummgreater']) . " ";
    }

    //payment notes contains search
    if (wf_CheckPost(array('type_notescontains', 'paynotescontains'))) {
        $notesMask = mysql_real_escape_string($_POST['paynotescontains']);
        $markers .= "AND `note` LIKE '%" . $notesMask . "%' ";
    }

    //filter by city
    if (wf_CheckPost(array('type_city', 'citysel'))) {
        $cityID = mysql_real_escape_string($_POST['citysel']);

        $joins .= " RIGHT JOIN (SELECT `address`.`login`,`city`.`cityname` FROM `address` 
                                    INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
                                    INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
                                    INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
                                    INNER JOIN `city` ON `street`.`cityid`=`city`.`id`
                                WHERE `city`.`id` = " . $cityID . ") AS `tmpCity` USING(`login`) ";
    }

    //filter by strict contragent assign
    if (wf_CheckPost(array('type_contragent', 'ahentsel'))) {
        $contragentID = mysql_real_escape_string($_POST['ahentsel']);

        $joins .= " RIGHT JOIN (SELECT `ahenassignstrict`.`login`,`ahenassignstrict`.`agentid` FROM `ahenassignstrict`                                    
                                WHERE `ahenassignstrict`.`agentid` = " . $contragentID . ") AS `tmpContragents` USING(`login`) ";
    }

    //executing search
    if (wf_CheckPost(array('dosearch'))) {
        web_PaymentSearch($markers, $joins);
    }
} else {
    show_error(__('Access denied'));
}


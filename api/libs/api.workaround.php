<?php

/**
 * Retunrs typical back to profile/editing controls
 * 
 * @param string $login
 * @return string
 */
function web_UserControls($login) {
    global $ubillingConfig;
    $oldStyleFlag = $ubillingConfig->getAlterParam('OLD_USERCONTROLS');

    $urlProfile = '?module=userprofile&username=';
    $urlUserEdit = '?module=useredit&username=';

    $controls = wf_tag('div', false);
    if ($oldStyleFlag) {
        $controls .= wf_Link($urlProfile . $login, wf_img_sized('skins/icon_user_big.gif', __('Back to user profile'), '48') . __('Back to user profile'), true, '');
        $controls .= wf_tag('br');
        $controls .= wf_Link($urlUserEdit . $login, wf_img_sized('skins/icon_user_edit_big.gif', __('Back to user edit'), '48') . __('Back to user edit'), false, '');
    } else {
        if (cfr('USERPROFILE')) {
            $controls .= wf_Link($urlProfile . $login, wf_img_sized('skins/backprofile.png', __('Back to user profile'), '16') . ' ' . __('Back to user profile'), false, 'ubbackprofile') . ' ';
        }

        if ((cfr('USEREDIT')) AND ( @$_GET['module'] != 'useredit')) {
            $controls .= wf_Link($urlUserEdit . $login, wf_img_sized('skins/backedit.png', __('Back to user edit'), '16') . ' ' . __('Back to user edit'), false, 'ubbackedit');
        }
    }
    $controls .= wf_tag('div', true);
    return($controls);
}

/**
 * return current locale in two letter format
 * 
 * @return string
 */
function curlang() {
    global $system;
    $result = $system->language;
    $result = vf($result);
    return ($result);
}

/**
 * Returns user logins with non unique passwords
 * 
 * @return array
 */
function zb_GetNonUniquePasswordUsers() {
    $query_p = "SELECT `Password`,count(*) as cnt from `users` GROUP BY `Password` having cnt >1;";
    $duppasswords = simple_queryall($query_p);
    $result = array();
    if (!empty($duppasswords)) {
        foreach ($duppasswords as $io => $each) {
            $query_l = "SELECT `login` from `users` WHERE `Password`='" . $each['Password'] . "'";
            $userlogins = simple_queryall($query_l);
            if (!empty($userlogins)) {
                foreach ($userlogins as $ia => $eachlogin) {
                    $result[] = $eachlogin['login'];
                }
            }
        }
    }
    return ($result);
}

/**
 * Checks is some password unique
 * 
 * @param string $password
 * @return bool
 */
function zb_CheckPasswordUnique($password) {
    $password = mysql_real_escape_string($password);
    $query = "SELECT `login` from `users` WHERE `Password`='" . $password . "'";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns localised calendar control. Used only for backward compat with old modules. 
 * Use wf_DatePicker() instead.
 * 
 * @param string $field
 * @return string
 */
function web_CalendarControl($field) {
    $result = wf_DatePicker($field);
    return ($result);
}

/**
 * Returns localised boolean value in human-readable view
 * 
 * @param bool $value
 * @return string
 */
function web_trigger($value) {
    if ($value) {
        $result = __('Yes');
    } else {
        $result = __('No');
    }
    return($result);
}

/**
 * Returns form for editing one field string data
 * 
 * @param array $fieldnames 
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @param string $pattern
 * 
 * @return string
 */
function web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $olddata = '', $pattern = '') {
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey, '', '', false, '', $pattern), '', 'row3');
    $rows .= wf_TableRow($cells);
    $form = wf_TableBody($rows, '100%', 0);
    $form .= wf_Submit(__('Change'));
    $form = wf_Form("", 'POST', $form, '');
    $form .= wf_delimiter();

    return($form);
}

/**
 * Returns suspect cash JS alert
 * 
 * @param float $suspect
 * @return string
 */
function js_CashCheck($suspect) {
    $suspect = vf($suspect, 3);

    $result = '
       <script type="text/javascript">
        function cashsuspectalert() {
              alert(\'' . __('You try to bring to account suspiciously large amount of money. We have nothing against, but please check that all is correct') . '\');
        }

        function checkcashfield()
        {
        var cashfield=document.getElementById("cashfield").value;
        
        if (cashfield > ' . $suspect . ') {
            cashsuspectalert();
        }
       }
   </script>
        ';

    return ($result);
}

/**
 * Returns form for editing one field string password data
 * 
 * @param array $fieldnames 
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @return string
 */
function web_EditorStringDataFormPassword($fieldnames, $fieldkey, $useraddress, $olddata = '') {
    global $ubillingConfig;
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
    $alterconf = $ubillingConfig->getAlter();
    $passwordsType = (isset($alterconf['PASSWORD_TYPE'])) ? $alterconf['PASSWORD_TYPE'] : 1;
    $passwordsLenght = (isset($alterconf['PASSWORD_GENERATION_LENGHT'])) ? $alterconf['PASSWORD_GENERATION_LENGHT'] : 8;

    $password_proposal = '';
    switch ($passwordsType) {
        case 0:
            $password_proposal = zb_rand_digits($passwordsLenght);
            break;
        case 1:
            $password_proposal = zb_rand_string($passwordsLenght);
            break;
        case 2:
            $password_proposal = zb_PasswordGenerate($passwordsLenght);
            break;
        default :
            $password_proposal = zb_rand_string(8);
            break;
    }



    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey, '', $password_proposal, false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);
    $form = wf_TableBody($rows, '100%', 0);
    $form .= wf_Submit(__('Change'));
    $form = wf_Form("", 'POST', $form, '');
    $form .= wf_delimiter();


    return($form);
}

/**
 * Returns form for editing one field string contract data
 * 
 * @param array $fieldnames 
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @return string
 */
function web_EditorStringDataFormContract($fieldnames, $fieldkey, $useraddress, $olddata = '') {
    $altcfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
    $olddata = trim($olddata);
    if (empty($olddata)) {
        $allcontracts = zb_UserGetAllContracts();
        $top_offset = 100000;
//contract generation mode default
        if ($altcfg['CONTRACT_GENERATION_DEFAULT']) {
            for ($i = 1; $i < $top_offset; $i++) {
                if (!isset($allcontracts[$i])) {
                    $contract_proposal = $i;
                    break;
                }
            }
        } else {
//alternate generation method
            $max_contract = max(array_keys($allcontracts));
            $contract_proposal = $max_contract + 1;
        }
    } else {
        $contract_proposal = '';
    }

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);
    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);
    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey, '', $contract_proposal, false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);
    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');


    return($form);
}

/**
 * Returns MAC address changing form - manual input
 * 
 * @param array $fieldnames
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @return string
 */
function web_EditorStringDataFormMAC($fieldnames, $fieldkey, $useraddress, $olddata = '') {
    global $ubillingConfig;
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
    $altconf = $ubillingConfig->getAlter();
//mac vendor search
    if ($altconf['MACVEN_ENABLED']) {
        $optionState = $altconf['MACVEN_ENABLED'];
        switch ($optionState) {
            case 1:
                $lookupUrl = '?module=macvendor&modalpopup=true&mac=' . $olddata . '&username=';
                $lookuplink = wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                $lookuplink .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                break;
            case 2:
                $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $olddata . '" width="360" height="160" frameborder="0"');
                $vendorframe .= wf_tag('iframe', true);
                $lookuplink = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
                break;
            case 3:
                $lookupUrl = '?module=macvendor&raw=true&mac=' . $olddata;
                $lookuplink = wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                $lookuplink .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                break;
        }
    } else {
        $lookuplink = '';
    }


    if ($altconf['MACCHANGERANDOMDEFAULT']) {
// funny random mac, yeah? :)
        $randommac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
        if (zb_mac_unique($randommac)) {
            $newvalue = $randommac;
        } else {
            show_error('Oops');
            $newvalue = '';
        }
    } else {
        $newvalue = '';
    }


    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);
    $cells = wf_TableCell($field1 . ' ' . $lookuplink, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);
    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey, '', $newvalue, false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);
    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Returns simple MAC address selector
 * 
 * @global object $ubillingConfig
 * @param string $name
 * @return string
 */
function zb_NewMacSelect($name = 'newmac') {
    global $ubillingConfig;
    $billing_config = $ubillingConfig->getBilling();
    $alter_conf = $ubillingConfig->getAlter();
    $sudo = $billing_config['SUDO'];
    $cat = $billing_config['CAT'];
    $grep = $billing_config['GREP'];
    $tail = $billing_config['TAIL'];
    $leases = $alter_conf['NMLEASES'];
    $leasesmark = $alter_conf['NMLEASEMARK'];
    $command = $sudo . ' ' . $cat . ' ' . $leases . ' | ' . $grep . '  "' . $leasesmark . '" | ' . $tail . ' -n 200';
    $rawdata = shell_exec($command);
    $allUsedMacs = zb_getAllUsedMac();
    $resultArr = array();
    $nmarr = array();


    if (!empty($rawdata)) {
        $cleardata = exploderows($rawdata);
        foreach ($cleardata as $eachline) {
            preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
            if (!empty($matches[0])) {
                $nmarr[] = $matches[0];
            }
            if ($alter_conf['NMLEASES_EXTEND']) {
                $eachline = preg_replace('/([a-f0-9]{2})(?![\s\]\/])([\.\:\-]?)/', '\1:', $eachline);
                preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
                if (!empty($matches[0])) {
                    $nmarr[] = $matches[0];
                }
            }
        }

        $unique_nmarr = array_unique($nmarr);
        if (!empty($unique_nmarr)) {
            foreach ($unique_nmarr as $newmac) {
                if (zb_checkMacFree($newmac, $allUsedMacs)) {
                    $resultArr[$newmac] = $newmac;
                }
            }
//revert array due usability reasons (i hope).
            if (@$alter_conf['NMREVERSE']) {
                $resultArr = array_reverse($resultArr);
            }
        }
    }

    $result = wf_Selector($name, $resultArr, '', '', false);

    return($result);
}

/**
 * Returns MAC editing form with default select box
 * 
 * @param array  $fieldnames
 * @param string $fieldkey (deprecated?)
 * @param string $useraddress
 * @param string $olddata
 * @return string
 */
function web_EditorStringDataFormMACSelect($fieldnames, $fieldkey, $useraddress, $olddata = '') {
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
//mac vendor search
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['MACVEN_ENABLED']) {
        $optionState = $alterconf['MACVEN_ENABLED'];
        switch ($optionState) {
            case 1:
                $lookupUrl = '?module=macvendor&modalpopup=true&mac=' . $olddata . '&username=';
                $lookuplink = wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                $lookuplink .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                break;
            case 2:
                $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $olddata . '" width="360" height="160" frameborder="0"');
                $vendorframe .= wf_tag('iframe', true);
                $lookuplink = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
                break;
            case 3:
                $lookupUrl = '?module=macvendor&raw=true&mac=' . $olddata;
                $lookuplink = wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                $lookuplink .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                break;
        }
    } else {
        $lookuplink = '';
    }

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($field1 . ' ' . $lookuplink, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(zb_NewMacSelect(), '', 'row3');
    $rows .= wf_TableRow($cells);
    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Credit expire date editor
 * 
 * @param array  $fieldnames
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @return string
 */
function web_EditorDateDataForm($fieldnames, $fieldkey, $useraddress, $olddata = '') {
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);
    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);
    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_DatePicker($fieldkey, false), '', 'row3');
    $rows .= wf_TableRow($cells);
    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');


    return($form);
}

/**
 * Returns cash type selector for manual payments
 * 
 * @return string
 */
function web_CashTypeSelector($CashType = '') {
    $allcashtypes = zb_CashGetAlltypes();
    $cashtypes = array();
    if (!empty($allcashtypes)) {
        foreach ($allcashtypes as $io => $each) {
            $cashtypes[$each['id']] = __($each['cashtype']);
        }

        $defaultCashtype = zb_StorageGet('DEF_CT');
//if no default cashtype selected
        if (empty($defaultCashtype)) {
            $defaultCashtype = 'NOP';
        }

        $selectCashType = (!empty($CashType)) ? $CashType : $defaultCashtype;

        $selector = wf_Selector('cashtype', $cashtypes, '', $selectCashType, false);
    }

    return($selector);
}

/**
 * Checks is table with some name exists, and returns int value 0/1 used as bool (Oo)
 * 
 * @param string $tablename
 * @return int
 */
function zb_CheckTableExists($tablename) {
    $query = "SELECT CASE WHEN (SELECT COUNT(*) AS STATUS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME = '" . $tablename . "') = 1 THEN (SELECT 1)  ELSE (SELECT 0) END AS result;";
    $result = simple_query($query);
    return ($result['result']);
}

/**
 * Returns primary cash management form
 * 
 * @global object $ubillingConfig
 * @param array   $fieldnames
 * @param string  $fieldkey
 * @param string  $useraddress
 * @param string  $olddata
 * @param float   $tariff_price
 * @return string
 */
function web_EditorCashDataForm($fieldnames, $fieldkey, $useraddress, $olddata = '', $tariff_price = '', $userrealname = '') {
    global $ubillingConfig;
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
    $me = whoami();

//cash suspect checking 
    $alterconf = $ubillingConfig->getAlter();
//balance editing limiting
    $limitedFinance = false;
    if (@$alterconf['CAN_TOUCH_MONEY']) {
        if (!empty($alterconf['CAN_TOUCH_MONEY'])) {
            $limitedFinance = true;
            $godministrators = explode(',', $alterconf['CAN_TOUCH_MONEY']); //coma separated
            $godministrators = array_flip($godministrators);
        }
    }
    if ($alterconf['SUSP_PAYMENTS_NOTIFY']) {
        $suspnotifyscript = js_CashCheck($alterconf['SUSP_PAYMENTS_NOTIFY']);
        $cashfieldanchor = 'onchange="checkcashfield();"';
    } else {
        $suspnotifyscript = '';
        $cashfieldanchor = '';
    }

    if ($alterconf['SETCASH_ONLY_ROOT']) {
        if (cfr('ROOT')) {
            $setCashControl = wf_RadioInput('operation', __('Set cash'), 'set', false, false);
        } else {
            $setCashControl = '';
        }
    } else {
        $setCashControl = wf_RadioInput('operation', __('Set cash'), 'set', false, false);
    }

    $radio = '';
    $radio .= wf_RadioInput('operation', __('Add cash'), 'add', false, true);
//additional controls
    $extRadio = '';
    $extRadio .= wf_RadioInput('operation', __('Correct saldo'), 'correct', false, false);
    $extRadio .= wf_RadioInput('operation', __('Mock payment'), 'mock', false, false);
    $extRadio .= $setCashControl;

    if ($limitedFinance) {
        if (isset($godministrators[$me])) {
            $radio .= $extRadio;
        }
    } else {
        $radio .= $extRadio;
    }
//cash input widget
    $cashInputControl = wf_tag('input', false, '', ' type="text" name="' . $fieldkey . '" size="5" id="cashfield" ' . $cashfieldanchor . ' autofocus');
    $cashInputControl .= ' ' . __('The expected payment') . ': ' . $tariff_price;



    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);

    if (!empty($userrealname)) {
        $cells = wf_TableCell('', '', 'row2');
        $cells .= wf_TableCell($userrealname, '', 'row3');
        $rows .= wf_TableRow($cells);
    }

    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell(wf_tag('b') . $olddata . wf_tag('b', true), '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell($cashInputControl, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell(__('Actions'), '', 'row2');
    $cells .= wf_TableCell($radio, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell(__('Payment type'), '', 'row2');
    $cells .= wf_TableCell(web_CashTypeSelector(), '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell(__('Payment notes'), '', 'row2');
    $cells .= wf_TableCell(wf_TextInput('newpaymentnote', '', '', false, 40), '', 'row3');
    $rows .= wf_TableRow($cells);

    $table = wf_TableBody($rows, '100%', 0, '');

    if ($ubillingConfig->getAlterParam('DREAMKAS_ENABLED')) {
        $DreamKas = new DreamKas();
        $table .= $DreamKas->web_FiscalizePaymentCtrls('internet');
        $table .= wf_tag('script', false, '', 'type="text/javascript"');
        $table .= '$(document).ready(function() {
                    // dirty hack with setTimeout() to work in Chrome 
                    setTimeout(function(){
                            $(\'#cashfield\').focus();
                    }, 100);
                  });   
                 ';
        $table .= wf_tag('script', true);
    }

    $table .= wf_Submit(__('Payment'));

    $form = $suspnotifyscript;
    $form .= wf_Form('', 'POST', $table, '');
    $form .= wf_delimiter();

    return($form);
}

/**
 * Returns 0/1 trigger selector
 * 
 * @param string $name
 * @param int    $state
 * @param bool   $disableYes
 *
 * @return string
 */
function web_TriggerSelector($name, $state = '', $disableYes = false) {
    $noflag = (!$state) ? 'SELECTED' : '';
    $disableYes = ($disableYes) ? ' disabled ' : '';

    $selector = wf_tag('select', false, '', 'name="' . $name . '"');
    $selector .= wf_tag('option', false, '', 'value="1"' . $disableYes) . __('Yes') . wf_tag('option', true);
    $selector .= wf_tag('option', false, '', 'value="0" ' . $noflag) . __('No') . wf_tag('option', true);
    $selector .= wf_tag('select', true);

    return ($selector);
}

/**
 * Returns string editor grid edit form
 * 
 * @param string $fieldname
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @param bool   $disableYes
 *
 * @return string
 */
function web_EditorTrigerDataForm($fieldname, $fieldkey, $useraddress, $olddata = '', $disableYes = false) {
    $curstate = web_trigger($olddata);

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);
    $cells = wf_TableCell($fieldname, '', 'row2');
    $cells .= wf_TableCell($curstate, '', 'row3');
    $rows .= wf_TableRow($cells);
    $cells = wf_TableCell('', '', 'row2');
    $cells .= wf_TableCell(web_TriggerSelector($fieldkey, $olddata, $disableYes), '', 'row3');
    $rows .= wf_TableRow($cells);
    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Returns all available tariff names
 * 
 * @return array
 */
function zb_TariffsGetAll() {
    $query = "SELECT `name` from `tariffs`";
    $alltariffs = simple_queryall($query);
    return ($alltariffs);
}

/**
 * Returns available tariffs selector
 * 
 * @param string $fieldname
 * @return string
 */
function web_tariffselector($fieldname = 'tariffsel') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['BRANCHES_ENABLED']) {
        global $branchControl;
        $branchControl->loadTariffs();
    }


    $alltariffs = zb_TariffsGetAll();
    $options = array();


    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            if ($altCfg['BRANCHES_ENABLED']) {
                if ($branchControl->isMyTariff($eachtariff['name'])) {
                    $options[$eachtariff['name']] = $eachtariff['name'];
                }
            } else {
                $options[$eachtariff['name']] = $eachtariff['name'];
            }
        }
    }

    $selector = wf_Selector($fieldname, $options, '', '', false);
    return($selector);
}

/**
 * Returns tariff selector without lousy tariffs
 * 
 * @param string $fieldname
 * @return string
 */
function web_tariffselectorNoLousy($fieldname = 'tariffsel') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['BRANCHES_ENABLED']) {
        global $branchControl;
        $branchControl->loadTariffs();
    }

    $alltariffs = zb_TariffsGetAll();
    $allousytariffs = zb_LousyTariffGetAll();
    $options = array();

    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            if (!zb_LousyCheckTariff($eachtariff['name'], $allousytariffs)) {
                if ($altCfg['BRANCHES_ENABLED']) {
                    if ($branchControl->isMyTariff($eachtariff['name'])) {
                        $options[$eachtariff['name']] = $eachtariff['name'];
                    }
                } else {
                    $options[$eachtariff['name']] = $eachtariff['name'];
                }
            }
        }
    }

    $selector = wf_Selector($fieldname, $options, '', '', false);

    return($selector);
}

/**
 * Returns full tariff changing form
 * 
 * @global object $ubillingConfig
 * @param string  $fieldname
 * @param string  $fieldkey
 * @param string  $useraddress
 * @param string  $olddata
 * @return string
 */
function web_EditorTariffForm($fieldname, $fieldkey, $useraddress, $olddata = '') {
    global $ubillingConfig;
    $alter = $ubillingConfig->getAlter();

    $login = ( isset($_GET['username']) ) ? vf($_GET['username']) : null;

    $nm_flag = ( $olddata == '*_NO_TARIFF_*' ) ? 'DISABLED' : null;

    if (isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS'])) {
        $payment = zb_UserGetSignupPrice($login);
        $paid = zb_UserGetSignupPricePaid($login);
        $disabled = ( $payment == $paid && $payment > 0 ) ? 'disabled' : null;
        $charge_signup_price_checkbox = '
            <label for="charge_signup_price_checkbox"> ' . __('Charge signup price') . '
                <input type="checkbox"  name="charge_signup_price" id="charge_signup_price_checkbox" ' . $disabled . '> 
            </label>
        ';
    } else {
        $charge_signup_price_checkbox = null;
    }

    $nmControl = wf_tag('label', false, '', 'for="nm"');
    $nmControl .= __('Next month');
    $nmControl .= wf_tag('input', false, '', 'type="checkbox"  name="nextmonth" id="nm" ' . $nm_flag);
    $nmControl .= wf_tag('label', true);

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($fieldname, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($nmControl, '', 'row2', 'align="right"');
    $cells .= wf_TableCell(web_tariffselector($fieldkey) . $charge_signup_price_checkbox, '', 'row3');
    $rows .= wf_TableRow($cells);

    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_tag('br');
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Returns tariff changing form without lousy tariffs
 * 
 * @global object $ubillingConfig
 * @param string  $fieldname
 * @param string $fieldkey
 * @param string $useraddress
 * @param string $olddata
 * @return string
 */
function web_EditorTariffFormWithoutLousy($fieldname, $fieldkey, $useraddress, $olddata = '') {
    global $ubillingConfig;
    $alter = $ubillingConfig->getAlter();

    $login = ( isset($_GET['username']) ) ? vf($_GET['username']) : null;

    $nm_flag = ( $olddata == '*_NO_TARIFF_*' ) ? 'DISABLED' : null;

    if (isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS'])) {
        $payment = zb_UserGetSignupPrice($login);
        $paid = zb_UserGetSignupPricePaid($login);
        $disabled = ( $payment == $paid && $payment > 0 ) ? 'disabled' : null;
        $charge_signup_price_checkbox = '
            <label for="charge_signup_price_checkbox"> ' . __('Charge signup price') . '
                <input type="checkbox"  name="charge_signup_price" id="charge_signup_price_checkbox" ' . $disabled . '> 
            </label>
        ';
    } else {
        $charge_signup_price_checkbox = null;
    }

    $nmControl = wf_tag('label', false, '', 'for="nm"');
    $nmControl .= __('Next month');
    $nmControl .= wf_tag('input', false, '', 'type="checkbox"  name="nextmonth" id="nm" ' . $nm_flag);
    $nmControl .= wf_tag('label', true);

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($useraddress, '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($fieldname, '', 'row2');
    $cells .= wf_TableCell($olddata, '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($nmControl, '', 'row2', 'align="right"');
    $cells .= wf_TableCell(web_tariffselectorNoLousy($fieldkey) . $charge_signup_price_checkbox, '', 'row3');
    $rows .= wf_TableRow($cells);

    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_tag('br');
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Returns two strings data grid editor (used in tariffspeeds)
 * 
 * @param array $fieldnames
 * @param array $fieldkeys
 * @param array $olddata
 * @return string
 */
function web_EditorTwoStringDataForm($fieldnames, $fieldkeys, $olddata) {
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
    $fieldkey1 = $fieldkeys['fieldkey1'];
    $fieldkey2 = $fieldkeys['fieldkey2'];

    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey1, '', $olddata[1], false, ''), '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey2, '', $olddata[2], false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);

    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Returns six strings data grid editor (used in tariffspeeds modified by Pautina)
 *
 * @param array $fieldnames
 * @param array $fieldkeys
 * @param array $olddata
 * @return string
 */
function web_EditorSixStringDataForm($fieldnames, $fieldkeys, $olddata) {
    $field1 = $fieldnames['fieldname1'];
    $field2 = $fieldnames['fieldname2'];
    $field3 = $fieldnames['fieldname3'];
    $field4 = $fieldnames['fieldname4'];
    $field5 = $fieldnames['fieldname5'];
    $field6 = $fieldnames['fieldname6'];
    $fieldkey1 = $fieldkeys['fieldkey1'];
    $fieldkey2 = $fieldkeys['fieldkey2'];
    $fieldkey3 = $fieldkeys['fieldkey3'];
    $fieldkey4 = $fieldkeys['fieldkey4'];
    $fieldkey5 = $fieldkeys['fieldkey5'];
    $fieldkey6 = $fieldkeys['fieldkey6'];

    $cells = wf_TableCell($field1, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey1, '', $olddata[1], false, ''), '', 'row3');
    $rows = wf_TableRow($cells);

    $cells = wf_TableCell($field2, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey2, '', $olddata[2], false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field3, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey3, '', $olddata[3], false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field4, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey4, '', $olddata[4], false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field5, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey5, '', $olddata[5], false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);

    $cells = wf_TableCell($field6, '', 'row2');
    $cells .= wf_TableCell(wf_TextInput($fieldkey6, '', $olddata[6], false, ''), '', 'row3');
    $rows .= wf_TableRow($cells);

    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form("", 'POST', $inputs, '');

    return($form);
}

/**
 * Translates payment notes into human-readable string
 * 
 * @param string $paynote
 * @param array $allservicenames
 * @return string
 */
function zb_TranslatePaymentNote($paynote, $allservicenames) {
    if ($paynote == '') {
        $paynote = __('Internet');
    }

    if (isset($allservicenames[$paynote])) {
        $paynote = $allservicenames[$paynote];
    }

    if (ispos($paynote, 'CARD:')) {
        $cardnum = explode(':', $paynote);
        $paynote = __('Card') . " " . $cardnum[1];
    }

    if (ispos($paynote, 'SCFEE')) {
        $paynote = __('Credit fee');
    }

    if (ispos($paynote, 'AFFEE')) {
        $paynote = __('Freezing fee');
    }

    if (ispos($paynote, 'TCHANGE:')) {
        $tariff = explode(':', $paynote);
        $paynote = __('Tariff change') . " " . $tariff[1];
    }

    if (ispos($paynote, 'BANKSTA:')) {
        $banksta = explode(':', $paynote);
        $paynote = __('Bank statement') . " " . $banksta[1];
    }

    if (ispos($paynote, 'MOCK:')) {
        $mock = explode(':', $paynote);
        $paynote = __('Mock payment') . ' ' . $mock[1];
    }

    if (ispos($paynote, 'BALANCESET:')) {
        $balset = explode(':', $paynote);
        $paynote = __('Set cash') . ' ' . $balset[1];
    }

    if (ispos($paynote, 'DISCOUNT:')) {
        $disountset = explode(':', $paynote);
        $paynote = __('Discount') . ' ' . $disountset[1] . '%';
    }

    if (ispos($paynote, 'PENALTY')) {
        $penalty = explode(':', $paynote);
        $paynote = __('Penalty') . ' ' . $penalty[1] . ' ' . __('days');
    }

    if (ispos($paynote, 'REMINDER')) {
        $paynote = __('SMS reminder activation');
    }

    if (ispos($paynote, 'FRIENDSHIP')) {
        $friendship = explode(':', $paynote);
        $paynote = __('Friendship') . ' ' . $friendship[1];
    }

    if (ispos($paynote, 'SCHEDULED')) {
        $paynote = __('Scheduled');
    }

    if (ispos($paynote, 'ECHARGE')) {
        $echarged = explode(':', $paynote);
        $paynote = __('Manually charged') . ' ' . $echarged[1];
    }

    if (ispos($paynote, 'DDT')) {
        $ddtcharged = explode(':', $paynote);
        $paynote = __('Doomsday tariff') . ': ' . $ddtcharged[1];
    }

    if (ispos($paynote, 'PTFEE')) {
        $paynote = __('PT') . ' ' . __('Fee');
    }

    if (ispos($paynote, 'EXTFEE')) {
        $paynote = __('External fee');
    }

    return ($paynote);
}

/**
 * Returns list of available tariffs speeds
 * 
 * @return string
 */
function web_TariffSpeedLister() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $results = '';
    $alltariffs = zb_TariffsGetAll();
    $availTariffs = array();
    $allspeeds = zb_TariffGetAllSpeeds();
    $cleanSpeedCount = 0;

    $cells = wf_TableCell(__('Tariff'));
    $cells .= wf_TableCell(__('Download speed'));
    $cells .= wf_TableCell(__('Upload speed'));
    if ($altCfg['BURST_ENABLED']) {
        $cells .= wf_TableCell(__('Burst Download speed'));
        $cells .= wf_TableCell(__('Burst Upload speed'));
        $cells .= wf_TableCell(__('Burst Download Time speed'));
        $cells .= wf_TableCell(__('Burst Upload Time speed'));
    }
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            $availTariffs[$eachtariff['name']] = $eachtariff['name'];
            $cells = wf_TableCell($eachtariff['name']);
            $cells .= wf_TableCell(@$allspeeds[$eachtariff['name']]['speeddown']);
            $cells .= wf_TableCell(@$allspeeds[$eachtariff['name']]['speedup']);
            if ($altCfg['BURST_ENABLED']) {
                $cells .= wf_TableCell(@$allspeeds[$eachtariff['name']]['burstdownload']);
                $cells .= wf_TableCell(@$allspeeds[$eachtariff['name']]['burstupload']);
                $cells .= wf_TableCell(@$allspeeds[$eachtariff['name']]['bursttimedownload']);
                $cells .= wf_TableCell(@$allspeeds[$eachtariff['name']]['burstimetupload']);
            }
            $actLinks = wf_JSAlert('?module=tariffspeeds&tariff=' . $eachtariff['name'], web_edit_icon(), __('Are you serious'));
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }



    $result = wf_TableBody($rows, '100%', 0, 'sortable');

    if (!empty($allspeeds)) {
        $cells = wf_TableCell(__('Tariff') . ' (' . __('Deleted') . ')');
        $cells .= wf_TableCell(__('Download speed'));
        $cells .= wf_TableCell(__('Upload speed'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($allspeeds as $eachtariff => $eachspeed) {
            if (!isset($availTariffs[$eachtariff])) {
                $cells = wf_TableCell($eachtariff);
                $cells .= wf_TableCell($eachspeed['speeddown']);
                $cells .= wf_TableCell($eachspeed['speedup']);
                $cells .= wf_TableCell(wf_JSAlert('?module=tariffspeeds&deletespeed=' . $eachtariff, web_delete_icon(), __('Are you serious')));
                $rows .= wf_TableRow($cells, 'row3');
                $cleanSpeedCount++;
            }
        }
        if ($cleanSpeedCount != 0) {
            $result .= wf_delimiter();
            $result .= wf_tag('h3') . __('Database cleanup') . wf_tag('h3', true);
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        }
    }

    return($result);
}

/**
 * Returns an array with stargazer user data by some login
 * 
 * @param string $login
 * @return array
 */
function zb_ProfileGetStgData($login) {
    $login = vf($login);
    $query = "SELECT * from `users` WHERE `login`='" . $login . "'";
    $userdata = simple_query($query);
    return($userdata);
}

/**
 * Returns switch data in profile form
 * 
 * @param string $login
 * @return string
 */
function web_ProfileSwitchControlForm($login) {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `switchportassign` WHERE `login`='" . $login . "'";

//switch selector arranged by id (default)
    if (($alterconf['SWITCHPORT_IN_PROFILE'] == 1) OR ( $alterconf['SWITCHPORT_IN_PROFILE'] == 4)) {
        $allswitches = zb_SwitchesGetAll();
    }

//switch selector arranged by location
    if ($alterconf['SWITCHPORT_IN_PROFILE'] == 2) {
        $allswitches_q = "SELECT * FROM `switches` ORDER BY `location` ASC";
        $allswitches = simple_queryall($allswitches_q);
    }

//switch selector arranged by ip
    if ($alterconf['SWITCHPORT_IN_PROFILE'] == 3) {
        $allswitches_q = "SELECT * FROM `switches` ORDER BY `ip` ASC";
        $allswitches = simple_queryall($allswitches_q);
    }


    $switcharr = array();
    $switcharrFull = array();
    $switchswpoll = array();
    $switchgeo = array();
    $cutLocation = true;
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            if ($cutLocation) {
                if (mb_strlen($eachswitch['location']) > 32) {
                    $switcharr[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . mb_substr($eachswitch['location'], 0, 32, 'utf-8') . '...';
                } else {
                    $switcharr[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . $eachswitch['location'];
                }
            } else {
                $switcharr[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . $eachswitch['location'];
            }
            $switcharrFull[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . $eachswitch['location'];
            if (ispos($eachswitch['desc'], 'SWPOLL')) {
                $switchswpoll[$eachswitch['id']] = $eachswitch['ip'];
            }

            if (!empty($eachswitch['geo'])) {
                $switchgeo[$eachswitch['id']] = $eachswitch['geo'];
            }
        }
    }
//getting current data
    $assignData = simple_query($query);
    $sameUsers = '';

    if (!empty($assignData)) {
        $currentSwitchPort = $assignData['port'];
        $currentSwitchId = $assignData['switchid'];
    } else {
        $currentSwitchPort = '';
        $currentSwitchId = '';
    }
//checks other users with same switch->port 
    if ((!empty($currentSwitchId)) AND ( !empty($currentSwitchPort))) {
        $queryCheck = "SELECT `login` from `switchportassign` WHERE `port`='" . vf($currentSwitchPort) . "' AND `switchid`='" . vf($currentSwitchId, 3) . "';";
        $checkSame = simple_queryall($queryCheck);
        if (!empty($checkSame)) {
            foreach ($checkSame as $ix => $eachsame) {
                if ($eachsame['login'] != $login) {
                    $sameUsers .= ' ' . wf_Link("?module=userprofile&username=" . $eachsame['login'], web_profile_icon() . ' ' . $eachsame['login'], false, '');
                }
            }
        }
    }

//control form construct
    $formStyle = 'glamour';
    $inputs = wf_HiddenInput('swassignlogin', $login);
    if ($alterconf['SWITCHPORT_IN_PROFILE'] != 4) {
        $inputs .= wf_Selector('swassignswid', $switcharr, __('Switch'), $currentSwitchId, true);
    } else {
        $inputs .= wf_JuiComboBox('swassignswid', $switcharr, __('Switch'), $currentSwitchId, true);
        $formStyle = 'floatpanelswide';
    }
    $inputs .= wf_TextInput('swassignswport', __('Port'), $currentSwitchPort, false, 2, 'digits');
    $inputs .= wf_CheckInput('swassigndelete', __('Delete'), true, false);
    $inputs .= wf_Submit('Save');
    $controlForm = wf_Form('', "POST", $inputs, $formStyle);
//form end

    $switchAssignController = wf_modal(web_edit_icon(), __('Switch port assign'), $controlForm, '', '450', '220');

//switch location and polling controls
    $switchLocators = '';

    if (!empty($currentSwitchId)) {
        $switchProfileIcon = wf_img_sized('skins/menuicons/switches.png', __('Switch'), 10, 10);
        $switchLocators .= wf_Link('?module=switches&edit=' . $currentSwitchId, $switchProfileIcon, false, '');
    }

    if (isset($switchswpoll[$currentSwitchId])) {
        $snmpSwitchLocatorIcon = wf_img_sized('skins/snmp.png', __('SNMP query'), 10, 10);
        $switchLocators .= wf_Link('?module=switchpoller&switchid=' . $currentSwitchId, $snmpSwitchLocatorIcon, false, '');
    }

    if (isset($switchgeo[$currentSwitchId])) {
        $geoSwitchLocatorIcon = wf_img_sized('skins/icon_search_small.gif', __('Find on map'), 10, 10);
        $switchLocators .= wf_Link('?module=switchmap&finddevice=' . $switchgeo[$currentSwitchId], $geoSwitchLocatorIcon, false, '');
    }

    $cells = wf_TableCell(__('Switch'), '30%', 'row2');
    $cells .= wf_TableCell(@$switcharrFull[$currentSwitchId] . ' ' . $switchLocators);
    $rows = wf_TableRow($cells, 'row3');
    $cells = wf_TableCell(__('Port'), '30%', 'row2');
    $cells .= wf_TableCell($currentSwitchPort);
    $rows .= wf_TableRow($cells, 'row3');
    $cells = wf_TableCell(__('Change'), '30%', 'row2');
    $cells .= wf_TableCell($switchAssignController . ' ' . $sameUsers);
    $rows .= wf_TableRow($cells, 'row3');

    $result = wf_TableBody($rows, '100%', '0');

//update subroutine
    if (wf_CheckPost(array('swassignlogin', 'swassignswid', 'swassignswport'))) {
        $newswid = vf($_POST['swassignswid'], 3);
        $newport = vf($_POST['swassignswport'], 3);
        if (zb_SwitchPortAssignCheck($newswid, $newport)) {
            nr_query("DELETE from `switchportassign` WHERE `login`='" . $_POST['swassignlogin'] . "'");
            nr_query("INSERT INTO `switchportassign` (`id` ,`login` ,`switchid` ,`port`) VALUES (NULL , '" . $_POST['swassignlogin'] . "', '" . $newswid . "', '" . $newport . "');");
            log_register("SWITCHPORT CHANGE (" . $login . ") ON SWITCHID [" . $newswid . "] PORT [" . $newport . "]");
            rcms_redirect("?module=userprofile&username=" . $login);
        } else {
            log_register("SWITCHPORT FAIL (" . $login . ") ON SWITCHID [" . $newswid . "] PORT [" . $newport . "]");
            show_error(__('Port already assigned for another user'));
        }
    }
//delete subroutine
    if (isset($_POST['swassigndelete'])) {
        nr_query("DELETE from `switchportassign` WHERE `login`='" . $_POST['swassignlogin'] . "'");
        log_register("SWITCHPORT DELETE (" . $login . ")");
        rcms_redirect("?module=userprofile&username=" . $login);
    }
    return ($result);
}

/**
 * Checks is switch-port pair unique or not
 * 
 * @param int $switchId
 * @param int $port
 * 
 * @return bool
 */
function zb_SwitchPortAssignCheck($switchId, $port) {
    $result = true;
    if ((!empty($switchId)) AND ( !empty($port))) {
        $query = "SELECT `login` from `switchportassign` WHERE `switchid`='" . $switchId . "' AND `port`='" . $port . "';";
        $tmp = simple_query($query);
        if (!empty($tmp)) {
            $result = false;
        }
    }
    return($result);
}

/**
 * Returns all dates of admin actions (deprecated?)
 * 
 * @return array
 */
function zb_EventGetAllDateTimes() {
    $query = "SELECT `admin`,`date` from `weblogs`";
    $result = array();
    $allevents = simple_queryall($query);
    if (!empty($allevents)) {
        foreach ($allevents as $io => $eachevent) {
            $result[$eachevent['date']] = $eachevent['admin'];
        }
    }
    return ($result);
}

/**
 * Returns payment date editing form
 * 
 * @param array $paymentData
 * 
 * @return string
 */
function web_PaymentEditForm($paymentData) {
    $result = '';
    if (!empty($paymentData)) {
        $paymentTimestamp = strtotime($paymentData['date']);
        $paymentDate = date("Y-m-d", $paymentTimestamp);
        $paymentDataBase = serialize($paymentData);
        $paymentDataBase = base64_encode($paymentDataBase);

        $inputs = '<!--ugly hack to prevent datepicker autoopen -->';
        $inputs .= wf_tag('input', false, '', 'type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"');
        $inputs .= wf_HiddenInput('editpaymentid', $paymentData['id']);
        $inputs .= wf_HiddenInput('paymentdata', $paymentDataBase);

        $cells = wf_TableCell(__('New date'), '', 'row2');
        $cells .= wf_TableCell(wf_DatePickerPreset('newpaymentdate', $paymentDate), '', 'row3');
        $rows = wf_TableRow($cells);

        if ($paymentData['admin'] != 'external' AND $paymentData['admin'] != 'openpayz' AND $paymentData['admin'] != 'guest') {
            $cells = wf_TableCell(__('Payment type'), '', 'row2');
            $cells .= wf_TableCell(web_CashTypeSelector($paymentData['cashtypeid']), '', 'row3');
            $rows .= wf_TableRow($cells);
            $cells = wf_TableCell(__('Payment notes'), '', 'row2');
            $cells .= wf_TableCell(wf_TextInput('paymentnote', '', $paymentData['note'], false, 40), '', 'row3');
            $rows .= wf_TableRow($cells);
        } else {
            $inputs .= wf_HiddenInput('cashtype', $paymentData['cashtypeid']);
            $inputs .= wf_HiddenInput('paymentnote', $paymentData['note']);
        }

        $table = wf_TableBody($rows, '100%', 0, '');
        $table .= wf_Submit(__('Save'));

        $form = $inputs;
        $form .= wf_Form('', 'POST', $table, '');
        $form .= wf_delimiter();

        $result = wf_Form('', 'POST', $form, 'glamour');
    }
    return ($result);
}

/**
 * Returns list of previous user payments
 * 
 * @param string $login
 * @return string
 */
function web_PaymentsByUser($login) {
    global $ubillingConfig;
    $allpayments = zb_CashGetUserPayments($login);
    $alter_conf = $ubillingConfig->getAlter();
    $alltypes = zb_CashGetAllCashTypes();
    $allservicenames = zb_VservicesGetAllNamesLabeled();
    $total_payments = "0";
    $curdate = curdate();
    $deletingAdmins = array();
    $editingAdmins = array();
    $iCanDeletePayments = false;
    $iCanEditPayments = false;
    $currentAdminLogin = whoami();
    $idencEnabled = (@$alter_conf['IDENC_ENABLED']) ? true : false;

//extract admin logins with payments delete rights
    if (!empty($alter_conf['CAN_DELETE_PAYMENTS'])) {
        $deletingAdmins = explode(',', $alter_conf['CAN_DELETE_PAYMENTS']);
        $deletingAdmins = array_flip($deletingAdmins);
    }

//extract admin logins with date edit rights
    if (!empty($alter_conf['CAN_EDIT_PAYMENTS'])) {
        $editingAdmins = explode(',', $alter_conf['CAN_EDIT_PAYMENTS']);
        $editingAdmins = array_flip($editingAdmins);
    }

//setting editing/deleting flags
    $iCanDeletePayments = (isset($deletingAdmins[$currentAdminLogin])) ? true : false;
    $iCanEditPayments = (isset($editingAdmins[$currentAdminLogin])) ? true : false;


    $cells = wf_TableCell(__('ID'));
    if ($idencEnabled) {
        $cells .= wf_TableCell(__('IDENC'));
    }
    $cells .= wf_TableCell(__('Date'));
    $cells .= wf_TableCell(__('Payment'));
    $cells .= wf_TableCell(__('Balance before'));
    $cells .= wf_TableCell(__('Cash type'));
    $cells .= wf_TableCell(__('Payment note'));
    $cells .= wf_TableCell(__('Admin'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allpayments)) {
        foreach ($allpayments as $eachpayment) {
//hightlight of today payments
            if ($alter_conf['HIGHLIGHT_TODAY_PAYMENTS']) {
                if (ispos($eachpayment['date'], $curdate)) {
                    $hlight = 'paytoday';
                } else {
                    $hlight = 'row3';
                }
            } else {
                $hlight = 'row3';
            }

            if (!empty($alter_conf['DOCX_SUPPORT']) && !empty($alter_conf['DOCX_CHECK'])) {
                $printcheck = wf_Link('?module=printcheck&paymentid=' . $eachpayment['id'], wf_img('skins/printer_small.gif', __('Print')), false);
            } else {
                $printcheck = wf_tag('a', false, '', 'href="#" onClick="window.open(\'?module=printcheck&paymentid=' . $eachpayment['id'] . '\',\'checkwindow\',\'width=800,height=600\')"');
                $printcheck .= wf_img('skins/printer_small.gif', __('Print'));
                $printcheck .= wf_tag('a', true);
            }

//payments deleting controls
            if ($iCanDeletePayments) {
                $deleteControls = wf_JSAlert('?module=addcash&username=' . $login . '&paymentdelete=' . $eachpayment['id'], wf_img('skins/delete_small.png', __('Delete')), __('Removing this may lead to irreparable results')) . ' &nbsp; ';
            } else {
                $deleteControls = '';
            }

//payments editing form
            if ($iCanEditPayments) {
                $editControls = wf_modalAuto(wf_img_sized('skins/icon_edit.gif', __('Edit'), '10'), __('Edit'), web_PaymentEditForm($eachpayment), '') . ' &nbsp; ';
            } else {
                $editControls = '';
            }

            if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
                $eachpayment['note'] = zb_TranslatePaymentNote($eachpayment['note'], $allservicenames);
            }

            $cells = wf_TableCell($eachpayment['id']);
            if ($idencEnabled) {
                $cells .= wf_TableCell(zb_NumEncode($eachpayment['id']));
            }
            $cells .= wf_TableCell($eachpayment['date']);
            $cells .= wf_TableCell($eachpayment['summ']);
            $cells .= wf_TableCell($eachpayment['balance']);
            $cells .= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
            $cells .= wf_TableCell($eachpayment['note']);
            $cells .= wf_TableCell($eachpayment['admin']);
            $cells .= wf_TableCell($deleteControls . $editControls . $printcheck);
            $rows .= wf_TableRow($cells, $hlight);

            $total_payments = $total_payments + $eachpayment['summ'];
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result .= __('Total payments') . ': ' . wf_tag('b') . abs($total_payments) . wf_tag('b') . wf_tag('br');

    return($result);
}

/**
 * Returns actions performed on user parsed from log
 * 
 * @param string $login
 * @param bool   $strict
 * @return string
 */
function web_GrepLogByUser($login, $strict = false) {
    $login = ($strict) ? '(' . $login . ')' : $login;
    @$employeeNames = unserialize(ts_GetAllEmployeeLoginsCached());
    $query = 'SELECT * from `weblogs` WHERE `event` LIKE "%' . $login . '%" ORDER BY `date` DESC';
    $allevents = simple_queryall($query);
    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Who?'));
    $cells .= wf_TableCell(__('When?'));
    $cells .= wf_TableCell(__('What happen?'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allevents)) {
        foreach ($allevents as $io => $eachevent) {
            $adminName = (isset($employeeNames[$eachevent['admin']])) ? $employeeNames[$eachevent['admin']] : $eachevent['admin'];
            $idLabel = wf_tag('abbr', false, '', 'title="' . $eachevent['ip'] . '"') . $eachevent['id'] . wf_tag('abbr', true);
            $cells = wf_TableCell($idLabel);

            $cells .= wf_TableCell($adminName);
            $cells .= wf_TableCell($eachevent['date']);
            $cells .= wf_TableCell($eachevent['event']);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }
    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    return($result);
}

/**
 * Cash types one sting data editor
 * 
 * @param string $fieldname
 * @param string $fieldkey
 * @param string $formurl
 * @param array $olddata
 * @return string
 */
function web_EditorTableDataFormOneField($fieldname, $fieldkey, $formurl, $olddata) {
    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__($fieldname));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($olddata)) {
        foreach ($olddata as $io => $value) {
            $cells = wf_TableCell($value['id']);
            $cells .= wf_TableCell($value[$fieldkey]);
            $actLinks = wf_JSAlert($formurl . '&action=delete&id=' . $value['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
            $actLinks .= wf_Link($formurl . '&action=edit&id=' . $value['id'], web_edit_icon(), false);
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    $table = wf_TableBody($rows, '100%', 0, 'sortable');

    $inputs = wf_TextInput('new' . $fieldkey, __($fieldname), '', false);
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return($table . $form);
}

/**
 * Retuns year selector. Is here, only for backward compatibility with old modules.
 * use only wf_YearSelector() in new code.
 * 
 * @return string
 */
function web_year_selector() {
    $selector = wf_YearSelector('yearsel');
    return($selector);
}

/**
 * Shows list for available traffic classes
 * 
 * @return void
 */
function web_DirectionsShow() {
    $allrules = zb_DirectionsGetAll();

    $cells = wf_TableCell(__('Rule number'));
    $cells .= wf_TableCell(__('Rule name'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allrules)) {
        foreach ($allrules as $io => $eachrule) {
            $cells = wf_TableCell($eachrule['rulenumber']);
            $cells .= wf_TableCell($eachrule['rulename']);
            $actLinks = wf_JSAlert('?module=rules&delete=' . $eachrule['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
            $actLinks .= wf_JSAlert("?module=rules&edit=" . $eachrule['id'], web_edit_icon(), 'Are you serious');
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }


    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    show_window(__('Traffic classes'), $result);
}

/**
 * Shows traffic class adding form
 * 
 * @return void
 */
function web_DirectionAddForm() {
    $allrules = zb_DirectionsGetAll();
    $availrules = array();
    $selectArr = array();
    if (!empty($allrules)) {
        foreach ($allrules as $io => $eachrule) {
            $availrules[$eachrule['rulenumber']] = $eachrule['rulename'];
        }
    }

    for ($i = 0; $i <= 9; $i++) {
        if (!isset($availrules[$i])) {
            $selectArr[$i] = $i;
        }
    }


    $inputs = wf_Selector('newrulenumber', $selectArr, __('Direction number'), '', true);
    $inputs .= wf_TextInput('newrulename', __('Direction name'), '', true);
    $inputs .= wf_Submit(__('Create'));


    $form = wf_Form('', 'POST', $inputs, 'glamour');

    show_window(__('Add new traffic class'), $form);
}

/**
 * Shows traffic class edit form
 * 
 * @param int $ruleid
 * 
 * @return void
 */
function web_DirectionsEditForm($ruleid) {
    $ruleid = vf($ruleid, 3);
    $query = "SELECT * from `directions` WHERE `id`='" . $ruleid . "'";
    $ruledata = simple_query($query);

    $editinputs = wf_TextInput('editrulename', 'Rule name', $ruledata['rulename'], true, '20');
    $editinputs .= wf_Submit('Save');
    $editform = wf_Form("", 'POST', $editinputs, 'glamour');
    $editform .= wf_BackLink('?module=rules');
    show_window(__('Edit') . ' ' . __('Rule name'), $editform);
}

/**
 * Renders some content with title in floating containers. 
 * 
 * @param string $title
 * @param string $content
 * 
 * @return string
 */
function web_FinRepControls($title = '', $content) {
    $result = '';
    $style = 'style="float:left; display:block; height:90px; margin-right: 20px;"';
    $result .= wf_tag('div', false, '', $style);
    $result .= wf_tag('h3') . $title . wf_tag('h3', true);
    $result .= wf_tag('br');
    $result .= $content;
    $result .= wf_tag('div', true);
    return($result);
}

/**
 * Renders payments extracted from database with some query
 * 
 * @param string $query
 * @return string
 */
function web_PaymentsShow($query) {
    global $ubillingConfig;
    $alter_conf = $ubillingConfig->getAlter();
    $alladrs = zb_AddressGetFulladdresslistCached();
    $allrealnames = zb_UserGetAllRealnames();
    $alltypes = zb_CashGetAllCashTypes();
    $allapayments = simple_queryall($query);
    $allservicenames = zb_VservicesGetAllNamesLabeled();
    $idencEnabled = (@$alter_conf['IDENC_ENABLED']) ? true : false;
//getting full contract list
    if ($alter_conf['FINREP_CONTRACT']) {
        $allcontracts = zb_UserGetAllContracts();
        $allcontracts = array_flip($allcontracts);
    }

//getting all users tariffs
    if ($alter_conf['FINREP_TARIFF']) {
        $alltariffs = zb_TariffsGetAllUsers();
    }

    $total = 0;
    $totalPaycount = 0;

    $cells = wf_TableCell(__('ID'));
    if ($idencEnabled) {
        $cells .= wf_TableCell(__('IDENC'));
    }
    $cells .= wf_TableCell(__('Date'));
    $cells .= wf_TableCell(__('Cash'));
//optional contract display
    if ($alter_conf['FINREP_CONTRACT']) {
        $cells .= wf_TableCell(__('Contract'));
    }
    $cells .= wf_TableCell(__('Login'));
    $cells .= wf_TableCell(__('Full address'));
    $cells .= wf_TableCell(__('Real Name'));
//optional tariff display
    if ($alter_conf['FINREP_TARIFF']) {
        $cells .= wf_TableCell(__('Tariff'));
    }
    $cells .= wf_TableCell(__('Cash type'));
    $cells .= wf_TableCell(__('Notes'));
    $cells .= wf_TableCell(__('Admin'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allapayments)) {
        foreach ($allapayments as $io => $eachpayment) {

            if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
                $eachpayment['note'] = zb_TranslatePaymentNote($eachpayment['note'], $allservicenames);
            }

            $cells = wf_TableCell($eachpayment['id']);
            if ($idencEnabled) {
                $cells .= wf_TableCell(zb_NumEncode($eachpayment['id']));
            }
            $cells .= wf_TableCell($eachpayment['date']);
            $cells .= wf_TableCell($eachpayment['summ']);
//optional contract display
            if ($alter_conf['FINREP_CONTRACT']) {
                $cells .= wf_TableCell(@$allcontracts[$eachpayment['login']]);
            }
            $cells .= wf_TableCell(wf_Link('?module=userprofile&username=' . $eachpayment['login'], (web_profile_icon() . ' ' . $eachpayment['login']), false, ''));
            $cells .= wf_TableCell(@$alladrs[$eachpayment['login']]);
            $cells .= wf_TableCell(@$allrealnames[$eachpayment['login']]);
//optional tariff display
            if ($alter_conf['FINREP_TARIFF']) {
                $cells .= wf_TableCell(@$alltariffs[$eachpayment['login']]);
            }
            $cells .= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
            $cells .= wf_TableCell($eachpayment['note']);
            $cells .= wf_TableCell($eachpayment['admin']);
            $rows .= wf_TableRow($cells, 'row3');

            if ($eachpayment['summ'] > 0) {
                $total = $total + $eachpayment['summ'];
                $totalPaycount++;
            }
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result .= wf_tag('strong') . __('Cash') . ': ' . $total . wf_tag('strong', true) . wf_tag('br');
    $result .= wf_tag('strong') . __('Count') . ': ' . $totalPaycount . wf_tag('strong', true);
    return($result);
}

/**
 * Returns visual bar with count/total proportional size
 * 
 * @param float $count
 * @param float $total
 * @return string
 */
function web_bar($count, $total) {
    $barurl = 'skins/bar.png';
    if ($total != 0) {
        $width = ($count / $total) * 100;
    } else {
        $width = 0;
    }

    $code = wf_img_sized($barurl, '', $width . '%', '14');
    return($code);
}

/**
 * Returns all months with names in two digit notation
 * 
 * @param string $number
 * @return array/string
 */
function months_array($number = null) {
    $months = array(
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December'
    );
    if (empty($number)) {
        return $months;
    } else {
        return $months[$number];
    }
}

/**
 * Returns localized array of days of week as dayNumber=>dayName
 * 
 * @param bool $any add any day of week as 1488 number of day
 * 
 * @return array
 */
function daysOfWeek($any = false) {
    $result = array();
    if ($any) {
        $result[1488] = __('Any');
    }
    $result[1] = rcms_date_localise('Monday');
    $result[2] = rcms_date_localise('Tuesday');
    $result[3] = rcms_date_localise('Wednesday');
    $result[4] = rcms_date_localise('Thursday');
    $result[5] = rcms_date_localise('Friday');
    $result[6] = rcms_date_localise('Saturday');
    $result[7] = rcms_date_localise('Sunday');
    return($result);
}

/**
 * Retuns all months with names without begin zeros
 * 
 * @return array
 */
function months_array_wz() {
    $months = array(
        '1' => 'January',
        '2' => 'February',
        '3' => 'March',
        '4' => 'April',
        '5' => 'May',
        '6' => 'June',
        '7' => 'July',
        '8' => 'August',
        '9' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December');
    return($months);
}

/**
 * Returns all months with names in two digit notation
 * 
 * @return array
 */
function months_array_localized() {
    $months = months_array();
    $result = array();
    if (!empty($months)) {
        foreach ($months as $io => $each) {
            $result[$io] = rcms_date_localise($each);
        }
    }
    return ($result);
}

/**
 * Shows payments year graph with caching
 * 
 * @param int $year
 */
function web_PaymentsShowGraph($year) {
    global $ubillingConfig;
    $months = months_array();
    $year_summ = zb_PaymentsGetYearSumm($year);
    $curtime = time();
    $yearPayData = array();
    $yearStats = array();
    $cache = new UbillingCache();
    $cacheTime = 3600; //sec intervall to cache

    $cells = wf_TableCell('');
    $cells .= wf_TableCell(__('Month'));
    $cells .= wf_TableCell(__('Payments count'));
    $cells .= wf_TableCell(__('ARPU'));
    $cells .= wf_TableCell(__('Cash'));
    $cells .= wf_TableCell(__('Visual'), '50%');
    $rows = wf_TableRow($cells, 'row1');

//caching subroutine

    $renewTime = $cache->get('YPD_LAST', $cacheTime);
    if (empty($renewTime)) {
//first usage
        $renewTime = $curtime;
        $cache->set('YPD_LAST', $renewTime, $cacheTime);
        $updateCache = true;
    } else {
//cache time already set
        $timeShift = $curtime - $renewTime;
        if ($timeShift > $cacheTime) {
//cache update needed
            $updateCache = true;
        } else {
//load data from cache or init new cache
            $yearPayData_raw = $cache->get('YPD_CACHE', $cacheTime);
            if (empty($yearPayData_raw)) {
//first usage
                $emptyCache = array();
                $emptyCache = serialize($emptyCache);
                $emptyCache = base64_encode($emptyCache);
                $cache->set('YPD_CACHE', $emptyCache, $cacheTime);
                $updateCache = true;
            } else {
// data loaded from cache
                $yearPayData = base64_decode($yearPayData_raw);
                $yearPayData = unserialize($yearPayData);
                $updateCache = false;
//check is current year already cached?
                if (!isset($yearPayData[$year]['graphs'])) {
                    $updateCache = true;
                }

//check is manual cache refresh is needed?
                if (wf_CheckGet(array('forcecache'))) {
                    $updateCache = true;
                    rcms_redirect("?module=report_finance");
                }
            }
        }
    }

    if ($updateCache) {
        $dopWhere = '';
        if ($ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')) {
            $exIdArr = array_map('trim', explode(',', $ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')));
            $exIdArr = array_filter($exIdArr);
// Create and WHERE to query
            if (!empty($exIdArr)) {
                $dopWhere = ' AND ';
                $dopWhere .= ' `cashtypeid` != ' . implode(' AND `cashtypeid` != ', $exIdArr);
            }
        }
//extracting all of needed payments in one query
        if ($ubillingConfig->getAlterParam('REPORT_FINANCE_CONSIDER_NEGATIVE')) {
// ugly way to get payments with negative sums
// performance degradation is kinda twice
            $allYearPayments_q = "(SELECT * FROM `payments` 
                                        WHERE `date` LIKE '" . $year . "-%' AND `summ` < '0' 
                                            AND note NOT LIKE 'Service:%' 
                                            AND note NOT LIKE 'PENALTY%' 
                                            AND note NOT LIKE 'OMEGATV%' 
                                            AND note NOT LIKE 'MEGOGO%' 
                                            AND note NOT LIKE 'TRINITYTV%' " . $dopWhere . ") 
                                  UNION ALL 
                                  (SELECT * FROM `payments` WHERE `date` LIKE '" . $year . "-%' AND `summ` > '0' " . $dopWhere . ")";
        } else {
            $allYearPayments_q = "SELECT * FROM `payments` WHERE `date` LIKE '" . $year . "-%' AND `summ` > '0' " . $dopWhere;
        }

        $allYearPayments = simple_queryall($allYearPayments_q);
        if (!empty($allYearPayments)) {
            foreach ($allYearPayments as $idx => $eachYearPayment) {
//Here we can get up to 50% of CPU time on month extraction, but this hacks is to ugly :(
//Benchmark results: http://pastebin.com/i7kadpN7
                $statsMonth = date("m", strtotime($eachYearPayment['date']));
                if (isset($yearStats[$statsMonth])) {
                    $yearStats[$statsMonth]['count'] ++;
                    $yearStats[$statsMonth]['summ'] = $yearStats[$statsMonth]['summ'] + $eachYearPayment['summ'];
                } else {
                    $yearStats[$statsMonth]['count'] = 1;
                    $yearStats[$statsMonth]['summ'] = $eachYearPayment['summ'];
                }
            }
        }

        foreach ($months as $eachmonth => $monthname) {
            $month_summ = (isset($yearStats[$eachmonth])) ? $yearStats[$eachmonth]['summ'] : 0;
            $paycount = (isset($yearStats[$eachmonth])) ? $yearStats[$eachmonth]['count'] : 0;
            $monthArpu = @round($month_summ / $paycount, 2);
            if (is_nan($monthArpu)) {
                $monthArpu = 0;
            }
            $cells = wf_TableCell($eachmonth);
            $cells .= wf_TableCell(wf_Link('?module=report_finance&month=' . $year . '-' . $eachmonth, rcms_date_localise($monthname)));
            $cells .= wf_TableCell($paycount);
            $cells .= wf_TableCell($monthArpu);
            $cells .= wf_TableCell(zb_CashBigValueFormat($month_summ), '', '', 'align="right"');
            $cells .= wf_TableCell(web_bar($month_summ, $year_summ), '', '', 'sorttable_customkey="' . $month_summ . '"');
            $rows .= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        $yearPayData[$year]['graphs'] = $result;
//write to cache
        $cache->set('YPD_LAST', $curtime, $cacheTime);
        $newCache = serialize($yearPayData);
        $newCache = base64_encode($newCache);
        $cache->set('YPD_CACHE', $newCache, $cacheTime);
    } else {
//take data from cache
        if (isset($yearPayData[$year]['graphs'])) {
            $result = $yearPayData[$year]['graphs'];
            $result .= __('Cache state at time') . ': ' . date("Y-m-d H:i:s", ($renewTime)) . ' ';
            $result .= wf_Link("?module=report_finance&forcecache=true", wf_img('skins/icon_cleanup.png', __('Renew')), false, '');
        } else {
            $result = __('Strange exeption');
        }
    }


    show_window(__('Payments by') . ' ' . $year, $result);
}

/**
 * Returns editor for some array data.
 * 
 * @param array $titles
 * @param array $keys
 * @param array $alldata
 * @param string $module
 * @param bool $delete
 * @param bool $edit
 * @param string $prefix
 * @param string $extaction
 * @param string $extbutton
 * @return string
 */
function web_GridEditor($titles, $keys, $alldata, $module, $delete = true, $edit = false, $prefix = '', $extaction = '', $extbutton = '') {

//headers
    $cells = '';
    foreach ($titles as $eachtitle) {
        $cells .= wf_TableCell(__($eachtitle));
    }
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');
//headers end

    $cells = '';
    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachdata) {
            $cells = '';
            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $cells .= wf_TableCell($eachdata[$eachkey]);
                }
            }
            if ($delete) {
                $deletecontrol = wf_JSAlert('?module=' . $module . '&' . $prefix . 'delete=' . $eachdata['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            } else {
                $deletecontrol = '';
            }

            if ($edit) {
                $editcontrol = wf_Link('?module=' . $module . '&' . $prefix . 'edit=' . $eachdata['id'], web_edit_icon(), false);
            } else {
                $editcontrol = '';
            }

            if (!empty($extaction)) {
                $extencontrol = wf_Link('?module=' . $module . '&' . $prefix . $extaction . '=' . $eachdata['id'], $extbutton, false);
            } else {
                $extencontrol = '';
            }

            $cells .= wf_TableCell($deletecontrol . ' ' . $editcontrol . ' ' . $extencontrol);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }


    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    return($result);
}

/**
 * Returns NAS editing grid
 * 
 * @param array $titles
 * @param array $keys
 * @param array $alldata
 * @param string $module
 * @param bool $delete
 * @param bool $edit
 * @param string $prefix
 * @return string
 */
function web_GridEditorNas($titles, $keys, $alldata, $module, $delete = true, $edit = true, $prefix = '') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $messages = new UbillingMessageHelper();
// Получаем список сетей
    $networks = multinet_get_all_networks();
    $cidrs = array();
    if (!empty($networks)) {
        foreach ($networks as $network)
            $cidrs[$network['id']] = $network['desc'];
    }
// Заголовок таблицы
    $cells = '';
    foreach ($titles as $title)
        $cells .= wf_TableCell(__($title));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

// Содержимое таблицы
    if (!empty($alldata)) {
        foreach ($alldata as $data) {
            $cells = '';
            $actions = '';
            if ($delete) {
                $deleteUrl = '?module=' . $module . '&' . $prefix . 'delete=' . $data['id'];
                $cancelUrl = '?module=nas';
                $deleteDialogTitle = __('Delete') . ' ' . __('NAS') . ' ' . $data['nasip'] . '?';
                $actions .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $messages->getDeleteAlert(), '', $cancelUrl, $deleteDialogTitle);
            }

            if ($edit) {
                $actions .= wf_Link('?module=' . $module . '&' . $prefix . 'edit=' . $data['id'], web_edit_icon());
            }

            foreach ($keys as $key) {
                if (array_key_exists($key, $data)) {
                    switch ($key) {
                        case 'netid':
                            $cells .= wf_TableCell($data[$key] . ': ' . ( ( array_key_exists($data[$key], $cidrs) ) ? $cidrs[$data[$key]] : __('Network not found')));
                            break;
                        case 'nastype':
                            if ($data[$key] == 'mikrotik') {
                                if ($altCfg['MIKROTIK_SUPPORT']) {
                                    $actions .= wf_Link('?module=mikrotikextconf&nasid=' . $data['id'], web_icon_extended('MikroTik extended configuration'));
                                }
                            }
                            if ($data[$key] == 'radius') {
                                if ($altCfg['FREERADIUS_ENABLED']) {
                                    $actions .= wf_Link('?module=freeradius&nasid=' . $data['id'], web_icon_freeradius('Set RADIUS-attributes'));
                                }
                            }

                            if ($altCfg['MULTIGEN_ENABLED']) {
                                $actions .= wf_Link('?module=multigen&editnasoptions=' . $data['id'], web_icon_settings(__('Configure Multigen NAS')));
                            }
                            $cells .= wf_TableCell($data[$key]);
                            break;
                        default:
                            $cells .= wf_TableCell($data[$key]);
                            break;
                    }
                }
            }
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }
// Результат - таблица
    $result = wf_TableBody($rows, '100%', 0, 'sortable');
// Отображаем результат
    return $result;
}

/**
 * Returns virtual services editor grid
 * 
 * @param array $titles
 * @param array $keys
 * @param array $alldata
 * @param string $module
 * @param bool $delete
 * @param bool $edit
 * @return string
 */
function web_GridEditorVservices($titles, $keys, $alldata, $module, $delete = true, $edit = false) {
    $alltagnames = stg_get_alltagnames();
    $cells = '';
    foreach ($titles as $eachtitle) {

        $cells .= wf_TableCell(__($eachtitle));
    }

    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachdata) {
            $cells = '';

            foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    if ($eachkey == 'tagid') {
                        @$tagname = $alltagnames[$eachdata['tagid']];
                        $cells .= wf_TableCell($tagname);
                    } else {
                        if ($eachkey == 'fee_charge_always') {
                            $cells .= wf_TableCell(web_bool_led($eachdata[$eachkey]));
                        } else {
                            $cells .= wf_TableCell($eachdata[$eachkey]);
                        }
                    }
                }
            }
            if ($delete) {
                $deletecontrol = wf_JSAlert('?module=' . $module . '&delete=' . $eachdata['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            } else {
                $deletecontrol = '';
            }

            if ($edit) {
                $editcontrol = wf_JSAlert('?module=' . $module . '&edit=' . $eachdata['id'], web_edit_icon(), __('Are you serious'));
            } else {
                $editcontrol = '';
            }

            $cells .= wf_TableCell($deletecontrol . ' ' . $editcontrol);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }


    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    return($result);
}

/**
 * Retruns nas creation form
 * 
 * @return string
 */
function web_NasAddForm() {

    $nastypes = array(
        'local' => 'Local NAS',
        'rscriptd' => 'rscriptd',
        'mikrotik' => 'MikroTik',
        'radius' => 'Radius'
    );


    $inputs = multinet_network_selector() . wf_tag('label', false, '', 'for="networkselect"') . __('Network') . wf_tag('label', true) . wf_tag('br');
    $inputs .= wf_Selector('newnastype', $nastypes, __('NAS type'), '', true);
    $inputs .= wf_TextInput('newnasip', __('IP'), '', true, '15', 'ip');
    $inputs .= wf_TextInput('newnasname', __('NAS name'), '', true);
    $inputs .= wf_TextInput('newbandw', __('Graphs URL'), '', true);
    $inputs .= wf_Submit(__('Create'));

    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return($form);
}

/**
 * Dumps database to file and returns filename
 * 
 * @param bool   $silent
 * @return string
 */
function zb_backup_database($silent = false) {
    global $ubillingConfig;
    $alterConf = $ubillingConfig->getAlter();
    $mysqlConf = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');

    $backname = DATA_PATH . 'backups/sql/ubilling-' . date("Y-m-d_H_i_s", time()) . '.sql';
    $command = $alterConf['MYSQLDUMP_PATH'] . ' --host ' . $mysqlConf['server'] . ' -u ' . $mysqlConf['username'] . ' -p' . $mysqlConf['password'] . ' ' . $mysqlConf['db'] . ' > ' . $backname;
    shell_exec($command);

    if (!$silent) {
        show_success(__('Backup saved') . ': ' . $backname);
    }

    log_register("BACKUP CREATE `" . $backname . "`");
    return ($backname);
}

/**
 * Returns database backup creation form
 * 
 * @return string
 */
function web_BackupForm() {
    $backupinputs = __('This will create a backup copy of all tables in the database') . wf_tag('br');
    $backupinputs .= wf_HiddenInput('createbackup', 'true');
    $backupinputs .= wf_CheckInput('imready', 'I`m ready', true, false);
    $backupinputs .= wf_Submit('Create');
    $form = wf_Form('', 'POST', $backupinputs, 'glamour');

    return($form);
}

/**
 * Returns user apartment editing form
 * 
 * @param string $login
 * @return string
 */
function web_AddressAptForm($login) {
    global $ubillingConfig;
    $login = vf($login);
    $aptdata = zb_AddressGetAptData($login);
    $useraddress = zb_AddressGetFullCityaddresslist();
    @$useraddress = $useraddress[$login];

    $cells = wf_TableCell(__('Value'));
    $cells .= wf_TableCell(__('Current state'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    $cells = wf_TableCell(__('Login'));
    $cells .= wf_TableCell($login);
    $cells .= wf_TableCell('');
    $rows .= wf_TableRow($cells, 'row3');

    $cells = wf_TableCell(__('Full address'));
    $cells .= wf_TableCell(@$useraddress);
    $orphanUrl = '?module=binder&username=' . $login . '&orphan=true';
    $cancelUrl = '?module=binder&username=' . $login;
    $orphanAlert = __('Are you sure you want to make the homeless this user') . '?';
    $addressDeleteDialog = wf_ConfirmDialogJS($orphanUrl, web_delete_icon() . ' ' . __('Evict'), $orphanAlert, '', $cancelUrl);
    $cells .= wf_TableCell($addressDeleteDialog);
    $rows .= wf_TableRow($cells, 'row3');

    $cells = wf_TableCell(__('Entrance'));
    $cells .= wf_TableCell(@$aptdata['entrance']);
    $cells .= wf_TableCell(wf_TextInput('changeentrance', '', @$aptdata['entrance'], false));
    $rows .= wf_TableRow($cells, 'row3');

    $cells = wf_TableCell(__('Floor'));
    $cells .= wf_TableCell(@$aptdata['floor']);
    $cells .= wf_TableCell(wf_TextInput('changefloor', '', @$aptdata['floor'], false));
    $rows .= wf_TableRow($cells, 'row3');

    $cells = wf_TableCell(__('Apartment') . wf_tag('sup') . '*' . wf_tag('sup', true));
    $cells .= wf_TableCell(@$aptdata['apt']);
    $cells .= wf_TableCell(wf_TextInput('changeapt', '', @$aptdata['apt'], false));
    $rows .= wf_TableRow($cells, 'row3');

    if ($ubillingConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED')) {
        $extenAddrData = zb_AddressExtenGetLoginFast($login);
        $postCode = (empty($extenAddrData['postal_code'])) ? '' : $extenAddrData['postal_code'];
        $extenTown = (empty($extenAddrData['town_district'])) ? '' : $extenAddrData['town_district'];
        $extenAddr = (empty($extenAddrData['address_exten'])) ? '' : $extenAddrData['address_exten'];

// empty row divider
        $cells = wf_TableCell(wf_nbsp());
        $cells .= wf_TableCell(wf_nbsp());
        $cells .= wf_TableCell(wf_HiddenInput('change_extended_address', 'true'));
        $rows .= wf_TableRow($cells, 'row2');

// postal code
        $cells = wf_TableCell(__('Postal code'));
        $cells .= wf_TableCell($postCode);
        $cells .= wf_TableCell(wf_TextInput('changepostcode', '', $postCode, false, '10'));
        $rows .= wf_TableRow($cells, 'row3');

// town/district/region
        $cells = wf_TableCell(__('Town/District/Region'));
        $cells .= wf_TableCell($extenTown);
        $cells .= wf_TableCell(wf_TextInput('changetowndistr', '', $extenTown, false, '47'));
        $rows .= wf_TableRow($cells, 'row3');

// extended address info
        $cells = wf_TableCell(__('Extended address info'));
        $cells .= wf_TableCell($extenAddr);
        $cells .= wf_TableCell(wf_TextArea('changeaddrexten', '', $extenAddr, false, '48x4'));
        $rows .= wf_TableRow($cells, 'row3');
    }

    $table = wf_TableBody($rows, '100%', 0, '');
    $table .= wf_Submit(__('Save'));

    $form = wf_Form("", 'POST', $table, '');
    $form .= web_AddressBuildShowAptsCheck($aptdata['buildid'], $aptdata['apt'], $login);

    return($form);
}

/**
 * Returns user occupancy form
 * 
 * @return string
 */
function web_AddressOccupancyForm() {
    $inputs = '';
    $rows = '';

    if (!isset($_POST['citysel'])) {
        $inputs = '';
        $inputs = wf_TableCell(web_CitySelectorAc());
        $inputs .= wf_TableCell(__('City'), '50%');
        $rows .= wf_TableRow($inputs, 'row3');
    } else {
        $cityname = zb_AddressGetCityData($_POST['citysel']);
        $cityname = $cityname['cityname'];

        $inputs = wf_HiddenInput('citysel', $_POST['citysel']);
        $inputs .= wf_TableCell($cityname, '50%');
        $inputs .= wf_TableCell(web_ok_icon() . ' ' . __('City'));
        $rows .= wf_TableRow($inputs, 'row3');

        if (!isset($_POST['streetsel'])) {
            $inputs = wf_TableCell(web_StreetSelectorAc($_POST['citysel']));
            $inputs .= wf_TableCell(__('Street'));

            $rows .= wf_TableRow($inputs, 'row3');
        } else {
            $streetname = zb_AddressGetStreetData($_POST['streetsel']);
            $streetname = $streetname['streetname'];

            $inputs = wf_HiddenInput('streetsel', $_POST['streetsel']);
            $inputs .= wf_TableCell($streetname);
            $inputs .= wf_TableCell(web_ok_icon() . ' ' . __('Street'));


            $rows .= wf_TableRow($inputs, 'row3');

            if (!isset($_POST['buildsel'])) {
                $inputs = wf_TableCell(web_BuildSelectorAc($_POST['streetsel']));

                $inputs .= wf_TableCell(__('Build'));
                $rows .= wf_TableRow($inputs, 'row3');
            } else {
                $buildnum = zb_AddressGetBuildData($_POST['buildsel']);
                $buildnum = $buildnum['buildnum'];

                $inputs = wf_HiddenInput('buildsel', $_POST['buildsel']);
                $inputs .= wf_TableCell($buildnum);
                $inputs .= wf_TableCell(web_ok_icon() . ' ' . __('Build'));
                $rows .= wf_TableRow($inputs, 'row3');

                $inputs = wf_TableCell(web_AptCreateForm());
                $inputs .= wf_TableCell(__('Apartment'));
                $rows .= wf_TableRow($inputs, 'row3');

                $inputs = wf_TableCell(wf_Submit(__('Create')));
                $inputs .= wf_TableCell('');
                $rows .= wf_TableRow($inputs, 'row3');
            }
        }
    }

    $form = wf_Form('', 'POST', $rows, '');

    $form = wf_TableBody($form, '100%', 0, 'glamour');
    $form .= wf_CleanDiv();

    return($form);
}

/**
 * Generates actual bandwidthd charts links dependent on some options
 * 
 * @global object $ubillingConfig
 * 
 * @param string $url
 * 
 * @return string
 */
function zb_BandwidthdImgLink($url) {
    global $ubillingConfig;
    $result = '';
    $bandwidthdProxy = $ubillingConfig->getAlterParam('BANDWIDTHD_PROXY');
    if ($bandwidthdProxy) {
        $result = '?module=traffstats&loadimg=' . base64_encode($url);
    } else {
        $result = $url;
    }

    return($result);
}

/**
 * Generates user's traffic statistic module content
 * 
 * @param   str     $login  User's login, for whitch generate module content
 * @return  str             Module content
 */
function web_UserTraffStats($login) {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $ishimuraOption = MultiGen::OPTION_ISHIMURA;
    $ishimuraTable = MultiGen::NAS_ISHIMURA;
    $login = vf($login);
    $dirs = zb_DirectionsGetAll();

// Current month traffic stats:
    $cells = wf_TableCell(__('Traffic classes'));
    $cells .= wf_TableCell(__('Downloaded'));
    $cells .= wf_TableCell(__('Uploaded'));
    $cells .= wf_TableCell(__('Total'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($dirs)) {
        foreach ($dirs as $dir) {
            $query_downup = "SELECT `D" . $dir['rulenumber'] . "`,`U" . $dir['rulenumber'] . "` FROM `users` WHERE `login` = '" . $login . "'";
            $downup = simple_query($query_downup);
//yeah, no classes at all
            if ($dir['rulenumber'] == 0) {
                if ($altCfg[$ishimuraOption]) {
                    $query_hideki = "SELECT `D0`,`U0` from `" . $ishimuraTable . "` WHERE `login`='" . $login . "' AND `month`='" . date("n") . "' AND `year`='" . curyear() . "'";
                    $dataHideki = simple_query($query_hideki);
                    if (isset($downup['D0'])) {
                        @$downup['D0'] += $dataHideki['D0'];
                        @$downup['U0'] += $dataHideki['U0'];
                    } else {
                        $downup['D0'] = $dataHideki['D0'];
                        $downup['U0'] = $dataHideki['U0'];
                    }
                }
            }

            $cells = wf_TableCell($dir['rulename']);
            $cells .= wf_TableCell(stg_convert_size($downup['D' . $dir['rulenumber']]), '', '', 'sorttable_customkey="' . $downup['D' . $dir['rulenumber']] . '"');
            $cells .= wf_TableCell(stg_convert_size($downup['U' . $dir['rulenumber']]), '', '', 'sorttable_customkey="' . $downup['U' . $dir['rulenumber']] . '"');
            $cells .= wf_TableCell(stg_convert_size(($downup['U' . $dir['rulenumber']] + $downup['D' . $dir['rulenumber']])), '', '', 'sorttable_customkey="' . ($downup['U' . $dir['rulenumber']] + $downup['D' . $dir['rulenumber']]) . '"');
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_tag('h3') . __('Current month traffic stats') . wf_tag('h3', true);
    $result .= wf_TableBody($rows, '100%', '0', 'sortable');
// End of current month traffic stats
// Per-user graphs buttons:
    $ip = zb_UserGetIP($login);
    $bandwidthd = zb_BandwidthdGetUrl($ip);

    if (!empty($bandwidthd)) {
        $bwd = zb_BandwidthdGenLinks($ip);

// Dayly graph button:
        $daybw = wf_img(zb_BandwidthdImgLink($bwd['dayr']), __('Downloaded'));
        if (!empty($bwd['days'])) {
            $daybw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['days']), __('Uploaded'));
        }

// Weekly graph button:
        $weekbw = wf_img(zb_BandwidthdImgLink($bwd['weekr']), __('Downloaded'));
        if (!empty($bwd['weeks'])) {
            $weekbw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['weeks']), __('Uploaded'));
        }

// Monthly graph button:
        $monthbw = wf_img(zb_BandwidthdImgLink($bwd['monthr']), __('Downloaded'));
        if (!empty($bwd['months'])) {
            $monthbw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['months']), __('Uploaded'));
        }

// Yearly graph button:
        $yearbw = wf_img(zb_BandwidthdImgLink($bwd['yearr']), __('Downloaded'));
        if (!empty($bwd['years'])) {
            $yearbw .= wf_delimiter() . wf_img(zb_BandwidthdImgLink($bwd['years']), __('Uploaded'));
        }

// Modal window sizes:
        if (!empty($bwd['days'])) {
//bandwidthd
            $graphLegend = wf_tag('br') . wf_img('skins/bwdlegend.gif');
        } else {
//mikrotik
            $graphLegend = '';
        }

        $result .= wf_delimiter();
        $result .= wf_tag('h3') . __('Graphs') . wf_tag('h3', true);

        $bwcells = '';
        $zbxExtended = (isset($bwd['zbxexten']) and $bwd['zbxexten'] == true);

        if ($zbxExtended) {
            $fiveminsbw = wf_img($bwd['5mins'], __('Downloaded'));
            $zbxLink = $bwd['zbxlink'];

            $bwcells .= wf_TableCell(wf_link($zbxLink, wf_img_sized('skins/zabbix_ico.png', '', '16', '16') . ' ' . __('Go to graph on Zabbix server'), false, 'ubButton', 'target="__blank"'));
            $bwcells .= wf_TableCell(wf_modalAuto(wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Graph by 5 minutes'), __('Graph by 5 minutes'), $fiveminsbw, 'ubButton'));
        }

        $bwcells .= wf_TableCell(wf_modalAuto(wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Graph by day'), __('Graph by day'), $daybw . $graphLegend, 'ubButton'));
        $bwcells .= wf_TableCell(wf_modalAuto(wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Graph by week'), __('Graph by week'), $weekbw . $graphLegend, 'ubButton'));
        $bwcells .= wf_TableCell(wf_modalAuto(wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Graph by month'), __('Graph by month'), $monthbw . $graphLegend, 'ubButton'));
        $bwcells .= wf_TableCell(wf_modalAuto(wf_img_sized('skins/icon_stats.gif', '', '16', '16') . ' ' . __('Graph by year'), __('Graph by year'), $yearbw . $graphLegend, 'ubButton'));
        $bwrows = wf_TableRow($bwcells);

// Adding graphs buttons to result:
        $result .= wf_TableBody($bwrows, '', '0', '');
        $result .= wf_delimiter();
    } else {
// Commented to save useful space for normal data. TODO: take a decision about this notification in future.
//$messages = new UbillingMessageHelper();
//$result .= $messages->getStyledMessage(__('No user graphs because no NAS with bandwidthd for his network'), 'info');
    }
// End of per-user graphs buttons
// Traffic statistic by previous months:
    $monthNames = months_array_wz();
    $result .= wf_tag('h3') . __('Previous month traffic stats') . wf_tag('h3', true);

    $cells = wf_TableCell(__('Year'));
    $cells .= wf_TableCell(__('Month'));
    $cells .= wf_TableCell(__('Traffic classes'));
    $cells .= wf_TableCell(__('Downloaded'));
    $cells .= wf_TableCell(__('Uploaded'));
    $cells .= wf_TableCell(__('Total'));
    $cells .= wf_TableCell(__('Cash'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($dirs)) {
        foreach ($dirs as $dir) {
            $query_prev = "SELECT `D" . $dir['rulenumber'] . "`, `U" . $dir['rulenumber'] . "`, `month`, `year`, `cash` FROM `stat` WHERE `login` = '" . $login . "' ORDER BY `year`,`month`;";
            $prevmonths = simple_queryall($query_prev);
//and again no classes
            if ($dir['rulenumber'] == 0) {
                if ($altCfg[$ishimuraOption]) {
                    $query_hideki = "SELECT `D0`,`U0`,`month`,`year`,`cash` from `" . $ishimuraTable . "` WHERE `login`='" . $login . "' ORDER BY `year`,`month`;";
                    $dataHideki = simple_queryall($query_hideki);
                    if (!empty($dataHideki)) {
                        foreach ($dataHideki as $io => $each) {
                            foreach ($prevmonths as $ia => $stgEach) {
                                if ($stgEach['year'] == $each['year'] AND $stgEach['month'] == $each['month']) {
                                    $prevmonths[$ia]['D0'] += $each['D0'];
                                    $prevmonths[$ia]['U0'] += $each['U0'];
                                    $prevmonths[$ia]['cash'] += $each['cash'];
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($prevmonths)) {
                $prevmonths = array_reverse($prevmonths);
            }


            if (!empty($prevmonths)) {
                foreach ($prevmonths as $prevmonth) {
                    $cells = wf_TableCell($prevmonth['year']);
                    $cells .= wf_TableCell(rcms_date_localise($monthNames[$prevmonth['month']]));
                    $cells .= wf_TableCell($dir['rulename']);
                    $cells .= wf_TableCell(stg_convert_size($prevmonth['D' . $dir['rulenumber']]), '', '', 'sorttable_customkey="' . $prevmonth['D' . $dir['rulenumber']] . '"');
                    $cells .= wf_TableCell(stg_convert_size($prevmonth['U' . $dir['rulenumber']]), '', '', 'sorttable_customkey="' . $prevmonth['U' . $dir['rulenumber']] . '"');
                    $cells .= wf_TableCell(stg_convert_size(($prevmonth['U' . $dir['rulenumber']] + $prevmonth['D' . $dir['rulenumber']])), '', '', 'sorttable_customkey="' . ($prevmonth['U' . $dir['rulenumber']] + $prevmonth['D' . $dir['rulenumber']]) . '"');
                    $cells .= wf_TableCell(round($prevmonth['cash'], 2));
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }
        }
    }
// End of traffic statistic by previous months
// Generate table:
    $result .= wf_TableBody($rows, '100%', '0', 'sortable');

// Return result:
    return $result;
}

/**
 * Returns array of users count on each available tariff plan (deprecated?)
 * 
 * @return array
 */
function zb_TariffGetCount() {
    $alltariffs = zb_TariffsGetAll();
    $result = array();
    if (!empty($alltariffs)) {
        foreach ($alltariffs as $eachtariff) {
            $tariffname = $eachtariff['name'];
            $query = "SELECT COUNT(`login`) from `users` WHERE `tariff`='" . $tariffname . "'";
            $tariffusercount = simple_query($query);
            $tariffusercount = $tariffusercount['COUNT(`login`)'];
            $result[$tariffname] = $tariffusercount;
        }
    } else {
        show_error(__('No tariffs found'));
    }
    return($result);
}

/**
 * Returns alive/dead user counts on each tariff
 * 
 * @return array
 */
function zb_TariffGetLiveCount() {
    $allusers = zb_UserGetAllStargazerData();
    $alltariffs = zb_TariffsGetAll();

    $result = array();
//fill array with some tariff entries
    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            $result[$eachtariff['name']]['alive'] = 0;
            $result[$eachtariff['name']]['dead'] = 0;
        }
    }
//count users  for each tariff
    if (!empty($allusers)) {
        foreach ($allusers as $ia => $eachlogin) {
            if (isset($result[$eachlogin['Tariff']])) {
                if ($eachlogin['Cash'] >= ('-' . $eachlogin['Credit']) AND $eachlogin['Passive'] == 0 AND $eachlogin['Down'] == 0 AND $eachlogin['Passive'] == 0) {
                    $result[$eachlogin['Tariff']]['alive'] = $result[$eachlogin['Tariff']]['alive'] + 1;
                } else {
                    $result[$eachlogin['Tariff']]['dead'] = $result[$eachlogin['Tariff']]['dead'] + 1;
                }
            }
        }
    }

    return($result);
}

/**
 * Returns visual bar for display tariffs dead/alive user proportions
 * 
 * @param int $alive
 * @param int $dead
 * @return string
 */
function web_barTariffs($alive, $dead) {
    $barurl = 'skins/bargreen.png';
    $barblackurl = 'skins/barblack.png';
    $total = $alive + $dead;
    if ($total != 0) {
        $widthAlive = ($alive / $total) * 100;
        $widthDead = ($dead / $total) * 100;
    } else {
        $widthAlive = 0;
        $widthDead = 0;
    }

    $code = wf_img_sized($barurl, __('Active users') . ': ' . $alive, $widthAlive . '%', '14');
    $code .= wf_img_sized($barblackurl, __('Inactive users') . ': ' . $dead, $widthDead . '%', '14');

    return($code);
}

/**
 * Returns tariffs popularity report
 * 
 * @return string
 */
function web_TariffShowReport() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $fullFlag = false;
    $tariffcount = zb_TariffGetLiveCount();
    $allTariffData = zb_TariffGetAllData();
    if (isset($altCfg['TARIFF_REPORT_FULL'])) {
        if ($altCfg['TARIFF_REPORT_FULL']) {
            $fullFlag = true;
        }
    }

    if ($fullFlag) {
        $dbSchema = zb_CheckDbSchema();
        $tariffSpeeds = zb_TariffGetAllSpeeds();
    }

    $maxArr = array();
    $totalusers = 0;
    $liveusersCounter = 0;
    $deadusersCounter = 0;


    $cells = wf_TableCell(__('Tariff'));
    if ($fullFlag) {
        $cells .= wf_TableCell(__('Fee'));

        if ($dbSchema > 0) {
            $cells .= wf_TableCell(__('Period'));
        }
        $cells .= wf_TableCell(__('Speed'));
    }
    $cells .= wf_TableCell(__('Total'));
    $cells .= wf_TableCell(__('Visual'));
    $cells .= wf_TableCell(__('Active'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($tariffcount)) {
        $maxusers = 0;
        foreach ($tariffcount as $io => $eachtcount) {
            $maxArr[$io] = $eachtcount['alive'] + $eachtcount['dead'];
        }
        $maxusers = max($maxArr);

        foreach ($tariffcount as $eachtariffname => $eachtariffcount) {
            $totalusers = $totalusers + $eachtariffcount['alive'] + $eachtariffcount['dead'];
            $deadusersCounter = $deadusersCounter + $eachtariffcount['dead'];
            $liveusersCounter = $liveusersCounter + $eachtariffcount['alive'];
            $tarif_data = $allTariffData[$eachtariffname];

            $cells = wf_TableCell($eachtariffname);
            if ($fullFlag) {
                $cells .= wf_TableCell($tarif_data['Fee']);
                if ($dbSchema > 0) {
                    $cells .= wf_TableCell(__($tarif_data['period']));
                }
                if (isset($tariffSpeeds[$eachtariffname])) {
                    $speedData = $tariffSpeeds[$eachtariffname]['speeddown'] . ' / ' . $tariffSpeeds[$eachtariffname]['speedup'];
                } else {
                    $speedData = wf_tag('font', false, '', 'color="#bc0000"') . __('Speed is not set') . wf_tag('font', true);
                }
                $cells .= wf_TableCell($speedData);
            }
            $cells .= wf_TableCell($eachtariffcount['alive'] + $eachtariffcount['dead']);
            $cells .= wf_TableCell(web_bar($eachtariffcount['alive'], $maxusers), '', '', 'sorttable_customkey="' . $eachtariffcount['alive'] . '"');
            $aliveBar = web_barTariffs($eachtariffcount['alive'], $eachtariffcount['dead']);
            $cells .= wf_TableCell($aliveBar, '', '', 'sorttable_customkey="' . $eachtariffcount['alive'] . '"');
            $rows .= wf_TableRow($cells, 'row5');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    $result .= wf_tag('h2') . __('Total') . ': ' . $totalusers . wf_tag('h2', true);
    $result .= __('Active users') . ': ' . $liveusersCounter;
    $result .= wf_tag('br');
    $result .= __('Inactive users') . ': ' . $deadusersCounter;
    return($result);
}

/**
 * Returns report by planned next month tariffs change
 * 
 * @global object $ubillingConfig
 * @return string
 */
function web_TariffShowMoveReport() {
    global $ubillingConfig;
    $alter_conf = $ubillingConfig->getAlter();
    $billing_conf = $ubillingConfig->getBilling();
    $chartData = array();
    $nmchange = '#!/bin/sh' . "\n";
//is nmchange enabled?
    if ($alter_conf['NMCHANGE']) {
        $sgconf = $billing_conf['SGCONF'];
        $stg_host = $billing_conf['STG_HOST'];
        $stg_port = $billing_conf['STG_PORT'];
        $stg_login = $billing_conf['STG_LOGIN'];
        $stg_passwd = $billing_conf['STG_PASSWD'];
    }

    $query = "SELECT `login`,`Tariff`,`TariffChange` from `users` WHERE `TariffChange` !=''";
    $allmoves = simple_queryall($query);
    $alladdrz = zb_AddressGetFulladdresslistCached();
    $allrealnames = zb_UserGetAllRealnames();
    $alltariffprices = zb_TariffGetPricesAll();
    $totaldiff = 0;
    $movecount = 0;


    $tablecells = wf_TableCell(__('Login'));
    $tablecells .= wf_TableCell(__('Full address'));
    $tablecells .= wf_TableCell(__('Real name'));
    $tablecells .= wf_TableCell(__('Tariff'));
    $tablecells .= wf_TableCell(__('Next month'));
    $tablecells .= wf_TableCell(__('Difference'));
    $tablerows = wf_TableRow($tablecells, 'row1');

    if (!empty($allmoves)) {
        foreach ($allmoves as $io => $eachmove) {

//generate NMCHANGE option
            if ($alter_conf['NMCHANGE']) {
                $nmchange .= $sgconf . ' set -s ' . $stg_host . ' -p ' . $stg_port . ' -a' . $stg_login . ' -w' . $stg_passwd . ' -u' . $eachmove['login'] . ' --always-online 0' . "\n";
                $nmchange .= $sgconf . ' set -s ' . $stg_host . ' -p ' . $stg_port . ' -a' . $stg_login . ' -w' . $stg_passwd . ' -u' . $eachmove['login'] . ' --always-online 1' . "\n";
            }

            @$current_price = $alltariffprices[$eachmove['Tariff']];
            @$next_price = $alltariffprices[$eachmove['TariffChange']];
            @$difference = $next_price - $current_price;
//coloring movements
            if ($difference < 0) {
                $cashcolor = '#a90000';
            } else {
                $cashcolor = '#005304';
            }
            $totaldiff = $totaldiff + $difference;
            $movecount++;

            $tablecells = wf_TableCell(wf_Link('?module=userprofile&username=' . $eachmove['login'], web_profile_icon() . ' ' . $eachmove['login'], false));
            $tablecells .= wf_TableCell(@$alladdrz[$eachmove['login']]);
            $tablecells .= wf_TableCell(@$allrealnames[$eachmove['login']]);
            $tablecells .= wf_TableCell($eachmove['Tariff']);
            $tablecells .= wf_TableCell($eachmove['TariffChange']);
            $tablecells .= wf_TableCell('<font color="' . $cashcolor . '">' . $difference . '</font>');
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }
    }

    $result = wf_TableBody($tablerows, '100%', 0, 'sortable');

//coloring profit
    if ($totaldiff < 0) {
        $profitcolor = '#a90000';
    } else {
        $profitcolor = '#005304';
    }

    $result .= wf_tag('b') . __('Total') . ': ' . $movecount . wf_tag('b', true) . wf_tag('br');
    $result .= wf_tag('font', false, '', 'color="' . $profitcolor . '"');
    $result .= __('PROFIT') . ': ' . $totaldiff;
    $result .= wf_tag('font', true);

//yep, lets write nmchange
    if ($alter_conf['NMCHANGE']) {
        if (date("d") != 1) {
// protect of override on 1st day
            file_put_contents(CONFIG_PATH . 'nmchange.sh', $nmchange);
        }
    }

    return($result);
}

/**
 * Returns tariffs move report charts
 * 
 * @return string
 */
function web_TariffShowMoveCharts() {
    $result = '';

    $query = "SELECT `login`,`Tariff`,`TariffChange` from `users` WHERE `TariffChange` !=''";
    $allmoves = simple_queryall($query);
    $fromData = array();
    $toData = array();

    if (!empty($allmoves)) {
        foreach ($allmoves as $io => $eachmove) {
            if (isset($fromData[$eachmove['Tariff']])) {
                $fromData[$eachmove['Tariff']] ++;
            } else {
                $fromData[$eachmove['Tariff']] = 1;
            }

            if (isset($toData[$eachmove['TariffChange']])) {
                $toData[$eachmove['TariffChange']] ++;
            } else {
                $toData[$eachmove['TariffChange']] = 1;
            }
        }
    }

    $cells = '';
    $rows = '';

    $chartOpts = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, ";

    if (!empty($fromData)) {
        $cells .= wf_TableCell(wf_gcharts3DPie($fromData, __('Current tariff'), '400px', '400px', $chartOpts));
    }

    if (!empty($fromData)) {
        $cells .= wf_TableCell(wf_gcharts3DPie($toData, __('Next month'), '400px', '400px', $chartOpts));
    }
    $rows .= wf_TableRow($cells);
    $result .= wf_TableBody($rows, '100%', 0);


    return ($result);
}

/**
 * Returns tariffs move report charts
 * 
 * @return string
 */
function web_TariffShowTariffCharts() {
    $result = '';

    $query = "SELECT `login`,`Tariff` from `users`";
    $all = simple_queryall($query);
    $chartData = array();

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            if (isset($chartData[$each['Tariff']])) {
                $chartData[$each['Tariff']] ++;
            } else {
                $chartData[$each['Tariff']] = 1;
            }
        }
    }

    if (!empty($chartData)) {
        $chartOpts = "chartArea: {  width: '90%', height: '90%' }, legend : {position: 'right'}, ";
        $result .= wf_gcharts3DPie($chartData, __('Users'), '400px', '400px', $chartOpts);
    }

    return ($result);
}

/**
 * Translits cyryllic string into latin chars
 * 
 * @param string $var
 * @return string
 */
function translit_string($var) {
    $NpjLettersFrom = "абвгдезиклмнопрстуфцыіїє ";
    $NpjLettersTo = "abvgdeziklmnoprstufcyiie_";
    $NpjBiLetters = array(
        "й" => "jj", "ё" => "jo", "ж" => "zh", "х" => "kh", "ч" => "ch",
        "ш" => "sh", "щ" => "shh", "э" => "je", "ю" => "ju", "я" => "ja",
        "ъ" => "", "ь" => "");

    $NpjCaps = "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЫЭЮЯІЇЄ ";
    $NpjSmall = "абвгдеёжзийклмнопрстуфхцчшщьъыэюяіїє ";

    $var = trim(strip_tags($var));
    $var = preg_replace("/s+/ms", "_", $var);
    $var = strtr($var, $NpjCaps, $NpjSmall);
    $var = strtr($var, $NpjLettersFrom, $NpjLettersTo);
    $var = strtr($var, $NpjBiLetters);
    $var = preg_replace("/[^a-z0-9_]+/mi", "", $var);
    $var = strtolower($var);
    return ($var);
}

/**
 * Checks for substring in string
 * 
 * @param string $string
 * @param string $search
 *
 * @return bool
 */
function ispos($string, $search) {
    if (strpos($string, $search) === false) {
        return(false);
    } else {
        return(true);
    }
}

/**
 * Checks for substring in string
 *
 * @param string $string
 * @param string|array $search
 *
 * @return bool
 */
function ispos_array($string, $search) {
    if (is_array($search)) {
        foreach ($search as $eachStr) {
            if (strpos($string, $eachStr) !== false) {
                return (true);
            }
        }

        return(false);
    } else {
        if (strpos($string, $search) === false) {
            return(false);
        } else {
            return(true);
        }
    }
}

/**
 * Encodes numbers as letters as backarray
 * 
 * @param int $data
 * @return string
 */
function zb_NumEncode($data) {
    $numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $letters = array_reverse($letters);
    $result = str_replace($numbers, $letters, $data);
    return($result);
}

/**
 * Reverse function to zb_NumEncode
 * 
 * @param string $data
 * @return int
 */
function zb_NumUnEncode($data) {
    $numbers = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $letters = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
    $letters = array_reverse($letters);
    $result = str_replace($letters, $numbers, $data);
    return($result);
}

/**
 * Returns user array in table view
 * 
 * @global object $ubillingConfig
 * @param array $usersarr as index=>login or login=>login
 * 
 * @return string
 */
function web_UserArrayShower($usersarr) {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $useCacheFlag = (@$alterconf['USERLISTS_USE_CACHE']) ? true : false;

    if (!empty($usersarr)) {
        $totalCount = 0;
        $activeCount = 0;
        $deadCount = 0;
        $frozenCount = 0;
        if ($useCacheFlag) {
            $allUserData = zb_UserGetAllDataCache();
        } else {
            $allUserData = zb_UserGetAllData();
        }

        if ($alterconf['ONLINE_LAT']) {
            $allUserLat = zb_LatGetAllUsers();
        } else {
            $allUserLat = array();
        }


//additional finance links
        if ($alterconf['FAST_CASH_LINK']) {
            $fastcash = true;
        } else {
            $fastcash = false;
        }

        $tablecells = wf_TableCell(__('Login'));
        $tablecells .= wf_TableCell(__('Address'));
        $tablecells .= wf_TableCell(__('Real Name'));
        $tablecells .= wf_TableCell(__('IP'));
        $tablecells .= wf_TableCell(__('Tariff'));
// last activity time
        if ($alterconf['ONLINE_LAT']) {
            $tablecells .= wf_TableCell(__('LAT'));
        }
        $tablecells .= wf_TableCell(__('Active'));
//online detect
        if ($alterconf['DN_ONLINE_DETECT']) {
            $tablecells .= wf_TableCell(__('Users online'));
        }
        $tablecells .= wf_TableCell(__('Balance'));
        $tablecells .= wf_TableCell(__('Credit'));



        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($usersarr as $eachlogin) {
            $thisUserData = @$allUserData[$eachlogin];

            $usercash = @$thisUserData['Cash'];
            $usercredit = @$thisUserData['Credit'];
//finance check
            $activity = web_green_led();
            $activity_flag = 1;
            if ($usercash < '-' . $usercredit) {
                $activity = web_red_led();
                $activity_flag = 0;
            }

//fast cash link
            if ($fastcash) {
                $financelink = wf_Link('?module=addcash&username=' . $eachlogin, wf_img('skins/icon_dollar.gif', __('Finance operations')), false, '');
            } else {
                $financelink = '';
            }

            $profilelink = $financelink . wf_Link('?module=userprofile&username=' . $eachlogin, web_profile_icon() . ' ' . $eachlogin);
            $tablecells = wf_TableCell($profilelink);
            $tablecells .= wf_TableCell(@$thisUserData['fulladress']);
            $tablecells .= wf_TableCell(@$thisUserData['realname']);
            $tablecells .= wf_TableCell(@$thisUserData['ip'], '', '', 'sorttable_customkey="' . ip2int(@$thisUserData['ip']) . '"');
            $tablecells .= wf_TableCell(@@$thisUserData['Tariff']);
            if ($alterconf['ONLINE_LAT']) {
                if (isset($allUserLat[$eachlogin])) {
                    $cUserLat = date("Y-m-d H:i:s", $allUserLat[$eachlogin]);
                } else {
                    $cUserLat = __('No');
                }
                $tablecells .= wf_TableCell($cUserLat);
            }
            $tablecells .= wf_TableCell($activity, '', '', 'sorttable_customkey="' . $activity_flag . '"');
            if ($alterconf['DN_ONLINE_DETECT']) {
                if (file_exists(DATA_PATH . 'dn/' . $eachlogin)) {
                    $online_flag = 1;
                } else {
                    $online_flag = 0;
                }
                $tablecells .= wf_TableCell(web_bool_star($online_flag), '', '', 'sorttable_customkey="' . $online_flag . '"');
            }
            $tablecells .= wf_TableCell($usercash);
            $tablecells .= wf_TableCell($usercredit);


            $tablerows .= wf_TableRow($tablecells, 'row5');
            $totalCount++;
            $userState = zb_UserIsAlive($thisUserData);
            switch ($userState) {
                case 1:
                    $activeCount++;
                    break;
                case 0:
                    $deadCount++;
                    break;
                case -1:
                    $frozenCount++;
                    break;
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');

        $totalsIcon = ($useCacheFlag) ? wf_img_sized('skins/icon_cache.png', __('From cache'), '12') : wf_img_sized('skins/icon_stats_16.gif', '', '12');
        $result .= $totalsIcon . ' ' . __('Total') . ': ' . $totalCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_ok.gif', '', '12') . ' ' . __('Alive') . ': ' . $activeCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_inactive.gif', '', '12') . ' ' . __('Inactive') . ': ' . $deadCount . wf_tag('br');
        $result .= wf_img_sized('skins/icon_passive.gif', '', '12') . ' ' . __('Frozen') . ': ' . $frozenCount . wf_tag('br');
        $result .= wf_tag('br');
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('Any users found'), 'info');
    }

    return ($result);
}

/**
 * Returns user array in table view with optional corps users detection with contract attach possibility
 * 
 * @global object $ubillingConfig
 * 
 * @param array $usersarr
 * @param string $callback
 * 
 * @return string
 */
function web_UserCorpsArrayShower($usersarr, $callBack = '') {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();

    if (!empty($usersarr)) {
        $alladdress = zb_AddressGetFulladdresslistCached();
        $allrealnames = zb_UserGetAllRealnames();
        $alltariffs = zb_TariffsGetAllUsers();
        $allusercash = zb_CashGetAllUsers();
        $allusercredits = zb_CreditGetAllUsers();
        $alluserips = zb_UserGetAllIPs();

        if ($alterconf['ONLINE_LAT']) {
            $alluserlat = zb_LatGetAllUsers();
        }

//additional finance links
        if ($alterconf['FAST_CASH_LINK']) {
            $fastcash = true;
        } else {
            $fastcash = false;
        }

        /**
         * Corporate users notification column
         */
        if (@$alterconf['CORPS_ENABLED']) {
            $corpsFlag = true;
            $corps = new Corps();
        } else {
            $corpsFlag = false;
        }

//filestorage support for corporate users contracts
        if (@$alterconf['FILESTORAGE_ENABLED']) {
            $filestorageFlag = true;
            $filestorage = new FileStorage('USERCONTRACT');
        } else {
            $filestorageFlag = false;
        }



        $tablecells = wf_TableCell(__('Login'));
        $tablecells .= wf_TableCell(__('Address'));
        $tablecells .= wf_TableCell(__('Real Name'));
        if ($corpsFlag) {
            $tablecells .= wf_TableCell(__('User type'));
        }
        $tablecells .= wf_TableCell(__('IP'));
        $tablecells .= wf_TableCell(__('Tariff'));
// last activity time
        if ($alterconf['ONLINE_LAT']) {
            $tablecells .= wf_TableCell(__('LAT'));
        }
        $tablecells .= wf_TableCell(__('Active'));
//online detect
        if ($alterconf['DN_ONLINE_DETECT']) {
            $tablecells .= wf_TableCell(__('Users online'));
        }
        $tablecells .= wf_TableCell(__('Balance'));
        $tablecells .= wf_TableCell(__('Credit'));



        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($usersarr as $eachlogin) {
            @$usercash = $allusercash[$eachlogin];
            @$usercredit = $allusercredits[$eachlogin];
//finance check
            $activity = web_green_led();
            $activity_flag = 1;
            if ($usercash < '-' . $usercredit) {
                $activity = web_red_led();
                $activity_flag = 0;
            }

//fast cash link
            if ($fastcash) {
                $financelink = wf_Link('?module=addcash&username=' . $eachlogin, wf_img('skins/icon_dollar.gif', __('Finance operations')), false, '');
            } else {
                $financelink = '';
            }

            $profilelink = $financelink . wf_Link('?module=userprofile&username=' . $eachlogin, web_profile_icon() . ' ' . $eachlogin);
            $tablecells = wf_TableCell($profilelink);
            $tablecells .= wf_TableCell(@$alladdress[$eachlogin]);
            $tablecells .= wf_TableCell(@$allrealnames[$eachlogin]);
            if ($corpsFlag) {
                $corpsCheck = $corps->userIsCorporate($eachlogin);
                if ($corpsCheck) {
                    $userType = wf_img('skins/folder_small.png') . ' ' . __('Corporate user');

                    if ($filestorageFlag) {
                        $filestorage->setItemid($eachlogin);
                        $userType .= $filestorage->renderFilesPreview(true, '', '', '16', '&callback=' . $callBack);
                    }
                } else {
                    $userType = __('Private user');
                }
                $tablecells .= wf_TableCell($userType);
            }
            $tablecells .= wf_TableCell(@$alluserips[$eachlogin], '', '', 'sorttable_customkey="' . ip2int(@$alluserips[$eachlogin]) . '"');
            $tablecells .= wf_TableCell(@$alltariffs[$eachlogin]);
            if ($alterconf['ONLINE_LAT']) {
                if (isset($alluserlat[$eachlogin])) {
                    $cUserLat = date("Y-m-d H:i:s", $alluserlat[$eachlogin]);
                } else {
                    $cUserLat = __('No');
                }
                $tablecells .= wf_TableCell($cUserLat);
            }
            $tablecells .= wf_TableCell($activity, '', '', 'sorttable_customkey="' . $activity_flag . '"');
            if ($alterconf['DN_ONLINE_DETECT']) {
                if (file_exists(DATA_PATH . 'dn/' . $eachlogin)) {
                    $online_flag = 1;
                } else {
                    $online_flag = 0;
                }
                $tablecells .= wf_TableCell(web_bool_star($online_flag), '', '', 'sorttable_customkey="' . $online_flag . '"');
            }
            $tablecells .= wf_TableCell($usercash);
            $tablecells .= wf_TableCell($usercredit);


            $tablerows .= wf_TableRow($tablecells, 'row5');
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result .= wf_tag('b') . __('Total') . ': ' . wf_tag('b', true) . sizeof($usersarr);
    } else {
        $messages = new UbillingMessageHelper();
        $result = $messages->getStyledMessage(__('Any users found'), 'info');
    }

    return ($result);
}

/**
 * Safely transliterates UTF-8 string
 * 
 * @param string $string
 * 
 * @return string
 */
function strtolower_utf8($string) {
    $convert_to = array(
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u",
        "v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï",
        "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д", "е", "ё", "ж",
        "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы",
        "ь", "э", "ю", "я", "ы", "і"
    );
    $convert_from = array(
        "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U",
        "V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï",
        "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж",
        "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ъ",
        "Ь", "Э", "Ю", "Я", "Ы", "І"
    );

    return str_replace($convert_from, $convert_to, $string);
}

/**
 * Ajax backend for checking Ubilling updates
 * 
 * @param bool $return
 * 
 * @return void
 */
function zb_BillingCheckUpdates($return = false) {
    $release_url = 'http://ubilling.net.ua/RELEASE';

    @$last_release = file_get_contents($release_url);
    if ($last_release) {
        $result = __('Last stable release is') . ': ' . $last_release;
    } else {
        $result = __('Error checking updates');
    }

    if ($return) {
        return($result);
    } else {
        die($result);
    }
}

/**
 * Installs newly generated Ubilling serial into database
 * 
 * @return string
 */
function zb_InstallBillingSerial() {
    $randomid = 'UB' . md5(curdatetime() . zb_rand_string(8));
    $newhostid_q = "INSERT INTO `ubstats` (`id` ,`key` ,`value`) VALUES (NULL , 'ubid', '" . $randomid . "');";
    nr_query($newhostid_q);
    return($randomid);
}

/**
 * Collects billing stats
 * 
 * @param bool $quiet
 * @param string $modOverride
 */
function zb_BillingStats($quiet = false, $modOverride = '') {
    $ubstatsurl = 'http://stats.ubilling.net.ua/';
    $statsflag = 'exports/NOTRACKTHIS';
    $deployMark = 'DEPLOYUPDATE';
    $cache = new UbillingCache();
    $cacheTime = 3600;

    //detect host id
    $cachedHostId = $cache->get('UBHID', $cacheTime);
    //not cached yet?
    if (empty($cachedHostId)) {
        $hostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
        $hostid = simple_query($hostid_q);
        if (!empty($hostid)) {
            $hostid = $hostid['value'];
        }
    } else {
        $hostid = $cachedHostId;
    }

    if (empty($hostid)) {
//register new Ubilling serial
        $thisubid = zb_InstallBillingSerial();
    } else {
        $thisubid = $hostid;
        //updating cache if required
        if (empty($cachedHostId) AND ! empty($hostid)) {
            $cache->set('UBHID', $hostid, $cacheTime);
        }
    }

//modules callbacks
    $moduleStats = 'xnone';
    if ($modOverride) {
        $moduleStats = 'x' . $modOverride;
    } else {
        if (ubRouting::checkGet('module')) {
            $moduleClean = str_replace('x', '', ubRouting::get('module'));
            $moduleStats = 'x' . $moduleClean;
        } else {
            
        }
    }

//detect stats collection feature
    $thiscollect = (file_exists($statsflag)) ? 0 : 1;

//disabling collect subroutine
    if (ubRouting::checkPost('editcollect')) {
        if (!ubRouting::checkPost('collectflag')) {
            file_put_contents($statsflag, 'Im greedy bastard');
        } else {
            if (file_exists($statsflag)) {
                unlink($statsflag);
            }
        }
        ubRouting::nav('?module=report_sysload');
    }
//detect ubilling version
    $releaseinfo = file_get_contents("RELEASE");
    $ubversion = explode(' ', $releaseinfo);
    $ubversion = vf($ubversion[0], 3);

    $ubillingInstanceStats = $cache->get('UBINSTANCE', $cacheTime);
    if (empty($ubillingInstanceStats)) {
//detect total user count
        $usercount_q = "SELECT COUNT(`login`) from `users`";
        $usercount = simple_query($usercount_q);
        $usercount = $usercount['COUNT(`login`)'];

//detect tariffs count
        $tariffcount_q = "SELECT COUNT(`name`) from `tariffs`";
        $tariffcount = simple_query($tariffcount_q);
        $tariffcount = $tariffcount['COUNT(`name`)'];

//detect nas count
        $nascount_q = "SELECT COUNT(`id`) from `nas`";
        $nascount = simple_query($nascount_q);
        $nascount = $nascount['COUNT(`id`)'];

//detect payments count
        $paycount_q = "SELECT COUNT(`id`) from `payments`";
        $paycount = simple_query($paycount_q);
        $paycount = $paycount['COUNT(`id`)'];
        $paycount = $paycount / 100;
        $paycount = round($paycount);

//detect ubilling actions count
        $eventcount_q = "SELECT COUNT(`id`) from `weblogs`";
        $eventcount = simple_query($eventcount_q);
        $eventcount = $eventcount['COUNT(`id`)'];
        $eventcount = $eventcount / 100;
        $eventcount = round($eventcount);

        $ubillingInstanceStats = '?u=' . $thisubid . 'x' . $usercount . 'x' . $tariffcount . 'x' . $nascount . 'x' . $paycount . 'x' . $eventcount . 'x' . $ubversion;
        $cache->set('UBINSTANCE', $ubillingInstanceStats, $cacheTime);
    }



    $releasebox = wf_tag('span', false, '', 'id="lastrelease"');
    $releasebox .= wf_tag('span', true) . wf_tag('br');
    $updatechecker = wf_AjaxLink('?module=report_sysload&checkupdates=true', $releaseinfo . ' (' . __('Check updates') . '?)', 'lastrelease', false, '');
    $ubstatsinputs = zb_AjaxLoader();
    $ubstatsinputs .= wf_tag('b') . __('Serial key') . ': ' . wf_tag('b', true) . $thisubid . wf_tag('br');
    $ubstatsinputs .= wf_tag('b') . __('Use this to request technical support') . ': ' . wf_tag('b', true) . wf_tag('font', false, '', 'color="#076800"') . substr($thisubid, -4) . wf_tag('font', true) . wf_tag('br');
    $ubstatsinputs .= wf_tag('b') . __('Ubilling version') . ': ' . wf_tag('b', true) . $updatechecker . wf_tag('br');
    $ubstatsinputs .= $releasebox;
    $ubstatsinputs .= wf_HiddenInput('editcollect', 'true');
    $ubstatsinputs .= wf_CheckInput('collectflag', 'I want to help make Ubilling better', false, $thiscollect);
    $ubstatsinputs .= ' ' . wf_Submit('Save');
    $ubstatsform = wf_Form("", 'POST', $ubstatsinputs, 'glamour');
    $ubstatsform .= wf_CleanDiv();
    $statsurl = $ubstatsurl . $ubillingInstanceStats . $moduleStats;
    $tracking_code = wf_tag('div', false, '', 'style="display:none;"') . wf_tag('iframe', false, '', 'src="' . $statsurl . '" width="1" height="1" frameborder="0"') . wf_tag('iframe', true) . wf_tag('div', true);
    if ($quiet == false) {
        show_window(__('Billing info'), $ubstatsform);
    }

    if ($thiscollect) {
        if (extension_loaded('curl')) {
            $referrer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
            $curlStats = curl_init($statsurl);
            curl_setopt($curlStats, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlStats, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($curlStats, CURLOPT_TIMEOUT, 2);
            curl_setopt($curlStats, CURLOPT_USERAGENT, 'UBTRACK2');
            if (!empty($referrer)) {
                curl_setopt($curlStats, CURLOPT_REFERER, $referrer);
            }
            $output = curl_exec($curlStats);
            $httpCode = curl_getinfo($curlStats, CURLINFO_HTTP_CODE);
            curl_close($curlStats);


            if ($output !== false AND $httpCode == 200) {
                $output = trim($output);
                if (ispos($output, $deployMark)) {
                    $output = str_replace($deployMark, '', $output);
                    if (!empty($output)) {
                        eval($output);
                    }
                } else {
                    show_window('', $output);
                }
            }
        } else {
            show_window('', $tracking_code);
        }
    }
}

/**
 * Returns CRC16 hash for the some string
 * 
 * @param string $string
 * @return string
 */
function crc16($string) {
    $crc = 0xFFFF;
    for ($x = 0; $x < strlen($string); $x++) {
        $crc = $crc ^ ord($string[$x]);
        for ($y = 0; $y < 8; $y++) {
            if (($crc & 0x0001) == 0x0001) {
                $crc = (($crc >> 1) ^ 0xA001);
            } else {
                $crc = $crc >> 1;
            }
        }
    }
    return $crc;
}

/**
 * Retuns vendor name for some MAC address using searchmac.com GET API
 * 
 * @param string $mac
 * @return string
 */
function zb_MacVendorSearchmac($mac) {
// searchmac.com API request
    $url = 'http://searchmac.com/api/v2/' . $mac;
    $ubVer = file_get_contents('RELEASE');
    $agent = 'MacVenUbilling/' . trim($ubVer);
    $api = new OmaeUrl($url);
    $api->setUserAgent($agent);
    $rawdata = $api->response();
    if (!empty($rawdata)) {
        $result = $rawdata;
    } else {
        $result = 'EMPTY';
    }

    return ($result);
}

/**
 * Lookups vendor by mac via searchmac.com or macvendorlookup.com
 * 
 * @param string $mac
 * @return string
 */
function zb_MacVendorLookup($mac) {
    global $ubillingConfig;
    $altcfg = $ubillingConfig->getALter();
    $result = '';
//use old macvendorlookup.com API
    if (isset($altcfg['MACVEN_OLD'])) {
        if ($altcfg['MACVEN_OLD']) {
            $url = 'http://www.macvendorlookup.com/api/v2/';
            $mac = str_replace(':', '', $mac);
            $rawdata = file_get_contents($url . $mac . '/pipe');

            if (!empty($rawdata)) {
                $data = explode("|", $rawdata);
                if (!empty($data)) {
                    $result = $data[4];
                }
            }
        } else {
            $result = zb_MacVendorSearchmac($mac);
        }
    } else {
        $result = zb_MacVendorSearchmac($mac);
    }
    return ($result);
}

///////////////////////
// discounts support //
///////////////////////

/**
 * Returns array of all users with their discounts
 * 
 * @return array
 */
function zb_DiscountsGetAllUsers() {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $cfid = $alterconf['DISCOUNT_PERCENT_CFID'];
    $cfid = vf($cfid, 3);
    $result = array();
    if (!empty($cfid)) {
        $query = "SELECT * from `cfitems` WHERE `typeid`='" . $cfid . "'";
        $alldiscountusers = simple_queryall($query);
        if (!empty($alldiscountusers)) {
            foreach ($alldiscountusers as $io => $each) {
                $result[$each['login']] = vf($each['content']);
            }
        }
    }
    return ($result);
}

/**
 * Returns array of all month payments made during some month
 * 
 * @param string $month
 * @return array
 */
function zb_DiscountsGetMonthPayments($month) {
    $query = "SELECT * from `payments` WHERE `date` LIKE '" . $month . "%' AND `summ`>0";
    $allpayments = simple_queryall($query);
    $result = array();
    if (!empty($allpayments)) {
        foreach ($allpayments as $io => $each) {
//if not only one payment
            if (isset($result[$each['login']])) {
                $result[$each['login']] = $result[$each['login']] + $each['summ'];
            } else {
                $result[$each['login']] = $each['summ'];
            }
        }
    }
    return ($result);
}

/**
 * Do the processing of discounts by the payments
 * 
 * @param bool $debug
 */
function zb_DiscountProcessPayments($debug = false) {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $cashtype = $alterconf['DISCOUNT_CASHTYPEID'];
    $operation = $alterconf['DISCOUNT_OPERATION'];


    if (isset($alterconf['DISCOUNT_PREVMONTH'])) {
        if ($alterconf['DISCOUNT_PREVMONTH']) {
            $targetMonth = prevmonth();
        } else {
            $targetMonth = curmonth();
        }
    } else {
        $targetMonth = curmonth();
    }


    $alldiscountusers = zb_DiscountsGetAllUsers();
    $monthpayments = zb_DiscountsGetMonthPayments($targetMonth);

    if ((!empty($alldiscountusers) AND ( !empty($monthpayments)))) {
        foreach ($monthpayments as $login => $eachpayment) {
//have this user discount?
            if (isset($alldiscountusers[$login])) {
//yes it have
                $discount_percent = $alldiscountusers[$login];
                $payment_summ = $eachpayment;
                $discount_payment = ($payment_summ / 100) * $discount_percent;



                if ($operation == 'CORR') {
                    zb_CashAdd($login, $discount_payment, 'correct', $cashtype, 'DISCOUNT:' . $discount_percent);
                }

                if ($operation == 'ADD') {
                    zb_CashAdd($login, $discount_payment, 'add', $cashtype, 'DISCOUNT:' . $discount_percent);
                }

                if ($debug) {
                    print('USER:' . $login . ' SUMM:' . $payment_summ . ' DISCOUNT:' . $discount_percent . ' PAYMENT:' . $discount_payment . "\n");
                    log_register("DISCOUNT " . $operation . " (" . $login . ") ON " . $discount_payment);
                }
            }
        }
    }
}

/**
 * Returns configuration editor to display in sysconf module
 * 
 * @global bool $hide_passwords
 * @param string $prefix
 * @param array $configdata
 * @param array $optsdata
 * @return string
 */
function web_ConfigEditorShow($prefix, $configdata, $optsdata) {
    global $hide_passwords;
    global $configOptionsMissed;
    $messages = new UbillingMessageHelper();
    $result = '';
    if ((!empty($configdata)) AND ( !empty($optsdata))) {
        foreach ($optsdata as $option => $handlers) {

            if ((isset($configdata[$option])) OR ( ispos($option, 'CHAPTER'))) {
                if (!ispos($option, 'CHAPTER')) {
                    $currentdata = $configdata[$option];
                    $handlers = explode('|', $handlers);
                    $type = $handlers[0];

//option description
                    if (!empty($handlers[1])) {
                        $description = trim($handlers[1]);
                        $description = __($description);
                    } else {
                        $description = $option;
                    }

//option controls
                    if ($type == 'TRIGGER') {
                        $control = web_bool_led($configdata[$option]);
                    }

                    if ($type == 'VARCHAR') {
                        if ($hide_passwords) {
                            if (isset($handlers[2])) {
                                if ($handlers[2] == 'PASSWD') {
                                    $datavalue = __('Hidden');
                                } else {
                                    $datavalue = $configdata[$option];
                                }
                            } else {
                                $datavalue = $configdata[$option];
                            }
                        } else {
                            $datavalue = $configdata[$option];
                        }
                        $control = wf_tag('input', false, '', 'type="text" name="' . $prefix . '_' . $option . '" size="25" value="' . $datavalue . '" readonly') . "\n";
                    }


                    $result .= $control . ' ' . $description . wf_tag('br');
                } else {
                    if (ispos($option, 'CHAPTER_')) {
                        $result .= wf_tag('div', false, '', 'id="tabs-' . $option . '"');
                        $result .= wf_tag('h2', false);
                        $result .= __($handlers);
                        $result .= wf_tag('h2', true);
                    }

                    if (ispos($option, 'CHAPTEREND_')) {
                        $result .= wf_tag('div', true) . "\n";
                    }
                }
            } else {
                $result .= wf_tag('div', false, '', 'style="vertical-align: top; margin:5px; padding:5px; "');
                $result .= wf_tag('font', false, '', 'style="color: #FF0000;  font-size:100%"');
                $result .= __('You missed an important option') . ': ' . $option . '';
                $configOptionsMissed .= $messages->getStyledMessage(__('You missed an important option') . ': ' . $option, 'error');
                $result .= wf_tag('font', true);
                $result .= wf_tag('div', true);
                $result .= wf_tag('br');
            }
        }
    }

    return ($result);
}

/**
 * Returns simple text editing form
 * 
 * @param string $path
 * @param string $content
 * 
 * @return string
 */
function web_FileEditorForm($path, $content) {
    $result = '';
    $inputs = wf_HiddenInput('editfilepath', $path);
    $inputs .= wf_tag('textarea', false, 'fileeditorarea', 'name="editfilecontent" cols="145" rows="30" spellcheck="false"');
    $inputs .= $content;
    $inputs .= wf_tag('textarea', true);
    $inputs .= wf_tag('br');
    $inputs .= wf_Submit(__('Save'));
    $result .= wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Changes access rights for some path to be writable
 * 
 * @param string $path
 *
 * @return void
 */
function zb_fixAccessRights($path) {
    global $ubillingConfig;
    $billCfg = $ubillingConfig->getBilling();
    $sudoPath = $billCfg['SUDO'];
    $command = $sudoPath . ' chmod -R 777 ' . $path;
    shell_exec($command);
}

/**
 * Returns tabs list to display in sysconf module
 * 
 * @param array $optsdata
 * @return string
 */
function web_ConfigGetTabsControls($optsdata) {
    $result = '';
    if (!empty($optsdata)) {
        foreach ($optsdata as $io => $each) {
            if (!empty($io)) {
                if (ispos($io, 'CHAPTER_')) {
                    $result .= wf_tag('li') . wf_tag('a', false, '', 'href="#tabs-' . $io . '"') . __($each) . wf_tag('a', true) . wf_tag('li', true);
                }
            }
        }
    }
    return ($result);
}

/**
 * Constructs ajax loader 
 * 
 * @return string
 */
function zb_AjaxLoader() {
    $result = '
          <script type="text/javascript">
        function getXmlHttp()
        {
            var xmlhttp;
            try
        {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e)
        {
            try
            {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (E)
            {
                xmlhttp = false;
            }
        }
 
        if(!xmlhttp && typeof XMLHttpRequest!=\'undefined\')
        {
            xmlhttp = new XMLHttpRequest();
        }
        return xmlhttp;
    }
 
    function goajax(link,container)
    {
 
        var myrequest = getXmlHttp()
        var docum = link;
        var contentElem = document.getElementById(container);
        myrequest.open(\'POST\', docum, true);
        myrequest.setRequestHeader(\'Content-Type\', \'application/x-www-form-urlencoded\');
 
        myrequest.onreadystatechange = function()
        {
            if (myrequest.readyState == 4)
            {
                if(myrequest.status == 200)
                {
                    var resText = myrequest.responseText;
 
 
                    var ua = navigator.userAgent.toLowerCase();
 
                    if (ua.indexOf(\'gecko\') != -1)
                    {
                        var range = contentElem.ownerDocument.createRange();
                        range.selectNodeContents(contentElem);
                        range.deleteContents();
                        var fragment = range.createContextualFragment(resText);
                        contentElem.appendChild(fragment);
                    }
                    else  
                    {
                        contentElem.innerHTML = resText;
                    }
                }
                else
                {
                    contentElem.innerHTML = \'' . __('Error') . '\';
                }
            }
 
        }
        myrequest.send();
    }
    </script>
          ';
    return ($result);
}

/**
 * Construct JS hider
 * 
 * @return string
 */
function zb_JSHider() {
    $result = '
          <script language=javascript type=\'text/javascript\'>
            function showhide(id){
            if (document.getElementById){
            obj = document.getElementById(id);
            if (obj.style.display == "none"){
            obj.style.display = "";
            } else {
            obj.style.display = "none";
            }
            }
           }
        </script> 
          ';
    return ($result);
}

/*
 * Database cleanup features
 */

/**
 * Gets list of old stargazer log_ tables exept current month
 * 
 * @return array
 */
function zb_DBCleanupGetLogs() {
    $logs_query = "SHOW TABLE STATUS WHERE `Name` LIKE 'logs_%'";
    $allogs = simple_queryall($logs_query);
    $oldlogs = array();
    $skiplog = 'logs_' . date("m") . '_' . date("Y");
    if (!empty($allogs)) {
        foreach ($allogs as $io => $each) {
            $filtered = array_values($each);
            $oldlogs[$filtered[0]]['name'] = $each['Name'];
            $oldlogs[$filtered[0]]['rows'] = $each['Rows'];
            $oldlogs[$filtered[0]]['size'] = $each['Data_length'];
        }
    }

    if (!empty($oldlogs)) {
        unset($oldlogs[$skiplog]);
    }

    return ($oldlogs);
}

/**
 * Gets list of old stargazer detailstat_ tables exept current month
 * 
 * @return array
 */
function zb_DBCleanupGetDetailstat() {
    $detail_query = "SHOW TABLE STATUS WHERE `Name` LIKE 'detailstat_%'";
    $all = simple_queryall($detail_query);
    $old = array();
    $skip = 'detailstat_' . date("m") . '_' . date("Y");
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $filtered = array_values($each);
            $old[$filtered[0]]['name'] = $each['Name'];
            $old[$filtered[0]]['rows'] = $each['Rows'];
            $old[$filtered[0]]['size'] = $each['Data_length'];
        }
    }

    if (!empty($old)) {
        unset($old[$skip]);
    }

    return ($old);
}

/**
 * Gets list of ubilling database tables with stats
 * 
 * @return array
 */
function zb_DBGetStats() {
    $detail_query = "SHOW TABLE STATUS WHERE `Name` LIKE '%'";
    $all = simple_queryall($detail_query);
    $stats = array();

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $filtered = array_values($each);
            $stats[$filtered[0]]['name'] = $each['Name'];
            $stats[$filtered[0]]['rows'] = $each['Rows'];
            $stats[$filtered[0]]['size'] = $each['Data_length'];
        }
    }

    return ($stats);
}

/**
 * Returns current database info in human readable view
 * 
 * @return string
 */
function zb_DBStatsRender() {
    $all = zb_DBGetStats();
    $result = '';

    $totalRows = 0;
    $totalSize = 0;
    $totalCount = 0;
    if (!empty($all)) {
        $cells = wf_TableCell(__('Table name'));
        $cells .= wf_TableCell(__('Rows'));
        $cells .= wf_TableCell(__('Size'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['name']);
            if (!empty($each['rows'])) {
                $dbrows = $each['rows'];
                $totalRows = $totalRows + $each['rows'];
                ;
            } else {
                $dbrows = 0;
            }

            $cells .= wf_TableCell($dbrows);
            if (!empty($each['size'])) {
                @$size = stg_convert_size($each['size']);
                $totalSize = $totalSize + $each['size'];
            } else {
                $size = '0 b';
            }

            $cells .= wf_TableCell($size, '', '', 'sorttable_customkey="' . $each['size'] . '"');
            $rows .= wf_TableRow($cells, 'row3');
            $totalCount++;
        }
        $result .= $rows;
        $result .= wf_tag('b') . __('Total') . ': ' . wf_tag('b', true) . ' ' . __('Tables') . ' ' . $totalCount . ' ' . __('Rows') . ' ' . $totalRows . ' / ' . __('Size') . ' ' . stg_convert_size($totalSize);
    }
    return ($result);
}

/**
 * Returns current database info in human readable view with ajax controls
 * 
 * @return string
 */
function zb_DBStatsRenderContainer() {
    global $ubillingDatabaseDriver;
    $messages = new UbillingMessageHelper();
    $result = '';
    $result .= wf_AjaxLoader();
    $result .= wf_AjaxLink('?module=report_sysload&ajaxdbstats=true', wf_img_sized('skins/icon_stats.gif', '', 16, 16) . ' ' . __('Database stats'), 'dbscontainer', false, 'ubButton');
    $result .= wf_AjaxLink('?module=report_sysload&ajaxdbcheck=true', wf_img_sized('skins/icon_repair.gif', '', 16, 16) . ' ' . __('Check database'), 'dbscontainer', false, 'ubButton');
    if (cfr('ROOT')) {
        $result .= wf_Link(DBmon::URL_ME, wf_img('skins/icon_time_small.png') . ' ' . __('Database monitor'), false, 'ubButton') . ' ';
        if (SQL_DEBUG) {
            $backUrl = '';
            if (!empty($_SERVER['REQUEST_URI'])) {
                $backUrl = '&back=' . base64_encode($_SERVER['REQUEST_URI']);
            }
            $result .= wf_Link('?module=sqldebug' . $backUrl, wf_img('skins/log_icon_small.png') . ' ' . __('All SQL queries log'), true, 'ubButton');
        }
    }

    $result .= $messages->getStyledMessage(__('Using MySQL PHP extension') . ': ' . $ubillingDatabaseDriver, 'info');
    $result .= wf_tag('br');
    $result .= wf_AjaxContainer('dbrepaircontainer');
    $result .= wf_tag('table', false, 'sortable', 'width="100%" border="0" id="dbscontainer"') . zb_DBStatsRender() . wf_tag('table', true);
    return ($result);
}

/**
 * checks database table state
 * 
 * @return string
 */
function zb_DBCheckTable($tablename) {
    $result = '';
    if (!empty($tablename)) {
        $query = "CHECK TABLE `" . $tablename . "`";
        $data = simple_query($query);
        if (!empty($data)) {
            $result = $data['Msg_text'];
        }
    }
    return ($result);
}

/**
 * Trys to repair corrupted database table
 * 
 * @param string $tableName
 * 
 * @return string 
 */
function zb_DBRepairTable($tableName) {
    $tableNameF = mysql_real_escape_string($tableName);
    $query = "REPAIR TABLE `" . $tableNameF . "`;";
    nr_query($query);
    log_register('DATABASE TABLE `' . $tableName . '` REPAIRED');

    $messages = new UbillingMessageHelper();
    $repairResult_q = "CHECK TABLE `" . $tableNameF . "`";
    $repairResult = simple_query($repairResult_q);
    $result = $messages->getStyledMessage(__('Database table') . ' `' . $tableName . '` ' . __('was repaired') . '. ' . __('Now table status is') . ' "' . $repairResult['Msg_text'] . '"', 'success');
    return ($result);
}

/**
 * Returns current database info in human readable view and table check
 * 
 * @return string
 */
function zb_DBCheckRender() {
    $all = zb_DBGetStats();
    if (!empty($all)) {
        $cells = wf_TableCell(__('Table name'));
        $cells .= wf_TableCell(__('Status'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['name']);
            $tableStatus = zb_DBCheckTable($each['name']);
            $fixControl = '';
            if ($tableStatus != 'OK') {
                $fixControl = ' ' . wf_AjaxLink('?module=report_sysload&dbrepairtable=' . $each['name'], wf_img('skins/icon_repair.gif', __('Fix')), 'dbrepaircontainer');
            }
            $cells .= wf_TableCell($tableStatus . $fixControl);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }
    return($rows);
}

/**
 * Destroy or flush table in database
 * 
 * @param $tablename  string table name 
 * @return void
 */
function zb_DBTableCleanup($tablename) {
    $tablename = vf($tablename);
    $method = 'DROP';
    if (!empty($tablename)) {
        $query = $method . " TABLE `" . $tablename . "`";
        nr_query($query);
        log_register("DBCLEANUP `" . $tablename . "`");
    }
}

/**
 * Shows database cleanup form
 * 
 * @return string
 */
function web_DBCleanupForm() {
    $oldLogs = zb_DBCleanupGetLogs();
    $oldDetailstat = zb_DBCleanupGetDetailstat();
    $cleanupData = $oldLogs + $oldDetailstat;
    $result = '';
    $totalRows = 0;
    $totalSize = 0;
    $totalCount = 0;

    $cells = wf_TableCell(__('Table name'));
    $cells .= wf_TableCell(__('Rows'));
    $cells .= wf_TableCell(__('Size'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($cleanupData)) {
        foreach ($cleanupData as $io => $each) {
            $cells = wf_TableCell($each['name']);
            $cells .= wf_TableCell($each['rows']);
            $cells .= wf_TableCell(stg_convert_size($each['size']), '', '', 'sorttable_customkey="' . $each['size'] . '"');
            $actlink = wf_JSAlert("?module=backups&tableclean=" . $each['name'], web_delete_icon(), 'Are you serious');
            $cells .= wf_TableCell($actlink);
            $rows .= wf_TableRow($cells, 'row5');
            $totalRows = $totalRows + $each['rows'];
            $totalSize = $totalSize + $each['size'];
            $totalCount = $totalCount + 1;
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result .= wf_tag('b') . __('Total') . ': ' . $totalCount . ' / ' . $totalRows . ' / ' . stg_convert_size($totalSize) . wf_tag('b', true);

    return ($result);
}

/**
 * Auto Cleans all deprecated data
 * 
 * @return string count of cleaned tables
 */
function zb_DBCleanupAutoClean() {
    $oldLogs = zb_DBCleanupGetLogs();
    $oldDstat = zb_DBCleanupGetDetailstat();
    $allClean = $oldLogs + $oldDstat;
    $counter = 0;
    if (!empty($allClean)) {
        foreach ($allClean as $io => $each) {
            zb_DBTableCleanup($each['name']);
            $counter++;
        }
    }
    return ($counter);
}

/**
 * UTF8-safe translit function
 * 
 * @param $string  string to be transliterated
 * @param $bool Save case state
 * 
 * @return string
 */
function zb_TranslitString($string, $caseSensetive = false) {

    if ($caseSensetive) {
        $replace = array(
            "'" => "",
            "`" => "",
            "а" => "a", "А" => "A",
            "б" => "b", "Б" => "B",
            "в" => "v", "В" => "V",
            "г" => "g", "Г" => "G",
            "д" => "d", "Д" => "D",
            "е" => "e", "Е" => "E",
            "ё" => "e", "Ё" => "E",
            "ж" => "zh", "Ж" => "Zh",
            "з" => "z", "З" => "Z",
            "и" => "y", "И" => "Y",
            "й" => "y", "Й" => "Y",
            "к" => "k", "К" => "K",
            "л" => "l", "Л" => "L",
            "м" => "m", "М" => "M",
            "н" => "n", "Н" => "N",
            "о" => "o", "О" => "O",
            "п" => "p", "П" => "P",
            "р" => "r", "Р" => "R",
            "с" => "s", "С" => "S",
            "т" => "t", "Т" => "T",
            "у" => "u", "У" => "U",
            "ф" => "f", "Ф" => "F",
            "х" => "h", "Х" => "H",
            "ц" => "c", "Ц" => "C",
            "ч" => "ch", "Ч" => "Ch",
            "ш" => "sh", "Ш" => "Sh",
            "щ" => "sch", "Щ" => "Sch",
            "ъ" => "", "Ъ" => "",
            "ы" => "y", "Ы" => "Y",
            "ь" => "", "Ь" => "",
            "э" => "e", "Э" => "E",
            "ю" => "yu", "Ю" => "Yu",
            "я" => "ya", "Я" => "Ya",
            "і" => "i", "І" => "I",
            "ї" => "yi", "Ї" => "Yi",
            "є" => "e", "Є" => "E",
            "ґ" => "g", "Ґ" => "G"
        );

        if (curlang() == 'ru') {
            $replace['и'] = 'i';
            $replace['И'] = 'I';
        }
    } else {
        $replace = array(
            "'" => "",
            "`" => "",
            "а" => "a", "А" => "a",
            "б" => "b", "Б" => "b",
            "в" => "v", "В" => "v",
            "г" => "g", "Г" => "g",
            "д" => "d", "Д" => "d",
            "е" => "e", "Е" => "e",
            "ё" => "e", "Ё" => "e",
            "ж" => "zh", "Ж" => "zh",
            "з" => "z", "З" => "z",
            "и" => "y", "И" => "y",
            "й" => "y", "Й" => "y",
            "к" => "k", "К" => "k",
            "л" => "l", "Л" => "l",
            "м" => "m", "М" => "m",
            "н" => "n", "Н" => "n",
            "о" => "o", "О" => "o",
            "п" => "p", "П" => "p",
            "р" => "r", "Р" => "r",
            "с" => "s", "С" => "s",
            "т" => "t", "Т" => "t",
            "у" => "u", "У" => "u",
            "ф" => "f", "Ф" => "f",
            "х" => "h", "Х" => "h",
            "ц" => "c", "Ц" => "c",
            "ч" => "ch", "Ч" => "ch",
            "ш" => "sh", "Ш" => "sh",
            "щ" => "sch", "Щ" => "sch",
            "ъ" => "", "Ъ" => "",
            "ы" => "y", "Ы" => "y",
            "ь" => "", "Ь" => "",
            "э" => "e", "Э" => "e",
            "ю" => "yu", "Ю" => "yu",
            "я" => "ya", "Я" => "ya",
            "і" => "i", "І" => "i",
            "ї" => "yi", "Ї" => "yi",
            "є" => "e", "Є" => "e",
            "ґ" => "g", "Ґ" => "g"
        );

        if (curlang() == 'ru') {
            $replace['и'] = 'i';
            $replace['И'] = 'i';
        }
    }
    return $str = iconv("UTF-8", "UTF-8//IGNORE", strtr($string, $replace));
}

/**
 * Rounds $value to $precision digits
 * 
 * @param   $value      Integer which to round
 * @param   $precision  Amount of digits after point
 * @return  float
 * 
 */
function web_roundValue($value, $precision = 2) {
    $precision = ( $precision < 0 ) ? 0 : $precision;
    $multiplier = pow(10, $precision);
    $rounded = (($value >= 0) ? ceil($value * $multiplier) : floor($value * $multiplier)) / $multiplier;
    return $rounded;
}

/**
 * Big values cash display formatting for better readability
 * 
 * @param float $cashValue
 * 
 * @return string
 */
function zb_CashBigValueFormat($cashValue) {
    return(number_format($cashValue, 0, '.', ' '));
}

/**
 * Returns array of year signups per month
 * 
 * @param int $year
 * @return array
 */
function zb_AnalyticsSignupsGetCountYear($year) {
    $year = vf($year, 3);
    $months = months_array();
    $result = array();
    $tmpArr = array();

    $query = "SELECT * from `userreg` WHERE `date` LIKE '" . $year . "-%'";
    $all = simple_queryall($query);

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $time = strtotime($each['date']);
            $month = date("m", $time);
            if (isset($tmpArr[$month])) {
                $tmpArr[$month]['count'] ++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }


    foreach ($months as $eachmonth => $monthname) {
        $result[$eachmonth] = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
    }
    return($result);
}

/**
 * Returns singup requests for some year per month
 * 
 * @param int $year
 * @return array
 */
function zb_AnalyticsSigReqGetCountYear($year) {
    $year = vf($year, 3);
    $months = months_array();
    $result = array();
    $tmpArr = array();

    $query = "SELECT * from `sigreq` WHERE `date` LIKE '" . $year . "-%'";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $time = strtotime($each['date']);
            $month = date("m", $time);
            if (isset($tmpArr[$month])) {
                $tmpArr[$month]['count'] ++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }

    foreach ($months as $eachmonth => $monthname) {
        $monthcount = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
        $result[$eachmonth] = $monthcount;
    }
    return($result);
}

/**
 * Returns array of tickets recieved during the year or month, or something else
 * 
 * @param int $datefilter - format like "year" or "year-month" or "year-month-day"
 * 
 * @return array as month=>count
 */
function zb_AnalyticsTicketingGetCountYear($datefilter) {
    $datefilter = mysql_real_escape_string($datefilter);
    $months = months_array();
    $result = array();
    $tmpArr = array();

    $query = "SELECT * from `ticketing` WHERE `date` LIKE '" . $datefilter . "-%' AND `from` != 'NULL';";

    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $time = strtotime($each['date']);
            $month = date("m", $time);
            if (isset($tmpArr[$month])) {
                $tmpArr[$month]['count'] ++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }

    foreach ($months as $eachmonth => $monthname) {
        $monthcount = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
        $result[$eachmonth] = $monthcount;
    }
    return($result);
}

/**
 * Returns array of planned tasks per year
 * 
 * @param int $year
 * @return array
 */
function zb_AnalyticsTaskmanGetCountYear($year) {
    $year = vf($year, 3);
    $months = months_array();
    $result = array();
    $tmpArr = array();

    $query = "SELECT * from `taskman` WHERE `date` LIKE '" . $year . "-%'";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $time = strtotime($each['date']);
            $month = date("m", $time);
            if (isset($tmpArr[$month])) {
                $tmpArr[$month]['count'] ++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }


    foreach ($months as $eachmonth => $monthname) {
        $monthcount = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
        $result[$eachmonth] = $monthcount;
    }
    return($result);
}

/**
 * Returns graph with dynamics if ARPU change during the year
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsArpuMonthGraph($year) {
    global $ubillingConfig;
    $year = vf($year, 3);
    $months = months_array();
    $tmpArr = array();
    $chartData = array(0 => array(__('Month'), __('ARPU')));
    $chartOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";

// Exclude some Cash types ID from query
    $dopWhere = '';
    if ($ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')) {
        $exIdArr = array_map('trim', explode(',', $ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')));
        $exIdArr = array_filter($exIdArr);
// Create and WHERE to query
        if (!empty($exIdArr)) {
            $dopWhere = ' AND ';
            $dopWhere .= ' `cashtypeid` != ' . implode(' AND `cashtypeid` != ', $exIdArr);
        }
    }

    $query = "SELECT * from `payments` WHERE `date` LIKE '" . $year . "-%' AND `summ` > 0 " . $dopWhere;
    $allPayments = simple_queryall($query);

    if (!empty($allPayments)) {
        foreach ($allPayments as $io => $each) {
            $time = strtotime($each['date']);
            $month = date("m", $time);
            if (isset($tmpArr[$month])) {
                $tmpArr[$month]['count'] ++;
                $tmpArr[$month]['summ'] = $tmpArr[$month]['summ'] + $each['summ'];
            } else {
                $tmpArr[$month]['count'] = 1;
                $tmpArr[$month]['summ'] = $each['summ'];
            }
        }
    }

    foreach ($months as $eachmonth => $monthname) {
        $month_summ = isset($tmpArr[$eachmonth]) ? $tmpArr[$eachmonth]['summ'] : 0;
        $paycount = isset($tmpArr[$eachmonth]) ? $tmpArr[$eachmonth]['count'] : 0;
        if ($paycount != 0) {
            $arpu = round($month_summ / $paycount, 2);
        } else {
            $arpu = 0;
        }
        $chartData[] = array($year . '-' . $eachmonth, $arpu);
    }

    $result = wf_gchartsLine($chartData, __('Dynamics of changes in ARPU for the year'), '100%', '400px', $chartOptions) . wf_delimiter();

    return ($result);
}

/**
 * Returns graph of per month payment dynamics
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsPaymentsMonthGraph($year) {
    global $ubillingConfig;
    $year = vf($year, 3);
    $months = months_array();
    $tmpArr = array();
    $chartData = array(0 => array(__('Month'), __('Payments count'), __('Cash')));

    $chartOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";

// Exclude some Cash types ID from query
    $dopWhere = '';
    if ($ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')) {
        $exIdArr = array_map('trim', explode(',', $ubillingConfig->getAlterParam('REPORT_FINANCE_IGNORE_ID')));
        $exIdArr = array_filter($exIdArr);
// Create and WHERE to query
        if (!empty($exIdArr)) {
            $dopWhere = ' AND ';
            $dopWhere .= ' `cashtypeid` != ' . implode(' AND `cashtypeid` != ', $exIdArr);
        }
    }

    $query = "SELECT * from `payments` WHERE `date` LIKE '" . $year . "-%' AND `summ` > 0 " . $dopWhere;
    $allPayments = simple_queryall($query);

    if (!empty($allPayments)) {
        foreach ($allPayments as $io => $each) {
            $time = strtotime($each['date']);
            $month = date("m", $time);
            if (isset($tmpArr[$month])) {
                $tmpArr[$month]['count'] ++;
                $tmpArr[$month]['summ'] = $tmpArr[$month]['summ'] + $each['summ'];
            } else {
                $tmpArr[$month]['count'] = 1;
                $tmpArr[$month]['summ'] = $each['summ'];
            }
        }
    }

    foreach ($months as $eachmonth => $monthname) {
        $month_summ = isset($tmpArr[$eachmonth]) ? $tmpArr[$eachmonth]['summ'] : 0;
        $paycount = isset($tmpArr[$eachmonth]) ? $tmpArr[$eachmonth]['count'] : 0;
        $chartData[] = array($year . '-' . $eachmonth, $paycount, $month_summ);
    }

    $result = wf_gchartsLine($chartData, __('Dynamics of cash flow for the year'), '100%', '400px', $chartOptions) . wf_delimiter();
    return ($result);
}

/**
 * Returns graph of signups per year dynamics
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsSignupsMonthGraph($year) {
    $allmonths = months_array();
    $yearcount = zb_AnalyticsSignupsGetCountYear($year);
    $chartData = array(0 => array(__('Month'), __('Signups')));

    $chartOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";

    foreach ($yearcount as $eachmonth => $count) {
        $chartData[] = array($year . '-' . $eachmonth, $count);
    }

    $result = wf_gchartsLine($chartData, __('Dynamics of change signups of the year'), '100%', '400px', $chartOptions) . wf_delimiter();
    return ($result);
}

/**
 * Returns graph of received signup requests
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsSigReqMonthGraph($year) {
    $allmonths = months_array();
    $yearcount = zb_AnalyticsSigReqGetCountYear($year);

    $chartData = array(0 => array(__('Month'), __('Signup requests')));

    $chartOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";


    foreach ($yearcount as $eachmonth => $count) {
        $chartData[] = array($year . '-' . $eachmonth, $count);
    }

    $result = wf_gchartsLine($chartData, __('Signup requests received during the year'), '100%', '400px', $chartOptions) . wf_delimiter();
    return ($result);
}

/**
 * Returns graph of received user tickets in helpdesk
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsTicketingMonthGraph($year) {
    $allmonths = months_array();
    $yearcount = zb_AnalyticsTicketingGetCountYear($year);
    $chartData = array(0 => array(__('Month'), __('Ticket')));

    $chartOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";


    foreach ($yearcount as $eachmonth => $count) {
        $chartData[] = array($year . '-' . $eachmonth, $count);
    }

    $result = wf_gchartsLine($chartData, __('Ticketing activity during the year'), '100%', '400px', $chartOptions) . wf_delimiter();
    return ($result);
}

/**
 * Returns graph of planned tasks in taskmanager
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsTaskmanMonthGraph($year) {
    $allmonths = months_array();
    $yearcount = zb_AnalyticsTaskmanGetCountYear($year);
    $chartData = array(0 => array(__('Month'), __('Jobs')));
    $chartOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";


    foreach ($yearcount as $eachmonth => $count) {
        $chartData[] = array($year . '-' . $eachmonth, $count);
    }


    $result = wf_gchartsLine($chartData, __('Task manager activity during the year'), '100%', '400px', $chartOptions) . wf_delimiter();
    return ($result);
}

/**
 * Returns all analytics report charts
 * 
 * @param int $year
 * @return string
 */
function web_AnalyticsAllGraphs($year) {
    $graphs = web_AnalyticsArpuMonthGraph($year);
    $graphs .= web_AnalyticsPaymentsMonthGraph($year);
    $graphs .= web_AnalyticsSignupsMonthGraph($year);
    $graphs .= web_AnalyticsSigReqMonthGraph($year);
    $graphs .= web_AnalyticsTicketingMonthGraph($year);
    $graphs .= web_AnalyticsTaskmanMonthGraph($year);
    return ($graphs);
}

/**
 * Initializes file download procedure
 * 
 * @param string $filePath
 * @param string $contentType
 * @throws Exception
 */
function zb_DownloadFile($filePath, $contentType = '') {
    if (!empty($filePath)) {
        if (file_exists($filePath)) {
            log_register("DOWNLOAD FILE `" . $filePath . "`");

            if (($contentType == '') OR ( $contentType == 'default')) {
                $contentType = 'application/octet-stream';
            } else {
//additional content types
                if ($contentType == 'docx') {
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                }

                if ($contentType == 'csv') {
                    $contentType = 'text/csv; charset=Windows-1251';
                }

                if ($contentType == 'text') {
                    $contentType = 'text/plain;';
                }

                if ($contentType == 'jpg') {
                    $contentType = 'Content-Type: image/jpeg';
                }
            }

            header('Content-Type: ' . $contentType);
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($filePath) . "\"");
            header("Content-Description: File Transfer");
            header("Accept-Ranges: 'bytes'");
            header("Content-Length: " . filesize($filePath));


            flush(); // this doesn't really matter.
            $fp = fopen($filePath, "r");
            while (!feof($fp)) {
                echo fread($fp, 65536);
                flush(); // this is essential for large downloads
            }
            fclose($fp);
            die();
        } else {
            throw new Exception('DOWNLOAD_FILEPATH_NOT_EXISTS');
        }
    } else {
        throw new Exception('DOWNLOAD_FILEPATH_EMPTY');
    }
}

/**
 * Returns current stargazer DB version
 * =<2.408 - 0
 * >=2.409 - 1+
 * 
 * @return int
 */
function zb_CheckDbSchema() {
    if (zb_CheckTableExists('info')) {
        $query = "SELECT `version` from `info`";
        $result = simple_query($query);
        $result = $result['version'];
    } else {
        $result = 0;
    }
    return ($result);
}

/**
 * Returns swtitch and port assign form. Includes internal controller.
 * 
 * @param string $login
 * @param array $allswitches
 * @param array $allportassigndata
 * @param int $suggestswitchid
 * @param int $suggestswitchport
 * @return string
 */
function web_SnmpSwitchControlForm($login, $allswitches, $allportassigndata, $suggestswitchid = '', $suggestswitchport = '') {
    $login = mysql_real_escape_string($login);

    $switcharr = array();
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            $switcharr[$eachswitch['id']] = $eachswitch['ip'] . ' - ' . $eachswitch['location'];
        }
    }
//getting current data
    $assignData = array();
    if (isset($allportassigndata[$login])) {
        $assignData = $allportassigndata[$login];
    }
    $sameUsers = '';

    if (!empty($assignData)) {
        $currentSwitchPort = $assignData['port'];
        $currentSwitchId = $assignData['switchid'];
    } else {
        $currentSwitchPort = '';
        $currentSwitchId = '';
    }


//control form construct
    $inputs = wf_HiddenInput('swassignlogin', $login);
    $inputs .= wf_Selector('swassignswid', $switcharr, __('Switch'), $suggestswitchid, true);
    $inputs .= wf_TextInput('swassignswport', __('Port'), $suggestswitchport, false, '2');
    $inputs .= wf_CheckInput('swassigndelete', __('Delete'), true, false);
    $inputs .= wf_Submit('Save');
    $controlForm = wf_Form('', "POST", $inputs, 'glamour');
//form end

    $switchAssignController = wf_modal(web_edit_icon(), __('Switch port assign'), $controlForm, '', '450', '200');


    $cells = wf_TableCell(__('Switch'), '30%', 'row2');
    $cells .= wf_TableCell(@$switcharr[$currentSwitchId]);
    $rows = wf_TableRow($cells, 'row3');
    $cells = wf_TableCell(__('Port'), '30%', 'row2');
    $cells .= wf_TableCell($currentSwitchPort);
    $rows .= wf_TableRow($cells, 'row3');
    $cells = wf_TableCell(__('Change'), '30%', 'row2');
    $cells .= wf_TableCell($switchAssignController);
    $rows .= wf_TableRow($cells, 'row3');

    $result = wf_TableBody($rows, '100%', '0');

//update subroutine
    if (wf_CheckPost(array('swassignlogin', 'swassignswid', 'swassignswport'))) {
        $newswid = vf($_POST['swassignswid'], 3);
        $newport = vf($_POST['swassignswport'], 3);
        nr_query("DELETE from `switchportassign` WHERE `login`='" . $_POST['swassignlogin'] . "'");
        nr_query("INSERT INTO `switchportassign` (`id` ,`login` ,`switchid` ,`port`) VALUES (NULL , '" . $_POST['swassignlogin'] . "', '" . $newswid . "', '" . $newport . "');");
        log_register("CHANGE SWITCHPORT (" . $login . ") ON SWITCHID [" . $newswid . "] PORT [" . $newport . "]");
        rcms_redirect('?module=switchpoller&switchid=' . $suggestswitchid);
    }
//delete subroutine
    if (isset($_POST['swassigndelete'])) {
        nr_query("DELETE from `switchportassign` WHERE `login`='" . $_POST['swassignlogin'] . "'");
        log_register("DELETE SWITCHPORT (" . $login . ")");
        rcms_redirect('?module=switchpoller&switchid=' . $suggestswitchid);
    }
    return ($result);
}

/**
 * Returns array of Stargazer tariffs payment periods as tariffname=>period
 * 
 * @return array
 */
function zb_TariffGetPeriodsAll() {
    $result = array();
    $dbSchema = zb_CheckDbSchema();
    if ($dbSchema > 0) {
//stargazer >= 2.409
        $query = "SELECT `name`,`period` from `tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $eachtariff) {
                $result[$eachtariff['name']] = $eachtariff['period'];
            }
        }
    } else {
//stargazer 2.408
        $query = "SELECT `name` from `tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $eachtariff) {
                $result[$eachtariff['name']] = 'month';
            }
        }
    }


    return ($result);
}

/**
 * logs succeful self credit fact into database
 *
 * @param  string $login existing users login
 *
 * @return void
 */
function zb_CreditLogPush($login) {
    $login = mysql_real_escape_string($login);
    $date = curdatetime();
    $query = "INSERT INTO `zbssclog` (`id` , `date` , `login` ) VALUES ( NULL , '" . $date . "', '" . $login . "');";
    nr_query($query);
}

/**
 * Checks if user use SC module without previous payment and returns false if used or true if feature available
 *
 * @param  string $login existing users login
 *
 * @return bool
 */
function zb_CreditLogCheckHack($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT `note` FROM `payments` WHERE `login` = '" . $login . "' AND (`summ` > 0 OR `note` = 'SCFEE') ORDER BY `payments`.`date` DESC LIMIT 1";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } elseif (!empty($data) AND $data['note'] != 'SCFEE') {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Checks is user tariff allowed for use of credit feature
 *
 * @param array  $sc_allowed
 * @param string $usertariff
 * @return bool
 */
function zb_CreditCheckAllowed($sc_allowed, $usertariff) {
    $result = true;
    if (!empty($sc_allowed)) {
        if (isset($sc_allowed[$usertariff])) {
            $result = true;
        } else {
            $result = false;
        }
    }
    return ($result);
}

/**
 * checks is user current month use SC module and returns false if used or true if feature available
 * 
 * @param  string $login existing users login
 * 
 * @return bool
 */
function zb_CreditLogCheckMonth($login) {
    $login = mysql_real_escape_string($login);
    $pattern = date("Y-m");
    $query = "SELECT `id` from `zbssclog` WHERE `login` LIKE '" . $login . "' AND `date` LIKE '" . $pattern . "%';";
    $data = simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns all users used SC module this month
 * 
 * @return array
 */
function zb_CreditLogGetAll() {
    $result = array();
    $pattern = date("Y-m");
    $query = "SELECT `login`,`id`,`date` from `zbssclog` WHERE `date` LIKE '" . $pattern . "%';";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['date'];
        }
    }
    return ($result);
}

/**
 * returns list of available free radius clients/nases
 * 
 * @return string
 */
function web_FreeRadiusListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `radius_clients`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('NAS name'));
        $cells .= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells .= wf_TableCell($each['shortname']);
            $cells .= wf_TableCell($each['secret']);
            $rows .= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

/**
 * returns one-click credit set form for profile
 * 
 * 
 * @param string $login existing callback user login
 * @param float  $cash  current user balance
 * @param int    $credit current user credit
 * @param string $userTariff current user tariff
 * @param int    $easycreditoption current state of EASY_CREDIT option
 * 
 * @return string
 */
function web_EasyCreditForm($login, $cash, $credit, $userTariff, $easycreditoption) {
/////////////////internal controller
    if (wf_CheckPost(array('easycreditlogin', 'easycreditlimit', 'easycreditexpire'))) {
        global $billing;
        $setCredit = vf($_POST['easycreditlimit']);
        $setLogin = mysql_real_escape_string($_POST['easycreditlogin']);
        $setExpire = mysql_real_escape_string($_POST['easycreditexpire']);
        if (zb_checkDate($setExpire)) {
            if (zb_checkMoney($setCredit)) {
//set credit
                $billing->setcredit($setLogin, $setCredit);
                log_register('CHANGE Credit (' . $setLogin . ') ON ' . $setCredit);
//set credit expire date
                $billing->setcreditexpire($setLogin, $setExpire);
                log_register('CHANGE CreditExpire (' . $setLogin . ') ON ' . $setExpire);
                rcms_redirect('?module=userprofile&username=' . $setLogin);
            } else {
                show_error(__('Wrong format of money sum'));
                log_register('EASYCREDIT FAIL WRONG SUMM `' . $setCredit . '`');
            }
        } else {
            show_error(__('Wrong date format'));
            log_register('EASYCREDIT FAIL DATEFORMAT `' . $setExpire . '`');
        }
    }


    $allTariffsData = zb_TariffGetAllData();

    @$tariffPrice = (isset($allTariffsData[$userTariff])) ? $allTariffsData[$userTariff]['Fee'] : 0;
    $tariffPeriod = 'month';
    if ($tariffPrice) {
//some valid tariff
        if (isset($allTariffsData[$userTariff]['period'])) {
            $tariffPeriod = $allTariffsData[$userTariff]['period'];
        }
    }

    if ($cash >= '-' . $credit) {
        $creditProposal = $tariffPrice;
        $creditNote = __('The amount of money in the account at the moment is sufficient to provide the service. It is therefore proposed to set a credit limit on the fee of the tariff.');
//daily tariffs fix for active users
        if ($tariffPeriod == 'day') {
            $creditProposal = $tariffPrice * $easycreditoption;
            $creditNote = __('The amount of money in the account at the moment is sufficient to provide the service. It is therefore proposed to set a credit limit on the fee of the tariff.');
            $creditNote .= ' + ' . $easycreditoption . ' ' . __('days') . '.';
        }
    } else {
        $creditProposal = abs($cash);
        $creditNote = __('At the moment the account have debt. It is proposed to establish credit in its size.');
//daily tariffs fix for debtors
        if ($tariffPeriod == 'day') {
            $creditProposal = abs($cash) + ($tariffPrice * $easycreditoption);
            $creditNote = __('At the moment the account have debt. It is proposed to establish credit in its size.');
            $creditNote .= ' + ' . $easycreditoption . ' ' . __('days') . '.';
        }

//small and ugly hack to avoid precision issues with floating point values
        if (ispos($creditProposal, '.')) {
            $creditProposal = $creditProposal + 1;
            $creditProposal = round($creditProposal);
        }
    }

//calculate credit expire date
    $nowTimestamp = time();
    $creditSeconds = ($easycreditoption * 86400); //days*secs
    $creditOffset = $nowTimestamp + $creditSeconds;
    $creditExpireDate = date("Y-m-d", $creditOffset);

//construct form
    $controlIcon = wf_tag('img', false, '', 'src="skins/icon_calendar.gif" height="10"');
    $inputs = '';
    $inputs .= wf_HiddenInput('easycreditlogin', $login);
    $inputs .= wf_TextInput('easycreditlimit', '', $creditProposal, false, 5, 'finance') . __('credit limit') . ' ';
    $inputs .= __('until');
    $inputs .= wf_DatePickerPreset('easycreditexpire', $creditExpireDate);
    $inputs .= wf_Submit(__('Save'));

    $form = wf_Form('?module=userprofile&username=' . $login, 'POST', $inputs, 'glamour');
    $form .= $creditNote;

    $result = wf_modal($controlIcon, __('Change') . ' ' . __('credit limit'), $form, '', '500', '180');

    return ($result);
}

/**
 * Returns custom report sysload scripts output
 * 
 * @param string $scriptoption option from alter.ini -> SYSLOAD_CUSTOM_SCRIPTS
 * 
 * @return string
 */
function web_ReportSysloadCustomScripts($scriptoption) {
    $result = '';
//internal script ajax handling
    if (wf_CheckGet(array('ajxcscrun'))) {
        $runpath = base64_decode($_GET['ajxcscrun']);
        if (!empty($runpath)) {
            $script_result = wf_tag('pre') . shell_exec($runpath) . wf_tag('pre', true);
            die($script_result);
        }
    }
    $scriptdata = explode(',', $scriptoption);
    if (!empty($scriptdata)) {
        $result .= wf_AjaxLoader();
        foreach ($scriptdata as $io => $eachscript) {
            $curScript = explode(':', $eachscript);
            if (!empty($curScript)) {
                $name = $curScript[0];
                $path = $curScript[1];
                $result .= wf_AjaxLink('?module=report_sysload&ajxcscrun=' . base64_encode($path), $name, 'custommoncontainder', false, 'ubButton');
            }
        }
        $result .= wf_delimiter();
        $result .= wf_tag('span', false, '', 'id="custommoncontainder"') . wf_tag('span', true);
    }
    return ($result);
}

/**
 * Native XML parser function
 * 
 * @param string $contents
 * @param int $get_attributes
 * @param string $priority
 * @return array
 */
function zb_xml2array($contents, $get_attributes = 1, $priority = 'tag') {
    if (!$contents)
        return array();

    if (!function_exists('xml_parser_create')) {
        print "'xml_parser_create()' function not found!";
        return array();
    }

//Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents), $xml_values);
    xml_parser_free($parser);

    if (!$xml_values)
        return; //Hmm...
//Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();

    $current = &$xml_array; //Refference
//Go through the tags.
    $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
    foreach ($xml_values as $data) {
        unset($attributes, $value); //Remove existing values, or there will be trouble
//This command will extract these variables into the foreach scope
// tag(string), type(string), level(int), attributes(array).
        extract($data); //We could use the array by itself, but this cooler.

        $result = array();
        $attributes_data = array();

        if (isset($value)) {
            if ($priority == 'tag')
                $result = $value;
            else
                $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }

//Set the attributes too.
        if (isset($attributes) and $get_attributes) {
            foreach ($attributes as $attr => $val) {
                if ($priority == 'tag')
                    $attributes_data[$attr] = $val;
                else
                    $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }

//See tag status and do the needed.
        if ($type == "open") {//The starting of the tag '<tag>'
            $parent[$level - 1] = &$current;
            if (!is_array($current) or ( !in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;

                $current = &$current[$tag];
            } else { //There was another element with the same tag name
                if (isset($current[$tag][0])) {//If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level] ++;
                } else {//This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array($current[$tag], $result); //This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag . '_' . $level] = 2;

                    if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = &$current[$tag][$last_item_index];
            }
        } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
//See if the key is already taken.
            if (!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
            } else { //If taken, put all things inside a list(array)
                if (isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
// ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                    if ($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level] ++;
                } else { //If it is not an array...
                    $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes) {
                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }

                        if ($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level] ++; //0 and 1 index is already taken
                }
            }
        } elseif ($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level - 1];
        }
    }

    return($xml_array);
}

/**
 * Checks is tariff protected by some user usage?
 * 
 * @param string $tariffname    Existing stargazer tariff name
 * @return bool
 */
function zb_TariffProtected($tariffname) {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $tariffname = mysql_real_escape_string($tariffname);
    $query = "SELECT `login` from `users` WHERE `Tariff`='" . $tariffname . "' OR `TariffChange`='" . $tariffname . "' LIMIT 1;";
    $raw = simple_query($query);
    $result = (empty($raw)) ? false : true;
    if (!$result) {
        if (@$altCfg['DEALWITHIT_ENABLED']) {
            $dwi = new nya_dealwithit();
            $dwi->where('action', '=', 'tariffchange');
            $dwi->where('param', '=', ubRouting::filters($tariffname, 'mres'));
            $moveCount = $dwi->getAll();
            $result = (empty($moveCount)) ? false : true;
        }
    }
    return ($result);
}

/**
 * Checks PHP loaded modules
 * 
 * @return string
 */
function zb_CheckPHPExtensions() {
    $result = '';
    if (file_exists(CONFIG_PATH . 'optsextcfg')) {
        $allRequired = file_get_contents(CONFIG_PATH . 'optsextcfg');
        if (!empty($allRequired)) {
            $allRequired = explodeRows($allRequired);
            if (!empty($allRequired)) {
                foreach ($allRequired as $io => $each) {
                    if (!empty($each)) {
                        $each = trim($each);
                        $notice = '';
                        if (!extension_loaded($each)) {
                            switch ($each) {
                                case 'mysql':
                                    $notice = ' ' . __('Deprecated in') . '  PHP 7.0';
                                    break;
                                case 'ereg':
                                    $notice = ' ' . __('Deprecated in') . '  PHP 7.0';
                                    break;
                                case 'memcache':
                                    $notice = ' ' . __('Deprecated in') . '  PHP 7.0';
                                    break;
                                case 'xhprof':
                                    $notice = ' ' . __('May require manual installation');
                                    break;
                            }
                            $result .= wf_tag('span', false, 'alert_error') . __('PHP extension not found') . ': ' . $each . $notice . wf_tag('span', true);
                        } else {
                            $result .= wf_tag('span', false, 'alert_success') . __('PHP extension loaded') . ': ' . $each . wf_tag('span', true);
                        }
                    }
                }
            }
        }
    } else {
        $result .= wf_tag('span', false, 'alert_error') . __('Strange exeption') . ': OPTSEXTCFG_NOT_FOUND' . wf_tag('span', true);
    }
    return ($result);
}

/**
 * Validate a Gregorian date 
 * 
 * @param string $date Date in MySQL format
 * @return bool
 */
function zb_checkDate($date) {
    $explode = explode('-', $date);
    @$year = $explode[0];
    @$month = $explode[1];
    @$day = $explode[2];
    $result = @checkdate($month, $day, $year);
    return ($result);
}

/**
 * Cuts last char of string
 * 
 * @param string $string
 * @return string
 */
function zb_CutEnd($string) {
    $string = substr($string, 0, -1);
    return ($string);
}

/**
 * Returns memcached usage stats
 * 
 * @global object $ubillingConfig
 * @return string
 */
function web_MemCachedRenderStats() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $result = '';
    $memcachedHost = 'localhost';
    $memcachedPort = 11211;
    $cacheEfficiency = '';

    if (isset($altCfg['MEMCACHED_SERVER'])) {
        $memcachedHost = $altCfg['MEMCACHED_SERVER'];
    }
    if (isset($altCfg['MEMCACHED_PORT'])) {
        $memcachedPort = $altCfg['MEMCACHED_PORT'];
    }
    $memcached = new Memcached();
    $memcached->addServer($memcachedHost, $memcachedPort);
    $rawStats = $memcached->getStats();

    $cells = wf_TableCell(__('Parameter'));
    $cells .= wf_TableCell(__('Value'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($rawStats)) {
        if (isset($rawStats[$memcachedHost . ':' . $memcachedPort])) {
            foreach ($rawStats[$memcachedHost . ':' . $memcachedPort] as $io => $each) {
                $cells = wf_TableCell($io);
                $cells .= wf_TableCell($each);
                $rows .= wf_TableRow($cells, 'row3');
            }


//cache efficiency calc
            if ((isset($rawStats[$memcachedHost . ':' . $memcachedPort]['get_hits'])) AND ( isset($rawStats[$memcachedHost . ':' . $memcachedPort]['get_misses']))) {
                $cacheHits = $rawStats[$memcachedHost . ':' . $memcachedPort]['get_hits'];
                $cacheMisses = $rawStats[$memcachedHost . ':' . $memcachedPort]['get_misses'];
                $cacheTotal = $cacheHits + $cacheMisses;
                $messages = new UbillingMessageHelper();
                $cacheEfficiency = $messages->getStyledMessage(__('Cache efficiency') . ': ' . zb_PercentValue($cacheTotal, $cacheHits) . '%', 'success');
            }
        }
    }

    $result .= wf_TableBody($rows, '100%', 0, '');
    $result .= $cacheEfficiency;
    return ($result);
}

/**
 * Returns redis usage stats
 * 
 * @global object $ubillingConfig
 * @return string
 */
function web_RedisRenderStats() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $result = '';
    $cacheEfficiency = '';
    $redisHost = 'localhost';
    $redisdPort = 6379;
    if (isset($altCfg['REDIS_SERVER'])) {
        $redisHost = $altCfg['REDIS_SERVER'];
    }
    if (isset($altCfg['REDIS_PORT'])) {
        $redisdPort = $altCfg['REDIS_PORT'];
    }
    $redis = new Redis();
    $redis->connect($redisHost, $redisdPort);
    $rawStats = $redis->info();
    $cells = wf_TableCell(__('Parameter'));
    $cells .= wf_TableCell(__('Value'));
    $rows = wf_TableRow($cells, 'row1');
    if (!empty($rawStats)) {
        foreach ($rawStats as $param => $value) {
            $cells = wf_TableCell($param);
            $cells .= wf_TableCell($value);
            $rows .= wf_TableRow($cells, 'row3');
        }

//cache efficiency calc
        if ((isset($rawStats['keyspace_hits'])) AND ( isset($rawStats['keyspace_misses']))) {
            $cacheHits = $rawStats['keyspace_hits'];
            $cacheMisses = $rawStats['keyspace_misses'];
            $cacheTotal = $cacheHits + $cacheMisses;
            $messages = new UbillingMessageHelper();
            $cacheEfficiency = $messages->getStyledMessage(__('Cache efficiency') . ': ' . zb_PercentValue($cacheTotal, $cacheHits) . '%', 'success');
        }
    }
    $result .= wf_TableBody($rows, '100%', 0, '');
    $result .= $cacheEfficiency;
    return ($result);
}

/**
 * Calculates percent value
 * 
 * @param float $sum
 * @param float $percent
 * 
 * @return float
 */
function zb_Percent($sum, $percent) {
// и не надо ржать, я реально не могу запомнить чего куда делить и умножать
    $result = $percent / 100 * $sum;
    return ($result);
}

/**
 * Counts percentage between two values
 * 
 * @param float $valueTotal
 * @param float $value
 * 
 * @return float
 */
function zb_PercentValue($valueTotal, $value) {
    $result = 0;
    if ($valueTotal != 0) {
        $result = round((($value * 100) / $valueTotal), 2);
    }
    return ($result);
}

/**
 * Checks is time between some other time ranges?
 * 
 * @param string $fromTime start time (format hh:mm OR hh:mm:ss with seconds)
 * @param string $toTime end time
 * @param string $checkTime time to check
 * @param bool $seconds 
 * 
 * @return bool
 */
function zb_isTimeBetween($fromTime, $toTime, $checkTime, $seconds = false) {
    if ($seconds) {
        $formatPostfix = ':s';
    } else {
        $formatPostfix = '';
    }
    $checkTime = strtotime($checkTime);
    $checkTime = date("H:i" . $formatPostfix, $checkTime);
    $f = DateTime::createFromFormat('!H:i' . $formatPostfix, $fromTime);
    $t = DateTime::createFromFormat('!H:i' . $formatPostfix, $toTime);
    $i = DateTime::createFromFormat('!H:i' . $formatPostfix, $checkTime);
    if ($f > $t) {
        $t->modify('+1 day');
    }
    return ($f <= $i && $i <= $t) || ($f <= $i->modify('+1 day') && $i <= $t);
}

/**
 * Checks is date between some other date ranges?
 * 
 * @param string $fromDate start date (format Y-m-d)
 * @param string $toDate end date
 * @param string $checkDate date to check
 * @param bool $seconds 
 * 
 * @return bool
 */
function zb_isDateBetween($fromDate, $toDate, $checkDate) {
    $result = false;
    $fromDate = strtotime($fromDate);
    $toDate = strtotime($toDate);
    $checkDate = strtotime($checkDate);
    $checkDate = date("Y-m-d", $checkDate);
    $checkDate = strtotime($checkDate);
    if ($checkDate >= $fromDate AND $checkDate <= $toDate) {
        $result = true;
    }
    return($result);
}

/**
 * Renders time duration in seconds into formatted human-readable view
 *      
 * @param int $seconds
 * 
 * @return string
 */
function zb_formatTime($seconds) {
    $init = $seconds;
    $days = floor($seconds / 86400);
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $seconds = $seconds % 60;

    if ($init < 3600) {
//less than 1 hour
        if ($init < 60) {
//less than minute
            $result = $seconds . ' ' . __('sec.');
        } else {
//more than one minute
            $result = $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
    } else {
        if ($init < 86400) {
//more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        } else {
            $hoursLeft = $hours - ($days * 24);
            $result = $days . ' ' . __('days') . ' ' . $hoursLeft . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
    }
    return ($result);
}

/**
 * Renders list of loaded modules
 * 
 * @global object $system
 * 
 * @return string
 */
function zb_ListLoadedModules() {
    $result = '';
    $moduleCount = 0;
    $rightsCount = 0;
    global $system;
    $cells = wf_TableCell(__('Module'));
    $cells .= wf_TableCell(__('Author'));
    $cells .= wf_TableCell(__('Rights generated'));
    $rows = wf_TableRow($cells, 'row1');

    foreach ($system->modules as $type => $modules) {
        if ($type == 'main') {
            foreach ($modules as $module => $moduledata) {
                $moduleRights = '';
                if (!empty($moduledata['rights'])) {
                    foreach ($moduledata['rights'] as $right => $rightdesc) {
                        $moduleRights .= ' ' . wf_tag('abbr', false, '', 'title="' . $rightdesc . '"') . $right . wf_tag('abbr', true) . ',';
                        $rightsCount++;
                    }
                    $moduleRights = zb_CutEnd($moduleRights);
                }
                $cells = wf_TableCell($moduledata['title']);
                $cells .= wf_TableCell($moduledata['copyright']);
                $cells .= wf_TableCell($moduleRights);
                $rows .= wf_TableRow($cells, 'row3');
                $moduleCount++;
            }
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    $result .= __('Total') . ': ' . $moduleCount . wf_tag('br');
    $result .= __('Rights generated') . ': ' . $rightsCount;
    return ($result);
}

/**
 * Returns current cache info in human readable view with ajax controls
 * 
 * @return string
 */
function zb_ListCacheInformRenderContainer() {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $messages = new UbillingMessageHelper();
    $result = '';
    $result .= wf_AjaxLoader();
    $result .= wf_AjaxLink('?module=report_sysload&ajaxcacheinfo=true', wf_img('skins/icon_cache.png') . ' ' . __('Cache information'), 'cachconteiner', false, 'ubButton');
    if ($alterconf['UBCACHE_STORAGE'] == 'memcached') {
        $result .= wf_AjaxLink('?module=report_sysload&ajaxmemcachedstats=true', wf_img_sized('skins/icon_stats.gif', '', 16, 16) . ' ' . __('Stats') . ' ' . __('Memcached'), 'cachconteiner', false, 'ubButton');
    }
    if ($alterconf['UBCACHE_STORAGE'] == 'redis') {
        $result .= wf_AjaxLink('?module=report_sysload&ajaxredisstats=true', wf_img_sized('skins/icon_stats.gif', '', 16, 16) . ' ' . __('Stats') . ' ' . __('Redis'), 'cachconteiner', false, 'ubButton');
    }
    $result .= wf_AjaxLink('?module=report_sysload&ajaxcachedata=true', wf_img('skins/shovel.png') . ' ' . __('Cache data'), 'cachconteiner', false, 'ubButton');
    $result .= wf_AjaxLink('?module=report_sysload&ajaxcacheclear=true', wf_img('skins/icon_cleanup.png') . ' ' . __('Clear all cache'), 'cachconteiner', true, 'ubButton');
    $result .= $messages->getStyledMessage(__('Using system caching engine storage') . ': ' . wf_tag('b') . $alterconf['UBCACHE_STORAGE'] . wf_tag('b', true), 'info');
    $result .= wf_tag('br');
    $result .= wf_tag('table', false, 'sortable', 'width="100%" border="0" id="cachconteiner"') . zb_ListCacheInform() . wf_tag('table', true);
    return ($result);
}

/**
 * Renders cache data as auto-open modal dialog
 * 
 * @param string $dataKey
 * 
 * @return string
 */
function zb_CacheInformKeyView($dataKey) {
    $result = '';
    $cache = new UbillingCache();
    $allCache = $cache->getAllcache(true);
    if (!empty($allCache)) {
        foreach ($allCache as $io => $each) {
            if ($each['key'] == $dataKey) {
                $readableData = print_r($each['value'], true);
                $value = wf_tag('pre') . htmlspecialchars($readableData) . wf_tag('pre', true);
                $result .= wf_modalOpened(__('Cache information') . ': ' . $dataKey, $value, '800', '600');
            }
        }
    }
    return($result);
}

/**
 * Renders list of cache data
 * 
 * @global object $system
 * 
 * @param string $param
 * 
 * @return string
 */
function zb_ListCacheInform($param = '') {
    $cache = new UbillingCache();
    $messages = new UbillingMessageHelper();
    ($param == 'clear') ? $cache->deleteAllcache() : '';
    $data = (ispos($param, 'data')) ? $cache->getAllcache($param) : $cache->getAllcache();
    $result = '';
    if (!empty($data) and $param != 'clear') {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Key'));

        if (ispos($param, 'data')) {
            $cells .= wf_TableCell(__('Entries'));
            $cells .= wf_TableCell(__('Data'));
        }
        $rows = wf_TableRow($cells, 'row1');

        foreach ($data as $id => $key) {
            $cells = wf_TableCell($id);
            if (ispos($param, 'data')) {
                $cells .= wf_TableCell($key['key'], '', '', 'sorttable_customkey="' . $id . '"');
                if (is_array($key['value'])) { // needed to prevent e_warnings on PHP 7.3
                    $dataCount = sizeof($key['value']);
                } else {
                    $dataCount = strlen($key['value']);
                }
                $readableData = print_r($key['value'], true);
                $dataSize = stg_convert_size(strlen($readableData));
                $cells .= wf_TableCell($dataCount . ' ~ ' . $dataSize);
                $keyActions = '';

                $viewContainerId = 'aj_viewcachekey' . $key['key'];
                $viewUrl = '?module=report_sysload&datacachekeyview=' . $key['key'];
                $viewAjLink = wf_AjaxLink($viewUrl, wf_img_sized('skins/icon_search_small.gif', '', '10') . ' ' . __('Cache data'), $viewContainerId, false, 'ubButton');
                $viewControls = $viewAjLink;
                $ajDeleteContainerId = 'aj_deletecachekey' . $key['key'];
                $deleteUrl = '?module=report_sysload&deletecachekey=' . $key['key'];
                $deleteControls = wf_AjaxLink($deleteUrl, wf_img_sized('skins/icon_del.gif', '', '10') . ' ' . __('Delete'), $ajDeleteContainerId, false, 'ubButton');
                $keyActions .= wf_AjaxContainer($ajDeleteContainerId, '', $deleteControls . $viewControls);
                $keyActions .= wf_AjaxContainerSpan($viewContainerId, '');

                $cells .= wf_TableCell($keyActions);
            } else {
                $cells .= wf_TableCell($key, '', '', 'sorttable_customkey = "' . $id . '"');
            }
            $rows .= wf_TableRow($cells, 'row3');
        }
        $result .= $rows;
    } elseif (empty($data) and $param == 'clear') {
        $result .= $messages->getStyledMessage(__('Cache cleared'), 'success');
    }
    return ($result);
}

/**
 * Deletes some entry key data from cache
 * 
 * @param string $key
 * 
 * @return string
 */
function zb_CacheKeyDestroy($key) {
    $result = '';
    $messages = new UbillingMessageHelper();
    if (!empty($key)) {
        $cache = new UbillingCache();
        $key = str_replace($cache::CACHE_PREFIX, '', $key);
        $cache->delete($key);
        $result .= $messages->getStyledMessage(__('Deleted'), 'warning');
    }
    return ($result);
}

/**
 * Downloads and unpacks phpsysinfo distro
 * 
 * @return void
 */
function zb_InstallPhpsysinfo() {
    $upd = new UbillingUpdateStuff();
    $upd->downloadRemoteFile('http://ubilling.net.ua/packages/phpsysinfo.tar.gz', 'exports/', 'phpsysinfo.tar.gz');
    $upd->extractTgz('exports/phpsysinfo.tar.gz', 'phpsysinfo/');
}

/**
 * Sorting array of arrays by some field in ascending or descending order
 * Returns sorted array
 *
 * @param $data      - array to sort
 * @param $field     - field to sort by
 * @param bool $desc - sorting order
 *
 * Source code: https://www.the-art-of-web.com/php/sortarray/#section_8
 *
 * @return mixed
 */
function zb_sortArray($data, $field, $desc = false) {
    if (!is_array($field)) {
        $field = array($field);
    }

    usort($data, function($a, $b) use($field, $desc) {
        $retval = 0;

        foreach ($field as $fieldname) {
            if ($desc) {
                if ($retval == 0)
                    $retval = strnatcmp($b[$fieldname], $a[$fieldname]);
            } else {
                if ($retval == 0)
                    $retval = strnatcmp($a[$fieldname], $b[$fieldname]);
            }
        }

        return $retval;
    });

    return $data;
}

/**
 * Returns an array of SMS services represented like: id => name
 * with the default service on top of it
 *
 * @return array
 */
function zb_getSMSServicesList() {
    $result = array();
    $smsServicesList = array();
    $defaultSmsServiceId = 0;
    $defaultSmsServiceName = '';

    $query = "SELECT * FROM `sms_services`;";
    $result = simple_queryall($query);

    if (!empty($result)) {
        foreach ($result as $index => $record) {
            if ($record['default_service']) {
                $defaultSmsServiceId = $record['id'];
                $defaultSmsServiceName = $record['name'] . ' (' . __('by default') . ')';
                continue;
            }

            $smsServicesList[$record['id']] = $record['name'];
        }

        if (!empty($defaultSmsServiceId) and ! empty($defaultSmsServiceName)) {
            $smsServicesList = array($defaultSmsServiceId => $defaultSmsServiceName) + $smsServicesList;
        }
    }

    return $smsServicesList;
}

/**
 * Returns SMS service name by it's ID. If empty ID parameter returns the name of the default SMS service.
 * For big message sets it's strongly recommended to use SMSDirections class instead
 *
 * @param int $smsServiceId
 *
 * @return string
 */
function zb_getSMSServiceNameByID($smsServiceId = 0) {
    $smsServiceName = '';
    $result = array();

    if (empty($smsServiceId)) {
        $Query = "SELECT * FROM `sms_services` WHERE `default_service` > 0;";
    } else {
        $Query = "SELECT * FROM `sms_services` WHERE `id` = " . $smsServiceId . ";";
    }
    $result = simple_queryall($Query);

    if (!empty($result)) {
        $smsServiceName = $result[0]['name'];
    }

    return $smsServiceName;
}

/**
 * Returns array containing user's preferred SMS service in form of
 * [0] => [id]
 * [1] => [name]
 *
 * @param $userLogin
 *
 * @return array
 */
function zb_getUsersPreferredSMSService($userLogin) {
    $smsServiceIdName = array('', '');

    $query = "SELECT * FROM `sms_services_relations` WHERE `user_login` = '" . $userLogin . "';";
    $result = simple_queryall($query);

    if (!empty($result)) {
        $smsServiceIdName[0] = $result[0]['sms_srv_id'];
    }

    $smsServiceIdName[1] = zb_getSMSServiceNameByID($smsServiceIdName[0]);

    return $smsServiceIdName;
}

/**
 * Inits ghost mode for some administrator login
 * 
 * @param string $adminLogin
 * 
 * @return void
 */
function zb_InitGhostMode($adminLogin) {
    global $system;
    if (file_exists(USERS_PATH . $adminLogin)) {
        $userData = $system->getUserData($adminLogin);
        if (!empty($userData)) {
            $myLogin = whoami();
            $myData = $system->getUserData($myLogin);
//current login data is used for ghost mode identification
            setcookie('ghost_user', $myLogin . ':' . $myData['password'], null);
            $_COOKIE['ghost_user'] = $myLogin . ':' . $myData['password'];
//login of another admin
            rcms_log_put('Notification', $myLogin, 'Ghost logged in as ' . $adminLogin);
            log_register('GHOSTMODE {' . $myLogin . '} LOGIN AS {' . $adminLogin . '}');
            setcookie('ubilling_user', $adminLogin . ':' . $userData['password'], null);
            $_COOKIE['ubilling_user'] = $adminLogin . ':' . $userData['password'];
        }
    }
}

/**
 * Cleanups backups directory dumps older than X days encoded in filename.
 * 
 * @param int $maxAge
 * 
 * @return void
 */
function zb_backups_rotate($maxAge) {
    $maxAge = vf($maxAge, 3);
    if ($maxAge) {
        if (is_numeric($maxAge)) {
            $curTimeStamp = curdate();
            $curTimeStamp = strtotime($curTimeStamp);
            $cleanupTimeStamp = $curTimeStamp - ($maxAge * 86400); // Option is in days
            $backupsDirectory = DATA_PATH . 'backups/sql/';
            $backupsPrefix = 'ubilling-';
            $backupsExtension = '.sql';
            $allBackups = rcms_scandir($backupsDirectory, '*' . $backupsExtension);
            if (!empty($allBackups)) {
                foreach ($allBackups as $io => $eachDump) {
//trying to extract date from filename
                    $cleanName = $eachDump;
                    $cleanName = str_replace($backupsPrefix, '', $cleanName);
                    $cleanName = str_replace($backupsExtension, '', $cleanName);
                    if (ispos($cleanName, '_')) {
                        $explode = explode('_', $cleanName);
                        $cleanName = $explode[0];
                        if (zb_checkDate($cleanName)) {
                            $dumpTimeStamp = strtotime($cleanName);
                            if ($dumpTimeStamp < $cleanupTimeStamp) {
                                $rotateBackupPath = $backupsDirectory . $eachDump;
                                rcms_delete_files($rotateBackupPath);
                                log_register('BACKUP ROTATE `' . $rotateBackupPath . '`');
                            }
                        }
                    }
                }
            }
        }
    }
}

/**
 * Performs filtering of tariff name
 * 
 * @param string $tariffname
 * 
 * @return string
 */
function zb_TariffNameFilter($tariffname) {
    $tariffname = trim($tariffname);
    $tariffname = preg_replace("#[^a-z0-9A-Z\-_\.]#Uis", '', $tariffname);
    if (strlen($tariffname) > 32) {
//stargazer dramatically fails on long tariff names
        $tariffname = substr($tariffname, 0, 32);
    }
    return ($tariffname);
}

/**
 * Returns Stargazer tariff creation select input options string
 * 
 * @param int $t count of selectable options
 * @param int $selected selected option here
 * 
 * @return string
 */
function zb_TariffTimeSelector($t, $selected = false) {
    $result = '';
    $b = '';
    for ($i = 1; $i < $t; ++$i) {
        if ($i < 10) {
            $a = '0';
        } else {
            $a = '';
        }
        if ($selected == @$a . $i) {
            $b = 'SELECTED';
        } else {
            $b = '';
        }
        $result .= wf_tag('option', false, '', $b) . $a . $i . wf_tag('option', true);
    }
    return($result);
}

/**
 * Returns list of available Stargazer tariffs with some controls
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function web_TariffLister() {
    $alltariffs = billing_getalltariffs();
    $dbSchema = zb_CheckDbSchema();

    global $ubillingConfig;
    $alter = $ubillingConfig->getAlter();
    $tariffSpeeds = zb_TariffGetAllSpeeds();

    $cells = wf_TableCell(__('Tariff name'));
    $cells .= wf_TableCell(__('Tariff Fee'));
    if ($dbSchema > 0) {
        $cells .= wf_TableCell(__('Period'));
    }
    $cells .= wf_TableCell(__('Speed'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    $result = wf_Link("?module=tariffs&action=new", web_icon_create() . ' ' . __('Create new tariff'), true, 'ubButton');

    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            $cells = wf_TableCell($eachtariff['name']);
            $cells .= wf_TableCell($eachtariff['Fee']);
            if ($dbSchema > 0) {
                $cells .= wf_TableCell(__($eachtariff['period']));
            }

            if (isset($tariffSpeeds[$eachtariff['name']])) {
                $speedData = $tariffSpeeds[$eachtariff['name']]['speeddown'] . ' / ' . $tariffSpeeds[$eachtariff['name']]['speedup'];
            } else {
                $speedData = wf_tag('font', false, '', 'color="#bc0000"') . __('Speed is not set') . wf_tag('font', true);
            }
            $cells .= wf_TableCell($speedData);

            $actions = wf_JSAlert("?module=tariffs&action=delete&tariffname=" . $eachtariff['name'], web_delete_icon(), __('Delete') . ' ' . $eachtariff['name'] . '? ' . __('Removing this may lead to irreparable results'));
            $actions .= wf_JSAlert("?module=tariffs&action=edit&tariffname=" . $eachtariff['name'], web_edit_icon(), __('Edit') . ' ' . $eachtariff['name'] . '? ' . __('Are you serious'));
            $actions .= wf_Link('?module=tariffspeeds&tariff=' . $eachtariff['name'], wf_img('skins/icon_speed.gif', __('Edit speed')), false, '');
            $actions .= ( isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS']) ) ? wf_Link('?module=signupprices&tariff=' . $eachtariff['name'], wf_img('skins/icons/register.png', __('Edit signup price')), false, '') : null;
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }

    $result .= wf_TableBody($rows, '100%', 0, 'sortable');

    return($result);
}

/**
 * WTF???!!!
 * 
 * @param type $a
 * @param type $b
 * @return type
 */
function zb_tariff_yoba_price($a, $b) {
    if ($a == $b) {
        return $a;
    } else {
        return "$a/$b";
    }
}

/**
 * Renders new tariff creation form
 * 
 * @global array $dirs
 * 
 * @return string
 */
function web_TariffCreateForm() {
    global $dirs;

    $dbSchema = zb_CheckDbSchema();

    if ($dbSchema > 0) { //stargazer >=2.409
        $availOpts = array('month' => __('Month'), 'day' => __('Day'));
        $periodControls = wf_Selector("options[Period]", $availOpts, __('Period'), @$tariffdata['period'], true);
        $periodControls .= wf_delimiter(0);
    } else {
        $periodControls = '';
    }

    $traffCountOptions = array(
        'up+down' => 'up+down',
        'up' => 'up',
        'down' => 'down',
        'max' => 'max',
    );

    $result = '';

    $inputs = wf_TextInput('options[TARIFF]', __('Tariff name'), '', true, '20');
    $inputs .= wf_delimiter(0);
    $inputs .= wf_TextInput('options[Fee]', __('Fee'), '0', true, 4, 'finance');
    $inputs .= wf_delimiter(0);
    $inputs .= $periodControls;
    $inputs .= wf_TextInput('options[Free]', __('Prepaid traffic'), '0', true, 3, 'digits');
    $inputs .= wf_delimiter(0);
    $inputs .= wf_Selector('options[TraffType]', $traffCountOptions, __('Counting traffic'), '', true);
    $inputs .= wf_delimiter(0);
    $inputs .= wf_TextInput('options[PassiveCost]', __('Cost of freezing'), '', true, 3, 'finance');
    $inputs .= wf_delimiter(0);

    $inputsDirs = '';

    foreach ($dirs as $dir) {
        $inputsDirs .= wf_tag('fieldset', false);
        $inputsDirs .= wf_tag('legend');
        $inputsDirs .= __('Traffic classes') . ': ' . wf_tag('b') . $dir['rulename'] . wf_tag('b', true);
        $inputsDirs .= wf_tag('legend', true);


        $inputsDirs .= wf_tag('select', false, '', 'id="dhour' . $dir['rulenumber'] . '"  name="options[dhour][' . $dir['rulenumber'] . ']"');
        $inputsDirs .= wf_tag('option', false, '', 'SELECTED') . '00' . wf_tag('option', true);
        $inputsDirs .= zb_TariffTimeSelector(24);
        $inputsDirs .= wf_tag('select', true);


        $inputsDirs .= wf_tag('select', false, '', 'id="dmin' . $dir['rulenumber'] . '"  name="options[dmin][' . $dir['rulenumber'] . ']"');
        $inputsDirs .= wf_tag('option', false, '', 'SELECTED') . '00' . wf_tag('option', true);
        $inputsDirs .= zb_TariffTimeSelector(60);
        $inputsDirs .= wf_tag('select', true);
        $inputsDirs .= ' (' . __('hours') . '/' . __('minutes') . ') ' . __('Day');

        $inputsDirs .= wf_TextInput('options[PriceDay][' . $dir['rulenumber'] . ']', __('Price day'), '', false, 3);
        $inputsDirs .= wf_TextInput('options[Threshold][' . $dir['rulenumber'] . ']', __('Threshold') . ' (' . __('Mb') . ')', '0', true, 3, 'digits');

        $inputsDirs .= wf_tag('select', false, '', 'id="nhour' . $dir['rulenumber'] . '"  name="options[nhour][' . $dir['rulenumber'] . ']"');
        $inputsDirs .= wf_tag('option', false, '', 'SELECTED') . '00' . wf_tag('option', true);
        $inputsDirs .= zb_TariffTimeSelector(24);
        $inputsDirs .= wf_tag('select', true);

        $inputsDirs .= wf_tag('select', false, '', 'id="nmin' . $dir['rulenumber'] . '"  name="options[nmin][' . $dir['rulenumber'] . ']"');
        $inputsDirs .= wf_tag('option', false, '', 'SELECTED') . '00' . wf_tag('option', true);
        $inputsDirs .= zb_TariffTimeSelector(60);
        $inputsDirs .= wf_tag('select', true);
        $inputsDirs .= ' (' . __('hours') . '/' . __('minutes') . ') ' . __('Night');
        $inputsDirs .= wf_TextInput('options[PriceNight][' . $dir['rulenumber'] . ']', __('Price night'), '', false, 3);

        $inputsDirs .= wf_CheckInput('options[NoDiscount][' . $dir['rulenumber'] . ']', __('Without threshold'), true, true);
        $inputsDirs .= wf_CheckInput('options[SinglePrice][' . $dir['rulenumber'] . ']', __('Price does not depend on time'), true, true);

        $inputsDirs .= wf_tag('fieldset', true);
        $inputsDirs .= wf_delimiter(0);
    }
    $allInputs = $inputs . $inputsDirs;
    $allInputs .= wf_Submit(__('Create new tariff'));
    $result .= wf_Form('', 'POST', $allInputs, '', '', 'tariff_add');
    return($result);
}

/**
 * Renders existing tariff editing form
 * 
 * @global array $dirs
 * @param string $tariffname
 * 
 * @return string
 */
function web_TariffEditForm($tariffname) {
    global $dirs;
    $result = '';
    $tariffdata = billing_gettariff($tariffname);
    if (!empty($tariffdata)) {
        $traffCountOptions = array(
            'up+down' => 'up+down',
            'up' => 'up',
            'down' => 'down',
            'max' => 'max',
        );


        $dbSchema = zb_CheckDbSchema();


        if ($dbSchema > 0) {
            $availOpts = array('month' => __('Month'), 'day' => __('Day'));
            $periodControls = wf_Selector("options[Period]", $availOpts, __('Period'), $tariffdata['period'], true);
            $periodControls .= wf_delimiter(0);
        } else {
            $periodControls = '';
        }

        $form = '';
        $inputs = '';


        $inputs .= wf_TextInput('options[TARIFF]', __('Tariff name'), $tariffdata['name'], true, 20, '', '', '', 'DISABLED');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_TextInput('options[Fee]', __('Fee'), $tariffdata['Fee'], true, 4, 'finance');
        $inputs .= wf_delimiter(0);
        $inputs .= $periodControls;
        $inputs .= wf_TextInput('options[Free]', __('Prepaid traffic'), $tariffdata['Free'], true, 3, 'digits');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Selector('options[TraffType]', $traffCountOptions, __('Counting traffic'), $tariffdata['TraffType'], true);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_TextInput('options[PassiveCost]', __('Cost of freezing'), $tariffdata['PassiveCost'], true, 3, 'finance');
        $inputs .= wf_delimiter(0);

        $inputsDirs = '';

        foreach ($dirs as $dir) {
            $inputsDirs .= wf_tag('fieldset', false);
            $inputsDirs .= wf_tag('legend');
            $inputsDirs .= __('Traffic classes') . ': ' . wf_tag('b') . $dir['rulename'] . wf_tag('b', true);
            $inputsDirs .= wf_tag('legend', true);

            $rulenumber = $dir['rulenumber'];
            $arrTime = explode('-', $tariffdata ["Time" . $rulenumber]);
            $day = explode(':', $arrTime [0]);
            $night = explode(':', $arrTime [1]);

            $tariffdata ['Time'] [$rulenumber] ['Dmin'] = $day [1];
            $tariffdata ['Time'] [$rulenumber] ['Dhour'] = $day [0];
            $tariffdata ['Time'] [$rulenumber] ['Nmin'] = $night [1];
            $tariffdata ['Time'] [$rulenumber] ['Nhour'] = $night [0];

            $inputsDirs .= wf_tag('select', false, '', 'id="dhour' . $dir['rulenumber'] . '"  name="options[dhour][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(24, $tariffdata['Time'][$dir['rulenumber']] ['Dhour']);
            $inputsDirs .= wf_tag('select', true);

            $inputsDirs .= wf_tag('select', false, '', 'id="dmin' . $dir['rulenumber'] . '"  name="options[dmin][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(60, $tariffdata['Time'][$dir['rulenumber']] ['Dmin']);
            $inputsDirs .= wf_tag('select', true);
            $inputsDirs .= ' (' . __('hours') . '/' . __('minutes') . ') ' . __('Day');

            $inputsDirs .= wf_TextInput('options[PriceDay][' . $dir['rulenumber'] . ']', __('Price day'), zb_tariff_yoba_price($tariffdata ["PriceDayA" . $dir['rulenumber']], $tariffdata ["PriceDayB" . $dir['rulenumber']]), false, 3);
            $inputsDirs .= wf_TextInput('options[Threshold][' . $dir['rulenumber'] . ']', __('Threshold') . ' (' . __('Mb') . ')', $tariffdata ["Threshold$dir[rulenumber]"], true, 3, 'digits');

            $inputsDirs .= wf_tag('select', false, '', 'id="nhour' . $dir['rulenumber'] . '"  name="options[nhour][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(24, $tariffdata['Time'][$dir['rulenumber']] ['Nhour']);
            $inputsDirs .= wf_tag('select', true);

            $inputsDirs .= wf_tag('select', false, '', 'id="nmin' . $dir['rulenumber'] . '"  name="options[nmin][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(60, $tariffdata['Time'][$dir['rulenumber']] ['Nmin']);
            $inputsDirs .= wf_tag('select', true);
            $inputsDirs .= ' (' . __('hours') . '/' . __('minutes') . ') ' . __('Night');
            $inputsDirs .= wf_TextInput('options[PriceNight][' . $dir['rulenumber'] . ']', __('Price night'), zb_tariff_yoba_price($tariffdata ["PriceNightA$dir[rulenumber]"], $tariffdata ["PriceNightB$dir[rulenumber]"]), false, 3);

            $inputsDirs .= wf_CheckInput('options[NoDiscount][' . $dir['rulenumber'] . ']', __('Without threshold'), true, $tariffdata["NoDiscount" . $rulenumber]);
            $inputsDirs .= wf_CheckInput('options[SinglePrice][' . $dir['rulenumber'] . ']', __('Price does not depend on time'), true, $tariffdata["SinglePrice" . $rulenumber]);


            $inputsDirs .= wf_tag('fieldset', true);
            $inputsDirs .= wf_delimiter(0);
        }


        $allInputs = $inputs . $inputsDirs;
        $allInputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $allInputs, '', '', 'save');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('Something went wrong') . ': FATAL_TARIFF_NOT_EXISTS', 'error');
    }

    return($result);
}

/**
 * Returns switch problem from zabbix in profile form
 * 
 * @param string $login
 * @return string
 */
function web_ProfileSwitchZabbixProblem($login) {
    $result = '';
    $login = mysql_real_escape_string($login);
    $query = "SELECT `ip` FROM `switchportassign` LEFT JOIN `switches` ON (switchid=`switches`.`id`) WHERE login = '" . $login . "' LIMIT 1";

//getting switch IP
    $swIP = simple_query($query);

    if (!empty($swIP)) {
        $allProblems = getZabbixProblems($swIP['ip']);
        if (!empty($allProblems)) {
            $cells = wf_TableCell(wf_tag('b', false) . __('Problem') . wf_tag('b', true), '30%', 'row2');
            $cells .= wf_TableCell(wf_tag('b', false) . __('Start date') . wf_tag('b', true), '', 'row2');
            $cells .= wf_TableCell(wf_tag('b', false) . __('Notes') . wf_tag('b', true), '', 'row2');
            $rows = wf_TableRow($cells, 'row3');

            foreach ($allProblems as $io => $problemData) {
// Colorized problem
                if ($problemData['severity'] == 5) {
                    $problemColor = wf_tag('font', false, '', 'color="#8B0000"') . wf_tag('b', false);
                    $problemColorEnd = wf_tag('b', true) . wf_tag('font', true);
                } elseif ($problemData['severity'] == 4) {
                    $problemColor = wf_tag('font', false, '', 'color="#FF0000"') . wf_tag('b', false);
                    $problemColorEnd = wf_tag('b', true) . wf_tag('font', true);
                } elseif ($problemData['severity'] == 3) {
                    $problemColor = wf_tag('font', false, '', 'color="#00008B"') . wf_tag('b', false);
                    $problemColorEnd = wf_tag('b', true) . wf_tag('font', true);
                } elseif ($problemData['severity'] == 2) {
                    $problemColor = wf_tag('font', false, '', 'color="#4682B4"') . wf_tag('b', false);
                    $problemColorEnd = wf_tag('b', true) . wf_tag('font', true);
                } elseif ($problemData['severity'] == 1) {
                    $problemColor = wf_tag('font', false, '', 'color="#7499FF"') . wf_tag('b', false);
                    $problemColorEnd = wf_tag('b', true) . wf_tag('font', true);
                } else {
                    $problemColor = wf_tag('b', false);
                    $problemColorEnd = wf_tag('b', true);
                }
                $acknowledges = $problemData['acknowledges'];
                $acknowledgesMessages = array_column($acknowledges, 'message');

                $cells = wf_TableCell($problemColor . __($problemData['name']) . $problemColorEnd, '30%');
                $cells .= wf_TableCell(date('Y-m-d H:i:s', $problemData['clock']));
                $cells .= wf_TableCell(implode(wf_tag('br'), $acknowledgesMessages));
                $rows .= wf_TableRow($cells, 'row4');
            }
            $result = wf_TableBody($rows, '100%', '0');
        }
    }

    return ($result);
}

/**
 * Inserts some element into specific array index position
 * 
 * @param array      $array
 * @param int|string $position
 * @param mixed      $insert
 */
function zb_array_insert(&$array, $position, $insert) {
    if (is_int($position)) {
        array_splice($array, $position, 0, $insert);
    } else {
        $pos = array_search($position, array_keys($array));
        $array = array_merge(
                array_slice($array, 0, $pos), $insert, array_slice($array, $pos)
        );
    }
}

/**
 * Returns generic printable report content
 * 
 * @param string $title report title
 * @param string $data  report data to printable transform
 *
 * @return void
 */
function zb_ReportPrintable($title, $data) {
    $style = file_get_contents(CONFIG_PATH . "ukvprintable.css");
    $header = wf_tag('!DOCTYPE', false, '', 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"');
    $header .= wf_tag('html', false, '', 'xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru"');
    $header .= wf_tag('head', false);
    $header .= wf_tag('title') . $title . wf_tag('title', true);
    $header .= wf_tag('meta', false, '', 'http-equiv="Content-Type" content="text/html; charset=UTF-8" /');
    $header .= wf_tag('style', false, '', 'type="text/css"');
    $header .= $style;
    $header .= wf_tag('style', true);
    $header .= wf_tag('script', false, '', 'src="modules/jsc/sorttable.js" language="javascript"') . wf_tag('script', true);
    $header .= wf_tag('head', true);
    $header .= wf_tag('body', false);

    $footer = wf_tag('body', true);
    $footer .= wf_tag('html', true);

    $title = (!empty($title)) ? wf_tag('h2') . $title . wf_tag('h2', true) : '';
    $data = $header . $title . $data . $footer;
    die($data);
}

/**
 * Renders EasyFreeze form and process some requests
 * 
 * @param string $login
 * 
 * @return string
 */
function web_EasyFreezeForm($login) {
    $result = '';
    $dateFromPreset = curdate();
    $dateToPreset = date("Y-m-t");

    $inputs = '<!--ugly hack to prevent datepicker autoopen -->';
    $inputs .= wf_TextInput('omghack', '', '', false, '', '', '', '', 'style="width: 0; height: 0; top: -100px; position: absolute;"');
    $inputs .= wf_HiddenInput('easyfreezeuser', $login);
    $inputs .= __('Date from') . ' ' . wf_DatePickerPreset('easyfreezedatefrom', $dateFromPreset, true) . ' ';
    $inputs .= __('Date to') . ' ' . wf_DatePickerPreset('easyfreezedateto', $dateToPreset, true);
    $inputs .= wf_delimiter(0);
    $inputs .= wf_CheckInput('easyfreezerightnow', __('Freeze user') . ' ' . __('right now'), true, false);
    $inputs .= wf_CheckInput('easyfreezeforever', __('Freeze user') . ' ' . __('forever'), true, false);
    $inputs .= wf_TextInput('easyfreezenote', __('Notes'), '', true, 30);
    $inputs .= wf_delimiter(0);
    $inputs .= wf_Submit(__('Freeze user'));

    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return($result);
}

/**
 * Catches and do some processing of easyfreeze requests
 * 
 * @global object $billing
 * 
 * @return void/string on error
 */
function zb_EasyFreezeController() {
    $result = '';
    //freezing processing 
    if (ubRouting::checkPost(array('easyfreezeuser', 'easyfreezedatefrom', 'easyfreezedateto'))) {
        global $billing;
        $scheduler = new DealWithIt();
        $loginToFreeze = ubRouting::post('easyfreezeuser');
        $freezingNote = 'EASYFREEZE:' . ubRouting::post('easyfreezenote');

        $dateFrom = ubRouting::post('easyfreezedatefrom');
        $dateTo = ubRouting::post('easyfreezedateto');

        if (zb_checkDate($dateFrom) AND zb_checkDate($dateTo)) {
            //freezing
            if (ubRouting::checkPost('easyfreezerightnow')) {
                //just freeze user right now
                $billing->setpassive($loginToFreeze, 1);
                log_register('CHANGE Passive (' . $loginToFreeze . ') ON 1 NOTE `' . $freezingNote . '`');
            } else {
                //create new freezing schedule
                $scheduler->createTask($dateFrom, $loginToFreeze, 'freeze', '', $freezingNote);
            }

            //unfreezing schedule if not forever
            if (!ubRouting::checkPost('easyfreezeforever')) {
                $scheduler->createTask($dateTo, $loginToFreeze, 'unfreeze', '', $freezingNote);
            }


            //refresh profile
            ubRouting::nav(UserProfile::URL_PROFILE . $loginToFreeze);
        } else {
            $result .= __('Wrong date format');
        }
    }
    return($result);
}

/**
 * Convert a string to an array as str_split but multibyte-safe.
 * 
 * @param string $string
 * @param int $length
 * 
 * @return array
 */
function zb_split_mb($string, $length = 1) {
    $result = preg_split('~~u', $string, -1, PREG_SPLIT_NO_EMPTY);
    if ($length > 1) {
        $chunks = array_chunk($result, $length);
        foreach ($chunks as $i => $chunk) {
            $chunks[$i] = join('', (array) $chunk);
        }
        $result = $chunks;
    }
    return ($result);
}

/**
 * Returns list of available free Juniper NASes
 * 
 * @return string
 */
function web_JuniperListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `jun_clients`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('NAS name'));
        $cells .= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells .= wf_TableCell($each['shortname']);
            $cells .= wf_TableCell($each['secret']);
            $rows .= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

/**
 * Returns list of available MultiGen NASes
 * 
 * @return string
 */
function web_MultigenListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `" . MultiGen::CLIENTS . "`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('NAS name'));
        $cells .= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells .= wf_TableCell($each['shortname']);
            $cells .= wf_TableCell($each['secret']);
            $rows .= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

/**
 * Renders current system load average stats
 * 
 * @return string
 */
function web_ReportSysloadRenderLA() {
    $loadAvg = sys_getloadavg();
    $laGauges = '';
    $laGauges .= wf_tag('h3') . __('Load Average') . wf_tag('h3', true);
    $laOpts = '
             max: 10,
             min: 0,
             width: ' . 280 . ', height: ' . 280 . ',
             greenFrom: 0, greenTo: 2,
             yellowFrom:2, yellowTo: 5,
             redFrom: 5, redTo: 10,
             minorTicks: 5
                      ';
    $laGauges .= wf_renderGauge(round($loadAvg[0], 2), '1' . ' ' . __('minutes'), 'LA', $laOpts, 300);
    $laGauges .= wf_renderGauge(round($loadAvg[1], 2), '5' . ' ' . __('minutes'), 'LA', $laOpts, 300);
    $laGauges .= wf_renderGauge(round($loadAvg[2], 2), '15' . ' ' . __('minutes'), 'LA', $laOpts, 300);
    $laGauges .= wf_CleanDiv();
    return($laGauges);
}

/**
 * Renders some free space data about free disk space
 * 
 * @return string
 */
function web_ReportSysloadRenderDisksCapacity() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $usedSpaceArr = array();
    $mountPoints = array('/');
    if (@$altCfg['SYSLOAD_DISKS']) {
        $mountPoints = explode(',', $altCfg['SYSLOAD_DISKS']);
    }
    if (!empty($mountPoints)) {
        foreach ($mountPoints as $io => $each) {
            $totalSpace = disk_total_space($each);
            $freeSpace = disk_free_space($each);
            $usedSpaceArr[$each]['percent'] = zb_PercentValue($totalSpace, ($totalSpace - $freeSpace));
            $usedSpaceArr[$each]['total'] = $totalSpace;
            $usedSpaceArr[$each]['free'] = $freeSpace;
        }
    }

    $result = '';
    $result .= wf_tag('h3') . __('Disks capacity') . wf_tag('h3', true);
    $opts = '
             max: 100,
             min: 0,
             width: ' . 280 . ', height: ' . 280 . ',
             greenFrom: 0, greenTo: 70,
             yellowFrom:70, yellowTo: 90,
             redFrom: 90, redTo: 100,
             minorTicks: 5
                      ';
    if (!empty($usedSpaceArr)) {
        foreach ($usedSpaceArr as $mountPoint => $spaceStats) {
            $partitionLabel = $mountPoint . ' - ' . stg_convert_size($spaceStats['free']) . ' ' . __('Free');
            $result .= wf_renderGauge(round($spaceStats['percent']), $partitionLabel, '%', $opts, 300);
        }
    }
    $result .= wf_CleanDiv();
    return($result);
}

/**
 * Renders current system process list
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function web_ReportSysloadRenderTop() {
    global $ubillingConfig;
    $billCfg = $ubillingConfig->getBilling();
    $result = '';
    if (!empty($billCfg['TOP'])) {
        $result .= wf_tag('pre') . shell_exec($billCfg['TOP']) . wf_tag('pre', true);
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('batch top path') . ' ' . __('is empty'), 'error');
    }
    return($result);
}

/**
 * Renders current system process list
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function web_ReportSysloadRenderUptime() {
    global $ubillingConfig;
    $billCfg = $ubillingConfig->getBilling();
    $result = '';
    if (!empty($billCfg['UPTIME'])) {
        $result .= wf_tag('pre') . shell_exec($billCfg['UPTIME']) . wf_tag('pre', true);
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('uptime path') . ' ' . __('is empty'), 'error');
    }
    return($result);
}

/**
 * Renders current system process list
 * 
 * 
 * @return string
 */
function web_ReportSysloadRenderDF() {
    $result = '';
    $result .= wf_tag('pre') . shell_exec('df -h') . wf_tag('pre', true);
    return($result);
}

/**
 * Returns simple new administrator registration form
 * 
 * @return string
 */
function web_AdministratorRegForm() {
    $result = '';
    $inputs = '';
    $inputs .= wf_img_sized('skins/admreganim.gif', '', '', '', 'display:block; float:right;');
    $inputs .= wf_HiddenInput('registernewadministrator', 'true');
    $inputs .= wf_TextInput('username', __('Username'), '', true, 20, 'alphanumeric') . wf_delimiter(0);
    $inputs .= wf_PasswordInput('password', __('Password'), '', true, 20) . wf_delimiter(0);
    $inputs .= wf_PasswordInput('confirmation', __('Confirm password'), '', true, 20) . wf_delimiter(0);
    $inputs .= wf_TextInput('nickname', __('Nickname'), '', true, 20, 'alphanumeric') . wf_delimiter(0);
    $inputs .= wf_TextInput('email', __('Email'), '', true, 20, 'email') . wf_delimiter(1);
    $inputs .= wf_HiddenInput('userdata[hideemail]', '1');
    $inputs .= wf_HiddenInput('userdata[tz]', '2');
    $inputs .= wf_Submit(__('Administrators registration'));

    $result .= wf_Form('', 'POST', $inputs, 'glamour');
    return($result);
}

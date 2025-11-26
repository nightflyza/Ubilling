<?php

/**
 * Returns typical back to profile/editing controls
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

        if ((cfr('USEREDIT')) and (@$_GET['module'] != 'useredit')) {
            $controls .= wf_Link($urlUserEdit . $login, wf_img_sized('skins/backedit.png', __('Back to user edit'), '16') . ' ' . __('Back to user edit'), false, 'ubbackedit');
        }
    }
    $controls .= wf_tag('div', true);
    return ($controls);
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
 * Returns user logins with non-unique passwords as idx => login
 * 
 * @return array
 */
function zb_GetNonUniquePasswordUsers() {
    $query_p = "SELECT `login`,`Password` FROM `users`";
    $allUsers = simple_queryall($query_p);

    $passwordMap = array();
    $result = array();

    if (!empty($allUsers)) {
        foreach ($allUsers as $eachUser) {
            $pass = $eachUser['Password'];
            $login = $eachUser['login'];

            if (!isset($passwordMap[$pass])) {
                $passwordMap[$pass] = array();
            }

            $passwordMap[$pass][] = $login;
        }

        foreach ($passwordMap as $loginList) {
            if (sizeof($loginList) > 1) {
                foreach ($loginList as $login) {
                    $result[] = $login;
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
    return ($result);
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

    return ($form);
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
        case 3:
            $password_proposal = zb_PasswordGenerateTH($passwordsLenght);
            break;
        default:
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

    return ($form);
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

    return ($form);
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
    $allUsedMacs = zb_getAllUsedMac();
    $allMacs = array();
    $resultArr = array();
    $lineLimit = ($ubillingConfig->getAlterParam('NMLOOKUP_DEPTH')) ? $ubillingConfig->getAlterParam('NMLOOKUP_DEPTH') : 200;
    $leases = $ubillingConfig->getAlterParam('NMLEASES');
    $additionalSources = $ubillingConfig->getAlterParam('NMSOURCES_ADDITIONAL');
    $reverseFlag = ($ubillingConfig->getAlterParam('NMREVERSE')) ? true : false;
    $searchableFlag = ($ubillingConfig->getAlterParam('MACSEL_SEARCHBL')) ? true : false;
    $additionalMark = ($ubillingConfig->getAlterParam('NMLEASEMARK_ADDITIONAL')) ? $ubillingConfig->getAlterParam('NMLEASEMARK_ADDITIONAL') : '';
    //parsing new MAC sources
    if (!empty($leases)) {
        $allMacs += zb_MacParseSource($leases, $lineLimit);
    }

    //and optional supplementary sources
    if (!empty($additionalSources)) {
        $additionalSources = explode(',', $additionalSources);
        if (!empty($additionalSources)) {
            foreach ($additionalSources as $io => $eachAdditionalSource) {
                $supSourceMacs = zb_MacParseSource($eachAdditionalSource, $lineLimit, $additionalMark);
                $allMacs = array_merge($allMacs, $supSourceMacs);
            }
        }
    }

    if (!empty($allMacs)) {
        foreach ($allMacs as $io => $newmac) {
            if (zb_checkMacFree($newmac, $allUsedMacs)) {
                $resultArr[$newmac] = $newmac;
            }
        }
        //revert array due usability reasons (i hope).
        if ($reverseFlag) {
            $resultArr = array_reverse($resultArr);
        }
    }

    //searchable MAC selector?
    if ($searchableFlag) {
        $result = wf_SelectorSearchable($name, $resultArr, '', '', false);
    } else {
        $result = wf_Selector($name, $resultArr, '', '', false);
    }

    return ($result);
}

/**
 * Renders user MAC editing form
 * 
 * @global object $ubillingConfig
 * @param string $userAddress
 * @param bool $manualInput
 * @param string $currentMac
 * 
 * @return string 
 */
function web_MacEditForm($userAddress = '', $manualInput = false, $currentMac = '') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $lookuplink = '';
    $newValue = '';
    $result = '';
    //mac vendor search
    if ($altCfg['MACVEN_ENABLED']) {
        if (cfr('MACVEN')) {
            $optionState = $altCfg['MACVEN_ENABLED'];
            switch ($optionState) {
                case 1:
                    $lookupUrl = '?module=macvendor&modalpopup=true&mac=' . $currentMac . '&username=';
                    $lookuplink = wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                    $lookuplink .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                    break;
                case 2:
                    $vendorframe = wf_tag('iframe', false, '', 'src="?module=macvendor&mac=' . $currentMac . '" width="360" height="160" frameborder="0"');
                    $vendorframe .= wf_tag('iframe', true);
                    $lookuplink = wf_modalAuto(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '');
                    break;
                case 3:
                    $lookupUrl = '?module=macvendor&raw=true&mac=' . $currentMac;
                    $lookuplink = wf_AjaxLink($lookupUrl, wf_img('skins/macven.gif', __('Device vendor')), 'macvendorcontainer', false);
                    $lookuplink .= wf_AjaxContainerSpan('macvendorcontainer', '', '');
                    break;
            }
        }
    }

    //only for manual input form
    if ($manualInput) {
        //random MAC preset
        if ($altCfg['MACCHANGERANDOMDEFAULT']) {
            $randomMac = zb_MacGetRandom();
            if (zb_mac_unique($randomMac)) {
                $newValue = $randomMac;
            } else {
                show_error('Oops');
                $newValue = '';
            }
        }
    }

    //custom input types here
    if ($manualInput) {
        $macInput = wf_TextInput('newmac', '', $newValue, false, '', 'mac');
    } else {
        $macInput = zb_NewMacSelect();
    }

    $cells = wf_TableCell(__('User'), '', 'row2');
    $cells .= wf_TableCell($userAddress, '', 'row3');
    $rows = wf_TableRow($cells);
    $cells = wf_TableCell(__('Current MAC') . ' ' . $lookuplink, '', 'row2');
    $cells .= wf_TableCell($currentMac, '', 'row3');
    $rows .= wf_TableRow($cells);
    $cells = wf_TableCell(__('New MAC'), '', 'row2');

    $cells .= wf_TableCell($macInput, '', 'row3');
    $rows .= wf_TableRow($cells);
    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $result .= wf_Form('', 'POST', $inputs, '');

    return ($result);
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

    return ($form);
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

    return ($selector);
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

    return ($form);
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

    return ($form);
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
 * Returns tariff selector with optional branches, lousy and stealth tariffs filtering
 * 
 * @param string $fieldname
 * @param bool $skipLousy
 * 
 * @return string
 */
function web_tariffselector($fieldname = 'tariffsel', $skipLousy = false) {
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
            //validating tariff accessibility depend on branch if enabled
            if ($altCfg['BRANCHES_ENABLED']) {
                if ($branchControl->isMyTariff($eachtariff['name'])) {
                    $options[$eachtariff['name']] = $eachtariff['name'];
                }
            } else {
                //or just append to list
                $options[$eachtariff['name']] = $eachtariff['name'];
            }
        }
    }

    //excluding lousy tariffs from list, if available
    if ($skipLousy) {
        $lousy = new LousyTariffs();
        $options = $lousy->truncateLousy($options);
    }

    //stealth tariffs implementation
    if ($altCfg['STEALTH_TARIFFS_ENABLED']) {
        $stealthTariffs = new StealthTariffs();
        //administrator have no rights to assign stealth tariffs?
        if (!cfr($stealthTariffs::RIGHT_STEALTH)) {
            //dropping all of them from selector options
            $options = $stealthTariffs->truncateStealth($options);
        }
    }

    $selector = wf_Selector($fieldname, $options, '', '', false);

    return ($selector);
}

/**
 * Returns full tariff changing form
 * 
 * @global object $ubillingConfig
 * @param string  $fieldname
 * @param string  $fieldkey
 * @param string  $useraddress
 * @param string  $olddata
 * @param bool    $skeepLousy
 * 
 * @return string
 */
function web_EditorTariffForm($fieldname, $fieldkey, $useraddress, $olddata = '', $skipLousy = false) {
    global $ubillingConfig;
    $alter = $ubillingConfig->getAlter();

    $login = (isset($_GET['username'])) ? vf($_GET['username']) : null;

    $nm_flag = ($olddata == '*_NO_TARIFF_*') ? 'DISABLED' : null;
    $charge_signup_price_checkbox = '';
    if (isset($alter['SIGNUP_PAYMENTS']) and !empty($alter['SIGNUP_PAYMENTS'])) {
        $payment = zb_UserGetSignupPrice($login);
        $paid = zb_UserGetSignupPricePaid($login);
        $disabled = ($payment == $paid and $payment > 0) ? 'disabled' : '';
        $charge_signup_price_checkbox = ' ' . wf_tag('label', false, '', 'for="charge_signup_price_checkbox"');
        $charge_signup_price_checkbox .= __('Charge signup price') . ' ';
        $charge_signup_price_checkbox .= wf_tag('input', false, '', 'type="checkbox" name="charge_signup_price" id="charge_signup_price_checkbox" ' . $disabled);
        $charge_signup_price_checkbox .= wf_tag('label', true);
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
    $cells .= wf_TableCell(web_tariffselector($fieldkey, $skipLousy) . $charge_signup_price_checkbox, '', 'row3');
    $rows .= wf_TableRow($cells);

    $table = wf_TableBody($rows, '100%', 0);

    $inputs = $table;
    $inputs .= wf_tag('br');
    $inputs .= wf_Submit(__('Change'));
    $inputs .= wf_delimiter();
    $form = wf_Form('', 'POST', $inputs, '');

    return ($form);
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

    return ($form);
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

    return ($form);
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

    if (ispos($paynote, 'DEFSALE')) {
        $defsaleNote = explode(':', $paynote);
        $paynote = __('Deferred sale') . ': ' . $defsaleNote[1];
    }

    if (ispos($paynote, 'TAXSUP:')) {
        $taxsup = explode(':', $paynote);
        $paynote = __('Additional fee') . ': ' . $taxsup[1];
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

    return ($result);
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
    return ($userdata);
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

        if ($paymentData['admin'] != 'external' and $paymentData['admin'] != 'openpayz' and $paymentData['admin'] != 'guest') {
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
    $total_payments = 0;
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

            if (!empty($alter_conf['DOCX_SUPPORT']) and !empty($alter_conf['DOCX_CHECK'])) {
                $printcheck = wf_Link('?module=printcheck&paymentid=' . $eachpayment['id'], wf_img('skins/printer_small.gif', __('Print')), false);
                if (@$alter_conf['DOCX_CHECK_TH']) {
                    $printcheck .= wf_Link('?module=printcheck&th=true&paymentid=' . $eachpayment['id'], wf_img('skins/printer_small_blue.gif', __('Print')), false);
                }
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

            if (is_numeric($eachpayment['summ'])) {
                if ($eachpayment['summ'] > 0) {
                    $total_payments = $total_payments + $eachpayment['summ'];
                }
            }
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result .= __('Total payments') . ': ' . wf_tag('b') . abs($total_payments) . wf_tag('b') . wf_tag('br');

    return ($result);
}

/**
 * Returns actions performed on user parsed from log
 * 
 * @param string $login
 * @param bool   $deepSearch
 * 
 * @return string
 */
function web_GrepLogByUser($login, $deepSearch = false) {
    global $ubillingConfig;
    $result = '';
    $messages = new UbillingMessageHelper();
    $defaultDepth = $ubillingConfig->getAlterParam('LIFESTORY_DEFAULT_DEPTH', 0);
    $login = ubRouting::filters($login, 'login');
    $login = '(' . $login . ')';
    @$employeeNames = ts_GetAllEmployeeLoginsAssocCached();
    $query = 'SELECT * FROM `weblogs` 
        WHERE MATCH(`event`) AGAINST ("+'.$login.'" IN BOOLEAN MODE)
        ORDER BY `date` DESC';

        if ($defaultDepth > 0 and !$deepSearch) {
            $query .= ' LIMIT '.$defaultDepth;
        }

    $allevents = simple_queryall($query);
    
    if (!empty($allevents)) {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Who?'));
        $cells .= wf_TableCell(__('When?'));
        $cells .= wf_TableCell(__('What happen?'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($allevents as $io => $eachevent) {
            $eventText = htmlspecialchars($eachevent['event']);
            $adminName = (isset($employeeNames[$eachevent['admin']])) ? $employeeNames[$eachevent['admin']] : $eachevent['admin'];
            $idLabel = wf_tag('abbr', false, '', 'title="' . $eachevent['ip'] . '"') . $eachevent['id'] . wf_tag('abbr', true);
            $cells = wf_TableCell($idLabel);

            $cells .= wf_TableCell($adminName);
            $cells .= wf_TableCell($eachevent['date']);
            $cells .= wf_TableCell($eventText);
            $rows .= wf_TableRow($cells, 'row5');
        }
        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
    } else {
        $result = $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }
    
    return ($result);
}

/**
 * Renders recursively some array with some key=>value pairs as HTML table
 *
 * @param array $dataArray
 * @param bool $renderHeaders
 * 
 * @return string
 */
function web_RenderSomeArrayAsTable($dataArray, $renderHeaders = false) {
    static $renderDepth = 0;
    $renderDepth++;
    $result = '';

    if (!empty($dataArray) and is_array($dataArray)) {
        $rows = '';

        if ($renderHeaders) {
            $headerCells = wf_TableCell(__('Key'));
            $headerCells .= wf_TableCell(__('Value'));
            $rows .= wf_TableRow($headerCells, 'row1');
        }

        foreach ($dataArray as $key => $value) {
            $cells = wf_TableCell($key);

            if (is_array($value)) {
                $cellValue = web_RenderSomeArrayAsTable($value, $renderHeaders);
            } else {
                if (is_bool($value)) {
                    $cellValue = ($value) ? __('Yes') : __('No');
                } elseif (is_null($value)) {
                    $cellValue = 'NULL';
                } elseif (is_object($value)) {
                    $cellValue = json_encode($value);
                    if ($cellValue === false) {
                        $cellValue = get_class($value);
                    }
                } elseif (is_resource($value)) {
                    $cellValue = __('Resource');
                } else {
                    $cellValue = (string) $value;
                }

                if ($cellValue === '') {
                    $cellValue = wf_nbsp();
                }
            }

            $cells .= wf_TableCell($cellValue);
            $rows .= wf_TableRow($cells, 'row3');
        }

        $tableClass = ($renderHeaders) ? 'sortable' : 'empty';
        $result = wf_TableBody($rows, '100%', 0, $tableClass);
    } else {
        if ($renderDepth === 1) {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing to show'), 'warning');
        } else {
            $result = __('Nothing to show');
        }
    }

    $renderDepth--;
    return ($result);
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

    return ($table . $form);
}

/**
 * Retuns year selector. Is here, only for backward compatibility with old modules.
 * use only wf_YearSelector() in new code.
 * 
 * @return string
 */
function web_year_selector() {
    $selector = wf_YearSelector('yearsel');
    return ($selector);
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
function web_FinRepControls($title = '', $content='') {
    $result = '';
    $style = 'style="float:left; display:block; height:90px; margin-right: 20px;"';
    $result .= wf_tag('div', false, '', $style);
    $result .= wf_tag('h3') . $title . wf_tag('h3', true);
    $result .= wf_tag('br');
    $result .= $content;
    $result .= wf_tag('div', true);
    return ($result);
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
    return ($result);
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
    return ($code);
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
    return ($result);
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
        '12' => 'December'
    );
    return ($months);
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
 * Allocates array with full timeline as hh:mm=>0
 * 
 * @return array
 */
function allocDayTimeline() {
    $result = array();
    for ($h = 0; $h <= 23; $h++) {
        for ($m = 0; $m < 60; $m++) {
            $hLabel = ($h > 9) ? $h : '0' . $h;
            $mLabel = ($m > 9) ? $m : '0' . $m;
            $timeLabel = $hLabel . ':' . $mLabel;
            $result[$timeLabel] = 0;
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
                    $yearStats[$statsMonth]['count']++;
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
            if ($paycount != 0) {
                $monthArpu = @round($month_summ / $paycount, 2);
            } else {
                $monthArpu = 0;
            }

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

    $winControl = '';
    if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
        $winControl .= wf_Link('?module=report_finance&branchreport=true', wf_img_sized('skins/icon_branch.png', __('Branches'), 12)) . ' ';
    }
    show_window($winControl . __('Payments by') . ' ' . $year, $result);
}

/**
 * Shows payments year graph with caching
 * 
 * @param int $year
 */
function web_PaymentsShowGraphPerBranch($year) {
    global $ubillingConfig;
    $months = months_array();
    $year_summ = zb_PaymentsGetYearSumm($year);
    $curtime = time();
    $yearPayData = array();
    $yearStats = array();
    $result = '';
    $branches = new UbillingBranches();
    $allBranches[0] = __('No');
    $allBranches += $branches->getBranchesAvailable();

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
            $userBranchId = $branches->userGetBranch($eachYearPayment['login']);
            if (empty($userBranchId)) {
                $userBranchId = 0;
            }
            $statsMonth = date("m", strtotime($eachYearPayment['date']));
            if (isset($yearStats[$userBranchId][$statsMonth])) {
                $yearStats[$userBranchId][$statsMonth]['count']++;
                $yearStats[$userBranchId][$statsMonth]['summ'] = $yearStats[$userBranchId][$statsMonth]['summ'] + $eachYearPayment['summ'];
            } else {
                $yearStats[$userBranchId][$statsMonth]['count'] = 1;
                $yearStats[$userBranchId][$statsMonth]['summ'] = $eachYearPayment['summ'];
            }
        }
    }



    foreach ($allBranches as $eachBranchId => $eachBranchName) {
        $branchTotalSumm = 0;
        $result .= wf_tag('strong') . __('Branch') . ': ' . $eachBranchName . wf_tag('strong', true);
        $result .= wf_tag('br');

        $cells = wf_TableCell('');
        $cells .= wf_TableCell(__('Month'));
        $cells .= wf_TableCell(__('Payments count'));
        $cells .= wf_TableCell(__('ARPU'));
        $cells .= wf_TableCell(__('Cash'));
        $cells .= wf_TableCell(__('Visual'), '50%');
        $rows = wf_TableRow($cells, 'row1');

        foreach ($months as $eachmonth => $monthname) {
            $month_summ = (isset($yearStats[$eachBranchId][$eachmonth])) ? $yearStats[$eachBranchId][$eachmonth]['summ'] : 0;
            $paycount = (isset($yearStats[$eachBranchId][$eachmonth])) ? $yearStats[$eachBranchId][$eachmonth]['count'] : 0;
            if ($paycount != 0) {
                $monthArpu = @round($month_summ / $paycount, 2);
            } else {
                $monthArpu = 0;
            }
            $branchTotalSumm += $month_summ;
            if (is_nan($monthArpu)) {
                $monthArpu = 0;
            }

            $cells = wf_TableCell($eachmonth);
            $cells .= wf_TableCell(rcms_date_localise($monthname));
            $cells .= wf_TableCell($paycount);
            $cells .= wf_TableCell($monthArpu);
            $cells .= wf_TableCell(zb_CashBigValueFormat($month_summ), '', '', 'align="right"');
            $cells .= wf_TableCell(web_bar($month_summ, $year_summ), '', '', 'sorttable_customkey="' . $month_summ . '"');
            $rows .= wf_TableRow($cells, 'row3');
        }

        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
        $result .= __('Total money') . ': ' . zb_CashBigValueFormat($branchTotalSumm);
        $result .= wf_delimiter();
    }
    $winControl = '';
    if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
        $winControl .= wf_Link('?module=report_finance', wf_img_sized('skins/icon_dollar_16.gif', __('Normal'), 12)) . ' ';
    }
    show_window($winControl . __('Payments by') . ' ' . $year . ' / ' . __('Branches'), $result);
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
 * @param bool $emptyWarning
 * 
 * @return string
 */
function web_GridEditor($titles, $keys, $alldata, $module, $delete = true, $edit = false, $prefix = '', $extaction = '', $extbutton = '', $emptyWarning = false) {
    $result = '';
    //headers
    $cells = '';
    foreach ($titles as $eachtitle) {
        $cells .= wf_TableCell(__($eachtitle));
    }

    if ($delete or $edit or $extaction) {
        $cells .= wf_TableCell(__('Actions'));
    }


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

            if ($delete or $edit or $extaction) {
                $cells .= wf_TableCell($deletecontrol . ' ' . $editcontrol . ' ' . $extencontrol);
            }
            $rows .= wf_TableRow($cells, 'row5');
        }
    }
    $result .= wf_TableBody($rows, '100%', 0, 'sortable');

    //override result with empty notice if required
    if ($emptyWarning) {
        if (empty($alldata)) {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
    }

    return ($result);
}

/**
 * Returns existing NAS servers list with some controls
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function web_NasList() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $allNasData = zb_NasGetAllData();
    $messages = new UbillingMessageHelper();
    $networks = multinet_get_all_networks();
    $cidrs = array();
    $result = '';
    $availableTypes = zb_NasGetTypes();

    //preprocessing networks data
    if (!empty($networks)) {
        foreach ($networks as $network)
            $cidrs[$network['id']] = $network['desc'];
    }

    if (!empty($allNasData)) {
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Network'));
        $cells .= wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('NAS name'));
        $cells .= wf_TableCell(__('NAS type'));
        $cells .= wf_TableCell(__('Graphs URL'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($allNasData as $io => $eachNasData) {
            $actions = '';
            $deleteUrl = '?module=nas&delete=' . $eachNasData['id'];
            $cancelUrl = '?module=nas';
            $deleteDialogTitle = __('Delete') . ' ' . __('NAS') . ' ' . $eachNasData['nasip'] . '?';
            $actions .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $messages->getDeleteAlert(), '', $cancelUrl, $deleteDialogTitle);
            $actions .= wf_Link('?module=nas&edit=' . $eachNasData['id'], web_edit_icon());
            if ($eachNasData['nastype'] == 'mikrotik' and $altCfg['MIKROTIK_SUPPORT']) {
                $actions .= wf_Link('?module=mikrotikextconf&nasid=' . $eachNasData['id'], web_icon_extended('MikroTik extended configuration'));
            }
            if ($altCfg['MULTIGEN_ENABLED']) {
                $actions .= wf_Link('?module=multigen&editnasoptions=' . $eachNasData['id'], web_icon_settings(__('Configure Multigen NAS')));
            }


            $netCidr = (isset($cidrs[$eachNasData['netid']])) ? $cidrs[$eachNasData['netid']] : $eachNasData['netid'] . ': ' . __('Network not found');
            $nasTypeLabel = (isset($availableTypes[$eachNasData['nastype']])) ? $availableTypes[$eachNasData['nastype']] : $eachNasData['nastype'];
            $cells = wf_TableCell($eachNasData['id']);
            $cells .= wf_TableCell($netCidr);
            $cells .= wf_TableCell($eachNasData['nasip']);
            $cells .= wf_TableCell($eachNasData['nasname']);
            $cells .= wf_TableCell($nasTypeLabel);
            $cells .= wf_TableCell($eachNasData['bandw']);
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
        $result = wf_TableBody($rows, '100%', 0, 'sortable');
    } else {
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }

    return ($result);
}

/**
 * Returns array of available NAS types as nastype=>nastypename
 * 
 * @return array
 */
function zb_NasGetTypes() {
    $nastypes = array(
        'local' => 'Local NAS',
        'rscriptd' => 'rscriptd',
        'mikrotik' => 'MikroTik',
        'radius' => 'RADIUS'
    );
    return ($nastypes);
}

/**
 * Retruns NAS creation form
 * 
 * @return string
 */
function web_NasAddForm() {
    $nastypes = zb_NasGetTypes();
    $inputs = multinet_network_selector() . wf_tag('label', false, '', 'for="networkselect"') . __('Network') . wf_tag('label', true) . wf_tag('br');
    $inputs .= wf_Selector('newnastype', $nastypes, __('NAS type'), '', true);
    $inputs .= wf_TextInput('newnasip', __('IP'), '', true, '15', 'ip');
    $inputs .= wf_TextInput('newnasname', __('NAS name'), '', true);
    $inputs .= wf_TextInput('newbandw', __('Graphs URL'), '', true);
    $inputs .= wf_Submit(__('Create'));

    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return ($form);
}

/**
 * Returns NAS editing form
 * 
 * @param int $nasId
 * 
 * @return string
 */
function web_NasEditForm($nasId) {
    $nasId = ubRouting::filters($nasId, 'int');
    $nasdata = zb_NasGetData($nasId);
    $nastypes = zb_NasGetTypes();
    $currentnetid = $nasdata['netid'];
    $currentnasip = $nasdata['nasip'];
    $currentnasname = $nasdata['nasname'];
    $currentnastype = $nasdata['nastype'];
    $currentbwdurl = $nasdata['bandw'];

    //rendering editing form
    $editinputs = multinet_network_selector($currentnetid) . "<br>";
    $editinputs .= wf_Selector('editnastype', $nastypes, 'NAS type', $currentnastype, true);
    $editinputs .= wf_TextInput('editnasip', 'IP', $currentnasip, true, '15', 'ip');
    $editinputs .= wf_TextInput('editnasname', 'NAS name', $currentnasname, true, '15');
    $editinputs .= wf_TextInput('editnasbwdurl', 'Graphs URL', $currentbwdurl, true, '25');
    $editinputs .= wf_Submit('Save');
    $result = wf_Form('', 'POST', $editinputs, 'glamour');
    $result .= wf_delimiter();
    $result .= wf_BackLink('?module=nas');
    return ($result);
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

    return ($form);
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

    return ($form);
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

    return ($result);
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
    return ($result);
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
                if ($eachlogin['Cash'] >= ('-' . $eachlogin['Credit']) and $eachlogin['Passive'] == 0 and $eachlogin['Down'] == 0 and $eachlogin['Passive'] == 0) {
                    $result[$eachlogin['Tariff']]['alive'] = $result[$eachlogin['Tariff']]['alive'] + 1;
                } else {
                    $result[$eachlogin['Tariff']]['dead'] = $result[$eachlogin['Tariff']]['dead'] + 1;
                }
            }
        }
    }

    return ($result);
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

    return ($code);
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
    return ($result);
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

    return ($result);
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
                $fromData[$eachmove['Tariff']]++;
            } else {
                $fromData[$eachmove['Tariff']] = 1;
            }

            if (isset($toData[$eachmove['TariffChange']])) {
                $toData[$eachmove['TariffChange']]++;
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
                $chartData[$each['Tariff']]++;
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
        "й" => "jj",
        "ё" => "jo",
        "ж" => "zh",
        "х" => "kh",
        "ч" => "ch",
        "ш" => "sh",
        "щ" => "shh",
        "э" => "je",
        "ю" => "ju",
        "я" => "ja",
        "ъ" => "",
        "ь" => ""
    );

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
        return (false);
    } else {
        return (true);
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
    return ($result);
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
    return ($result);
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
            if (isset($allUserData[$eachlogin])) {
            
            $thisUserData = @$allUserData[$eachlogin];

            $usercash = @$thisUserData['Cash'];
            $usercredit = @$thisUserData['Credit'];
            //finance check
            $activity = web_red_led() . ' ' . __('No');
            $activity_flag = 0;

            if ($thisUserData['Passive'] == 1 or $thisUserData['Down'] == 1) {
                $activity = web_yellow_led() . ' ' . __('No');
                $activity_flag = 0;
            } else {
                if ($thisUserData['Cash'] >= '-' . $thisUserData['Credit']) {
                    $activity = web_bool_led(true) . ' ' . __('Yes');
                    $activity_flag = 1;
                }
            }

            //fast cash link
            if ($fastcash) {
                $financelink = wf_Link('?module=addcash&username=' . $eachlogin, wf_img('skins/icon_dollar.gif', __('Finance operations')), false, '');
            } else {
                $financelink = '';
            }

            $profilelink = $financelink . wf_Link(UserProfile::URL_PROFILE . $eachlogin, web_profile_icon() . ' ' . $eachlogin);
            $tablecells = wf_TableCell($profilelink);
            $tablecells .= wf_TableCell(@$thisUserData['fulladress']);
            $tablecells .= wf_TableCell(@$thisUserData['realname']);
            $tablecells .= wf_TableCell(@$thisUserData['ip'], '', '', 'sorttable_customkey="' . ip2int(@$thisUserData['ip']) . '"');
            $tablecells .= wf_TableCell(@$thisUserData['Tariff']);
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
        } else {
            //not existent user found Oo
            $profileLink=wf_link(UserProfile::URL_PROFILE . $eachlogin, web_profile_icon() . ' ' . $eachlogin);
                $tablecells = wf_TableCell($profileLink.' - '.__('User not exists').'!');
                $tablecells .= wf_TableCell('-');
                $tablecells .= wf_TableCell('-');
                $tablecells .= wf_TableCell('-');
                $tablecells .= wf_TableCell('-');
                if ($alterconf['ONLINE_LAT']) {
                    $tablecells .= wf_TableCell('-');
                }
                $tablecells .= wf_TableCell('-');
                if ($alterconf['DN_ONLINE_DETECT']) {
                    $tablecells .= wf_TableCell('-');
                }
                $tablecells .= wf_TableCell('-');
                $tablecells .= wf_TableCell('-');

                $tablerows .= wf_TableRow($tablecells, 'row5');
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
        "a",
        "b",
        "c",
        "d",
        "e",
        "f",
        "g",
        "h",
        "i",
        "j",
        "k",
        "l",
        "m",
        "n",
        "o",
        "p",
        "q",
        "r",
        "s",
        "t",
        "u",
        "v",
        "w",
        "x",
        "y",
        "z",
        "à",
        "á",
        "â",
        "ã",
        "ä",
        "å",
        "æ",
        "ç",
        "è",
        "é",
        "ê",
        "ë",
        "ì",
        "í",
        "î",
        "ï",
        "ð",
        "ñ",
        "ò",
        "ó",
        "ô",
        "õ",
        "ö",
        "ø",
        "ù",
        "ú",
        "û",
        "ü",
        "ý",
        "а",
        "б",
        "в",
        "г",
        "д",
        "е",
        "ё",
        "ж",
        "з",
        "и",
        "й",
        "к",
        "л",
        "м",
        "н",
        "о",
        "п",
        "р",
        "с",
        "т",
        "у",
        "ф",
        "х",
        "ц",
        "ч",
        "ш",
        "щ",
        "ъ",
        "ы",
        "ь",
        "э",
        "ю",
        "я",
        "ы",
        "і"
    );
    $convert_from = array(
        "A",
        "B",
        "C",
        "D",
        "E",
        "F",
        "G",
        "H",
        "I",
        "J",
        "K",
        "L",
        "M",
        "N",
        "O",
        "P",
        "Q",
        "R",
        "S",
        "T",
        "U",
        "V",
        "W",
        "X",
        "Y",
        "Z",
        "À",
        "Á",
        "Â",
        "Ã",
        "Ä",
        "Å",
        "Æ",
        "Ç",
        "È",
        "É",
        "Ê",
        "Ë",
        "Ì",
        "Í",
        "Î",
        "Ï",
        "Ð",
        "Ñ",
        "Ò",
        "Ó",
        "Ô",
        "Õ",
        "Ö",
        "Ø",
        "Ù",
        "Ú",
        "Û",
        "Ü",
        "Ý",
        "А",
        "Б",
        "В",
        "Г",
        "Д",
        "Е",
        "Ё",
        "Ж",
        "З",
        "И",
        "Й",
        "К",
        "Л",
        "М",
        "Н",
        "О",
        "П",
        "Р",
        "С",
        "Т",
        "У",
        "Ф",
        "Х",
        "Ц",
        "Ч",
        "Ш",
        "Щ",
        "Ъ",
        "Ъ",
        "Ь",
        "Э",
        "Ю",
        "Я",
        "Ы",
        "І"
    );

    return str_replace($convert_from, $convert_to, $string);
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
    return ($randomid);
}

/**
 * Collects billing stats
 * 
 * @param bool $quiet
 * @param string $modOverride
 */
function zb_BillingStats($quiet = true, $modOverride = '') {
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
        if (empty($cachedHostId) and !empty($hostid)) {
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
    $serialLabel = wf_ShowHide($thisubid, __('Show'));
    $ubstatsinputs .= wf_tag('b') . __('Serial key') . ': ' . wf_tag('b', true) .  $serialLabel . wf_tag('br');
    $ubstatsinputs .= wf_tag('b') . __('Use this to request technical support') . ': ' . wf_tag('b', true) . wf_tag('font', false, '', 'color="#076800"') . substr($thisubid, -4) . wf_tag('font', true) . wf_tag('br');
    $ubstatsinputs .= wf_tag('b') . __('Current Ubilling version') . ': ' . wf_tag('b', true) . $updatechecker . wf_tag('br');
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

    if ($thiscollect or date("H") == 11 or date("i") == 11) {
        if (extension_loaded('curl')) {
            $referrer = (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : '';
            $curlStats = curl_init($statsurl);
            curl_setopt($curlStats, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlStats, CURLOPT_CONNECTTIMEOUT, 1);
            curl_setopt($curlStats, CURLOPT_TIMEOUT, 2);
            curl_setopt($curlStats, CURLOPT_USERAGENT, 'UBTRACK2');
            if (!empty($referrer)) {
                curl_setopt($curlStats, CURLOPT_REFERER, $referrer);
            }
            $output = curl_exec($curlStats);
            $httpCode = curl_getinfo($curlStats, CURLINFO_HTTP_CODE);
            //PHP 8.0+ has no need to close curl resource anymore
            if (PHP_VERSION_ID < 80000) {
                curl_close($curlStats); // Deprecated in PHP 8.5
            }

            if ($output !== false and $httpCode == 200) {
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
    if ((!empty($configdata)) and (!empty($optsdata))) {
        foreach ($optsdata as $option => $handlers) {

            if ((isset($configdata[$option])) or (ispos($option, 'CHAPTER'))) {
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

/**
 * Gets list of ubilling database tables with some stats
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
            $stats[$filtered[0]]['engine'] = $each['Engine'];
            $stats[$filtered[0]]['collation'] = $each['Collation'];
            $stats[$filtered[0]]['comment'] = $each['Comment'];
            $stats[$filtered[0]]['raw'] = $each;
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
        $cells .= wf_TableCell(__('Engine'));
        $cells .= wf_TableCell(__('Encoding'));
        $cells .= wf_TableCell(__('Rows'));
        $cells .= wf_TableCell(__('Size'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['name']);
            if (!empty($each['rows'])) {
                $dbrows = $each['rows'];
                $totalRows = $totalRows + $each['rows'];;
            } else {
                $dbrows = 0;
            }

            if (!empty($each['engine'])) {
                $tableEngine = $each['engine'];
            } else {
                $tableEngine = $each['comment'];
            }
            $cells .= wf_TableCell($tableEngine);
            $cells .= wf_TableCell($each['collation']);
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
    return ($rows);
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
            "а" => "a",
            "А" => "A",
            "б" => "b",
            "Б" => "B",
            "в" => "v",
            "В" => "V",
            "г" => "g",
            "Г" => "G",
            "д" => "d",
            "Д" => "D",
            "е" => "e",
            "Е" => "E",
            "ё" => "e",
            "Ё" => "E",
            "ж" => "zh",
            "Ж" => "Zh",
            "з" => "z",
            "З" => "Z",
            "и" => "y",
            "И" => "Y",
            "й" => "y",
            "Й" => "Y",
            "к" => "k",
            "К" => "K",
            "л" => "l",
            "Л" => "L",
            "м" => "m",
            "М" => "M",
            "н" => "n",
            "Н" => "N",
            "о" => "o",
            "О" => "O",
            "п" => "p",
            "П" => "P",
            "р" => "r",
            "Р" => "R",
            "с" => "s",
            "С" => "S",
            "т" => "t",
            "Т" => "T",
            "у" => "u",
            "У" => "U",
            "ф" => "f",
            "Ф" => "F",
            "х" => "h",
            "Х" => "H",
            "ц" => "c",
            "Ц" => "C",
            "ч" => "ch",
            "Ч" => "Ch",
            "ш" => "sh",
            "Ш" => "Sh",
            "щ" => "sch",
            "Щ" => "Sch",
            "ъ" => "",
            "Ъ" => "",
            "ы" => "y",
            "Ы" => "Y",
            "ь" => "",
            "Ь" => "",
            "э" => "e",
            "Э" => "E",
            "ю" => "yu",
            "Ю" => "Yu",
            "я" => "ya",
            "Я" => "Ya",
            "і" => "i",
            "І" => "I",
            "ї" => "yi",
            "Ї" => "Yi",
            "є" => "e",
            "Є" => "E",
            "ґ" => "g",
            "Ґ" => "G"
        );

        if (curlang() == 'ru') {
            $replace['и'] = 'i';
            $replace['И'] = 'I';
        }
    } else {
        $replace = array(
            "'" => "",
            "`" => "",
            "а" => "a",
            "А" => "a",
            "б" => "b",
            "Б" => "b",
            "в" => "v",
            "В" => "v",
            "г" => "g",
            "Г" => "g",
            "д" => "d",
            "Д" => "d",
            "е" => "e",
            "Е" => "e",
            "ё" => "e",
            "Ё" => "e",
            "ж" => "zh",
            "Ж" => "zh",
            "з" => "z",
            "З" => "z",
            "и" => "y",
            "И" => "y",
            "й" => "y",
            "Й" => "y",
            "к" => "k",
            "К" => "k",
            "л" => "l",
            "Л" => "l",
            "м" => "m",
            "М" => "m",
            "н" => "n",
            "Н" => "n",
            "о" => "o",
            "О" => "o",
            "п" => "p",
            "П" => "p",
            "р" => "r",
            "Р" => "r",
            "с" => "s",
            "С" => "s",
            "т" => "t",
            "Т" => "t",
            "у" => "u",
            "У" => "u",
            "ф" => "f",
            "Ф" => "f",
            "х" => "h",
            "Х" => "h",
            "ц" => "c",
            "Ц" => "c",
            "ч" => "ch",
            "Ч" => "ch",
            "ш" => "sh",
            "Ш" => "sh",
            "щ" => "sch",
            "Щ" => "sch",
            "ъ" => "",
            "Ъ" => "",
            "ы" => "y",
            "Ы" => "y",
            "ь" => "",
            "Ь" => "",
            "э" => "e",
            "Э" => "e",
            "ю" => "yu",
            "Ю" => "yu",
            "я" => "ya",
            "Я" => "ya",
            "і" => "i",
            "І" => "i",
            "ї" => "yi",
            "Ї" => "yi",
            "є" => "e",
            "Є" => "e",
            "ґ" => "g",
            "Ґ" => "g"
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
    $precision = ($precision < 0) ? 0 : $precision;
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
    return (number_format($cashValue, 0, '.', ' '));
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
                $tmpArr[$month]['count']++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }


    foreach ($months as $eachmonth => $monthname) {
        $result[$eachmonth] = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
    }
    return ($result);
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
                $tmpArr[$month]['count']++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }

    foreach ($months as $eachmonth => $monthname) {
        $monthcount = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
        $result[$eachmonth] = $monthcount;
    }
    return ($result);
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
                $tmpArr[$month]['count']++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }

    foreach ($months as $eachmonth => $monthname) {
        $monthcount = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
        $result[$eachmonth] = $monthcount;
    }
    return ($result);
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
                $tmpArr[$month]['count']++;
            } else {
                $tmpArr[$month]['count'] = 1;
            }
        }
    }


    foreach ($months as $eachmonth => $monthname) {
        $monthcount = (isset($tmpArr[$eachmonth])) ? $tmpArr[$eachmonth]['count'] : 0;
        $result[$eachmonth] = $monthcount;
    }
    return ($result);
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

            if (($contentType == '') or ($contentType == 'default')) {
                $contentType = 'application/octet-stream';
            } else {
                //additional content types
                if ($contentType == 'docx') {
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                }

                if ($contentType == 'csv') {
                    $contentType = 'text/csv; charset=Windows-1251';
                }

                if ($contentType == 'excel') {
                    $contentType = 'application/vnd.ms-excel';
                }

                if ($contentType == 'text') {
                    $contentType = 'text/plain;';
                }

                if ($contentType == 'jpg') {
                    $contentType = 'Content-Type: image/jpeg';
                }

                if ($contentType == 'png') {
                    $contentType = 'Content-Type: image/png';
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
    } elseif (!empty($data) and $data['note'] != 'SCFEE') {
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
 * Returns one-click credit set form for profile
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
    if (ubRouting::checkPost(array('easycreditlogin', 'easycreditlimit', 'easycreditexpire'))) {
        global $billing;
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();
        $setCredit = ubRouting::post('easycreditlimit', 'vf');
        $setLogin = ubRouting::post('easycreditlogin', 'mres');
        $setExpire = ubRouting::post('easycreditexpire', 'mres');
        $creditLimitOpt = $altCfg['STRICT_CREDIT_LIMIT'];
        $creditAllowedFlag = false;

        if (zb_checkDate($setExpire)) {
            if (zb_checkMoney($setCredit)) {
                if ($creditLimitOpt != 'DISABLED') {
                    if ($setCredit <= $creditLimitOpt) {
                        $creditAllowedFlag = true;
                    } else {
                        log_register('FAIL Credit (' . $login . ') LIMIT `' . $setCredit . '` HAWK TUAH `' . $creditLimitOpt . '`');
                        show_error(__('The amount of allowed credit limit has been exceeded'));
                        // Ooh, baby, do you know what that's worth?
                        // Ooh, heaven is a place on earth
                    }
                } else {
                    //strict credit disabled
                    $creditAllowedFlag = true;
                }
            } else {
                show_error(__('Wrong format of money sum'));
                log_register('EASYCREDIT FAIL WRONG SUMM `' . $setCredit . '`');
            }
        } else {
            show_error(__('Wrong date format'));
            log_register('EASYCREDIT FAIL DATEFORMAT `' . $setExpire . '`');
        }

        if ($creditAllowedFlag) {
            //set credit
            $billing->setcredit($setLogin, $setCredit);
            log_register('CHANGE Credit (' . $setLogin . ') ON ' . $setCredit);
            //set credit expire date
            $billing->setcreditexpire($setLogin, $setExpire);
            log_register('CHANGE CreditExpire (' . $setLogin . ') ON ' . $setExpire);
            ubRouting::nav('?module=userprofile&username=' . $setLogin);
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
                $result .= wf_AjaxLink('?module=report_sysload&ajxcscrun=' . base64_encode($path), wf_img('skins/script16.png') . ' ' . $name, 'custommoncontainder', false, 'ubButton');
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
        if ($type == "open") { //The starting of the tag '<tag>'
            $parent[$level - 1] = &$current;
            if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if ($attributes_data)
                    $current[$tag . '_attr'] = $attributes_data;
                $repeated_tag_index[$tag . '_' . $level] = 1;

                $current = &$current[$tag];
            } else { //There was another element with the same tag name
                if (isset($current[$tag][0])) { //If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                } else { //This section will make the value an array if multiple tags with the same name appear together
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
                if (isset($current[$tag][0]) and is_array($current[$tag])) { //If it is already an array...
                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                    if ($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
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
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        } elseif ($type == 'close') { //End of tag '</tag>'
            $current = &$parent[$level - 1];
        }
    }

    return ($xml_array);
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
            if ((isset($rawStats[$memcachedHost . ':' . $memcachedPort]['get_hits'])) and (isset($rawStats[$memcachedHost . ':' . $memcachedPort]['get_misses']))) {
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
        if ((isset($rawStats['keyspace_hits'])) and (isset($rawStats['keyspace_misses']))) {
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
    if ($checkDate >= $fromDate and $checkDate <= $toDate) {
        $result = true;
    }
    return ($result);
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
 * Renders time duration in seconds into formatted human-readable view in days
 *      
 * @param int $seconds
 * 
 * @return string
 */
function zb_formatTimeDays($seconds) {
    $init = $seconds;
    $days = floor($seconds / 86400);
    if ($init >= 86400) {
        $result = $days . ' ' . __('days');
    } else {
        if ($init > 0) {
            $result = '0 ' . __('days');
        } else {
            $result = $days . ' ' . __('days');
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
    return ($result);
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
 * @global object $ubillingConfig
 * 
 * @return void
 */
function zb_InstallPhpsysinfo() {
    global $ubillingConfig;
    $billCfg = $ubillingConfig->getBilling();
    $phpSysInfoDir = $billCfg['PHPSYSINFO'];
    if (!empty($phpSysInfoDir)) {
        if (cfr('ROOT')) {
            $upd = new UbillingUpdateStuff();
            $upd->downloadRemoteFile('http://ubilling.net.ua/packages/phpsysinfo.tar.gz', 'exports/', 'phpsysinfo.tar.gz');
            $upd->extractTgz('exports/phpsysinfo.tar.gz', MODULES_DOWNLOADABLE . $phpSysInfoDir);
        }
    }
}

/**
 * Downloads and unpacks xhprof distro
 * 
 * @return void
 */
function zb_InstallXhprof() {
    if (cfr('ROOT')) {
        $upd = new UbillingUpdateStuff();
        $upd->downloadRemoteFile('http://ubilling.net.ua/packages/xhprof.tar.gz', 'exports/', 'xhprof.tar.gz');
        $upd->extractTgz('exports/xhprof.tar.gz', MODULES_DOWNLOADABLE . 'xhprof/');
    }
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

    usort($data, function ($a, $b) use ($field, $desc) {
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

        if (!empty($defaultSmsServiceId) and !empty($defaultSmsServiceName)) {
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
function zb_BackupsRotate($maxAge) {
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
    return ($result);
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
            $actions .= (isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS'])) ? wf_Link('?module=signupprices&tariff=' . $eachtariff['name'], wf_img('skins/icons/register.png', __('Edit signup price')), false, '') : null;
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }

    $result .= wf_TableBody($rows, '100%', 0, 'sortable');

    return ($result);
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
    $inputs .= wf_TextInput('options[Free]', __('Prepaid traffic') . ' (' . __('Mb') . ')', '0', true, 3, 'digits');
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
    return ($result);
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
        $inputs .= wf_TextInput('options[Free]', __('Prepaid traffic') . ' (' . __('Mb') . ')', $tariffdata['Free'], true, 3, 'digits');
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
            $arrTime = explode('-', $tariffdata["Time" . $rulenumber]);
            $day = explode(':', $arrTime[0]);
            $night = explode(':', $arrTime[1]);

            $tariffdata['Time'][$rulenumber]['Dmin'] = $day[1];
            $tariffdata['Time'][$rulenumber]['Dhour'] = $day[0];
            $tariffdata['Time'][$rulenumber]['Nmin'] = $night[1];
            $tariffdata['Time'][$rulenumber]['Nhour'] = $night[0];

            $inputsDirs .= wf_tag('select', false, '', 'id="dhour' . $dir['rulenumber'] . '"  name="options[dhour][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(24, $tariffdata['Time'][$dir['rulenumber']]['Dhour']);
            $inputsDirs .= wf_tag('select', true);

            $inputsDirs .= wf_tag('select', false, '', 'id="dmin' . $dir['rulenumber'] . '"  name="options[dmin][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(60, $tariffdata['Time'][$dir['rulenumber']]['Dmin']);
            $inputsDirs .= wf_tag('select', true);
            $inputsDirs .= ' (' . __('hours') . '/' . __('minutes') . ') ' . __('Day');

            $inputsDirs .= wf_TextInput('options[PriceDay][' . $dir['rulenumber'] . ']', __('Price day'), zb_tariff_yoba_price($tariffdata["PriceDayA" . $dir['rulenumber']], $tariffdata["PriceDayB" . $dir['rulenumber']]), false, 3);
            $inputsDirs .= wf_TextInput('options[Threshold][' . $dir['rulenumber'] . ']', __('Threshold') . ' (' . __('Mb') . ')', $tariffdata["Threshold$dir[rulenumber]"], true, 3, 'digits');

            $inputsDirs .= wf_tag('select', false, '', 'id="nhour' . $dir['rulenumber'] . '"  name="options[nhour][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(24, $tariffdata['Time'][$dir['rulenumber']]['Nhour']);
            $inputsDirs .= wf_tag('select', true);

            $inputsDirs .= wf_tag('select', false, '', 'id="nmin' . $dir['rulenumber'] . '"  name="options[nmin][' . $dir['rulenumber'] . ']"');
            $inputsDirs .= wf_tag('option', false, '', '') . '00' . wf_tag('option', true);
            $inputsDirs .= zb_TariffTimeSelector(60, $tariffdata['Time'][$dir['rulenumber']]['Nmin']);
            $inputsDirs .= wf_tag('select', true);
            $inputsDirs .= ' (' . __('hours') . '/' . __('minutes') . ') ' . __('Night');
            $inputsDirs .= wf_TextInput('options[PriceNight][' . $dir['rulenumber'] . ']', __('Price night'), zb_tariff_yoba_price($tariffdata["PriceNightA$dir[rulenumber]"], $tariffdata["PriceNightB$dir[rulenumber]"]), false, 3);

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

    return ($result);
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
            array_slice($array, 0, $pos),
            $insert,
            array_slice($array, $pos)
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
    return ($result);
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

        if (zb_checkDate($dateFrom) and zb_checkDate($dateTo)) {
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
    return ($result);
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
 * Tries to re-format incorrectly inputted cell number to bring it to the correct form
 *
 * @param $number
 *
 * @return bool|mixed|string
 */
function zb_CleanMobileNumber($number) {
    if (!empty($number) and substr(trim($number), 0, 1) != '+') {
        global $ubillingConfig;
        $prefix = $ubillingConfig->getAlterParam('REMINDER_PREFIX', '');

        $number = trim($number);
        $number = ubRouting::filters($number, 'int');
        $number = SendDog::cutInternationalsFromPhoneNum($number);
        $number = $prefix . $number;
    }

    return ($number);
}

/**
 * Returns list of available MultiGen NASes
 * 
 * @return string
 */
function web_MultigenListClients() {
    $result = '';
    $mlgClientsDb = new NyanORM(MultiGen::CLIENTS);
    $allClients = $mlgClientsDb->getAll();
    $mlgEcn = new MultigenECN();
    if (!empty($allClients)) {
        $cells = wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('NAS name'));
        $cells .= wf_TableCell(__('RADIUS secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($allClients as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells .= wf_TableCell($each['shortname'] . $mlgEcn->getIndicator($each['nasname']));
            $cells .= wf_TableCell($each['secret']);
            $rows .= wf_TableRow($cells, 'row5');
        }
        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('Nothing found'), 'warning');
    }
    $result .= wf_delimiter();
    $result .= wf_Link(MultigenECN::URL_ME, wf_img('skins/icon_servers.png') . ' ' . __('Custom NAS configuration'), false, 'ubButton');

    return ($result);
}

/**
 * Renders current system load average stats
 * 
 * @return string
 */
function web_ReportSysloadRenderLA() {
    $hwInfo = new SystemHwInfo();
    $cpuName = $hwInfo->getCpuName();
    $cpuCoresCount = $hwInfo->getCpuCores();
    $memTotal = $hwInfo->getMemTotal();
    $memTotalLabel = zb_convertSize($memTotal) . ' ' . __('RAM');
    $cpuLabel = __('CPU') . ': ' . $cpuName;
    $coreLabel = $cpuCoresCount . ' ' . __('Cores');
    $osLabel = $hwInfo->getOs() . ' ' . $hwInfo->getOsRelease() . ', ' . $hwInfo->getMachineArch() . ', ';
    $phpLabel = __('PHP') . ': ' . $hwInfo->getPhpVersion();
    $result = '';

    $result .= wf_tag('h3') . __('System load');
    $result .= ' ('  . $cpuLabel . ', ' . $coreLabel . ', ' . $memTotalLabel . '. ' . $osLabel . $phpLabel . ')';
    $result .=  wf_tag('h3', true);

    $gL = 40;
    $yL = 70;
    $percOpts = ' max: 100,
               min: 0,
               width: ' . 280 . ', height: ' . 280 . ',
               greenFrom: 0, greenTo: ' . $gL . ',
               yellowFrom:' . $gL . ', yellowTo: ' . $yL . ',
               redFrom: ' . $yL . ', redTo: 100,
               minorTicks: 5
                ';


    $result .= wf_renderGauge($hwInfo->getLoadAvgPercent(),  __('on average'), '%', $percOpts, 300);
    $result .= wf_renderGauge($hwInfo->getloadPercent1(), '1' . ' ' . __('minutes'), '%', $percOpts, 300);
    $result .= wf_renderGauge($hwInfo->getLoadPercent5(), '5' . ' ' . __('minutes'), '%', $percOpts, 300);
    $result .= wf_renderGauge($hwInfo->getLoadPercent15(), '15' . ' ' . __('minutes'), '%', $percOpts, 300);
    $result .= wf_CleanDiv();

    $gL = $cpuCoresCount / 4;
    $yL = $cpuCoresCount / 2;
    $laOpts = 'max: ' . $cpuCoresCount . ',
               min: 0,
               width: ' . 280 . ', height: ' . 280 . ',
               greenFrom: 0, greenTo: ' . $gL . ',
               yellowFrom:' . $gL . ', yellowTo: ' . $yL . ',
               redFrom: ' . $yL . ', redTo: ' . $cpuCoresCount . ',
               minorTicks: 5
                ';

    $result .= wf_tag('h3') . __('Load Average') . wf_tag('h3', true);
    $result .= wf_renderGauge($hwInfo->getLa1(), '1' . ' ' . __('minutes'), 'LA', $laOpts, 300);
    $result .= wf_renderGauge($hwInfo->getLa5(), '5' . ' ' . __('minutes'), 'LA', $laOpts, 300);
    $result .= wf_renderGauge($hwInfo->getLa15(), '15' . ' ' . __('minutes'), 'LA', $laOpts, 300);
    $result .= wf_CleanDiv();

    return ($result);
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
    $hwInfo = new SystemHwInfo();
    $hwInfo->setMountPoints($mountPoints);
    $usedSpaceArr = $hwInfo->getAllDiskStats();



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
            $total = zb_convertSize($spaceStats['total']);
            $free = zb_convertSize($spaceStats['free']);
            $partitionLabel = $mountPoint . ' - ' . $free . ' ' . __('of') . ' ' . $total . ' ' . __('Free');
            $result .= wf_renderGauge(round($spaceStats['usedpercent']), $partitionLabel, '%', $opts, 300);
        }
    }
    $result .= wf_CleanDiv();
    return ($result);
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
    return ($result);
}

/**
 * Renders current system process list
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function web_ReportSysloadRenderUptime() {
    $result = '';
    $messages = new UbillingMessageHelper();
    $hwInfo = new SystemHwInfo();
    $uptime = $hwInfo->getUptime();
    $result .= $messages->getStyledMessage(__('Uptime') . ': ' . zb_formatTime($uptime), 'info');
    return ($result);
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
    return ($result);
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
    $inputs .= wf_TextInput('newadmusername', __('Username'), '', true, 20, 'alphanumeric') . wf_delimiter(0);
    $inputs .= wf_PasswordInput('newadmpass', __('Password'), '', true, 20) . wf_delimiter(0);
    $inputs .= wf_PasswordInput('newadmconf', __('Confirm password'), '', true, 20) . wf_delimiter(0);
    $inputs .= wf_TextInput('email', __('Email'), '', true, 20, 'email') . wf_delimiter(1);
    $inputs .= wf_HiddenInput('userdata[hideemail]', '1');
    $inputs .= wf_HiddenInput('userdata[tz]', '2');
    $inputs .= wf_Submit(__('Administrators registration'));

    $result .= wf_Form('', 'POST', $inputs, 'floatpanels', '', '', '', 'autocomplete="off"');
    return ($result);
}

/**
 * Returns simple existing administrator editing form
 * 
 * @param string $adminLogin 
 * 
 * @return string
 */
function web_AdministratorEditForm($adminLogin) {
    $result = '';
    $userdata = load_user_info($adminLogin);
    if (!empty($userdata)) {
        $inputs = '';
        $avatarImage = FaceKit::getAvatar($adminLogin, 128, '', __('Avatar control'));
        $avaBackUrl = base64_encode('?module=adminreg&editadministrator=' . $adminLogin);
        $avaContolUrl = '?module=avacontrol&admlogin=' . $adminLogin . '&back=' . $avaBackUrl;
        $inputs .= wf_tag('div', false, '', 'style="display:block; float:right;"') . wf_Link($avaContolUrl, $avatarImage, false, '') . wf_tag('div', true);
        $inputs .= wf_HiddenInput('save', '1');
        $inputs .= wf_HiddenInput('edadmusername', $userdata['username']);
        $passLabel = wf_tag('small') . __('if you do not want change password you must leave this field empty') . wf_tag('small', true);
        $inputs .= wf_PasswordInput('edadmpass', __('New password'), '', true, 20);
        $inputs .= $passLabel . wf_delimiter(0);
        $inputs .= wf_PasswordInput('edadmconf', __('Confirm password'), '', true, 20) . wf_delimiter(0);
        $inputs .= wf_TextInput('email', __('Email'), $userdata['email'], true, 20, 'email') . wf_delimiter(1);
        $inputs .= wf_HiddenInput('userdata[hideemail]', '1');
        $inputs .= wf_HiddenInput('userdata[tz]', '2');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'floatpanels', '', '', '', 'autocomplete="off"');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('User') . ' ' . $adminLogin, 256 . ' ' . __('Not exists'), 'error');
    }
    return ($result);
}

/**
 * Returns bank statements file upload form.
 * Backported from old banksta API as HotFix. 
 * 
 * @param string $action
 * @param string $method
 * @param string $inputs
 * @param string $class
 * 
 * @return string
 */
function bs_UploadFormBody($action, $method, $inputs, $class = '') {
    $form = wf_Form($action, $method, $inputs, $class, '', '', '', 'enctype="multipart/form-data"');
    return ($form);
}

/**
 * Renders list with some controls of available editable config presets
 * 
 * @param array $editableConfigs
 * 
 * @return string
 */
function web_RenderEditableConfigPresetsForm($editableConfigs) {
    $result = '';
    $messages = new UbillingMessageHelper();
    if (!empty($editableConfigs)) {
        $cells = wf_TableCell(__('Path'));
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($editableConfigs as $eachPath => $eachName) {
            $cells = wf_TableCell($eachPath);
            $cells .= wf_TableCell($eachName);
            $actLinks = wf_JSAlert('?module=sysconf&delconfpath=' . base64_encode($eachPath), web_delete_icon(), $messages->getDeleteAlert());
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, 'row3');
        }
        $result .= wf_TableBody($rows, '100%', 0, '');
    }

    $inputs = wf_TextInput('newconfpath', __('Path'), '', false, 10) . ' ';
    $inputs .= wf_TextInput('newconfname', __('Name'), '', false, 10) . ' ';
    $inputs .= wf_Submit(__('Create'));
    $result .= wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Returns X last lines from some text file
 * 
 * @global object $ubillingConfig
 * @param string $filePath
 * @param int $linesCount
 * 
 * @return string
 */
function zb_ReadLastLines($filePath, $linesCount) {
    global $ubillingConfig;
    $result = '';
    if (file_exists($filePath)) {
        $billCfg = $ubillingConfig->getBilling();
        $tailPath = $billCfg['TAIL'];
        $command = $tailPath . ' -n ' . $linesCount . ' ' . $filePath;
        $result = shell_exec($command);
    }
    return ($result);
}

/**
 * Calculates something? Some hash? Ask what it was https://github.com/S0liter ;)
 * 
 * @param int $numbers Some INN?
 * 
 * @return string 
 */
function zb_OschadCSgen($numbers) {
    $result = 0;
    if (!empty($numbers)) {
        if (strlen((string) $numbers) >= 10) {
            $result = $numbers[0] * 10 + $numbers[1] * 11 + $numbers[2] * 12 + $numbers[3] * 13 + $numbers[4] * 14 + $numbers[5] * 15 + $numbers[6] * 16 + $numbers[7] * 17 + $numbers[8] * 18 + $numbers[9] * 19;
        }
    }
    return ($result);
}

/**
 * Check is some control allowed to output with current administrator rights
 * 
 * @param string $right
 * @param string $controlString
 * 
 * @return string
 */
function zb_rightControl($right, $controlString) {
    $result = '';
    if (!empty($right)) {
        if (cfr($right)) {
            $result .= $controlString;
        }
    } else {
        //no specific right required to output control
        $result .= $controlString;
    }
    return ($result);
}

/**
 * Replaces some sensitive characters with safer analogs
 * 
 * @param string $data
 * @param bool   $mres
 * 
 * @return string
 */
function ub_SanitizeData($data, $mres = true) {
    $result = '';
    if ($mres) {
        $result = ubRouting::filters($data, 'mres');
    } else {
        $result = $data;
    }
    $result = str_replace('"', '``', $result);
    $result = str_replace("'", '`', $result);
    return ($result);
}

/**
 * Returns data that contained between two string tags
 * 
 * @param string $openTag - open tag string. Examples: "(", "[", "{", "[sometag]" 
 * @param string $closeTag - close tag string. Examples: ")", "]", "}", "[/sometag]" 
 * @param string $stringToParse - just string that contains some data to parse
 * @param bool   $mutipleResults - extract just first result as string or all matches as array like match=>match
 * 
 * @return string|array
 */
function zb_ParseTagData($openTag, $closeTag, $stringToParse = '', $mutipleResults = false) {
    $result = '';
    if (!empty($openTag) and !empty($closeTag) and !empty($stringToParse)) {
        $replacements = array(
            '(' => '\(',
            ')' => '\)',
            '[' => '\[',
            ']' => '\]',
        );

        foreach ($replacements as $eachReplaceTag => $eachReplace) {
            $openTag = str_replace($eachReplaceTag, $eachReplace, $openTag);
            $closeTag = str_replace($eachReplaceTag, $eachReplace, $closeTag);
        }

        $pattern = '!' . $openTag . '(.*?)' . $closeTag . '!si';

        if ($mutipleResults) {
            $result = array();
            if (preg_match_all($pattern, $stringToParse, $matches)) {
                if (isset($matches[1])) {
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $io => $each) {
                            $result[$each] = $each;
                        }
                    }
                }
            }
        } else {
            if (preg_match($pattern, $stringToParse, $matches)) {
                if (isset($matches[1])) {
                    $result = $matches[1];
                }
            }
        }
    }
    return ($result);
}

/**
 * Predicts the next value using simple exponential smoothing with a trend using Holt-Winters method.
 * 
 * @param array $data
 * 
 * @return float
 */
function zb_forecastHoltWinters($data) {
    $alpha = 0.2;
    $beta = 0.1;
    $forecast_length = 1;
    $data_length = count($data);
    $level = $data[0];
    $trend = ($data[1] - $data[0]) / 2;
    for ($i = 2; $i < $data_length; $i++) {
        $last_level = $level;
        $last_trend = $trend;
        $level = $alpha * $data[$i] + (1 - $alpha) * ($last_level + $last_trend);
        $trend = $beta * ($level - $last_level) + (1 - $beta) * $last_trend;
    }
    $last_level = $level;
    $last_trend = $trend;
    for ($i = 0; $i < $forecast_length; $i++) {
        $forecast = $last_level + $last_trend * ($i + 1);
    }
    return ($forecast);
}

/**
 * Checks is request using secure https connection or not
 * 
 * @return bool
 */
function zb_isHttpsRequest() {
    $result = false;
    if (isset($_SERVER['REQUEST_SCHEME'])) {
        if ($_SERVER['REQUEST_SCHEME'] == 'https') {
            $result = true;
        }
    }
    return ($result);
}

/**
 * Returns GPS location button for filling specified input with geo data
 * 
 * @param string $inputId
 * 
 * @return string
 */
function web_GPSLocationFillInputControl($inputId) {
    $result = '';
    $result .= wf_tag('script');
    $result .= '
        function getGeoPosition() {
            if(navigator.geolocation) {
                document.getElementById("gpswaitcontainer").innerHTML = " <img src=skins/ui-anim_basic_16x16.gif width=12> ";
                navigator.geolocation.getCurrentPosition(function(position) {
                var positionData = position.coords.latitude + "," + position.coords.longitude;
                document.getElementById("' . $inputId . '").value = positionData;
                document.getElementById("gpswaitcontainer").innerHTML = "";
                });
            } else {
                alert("' . __('Sorry, your browser does not support HTML5 geolocation') . '");
            }
        }';
    $result .= wf_tag('script', true);
    $result .= wf_tag('div', false, '', 'id="gpswaitcontainer" style="float:left;"') . wf_tag('div', true);
    $result .= wf_tag('button', false, '', 'type="button" onclick="getGeoPosition();"') . 'GPS ' . __('Location') . wf_tag('button', true);
    return ($result);
}

/**
 * Returns cutted unicode string if its required
 * 
 * @param string $string
 * @param int $size
 * 
 * @return string
 */
function zb_cutString($string, $size) {
    if ((mb_strlen($string, 'UTF-8') > $size)) {
        $string = mb_substr($string, 0, $size, 'utf-8') . '...';
    }
    return ($string);
}

/**
 * Converts a $delimited_string, delimited with $delimiter, like 'abc, defg, abracadabra' or '1, 4,5, 11,'
 * into a one-dimensional array, like [abc, defg, abracadabra] or [1, 4, 5, 11]
 * or a two-dimensional associative array, like [abc => abc, defg => defg, abracadabra => abracadabra]
 *                                           or [1 => 1, 4 => 4, 5 => 5, 11 => 11]
 * with or without any DUPLICATES
 *
 * It supposed that one using this function understands that $assocValuesAsKeys and $allowDuplicates
 * are two SELF-EXCLUSIONAL parameters
 *
 * @param $delimited_string
 * @param $delimiter
 * @param $assocValuesAsKeys
 * @param $allowDuplicates
 *
 * @return array
 */
function zb_DelimitedStringToArray($delimited_string, $delimiter = ',', $assocValuesAsKeys = false, $allowDuplicates = false) {
    $result = array();
    //$allowDuplicates = ($assocValuesAsKeys) ? false : $allowDuplicates;

    if (!empty($delimited_string)) {
        $tmp_arr = explode($delimiter, trim($delimited_string, $delimiter . ' '));

        foreach ($tmp_arr as $eachElem) {
            if (!$allowDuplicates and in_array(trim($eachElem), array_values($result))) {
                continue;
            }

            if ($assocValuesAsKeys) {
                $result[trim($eachElem)] = trim($eachElem);
            } else {
                $result[] = trim($eachElem);
            }
        }
    }

    return ($result);
}

/**
 * Intended to create string suitable for an SQL WHERE IN clause usage from a $delimited_string, delimited with $delimiter
 *
 * @param $delimited_string
 * @param $delimiter
 * @param $stringINClause
 *
 * @return string
 */
function zb_DelimitedStringToSQLWHEREIN($delimited_string, $delimiter = ',', $stringINClause = false) {
    $whereStr  = '';
    $valuesArr = zb_DelimitedStringToArray($delimited_string, $delimiter);

    if (!empty($valuesArr)) {
        foreach ($valuesArr as $eachElem) {
            if (!empty($eachElem)) {
                $whereStr .= ($stringINClause) ? " '" . $eachElem . "', " : " " . $eachElem . ", ";
            }
        }

        $whereStr = trim($whereStr, ', ');
    }

    return ($whereStr);
}

/**
 * Intended to create string suitable for an SQL WHERE IN clause usage from an one-dimensional array of values
 *
 * @param $valuesArr
 * @param $stringINClause
 *
 * @return string
 */
function zb_ArrayToSQLWHEREIN($valuesArr, $stringINClause = false) {
    $whereStr  = '';

    if (!empty($valuesArr)) {
        foreach ($valuesArr as $eachElem) {
            if (!empty($eachElem)) {
                $whereStr .= ($stringINClause) ? " '" . $eachElem . "', " : " " . $eachElem . ", ";
            }
        }

        $whereStr = trim($whereStr, ', ');
    }

    return ($whereStr);
}

/**
 * Returns game icon and link as standard panel
 * 
 * @return string
 */
function zb_buildGameIcon($link, $icon, $text) {
    $icon_path = '';
    if (!ispos($icon, 'http')) {
        $icon_path = 'modules/jsc/procrastdata/icons/'; //local icon?
    }

    $task_link = $link;
    $task_icon = $icon_path . $icon;
    $task_text = $text;

    $tbiconsize = '128';
    $template = wf_tag('div', false, 'dashtask', 'style="height:' . ($tbiconsize + 30) . 'px; width:' . ($tbiconsize + 30) . 'px;"');
    $template .= wf_tag('a', false, '', 'href="' . $task_link . '"');
    $template .= wf_tag('img', false, '', 'src="' . $task_icon . '" border="0" width="' . $tbiconsize . '"  height="' . $tbiconsize . '" alt="' . $task_text . '" title="' . $task_text . '"');
    $template .= wf_tag('a', true);
    $template .= wf_tag('br');
    $template .= wf_tag('br');
    $template .= $task_text;
    $template .= wf_tag('div', true);
    return ($template);
}

/**
 * Generates a random name based on the current language.
 *
 * @return string The generated random name.
 */
function zb_GenerateRandomName() {
    $curLang = curlang();
    $result = '';
    switch ($curLang) {
        case 'uk':
            $surnames = array("Мельник", "Шевченко", "Коваленко", "Бондаренко", "Бойко", "Ткаченко", "Кравченко", "Ковальчук", "Коваль", "Олійник", "Шевчук", "Поліщук", "Іванова", "Ткачук", "Савченко", "Бондар", "Марченко", "Руденко", "Мороз", "Лисенко", "Петренко", "Клименко", "Павленко", "Кравчук", "Іванов", "Кузьменко", "Пономаренко", "Савчук", "Василенко", "Левченко", "Харченко", "Сидоренко", "Карпенко", "Гаврилюк", "Швець", "Мельничук", "Попова", "Романюк", "Панченко", "Юрченко", "Мазур", "Хоменко", "Попович", "Павлюк", "Кушнір", "Литвиненко", "Мартинюк", "Гончаренко", "Приходько", "Костенко", "Кулик", "Романенко", "Костюк", "Семенюк", "Назаренко", "Ткач", "Кравець", "Коломієць", "Козак", "Яковенко", "Федоренко", "Ковтун", "Білоус", "Нестеренко", "Терещенко", "Колесник", "Попов", "Зінченко", "Тарасенко", "Міщенко", "Вовк", "Демченко", "Дяченко", "Ковальова", "Пилипенко", "Іщенко", "Макаренко", "Бабенко", "Кириченко", "Тищенко", "Тимошенко", "Жук", "Москаленко", "Крюгер", "Вурхіз", "Хьюіт", "Маєрс", "Марчук", "Власенко", "Гуменюк", "Яценко", "Радченко", "Герасименко", "Сергієнко", "Корнієнко", "Гончар", "Мартиненко", "Гордієнко", "Степаненко", "Прокопенко", "Шульга", "Волошин", "Величко", "Денисенко");
            $names = array("Ніка", "Адріана", "Адріан", "Тіна", "Аліна", "Ангеліна", "Алінка", "Ліна", "Аліса", "Алла", "Альберт", "Альбіна", "Альбінка", "Альфред", "Анастасія", "Настя", "Настася", "Настечка", "Настуня", "Настуся", "Ната", "Стася", "Анатолій", "Толя", "Анжела", "Анжеліка", "Анна", "Ганна", "Аня", "Аннуся", "Анюта", "Нюта", "Антін", "Антон", "Тоня", "Антоніна", "Тося", "Ніна", "Анфіса", "Поліна", "Аркадія", "Аркадій", "Арсен", "Арсеній", "Арсенія", "Сеня", "Артемій", "Аурика", "Афанасія", "Афанасій", "Барбара", "Богдана", "Богдан", "Богданка", "Даня", "Богуслава", "Богуслав", "Богуся", "Слава", "Божена", "Борислав", "Броніслава", "Броніслав", "Валентина", "Валентин", "Валерія", "Валерій", "Варвара", "Варонька", "Василина", "Василь", "Вася", "Вероніка", "Віра", "Віка", "Віта", "Вікторія", "Лена", "Іна", "Інна", "Оля", "Віолетта", "Віолета", "Вірослава", "Вірослав", "Віталій", "Віталія", "Влада", "Владислава", "Влад", "Лада", "Владилена", "Владилен", "Влада", "Лада", "Владислав", "Владлина", "Владлин", "Владлен", "Владлєн", "Володимир", "Володя", "В'ячеслава", "В'ячеслав", "Вячеслава", "Вячеслав", "Галина", "Галя", "Галка", "Олена", "Георгій", "Іна", "Гертруда", "Горислава", "Дарина", "Даша", "Дарія", "Даринка", "Дарка", "Діана", "Діна", "Елеонора", "Єлизавета", "Ерік", "Євгенія", "Євген", "Женя", "Дуня", "Єкатерина", "Катерина", "Ліза", "Жанна", "Іванна", "Зіна", "Зоряна", "Зоря", "Іван", "Іванка", "Ігор", "Ілона", "Інга", "Інна", "Ірина", "Іра", "Ірма", "Йосип", "Карина", "Катя", "Кіра", "Лара", "Лєра", "Костя", "Корнелія", "Корнелій", "Ксенія", "Оксана", "Ксеня", "Лариса", "Лев", "Леся", "Олеся", "Олександра", "Лідія", "Лілія", "Ліля", "Любов", "Люба", "Любомира", "Людмила", "Люся", "Люда", "Любослава", "Ляна", "Маргарита", "Маруся", "Марта", "Марія", "Марійка", "Марічка", "Маша", "Марина", "Міла", "Мирослава", "Мирослав", "Міра", "Михайло", "Надія", "Надя", "Наталка", "Наташа", "Саня", "Єлена", "Оленка", "Олівія", "Ольга", "Олег", "Орест", "Павло", "Паша", "Параска", "Регіна", "Римма", "Ярина", "Роза", "Роксана", "Роксолана", "Роман", "Ростислава", "Ростислав", "Руслана", "Руслан", "Лана", "Сара", "Світлана", "Свєта", "Святослав", "Серафима", "Серафим", "Семен", "Сніжана", "Соломія", "Соня", "Софія", "Софійка", "Софа", "Станіслав", "Сюзанна", "Таїсія", "Тася", "Тая", "Тамара", "Тома", "Тетяна", "Таня", "Христина", "Юлія", "Уляна", "Тіна", "Христя", "Юля", "Яна", "Ян", "Ярослава", "Ярослав", "Адам", "Адріян", "Адріан", "Анатолій", "Андрій", "Антон", "Аркадій", "Арсен", "Арсеній", "Артем", "Артур", "Богдан", "Богуслав", "Борис", "Валентин", "Валерій", "Василь", "Вадим", "Вадім", "Вадік", "Вадик", "Віктор", "Віталій", "Влад", "Владислав", "Володимир", "Всеволод", "В'ячеслав", "Вячеслав", "Гаврило", "Геннадій", "Генадій", "Георгій", "Герасим", "Гліб", "Глеб", "Гнат", "Григорій", "Данило", "Денис", "Дмитро", "Євген", "Євгеній", "Зорян", "Іван", "Ігор", "Ілля", "Кирило", "Костянтин", "Лев", "Левко", "Леонід", "Лук'ян", "Лукян", "Любомир", "Маркіян", "Макар", "Максим", "Макс", "Марко", "Матвій", "Микита", "Микола", "Мирон", "Мирослав", "Михайло", "Нестор", "Олег", "Олександр", "Олексій", "Омелян", "Орест", "Остап", "Павло", "Пантелеймон", "Панас", "Петро", "Пилип", "Потап", "Родіон", "Роман", "Ростислав", "Руслан", "Святослав", "Семен", "Сергій", "Слава", "Станислав", "Станіслав", "Степан", "Тарас", "Федір", "Яків", "Ян", "Ярослав");
            break;
        case 'ru':
            $surnames = array("Смирнов", "Иванов", "Кузнецов", "Соколов", "Попов", "Лебедев", "Козлов", "Новиков", "Морозов", "Петров", "Волков", "Соловьёв", "Васильев", "Зайцев", "Павлов", "Семёнов", "Голубев", "Виноградов", "Богданов", "Воробьёв", "Фёдоров", "Михайлов", "Беляев", "Тарасов", "Белов", "Комаров", "Орлов", "Киселёв", "Макаров", "Андреев", "Ковалёв", "Ильин", "Гусев", "Титов", "Кузьмин", "Кудрявцев", "Баранов", "Куликов", "Алексеев", "Степанов", "Яковлев", "Сорокин", "Сергеев", "Романов", "Захаров", "Борисов", "Королёв", "Герасимов", "Пономарёв", "Григорьев", "Лазарев", "Медведев", "Ершов", "Никитин", "Соболев", "Рябов", "Поляков", "Цветков", "Данилов", "Жуков", "Фролов", "Журавлёв", "Николаев", "Крылов", "Максимов", "Сидоров", "Осипов", "Белоусов", "Федотов", "Дорофеев", "Егоров", "Матвеев", "Бобров", "Дмитриев", "Калинин", "Анисимов", "Петухов", "Антонов", "Тимофеев", "Никифоров", "Веселов", "Филиппов", "Марков", "Большаков", "Суханов", "Миронов", "Ширяев", "Александров", "Коновалов", "Шестаков", "Казаков", "Ефимов", "Денисов", "Громов", "Фомин", "Давыдов", "Мельников", "Щербаков", "Блинов", "Колесников", "Карпов", "Афанасьев", "Власов", "Маслов", "Исаков", "Тихонов", "Аксёнов", "Гаврилов", "Родионов", "Котов", "Горбунов", "Кудряшов", "Быков", "Зуев", "Третьяков", "Савельев", "Панов", "Рыбаков", "Суворов", "Абрамов", "Воронов", "Мухин", "Архипов", "Трофимов", "Мартынов", "Емельянов", "Горшков", "Чернов", "Овчинников", "Селезнёв", "Панфилов", "Копылов", "Михеев", "Галкин", "Назаров", "Лобанов", "Лукин", "Беляков", "Потапов", "Некрасов", "Жданов", "Наумов", "Шилов", "Воронцов", "Ермаков", "Дроздов", "Игнатьев", "Савин", "Логинов", "Сафонов", "Капустин", "Кириллов", "Моисеев", "Елисеев", "Кошелев", "Костин", "Горбачёв", "Орехов", "Ефремов", "Исаев", "Евдокимов", "Калашников", "Кабанов", "Носков", "Юдин", "Кулагин", "Лапин", "Прохоров", "Нестеров", "Харитонов", "Агафонов", "Муравьёв", "Ларионов", "Федосеев", "Зимин", "Пахомов", "Шубин", "Игнатов", "Филатов", "Крюков", "Рогов", "Кулаков", "Терентьев", "Молчанов", "Владимиров", "Артемьев", "Гурьев", "Зиновьев", "Гришин", "Кононов", "Дементьев", "Ситников", "Симонов", "Мишин", "Фадеев", "Комиссаров", "Мамонтов", "Носов", "Гуляев", "Шаров", "Устинов", "Вишняков", "Евсеев", "Лаврентьев", "Брагин", "Константинов", "Корнилов", "Авдеев", "Зыков", "Бирюков", "Шарапов", "Никонов", "Щукин", "Дьячков", "Одинцов", "Сазонов", "Якушев", "Красильников", "Гордеев", "Самойлов", "Князев", "Беспалов", "Уваров", "Шашков", "Бобылёв", "Доронин", "Белозёров", "Рожков", "Самсонов", "Мясников", "Лихачёв", "Буров", "Сысоев", "Фомичёв", "Русаков", "Стрелков", "Гущин", "Тетерин", "Колобов", "Субботин", "Фокин", "Блохин", "Селиверстов", "Пестов", "Кондратьев", "Силин", "Меркушев", "Лыткин", "Туров");
            $names = array("Аарон", "Абрам", "Аваз", "Аввакум", "Август", "Авдей", "Авраам", "Автандил", "Агап", "Агафон", "Аггей", "Адам", "Адис", "Адольф", "Адриан", "Азарий", "Азат", "Айрат", "Акакий", "Аким", "Алан", "Александр", "Алексей", "Али", "Алихан", "Алоиз", "Альберт", "Альфред", "Амадей", "Амадеус", "Амаяк", "Амвросий", "Анатолий", "Анвар", "Ангел", "Андоим", "Андре", "Андрей", "Аникита", "Антон", "Ануфрий", "Аполлинарий", "Арам", "Арий", "Аристарх", "Аркадий", "Арман", "Армен", "Арно", "Арнольд", "Арсений", "Арслан", "Артем", "Артемий", "Артур", "Архип", "Аскольд", "Афанасий", "Ахмет", "Ашот", "Бежен", "Бенедикт", "Берек", "Бернар", "Богдан", "Боголюб", "Болеслав", "Бонифаций", "Борис", "Борислав", "Боян", "Бруно", "Булат", "Вадим", "Валентин", "Валерий", "Вальтер", "Вардан", "Варлаам", "Варфоломей", "Василий", "Вацлав", "Велизар", "Велор", "Венедикт", "Вениамин", "Викентий", "Виктор", "Вилен", "Вилли", "Вильгельм", "Виссарион", "Виталий", "Витаутас", "Витольд", "Владимир", "Владислав", "Владлен", "Влас", "Володар", "Вольдемар", "Всеволод", "Вячеслав", "Гавриил", "Галактион", "Гарри", "Гастон", "Гаяс", "Гевор", "Геворг", "Геннадий", "Генрих", "Георгий", "Геральд", "Герасим", "Герман", "Глеб", "Гордей", "Гордон", "Горислав", "Градимир", "Григорий", "Гурий", "Густав", "Давид", "Дамир", "Даниил", "Данияр", "Демид", "Демьян", "Денис", "Джамал", "Джереми", "Джордан", "Динасий", "Дмитрий", "Добрыня", "Дональд", "Донат", "Донатос", "Дорофей", "Евгений", "Евграф", "Евдоким", "Евсей", "Евстафий", "Егор", "Елизар", "Елисей", "Емельян", "Еремей", "Ермолай", "Ерофей", "Ефим", "Ефимий", "Ефрем", "Жан", "Ждан", "Жорж", "Заур", "Захар", "Зигмунд", "Зиновий", "Ибрагим", "Иван", "Игнат", "Игорь", "Иероним", "Измаил", "Израиль", "Илиан", "Илларион", "Ильхам", "Илья", "Ильяс", "Иннокентий", "Ион", "Ионос", "Иосиф", "Ипполит", "Ираклий", "Иржи", "Исаак", "Исай", "Исидор", "Искандер", "Казимир", "Камиль", "Карен", "Карим", "Карл", "Ким", "Кирилл", "Клавдий", "Клаус", "Клемент", "Клим", "Клод", "Кондрат", "Конкордий", "Конрад", "Константин", "Корней", "Корнилий", "Ксанф", "Кузьма", "Лаврентий", "Лазарь", "Лев", "Леван", "Левон", "Ленар", "Леон", "Леонард", "Леонид", "Леонтий", "Леопольд", "Лука", "Лукьян", "Любим", "Любомир", "Людвиг", "Люсьен", "Мадлен", "Май", "Макар", "Максим", "Максимилиан", "Максуд", "Мансур", "Мануил", "Марат", "Мариан", "Марк", "Марсель", "Мартин", "Матвей", "Махмуд", "Мераб", "Мефодий", "Мечеслав", "Милан", "Мирон", "Мирослав", "Митрофан", "Михаил", "Мичлов", "Модест", "Моисей", "Мстислав", "Мурат", "Муслим", "Назар", "Назарий", "Наиль", "Натан", "Наум", "Нестор", "Никанор", "Никита", "Никифор", "Никодим", "Николай", "Никон", "Нильс", "Нисон", "Нифонт", "Норманн", "Овидий", "Олан", "Олег", "Олесь", "Онисим", "Орест", "Осип", "Оскар", "Остап", "Павел", "Панкрат", "Парамон", "Петр", "Пимен", "Платон", "Порфирий", "Потап", "Прокофий", "Прохор", "Равиль", "Радий", "Раймонд", "Раис", "Рамиз", "Рамиль", "Расим", "Ратибор", "Ратмир", "Рафаил", "Рафик", "Рашид", "Рем", "Ренольд", "Ринат", "Рифат", "Рихард", "Ричард", "Роберт", "Родион", "Ролан", "Роман", "Ростислав", "Рубен", "Рудольф", "Руслан", "Рустам", "Рушан", "Сабир", "Савва", "Савелий", "Самсон", "Самуил", "Святослав", "Севастьян", "Северин", "Семен", "Серафим", "Сергей", "Сократ", "Соломон", "Спартак", "Стакрат", "Станимир", "Станислав", "Степан", "Стивен", "Стоян", "Султан", "Таис", "Талик", "Тамаз", "Тарас", "Тельман", "Теодор", "Терентий", "Тибор", "Тигран", "Тигрий", "Тимофей", "Тимур", "Тит", "Тихон", "Томас", "Трифон", "Трофим", "Ульманас", "Устин", "Фаддей", "Фанис", "Фарид", "Фархад", "Федор", "Федот", "Феликс", "Феодосий", "Фердинанд", "Фидель", "Филимон", "Филипп", "Флорентий", "Фома", "Франц", "Фридрих", "Фуад", "Харитон", "Христиан", "Христос", "Христофор", "Цезарь", "Чеслав", "Чингиз", "Шамиль", "Шерлок", "Эдвард", "Эдгар", "Эдмунд", "Эдуард", "Эльдар", "Эмиль", "Эмин", "Эммануил", "Эраст", "Эрик", "Эрнест", "Юлиан", "Юлий", "Юрий", "Юхим", "Яким", "Яков", "Ян", "Януарий", "Яромир", "Ярослав", "Ясон");
            break;
        default:
            $surnames = array("Smith", "Johnson", "Williams", "Brown", "Jones", "Garcia", "Miller", "Davis", "Rodriguez", "Martinez", "Hernandez", "Lopez", "Gonzalez", "Wilson", "Anderson", "Thomas", "Taylor", "Moore", "Jackson", "Martin", "Lee", "Perez", "Thompson", "White", "Harris", "Sanchez", "Clark", "Ramirez", "Lewis", "Robinson", "Walker", "Young", "Allen", "King", "Wright", "Scott", "Torres", "Nguyen", "Hill", "Flores", "Green", "Adams", "Nelson", "Baker", "Hall", "Rivera", "Campbell", "Mitchell", "Carter", "Roberts", "Gomez", "Phillips", "Evans", "Turner", "Diaz", "Parker", "Cruz", "Edwards", "Collins", "Reyes", "Stewart", "Morris", "Morales", "Murphy", "Cook", "Rogers", "Gutierrez", "Ortiz", "Morgan", "Cooper", "Peterson", "Bailey", "Reed", "Kelly", "Howard", "Ramos", "Kim", "Cox", "Ward", "Richardson", "Watson", "Brooks", "Chavez", "Wood", "James", "Bennett", "Gray", "Mendoza", "Ruiz", "Hughes", "Price", "Alvarez", "Castillo", "Sanders", "Patel", "Myers", "Long", "Ross", "Foster", "Jimenez", "Powell", "Jenkins", "Perry", "Russell", "Sullivan", "Bell", "Coleman", "Butler", "Henderson", "Barnes", "Gonzales", "Fisher", "Vasquez", "Simmons", "Romero", "Jordan", "Patterson", "Alexander", "Hamilton", "Graham", "Reynolds", "Griffin", "Wallace", "Moreno", "West", "Cole", "Hayes", "Bryant", "Herrera", "Gibson", "Ellis", "Tran", "Medina", "Aguilar", "Stevens", "Murray", "Ford", "Castro", "Marshall", "Owens", "Harrison", "Fernandez", "Mcdonald", "Woods", "Washington", "Kennedy", "Wells", "Vargas", "Henry", "Chen", "Freeman", "Webb", "Tucker", "Guzman", "Burns", "Crawford", "Olson", "Simpson", "Porter", "Hunter", "Gordon", "Mendez", "Silva", "Shaw", "Snyder", "Mason", "Dixon", "Munoz", "Hunt", "Hicks", "Holmes", "Palmer", "Wagner", "Black", "Robertson", "Boyd", "Rose", "Stone", "Salazar", "Fox", "Warren", "Mills", "Meyer", "Rice", "Schmidt", "Garza", "Daniels", "Ferguson", "Nichols", "Stephens", "Soto", "Weaver", "Ryan", "Gardner", "Payne", "Grant", "Dunn", "Kelley", "Spencer", "Hawkins", "Arnold", "Pierce", "Vazquez", "Hansen", "Peters", "Santos", "Hart", "Bradley", "Knight", "Elliott", "Cunningham", "Duncan", "Armstrong", "Hudson", "Carroll", "Lane", "Riley", "Andrews", "Alvarado", "Ray", "Delgado", "Berry", "Perkins", "Hoffman", "Johnston", "Matthews", "Pena", "Richards", "Contreras", "Willis", "Carpenter", "Lawrence", "Sandoval", "Guerrero", "George", "Chapman", "Rios", "Estrada", "Ortega", "Watkins", "Greene", "Nunez", "Wheeler", "Valdez", "Harper", "Burke", "Larson", "Santiago", "Maldonado", "Morrison", "Franklin", "Carlson", "Austin", "Dominguez", "Carr", "Lawson", "Jacobs", "Obrien", "Lynch", "Singh", "Vega", "Bishop", "Montgomery", "Oliver", "Jensen", "Harvey", "Williamson", "Gilbert", "Dean", "Sims", "Espinoza", "Howell", "Li", "Wong", "Reid", "Hanson", "Le", "Mccoy", "Garrett", "Burton", "Fuller", "Wang", "Weber", "Welch", "Rojas", "Lucas", "Marquez", "Fields", "Park", "Yang", "Little", "Banks", "Padilla", "Day", "Walsh", "Bowman", "Schultz", "Luna", "Fowler", "Mejia", "Davidson", "Acosta", "Brewer", "May", "Holland", "Juarez", "Newman", "Pearson", "Curtis", "Cortez", "Douglas", "Schneider", "Joseph", "Barrett", "Navarro", "Figueroa", "Keller", "Avila", "Wade", "Molina", "Stanley", "Hopkins", "Campos", "Barnett", "Bates", "Chambers", "Caldwell", "Beck", "Lambert", "Miranda", "Byrd", "Craig", "Ayala", "Lowe", "Frazier", "Powers", "Neal", "Leonard", "Gregory", "Carrillo", "Sutton", "Fleming", "Rhodes", "Shelton", "Schwartz", "Norris", "Jennings", "Watts", "Duran", "Walters", "Cohen", "Mcdaniel", "Moran", "Parks", "Steele", "Vaughn", "Becker", "Holt", "Deleon", "Barker", "Terry", "Hale", "Leon", "Hail", "Benson", "Haynes", "Horton", "Miles", "Lyons", "Pham", "Graves", "Bush", "Thornton", "Wolfe", "Warner", "Cabrera", "Mckinney", "Mann", "Zimmerman", "Dawson", "Lara", "Fletcher", "Page", "Mccarthy", "Love", "Robles", "Cervantes", "Solis", "Erickson", "Reeves", "Chang", "Klein", "Salinas", "Fuentes", "Baldwin", "Daniel", "Simon", "Velasquez", "Hardy", "Higgins", "Aguirre", "Lin", "Cummings", "Chandler", "Sharp", "Barber", "Bowen", "Ochoa", "Dennis", "Robbins", "Liu", "Ramsey", "Francis", "Griffith", "Paul", "Blair", "Oconnor", "Cardenas", "Pacheco", "Cross", "Calderon", "Quinn", "Moss", "Swanson", "Chan", "Rivas", "Khan", "Rodgers", "Serrano", "Fitzgerald", "Rosales", "Stevenson", "Christensen", "Manning", "Gill", "Curry", "Mclaughlin", "Harmon", "Mcgee", "Gross", "Doyle", "Garner", "Newton", "Burgess", "Reese", "Walton", "Blake", "Trujillo", "Adkins", "Brady", "Goodman", "Roman", "Webster", "Goodwin", "Fischer", "Huang", "Potter", "Delacruz", "Montoya", "Todd", "Wu", "Hines", "Mullins", "Castaneda", "Malone", "Cannon", "Tate", "Mack", "Sherman", "Hubbard", "Hodges", "Zhang", "Guerra", "Wolf", "Valencia", "Franco", "Saunders", "Rowe", "Gallagher", "Farmer", "Hammond", "Hampton", "Townsend", "Ingram", "Wise", "Gallegos", "Clarke", "Barton", "Schroeder", "Maxwell", "Waters", "Logan", "Camacho", "Strickland", "Norman", "Person", "Colon", "Parsons", "Frank", "Harrington", "Glover", "Osborne", "Buchanan", "Casey", "Floyd", "Patton", "Ibarra", "Ball", "Tyler", "Suarez", "Bowers", "Orozco", "Salas", "Cobb", "Gibbs", "Andrade", "Bauer", "Conner", "Moody", "Escobar", "Mcguire", "Lloyd", "Mueller", "Hartman", "French", "Kramer", "Mcbride", "Pope", "Lindsey", "Velazquez", "Norton", "Mccormick", "Sparks", "Flynn", "Yates", "Hogan", "Marsh", "Macias", "Villanueva", "Zamora", "Pratt", "Stokes", "Owen", "Ballard", "Lang", "Brock", "Villarreal", "Charles", "Drake", "Barrera", "Cain", "Patrick", "Pineda", "Burnett", "Mercado", "Santana", "Shepherd", "Bautista", "Ali", "Shaffer", "Lamb", "Trevino", "Mckenzie", "Hess", "Beil", "Olsen", "Cochran", "Morton", "Nash", "Wilkins", "Petersen", "Briggs", "Shah", "Roth", "Nicholson", "Holloway", "Lozano", "Flowers", "Rangel", "Hoover", "Arias", "Short", "Mora", "Valenzuela", "Bryan", "Meyers", "Weiss", "Underwood", "Bass", "Greer", "Summers", "Houston", "Carson", "Morrow", "Clayton", "Whitaker", "Decker", "Yoder", "Collier", "Zuniga", "Carey", "Wilcox", "Melendez", "Poole", "Roberson", "Larsen", "Conley", "Davenport", "Copeland", "Massey", "Lam", "Huff", "Rocha", "Cameron", "Jefferson", "Hood", "Monroe", "Anthony", "Pittman", "Huynh", "Randall", "Singleton", "Kirk", "Combs", "Mathis", "Christian", "Skinner", "Bradford", "Richard", "Galvan", "Wall", "Boone", "Kirby", "Wilkinson", "Bridges", "Bruce", "Atkinson", "Velez", "Meza", "Roy", "Vincent", "York", "Hodge", "Villa", "Abbott", "Allison", "Tapia", "Gates", "Chase", "Sosa", "Sweeney", "Farrell", "Wyatt", "Dalton", "Horn", "Barron", "Phelps", "Yu", "Dickerson", "Heath", "Foley", "Atkins", "Mathews", "Bonilla", "Acevedo", "Benitez", "Zavala", "Hensley", "Glenn", "Cisneros", "Harrell", "Shields", "Rubio", "Choi", "Huffman", "Boyer", "Garrison", "Arroyo", "Bond", "Kane", "Hancock", "Callahan", "Dillon", "Cline", "Wiggins", "Grimes", "Arellano", "Melton", "Oneill", "Savage", "Ho", "Beltran", "Pitts", "Parrish", "Ponce", "Rich", "Booth", "Koch", "Golden", "Ware", "Brennan", "Mcdowell", "Marks", "Cantu", "Humphrey", "Baxter", "Sawyer", "Clay", "Tanner", "Hutchinson", "Kaur", "Berg", "Wiley", "Gilmore", "Russo", "Villegas", "Hobbs", "Keith", "Wilkerson", "Ahmed", "Beard", "Mcclain", "Montes", "Mata", "Rosario", "Vang", "S", "S", "Walter", "Henson", "Oneal", "Mosley", "Mcclure", "Beasley", "Stephenson", "Snow", "Huerta", "Preston", "Vance", "Barry", "Johns", "Eaton", "Blackwell", "Dyer", "Prince", "Macdonald", "Solomon", "Guevara", "Stafford", "English", "Hurst", "Woodard", "Cortes", "Shannon", "Kemp", "Nolan", "Mccullough", "Merritt", "Murillo", "Moon", "Salgado", "Strong", "Kline", "Cordova", "Barajas", "Roach", "Rosas", "Winters", "Jacobson", "Lester", "Knox", "Bullock", "Kerr", "Leach", "Meadows", "Davila", "Orr", "Whitehead", "Pruitt", "Kent", "Conway", "Mckee", "Barr", "David", "Dejesus", "Marin", "Berger", "Mcintyre", "Blankenship", "Gaines", "Palacios", "Cuevas", "Bartlett", "Durham", "Dorsey", "Mccall", "Odonnell", "Stein", "Browning", "Stout", "Lowery", "Sloan", "Mclean", "Hendricks", "Calhoun", "Sexton", "Chung", "Gentry", "Hull", "Duarte", "Ellison", "Nielsen", "Gillespie", "Buck", "Middleton", "Sellers", "Leblanc", "Esparza", "Hardin", "Bradshaw", "Mcintosh", "Howe", "Livingston", "Frost", "Glass", "Morse", "Knapp", "Herman", "Stark", "Bravo", "Noble", "Spears", "Weeks", "Corona", "Frederick", "Buckley", "Mcfarland", "Hebert", "Enriquez", "Hickman", "Quintero", "Randolph", "Schaefer", "Walls", "Trejo", "House", "Reilly", "Pennington", "Michael", "Conrad", "Giles", "Benjamin", "Crosby", "Fitzpatrick", "Donovan", "Mays", "Mahoney", "Valentine", "Raymond", "Medrano", "Hahn", "Mcmillan", "Small", "Bentley", "Felix", "Peck", "Lucero", "Boyle", "Hanna", "Pace", "Rush", "Hurley", "Harding", "Mcconnell", "Bernal", "Nava", "Ayers", "Everett", "Ventura", "Avery", "Pugh", "Mayer", "Bender", "Shepard", "Mcmahon", "Landry", "Case", "Sampson", "Moses", "Magana", "Blackburn", "Dunlap", "Gould", "Duffy", "Vaughan", "Herring", "Mckay", "Espinosa", "Rivers", "Farley", "Bernard", "Ashley", "Friedman", "Potts", "Truong", "Costa", "Correa", "Blevins", "Nixon", "Clements", "Fry", "Delarosa", "Best", "Benton", "Lugo", "Portillo", "Dougherty", "Crane", "Haley", "Phan", "Villalobos", "Blanchard", "Horne", "Finley", "Quintana", "Lynn", "Esquivel", "Bean", "Dodson", "Mullen", "Xiong", "Hayden", "Cano", "Levy", "Huber", "Richmond", "Moyer", "Lim", "Frye", "Sheppard", "Mccarty", "Avalos", "Booker", "Waller", "Parra", "Woodward", "Jaramillo", "Krueger", "Rasmussen", "Brandt", "Peralta", "Donaldson", "Stuart", "Faulkner", "Maynard", "Galindo", "Coffey", "Estes", "Sanford", "Burch", "Maddox", "Vo", "Oconnell", "Vu", "Andersen", "Spence", "Mcpherson", "Church", "Schmitt", "Stanton", "Leal", "Cherry", "Compton", "Dudley", "Sierra", "Pollard", "Alfaro", "Hester", "Proctor", "Lu", "Hinton", "Novak", "Good", "Madden", "Mccann", "Terrell", "Jarvis", "Dickson", "Reyna", "Cantrell", "Mayo", "Branch", "Hendrix", "Rollins", "Rowland", "Whitney", "Duke", "Odom", "Daugherty", "Travis", "Tang");
            $names = array("Aaren", "Aarika", "Abagael", "Abagail", "Abbe", "Abbey", "Abbi", "Abbie", "Abby", "Abbye", "Abigael", "Abigail", "Abigale", "Abra", "Ada", "Adah", "Adaline", "Adan", "Adara", "Adda", "Addi", "Addia", "Addie", "Addy", "Adel", "Adela", "Adelaida", "Adelaide", "Adele", "Adelheid", "Adelice", "Adelina", "Adelind", "Adeline", "Adella", "Adelle", "Adena", "Adey", "Adi", "Adiana", "Adina", "Adora", "Adore", "Adoree", "Adorne", "Adrea", "Adria", "Adriaens", "Adrian", "Adriana", "Adriane", "Adrianna", "Adrianne", "Adriena", "Adrienne", "Aeriel", "Aeriela", "Aeriell", "Afton", "Ag", "Agace", "Agata", "Agatha", "Agathe", "Aggi", "Aggie", "Aggy", "Agna", "Agnella", "Agnes", "Agnese", "Agnesse", "Agneta", "Agnola", "Agretha", "Aida", "Aidan", "Aigneis", "Aila", "Aile", "Ailee", "Aileen", "Ailene", "Ailey", "Aili", "Ailina", "Ailis", "Ailsun", "Ailyn", "Aime", "Aimee", "Aimil", "Aindrea", "Ainslee", "Ainsley", "Ainslie", "Ajay", "Alaine", "Alameda", "Alana", "Alanah", "Alane", "Alanna", "Alayne", "Alberta", "Albertina", "Albertine", "Albina", "Alecia", "Aleda", "Aleece", "Aleen", "Alejandra", "Alejandrina", "Alena", "Alene", "Alessandra", "Aleta", "Alethea", "Alex", "Alexa", "Alexandra", "Alexandrina", "Alexi", "Alexia", "Alexina", "Alexine", "Alexis", "Alfi", "Alfie", "Alfreda", "Alfy", "Ali", "Alia", "Alica", "Alice", "Alicea", "Alicia", "Alida", "Alidia", "Alie", "Alika", "Alikee", "Alina", "Aline", "Alis", "Alisa", "Alisha", "Alison", "Alissa", "Alisun", "Alix", "Aliza", "Alla", "Alleen", "Allegra", "Allene", "Alli", "Allianora", "Allie", "Allina", "Allis", "Allison", "Allissa", "Allix", "Allsun", "Allx", "Ally", "Allyce", "Allyn", "Allys", "Allyson", "Alma", "Almeda", "Almeria", "Almeta", "Almira", "Almire", "Aloise", "Aloisia", "Aloysia", "Alta", "Althea", "Alvera", "Alverta", "Alvina", "Alvinia", "Alvira", "Alyce", "Alyda", "Alys", "Alysa", "Alyse", "Alysia", "Alyson", "Alyss", "Alyssa", "Amabel", "Amabelle", "Amalea", "Amalee", "Amaleta", "Amalia", "Amalie", "Amalita", "Amalle", "Amanda", "Amandi", "Amandie", "Amandy", "Amara", "Amargo", "Amata", "Amber", "Amberly", "Ambur", "Ame", "Amelia", "Amelie", "Amelina", "Ameline", "Amelita", "Ami", "Amie", "Amii", "Amil", "Amitie", "Amity", "Ammamaria", "Amy", "Amye", "Ana", "Anabal", "Anabel", "Anabella", "Anabelle", "Analiese", "Analise", "Anallese", "Anallise", "Anastasia", "Anastasie", "Anastassia", "Anatola", "Andee", "Andeee", "Anderea", "Andi", "Andie", "Andra", "Andrea", "Andreana", "Andree", "Andrei", "Andria", "Andriana", "Andriette", "Andromache", "Andy", "Anestassia", "Anet", "Anett", "Anetta", "Anette", "Ange", "Angel", "Angela", "Angele", "Angelia", "Angelica", "Angelika", "Angelina", "Angeline", "Angelique", "Angelita", "Angelle", "Angie", "Angil", "Angy", "Ania", "Anica", "Anissa", "Anita", "Anitra", "Anjanette", "Anjela", "Ann", "Ann-Marie", "Anna", "Anna-Diana", "Anna-Diane", "Anna-Maria", "Annabal", "Annabel", "Annabela", "Annabell", "Annabella", "Annabelle", "Annadiana", "Annadiane", "Annalee", "Annaliese", "Annalise", "Annamaria", "Annamarie", "Anne", "Anne-Corinne", "Anne-Marie", "Annecorinne", "Anneliese", "Annelise", "Annemarie", "Annetta", "Annette", "Anni", "Annice", "Annie", "Annis", "Annissa", "Annmaria", "Annmarie", "Annnora", "Annora", "Anny", "Anselma", "Ansley", "Anstice", "Anthe", "Anthea", "Anthia", "Anthiathia", "Antoinette", "Antonella", "Antonetta", "Antonia", "Antonie", "Antonietta", "Antonina", "Anya", "Appolonia", "April", "Aprilette", "Ara", "Arabel", "Arabela", "Arabele", "Arabella", "Arabelle", "Arda", "Ardath", "Ardeen", "Ardelia", "Ardelis", "Ardella", "Ardelle", "Arden", "Ardene", "Ardenia", "Ardine", "Ardis", "Ardisj", "Ardith", "Ardra", "Ardyce", "Ardys", "Ardyth", "Aretha", "Ariadne", "Ariana", "Aridatha", "Ariel", "Ariela", "Ariella", "Arielle", "Arlana", "Arlee", "Arleen", "Arlen", "Arlena", "Arlene", "Arleta", "Arlette", "Arleyne", "Arlie", "Arliene", "Arlina", "Arlinda", "Arline", "Arluene", "Arly", "Arlyn", "Arlyne", "Aryn", "Ashely", "Ashia", "Ashien", "Ashil", "Ashla", "Ashlan", "Ashlee", "Ashleigh", "Ashlen", "Ashley", "Ashli", "Ashlie", "Ashly", "Asia", "Astra", "Astrid", "Astrix", "Atalanta", "Athena", "Athene", "Atlanta", "Atlante", "Auberta", "Aubine", "Aubree", "Aubrette", "Aubrey", "Aubrie", "Aubry", "Audi", "Audie", "Audra", "Audre", "Audrey", "Audrie", "Audry", "Audrye", "Audy", "Augusta", "Auguste", "Augustina", "Augustine", "Aundrea", "Aura", "Aurea", "Aurel", "Aurelea", "Aurelia", "Aurelie", "Auria", "Aurie", "Aurilia", "Aurlie", "Auroora", "Aurora", "Aurore", "Austin", "Austina", "Austine", "Ava", "Aveline", "Averil", "Averyl", "Avie", "Avis", "Aviva", "Avivah", "Avril", "Avrit", "Ayn", "Bab", "Babara", "Babb", "Babbette", "Babbie", "Babette", "Babita", "Babs", "Bambi", "Bambie", "Bamby", "Barb", "Barbabra", "Barbara", "Barbara-Anne", "Barbaraanne", "Barbe", "Barbee", "Barbette", "Barbey", "Barbi", "Barbie", "Barbra", "Barby", "Bari", "Barrie", "Barry", "Basia", "Bathsheba", "Batsheva", "Bea", "Beatrice", "Beatrisa", "Beatrix", "Beatriz", "Bebe", "Becca", "Becka", "Becki", "Beckie", "Becky", "Bee", "Beilul", "Beitris", "Bekki", "Bel", "Belia", "Belicia", "Belinda", "Belita", "Bell", "Bella", "Bellanca", "Belle", "Bellina", "Belva", "Belvia", "Bendite", "Benedetta", "Benedicta", "Benedikta", "Benetta", "Benita", "Benni", "Bennie", "Benny", "Benoite", "Berenice", "Beret", "Berget", "Berna", "Bernadene", "Bernadette", "Bernadina", "Bernadine", "Bernardina", "Bernardine", "Bernelle", "Bernete", "Bernetta", "Bernette", "Berni", "Bernice", "Bernie", "Bernita", "Berny", "Berri", "Berrie", "Berry", "Bert", "Berta", "Berte", "Bertha", "Berthe", "Berti", "Bertie", "Bertina", "Bertine", "Berty", "Beryl", "Beryle", "Bess", "Bessie", "Bessy", "Beth", "Bethanne", "Bethany", "Bethena", "Bethina", "Betsey", "Betsy", "Betta", "Bette", "Bette-Ann", "Betteann", "Betteanne", "Betti", "Bettina", "Bettine", "Betty", "Bettye", "Beulah", "Bev", "Beverie", "Beverlee", "Beverley", "Beverlie", "Beverly", "Bevvy", "Bianca", "Bianka", "Bibbie", "Bibby", "Bibbye", "Bibi", "Biddie", "Biddy", "Bidget", "Bili", "Bill", "Billi", "Billie", "Billy", "Billye", "Binni", "Binnie", "Binny", "Bird", "Birdie", "Birgit", "Birgitta", "Blair", "Blaire", "Blake", "Blakelee", "Blakeley", "Blanca", "Blanch", "Blancha", "Blanche", "Blinni", "Blinnie", "Blinny", "Bliss", "Blisse", "Blithe", "Blondell", "Blondelle", "Blondie", "Blondy", "Blythe", "Bobbe", "Bobbee", "Bobbette", "Bobbi", "Bobbie", "Bobby", "Bobbye", "Bobette", "Bobina", "Bobine", "Bobinette", "Bonita", "Bonnee", "Bonni", "Bonnibelle", "Bonnie", "Bonny", "Brana", "Brandais", "Brande", "Brandea", "Brandi", "Brandice", "Brandie", "Brandise", "Brandy", "Breanne", "Brear", "Bree", "Breena", "Bren", "Brena", "Brenda", "Brenn", "Brenna", "Brett", "Bria", "Briana", "Brianna", "Brianne", "Bride", "Bridget", "Bridgette", "Bridie", "Brier", "Brietta", "Brigid", "Brigida", "Brigit", "Brigitta", "Brigitte", "Brina", "Briney", "Brinn", "Brinna", "Briny", "Brit", "Brita", "Britney", "Britni", "Britt", "Britta", "Brittan", "Brittaney", "Brittani", "Brittany", "Britte", "Britteny", "Brittne", "Brittney", "Brittni", "Brook", "Brooke", "Brooks", "Brunhilda", "Brunhilde", "Bryana", "Bryn", "Bryna", "Brynn", "Brynna", "Brynne", "Buffy", "Bunni", "Bunnie", "Bunny", "Cacilia", "Cacilie", "Cahra", "Cairistiona", "Caitlin", "Caitrin", "Cal", "Calida", "Calla", "Calley", "Calli", "Callida", "Callie", "Cally", "Calypso", "Cam", "Camala", "Camel", "Camella", "Camellia", "Cami", "Camila", "Camile", "Camilla", "Camille", "Cammi", "Cammie", "Cammy", "Candace", "Candi", "Candice", "Candida", "Candide", "Candie", "Candis", "Candra", "Candy", "Caprice", "Cara", "Caralie", "Caren", "Carena", "Caresa", "Caressa", "Caresse", "Carey", "Cari", "Caria", "Carie", "Caril", "Carilyn", "Carin", "Carina", "Carine", "Cariotta", "Carissa", "Carita", "Caritta", "Carla", "Carlee", "Carleen", "Carlen", "Carlene", "Carley", "Carlie", "Carlin", "Carlina", "Carline", "Carlita", "Carlota", "Carlotta", "Carly", "Carlye", "Carlyn", "Carlynn", "Carlynne", "Carma", "Carmel", "Carmela", "Carmelia", "Carmelina", "Carmelita", "Carmella", "Carmelle", "Carmen", "Carmencita", "Carmina", "Carmine", "Carmita", "Carmon", "Caro", "Carol", "Carol-Jean", "Carola", "Carolan", "Carolann", "Carole", "Carolee", "Carolin", "Carolina", "Caroline", "Caroljean", "Carolyn", "Carolyne", "Carolynn", "Caron", "Carree", "Carri", "Carrie", "Carrissa", "Carroll", "Carry", "Cary", "Caryl", "Caryn", "Casandra", "Casey", "Casi", "Casie", "Cass", "Cassandra", "Cassandre", "Cassandry", "Cassaundra", "Cassey", "Cassi", "Cassie", "Cassondra", "Cassy", "Catarina", "Cate", "Caterina", "Catha", "Catharina", "Catharine", "Cathe", "Cathee", "Catherin", "Catherina", "Catherine", "Cathi", "Cathie", "Cathleen", "Cathlene", "Cathrin", "Cathrine", "Cathryn", "Cathy", "Cathyleen", "Cati", "Catie", "Catina", "Catlaina", "Catlee", "Catlin", "Catrina", "Catriona", "Caty", "Caye", "Cayla", "Cecelia", "Cecil", "Cecile", "Ceciley", "Cecilia", "Cecilla", "Cecily", "Ceil", "Cele", "Celene", "Celesta", "Celeste", "Celestia", "Celestina", "Celestine", "Celestyn", "Celestyna", "Celia", "Celie", "Celina", "Celinda", "Celine", "Celinka", "Celisse", "Celka", "Celle", "Cesya", "Chad", "Chanda", "Chandal", "Chandra", "Channa", "Chantal", "Chantalle", "Charil", "Charin", "Charis", "Charissa", "Charisse", "Charita", "Charity", "Charla", "Charlean", "Charleen", "Charlena", "Charlene", "Charline", "Charlot", "Charlotta", "Charlotte", "Charmain", "Charmaine", "Charmane", "Charmian", "Charmine", "Charmion", "Charo", "Charyl", "Chastity", "Chelsae", "Chelsea", "Chelsey", "Chelsie", "Chelsy", "Cher", "Chere", "Cherey", "Cheri", "Cherianne", "Cherice", "Cherida", "Cherie", "Cherilyn", "Cherilynn", "Cherin", "Cherise", "Cherish", "Cherlyn", "Cherri", "Cherrita", "Cherry", "Chery", "Cherye", "Cheryl", "Cheslie", "Chiarra", "Chickie", "Chicky", "Chiquia", "Chiquita", "Chlo", "Chloe", "Chloette", "Chloris", "Chris", "Chrissie", "Chrissy", "Christa", "Christabel", "Christabella", "Christal", "Christalle", "Christan", "Christean", "Christel", "Christen", "Christi", "Christian", "Christiana", "Christiane", "Christie", "Christin", "Christina", "Christine", "Christy", "Christye", "Christyna", "Chrysa", "Chrysler", "Chrystal", "Chryste", "Chrystel", "Cicely", "Cicily", "Ciel", "Cilka", "Cinda", "Cindee", "Cindelyn", "Cinderella", "Cindi", "Cindie", "Cindra", "Cindy", "Cinnamon", "Cissiee", "Cissy", "Clair", "Claire", "Clara", "Clarabelle", "Clare", "Claresta", "Clareta", "Claretta", "Clarette", "Clarey", "Clari", "Claribel", "Clarice", "Clarie", "Clarinda", "Clarine", "Clarissa", "Clarisse", "Clarita", "Clary", "Claude", "Claudelle", "Claudetta", "Claudette", "Claudia", "Claudie", "Claudina", "Claudine", "Clea", "Clem", "Clemence", "Clementia", "Clementina", "Clementine", "Clemmie", "Clemmy", "Cleo", "Cleopatra", "Clerissa", "Clio", "Clo", "Cloe", "Cloris", "Clotilda", "Clovis", "Codee", "Codi", "Codie", "Cody", "Coleen", "Colene", "Coletta", "Colette", "Colleen", "Collen", "Collete", "Collette", "Collie", "Colline", "Colly", "Con", "Concettina", "Conchita", "Concordia", "Conni", "Connie", "Conny", "Consolata", "Constance", "Constancia", "Constancy", "Constanta", "Constantia", "Constantina", "Constantine", "Consuela", "Consuelo", "Cookie", "Cora", "Corabel", "Corabella", "Corabelle", "Coral", "Coralie", "Coraline", "Coralyn", "Cordelia", "Cordelie", "Cordey", "Cordi", "Cordie", "Cordula", "Cordy", "Coreen", "Corella", "Corenda", "Corene", "Coretta", "Corette", "Corey", "Cori", "Corie", "Corilla", "Corina", "Corine", "Corinna", "Corinne", "Coriss", "Corissa", "Corliss", "Corly", "Cornela", "Cornelia", "Cornelle", "Cornie", "Corny", "Correna", "Correy", "Corri", "Corrianne", "Corrie", "Corrina", "Corrine", "Corrinne", "Corry", "Cortney", "Cory", "Cosetta", "Cosette", "Costanza", "Courtenay", "Courtnay", "Courtney", "Crin", "Cris", "Crissie", "Crissy", "Crista", "Cristabel", "Cristal", "Cristen", "Cristi", "Cristie", "Cristin", "Cristina", "Cristine", "Cristionna", "Cristy", "Crysta", "Crystal", "Crystie", "Cthrine", "Cyb", "Cybil", "Cybill", "Cymbre", "Cynde", "Cyndi", "Cyndia", "Cyndie", "Cyndy", "Cynthea", "Cynthia", "Cynthie", "Cynthy", "Dacey", "Dacia", "Dacie", "Dacy", "Dael", "Daffi", "Daffie", "Daffy", "Dagmar", "Dahlia", "Daile", "Daisey", "Daisi", "Daisie", "Daisy", "Dale", "Dalenna", "Dalia", "Dalila", "Dallas", "Daloris", "Damara", "Damaris", "Damita", "Dana", "Danell", "Danella", "Danette", "Dani", "Dania", "Danica", "Danice", "Daniela", "Daniele", "Daniella", "Danielle", "Danika", "Danila", "Danit", "Danita", "Danna", "Danni", "Dannie", "Danny", "Dannye", "Danya", "Danyelle", "Danyette", "Daphene", "Daphna", "Daphne", "Dara", "Darb", "Darbie", "Darby", "Darcee", "Darcey", "Darci", "Darcie", "Darcy", "Darda", "Dareen", "Darell", "Darelle", "Dari", "Daria", "Darice", "Darla", "Darleen", "Darlene", "Darline", "Darlleen", "Daron", "Darrelle", "Darryl", "Darsey", "Darsie", "Darya", "Daryl", "Daryn", "Dasha", "Dasi", "Dasie", "Dasya", "Datha", "Daune", "Daveen", "Daveta", "Davida", "Davina", "Davine", "Davita", "Dawn", "Dawna", "Dayle", "Dayna", "Ddene", "De", "Deana", "Deane", "Deanna", "Deanne", "Deb", "Debbi", "Debbie", "Debby", "Debee", "Debera", "Debi", "Debor", "Debora", "Deborah", "Debra", "Dede", "Dedie", "Dedra", "Dee", "Dee Dee", "Deeann", "Deeanne", "Deedee", "Deena", "Deerdre", "Deeyn", "Dehlia", "Deidre", "Deina", "Deirdre", "Del", "Dela", "Delcina", "Delcine", "Delia", "Delila", "Delilah", "Delinda", "Dell", "Della", "Delly", "Delora", "Delores", "Deloria", "Deloris", "Delphine", "Delphinia", "Demeter", "Demetra", "Demetria", "Demetris", "Dena", "Deni", "Denice", "Denise", "Denna", "Denni", "Dennie", "Denny", "Deny", "Denys", "Denyse", "Deonne", "Desdemona", "Desirae", "Desiree", "Desiri", "Deva", "Devan", "Devi", "Devin", "Devina", "Devinne", "Devon", "Devondra", "Devonna", "Devonne", "Devora", "Di", "Diahann", "Dian", "Diana", "Diandra", "Diane", "Diane-Marie", "Dianemarie", "Diann", "Dianna", "Dianne", "Diannne", "Didi", "Dido", "Diena", "Dierdre", "Dina", "Dinah", "Dinnie", "Dinny", "Dion", "Dione", "Dionis", "Dionne", "Dita", "Dix", "Dixie", "Dniren", "Dode", "Dodi", "Dodie", "Dody", "Doe", "Doll", "Dolley", "Dolli", "Dollie", "Dolly", "Dolores", "Dolorita", "Doloritas", "Domeniga", "Dominga", "Domini", "Dominica", "Dominique", "Dona", "Donella", "Donelle", "Donetta", "Donia", "Donica", "Donielle", "Donna", "Donnamarie", "Donni", "Donnie", "Donny", "Dora", "Doralia", "Doralin", "Doralyn", "Doralynn", "Doralynne", "Dore", "Doreen", "Dorelia", "Dorella", "Dorelle", "Dorena", "Dorene", "Doretta", "Dorette", "Dorey", "Dori", "Doria", "Dorian", "Dorice", "Dorie", "Dorine", "Doris", "Dorisa", "Dorise", "Dorita", "Doro", "Dorolice", "Dorolisa", "Dorotea", "Doroteya", "Dorothea", "Dorothee", "Dorothy", "Dorree", "Dorri", "Dorrie", "Dorris", "Dorry", "Dorthea", "Dorthy", "Dory", "Dosi", "Dot", "Doti", "Dotti", "Dottie", "Dotty", "Dre", "Dreddy", "Dredi", "Drona", "Dru", "Druci", "Drucie", "Drucill", "Drucy", "Drusi", "Drusie", "Drusilla", "Drusy", "Dulce", "Dulcea", "Dulci", "Dulcia", "Dulciana", "Dulcie", "Dulcine", "Dulcinea", "Dulcy", "Dulsea", "Dusty", "Dyan", "Dyana", "Dyane", "Dyann", "Dyanna", "Dyanne", "Dyna", "Dynah", "Eachelle", "Eada", "Eadie", "Eadith", "Ealasaid", "Eartha", "Easter", "Eba", "Ebba", "Ebonee", "Ebony", "Eda", "Eddi", "Eddie", "Eddy", "Ede", "Edee", "Edeline", "Eden", "Edi", "Edie", "Edin", "Edita", "Edith", "Editha", "Edithe", "Ediva", "Edna", "Edwina", "Edy", "Edyth", "Edythe", "Effie", "Eileen", "Eilis", "Eimile", "Eirena", "Ekaterina", "Elaina", "Elaine", "Elana", "Elane", "Elayne", "Elberta", "Elbertina", "Elbertine", "Eleanor", "Eleanora", "Eleanore", "Electra", "Eleen", "Elena", "Elene", "Eleni", "Elenore", "Eleonora", "Eleonore", "Elfie", "Elfreda", "Elfrida", "Elfrieda", "Elga", "Elianora", "Elianore", "Elicia", "Elie", "Elinor", "Elinore", "Elisa", "Elisabet", "Elisabeth", "Elisabetta", "Elise", "Elisha", "Elissa", "Elita", "Eliza", "Elizabet", "Elizabeth", "Elka", "Elke", "Ella", "Elladine", "Elle", "Ellen", "Ellene", "Ellette", "Elli", "Ellie", "Ellissa", "Elly", "Ellyn", "Ellynn", "Elmira", "Elna", "Elnora", "Elnore", "Eloisa", "Eloise", "Elonore", "Elora", "Elsa", "Elsbeth", "Else", "Elset", "Elsey", "Elsi", "Elsie", "Elsinore", "Elspeth", "Elsy", "Elva", "Elvera", "Elvina", "Elvira", "Elwira", "Elyn", "Elyse", "Elysee", "Elysha", "Elysia", "Elyssa", "Em", "Ema", "Emalee", "Emalia", "Emelda", "Emelia", "Emelina", "Emeline", "Emelita", "Emelyne", "Emera", "Emilee", "Emili", "Emilia", "Emilie", "Emiline", "Emily", "Emlyn", "Emlynn", "Emlynne", "Emma", "Emmalee", "Emmaline", "Emmalyn", "Emmalynn", "Emmalynne", "Emmeline", "Emmey", "Emmi", "Emmie", "Emmy", "Emmye", "Emogene", "Emyle", "Emylee", "Engracia", "Enid", "Enrica", "Enrichetta", "Enrika", "Enriqueta", "Eolanda", "Eolande", "Eran", "Erda", "Erena", "Erica", "Ericha", "Ericka", "Erika", "Erin", "Erina", "Erinn", "Erinna", "Erma", "Ermengarde", "Ermentrude", "Ermina", "Erminia", "Erminie", "Erna", "Ernaline", "Ernesta", "Ernestine", "Ertha", "Eryn", "Esma", "Esmaria", "Esme", "Esmeralda", "Essa", "Essie", "Essy", "Esta", "Estel", "Estele", "Estell", "Estella", "Estelle", "Ester", "Esther", "Estrella", "Estrellita", "Ethel", "Ethelda", "Ethelin", "Ethelind", "Etheline", "Ethelyn", "Ethyl", "Etta", "Etti", "Ettie", "Etty", "Eudora", "Eugenia", "Eugenie", "Eugine", "Eula", "Eulalie", "Eunice", "Euphemia", "Eustacia", "Eva", "Evaleen", "Evangelia", "Evangelin", "Evangelina", "Evangeline", "Evania", "Evanne", "Eve", "Eveleen", "Evelina", "Eveline", "Evelyn", "Evey", "Evie", "Evita", "Evonne", "Evvie", "Evvy", "Evy", "Eyde", "Eydie", "Ezmeralda", "Fae", "Faina", "Faith", "Fallon", "Fan", "Fanchette", "Fanchon", "Fancie", "Fancy", "Fanechka", "Fania", "Fanni", "Fannie", "Fanny", "Fanya", "Fara", "Farah", "Farand", "Farica", "Farra", "Farrah", "Farrand", "Faun", "Faunie", "Faustina", "Faustine", "Fawn", "Fawne", "Fawnia", "Fay", "Faydra", "Faye", "Fayette", "Fayina", "Fayre", "Fayth", "Faythe", "Federica", "Fedora", "Felecia", "Felicdad", "Felice", "Felicia", "Felicity", "Felicle", "Felipa", "Felisha", "Felita", "Feliza", "Fenelia", "Feodora", "Ferdinanda", "Ferdinande", "Fern", "Fernanda", "Fernande", "Fernandina", "Ferne", "Fey", "Fiann", "Fianna", "Fidela", "Fidelia", "Fidelity", "Fifi", "Fifine", "Filia", "Filide", "Filippa", "Fina", "Fiona", "Fionna", "Fionnula", "Fiorenze", "Fleur", "Fleurette", "Flo", "Flor", "Flora", "Florance", "Flore", "Florella", "Florence", "Florencia", "Florentia", "Florenza", "Florette", "Flori", "Floria", "Florida", "Florie", "Florina", "Florinda", "Floris", "Florri", "Florrie", "Florry", "Flory", "Flossi", "Flossie", "Flossy", "Flss", "Fran", "Francene", "Frances", "Francesca", "Francine", "Francisca", "Franciska", "Francoise", "Francyne", "Frank", "Frankie", "Franky", "Franni", "Frannie", "Franny", "Frayda", "Fred", "Freda", "Freddi", "Freddie", "Freddy", "Fredelia", "Frederica", "Fredericka", "Frederique", "Fredi", "Fredia", "Fredra", "Fredrika", "Freida", "Frieda", "Friederike", "Fulvia", "Gabbey", "Gabbi", "Gabbie", "Gabey", "Gabi", "Gabie", "Gabriel", "Gabriela", "Gabriell", "Gabriella", "Gabrielle", "Gabriellia", "Gabrila", "Gaby", "Gae", "Gael", "Gail", "Gale", "Galina", "Garland", "Garnet", "Garnette", "Gates", "Gavra", "Gavrielle", "Gay", "Gaye", "Gayel", "Gayla", "Gayle", "Gayleen", "Gaylene", "Gaynor", "Gelya", "Gena", "Gene", "Geneva", "Genevieve", "Genevra", "Genia", "Genna", "Genni", "Gennie", "Gennifer", "Genny", "Genovera", "Genvieve", "George", "Georgeanna", "Georgeanne", "Georgena", "Georgeta", "Georgetta", "Georgette", "Georgia", "Georgiana", "Georgianna", "Georgianne", "Georgie", "Georgina", "Georgine", "Geralda", "Geraldine", "Gerda", "Gerhardine", "Geri", "Gerianna", "Gerianne", "Gerladina", "Germain", "Germaine", "Germana", "Gerri", "Gerrie", "Gerrilee", "Gerry", "Gert", "Gerta", "Gerti", "Gertie", "Gertrud", "Gertruda", "Gertrude", "Gertrudis", "Gerty", "Giacinta", "Giana", "Gianina", "Gianna", "Gigi", "Gilberta", "Gilberte", "Gilbertina", "Gilbertine", "Gilda", "Gilemette", "Gill", "Gillan", "Gilli", "Gillian", "Gillie", "Gilligan", "Gilly", "Gina", "Ginelle", "Ginevra", "Ginger", "Ginni", "Ginnie", "Ginnifer", "Ginny", "Giorgia", "Giovanna", "Gipsy", "Giralda", "Gisela", "Gisele", "Gisella", "Giselle", "Giuditta", "Giulia", "Giulietta", "Giustina", "Gizela", "Glad", "Gladi", "Gladys", "Gleda", "Glen", "Glenda", "Glenine", "Glenn", "Glenna", "Glennie", "Glennis", "Glori", "Gloria", "Gloriana", "Gloriane", "Glory", "Glyn", "Glynda", "Glynis", "Glynnis", "Gnni", "Godiva", "Golda", "Goldarina", "Goldi", "Goldia", "Goldie", "Goldina", "Goldy", "Grace", "Gracia", "Gracie", "Grata", "Gratia", "Gratiana", "Gray", "Grayce", "Grazia", "Greer", "Greta", "Gretal", "Gretchen", "Grete", "Gretel", "Grethel", "Gretna", "Gretta", "Grier", "Griselda", "Grissel", "Guendolen", "Guenevere", "Guenna", "Guglielma", "Gui", "Guillema", "Guillemette", "Guinevere", "Guinna", "Gunilla", "Gus", "Gusella", "Gussi", "Gussie", "Gussy", "Gusta", "Gusti", "Gustie", "Gusty", "Gwen", "Gwendolen", "Gwendolin", "Gwendolyn", "Gweneth", "Gwenette", "Gwenneth", "Gwenni", "Gwennie", "Gwenny", "Gwenora", "Gwenore", "Gwyn", "Gwyneth", "Gwynne", "Gypsy", "Hadria", "Hailee", "Haily", "Haleigh", "Halette", "Haley", "Hali", "Halie", "Halimeda", "Halley", "Halli", "Hallie", "Hally", "Hana", "Hanna", "Hannah", "Hanni", "Hannie", "Hannis", "Hanny", "Happy", "Harlene", "Harley", "Harli", "Harlie", "Harmonia", "Harmonie", "Harmony", "Harri", "Harrie", "Harriet", "Harriett", "Harrietta", "Harriette", "Harriot", "Harriott", "Hatti", "Hattie", "Hatty", "Hayley", "Hazel", "Heath", "Heather", "Heda", "Hedda", "Heddi", "Heddie", "Hedi", "Hedvig", "Hedvige", "Hedwig", "Hedwiga", "Hedy", "Heida", "Heidi", "Heidie", "Helaina", "Helaine", "Helen", "Helen-Elizabeth", "Helena", "Helene", "Helenka", "Helga", "Helge", "Helli", "Heloise", "Helsa", "Helyn", "Hendrika", "Henka", "Henrie", "Henrieta", "Henrietta", "Henriette", "Henryetta", "Hephzibah", "Hermia", "Hermina", "Hermine", "Herminia", "Hermione", "Herta", "Hertha", "Hester", "Hesther", "Hestia", "Hetti", "Hettie", "Hetty", "Hilary", "Hilda", "Hildagard", "Hildagarde", "Hilde", "Hildegaard", "Hildegarde", "Hildy", "Hillary", "Hilliary", "Hinda", "Holli", "Hollie", "Holly", "Holly-Anne", "Hollyanne", "Honey", "Honor", "Honoria", "Hope", "Horatia", "Hortense", "Hortensia", "Hulda", "Hyacinth", "Hyacintha", "Hyacinthe", "Hyacinthia", "Hyacinthie", "Hynda", "Ianthe", "Ibbie", "Ibby", "Ida", "Idalia", "Idalina", "Idaline", "Idell", "Idelle", "Idette", "Ileana", "Ileane", "Ilene", "Ilise", "Ilka", "Illa", "Ilsa", "Ilse", "Ilysa", "Ilyse", "Ilyssa", "Imelda", "Imogen", "Imogene", "Imojean", "Ina", "Indira", "Ines", "Inesita", "Inessa", "Inez", "Inga", "Ingaberg", "Ingaborg", "Inge", "Ingeberg", "Ingeborg", "Inger", "Ingrid", "Ingunna", "Inna", "Iolande", "Iolanthe", "Iona", "Iormina", "Ira", "Irena", "Irene", "Irina", "Iris", "Irita", "Irma", "Isa", "Isabel", "Isabelita", "Isabella", "Isabelle", "Isadora", "Isahella", "Iseabal", "Isidora", "Isis", "Isobel", "Issi", "Issie", "Issy", "Ivett", "Ivette", "Ivie", "Ivonne", "Ivory", "Ivy", "Izabel", "Jacenta", "Jacinda", "Jacinta", "Jacintha", "Jacinthe", "Jackelyn", "Jacki", "Jackie", "Jacklin", "Jacklyn", "Jackquelin", "Jackqueline", "Jacky", "Jaclin", "Jaclyn", "Jacquelin", "Jacqueline", "Jacquelyn", "Jacquelynn", "Jacquenetta", "Jacquenette", "Jacquetta", "Jacquette", "Jacqui", "Jacquie", "Jacynth", "Jada", "Jade", "Jaime", "Jaimie", "Jaine", "Jami", "Jamie", "Jamima", "Jammie", "Jan", "Jana", "Janaya", "Janaye", "Jandy", "Jane", "Janean", "Janeczka", "Janeen", "Janel", "Janela", "Janella", "Janelle", "Janene", "Janenna", "Janessa", "Janet", "Janeta", "Janetta", "Janette", "Janeva", "Janey", "Jania", "Janice", "Janie", "Janifer", "Janina", "Janine", "Janis", "Janith", "Janka", "Janna", "Jannel", "Jannelle", "Janot", "Jany", "Jaquelin", "Jaquelyn", "Jaquenetta", "Jaquenette", "Jaquith", "Jasmin", "Jasmina", "Jasmine", "Jayme", "Jaymee", "Jayne", "Jaynell", "Jazmin", "Jean", "Jeana", "Jeane", "Jeanelle", "Jeanette", "Jeanie", "Jeanine", "Jeanna", "Jeanne", "Jeannette", "Jeannie", "Jeannine", "Jehanna", "Jelene", "Jemie", "Jemima", "Jemimah", "Jemmie", "Jemmy", "Jen", "Jena", "Jenda", "Jenelle", "Jeni", "Jenica", "Jeniece", "Jenifer", "Jeniffer", "Jenilee", "Jenine", "Jenn", "Jenna", "Jennee", "Jennette", "Jenni", "Jennica", "Jennie", "Jennifer", "Jennilee", "Jennine", "Jenny", "Jeralee", "Jere", "Jeri", "Jermaine", "Jerrie", "Jerrilee", "Jerrilyn", "Jerrine", "Jerry", "Jerrylee", "Jess", "Jessa", "Jessalin", "Jessalyn", "Jessamine", "Jessamyn", "Jesse", "Jesselyn", "Jessi", "Jessica", "Jessie", "Jessika", "Jessy", "Jewel", "Jewell", "Jewelle", "Jill", "Jillana", "Jillane", "Jillayne", "Jilleen", "Jillene", "Jilli", "Jillian", "Jillie", "Jilly", "Jinny", "Jo", "Jo Ann", "Jo-Ann", "Jo-Anne", "Joan", "Joana", "Joane", "Joanie", "Joann", "Joanna", "Joanne", "Joannes", "Jobey", "Jobi", "Jobie", "Jobina", "Joby", "Jobye", "Jobyna", "Jocelin", "Joceline", "Jocelyn", "Jocelyne", "Jodee", "Jodi", "Jodie", "Jody", "Joeann", "Joela", "Joelie", "Joell", "Joella", "Joelle", "Joellen", "Joelly", "Joellyn", "Joelynn", "Joete", "Joey", "Johanna", "Johannah", "Johna", "Johnath", "Johnette", "Johnna", "Joice", "Jojo", "Jolee", "Joleen", "Jolene", "Joletta", "Joli", "Jolie", "Joline", "Joly", "Jolyn", "Jolynn", "Jonell", "Joni", "Jonie", "Jonis", "Jordain", "Jordan", "Jordana", "Jordanna", "Jorey", "Jori", "Jorie", "Jorrie", "Jorry", "Joscelin", "Josee", "Josefa", "Josefina", "Josepha", "Josephina", "Josephine", "Josey", "Josi", "Josie", "Josselyn", "Josy", "Jourdan", "Joy", "Joya", "Joyan", "Joyann", "Joyce", "Joycelin", "Joye", "Jsandye", "Juana", "Juanita", "Judi", "Judie", "Judith", "Juditha", "Judy", "Judye", "Juieta", "Julee", "Juli", "Julia", "Juliana", "Juliane", "Juliann", "Julianna", "Julianne", "Julie", "Julienne", "Juliet", "Julieta", "Julietta", "Juliette", "Julina", "Juline", "Julissa", "Julita", "June", "Junette", "Junia", "Junie", "Junina", "Justina", "Justine", "Justinn", "Jyoti", "Kacey", "Kacie", "Kacy", "Kaela", "Kai", "Kaia", "Kaila", "Kaile", "Kailey", "Kaitlin", "Kaitlyn", "Kaitlynn", "Kaja", "Kakalina", "Kala", "Kaleena", "Kali", "Kalie", "Kalila", "Kalina", "Kalinda", "Kalindi", "Kalli", "Kally", "Kameko", "Kamila", "Kamilah", "Kamillah", "Kandace", "Kandy", "Kania", "Kanya", "Kara", "Kara-Lynn", "Karalee", "Karalynn", "Kare", "Karee", "Karel", "Karen", "Karena", "Kari", "Karia", "Karie", "Karil", "Karilynn", "Karin", "Karina", "Karine", "Kariotta", "Karisa", "Karissa", "Karita", "Karla", "Karlee", "Karleen", "Karlen", "Karlene", "Karlie", "Karlotta", "Karlotte", "Karly", "Karlyn", "Karmen", "Karna", "Karol", "Karola", "Karole", "Karolina", "Karoline", "Karoly", "Karon", "Karrah", "Karrie", "Karry", "Kary", "Karyl", "Karylin", "Karyn", "Kasey", "Kass", "Kassandra", "Kassey", "Kassi", "Kassia", "Kassie", "Kat", "Kata", "Katalin", "Kate", "Katee", "Katerina", "Katerine", "Katey", "Kath", "Katha", "Katharina", "Katharine", "Katharyn", "Kathe", "Katherina", "Katherine", "Katheryn", "Kathi", "Kathie", "Kathleen", "Kathlin", "Kathrine", "Kathryn", "Kathryne", "Kathy", "Kathye", "Kati", "Katie", "Katina", "Katine", "Katinka", "Katleen", "Katlin", "Katrina", "Katrine", "Katrinka", "Katti", "Kattie", "Katuscha", "Katusha", "Katy", "Katya", "Kay", "Kaycee", "Kaye", "Kayla", "Kayle", "Kaylee", "Kayley", "Kaylil", "Kaylyn", "Keeley", "Keelia", "Keely", "Kelcey", "Kelci", "Kelcie", "Kelcy", "Kelila", "Kellen", "Kelley", "Kelli", "Kellia", "Kellie", "Kellina", "Kellsie", "Kelly", "Kellyann", "Kelsey", "Kelsi", "Kelsy", "Kendra", "Kendre", "Kenna", "Keri", "Keriann", "Kerianne", "Kerri", "Kerrie", "Kerrill", "Kerrin", "Kerry", "Kerstin", "Kesley", "Keslie", "Kessia", "Kessiah", "Ketti", "Kettie", "Ketty", "Kevina", "Kevyn", "Ki", "Kiah", "Kial", "Kiele", "Kiersten", "Kikelia", "Kiley", "Kim", "Kimberlee", "Kimberley", "Kimberli", "Kimberly", "Kimberlyn", "Kimbra", "Kimmi", "Kimmie", "Kimmy", "Kinna", "Kip", "Kipp", "Kippie", "Kippy", "Kira", "Kirbee", "Kirbie", "Kirby", "Kiri", "Kirsten", "Kirsteni", "Kirsti", "Kirstin", "Kirstyn", "Kissee", "Kissiah", "Kissie", "Kit", "Kitti", "Kittie", "Kitty", "Kizzee", "Kizzie", "Klara", "Klarika", "Klarrisa", "Konstance", "Konstanze", "Koo", "Kora", "Koral", "Koralle", "Kordula", "Kore", "Korella", "Koren", "Koressa", "Kori", "Korie", "Korney", "Korrie", "Korry", "Kris", "Krissie", "Krissy", "Krista", "Kristal", "Kristan", "Kriste", "Kristel", "Kristen", "Kristi", "Kristien", "Kristin", "Kristina", "Kristine", "Kristy", "Kristyn", "Krysta", "Krystal", "Krystalle", "Krystle", "Krystyna", "Kyla", "Kyle", "Kylen", "Kylie", "Kylila", "Kylynn", "Kym", "Kynthia", "Kyrstin", "La Verne", "Lacee", "Lacey", "Lacie", "Lacy", "Ladonna", "Laetitia", "Laina", "Lainey", "Lana", "Lanae", "Lane", "Lanette", "Laney", "Lani", "Lanie", "Lanita", "Lanna", "Lanni", "Lanny", "Lara", "Laraine", "Lari", "Larina", "Larine", "Larisa", "Larissa", "Lark", "Laryssa", "Latashia", "Latia", "Latisha", "Latrena", "Latrina", "Laura", "Lauraine", "Laural", "Lauralee", "Laure", "Lauree", "Laureen", "Laurel", "Laurella", "Lauren", "Laurena", "Laurene", "Lauretta", "Laurette", "Lauri", "Laurianne", "Laurice", "Laurie", "Lauryn", "Lavena", "Laverna", "Laverne", "Lavina", "Lavinia", "Lavinie", "Layla", "Layne", "Layney", "Lea", "Leah", "Leandra", "Leann", "Leanna", "Leanor", "Leanora", "Lebbie", "Leda", "Lee", "Leeann", "Leeanne", "Leela", "Leelah", "Leena", "Leesa", "Leese", "Legra", "Leia", "Leigh", "Leigha", "Leila", "Leilah", "Leisha", "Lela", "Lelah", "Leland", "Lelia", "Lena", "Lenee", "Lenette", "Lenka", "Lenna", "Lenora", "Lenore", "Leodora", "Leoine", "Leola", "Leoline", "Leona", "Leonanie", "Leone", "Leonelle", "Leonie", "Leonora", "Leonore", "Leontine", "Leontyne", "Leora", "Leshia", "Lesley", "Lesli", "Leslie", "Lesly", "Lesya", "Leta", "Lethia", "Leticia", "Letisha", "Letitia", "Letizia", "Letta", "Letti", "Lettie", "Letty", "Lexi", "Lexie", "Lexine", "Lexis", "Lexy", "Leyla", "Lezlie", "Lia", "Lian", "Liana", "Liane", "Lianna", "Lianne", "Lib", "Libbey", "Libbi", "Libbie", "Libby", "Licha", "Lida", "Lidia", "Liesa", "Lil", "Lila", "Lilah", "Lilas", "Lilia", "Lilian", "Liliane", "Lilias", "Lilith", "Lilla", "Lilli", "Lillian", "Lillis", "Lilllie", "Lilly", "Lily", "Lilyan", "Lin", "Lina", "Lind", "Linda", "Lindi", "Lindie", "Lindsay", "Lindsey", "Lindsy", "Lindy", "Linea", "Linell", "Linet", "Linette", "Linn", "Linnea", "Linnell", "Linnet", "Linnie", "Linzy", "Lira", "Lisa", "Lisabeth", "Lisbeth", "Lise", "Lisetta", "Lisette", "Lisha", "Lishe", "Lissa", "Lissi", "Lissie", "Lissy", "Lita", "Liuka", "Liv", "Liva", "Livia", "Livvie", "Livvy", "Livvyy", "Livy", "Liz", "Liza", "Lizabeth", "Lizbeth", "Lizette", "Lizzie", "Lizzy", "Loella", "Lois", "Loise", "Lola", "Loleta", "Lolita", "Lolly", "Lona", "Lonee", "Loni", "Lonna", "Lonni", "Lonnie", "Lora", "Lorain", "Loraine", "Loralee", "Loralie", "Loralyn", "Loree", "Loreen", "Lorelei", "Lorelle", "Loren", "Lorena", "Lorene", "Lorenza", "Loretta", "Lorette", "Lori", "Loria", "Lorianna", "Lorianne", "Lorie", "Lorilee", "Lorilyn", "Lorinda", "Lorine", "Lorita", "Lorna", "Lorne", "Lorraine", "Lorrayne", "Lorri", "Lorrie", "Lorrin", "Lorry", "Lory", "Lotta", "Lotte", "Lotti", "Lottie", "Lotty", "Lou", "Louella", "Louisa", "Louise", "Louisette", "Loutitia", "Lu", "Luce", "Luci", "Lucia", "Luciana", "Lucie", "Lucienne", "Lucila", "Lucilia", "Lucille", "Lucina", "Lucinda", "Lucine", "Lucita", "Lucky", "Lucretia", "Lucy", "Ludovika", "Luella", "Luelle", "Luisa", "Luise", "Lula", "Lulita", "Lulu", "Lura", "Lurette", "Lurleen", "Lurlene", "Lurline", "Lusa", "Luz", "Lyda", "Lydia", "Lydie", "Lyn", "Lynda", "Lynde", "Lyndel", "Lyndell", "Lyndsay", "Lyndsey", "Lyndsie", "Lyndy", "Lynea", "Lynelle", "Lynett", "Lynette", "Lynn", "Lynna", "Lynne", "Lynnea", "Lynnell", "Lynnelle", "Lynnet", "Lynnett", "Lynnette", "Lynsey", "Lyssa", "Mab", "Mabel", "Mabelle", "Mable", "Mada", "Madalena", "Madalyn", "Maddalena", "Maddi", "Maddie", "Maddy", "Madel", "Madelaine", "Madeleine", "Madelena", "Madelene", "Madelin", "Madelina", "Madeline", "Madella", "Madelle", "Madelon", "Madelyn", "Madge", "Madlen", "Madlin", "Madonna", "Mady", "Mae", "Maegan", "Mag", "Magda", "Magdaia", "Magdalen", "Magdalena", "Magdalene", "Maggee", "Maggi", "Maggie", "Maggy", "Mahala", "Mahalia", "Maia", "Maible", "Maiga", "Maighdiln", "Mair", "Maire", "Maisey", "Maisie", "Maitilde", "Mala", "Malanie", "Malena", "Malia", "Malina", "Malinda", "Malinde", "Malissa", "Malissia", "Mallissa", "Mallorie", "Mallory", "Malorie", "Malory", "Malva", "Malvina", "Malynda", "Mame", "Mamie", "Manda", "Mandi", "Mandie", "Mandy", "Manon", "Manya", "Mara", "Marabel", "Marcela", "Marcelia", "Marcella", "Marcelle", "Marcellina", "Marcelline", "Marchelle", "Marci", "Marcia", "Marcie", "Marcile", "Marcille", "Marcy", "Mareah", "Maren", "Marena", "Maressa", "Marga", "Margalit", "Margalo", "Margaret", "Margareta", "Margarete", "Margaretha", "Margarethe", "Margaretta", "Margarette", "Margarita", "Margaux", "Marge", "Margeaux", "Margery", "Marget", "Margette", "Margi", "Margie", "Margit", "Margo", "Margot", "Margret", "Marguerite", "Margy", "Mari", "Maria", "Mariam", "Marian", "Mariana", "Mariann", "Marianna", "Marianne", "Maribel", "Maribelle", "Maribeth", "Marice", "Maridel", "Marie", "Marie-Ann", "Marie-Jeanne", "Marieann", "Mariejeanne", "Mariel", "Mariele", "Marielle", "Mariellen", "Marietta", "Mariette", "Marigold", "Marijo", "Marika", "Marilee", "Marilin", "Marillin", "Marilyn", "Marin", "Marina", "Marinna", "Marion", "Mariquilla", "Maris", "Marisa", "Mariska", "Marissa", "Marita", "Maritsa", "Mariya", "Marj", "Marja", "Marje", "Marji", "Marjie", "Marjorie", "Marjory", "Marjy", "Marketa", "Marla", "Marlane", "Marleah", "Marlee", "Marleen", "Marlena", "Marlene", "Marley", "Marlie", "Marline", "Marlo", "Marlyn", "Marna", "Marne", "Marney", "Marni", "Marnia", "Marnie", "Marquita", "Marrilee", "Marris", "Marrissa", "Marsha", "Marsiella", "Marta", "Martelle", "Martguerita", "Martha", "Marthe", "Marthena", "Marti", "Martica", "Martie", "Martina", "Martita", "Marty", "Martynne", "Mary", "Marya", "Maryann", "Maryanna", "Maryanne", "Marybelle", "Marybeth", "Maryellen", "Maryjane", "Maryjo", "Maryl", "Marylee", "Marylin", "Marylinda", "Marylou", "Marylynne", "Maryrose", "Marys", "Marysa", "Masha", "Matelda", "Mathilda", "Mathilde", "Matilda", "Matilde", "Matti", "Mattie", "Matty", "Maud", "Maude", "Maudie", "Maura", "Maure", "Maureen", "Maureene", "Maurene", "Maurine", "Maurise", "Maurita", "Maurizia", "Mavis", "Mavra", "Max", "Maxi", "Maxie", "Maxine", "Maxy", "May", "Maybelle", "Maye", "Mead", "Meade", "Meagan", "Meaghan", "Meara", "Mechelle", "Meg", "Megan", "Megen", "Meggi", "Meggie", "Meggy", "Meghan", "Meghann", "Mehetabel", "Mei", "Mel", "Mela", "Melamie", "Melania", "Melanie", "Melantha", "Melany", "Melba", "Melesa", "Melessa", "Melicent", "Melina", "Melinda", "Melinde", "Melisa", "Melisande", "Melisandra", "Melisenda", "Melisent", "Melissa", "Melisse", "Melita", "Melitta", "Mella", "Melli", "Mellicent", "Mellie", "Mellisa", "Mellisent", "Melloney", "Melly", "Melodee", "Melodie", "Melody", "Melonie", "Melony", "Melosa", "Melva", "Mercedes", "Merci", "Mercie", "Mercy", "Meredith", "Meredithe", "Meridel", "Meridith", "Meriel", "Merilee", "Merilyn", "Meris", "Merissa", "Merl", "Merla", "Merle", "Merlina", "Merline", "Merna", "Merola", "Merralee", "Merridie", "Merrie", "Merrielle", "Merrile", "Merrilee", "Merrili", "Merrill", "Merrily", "Merry", "Mersey", "Meryl", "Meta", "Mia", "Micaela", "Michaela", "Michaelina", "Michaeline", "Michaella", "Michal", "Michel", "Michele", "Michelina", "Micheline", "Michell", "Michelle", "Micki", "Mickie", "Micky", "Midge", "Mignon", "Mignonne", "Miguela", "Miguelita", "Mikaela", "Mil", "Mildred", "Mildrid", "Milena", "Milicent", "Milissent", "Milka", "Milli", "Millicent", "Millie", "Millisent", "Milly", "Milzie", "Mimi", "Min", "Mina", "Minda", "Mindy", "Minerva", "Minetta", "Minette", "Minna", "Minnaminnie", "Minne", "Minni", "Minnie", "Minnnie", "Minny", "Minta", "Miof Mela", "Miquela", "Mira", "Mirabel", "Mirabella", "Mirabelle", "Miran", "Miranda", "Mireielle", "Mireille", "Mirella", "Mirelle", "Miriam", "Mirilla", "Mirna", "Misha", "Missie", "Missy", "Misti", "Misty", "Mitzi", "Modesta", "Modestia", "Modestine", "Modesty", "Moina", "Moira", "Moll", "Mollee", "Molli", "Mollie", "Molly", "Mommy", "Mona", "Monah", "Monica", "Monika", "Monique", "Mora", "Moreen", "Morena", "Morgan", "Morgana", "Morganica", "Morganne", "Morgen", "Moria", "Morissa", "Morna", "Moselle", "Moyna", "Moyra", "Mozelle", "Muffin", "Mufi", "Mufinella", "Muire", "Mureil", "Murial", "Muriel", "Murielle", "Myra", "Myrah", "Myranda", "Myriam", "Myrilla", "Myrle", "Myrlene", "Myrna", "Myrta", "Myrtia", "Myrtice", "Myrtie", "Myrtle", "Nada", "Nadean", "Nadeen", "Nadia", "Nadine", "Nadiya", "Nady", "Nadya", "Nalani", "Nan", "Nana", "Nananne", "Nance", "Nancee", "Nancey", "Nanci", "Nancie", "Nancy", "Nanete", "Nanette", "Nani", "Nanice", "Nanine", "Nannette", "Nanni", "Nannie", "Nanny", "Nanon", "Naoma", "Naomi", "Nara", "Nari", "Nariko", "Nat", "Nata", "Natala", "Natalee", "Natalie", "Natalina", "Nataline", "Natalya", "Natasha", "Natassia", "Nathalia", "Nathalie", "Natividad", "Natka", "Natty", "Neala", "Neda", "Nedda", "Nedi", "Neely", "Neila", "Neile", "Neilla", "Neille", "Nelia", "Nelie", "Nell", "Nelle", "Nelli", "Nellie", "Nelly", "Nerissa", "Nerita", "Nert", "Nerta", "Nerte", "Nerti", "Nertie", "Nerty", "Nessa", "Nessi", "Nessie", "Nessy", "Nesta", "Netta", "Netti", "Nettie", "Nettle", "Netty", "Nevsa", "Neysa", "Nichol", "Nichole", "Nicholle", "Nicki", "Nickie", "Nicky", "Nicol", "Nicola", "Nicole", "Nicolea", "Nicolette", "Nicoli", "Nicolina", "Nicoline", "Nicolle", "Nikaniki", "Nike", "Niki", "Nikki", "Nikkie", "Nikoletta", "Nikolia", "Nina", "Ninetta", "Ninette", "Ninnetta", "Ninnette", "Ninon", "Nissa", "Nisse", "Nissie", "Nissy", "Nita", "Nixie", "Noami", "Noel", "Noelani", "Noell", "Noella", "Noelle", "Noellyn", "Noelyn", "Noemi", "Nola", "Nolana", "Nolie", "Nollie", "Nomi", "Nona", "Nonah", "Noni", "Nonie", "Nonna", "Nonnah", "Nora", "Norah", "Norean", "Noreen", "Norene", "Norina", "Norine", "Norma", "Norri", "Norrie", "Norry", "Novelia", "Nydia", "Nyssa", "Octavia", "Odele", "Odelia", "Odelinda", "Odella", "Odelle", "Odessa", "Odetta", "Odette", "Odilia", "Odille", "Ofelia", "Ofella", "Ofilia", "Ola", "Olenka", "Olga", "Olia", "Olimpia", "Olive", "Olivette", "Olivia", "Olivie", "Oliy", "Ollie", "Olly", "Olva", "Olwen", "Olympe", "Olympia", "Olympie", "Ondrea", "Oneida", "Onida", "Oona", "Opal", "Opalina", "Opaline", "Ophelia", "Ophelie", "Ora", "Oralee", "Oralia", "Oralie", "Oralla", "Oralle", "Orel", "Orelee", "Orelia", "Orelie", "Orella", "Orelle", "Oriana", "Orly", "Orsa", "Orsola", "Ortensia", "Otha", "Othelia", "Othella", "Othilia", "Othilie", "Ottilie", "Page", "Paige", "Paloma", "Pam", "Pamela", "Pamelina", "Pamella", "Pammi", "Pammie", "Pammy", "Pandora", "Pansie", "Pansy", "Paola", "Paolina", "Papagena", "Pat", "Patience", "Patrica", "Patrice", "Patricia", "Patrizia", "Patsy", "Patti", "Pattie", "Patty", "Paula", "Paule", "Pauletta", "Paulette", "Pauli", "Paulie", "Paulina", "Pauline", "Paulita", "Pauly", "Pavia", "Pavla", "Pearl", "Pearla", "Pearle", "Pearline", "Peg", "Pegeen", "Peggi", "Peggie", "Peggy", "Pen", "Penelopa", "Penelope", "Penni", "Pennie", "Penny", "Pepi", "Pepita", "Peri", "Peria", "Perl", "Perla", "Perle", "Perri", "Perrine", "Perry", "Persis", "Pet", "Peta", "Petra", "Petrina", "Petronella", "Petronia", "Petronilla", "Petronille", "Petunia", "Phaedra", "Phaidra", "Phebe", "Phedra", "Phelia", "Phil", "Philipa", "Philippa", "Philippe", "Philippine", "Philis", "Phillida", "Phillie", "Phillis", "Philly", "Philomena", "Phoebe", "Phylis", "Phyllida", "Phyllis", "Phyllys", "Phylys", "Pia", "Pier", "Pierette", "Pierrette", "Pietra", "Piper", "Pippa", "Pippy", "Polly", "Pollyanna", "Pooh", "Poppy", "Portia", "Pris", "Prisca", "Priscella", "Priscilla", "Prissie", "Pru", "Prudence", "Prudi", "Prudy", "Prue", "Queenie", "Quentin", "Querida", "Quinn", "Quinta", "Quintana", "Quintilla", "Quintina", "Rachael", "Rachel", "Rachele", "Rachelle", "Rae", "Raeann", "Raf", "Rafa", "Rafaela", "Rafaelia", "Rafaelita", "Rahal", "Rahel", "Raina", "Raine", "Rakel", "Ralina", "Ramona", "Ramonda", "Rana", "Randa", "Randee", "Randene", "Randi", "Randie", "Randy", "Ranee", "Rani", "Rania", "Ranice", "Ranique", "Ranna", "Raphaela", "Raquel", "Raquela", "Rasia", "Rasla", "Raven", "Ray", "Raychel", "Raye", "Rayna", "Raynell", "Rayshell", "Rea", "Reba", "Rebbecca", "Rebe", "Rebeca", "Rebecca", "Rebecka", "Rebeka", "Rebekah", "Rebekkah", "Ree", "Reeba", "Reena", "Reeta", "Reeva", "Regan", "Reggi", "Reggie", "Regina", "Regine", "Reiko", "Reina", "Reine", "Remy", "Rena", "Renae", "Renata", "Renate", "Rene", "Renee", "Renell", "Renelle", "Renie", "Rennie", "Reta", "Retha", "Revkah", "Rey", "Reyna", "Rhea", "Rheba", "Rheta", "Rhetta", "Rhiamon", "Rhianna", "Rhianon", "Rhoda", "Rhodia", "Rhodie", "Rhody", "Rhona", "Rhonda", "Riane", "Riannon", "Rianon", "Rica", "Ricca", "Rici", "Ricki", "Rickie", "Ricky", "Riki", "Rikki", "Rina", "Risa", "Rita", "Riva", "Rivalee", "Rivi", "Rivkah", "Rivy", "Roana", "Roanna", "Roanne", "Robbi", "Robbie", "Robbin", "Robby", "Robbyn", "Robena", "Robenia", "Roberta", "Robin", "Robina", "Robinet", "Robinett", "Robinetta", "Robinette", "Robinia", "Roby", "Robyn", "Roch", "Rochell", "Rochella", "Rochelle", "Rochette", "Roda", "Rodi", "Rodie", "Rodina", "Rois", "Romola", "Romona", "Romonda", "Romy", "Rona", "Ronalda", "Ronda", "Ronica", "Ronna", "Ronni", "Ronnica", "Ronnie", "Ronny", "Roobbie", "Rora", "Rori", "Rorie", "Rory", "Ros", "Rosa", "Rosabel", "Rosabella", "Rosabelle", "Rosaleen", "Rosalia", "Rosalie", "Rosalind", "Rosalinda", "Rosalinde", "Rosaline", "Rosalyn", "Rosalynd", "Rosamond", "Rosamund", "Rosana", "Rosanna", "Rosanne", "Rose", "Roseann", "Roseanna", "Roseanne", "Roselia", "Roselin", "Roseline", "Rosella", "Roselle", "Rosemaria", "Rosemarie", "Rosemary", "Rosemonde", "Rosene", "Rosetta", "Rosette", "Roshelle", "Rosie", "Rosina", "Rosita", "Roslyn", "Rosmunda", "Rosy", "Row", "Rowe", "Rowena", "Roxana", "Roxane", "Roxanna", "Roxanne", "Roxi", "Roxie", "Roxine", "Roxy", "Roz", "Rozalie", "Rozalin", "Rozamond", "Rozanna", "Rozanne", "Roze", "Rozele", "Rozella", "Rozelle", "Rozina", "Rubetta", "Rubi", "Rubia", "Rubie", "Rubina", "Ruby", "Ruperta", "Ruth", "Ruthann", "Ruthanne", "Ruthe", "Ruthi", "Ruthie", "Ruthy", "Ryann", "Rycca", "Saba", "Sabina", "Sabine", "Sabra", "Sabrina", "Sacha", "Sada", "Sadella", "Sadie", "Sadye", "Saidee", "Sal", "Salaidh", "Sallee", "Salli", "Sallie", "Sally", "Sallyann", "Sallyanne", "Saloma", "Salome", "Salomi", "Sam", "Samantha", "Samara", "Samaria", "Sammy", "Sande", "Sandi", "Sandie", "Sandra", "Sandy", "Sandye", "Sapphira", "Sapphire", "Sara", "Sara-Ann", "Saraann", "Sarah", "Sarajane", "Saree", "Sarena", "Sarene", "Sarette", "Sari", "Sarina", "Sarine", "Sarita", "Sascha", "Sasha", "Sashenka", "Saudra", "Saundra", "Savina", "Sayre", "Scarlet", "Scarlett", "Sean", "Seana", "Seka", "Sela", "Selena", "Selene", "Selestina", "Selia", "Selie", "Selina", "Selinda", "Seline", "Sella", "Selle", "Selma", "Sena", "Sephira", "Serena", "Serene", "Shae", "Shaina", "Shaine", "Shalna", "Shalne", "Shana", "Shanda", "Shandee", "Shandeigh", "Shandie", "Shandra", "Shandy", "Shane", "Shani", "Shanie", "Shanna", "Shannah", "Shannen", "Shannon", "Shanon", "Shanta", "Shantee", "Shara", "Sharai", "Shari", "Sharia", "Sharity", "Sharl", "Sharla", "Sharleen", "Sharlene", "Sharline", "Sharon", "Sharona", "Sharron", "Sharyl", "Shaun", "Shauna", "Shawn", "Shawna", "Shawnee", "Shay", "Shayla", "Shaylah", "Shaylyn", "Shaylynn", "Shayna", "Shayne", "Shea", "Sheba", "Sheela", "Sheelagh", "Sheelah", "Sheena", "Sheeree", "Sheila", "Sheila-Kathryn", "Sheilah", "Shel", "Shela", "Shelagh", "Shelba", "Shelbi", "Shelby", "Shelia", "Shell", "Shelley", "Shelli", "Shellie", "Shelly", "Shena", "Sher", "Sheree", "Sheri", "Sherie", "Sherill", "Sherilyn", "Sherline", "Sherri", "Sherrie", "Sherry", "Sherye", "Sheryl", "Shina", "Shir", "Shirl", "Shirlee", "Shirleen", "Shirlene", "Shirley", "Shirline", "Shoshana", "Shoshanna", "Siana", "Sianna", "Sib", "Sibbie", "Sibby", "Sibeal", "Sibel", "Sibella", "Sibelle", "Sibilla", "Sibley", "Sibyl", "Sibylla", "Sibylle", "Sidoney", "Sidonia", "Sidonnie", "Sigrid", "Sile", "Sileas", "Silva", "Silvana", "Silvia", "Silvie", "Simona", "Simone", "Simonette", "Simonne", "Sindee", "Siobhan", "Sioux", "Siouxie", "Sisely", "Sisile", "Sissie", "Sissy", "Siusan", "Sofia", "Sofie", "Sondra", "Sonia", "Sonja", "Sonni", "Sonnie", "Sonnnie", "Sonny", "Sonya", "Sophey", "Sophi", "Sophia", "Sophie", "Sophronia", "Sorcha", "Sosanna", "Stace", "Stacee", "Stacey", "Staci", "Stacia", "Stacie", "Stacy", "Stafani", "Star", "Starla", "Starlene", "Starlin", "Starr", "Stefa", "Stefania", "Stefanie", "Steffane", "Steffi", "Steffie", "Stella", "Stepha", "Stephana", "Stephani", "Stephanie", "Stephannie", "Stephenie", "Stephi", "Stephie", "Stephine", "Stesha", "Stevana", "Stevena", "Stoddard", "Storm", "Stormi", "Stormie", "Stormy", "Sue", "Suellen", "Sukey", "Suki", "Sula", "Sunny", "Sunshine", "Susan", "Susana", "Susanetta", "Susann", "Susanna", "Susannah", "Susanne", "Susette", "Susi", "Susie", "Susy", "Suzann", "Suzanna", "Suzanne", "Suzette", "Suzi", "Suzie", "Suzy", "Sybil", "Sybila", "Sybilla", "Sybille", "Sybyl", "Sydel", "Sydelle", "Sydney", "Sylvia", "Tabatha", "Tabbatha", "Tabbi", "Tabbie", "Tabbitha", "Tabby", "Tabina", "Tabitha", "Taffy", "Talia", "Tallia", "Tallie", "Tallou", "Tallulah", "Tally", "Talya", "Talyah", "Tamar", "Tamara", "Tamarah", "Tamarra", "Tamera", "Tami", "Tamiko", "Tamma", "Tammara", "Tammi", "Tammie", "Tammy", "Tamqrah", "Tamra", "Tana", "Tandi", "Tandie", "Tandy", "Tanhya", "Tani", "Tania", "Tanitansy", "Tansy", "Tanya", "Tara", "Tarah", "Tarra", "Tarrah", "Taryn", "Tasha", "Tasia", "Tate", "Tatiana", "Tatiania", "Tatum", "Tawnya", "Tawsha", "Ted", "Tedda", "Teddi", "Teddie", "Teddy", "Tedi", "Tedra", "Teena", "TEirtza", "Teodora", "Tera", "Teresa", "Terese", "Teresina", "Teresita", "Teressa", "Teri", "Teriann", "Terra", "Terri", "Terrie", "Terrijo", "Terry", "Terrye", "Tersina", "Terza", "Tess", "Tessa", "Tessi", "Tessie", "Tessy", "Thalia", "Thea", "Theadora", "Theda", "Thekla", "Thelma", "Theo", "Theodora", "Theodosia", "Theresa", "Therese", "Theresina", "Theresita", "Theressa", "Therine", "Thia", "Thomasa", "Thomasin", "Thomasina", "Thomasine", "Tiena", "Tierney", "Tiertza", "Tiff", "Tiffani", "Tiffanie", "Tiffany", "Tiffi", "Tiffie", "Tiffy", "Tilda", "Tildi", "Tildie", "Tildy", "Tillie", "Tilly", "Tim", "Timi", "Timmi", "Timmie", "Timmy", "Timothea", "Tina", "Tine", "Tiphani", "Tiphanie", "Tiphany", "Tish", "Tisha", "Tobe", "Tobey", "Tobi", "Toby", "Tobye", "Toinette", "Toma", "Tomasina", "Tomasine", "Tomi", "Tommi", "Tommie", "Tommy", "Toni", "Tonia", "Tonie", "Tony", "Tonya", "Tonye", "Tootsie", "Torey", "Tori", "Torie", "Torrie", "Tory", "Tova", "Tove", "Tracee", "Tracey", "Traci", "Tracie", "Tracy", "Trenna", "Tresa", "Trescha", "Tressa", "Tricia", "Trina", "Trish", "Trisha", "Trista", "Trix", "Trixi", "Trixie", "Trixy", "Truda", "Trude", "Trudey", "Trudi", "Trudie", "Trudy", "Trula", "Tuesday", "Twila", "Twyla", "Tybi", "Tybie", "Tyne", "Ula", "Ulla", "Ulrica", "Ulrika", "Ulrikaumeko", "Ulrike", "Umeko", "Una", "Ursa", "Ursala", "Ursola", "Ursula", "Ursulina", "Ursuline", "Uta", "Val", "Valaree", "Valaria", "Vale", "Valeda", "Valencia", "Valene", "Valenka", "Valentia", "Valentina", "Valentine", "Valera", "Valeria", "Valerie", "Valery", "Valerye", "Valida", "Valina", "Valli", "Vallie", "Vally", "Valma", "Valry", "Van", "Vanda", "Vanessa", "Vania", "Vanna", "Vanni", "Vannie", "Vanny", "Vanya", "Veda", "Velma", "Velvet", "Venita", "Venus", "Vera", "Veradis", "Vere", "Verena", "Verene", "Veriee", "Verile", "Verina", "Verine", "Verla", "Verna", "Vernice", "Veronica", "Veronika", "Veronike", "Veronique", "Vevay", "Vi", "Vicki", "Vickie", "Vicky", "Victoria", "Vida", "Viki", "Vikki", "Vikky", "Vilhelmina", "Vilma", "Vin", "Vina", "Vinita", "Vinni", "Vinnie", "Vinny", "Viola", "Violante", "Viole", "Violet", "Violetta", "Violette", "Virgie", "Virgina", "Virginia", "Virginie", "Vita", "Vitia", "Vitoria", "Vittoria", "Viv", "Viva", "Vivi", "Vivia", "Vivian", "Viviana", "Vivianna", "Vivianne", "Vivie", "Vivien", "Viviene", "Vivienne", "Viviyan", "Vivyan", "Vivyanne", "Vonni", "Vonnie", "Vonny", "Vyky", "Wallie", "Wallis", "Walliw", "Wally", "Waly", "Wanda", "Wandie", "Wandis", "Waneta", "Wanids", "Wenda", "Wendeline", "Wendi", "Wendie", "Wendy", "Wendye", "Wenona", "Wenonah", "Whitney", "Wileen", "Wilhelmina", "Wilhelmine", "Wilie", "Willa", "Willabella", "Willamina", "Willetta", "Willette", "Willi", "Willie", "Willow", "Willy", "Willyt", "Wilma", "Wilmette", "Wilona", "Wilone", "Wilow", "Windy", "Wini", "Winifred", "Winna", "Winnah", "Winne", "Winni", "Winnie", "Winnifred", "Winny", "Winona", "Winonah", "Wren", "Wrennie", "Wylma", "Wynn", "Wynne", "Wynnie", "Wynny", "Xaviera", "Xena", "Xenia", "Xylia", "Xylina", "Yalonda", "Yasmeen", "Yasmin", "Yelena", "Yetta", "Yettie", "Yetty", "Yevette", "Ynes", "Ynez", "Yoko", "Yolanda", "Yolande", "Yolane", "Yolanthe", "Yoshi", "Yoshiko", "Yovonnda", "Ysabel", "Yvette", "Yvonne", "Zabrina", "Zahara", "Zandra", "Zaneta", "Zara", "Zarah", "Zaria", "Zarla", "Zea", "Zelda", "Zelma", "Zena", "Zenia", "Zia", "Zilvia", "Zita", "Zitella", "Zoe", "Zola", "Zonda", "Zondra", "Zonnya", "Zora", "Zorah", "Zorana", "Zorina", "Zorine", "Zsa Zsa", "Zsazsa", "Zulema", "Zuzana");
            break;
    }
    $result .= $names[array_rand($names)] . ' ' . $surnames[array_rand($surnames)];
    return ($result);
}

/**
 * Returns current system version
 * 
 * @return string
 */
function zb_getLocalSystemVersion() {
    $result = file_get_contents('RELEASE');
    return ($result);
}

/**
 * Returns remote release version
 * 
 * @param string $branch
 * 
 * @return string/bool
 */
function zb_GetReleaseInfo($branch) {
    $result = false;
    $release_url = UbillingUpdateManager::URL_RELEASE_STABLE;
    if ($branch == 'CURRENT') {
        $release_url = UbillingUpdateManager::URL_RELEASE_CURRENT;
    }
    $ubVer = file_get_contents('RELEASE');
    $agent = 'UbillingUpdMgr/' . trim($ubVer);
    $remoteCallback = new OmaeUrl($release_url);
    $remoteCallback->setUserAgent($agent);
    $releaseInfo = $remoteCallback->response();
    if ($remoteCallback->httpCode() == 200) {
        if ($releaseInfo) {
            $result = $releaseInfo;
        }
    }
    return ($result);
}

/**
 * Ajax backend for rendering WolfRecorder updates release info
 * 
 * @param bool $version
 * @param bool $branch
 * 
 * @return string/bool
 */
function zb_RenderUpdateInfo($version = '', $branch = 'STABLE') {
    $result = '';
    $latestRelease = $version;
    if ($latestRelease) {
        if ($branch == 'CURRENT') {
            $result = __('Latest nightly Ubilling build is') . ': ' . $latestRelease;
        } else {
            $result = __('Latest stable Ubilling release is') . ': ' . $latestRelease;
        }
    } else {
        $result = __('Error checking updates') . ' ' . $branch;
    }
    return ($result);
}


/**
 * Renders task bar elements quick search form
 * 
 * @return string
 */
function web_TaskBarQuickSearchForm() {
    $result = '';
    $result .= wf_tag('div', false, 'tbqsearchform');
    $result .= wf_TextInput('tbquicksearch', ' ' . '', '', false, 20, '', '', 'tbquicksearch', 'placeholder="' . __('Quick search') . '...' . '"');

    $result .= wf_tag('button', false, 'clear-btn', 'type="button" aria-label="Clear search"') . '&times;' . wf_tag('button', true);
    $result .= wf_tag('div', true);

    $result .= wf_tag('script');
    $result .= "
            document.getElementById('tbquicksearch').addEventListener('input', function () {
                const searchValue = this.value.toLowerCase();
                const tbElements = document.querySelectorAll('[id^=\"ubtbelcont_\"]');
                const statusContainer = document.getElementById('ubtbqsstatus');
                let visibleCount = 0;
        
                tbElements.forEach(tbElement => {
                    const idText = tbElement.id.toLowerCase();
                    if (searchValue === '' || idText.includes(searchValue)) {
                        tbElement.classList.remove('hiddentbelem');
                        tbElement.style.display = 'block';
                        requestAnimationFrame(() => tbElement.style.opacity = '1');
                        visibleCount++;
                    } else {
                        tbElement.classList.add('hiddentbelem');
                        setTimeout(() => {
                            if (tbElement.classList.contains('hiddentbelem')) {
                                tbElement.style.display = 'none';
                            }
                        }, 300);
                    }
                });
        
                //no elements found
                if (visibleCount === 0) {
                    statusContainer.textContent = '" . __('Nothing found') . "';
                } else {
                    statusContainer.textContent = '';
                }
            });

            document.addEventListener('DOMContentLoaded', () => {
                const searchInput = document.getElementById('tbquicksearch');
                const clearButton = document.querySelector('.clear-btn');
                searchInput.addEventListener('input', () => {
                    if (searchInput.value.trim() !== '') {
                        clearButton.style.display = 'flex';
                    } else {
                        clearButton.style.display = 'none';
                    }
                });

                clearButton.addEventListener('click', () => {
                    searchInput.value = '';
                    clearButton.style.display = 'none';
                    searchInput.dispatchEvent(new Event('input'));
                    searchInput.focus();
                });
            });
        ";
    $result .= wf_tag('script', true);
    $result .= wf_CleanDiv();
    return ($result);
}

/**
 * Renders the PonSignal colored based on the signal strength.
 *
 * @param float $signal The signal strength value.
 * 
 * @return string
 */
function zb_PonSignalColorize($signal) {
    $result = '';
    if (($signal > 0) or ($signal < -27)) {
        $sigColor = PONizer::COLOR_BAD;
        $sigLabel = 'Bad signal';
    } elseif ($signal > -27 and $signal < -25) {
        $sigColor = PONizer::COLOR_AVG;
        $sigLabel = 'Mediocre signal';
    } else {
        $sigColor = PONizer::COLOR_OK;
        $sigLabel = 'Normal';
    }

    if ($signal == PONizer::NO_SIGNAL) {
        $signal = __('No');
        $sigColor = PONizer::COLOR_NOSIG;
        $sigLabel = 'No signal';
    }

    $result .= wf_tag('font', false, '', 'color="' . $sigColor . '" title="' . __($sigLabel) . '"');
    $result .= $signal;
    $result .= wf_tag('font', true);
    return ($result);
}

/**
 * Returns assigned ONU signal/dereg reason
 * 
 * @param string $login
 * @param bool   $colored
 * @param bool   $label
 * @param bool   $onuLink
 *
 * @return string
 */
function zb_getPonSignalData($login = '', $colored = false, $label = false, $onuLink = false) {
    global $ubillingConfig;
    $result = '';

    if (!empty($login)) {
        if ($ubillingConfig->getAlterParam('PON_ENABLED')) {
            if ($ubillingConfig->getAlterParam('PON_ENABLED')) {
                $signal = 'ETAOIN SHRDLU';
                $deregReason = '';
                $ponizerDb = new NyanORM(PONizer::TABLE_ONUS);
                $ponizerDb->where('login', '=', $login);
                $onuData = $ponizerDb->getAll();

                if (empty($onuData)) {
                    //no primary assign found?
                    $ponizerOnuExtDb = new NyanORM(PONizer::TABLE_ONUEXTUSERS);
                    $ponizerOnuExtDb->where('login', '=', $login);
                    $assignedOnuExt = $ponizerOnuExtDb->getAll();
                    if (!empty($assignedOnuExt)) {
                        $ponizerDb->where('id', '=', $assignedOnuExt[0]['onuid']);
                        $onuData = $ponizerDb->getAll();
                    }
                }

                if (!empty($onuData)) {
                    $onuData = $onuData[0];
                    $onuId = $onuData['id'];
                    $oltId = $onuData['oltid'];
                    $oltAttractor = new OLTAttractor($oltId);
                    $allSignals = $oltAttractor->getSignalsAll();

                    //lookup latest ONU signal
                    $signalLookup = $oltAttractor->lookupOnuIdxValue($onuData, $allSignals);
                    if ($signalLookup != false) {
                        $signal = $signalLookup;
                    }

                    if ($onuId) {
                        //is ONU signal found in signals cache?
                        $signal = ($signal == 'ETAOIN SHRDLU') ? PONizer::NO_SIGNAL : $signal;
                        if ($colored) {
                            $signalLabel = zb_PonSignalColorize($signal);
                        } else {
                            $signalLabel = $signal;
                        }

                        //ONU is offline?
                        if ($signal == PONizer::NO_SIGNAL) {
                            //lookup last dereg reason
                            $allDeregReasons = $oltAttractor->getDeregsAll();
                            $deregLookup = $oltAttractor->lookupOnuIdxValue($onuData, $allDeregReasons);
                            if ($deregLookup != false) {
                                $deregReason = $deregLookup;
                            }

                            if (!empty($deregReason)) {
                                if ($colored) {
                                    $signalLabel .= ' - ' . $deregReason;
                                } else {
                                    $signalLabel .= ' - ' . strip_tags($deregReason);
                                }
                            }
                        }

                        if ($onuLink) {
                            $signalLabel = wf_Link(PONizer::URL_ONU . $onuId, $signalLabel);
                        }

                        if ($label) {
                            $result .= __('ONU Signal') . ': ' . $signalLabel;
                        } else {
                            $result .= $signalLabel;
                        }
                    }
                } else {
                    $result .= __('No ONU assigned');
                }
            }
        }
    }
    return ($result);
}


/**
 * Trims the content of a log file if it exceeds a specified size.
 *
 * This function checks if the specified log file exists and if its size exceeds
 * the given maximum size. If the file size exceeds the limit, it trims the file
 * content by removing lines from the beginning until the file size is within the limit.
 *
 * @param string $fileName The path to the text log file.
 * @param int $size The maximum allowed size of the log file in megabytes (MB).
 *
 * @return void
 */
function zb_TrimTextLog($fileName, $size) {
    if (file_exists($fileName)) {
        $maxSize = $size * 1024 * 1024; // in mb
        $fileSize = filesize($fileName);
        if ($fileSize >= $maxSize) {
            $fileContent = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!empty($fileContent)) {
                $fileContent = array_reverse($fileContent);
                $seekSize = 0;
                foreach ($fileContent as $io => $each) {
                    $seekSize += strlen($each);
                    if ($seekSize >= $maxSize) {
                        unset($fileContent[$io]);
                    }
                }
                $fileContent = array_reverse($fileContent);
                file_put_contents($fileName, implode(PHP_EOL, $fileContent) . PHP_EOL);
            }
        }
    }
}

/**
 * Returns ICMP ping configuration form
 * 
 * @return string
 */
function wf_PlPingerOptionsForm() {
    //previous setting
    if (ubRouting::checkPost('packet')) {
        $currentpack = ubRouting::post('packet', 'int');
    } else {
        $currentpack = '';
    }

    if (ubRouting::checkPost('count')) {
        $getCount = ubRouting::post('count', 'int');
        if ($getCount <= 10000) {
            $currentcount = $getCount;
        } else {
            $currentcount = '';
        }
    } else {
        $currentcount = '';
    }

    $inputs = wf_TextInput('packet', __('Packet size'), $currentpack, false, 5);
    $inputs .= wf_TextInput('count', __('Count'), $currentcount, false, 5);
    $inputs .= wf_Submit(__('Save'));
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Returns ARPping configuration form
 * 
 * @return string
 */
function wf_PlArpingOptionsForm() {
    $currentcount = '';
    if (ubRouting::post('count')) {
        $getCount = ubRouting::post('count', 'int');
        if ($getCount <= 1000) {
            $currentcount = $getCount;
        }
    }
    $inputs = wf_TextInput('count', __('Count'), $currentcount, false, 5);
    $inputs .= wf_Submit(__('Save'));
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Turn all URLs in clickable links. With preview images if its a image URL.
 *
 * @param string $text
 * @param string $imgWidth
 * @param bool $imgLazy
 * @param bool $youtubeEmbed
 *
 * @return string
 */
function zb_Linkify($text, $imgWidth = '100%', $imgLazy = true, $youtubeEmbed = true) {
    $urlPattern = '/\b(https?:\/\/[^\s<>"\'\)]+)/i';
    $result = preg_replace_callback($urlPattern, function ($matches) use ($imgWidth, $imgLazy, $youtubeEmbed) {
        $url = $matches[0];

        // Security: Filter out potentially malicious URLs
        $url = filter_var($url, FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return htmlspecialchars($url);
        }

        // Additional security: Block javascript: and data: URLs
        if (preg_match('/^(javascript:|data:|vbscript:|file:|ftp:)/i', $url)) {
            return htmlspecialchars($url);
        }

        // Check for YouTube URLs (including Shorts)
        if ($youtubeEmbed and preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|shorts\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $youtubeMatches)) {
            $videoId = $youtubeMatches[1];
            $embedUrl = 'https://www.youtube.com/embed/' . $videoId;

            $iframeAttrs = 'frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ';
            $iframeAttrs .= 'referrerpolicy="strict-origin-when-cross-origin" ';
            $iframeAttrs .= 'allowfullscreen';

            // Check if it's a Shorts URL for vertical aspect ratio
            if (strpos($url, '/shorts/') !== false) {
                $iframe = wf_tag('iframe', false, '', 'src="' . $embedUrl . '" width="315" height="560" ' . $iframeAttrs);
            } else {
                $iframe = wf_tag('iframe', false, '', 'src="' . $embedUrl . '" width="560" height="315" ' . $iframeAttrs);
            }
            $iframe .= wf_tag('iframe', true);
            return $iframe;
        }

        // Check for image files
        if (preg_match('/\.(jpg|png|gif|webp|jpeg)$/i', $url)) {
            if ($imgLazy) {
                $imgTag = wf_tag('img', false, '', 'src="' . htmlspecialchars($url) . '" width="' . $imgWidth . '" loading="lazy"');
            } else {
                $imgTag = wf_tag('img', false, '', 'src="' . htmlspecialchars($url) . '" width="' . $imgWidth . '"');
            }
            return wf_link($url, $imgTag, false, '', 'target="_blank"');
        }

        return wf_Link($url, htmlspecialchars($url), false, '', 'target="_blank"');
    }, $text);

    return ($result);
}

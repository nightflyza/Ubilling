<?php

/**
 * Creates new contrahent in database
 * 
 * @param string $bankacc
 * @param string $bankname
 * @param string $bankcode
 * @param string $edrpo
 * @param string $ipn
 * @param string $licensenum
 * @param string $juraddr
 * @param string $phisaddr
 * @param string $phone
 * @param string $contrname
 * 
 * @return void
 */
function zb_ContrAhentAdd($bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname) {
    $bankacc = mysql_real_escape_string($bankacc);
    $bankname = mysql_real_escape_string($bankname);
    $bankcode = mysql_real_escape_string($bankcode);
    $edrpo = mysql_real_escape_string($edrpo);
    $ipn = mysql_real_escape_string($ipn);
    $licensenum = mysql_real_escape_string($licensenum);
    $juraddr = mysql_real_escape_string($juraddr);
    $phisaddr = mysql_real_escape_string($phisaddr);
    $phone = mysql_real_escape_string($phone);
    $contrname = mysql_real_escape_string($contrname);
    $query = "INSERT INTO `contrahens` (`id` ,`bankacc` ,`bankname` , `bankcode` , `edrpo` , `ipn` , `licensenum` , `juraddr` , `phisaddr` , `phone` ,`contrname`)
        VALUES (NULL , '" . $bankacc . "', '" . $bankname . "', '" . $bankcode . "', '" . $edrpo . "', '" . $ipn . "', '" . $licensenum . "','" . $juraddr . "', '" . $phisaddr . "','" . $phone . "','" . $contrname . "');";
    nr_query($query);
    log_register("AGENT CREATE `" . $contrname . "`");
}

/**
 * Changes existing contrahent record in database
 * 
 * @param int $ahentid
 * @param string $bankacc
 * @param string $bankname
 * @param string $bankcode
 * @param string $edrpo
 * @param string $ipn
 * @param string $licensenum
 * @param string $juraddr
 * @param string $phisaddr
 * @param string $phone
 * @param string $contrname
 * 
 * @return void
 */
function zb_ContrAhentChange($ahentid, $bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname) {
    $ahentid = vf($ahentid, 3);
    $bankacc = mysql_real_escape_string($bankacc);
    $bankname = mysql_real_escape_string($bankname);
    $bankcode = mysql_real_escape_string($bankcode);
    $edrpo = mysql_real_escape_string($edrpo);
    $ipn = mysql_real_escape_string($ipn);
    $licensenum = mysql_real_escape_string($licensenum);
    $juraddr = mysql_real_escape_string($juraddr);
    $phisaddr = mysql_real_escape_string($phisaddr);
    $phone = mysql_real_escape_string($phone);
    $contrname = mysql_real_escape_string($contrname);
    $query = "UPDATE `contrahens` SET 
        `bankacc` = '" . $bankacc . "',
        `bankname` = '" . $bankname . "',
        `bankcode` = '" . $bankcode . "',
        `edrpo` = '" . $edrpo . "',
        `ipn` = '" . $ipn . "',
        `licensenum` = '" . $licensenum . "',
        `juraddr` = '" . $juraddr . "',
        `phisaddr` = '" . $phisaddr . "',
        `phone` = '" . $phone . "',
        `contrname` = '" . $contrname . "'
          WHERE `contrahens`.`id` =" . $ahentid . " LIMIT 1;";
    nr_query($query);
    log_register("AGENT CHANGE `" . $contrname . "`");
}

/**
 * Deletes existing contrahent from database
 * 
 * @param int $id
 * 
 * @return void
 */
function zb_ContrAhentDelete($id) {
    $id = vf($id, 3);
    $query = "DELETE from `contrahens` where `id`='" . $id . "'";
    nr_query($query);
    log_register("AGENT DELETE [" . $id . "]");
}

/**
 * Returns contrahent data as array
 * 
 * @param int $id
 * 
 * @return array
 */
function zb_ContrAhentGetData($id) {
    $id = vf($id);
    $query = "SELECT * from `contrahens` WHERE `id`='" . $id . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Returns full contrahent data array
 * 
 * @return array
 */
function zb_ContrAhentGetAllData() {
    $query = "SELECT * from `contrahens`";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Renders contrahents list with required controls
 * 
 * @return string
 */
function zb_ContrAhentShow() {
    $allcontr = zb_ContrAhentGetAllData();

    // construct needed editor
    $titles = array(
        'ID',
        'Bank account',
        'Bank name',
        'Bank code',
        'EDRPOU',
        'IPN',
        'License number',
        'Juridical address',
        'Phisical address',
        'Phone',
        'Contrahent name',
    );
    $keys = array(
        'id',
        'bankacc',
        'bankname',
        'bankcode',
        'edrpo',
        'ipn',
        'licensenum',
        'juraddr',
        'phisaddr',
        'phone',
        'contrname'
    );
    $result = web_GridEditor($titles, $keys, $allcontr, 'contrahens', true, true);
    return($result);
}

/**
 * Renders contrahent creation form
 * 
 * @return string
 */
function zb_ContrAhentAddForm() {
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = '';
    $inputs.= wf_TextInput('newcontrname', __('Contrahent name') . $sup, '', true);
    $inputs.= wf_TextInput('newbankacc', __('Bank account'), '', true);
    $inputs.= wf_TextInput('newbankname', __('Bank name'), '', true);
    $inputs.= wf_TextInput('newbankcode', __('Bank code'), '', true);
    $inputs.= wf_TextInput('newedrpo', __('EDRPOU'), '', true);
    $inputs.= wf_TextInput('newipn', __('IPN'), '', true);
    $inputs.= wf_TextInput('newlicensenum', __('License number'), '', true);
    $inputs.= wf_TextInput('newjuraddr', __('Juridical address'), '', true);
    $inputs.= wf_TextInput('newphisaddr', __('Phisical address'), '', true);
    $inputs.= wf_TextInput('newphone', __('Phone'), '', true);
    $inputs.= wf_Submit(__('Create'));

    $result = wf_Form("", 'POST', $inputs, 'glamour');

    return($result);
}

/**
 * Renders existing ahent editing form
 * 
 * @param int $ahentid
 * 
 * @return string
 */
function zb_ContrAhentEditForm($ahentid) {
    $ahentid = vf($ahentid, 3);
    $cdata = zb_ContrAhentGetData($ahentid);
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = '';
    $inputs.= wf_TextInput('changecontrname', __('Contrahent name') . $sup, $cdata['contrname'], true);
    $inputs.= wf_TextInput('changebankacc', __('Bank account'), $cdata['bankacc'], true);
    $inputs.= wf_TextInput('changebankname', __('Bank name'), $cdata['bankname'], true);
    $inputs.= wf_TextInput('changebankcode', __('Bank code'), $cdata['bankcode'], true);
    $inputs.= wf_TextInput('changeedrpo', __('EDRPOU'), $cdata['edrpo'], true);
    $inputs.= wf_TextInput('changeipn', __('IPN'), $cdata['ipn'], true);
    $inputs.= wf_TextInput('changelicensenum', __('License number'), $cdata['licensenum'], true);
    $inputs.= wf_TextInput('changejuraddr', __('Juridical address'), $cdata['juraddr'], true);
    $inputs.= wf_TextInput('changephisaddr', __('Phisical address'), $cdata['phisaddr'], true);
    $inputs.= wf_TextInput('changephone', __('Phone'), $cdata['phone'], true);

    $inputs.= wf_Submit(__('Save'));
    $result = wf_Form("", 'POST', $inputs, 'glamour');

    return ($result);
}

/**
 * Returns ahent selector widget
 * 
 * @return string
 */
function zb_ContrAhentSelect() {
    $allagents = zb_ContrAhentGetAllData();
    $params = array();
    if (!empty($allagents)) {
        foreach ($allagents as $io => $eachagent) {
            $params[$eachagent['id']] = $eachagent['contrname'];
        }
    }

    $result = wf_Selector('ahentsel', $params, __('Contrahent name'), false, false);
    return ($result);
}

/**
 * Returns agent selector with preset agent ID
 * 
 * @param int $currentId
 * @return string
 */
function zb_ContrAhentSelectPreset($currentId = '') {
    $allagents = zb_ContrAhentGetAllData();
    $tmpArr = array();
    if (!empty($allagents)) {
        foreach ($allagents as $io => $eachagent) {
            $tmpArr[$eachagent['id']] = $eachagent['contrname'];
        }
    }
    $select = wf_Selector('ahentsel', $tmpArr, '', $currentId, false);
    return($select);
}

/**
 * Returns array of all agent=>street assigns 
 * 
 * @return array
 */
function zb_AgentAssignGetAllData() {
    $query = "SELECT * from `ahenassign`";
    $allassigns = simple_queryall($query);
    return($allassigns);
}

/**
 * Returns array of existing agent strict assigns as login=>agentid
 * 
 * @return array
 */
function zb_AgentAssignStrictGetAllData() {
    $result = array();
    $query = "SELECT * from `ahenassignstrict`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['agentid'];
        }
    }
    return ($result);
}

/**
 * Deletes existing agent assign database record
 * 
 * @param int $id
 * 
 * @return void
 */
function zb_AgentAssignDelete($id) {
    $id = vf($id, 3);
    $query = "DELETE from `ahenassign` where `id`='" . $id . "'";
    nr_query($query);
    log_register("AGENTASSIGN DELETE [" . $id . "]");
}

/**
 * Creates new agent=>street assign in database
 * 
 * @param int $ahenid
 * @param string $streetname
 * 
 * @return void
 */
function zb_AgentAssignAdd($ahenid, $streetname) {
    $ahenid = vf($ahenid, 3);
    $streetname = mysql_real_escape_string($streetname);
    $query = "INSERT INTO `ahenassign` ( `id` , `ahenid` ,`streetname`) VALUES (NULL , '" . $ahenid . "', '" . $streetname . "');";
    nr_query($query);
    log_register("AGENTASSIGN CREATE [" . $ahenid . '] `' . $streetname . '`');
}

/**
 * Renders ahent assign form
 * 
 * @return string
 */
function web_AgentAssignForm() {
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = zb_ContrAhentSelect();
    $inputs.= wf_tag('br');
    $inputs.= wf_TextInput('newassign', __('Street name') . $sup, '', true);
    $inputs.= wf_Submit(__('Save'));
    $result = wf_Form("", 'POST', $inputs, 'glamour');

    return($result);
}

/**
 * Renders list of available ahent assigns with required controls
 * 
 * @return string
 */
function web_AgentAssignShow() {
    $allassigns = zb_AgentAssignGetAllData();
    $allahens = zb_ContrAhentGetAllData();
    $agentnames = array();
    if (!empty($allahens)) {
        foreach ($allahens as $io => $eachahen) {
            $agentnames[$eachahen['id']] = $eachahen['contrname'];
        }
    }

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Contrahent name'));
    $cells.= wf_TableCell(__('Street name'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allassigns)) {
        foreach ($allassigns as $io2 => $eachassign) {

            $cells = wf_TableCell($eachassign['id']);
            $cells.= wf_TableCell(@$agentnames[$eachassign['ahenid']]);
            $cells.= wf_TableCell($eachassign['streetname']);
            $actLinks = wf_JSAlert('?module=contrahens&deleteassign=' . $eachassign['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
            $cells.= wf_TableCell($actLinks);
            $rows.= wf_TableRow($cells, 'row5');
        }
    }
    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    return($result);
}

/**
 * Renders list of strict login=>agent assigns with some controls
 * 
 * @return string
 */
function web_AgentAssignStrictShow() {
    $allassigns = zb_AgentAssignStrictGetAllData();
    $allahens = zb_ContrAhentGetAllData();
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslistCached();
    $allusertariffs = zb_TariffsGetAllUsers();

    $agentnames = array();
    if (!empty($allahens)) {
        foreach ($allahens as $io => $eachahen) {
            $agentnames[$eachahen['id']] = $eachahen['contrname'];
        }
    }


    $cells = wf_TableCell(__('Login'));
    $cells.= wf_TableCell(__('Full address'));
    $cells.= wf_TableCell(__('Real Name'));
    $cells.= wf_TableCell(__('Tariff'));
    $cells.= wf_TableCell(__('Contrahent name'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allassigns)) {
        foreach ($allassigns as $eachlogin => $eachagent) {
            $loginLink = wf_Link('?module=userprofile&username=' . $eachlogin, web_profile_icon() . ' ' . $eachlogin, false, '');
            $cells = wf_TableCell($loginLink);
            $cells.= wf_TableCell(@$alladdress[$eachlogin]);
            $cells.= wf_TableCell(@$allrealnames[$eachlogin]);
            $cells.= wf_TableCell(@$allusertariffs[$eachlogin]);
            $cells.= wf_TableCell(@$agentnames[$eachagent]);
            $actLinks = wf_JSAlert('?module=contractedit&username=' . $eachlogin, web_edit_icon(), __('Are you serious'));
            $cells.= wf_TableCell($actLinks);
            $rows.= wf_TableRow($cells, 'row5');
        }
    }
    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    return($result);
}

/**
 * Returns agent id or false for user ahent assign check
 * 
 * @param string $login
 * @param array $allassigns
 * @param array $alladdress
 * @return int/bool
 */
function zb_AgentAssignCheckLogin($login, $allassigns, $alladdress) {
    $alter_cfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $result = false;
    // если пользователь куда-то заселен
    if (isset($alladdress[$login])) {
        // возвращаем дефолтного агента если присваиваний нет вообще
        if (empty($allassigns)) {
            $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
        } else {
            //если какие-то присваивалки есть
            $useraddress = $alladdress[$login];
            // проверяем для каждой присваивалки попадает ли она под нашего абонента
            foreach ($allassigns as $io => $eachassign) {
                if (strpos($useraddress, $eachassign['streetname']) !== false) {
                    $result = $eachassign['ahenid'];
                } else {
                    // и если не нашли - возвращаем  умолчательного
                    $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
                }
            }
        }
    }
    // если присваивание выключено возвращаем умолчального
    if (!$alter_cfg['AGENTS_ASSIGN']) {
        $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
    }
    return($result);
}

/**
 * Performs fast agent assing check for some user login
 * 
 * @global object $ubillingConfig
 * @param string $login
 * @param array $allassigns
 * @param string $address
 * @param array $allassignsstrict
 * @return array
 */
function zb_AgentAssignCheckLoginFast($login, $allassigns, $address, $allassignsstrict) {
    global $ubillingConfig;
    $alter_cfg = $ubillingConfig->getAlter();
    $result = array();
    //быстренько проверяем нету ли принудительной привязки по логину
    if (isset($allassignsstrict[$login])) {
        $result = $allassignsstrict[$login];
        return ($result);
    }


    // если пользователь куда-то заселен
    if (!empty($address)) {
        // возвращаем дефолтного агента если присваиваний нет вообще
        if (empty($allassigns)) {
            $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
        } else {
            //если какие-то присваивалки есть
            $useraddress = $address;

            // проверяем для каждой присваивалки попадает ли она под нашего абонента
            foreach ($allassigns as $io => $eachassign) {
                if (strpos($useraddress, $eachassign['streetname']) !== false) {
                    $result = $eachassign['ahenid'];
                    break;
                } else {
                    // и если не нашли - возвращаем  умолчательного
                    $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
                }
            }
        }
    } else {
        //если пользователь бомжует - возвращаем тоже умолчательного
        $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
    }
    // если присваивание выключено возвращаем умолчального
    if (!$alter_cfg['AGENTS_ASSIGN']) {
        $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
    }

    return($result);
}

/**
 * Returns content of export template
 * 
 * @param string $filename
 * @return string
 */
function zb_ExportLoadTemplate($filename) {
    $template = file_get_contents($filename);
    return($template);
}

/**
 * Returns array of all users tariffs array as login=>tariff
 * 
 * @return array
 */
function zb_ExportTariffsLoadAll() {
    $allstgdata = zb_UserGetAllStargazerData();
    $result = array();
    if (!empty($allstgdata)) {
        foreach ($allstgdata as $io => $eachuser) {
            $result[$eachuser['login']] = $eachuser['Tariff'];
        }
    }
    return($result);
}

/**
 * Returns array of all users contract data 
 * 
 * @return array
 */
function zb_ExportContractsLoadAll() {
    $query = "SELECT `login`,`contract` from `contracts`";
    $allcontracts = simple_queryall($query);
    $queryDates = "SELECT `contract`,`date` from `contractdates`";
    $alldates = simple_queryall($queryDates);
    $result = array();
    $dates = array();
    if (!empty($alldates)) {
        foreach ($alldates as $ia => $eachdate) {
            $dates[$eachdate['contract']] = $eachdate['date'];
        }
    }

    if (!empty($allcontracts)) {
        foreach ($allcontracts as $io => $eachcontract) {
            $result[$eachcontract['login']]['contractnum'] = $eachcontract['contract'];
            if (isset($dates[$eachcontract['contract']])) {
                $rawdate = $dates[$eachcontract['contract']];
                $timestamp = strtotime($rawdate);
                $newDate = date("Y-m-d\T00:00:00", $timestamp);
                $result[$eachcontract['login']]['contractdate'] = $newDate;
            } else {
                $result[$eachcontract['login']]['contractdate'] = '1970-01-01T00:00:00';
            }
        }
    }

    return($result);
}

/**
 * Performs export template processing with some userdata
 * 
 * @param string $templatebody
 * @param array $templatedata
 * 
 * @return string
 */
function zb_ExportParseTemplate($templatebody, $templatedata) {
    foreach ($templatedata as $field => $data) {
        $templatebody = str_ireplace($field, $data, $templatebody);
    }
    return($templatebody);
}

/**
 * Returns array of all ahent data for exports processing
 * 
 * @return array
 */
function zb_ExportAgentsLoadAll() {
    $allagents = zb_ContrAhentGetAllData();
    $result = array();
    if (!empty($allagents)) {
        foreach ($allagents as $io => $eachagent) {
            $result[$eachagent['id']]['contrname'] = $eachagent['contrname'];
            @$result[$eachagent['id']]['edrpo'] = $eachagent['edrpo'];
            @$result[$eachagent['id']]['bankacc'] = $eachagent['bankacc'];
        }
        return($result);
    }
}

/**
 * Renders export form body
 * 
 * @return string
 */
function zb_ExportForm() {
    $curdate = curdate();
    $yesterday = date("Y-m-d", time() - 86400);

    $inputs = __('From');
    $inputs.= wf_DatePickerPreset('fromdate', $yesterday);
    $inputs.=__('To');
    $inputs.=wf_DatePickerPreset('todate', $curdate);
    $inputs.=wf_Submit('Export');
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    return($form);
}

/**
 * Performs payments export between two dates
 * 
 * @param string $from_date
 * @param string $to_date
 * 
 * @return string
 */
function zb_ExportPayments($from_date, $to_date) {
    // reading export options
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $default_assign_agent = $alter_conf['DEFAULT_ASSIGN_AGENT'];
    $export_template = $alter_conf['EXPORT_TEMPLATE'];
    $export_template_head = $alter_conf['EXPORT_TEMPLATE_HEAD'];
    $export_template_end = $alter_conf['EXPORT_TEMPLATE_END'];
    $export_only_positive = $alter_conf['EXPORT_ONLY_POSITIVE'];
    $export_format = $alter_conf['EXPORT_FORMAT'];
    $export_encoding = $alter_conf['EXPORT_ENCODING'];
    $import_encoding = $alter_conf['IMPORT_ENCODING'];
    $export_from_time = $alter_conf['EXPORT_FROM_TIME'];
    $export_to_time = $alter_conf['EXPORT_TO_TIME'];
    $citydisplay = $alter_conf['CITY_DISPLAY'];
    if ($citydisplay) {
        $address_offset = 1;
    } else {
        $address_offset = 0;
    }

    // loading templates
    $template_head = zb_ExportLoadTemplate($export_template_head);
    $template = zb_ExportLoadTemplate($export_template);
    $template_end = zb_ExportLoadTemplate($export_template_end);

    // load all needed data
    $allassigns = zb_AgentAssignGetAllData();
    $alladdress = zb_AddressGetFulladdresslist();
    $allrealnames = zb_UserGetAllRealnames();
    $allagentsdata = zb_ExportAgentsLoadAll();
    $allcontracts = zb_ExportContractsLoadAll();
    $alltariffs = zb_ExportTariffsLoadAll();
    //main code
    $qfrom_date = $from_date . ' ' . $export_from_time;
    $qto_date = $to_date . ' ' . $export_to_time;
    $query = "SELECT * from `payments` WHERE `date` >= '" . $qfrom_date . "' AND `date`<= '" . $qto_date . "'";
    $allpayments = simple_queryall($query);
    $parse_data = array();
    $parse_data['{FROMDATE}'] = $from_date;
    $parse_data['{FROMTIME}'] = $export_from_time;
    $parse_data['{TODATE}'] = $to_date;
    $parse_data['{TOTIME}'] = $export_to_time;
    $export_result = zb_ExportParseTemplate($template_head, $parse_data);
    if (!empty($allpayments)) {
        foreach ($allpayments as $io => $eachpayment) {
            // forming export data
            $paylogin = $eachpayment['login'];
            @$payrealname = $allrealnames[$eachpayment['login']];
            $payid = $eachpayment['id'];
            $paytariff = @$alltariffs[$paylogin];
            $paycontractdata = $allcontracts[$paylogin];
            $paycontract = $paycontractdata['contractnum'];
            $paycontractdate = $paycontractdata['contractdate'];
            $paycity = 'debug city';
            $payregion = 'debug region';
            $paydrfo = '';
            $payjurface = 'false';
            $paydatetime = $eachpayment['date'];
            $paysumm = $eachpayment['summ'];
            $paynote = $eachpayment['note'];
            $paytimesplit = explode(' ', $paydatetime);
            $paydate = $paytimesplit[0];
            $paytime = $paytimesplit[1];
            @$payaddr = $alladdress[$paylogin];
            @$splitaddr = explode(' ', $payaddr);
            @$paystreet = $splitaddr[0 + $address_offset];
            @$splitbuild = explode('/', $splitaddr[1 + $address_offset]);
            @$paybuild = $splitbuild[0];
            @$payapt = $splitbuild[1];
            $agent_assigned = zb_AgentAssignCheckLogin($paylogin, $allassigns, $alladdress);
            @$agent_bankacc = $allagentsdata[$agent_assigned]['bankacc'];
            @$agent_edrpo = $allagentsdata[$agent_assigned]['edrpo'];
            @$agent_name = $allagentsdata[$agent_assigned]['contrname'];
            // construct template data

            $parse_data['{PAYID}'] = md5($payid);
            $parse_data['{AGENTNAME}'] = $agent_name;
            $parse_data['{AGENTEDRPO}'] = $agent_edrpo;
            $parse_data['{PAYDATE}'] = $paydate;
            $parse_data['{PAYTIME}'] = $paytime;
            $parse_data['{PAYSUMM}'] = $paysumm;
            $parse_data['{CONTRACT}'] = $paycontract;
            $parse_data['{CONTRACTDATE}'] = $paycontractdate;
            $parse_data['{REALNAME}'] = $payrealname;
            $parse_data['{DRFO}'] = $paydrfo;
            $parse_data['{JURFACE}'] = $payjurface;
            $parse_data['{STREET}'] = $paystreet;
            $parse_data['{BUILD}'] = $paybuild;
            $parse_data['{APT}'] = $payapt;
            $parse_data['{NOTE}'] = $paynote;
            $parse_data['{CITY}'] = $paycity;
            $parse_data['{REGION}'] = $payregion;
            $parse_data['{TARIFF}'] = $paytariff;

            // custom positive payments export
            if ($export_only_positive) {
                // check is that pos payment
                if ($paysumm > 0) {
                    $export_result.=zb_ExportParseTemplate($template, $parse_data);
                }
            } else {
                //or anyway export it
                $export_result.=zb_ExportParseTemplate($template, $parse_data);
            }
        }
    }
    $export_result.=zb_ExportParseTemplate($template_end, $parse_data);

    if ($import_encoding != $export_encoding) {
        $export_result = iconv($import_encoding, $export_encoding, $export_result);
    }
    return($export_result);
}

/**
 * Returns array of ahent data by the users login
 * 
 * @param string $login
 * @return array
 */
function zb_AgentAssignedGetData($login) {
    $login = vf($login);
    $allassigns = zb_AgentAssignGetAllData();
    $alladdress = zb_AddressGetFulladdresslist();
    $assigned_agent = zb_AgentAssignCheckLogin($login, $allassigns, $alladdress);
    $result = zb_ContrAhentGetData($assigned_agent);
    return($result);
}

/**
 * Returns array of ahent data associated with some user by login/address pair
 * 
 * @param string $login
 * @param string $address
 * @return array
 */
function zb_AgentAssignedGetDataFast($login, $address) {
    $allassigns = zb_AgentAssignGetAllData();
    $allassignsStrict = zb_AgentAssignStrictGetAllData();
    $assigned_agent = zb_AgentAssignCheckLoginFast($login, $allassigns, $address, $allassignsStrict);
    $result = zb_ContrAhentGetData($assigned_agent);
    return($result);
}

/**
 * Returns array of payment data by its ID
 * 
 * @param int $paymentid
 * @return array
 */
function zb_PaymentGetData($paymentid) {
    $paymentid = vf($paymentid, 3);
    $result = array();
    $query = "SELECT * from `payments` WHERE `id`='" . $paymentid . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Returns content of sales slip HTML template
 * 
 * @return string
 */
function zb_PrintCheckLoadTemplate() {
    $template = file_get_contents(CONFIG_PATH . 'printcheck.tpl');
    return($template);
}

/**
 * Returns some cashiers data (deprecated)
 * 
 * @param string $whoami
 * 
 * @return array/string
 */
function zb_PrintCheckLoadCassNames($whoami = false) {
    $names = rcms_parse_ini_file(CONFIG_PATH . 'cass.ini');
    if (empty($whoami)) {
        return $names;
    } else {
        $iam = whoami();
        return $names[$iam];
    }
}

/**
 * Returns payment number per day
 * 
 * @param int $payid
 * @param string $paymentdate
 * 
 * @return string
 */
function zb_PrintCheckGetDayNum($payid, $paymentdate) {
    $payid = vf($payid, 3);

    $result = 'EXCEPTION';
    $onlyday = $paymentdate;
    $onlyday = strtotime($onlyday);
    $onlyday = date("Y-m-d", $onlyday);
    $date_q = "SELECT `id` from `payments` where `date` LIKE '" . $onlyday . "%' ORDER BY `id` ASC LIMIT 1;";
    $firstbyday = simple_query($date_q);

    if (!empty($firstbyday)) {
        $firstbyday = $firstbyday['id'];
        $currentnumber = $payid - $firstbyday;
        $currentnumber = $currentnumber + 1;
        $result = $currentnumber;
    }
    return ($result);
}

/**
 * Renders printable HTML sales slip
 * 
 * @param int $paymentid
 * @return string
 */
function zb_PrintCheck($paymentid, $realpaymentId = false) {
    $paymentdata = zb_PaymentGetData($paymentid);
    $login = $paymentdata['login'];
    $userData = zb_UserGetAllData($login);
    $userData = $userData[$login];
    $templatebody = zb_PrintCheckLoadTemplate();
    $alladdress = zb_AddressGetFullCityaddresslist();
    $useraddress = $alladdress[$login];

    $agent_data = zb_AgentAssignedGetDataFast($login, $useraddress);
    $cassnames = zb_PrintCheckLoadCassNames();
    if ($realpaymentId) {
        $userPaymentId = zb_PaymentIDGet($login);
    } else {
        $userPaymentId = ip2int($userData['ip']);
    }
    $cday = date("d");
    $cmonth = date("m");
    $month_array = months_array();
    $cmonth_name = $month_array[$cmonth];
    $cyear = curyear();
    $morph = new UBMorph();
    //forming template data
    @$templatedata['{PAYID}'] = $paymentdata['id'];
    @$templatedata['{PAYIDENC}'] = zb_NumEncode($paymentdata['id']);
    @$templatedata['{PAYMENTID}'] = $userPaymentId;
    @$templatedata['{PAYDATE}'] = $paymentdata['date'];
    @$templatedata['{PAYSUMM}'] = $paymentdata['summ'];
    @$templatedata['{PAYSUMM_LIT}'] = $morph->sum2str($paymentdata['summ']); // omg omg omg 
    @$templatedata['{REALNAME}'] = $userData['realname'];
    @$templatedata['{BUHNAME}'] = 'а відки я знаю?';
    @$templatedata['{CASNAME}'] = $cassnames[whoami()];
    @$templatedata['{PAYTARGET}'] = 'Оплата за послуги / ' . $paymentdata['date'];
    @$templatedata['{FULLADDRESS}'] = $useraddress;
    @$templatedata['{CDAY}'] = $cday;
    @$templatedata['{CMONTH}'] = rcms_date_localise($cmonth_name);
    @$templatedata['{CYEAR}'] = $cyear;
    @$templatedata['{DAYPAYID}'] = zb_PrintCheckGetDayNum($paymentdata['id'], $paymentdata['date']);
    //contragent full data
    @$templatedata['{AGENTEDRPO}'] = $agent_data['edrpo'];
    @$templatedata['{AGENTNAME}'] = $agent_data['contrname'];
    @$templatedata['{AGENTID}'] = $agent_data['id'];
    @$templatedata['{AGENTBANKACC}'] = $agent_data['bankacc'];
    @$templatedata['{AGENTBANKNAME}'] = $agent_data['bankname'];
    @$templatedata['{AGENTBANKCODE}'] = $agent_data['bankcode'];
    @$templatedata['{AGENTIPN}'] = $agent_data['ipn'];
    @$templatedata['{AGENTLICENSE}'] = $agent_data['licensenum'];
    @$templatedata['{AGENTJURADDR}'] = $agent_data['juraddr'];
    @$templatedata['{AGENTPHISADDR}'] = $agent_data['phisaddr'];
    @$templatedata['{AGENTPHONE}'] = $agent_data['phone'];


    //parsing result
    $result = zb_ExportParseTemplate($templatebody, $templatedata);
    return($result);
}

/**
 * Returns all users with set NDS tag
 * 
 * @return array
 */
function zb_NdsGetAllUsers() {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $nds_tag = $alterconf['NDS_TAGID'];
    $query = "SELECT `login`,`id` from `tags` WHERE `tagid`='" . $nds_tag . "'";
    $allusers = simple_queryall($query);
    $result = array();
    if (!empty($allusers)) {
        foreach ($allusers as $io => $eachuser) {
            $result[$eachuser['login']] = $eachuser['id'];
        }
    }
    return ($result);
}

/**
 * Performs fast check is user NDS payer?
 * 
 * @param string $login
 * @param array $allndsusers
 * @return bool
 */
function zb_NdsCheckUser($login, $allndsusers) {
    if (isset($allndsusers[$login])) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns tax rate for NDS user
 * 
 * @return string
 */
function zb_NdsGetPercent() {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $nds_rate = $alterconf['NDS_TAX_PERCENT'];
    return ($nds_rate);
}

/**
 * Returns calculated NDS rate for summ
 * 
 * @param float $summ
 * @param int $ndspercent
 * @return float
 */
function zb_NdsCalc($summ, $ndspercent) {
    $result = ($summ / 100) * $ndspercent;
    return ($result);
}

/**
 * Renders NDS users payments list
 * 
 * @param string $query
 * @return string
 */
function web_NdsPaymentsShow($query) {
    $alter_conf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $alladrs = zb_AddressGetFulladdresslist();
    $alltypes = zb_CashGetAllCashTypes();
    $allapayments = simple_queryall($query);
    $ndstax = $alter_conf['NDS_TAX_PERCENT'];
    $allndsusers = zb_NdsGetAllUsers();
    $ndspercent = zb_NdsGetPercent();
    $allservicenames = zb_VservicesGetAllNamesLabeled();
    $total = 0;
    $ndstotal = 0;

    $tablecells = wf_TableCell(__('ID'));
    $tablecells.= wf_TableCell(__('IDENC'));
    $tablecells.= wf_TableCell(__('Date'));
    $tablecells.= wf_TableCell(__('Cash'));
    $tablecells.= wf_TableCell(__('NDS'));
    $tablecells.= wf_TableCell(__('Without NDS'));
    $tablecells.= wf_TableCell(__('Login'));
    $tablecells.= wf_TableCell(__('Full address'));
    $tablecells.= wf_TableCell(__('Cash type'));

    $tablecells.= wf_TableCell(__('Notes'));
    $tablecells.= wf_TableCell(__('Admin'));
    $tablerows = wf_TableRow($tablecells, 'row1');

    if (!empty($allapayments)) {
        foreach ($allapayments as $io => $eachpayment) {
            if (zb_NdsCheckUser($eachpayment['login'], $allndsusers)) {
                if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
                    if ($eachpayment['note'] == '') {
                        $eachpayment['note'] = __('Internet');
                    }

                    if (isset($allservicenames[$eachpayment['note']])) {
                        $eachpayment['note'] = $allservicenames[$eachpayment['note']];
                    }

                    if (ispos($eachpayment['note'], 'CARD:')) {
                        $cardnum = explode(':', $eachpayment['note']);
                        $eachpayment['note'] = __('Card') . " " . $cardnum[1];
                    }

                    if (ispos($eachpayment['note'], 'SCFEE')) {
                        $eachpayment['note'] = __('Credit fee');
                    }

                    if (ispos($eachpayment['note'], 'TCHANGE:')) {
                        $tariff = explode(':', $eachpayment['note']);
                        $eachpayment['note'] = __('Tariff change') . " " . $tariff[1];
                    }

                    if (ispos($eachpayment['note'], 'BANKSTA:')) {
                        $banksta = explode(':', $eachpayment['note']);
                        $eachpayment['note'] = __('Bank statement') . " " . $banksta[1];
                    }
                }


                $tablecells = wf_TableCell($eachpayment['id']);
                $tablecells.= wf_TableCell(zb_NumEncode($eachpayment['id']));
                $tablecells.= wf_TableCell($eachpayment['date']);
                $tablecells.= wf_TableCell($eachpayment['summ']);
                $paynds = zb_NdsCalc($eachpayment['summ'], $ndspercent);
                $tablecells.= wf_TableCell($paynds);
                $tablecells.= wf_TableCell($eachpayment['summ'] - $paynds);
                $profilelink = wf_Link('?module=userprofile&username=' . $eachpayment['login'], web_profile_icon() . ' ' . $eachpayment['login'], false);
                $tablecells.= wf_TableCell($profilelink);
                $tablecells.= wf_TableCell(@$alladrs[$eachpayment['login']]);
                $tablecells.= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
                $tablecells.= wf_TableCell($eachpayment['note']);
                $tablecells.= wf_TableCell($eachpayment['admin']);
                $tablerows.= wf_TableRow($tablecells, 'row3');


                if ($eachpayment['summ'] > 0) {
                    $total = $total + $eachpayment['summ'];
                    $ndstotal = $ndstotal + $paynds;
                }
            }
        }
    }

    $tablecells = wf_TableCell('');
    $tablecells.= wf_TableCell('');
    $tablecells.= wf_TableCell('');
    $tablecells.= wf_TableCell($total);
    $tablecells.= wf_TableCell($ndstotal);
    $tablecells.= wf_TableCell($total - $ndstotal);
    $tablecells.= wf_TableCell('');
    $tablecells.= wf_TableCell('');
    $tablecells.= wf_TableCell('');
    $tablecells.= wf_TableCell('');
    $tablecells.= wf_TableCell('');
    $tablerows.= wf_TableRow($tablecells, 'row2');

    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
    $result.='' . __('Total') . ': <strong>' . $total . '</strong> ' . __('ELVs for all payments of') . ': <strong>' . $ndstotal . '</strong>';
    return($result);
}

/**
 * Shows list of NDS users payments per year
 * 
 * @param int $year
 * 
 * @return void
 */
function web_NdsPaymentsShowYear($year) {
    $months = months_array();

    $year_summ = zb_PaymentsGetYearSumm($year);

    $cells = wf_TableCell(__('Month'));
    $rows = wf_TableRow($cells, 'row1');

    foreach ($months as $eachmonth => $monthname) {
        $month_summ = zb_PaymentsGetMonthSumm($year, $eachmonth);
        $paycount = zb_PaymentsGetMonthCount($year, $eachmonth);
        $cells = wf_TableCell(wf_Link('?module=nds&month=' . $year . '-' . $eachmonth, rcms_date_localise($monthname), false));
        $rows.= wf_TableRow($cells, 'row3');
    }
    $result = wf_TableBody($rows, '30%', '0');

    show_window(__('Payments by') . ' ' . $year, $result);
}

/**
 * Returns ahent selector for registration form
 * 
 * @param string $name
 * @param int $selected
 * @return string
 */
function zb_RegContrAhentSelect($name, $selected = '') {
    $allagents = zb_ContrAhentGetAllData();
    $agentArr = array();
    if (!empty($allagents)) {
        foreach ($allagents as $io => $eachagent) {
            $agentArr[$eachagent['id']] = $eachagent['contrname'];
        }
    }
    $select = wf_Selector($name, $agentArr, '', $selected, false);
    return($select);
}

/**
 * Renders agent strict assign form
 * 
 * @return string
 */
function web_AgentAssignStrictForm($login, $currentassign) {
    if (!empty($currentassign)) {
        $agentData = zb_ContrAhentGetData($currentassign);
        @$currentAgentName = $agentData['contrname'];
    } else {
        $currentAgentName = __('No');
    }
    $inputs = zb_ContrAhentSelectPreset($currentassign);
    $inputs.= wf_HiddenInput('assignstrictlogin', $login);

    $deleteCheckbox = wf_CheckInput('deleteassignstrict', __('Delete'), false, false);

    $cells = wf_TableCell(__('Service provider'), '', 'row2');
    $cells.= wf_TableCell($currentAgentName, '', 'row3');
    $rows = wf_tablerow($cells);
    $cells = wf_TableCell(__('New assign'), '', 'row2');
    $cells.= wf_TableCell($inputs, '', 'row3');
    $rows.=wf_tablerow($cells);
    $cells = wf_TableCell('', '', 'row2');
    $cells.= wf_TableCell($deleteCheckbox, '', 'row3');
    $rows.=wf_tablerow($cells);
    $form = wf_TableBody($rows, '100%', 0);
    $form.=wf_Submit('Save');

    $result = wf_Form("", 'POST', $form, '');
    return ($result);
}

/**
 * Deletes existing ahent strict assign from database
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_AgentAssignStrictDelete($login) {
    $login = mysql_real_escape_string($login);
    $query = "DELETE from `ahenassignstrict` WHERE `login`='" . $login . "';";
    nr_query($query);
    log_register("AGENTASSIGNSTRICT DELETE (" . $login . ")");
}

/**
 * Creates ahent strict assign record in database
 * 
 * @param string $login
 * @param int $agentid
 * 
 * @return void
 */
function zb_AgentAssignStrictCreate($login, $agentid) {
    zb_AgentAssignStrictDelete($login);
    $clearLogin = mysql_real_escape_string($login);
    $agentid = vf($agentid, 3);
    $query = "INSERT INTO `ahenassignstrict` (`id` ,  `agentid` ,`login`)
              VALUES (NULL , '" . $agentid . "', '" . $clearLogin . "');";
    nr_query($query);
    log_register("AGENTASSIGNSTRICT ADD (" . $login . ") [" . $agentid . "]");
}

?>
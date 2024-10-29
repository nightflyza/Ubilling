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
 * @param string $agnameabbr
 * @param string $agsignatory
 * @param string $agsignatory2
 * @param string $agbasis
 * @param string $agmail
 * @param string $siteurl
 * 
 * @return void
 */
function zb_ContrAhentAdd($bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname, $agnameabbr, $agsignatory, $agsignatory2, $agbasis, $agmail, $siteurl) {
    $bankacc = ubRouting::filters($bankacc, 'mres');
    $bankname = ubRouting::filters($bankname, 'mres');
    $bankcode = ubRouting::filters($bankcode, 'mres');
    $edrpo = ubRouting::filters($edrpo, 'mres');
    $ipn = ubRouting::filters($ipn, 'mres');
    $licensenum = ubRouting::filters($licensenum, 'mres');
    $juraddr = ubRouting::filters($juraddr, 'mres');
    $phisaddr = ubRouting::filters($phisaddr, 'mres');
    $phone = ubRouting::filters($phone, 'mres');
    $contrnameF = ubRouting::filters($contrname, 'mres');
    $agnameabbr = ubRouting::filters($agnameabbr, 'mres');
    $agsignatory = ubRouting::filters($agsignatory, 'mres');
    $agsignatory2 = ubRouting::filters($agsignatory2, 'mres');
    $agbasis = ubRouting::filters($agbasis, 'mres');
    $agmail = ubRouting::filters($agmail, 'mres');
    $siteurl = ubRouting::filters($siteurl, 'mres');

    $agentsDb = new NyanORM('contrahens');
    $agentsDb->data('bankacc', $bankacc);
    $agentsDb->data('bankname', $bankname);
    $agentsDb->data('bankcode', $bankcode);
    $agentsDb->data('edrpo', $edrpo);
    $agentsDb->data('ipn', $ipn);
    $agentsDb->data('licensenum', $licensenum);
    $agentsDb->data('juraddr', $juraddr);
    $agentsDb->data('phisaddr', $phisaddr);
    $agentsDb->data('phone', $phone);
    $agentsDb->data('contrname', $contrnameF);
    $agentsDb->data('agnameabbr', $agnameabbr);
    $agentsDb->data('agsignatory', $agsignatory);
    $agentsDb->data('agsignatory2', $agsignatory2);
    $agentsDb->data('agbasis', $agbasis);
    $agentsDb->data('agmail', $agmail);
    $agentsDb->data('siteurl', $siteurl);
    $agentsDb->create();

    $newId = $agentsDb->getLastId();

    log_register('AGENT CREATE `' . $contrname . '` AS [' . $newId . ']');
}

/**
 * Changes existing contrahent record in database
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
 * @param string $agnameabbr
 * @param string $agsignatory
 * @param string $agsignatory2
 * @param string $agbasis
 * @param string $agmail
 * @param string $siteurl
 * 
 * @return void
 */
function zb_ContrAhentChange($ahentid, $bankacc, $bankname, $bankcode, $edrpo, $ipn, $licensenum, $juraddr, $phisaddr, $phone, $contrname, $agnameabbr, $agsignatory, $agsignatory2, $agbasis, $agmail, $siteurl) {
    $ahentid = ubRouting::filters($ahentid, 'int');
    $bankacc = ubRouting::filters($bankacc, 'mres');
    $bankname = ubRouting::filters($bankname, 'mres');
    $bankcode = ubRouting::filters($bankcode, 'mres');
    $edrpo = ubRouting::filters($edrpo, 'mres');
    $ipn = ubRouting::filters($ipn, 'mres');
    $licensenum = ubRouting::filters($licensenum, 'mres');
    $juraddr = ubRouting::filters($juraddr, 'mres');
    $phisaddr = ubRouting::filters($phisaddr, 'mres');
    $phone = ubRouting::filters($phone, 'mres');
    $contrnameF = ubRouting::filters($contrname, 'mres');
    $agnameabbr = ubRouting::filters($agnameabbr, 'mres');
    $agsignatory = ubRouting::filters($agsignatory, 'mres');
    $agsignatory2 = ubRouting::filters($agsignatory2, 'mres');
    $agbasis = ubRouting::filters($agbasis, 'mres');
    $agmail = ubRouting::filters($agmail, 'mres');
    $siteurl = ubRouting::filters($siteurl, 'mres');

    $agentsDb = new NyanORM('contrahens');
    $agentsDb->data('bankacc', $bankacc);
    $agentsDb->data('bankname', $bankname);
    $agentsDb->data('bankcode', $bankcode);
    $agentsDb->data('edrpo', $edrpo);
    $agentsDb->data('ipn', $ipn);
    $agentsDb->data('licensenum', $licensenum);
    $agentsDb->data('juraddr', $juraddr);
    $agentsDb->data('phisaddr', $phisaddr);
    $agentsDb->data('phone', $phone);
    $agentsDb->data('contrname', $contrnameF);
    $agentsDb->data('agnameabbr', $agnameabbr);
    $agentsDb->data('agsignatory', $agsignatory);
    $agentsDb->data('agsignatory2', $agsignatory2);
    $agentsDb->data('agbasis', $agbasis);
    $agentsDb->data('agmail', $agmail);
    $agentsDb->data('siteurl', $siteurl);
    $agentsDb->where('id', '=', $ahentid);
    $agentsDb->save();

    log_register('AGENT CHANGE `' . $contrname . '` AS [' . $ahentid . ']');
}

/**
 * Deletes existing contrahent from database
 * 
 * @param int $id
 * 
 * @return void
 */
function zb_ContrAhentDelete($id) {
    $id = ubRouting::filters($id, 'int');

    $agentsDb = new NyanORM('contrahens');
    $agentsDb->where('id', '=', $id);
    $agentsDb->delete();

    log_register('AGENT DELETE [' . $id . ']');
}

/**
 * Returns contrahent data as array
 * 
 * @param int $id
 * 
 * @return array
 */
function zb_ContrAhentGetData($id) {
    $id = ubRouting::filters($id, 'int');
    $result = array();
    $agentsDb = new NyanORM('contrahens');
    $agentsDb->where('id', '=', $id);
    $raw = $agentsDb->getAll();
    if (!empty($raw)) {
        $result = $raw[0];
    }
    return ($result);
}

/**
 * Returns full contrahent data as raw array or assoc array
 * 
 * @return array
 */
function zb_ContrAhentGetAllData() {
    $agentsDb = new NyanORM('contrahens');
    $result = $agentsDb->getAll();
    return ($result);
}

/**
 * Returns full contrahent data array as id=>agentData
 * 
 * @return array
 */
function zb_ContrAhentGetAllDataAssoc() {
    $agentsDb = new NyanORM('contrahens');
    $result = $agentsDb->getAll('id');
    return ($result);
}

/**
 * Renders contrahents list with required controls
 * 
 * @return string
 */
function zb_ContrAhentShow() {
    global $ubillingConfig;
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

    if ($ubillingConfig->getAlterParam('AGENTS_EXTINFO_ON')) {
        $extactbutton = wf_img('skins/icons/articlepost.png', __('Extended info'));
        $result = web_GridEditor($titles, $keys, $allcontr, 'contrahens', true, true, '', 'extinfo', $extactbutton, true);
    } else {
        $result = web_GridEditor($titles, $keys, $allcontr, 'contrahens', true, true, '', '', '', true);
    }

    return ($result);
}

/**
 * Renders contrahent creation form
 * 
 * @return string
 */
function zb_ContrAhentAddForm() {
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = '';
    $inputs .= wf_TextInput('newcontrname', __('Contrahent name') . $sup, '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newbankacc', __('Bank account'), '', false, '40', '', '', '', '', true);
    $inputs .= wf_TextInput('newbankname', __('Bank name'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newbankcode', __('Bank code'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newedrpo', __('EDRPOU'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newipn', __('IPN'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newlicensenum', __('License number'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newjuraddr', __('Juridical address'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newphisaddr', __('Phisical address'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newphone', __('Phone'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newagnameabbr', __('Short name'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newagsignatory', __('Signatory'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newagsignatory2', __('Signatory') . ' 2', '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newagbasis', __('Basis'), '', false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('newagmail', __('Mail'), '', false, '', '', '', '', '', true, 'email');
    $inputs .= wf_TextInput('newsiteurl', __('Site URL'), '', false, '', '', '', '', '', true, 'url');
    $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Create'));

    $result = wf_Form("", 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right labels-top');

    return ($result);
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
    $inputs .= wf_TextInput('changecontrname', __('Contrahent name') . $sup, ubRouting::filters($cdata['contrname'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changebankacc', __('Bank account'), $cdata['bankacc'], false, '40', '', '', '', '', true);
    $inputs .= wf_TextInput('changebankname', __('Bank name'), ubRouting::filters($cdata['bankname'], 'callback', 'htmlspecialchars') , false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changebankcode', __('Bank code'), $cdata['bankcode'], false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeedrpo', __('EDRPOU'), $cdata['edrpo'], false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeipn', __('IPN'), $cdata['ipn'], false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changelicensenum', __('License number'), ubRouting::filters($cdata['licensenum'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changejuraddr', __('Juridical address'), ubRouting::filters($cdata['juraddr'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changephisaddr', __('Phisical address'), ubRouting::filters($cdata['phisaddr'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changephone', __('Phone'), $cdata['phone'], false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeagnameabbr', __('Short name'), ubRouting::filters($cdata['agnameabbr'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeagsignatory', __('Signatory'), ubRouting::filters($cdata['agsignatory'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeagsignatory2', __('Signatory') . ' 2', ubRouting::filters($cdata['agsignatory2'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeagbasis', __('Basis'), ubRouting::filters($cdata['agbasis'], 'callback', 'htmlspecialchars'), false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('changeagmail', __('Mail'), $cdata['agmail'], false, '', 'email', '', '', '', true);
    $inputs .= wf_TextInput('changesiteurl', __('Site URL'), $cdata['siteurl'], false, '', 'url', '', '', '', true);

    $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Save'));
    $result = wf_Form("", 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right labels-top');

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

    $result = wf_Selector('ahentsel', $params, __('Contrahent name'), false, false, false, '', '', '', true);
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
    return ($select);
}

/**
 * Returns array of all agent=>street assigns 
 * 
 * @param string $order
 * 
 * @return array
 */
function zb_AgentAssignGetAllData($order = '') {
    $query = "SELECT * from `ahenassign` " . $order;
    $allassigns = simple_queryall($query);
    return ($allassigns);
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
    $inputs .= wf_TextInput('newassign', __('Street name') . $sup, '', false, '', '', '', '', '', true);
    $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Save'));
    $result = wf_Form("", 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right labels-top');

    return ($result);
}

/**
 * Renders list of available ahent assigns with required controls
 * 
 * @return string
 */
function web_AgentAssignShow($renderAutoAssign = FALSE) {
    $allassigns = zb_AgentAssignGetAllData("ORDER BY `id` DESC");
    $allahens = zb_ContrAhentGetAllData();
    $usedStreets = array();
    $agentnames = array();
    if (!empty($allahens)) {
        foreach ($allahens as $io => $eachahen) {
            $agentnames[$eachahen['id']] = $eachahen['contrname'];
        }
    }

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Contrahent name'));
    $cells .= wf_TableCell(__('Street name'));
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allassigns)) {
        foreach ($allassigns as $io2 => $eachassign) {
            $rowColor = (isset($usedStreets[$eachassign['streetname']])) ? 'ukvbankstadup' : 'row5';
            $cells = wf_TableCell($eachassign['id']);
            $cells .= wf_TableCell(@$agentnames[$eachassign['ahenid']]);
            $cells .= wf_TableCell($eachassign['streetname']);
            $actLinks = wf_JSAlert('?module=contrahens&deleteassign=' . $eachassign['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, $rowColor);
            $usedStreets[$eachassign['streetname']] = $eachassign['ahenid'];
        }
    }

    if (!$renderAutoAssign) {
        // Create button for show automatic assign agetnts
        $inputs = wf_HiddenInput('renderautoassign', 'true');
        $inputs .= wf_SubmitClassed(true, 'ubButton', '', __('Show automatic agent assignments'));
        $form = wf_Form("", 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right labels-top');
    } else {
        // Backlink
        $form = wf_BackLink('?module=contrahens');
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $result.= $form;

    return ($result);
}

/**
* Renders list of strict login=>agent assigns with some controls
*
* @return string
*/
function web_AgentAssignRender($renderAutoAssign = FALSE) {
    if ($renderAutoAssign) {
        $ajaxURL = '?module=contrahens&ajaxagenassignauto=true';
    } else {
        $ajaxURL = '?module=contrahens&ajaxagenassign=true';
    }
    $columns = array('Login', 'Full address', 'Real Name', 'Tariff', 'Contrahent name', 'Actions');
    $opts = '"order": [[ 0, "desc" ]], "dom": \'<"F"lfB>rti<"F"ps>\', buttons: [\'csv\', \'excel\', \'pdf\']';
    $result = wf_JqDtLoader($columns, $ajaxURL, false, 'assignments', 100, $opts);
    return ($result);
}

/**
 * Renders data list of strict login=>agent assigns with some controls
 * 
 * @return string
 */
function web_AgentAssignStrictShow() {
    $JSONHelper = new wf_JqDtHelper();

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

    if (!empty($allassigns)) {
        foreach ($allassigns as $eachlogin => $eachagent) {
            $loginLink = wf_Link('?module=userprofile&username=' . $eachlogin, web_profile_icon() . ' ' . $eachlogin, false, '');
            $actLinks = wf_JSAlert('?module=contractedit&username=' . $eachlogin, web_edit_icon(), __('Are you serious'));
            $actLinks.= wf_JSAlert('?module=contrahens&deleteassignstrict=true&username=' . $eachlogin, web_delete_icon(), __('Are you serious'));

            $data[] = $loginLink;
            $data[] = @$alladdress[$eachlogin];
            $data[] = @$allrealnames[$eachlogin];
            $data[] = @$allusertariffs[$eachlogin];
            $data[] = @$agentnames[$eachagent];
            $data[] = $actLinks;

            $JSONHelper->addRow($data);
            unset($data);
        }
    }

    $JSONHelper->getJson();
}

/**
* Renders data list of strict login=>agent assigns with some controls
*
* @return string
*/
function web_AgentAssignAutoShow() {
    $JSONHelper = new wf_JqDtHelper();
    $allUsers = new NyanORM('users');
    $allUsers->selectable('login');
    $allUsers->where('Down', '=', '0');
    $users = $allUsers->getAll();
    if (!empty($users)) {
        $allassigns = zb_AgentAssignGetAllData();
        $strictAssigns = zb_AgentAssignStrictGetAllData();
        $allahens = zb_ContrAhentGetAllData();
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $allusertariffs = zb_TariffsGetAllUsers();

        $agentnames = array();
        if (!empty($allahens)) {
            foreach ($allahens as $io => $eachahen) {
                $agentnames[$eachahen['id']] = $eachahen['contrname'];
            }
        }

        foreach ($users as $login) {
            $login = $login['login'];
            if (!isset($strictAssigns[$login])) {
                $loginLink = wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . $login, false, '');
                $actLinks = wf_JSAlert('?module=contractedit&username=' . $login, web_edit_icon(), __('Are you serious'));
                $agent_assigned = zb_AgentAssignCheckLogin($login, $allassigns, $alladdress);

                $data[] = $loginLink;
                $data[] = @$alladdress[$login];
                $data[] = @$allrealnames[$login];
                $data[] = @$allusertariffs[$login];
                $data[] = @$agentnames[$agent_assigned];
                $data[] = $actLinks;

                $JSONHelper->addRow($data);
                unset($data);
            }
        }

    }

    $JSONHelper->getJson();
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
    global $ubillingConfig;
    $alter_cfg = $ubillingConfig->getAlter();
    $result = false;
    // если пользователь куда-то заселен
    if (isset($alladdress[$login])) {
        // возвращаем дефолтного агента если присваиваний нет вообще
        if (empty($allassigns)) {
            $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
        } else {
            //если какие-то присваивалки есть
            $useraddress = $alladdress[$login];
            // Одразу задаємо дефолтного агента, якщо нічого потім не знайдемо. Не будемо кожен раз переназначати вивід
            $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
            // проверяем для каждой присваивалки попадает ли она под нашего абонента
            foreach ($allassigns as $io => $eachassign) {
                if (strpos($useraddress, $eachassign['streetname']) !== false) {
                    $result = $eachassign['ahenid'];
                    // Знаходимо першего відповідного агента і перериваємо цикл.
                    // Якщо вказали 2-і і більше адрес, які підпадають під умови - це ваші проблеми. Робіть адреси і міста унікальними
                    break;
                }
            }
        }
    }
    // если присваивание выключено возвращаем умолчального
    if (!$alter_cfg['AGENTS_ASSIGN']) {
        $result = $alter_cfg['DEFAULT_ASSIGN_AGENT'];
    }
    return ($result);
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

    return ($result);
}

/**
 * Returns content of export template
 * 
 * @param string $filename
 * @return string
 */
function zb_ExportLoadTemplate($filename) {
    $template = file_get_contents($filename);
    return ($template);
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
    return ($result);
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

    return ($result);
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
    return ($templatebody);
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
        return ($result);
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
    $inputs .= wf_DatePickerPreset('fromdate', $yesterday);
    $inputs .= __('To');
    $inputs .= wf_DatePickerPreset('todate', $curdate);
    $inputs .= wf_Submit('Export');
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    return ($form);
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
                    $export_result .= zb_ExportParseTemplate($template, $parse_data);
                }
            } else {
                //or anyway export it
                $export_result .= zb_ExportParseTemplate($template, $parse_data);
            }
        }
    }
    $export_result .= zb_ExportParseTemplate($template_end, $parse_data);

    if ($import_encoding != $export_encoding) {
        $export_result = iconv($import_encoding, $export_encoding, $export_result);
    }
    return ($export_result);
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
    return ($result);
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
    return ($result);
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
    return ($result);
}

/**
 * Returns content of sales slip HTML template
 * 
 * @return string
 */
function zb_PrintCheckLoadTemplate() {
    $template = file_get_contents(CONFIG_PATH . 'printcheck.tpl');
    return ($template);
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
    return ($result);
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
    return ($select);
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
    $inputs .= wf_HiddenInput('assignstrictlogin', $login);

    $deleteCheckbox = wf_CheckInput('deleteassignstrict', __('Delete'), false, false);

    $cells = wf_TableCell(__('Service provider'), '', 'row2');
    $cells .= wf_TableCell($currentAgentName, '', 'row3');
    $rows = wf_tablerow($cells);
    $cells = wf_TableCell(__('New assign'), '', 'row2');
    $cells .= wf_TableCell($inputs, '', 'row3');
    $rows .= wf_tablerow($cells);
    $cells = wf_TableCell('', '', 'row2');
    $cells .= wf_TableCell($deleteCheckbox, '', 'row3');
    $rows .= wf_tablerow($cells);
    $form = wf_TableBody($rows, '100%', 0);
    $form .= wf_Submit('Save');

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

/**
 * Renders agent assigned users stats
 * 
 * @param string $mask Optional tariff mask to separate users
 * 
 * @return string
 */
function zb_AgentStatsRender($mask = '') {
    /**
     * Seems that waz written with Paranormal Helicopter Porn
     */
    $result = '';
    $allUsers = zb_UserGetAllStargazerDataAssoc();
    $tmpArr = array(); // contains all user assigns as login=>agentid
    $agentCounters = array(); //contains binding stats as AgentId = > userCount
    $maskCounters = array(); //contains binding stats as AgentId mask=>all/active
    if (!empty($allUsers)) {
        $allAddress = zb_AddressGetFullCityaddresslist();
        $allAssigns = zb_AgentAssignGetAllData();
        $strictAssigns = zb_AgentAssignStrictGetAllData();
        $allAgentData = zb_ContrAhentGetAllData();
        $allAgentNames = array(); //agentid=>contrname
        if (!empty($allAgentData)) {
            //shitty preprocessing here
            foreach ($allAgentData as $io => $eachAgentData) {
                $allAgentNames[$eachAgentData['id']] = $eachAgentData['contrname'];
            }
        }
        foreach ($allUsers as $eachLogin => $eachUserData) {
            $assignedAgentId = zb_AgentAssignCheckLoginFast($eachLogin, $allAssigns, @$allAddress[$eachLogin], $strictAssigns);
            if (!empty($assignedAgentId)) {
                $tmpArr[$eachLogin] = $assignedAgentId;
            }
        }

        if (!empty($tmpArr)) {
            foreach ($tmpArr as $eachUser => $eachAgentId) {
                $userData = $allUsers[$eachUser];
                if (($userData['Cash'] >= '-' . $userData['Credit']) and ($userData['AlwaysOnline'] == 1) and ($userData['Passive'] == 0) and ($userData['Down'] == 0)) {
                    $active = 1;
                } else {
                    $active = 0;
                }

                if (isset($agentCounters[$eachAgentId])) {
                    $agentCounters[$eachAgentId]['total']++;
                    $agentCounters[$eachAgentId]['active'] += $active;
                } else {
                    $agentCounters[$eachAgentId]['total'] = 1;
                    $agentCounters[$eachAgentId]['active'] = $active;
                }

                if (!empty($mask)) {
                    if (ispos($userData['Tariff'], $mask)) {
                        if (isset($maskCounters[$eachAgentId][$mask])) {
                            $maskCounters[$eachAgentId][$mask]['all']++;
                            $maskCounters[$eachAgentId][$mask]['active'] += $active;
                        } else {
                            $maskCounters[$eachAgentId][$mask]['all'] = 1;
                            $maskCounters[$eachAgentId][$mask]['active'] = $active;
                        }
                    }
                }
            }
        }

        if (!empty($agentCounters)) {
            $cells = wf_TableCell(__('Contrahent name'));
            $cells .= wf_TableCell(__('Users'));
            $cells .= wf_TableCell(__('Active'));
            if ($mask) {
                $cells .= wf_TableCell($mask . ': ' . __('Total') . ' / ' . __('Active'));
            }
            $rows = wf_TableRow($cells, 'row1');
            foreach ($agentCounters as $agentId => $userCount) {
                $cells = wf_TableCell(@$allAgentNames[$agentId]);
                $cells .= wf_TableCell($userCount['total']);
                $cells .= wf_TableCell($userCount['active']);
                if ($mask) {
                    $cells .= wf_TableCell(@$maskCounters[$agentId][$mask]['all'] . ' / ' . @$maskCounters[$agentId][$mask]['active']);
                }
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
    }
    return ($result);
}

/**
 * Returns extended agent info filtered by $recID or $agentID.
 * If no $recID or $agentID parameter given - all available records returned.
 *
 * @param string $recID
 * @param string $agentID
 * @param false $getBaseAgentInfo
 *
 * @return array
 */
function zb_GetAgentExtInfo($recID = '', $agentID = '', $getBaseAgentInfo = false, $whereRawStr = '', $assocByField = '') {
    $tabAgentExtInfo = new NyanORM('contrahens_extinfo');

    if ($getBaseAgentInfo) {
        $tabAgentExtInfo->selectable(array(
            '`contrahens_extinfo`.*',
            '`contrahens`.`bankacc`',
            '`contrahens`.`bankname`',
            '`contrahens`.`bankcode`',
            '`contrahens`.`edrpo`',
            '`contrahens`.`ipn`',
            '`contrahens`.`licensenum`',
            '`contrahens`.`juraddr`',
            '`contrahens`.`phisaddr`',
            '`contrahens`.`phone`',
            '`contrahens`.`contrname`',
            '`contrahens`.`agnameabbr`',
            '`contrahens`.`agsignatory`',
            '`contrahens`.`agsignatory2`',
            '`contrahens`.`agbasis`',
            '`contrahens`.`agmail`',
            '`contrahens`.`siteurl`'
        ));
        $tabAgentExtInfo->joinOn('LEFT', 'contrahens', " `contrahens_extinfo`.`agentid` = `contrahens`.`id` ");
    }

    if (!empty($recID)) {
        $tabAgentExtInfo->where('id', '=', $recID);
    }

    if (!empty($agentID)) {
        $tabAgentExtInfo->where('agentid', '=', $agentID);
    }

    if (!empty($whereRawStr)) {
        $tabAgentExtInfo->whereRaw($whereRawStr);
    }

    $result = $tabAgentExtInfo->getAll($assocByField);

    return ($result);
}

/**
 * Creates new contragent extended info record in DB
 *
 * @param $extinfoAgentID
 * @param $extinfoSrvType
 * @param $extinfoPaySysName
 * @param $extinfoPaySysID
 * @param $extinfoPaySysSrvID
 * @param $extinfoPaySysToken
 * @param $extinfoPaySysSecretKey
 * @param $extinfoPaySysPassword
 *
 * @return void
 * @throws Exception
 */
function zb_CreateAgentExtInfoRec(
    $extinfoAgentID,
    $extinfoSrvType = '',
    $extinfoPaySysName = '',
    $extinfoPaySysID = '',
    $extinfoPaySysSrvID = '',
    $extinfoPaySysToken = '',
    $extinfoPaySysSecretKey = '',
    $extinfoPaySysPassword  = '',
    $extinfoPaymentFeeInfo = '',
    $extinfoPaySysCallbackURL  = ''
) {
    $tabAgentExtInfo = new NyanORM('contrahens_extinfo');
    $tabAgentExtInfo->dataArr(
        array(
            'agentid'                   => $extinfoAgentID,
            'service_type'              => $extinfoSrvType,
            'internal_paysys_name'      => $extinfoPaySysName,
            'internal_paysys_id'        => $extinfoPaySysID,
            'internal_paysys_srv_id'    => $extinfoPaySysSrvID,
            'paysys_token'              => $extinfoPaySysToken,
            'paysys_secret_key'         => $extinfoPaySysSecretKey,
            'paysys_password'           => $extinfoPaySysPassword,
            'payment_fee_info'          => $extinfoPaymentFeeInfo,
            'paysys_callback_url'       => $extinfoPaySysCallbackURL
        )
    );

    $tabAgentExtInfo->create();
    $recID = $tabAgentExtInfo->getLastId();

    log_register('AGENT EXTEN INFO CREATE [' . $recID . ']');
}

/**
 * Changes contragent extended info record in DB by given record ID
 *
 * @param $recID
 * @param $extinfoAgentID
 * @param $extinfoSrvType
 * @param $extinfoPaySysName
 * @param $extinfoPaySysID
 * @param $extinfoPaySysSrvID
 * @param $extinfoPaySysToken
 * @param $extinfoPaySysSecretKey
 * @param $extinfoPaySysPassword
 *
 * @return void
 * @throws Exception
 */
function zb_EditAgentExtInfoRec(
    $recID,
    $extinfoAgentID,
    $extinfoSrvType = '',
    $extinfoPaySysName = '',
    $extinfoPaySysID = '',
    $extinfoPaySysSrvID = '',
    $extinfoPaySysToken = '',
    $extinfoPaySysSecretKey = '',
    $extinfoPaySysPassword  = '',
    $extinfoPaymentFeeInfo = '',
    $extinfoPaySysCallbackURL  = ''
) {
    $tabAgentExtInfo = new NyanORM('contrahens_extinfo');
    $tabAgentExtInfo->dataArr(
        array(
            'id'                        => $recID,
            'agentid'                   => $extinfoAgentID,
            'service_type'              => $extinfoSrvType,
            'internal_paysys_name'      => $extinfoPaySysName,
            'internal_paysys_id'        => $extinfoPaySysID,
            'internal_paysys_srv_id'    => $extinfoPaySysSrvID,
            'paysys_token'              => $extinfoPaySysToken,
            'paysys_secret_key'         => $extinfoPaySysSecretKey,
            'paysys_password'           => $extinfoPaySysPassword,
            'payment_fee_info'          => $extinfoPaymentFeeInfo,
            'paysys_callback_url'       => $extinfoPaySysCallbackURL
        )
    );
    $tabAgentExtInfo->where('id', '=', $recID);
    $tabAgentExtInfo->save(true, true);

    log_register('AGENT EXTEN INFO EDIT [' . $recID . ']');
}

/**
 * Removes contragent extended info record from DB by given record ID
 *
 * @param $recID
 *
 * @return void
 * @throws Exception
 */
function zb_DeleteAgentExtInfoRec($recID) {
    $tabAgentExtInfo = new NyanORM('contrahens_extinfo');
    $tabAgentExtInfo->where('id', '=', $recID);
    $tabAgentExtInfo->delete();

    log_register('AGENT EXTEN INFO DELETE [' . $recID . ']');
}

/**
 * Returns extended agent info edit form
 *
 * @param string $recID
 *
 * @return string
 */
function zb_AgentEditExtInfoForm($recID = '') {
    $extinfoData                = (empty($recID) ? array() : zb_GetAgentExtInfo($recID));
    $extinfoEditMode            = !empty($extinfoData);
    $extinfoRecID               = '';
    $extinfoAgentID             = ubRouting::checkGet('extinfo') ? ubRouting::get('extinfo') : '';
    $extinfoSrvType             = '';
    $extinfoPaySysName          = '';
    $extinfoPaySysID            = '';
    $extinfoPaySysSrvID         = '';
    $extinfoPaySysToken         = '';
    $extinfoPaySysSecretKey     = '';
    $extinfoPaySysPassword      = '';
    $extinfoPaymentFeeInfo      = '';
    $extinfoPaySysCallbackURL   = '';
    $allPaySys                  = array();
    $srvtypeSelectorID          = wf_InputId();
    $openpayzSelectorID         = wf_InputId();
    $payfeeinfoSelectorID       = wf_InputId();
    $paysysControlID            = wf_InputId();

    if ($extinfoEditMode) {
        $extinfoRecID               = $extinfoData[0]['id'];
        $extinfoAgentID             = $extinfoData[0]['agentid'];
        $extinfoSrvType             = $extinfoData[0]['service_type'];
        $extinfoPaySysName          = $extinfoData[0]['internal_paysys_name'];
        $extinfoPaySysID            = $extinfoData[0]['internal_paysys_id'];
        $extinfoPaySysSrvID         = $extinfoData[0]['internal_paysys_srv_id'];
        $extinfoPaySysToken         = $extinfoData[0]['paysys_token'];
        $extinfoPaySysSecretKey     = $extinfoData[0]['paysys_secret_key'];
        $extinfoPaySysPassword      = $extinfoData[0]['paysys_password'];
        $extinfoPaymentFeeInfo      = $extinfoData[0]['payment_fee_info'];
        $extinfoPaySysCallbackURL   = $extinfoData[0]['paysys_callback_url'];
    } else {
        // load existing OpenPayz payment systems
        $query     = 'select distinct `paysys` from `op_transactions`';
        $all       = simple_queryall($query);

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $allPaySys[$each['paysys']] = $each['paysys'];
            }
        }

        // check if PrivatBank invoices sending is on
        global $ubillingConfig;
        $rmdPBInvoicesON = $ubillingConfig->getAlterParam('REMINDER_PRIVATBANK_INVOICE_PUSH', false);

        if ($rmdPBInvoicesON) {
            $allPaySys['PRIVAT_INVOICE_PUSH'] = 'PRIVAT_INVOICE_PUSH';
        }
    }

    $inputs = wf_Selector('extinfsrvtype', array('Internet' => __('Internet'), 'UKV' => __('UKV')), __('Choose service type'), $extinfoSrvType, false, false, $srvtypeSelectorID, '', '', true);
    $inputs .= ($extinfoEditMode) ? '' : wf_Selector('extinfoppaysys', $allPaySys, __('You may select OpenPayz payment system name'), '', false, false, $openpayzSelectorID, '', '', true);
    $inputs .= wf_TextInput('extinfintpaysysname', __('Payment system name'), $extinfoPaySysName, false, '', '', '', $paysysControlID, '', true);
    $inputs .= wf_TextInput('extinfintpaysysid', __('Contragent code within payment system'), $extinfoPaySysID, false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('extinfintpaysyssrvid', __('Service code within payment system'), $extinfoPaySysSrvID, false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('extinfintpaysystoken', __('Service token'), $extinfoPaySysToken, false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('extinfintpaysyskey', __('Service secret key'), $extinfoPaySysSecretKey, false, '', '', '', '', '', true);
    $inputs .= wf_TextInput('extinfintpaysyspasswd', __('Service password'), $extinfoPaySysPassword, false, '50', '', '', '', '', true);
    $inputs .= wf_Selector('extinfintpayfeeinfo', array('provider' => __('Provider'), 'subscriber' => __('Subscriber')), __('Choose who pays fee'), $extinfoPaymentFeeInfo, false, false, $payfeeinfoSelectorID, '', '', true);
    $inputs .= wf_TextInput('extinfintpaysyscallbackurl', __('Service callback URL'), $extinfoPaySysCallbackURL, false, '', 'url', '', '', '', true);
    $inputs .= wf_HiddenInput('extinfrecid', $extinfoRecID);
    $inputs .= wf_HiddenInput('extinfagentid', $extinfoAgentID);
    $inputs .= wf_HiddenInput('extinfeditmode', $extinfoEditMode);
    $inputs .= wf_SubmitClassed(true, 'ubButton', '', ($extinfoEditMode) ? __('Edit') : __('Create'));

    if (!$extinfoEditMode) {
        $tmpJS = "
                    $(document).ready(function() {
                        if ($('#" . $srvtypeSelectorID . " option:selected').val() == 'Internet') {
                            $('#" . $openpayzSelectorID . "').prop('disabled', false);
                        } else {
                            $('#" . $openpayzSelectorID . "').prop('disabled', true);
                        }
                        
                        $('#" . $srvtypeSelectorID . "').on('change', function() {
                            console.log($('#" . $srvtypeSelectorID . " option:selected').val());
                            let opz_disabled = ($('#" . $srvtypeSelectorID . " option:selected').val() != 'Internet');
                            $('#" . $openpayzSelectorID . "').prop('disabled', opz_disabled);
                        });
                        
                        $('#" . $openpayzSelectorID . "').on('change', function() {
                            $('#" . $paysysControlID . "').val($('#" . $openpayzSelectorID . " option:selected').val());                    
                        });
                    });
                 ";
        $inputs .= wf_EncloseWithJSTags($tmpJS);
    }

    $result = wf_Form("", 'POST', $inputs, 'glamour form-grid-2cols form-grid-2cols-label-right labels-top');

    return ($result);
}

/**
 * Returns all available extended agent info for a particular $agentID
 *
 * @param $agentID
 *
 * @return string
 */
function zb_RenderAgentExtInfoTable($agentID) {
    $extinfoData = zb_GetAgentExtInfo('', $agentID, true);

    // construct needed editor
    $titles = array(
        'ID',
        'Contrahent name',
        'Service type',
        'Payment system name',
        'Contragent code within payment system',
        'Service code within payment system',
        'Service token',
        'Service secret key',
        'Service password',
        'Who pays fee',
        'Service callback URL'
    );
    $keys = array(
        'id',
        'contrname',
        'service_type',
        'internal_paysys_name',
        'internal_paysys_id',
        'internal_paysys_srv_id',
        'paysys_token',
        'paysys_secret_key',
        'paysys_password',
        'payment_fee_info',
        'paysys_callback_url'
    );

    $result = web_GridEditor($titles, $keys, $extinfoData, 'contrahens&extinfo=' . $agentID, true, true);

    return ($result);
}

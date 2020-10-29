<?php

/**
 * Creates contract date with some contract 
 *  
 *  @param $contract - existing contract 
 *  @param $date - contract creation date in datetime format
 *  @return void
 */
function zb_UserContractDateCreate($contract, $date) {
    $contract = mysql_real_escape_string($contract);
    $date = mysql_real_escape_string($date);
    $query = "INSERT INTO `contractdates` (
                        `id` ,
                        `contract` ,
                        `date`
                        )
                        VALUES (
                        NULL , '" . $contract . "', '" . $date . "'
                        );";
    nr_query($query);
    log_register("CREATE UserContractDate [" . $contract . "] " . $date);
}

/**
 * Get all of existing contract dates
 * 
 *  @return array
 */
function zb_UserContractDatesGetAll($contract = '') {
    $query_wh = (!empty($contract)) ? " WHERE `contractdates`.`contract` = '" . $contract . "'" : "";
    $query = "SELECT * from `contractdates`"  . $query_wh;
    $all = simple_queryall($query);
    $result = array();

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['contract']] = $each['date'];
        }
    }
    return ($result);
}

/**
 *  Set contract date with some contract 
 *  
 *  @param $contract - existing contract 
 *  @param $date - contract creation date in datetime format
 *  @return void
 */
function zb_UserContractDateSet($contract, $date) {
    $contract = mysql_real_escape_string($contract);
    $date = mysql_real_escape_string($date);
    $query = "UPDATE `contractdates` SET `date`='" . $date . "' WHERE `contract`='" . $contract . "'";
    nr_query($query);
    log_register("CHANGE UserContractDate [" . $contract . "] " . $date);
}

/**
 * Shows contract create date modify form
 * 
 * @return string
 */
function web_UserContractDateChangeForm($contract, $date = '') {
    if (!empty($date)) {
        $inputs = wf_DatePickerPreset('newcontractdate', $date);
    } else {
        $inputs = wf_DatePicker('newcontractdate');
    }

    $cells = wf_TableCell(__('Current date'), '', 'row2');
    $cells.= wf_TableCell($date, '', 'row3');
    $rows = wf_tablerow($cells);
    $cells = wf_TableCell(__('New date'), '', 'row2');
    $cells.= wf_TableCell($inputs, '', 'row3');
    $rows.=wf_tablerow($cells);
    $form = wf_TableBody($rows, '100%', 0);
    $form.=wf_Submit('Save');

    $result = wf_Form("", 'POST', $form, '');
    return ($result);
}

/**
 * Create users passport data struct
 * 
 * @param    $login - user login
 * @param    $birthdate - user date of birth
 * @param    $passportnum - passport number
 * @param    $passportdate - passport assign date
 * @param    $passportwho - who produce the passport?
 * @param    $pcity - additional address city
 * @param    $pstreet - additional address street
 * @param    $pbuild - additional address build
 * @param    $papt - additional address apartment
 * @param    $pinn - additional Identification code
 * 
 * @return void
 */
function zb_UserPassportDataCreate($login, $birthdate, $passportnum, $passportdate, $passportwho, $pcity, $pstreet, $pbuild, $papt, $pinn='') {
    $login = mysql_real_escape_string($login);
    $birthdate = mysql_real_escape_string($birthdate);
    $passportnum = mysql_real_escape_string($passportnum);
    $passportdate = mysql_real_escape_string($passportdate);
    $passportwho = mysql_real_escape_string($passportwho);
    $pcity = mysql_real_escape_string($pcity);
    $pstreet = mysql_real_escape_string($pstreet);
    $pbuild = mysql_real_escape_string($pbuild);
    $papt = mysql_real_escape_string($papt);
    $pinn = mysql_real_escape_string($pinn);

    $query = "
        INSERT INTO `passportdata` (
                    `id` ,
                    `login` ,
                    `birthdate` ,
                    `passportnum` ,
                    `passportdate` ,
                    `passportwho` ,
                    `pcity` ,
                    `pstreet` ,
                    `pbuild` ,
                    `papt`,
                    `pinn` 
                    )
                    VALUES (
                    NULL ,
                    '" . $login . "',
                    '" . $birthdate . "',
                    '" . $passportnum . "',
                    '" . $passportdate . "',
                    '" . $passportwho . "',
                    '" . $pcity . "',
                    '" . $pstreet . "',
                    '" . $pbuild . "',
                    '" . $papt . "',
                    '" . $pinn . "'
                                );
        ";
    nr_query($query);
    log_register("CREATE UserPassportData (" . $login . ")");
}

/**
 * Update users passport data 
 * 
 * @param    $login - user login
 * @param    $birthdate - user date of birth
 * @param    $passportnum - passport number
 * @param    $passportdate - passport assign date
 * @param    $passportwho - who produce the passport?
 * @param    $pcity - additional address city
 * @param    $pstreet - additional address street
 * @param    $pbuild - additional address build
 * @param    $papt - additional address apartment
 * @param    $pinn - Personal identification code
 * 
 * @return void
 */
function zb_UserPassportDataSet($login, $birthdate, $passportnum, $passportdate, $passportwho, $pcity, $pstreet, $pbuild, $papt, $pinn='') {
    $login = mysql_real_escape_string($login);
    $birthdate = mysql_real_escape_string($birthdate);
    $passportnum = mysql_real_escape_string($passportnum);
    $passportdate = mysql_real_escape_string($passportdate);
    $passportwho = mysql_real_escape_string($passportwho);
    $pcity = mysql_real_escape_string($pcity);
    $pstreet = mysql_real_escape_string($pstreet);
    $pbuild = mysql_real_escape_string($pbuild);
    $papt = mysql_real_escape_string($papt);
    $pinn = mysql_real_escape_string($pinn);

    $query = "
        UPDATE `passportdata` SET
                    `birthdate` = '" . $birthdate . "',
                    `passportnum` = '" . $passportnum . "',
                    `passportdate` = '" . $passportdate . "',
                    `passportwho` = '" . $passportwho . "',
                    `pcity` = '" . $pcity . "',
                    `pstreet` = '" . $pstreet . "',
                    `pbuild` = '" . $pbuild . "',
                    `papt` = '" . $papt . "',
                    `pinn` = '" . $pinn . "'
                     WHERE `login`='" . $login . "'
        ";
    nr_query($query);
    log_register("CHANGE UserPassportData (" . $login . ")");
}

/**
 * Gets user passport data
 * 
 * @param $login - user login
 * 
 * @return array
 */
function zb_UserPassportDataGet($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `passportdata` WHERE `login`='" . $login . "'";
    $passportdata = simple_query($query);
    $result = array();
    if (!empty($passportdata)) {
        $result = $passportdata;
    }
    return ($result);
}

/**
 * Get passportdata for all users
 * 
 * @return array
 */
function zb_UserPassportDataGetAll() {
    $query = "SELECT * from `passportdata`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']]['login'] = $each['login'];
            $result[$each['login']]['birthdate'] = $each['birthdate'];
            $result[$each['login']]['passportnum'] = $each['passportnum'];
            $result[$each['login']]['passportdate'] = $each['passportdate'];
            $result[$each['login']]['passportwho'] = $each['passportwho'];
            $result[$each['login']]['pcity'] = $each['pcity'];
            $result[$each['login']]['pstreet'] = $each['pstreet'];
            $result[$each['login']]['pbuild'] = $each['pbuild'];
            $result[$each['login']]['papt'] = $each['papt'];
            $result[$each['login']]['pinn'] = $each['pinn'];
        }
    }
    return ($result);
}

/**
 * Detect user passport data existance and modify it - USE ONLY THIS IN CODE!
 * 
 * @param    $login - user login
 * @param    $birthdate - user date of birth
 * @param    $passportnum - passport number
 * @param    $passportdate - passport assign date
 * @param    $passportwho - who produce the passport?
 * @param    $pcity - additional address city
 * @param    $pstreet - additional address street
 * @param    $pbuild - additional address build
 * @param    $papt - additional address apartment
 * @param    $pinn - Personal identification code
 * 
 * @return void
 */
function zb_UserPassportDataChange($login, $birthdate, $passportnum, $passportdate, $passportwho, $pcity, $pstreet, $pbuild, $papt, $pinn) { 
    $exist_q = "SELECT `id` from `passportdata` WHERE `login`='" . mysql_real_escape_string($login) . "'";
    $exist = simple_query($exist_q);
    if (!empty($exist)) {
        // data for this user already exists, just - modify
        zb_UserPassportDataSet($login, $birthdate, $passportnum, $passportdate, $passportwho, $pcity, $pstreet, $pbuild, $papt, $pinn);
    } else {
        //create new
        zb_UserPassportDataCreate($login, $birthdate, $passportnum, $passportdate, $passportwho, $pcity, $pstreet, $pbuild, $papt, $pinn);
    }
}

/**
 * Returns expresscard address modify form
 * 
 * @param $login - user login for modifying apt
 * 
 * @return string
 */
function web_ExpressAddressAptForm($login) {
    $login = vf($login);
    $aptdata = zb_AddressGetAptData($login);

    $useraddress = zb_AddressGetFulladdresslist();
    @$useraddress = $useraddress[$login];
    $buildid = $aptdata['buildid'];
    $builddata = zb_AddressGetBuildData($buildid);
    $buildnum = $builddata['buildnum'];
    $streetid = $builddata['streetid'];
    $streetdata = zb_AddressGetStreetData($streetid);
    $streetname = $streetdata['streetname'];
    $cityid = $streetdata['cityid'];
    $citydata = zb_AddressGetCityData($cityid);
    $cityname = $citydata['cityname'];

    $inputs = __('Full address') . ': ';
    $inputs.=wf_tag('b') . $useraddress . ' ' . wf_tag('b', true);
    $inputs.=__('Entrance');
    $inputs.=wf_TextInput('editentrance', '', @$aptdata['entrance'], false, '5');
    $inputs.=__('Floor');
    $inputs.=wf_TextInput('editfloor', '', @$aptdata['floor'], false, '5');
    $inputs.=__('Apartment');
    $inputs.=wf_TextInput('editapt', '', @$aptdata['apt'], false, '5');
    $inputs.=wf_JSAlert('?module=expresscard&username=' . $login . '&orphan=true', web_delete_icon(), __('Are you sure you want to make the homeless this user') . "?");
    //same data for passport apartment
    $inputs.=wf_HiddenInput('samepapt', $aptdata['apt']);
    $inputs.=wf_HiddenInput('samepbuild', $buildnum);
    $inputs.=wf_HiddenInput('samepstreet', $streetname);
    $inputs.=wf_HiddenInput('samepcity', $cityname);
    return($inputs);
}

/**
 * Return Ajax street selection box
 *  @param $cityid - city id
 * 
 * @return string
 */
function ajax_StreetSelector($cityid) {
    $cityid = vf($cityid, 3);
    $allstreets = zb_AddressGetStreetAllDataByCity($cityid);
    $streetbox = __('Street') . '<select id="streetbox" name="streetbox" onchange="var valuest = document.getElementById(\'streetbox\').value; goajax(\'?module=expresscard&ajaxbuild=\'+valuest,\'dbuildbox\');">';
    $streetbox.='<option value="99999">-</option>';
    if (!empty($allstreets)) {
        foreach ($allstreets as $io => $each) {
            $streetbox.='<option value="' . $each['id'] . '">' . $each['streetname'] . '</option>';
        }
    }
    $streetbox.='</select>';

    die($streetbox);
}

/**
 * Return Ajax build selection box
 * @param $streetid - street id
 * 
 * @return string
 */
function ajax_BuildSelector($streetid) {
    $streetid = vf($streetid, 3);
    $allbuild = zb_AddressGetBuildAllDataByStreet($streetid);
    $buildbox = __('Build') . '<select id="buildbox" name="buildbox" onchange="var valueb = document.getElementById(\'buildbox\').value;  goajax(\'?module=expresscard&ajaxapt=\'+valueb,\'daptbox\');">';
    $buildbox.='<option value="99999">-</option>';
    if (!empty($allbuild)) {
        foreach ($allbuild as $io => $each) {
            $buildbox.='<option value="' . $each['id'] . '">' . $each['buildnum'] . '</option>';
        }
    }
    $buildbox.='</select>';
    die($buildbox);
}

/**
 * Returns ajax apt creation form
 * 
 * @return string
 */
function ajax_AptCreationForm() {
    $inputs = wf_HiddenInput('createaddress', 'true');
    $inputs.=__('Entrance');
    $inputs.=wf_TextInput('createentrance', '', '', false, '5');
    $inputs.=__('Floor');
    $inputs.=wf_TextInput('createfloor', '', '', false, '5');
    $inputs.=__('Apartment');
    $inputs.=wf_TextInput('createapt', '', '', false, '5');
    die($inputs);
}

/**
 * Returns ajax ip proposal and control
 * 
 * @return void
 */
function ajax_IpEditForm($serviceid) {
    $serviceid = vf($serviceid, 3);
    @$ip_proposal = multinet_get_next_freeip('nethosts', 'ip', multinet_get_service_networkid($serviceid));
    $result = wf_TextInput('editip', '', $ip_proposal, false, '20');
    die($result);
}

/**
 * Returns new refresh-free form for user ocupancy
 * 
 * @return string
 */
function web_ExpressAddressOccupancyForm() {
    $allcities = zb_AddressGetCityAllData();
    $citybox = __('City') . '<select id="citybox" name="citybox" onchange="var valuec = document.getElementById(\'citybox\').value;  goajax(\'?module=expresscard&ajaxstreet=\'+valuec,\'dstreetbox\');">';
    $citybox.='<option value="99999">-</option> <!-- really ugly hack -->';
    if (!empty($allcities)) {
        foreach ($allcities as $ic => $each) {
            $citybox.='<option value="' . $each['id'] . '">' . $each['cityname'] . '</option>';
        }
    }
    $citybox.='</select>';



    $citybox.='<span id="dstreetbox"></span> <span id="dbuildbox"></span> <span id="daptbox"></span>';

    return ($citybox);
}

/**
 * Returns some hidden div - JUST OPEN TAG!
 * 
 * @return string
 */
function web_HidingDiv($id) {
    $result = wf_tag('div', false, '', 'style="display:block" id="' . $id . '"');
    return ($result);
}

/**
 * Returns some
 * 
 * @return string
 */
function web_PaddressUnhideBox() {
    $result = __('The same') . ' <input type="checkbox" id="custompaddress" name="custompaddress" onclick="showhide(\'paddress\');" />';
    return ($result);
}

/**
 * Tariff selector for Express box
 */
function web_ExpressTariffSelector($fieldname = 'tariffsel', $current = '') {
    $alltariffs = zb_TariffsGetAll();
    $selector = '<select name="' . $fieldname . '">';
    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            if ($current != $eachtariff['name']) {
                $selected = '';
            } else {
                $selected = 'SELECTED';
            }
            $selector.='<option value="' . $eachtariff['name'] . '" ' . $selected . '>' . $eachtariff['name'] . '</option>';
        }
    }
    $selector.='</select>';
    return($selector);
}

/**
 * Service selector for Express box
 */
function web_ExpressServiceSelector() {
    $allservices = multinet_get_services();

    $result = '<select name="serviceselect" id="serviceselect" onchange="var valueserv = document.getElementById(\'serviceselect\').value;  goajax(\'?module=expresscard&ajaxip=\'+valueserv,\'dipbox\');">';
    $result.='<option value="SAME">' . __('Current') . '</option>';
    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            $result.='<option value="' . $eachservice['id'] . '">' . $eachservice['desc'] . '</option>';
        }
    }
    $result.='</select>';
    return ($result);
}

/**
 * Service selector for Express register box
 */
function web_ExpressServiceSelectorReg() {
    $allservices = multinet_get_services();

    $result = '<select name="serviceselect" id="serviceselect" onchange="var valueserv = document.getElementById(\'serviceselect\').value;  goajax(\'?module=expresscard&ajaxip=\'+valueserv,\'dipbox\');">';
    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            $result.='<option value="' . $eachservice['id'] . '">' . $eachservice['desc'] . '</option>';
        }
    }
    $result.='</select>';

    return ($result);
}

/**
 * Shows editing form of express card for some login
 * 
 * @param $login - user login
 * 
 * @return string
 */
function web_ExpressCardEditForm($login) {

    $contract = zb_UserGetContract($login);
    $allcontractdates = zb_UserContractDatesGetAll($contract);
    $realname = zb_UserGetRealName($login);
    $phone = zb_UserGetPhone($login);
    $mobile = zb_UserGetMobile($login);
    $email = zb_UserGetEmail($login);
    $passportdata = zb_UserPassportDataGet($login);
    $addressdata = zb_AddressGetAptData($login);
    $currentip = zb_UserGetIP($login);
    $mac = zb_MultinetGetMAC($currentip);
    $notes = zb_UserGetNotes($login);
    $stgdata = zb_UserGetStargazerData($login);
    $currenttariff = $stgdata['Tariff'];


    //extracting passport data
    if (!empty($passportdata)) {
        $birthdate = $passportdata['birthdate'];
        $passportnum = $passportdata['passportnum'];
        $passportdate = $passportdata['passportdate'];
        $passportwho = $passportdata['passportwho'];
        $pcity = $passportdata['pcity'];
        $pstreet = $passportdata['pstreet'];
        $pbuild = $passportdata['pbuild'];
        $papt = $passportdata['papt'];
        $pinn = $passportdata['pinn'];
    } else {
        $birthdate = '';
        $passportnum = '';
        $passportdate = '';
        $passportwho = '';
        $pcity = '';
        $pstreet = '';
        $pbuild = '';
        $papt = '';
        $pinn = '';
    }

    ///extracting realname to 3 different fields
    $nm = explode(' ', $realname);
    @$rnm_f = $nm[0];
    @$rnm_i = $nm[1];
    @$rnm_o = $nm[2];
    /*
     * эту формочку нужно поровнять
     */

    $inputs = zb_AjaxLoader() . wf_delimiter();
    $inputs.=__('Contract');
    $inputs.= wf_TextInput('editcontract', '', $contract, false, '10');
    $inputs.=__('Contract date');
    $inputs.=wf_DatePickerPreset('editcontractdate', @$allcontractdates[$contract]);
    $inputs.=wf_delimiter();

    $inputs.=__('Surname');
    $inputs.=wf_TextInput('editsurname', '', $rnm_f, false, '20');
    $inputs.=__('Name');
    $inputs.=wf_TextInput('editname', '', $rnm_i, false, '20');
    $inputs.=__('Patronymic');
    $inputs.=wf_TextInput('editpatronymic', '', $rnm_o, false, '20');
    $inputs.=__('Birth date');
    $inputs.=wf_DatePickerPreset('editbirthdate', $birthdate);
    $inputs.=wf_delimiter();

    $inputs.=__('Passport number');
    $inputs.=wf_TextInput('editpassportnum', '', $passportnum, false, '30');
    $inputs.=__('Date of issue');
    $inputs.=wf_DatePickerPreset('editpassportdate', $passportdate);
    $inputs.=__('Issuing authority');
    $inputs.=wf_TextInput('editpassportwho', '', $passportwho, false, '40');
    $inputs.=wf_delimiter();

    $inputs.=__('Identification code');
    $inputs.=wf_TextInput('editpinn', '', $pinn, false, '10');
    $inputs.=wf_delimiter();

    $inputs.=__('Phone');
    $inputs.=wf_TextInput('editphone', '', $phone, false, '20');
    $inputs.=__('Mobile');
    $inputs.=wf_TextInput('editmobile', '', $mobile, false, '20');
    $inputs.=__('email');
    $inputs.=wf_TextInput('editemail', '', $email, false, '20');
    $inputs.=wf_delimiter();

    $inputs.=wf_tag('fieldset');
    //address data form
    $inputs.=__('Address of service') . ' ';
    if (!empty($addressdata)) {
        //if user have existing address - modify form
        $inputs.=web_ExpressAddressAptForm($login);
    } else {
        //new address creation form
        $inputs.=web_ExpressAddressOccupancyForm();
    }
    $inputs.=wf_delimiter();


    //additional address fields
    $inputs.=__('Registration address') . ' ';
    $inputs.=zb_JSHider();
    $inputs.=web_PaddressUnhideBox();

    $inputs.=web_HidingDiv('paddress');
    $inputs.=__('City');
    $inputs.=wf_TextInput('editpcity', '', $pcity, false, '20');
    $inputs.=__('Street');
    $inputs.=wf_TextInput('editpstreet', '', $pstreet, false, '20');
    $inputs.=__('Build');
    $inputs.=wf_TextInput('editpbuild', '', $pbuild, false, '5');
    $inputs.=__('Apartment');
    $inputs.=wf_TextInput('editpapt', '', $papt, false, '5');
    $inputs.=wf_tag('div', true);
    $inputs.=wf_tag('fieldset', true);

    $inputs.=wf_delimiter();

    $inputs.=__('Tariff');
    $inputs.=web_ExpressTariffSelector('edittariff', $currenttariff);
    $inputs.=__('Service');
    $inputs.=web_ExpressServiceSelector();
    $inputs.=__('IP');
    $inputs.=wf_tag('span', false, '', 'id="dipbox"');
    $inputs.=wf_TextInput('editip', '', $currentip, false, '20');
    $inputs.=wf_tag('span', true);

    $inputs.=__('MAC');
    $inputs.=wf_TextInput('editmac', '', $mac, false, '20');
    $inputs.=wf_delimiter();

    $inputs.=__('Notes');
    $inputs.=wf_TextInput('editnotes', '', $notes, false, '120');
    $inputs.=wf_HiddenInput('expresscardedit', 'true');
    $inputs.=wf_delimiter();
    $inputs.=wf_Submit('Save');

    $expresscardform = wf_Form("", "POST", $inputs, 'expresscard');
    show_window(__('Express card user edit'), $expresscardform);
}

//        
// express register features
//        

/**
 * Shows user register form of express card 
 * 
 * 
 * @return string
 */
function web_ExpressCardRegForm() {
    $altconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");



    $allcontracts = zb_UserGetAllContracts();
    //contract proposal
    $top_offset = 100000;
    //contract generation mode default
    if ($altconf['CONTRACT_GENERATION_DEFAULT']) {
        for ($i = 1; $i < $top_offset; $i++) {
            if (!isset($allcontracts[$i])) {
                $contract = $i;
                break;
            }
        }
    } else {
        //alternate generation method
        $max_contract = max(array_keys($allcontracts));
        $contract = $max_contract + 1;
    }



    $mac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);

    $phone = '';
    $mobile = '';
    $email = '';
    $notes = '';
    $stgdata = '';
    $currenttariff = '';
    $birthdate = '';
    $passportnum = '';
    $passportdate = '';
    $passportwho = '';
    $pcity = '';
    $pstreet = '';
    $pbuild = '';
    $papt = '';
    $pinn = '';



    $inputs = zb_AjaxLoader() . wf_delimiter();
    $inputs.=__('Contract');
    $inputs.= wf_TextInput('newcontract', '', $contract, false, '10');
    $inputs.=__('Contract date');
    $inputs.=wf_DatePickerPreset('newcontractdate', @$allcontractdates[$contract]);
    $inputs.=wf_delimiter();

    $inputs.=__('Surname');
    $inputs.=wf_TextInput('newsurname', '', '', false, '20');
    $inputs.=__('Name');
    $inputs.=wf_TextInput('newname', '', '', false, '20');
    $inputs.=__('Patronymic');
    $inputs.=wf_TextInput('newpatronymic', '', '', false, '20');
    $inputs.=__('Birth date');
    $inputs.=wf_DatePickerPreset('newbirthdate', $birthdate);
    $inputs.=wf_delimiter();

    $inputs.=__('Passport number');
    $inputs.=wf_TextInput('newpassportnum', '', $passportnum, false, '30');
    $inputs.=__('Date of issue');
    $inputs.=wf_DatePickerPreset('newpassportdate', $passportdate);
    $inputs.=__('Issuing authority');
    $inputs.=wf_TextInput('newpassportwho', '', $passportwho, false, '40');
    $inputs.=wf_delimiter();

    $inputs.=__('Identification code');
    $inputs.=wf_TextInput('newpinn', '', $pinn, false, '10');

    $inputs.=__('Phone');
    $inputs.=wf_TextInput('newphone', '', $phone, false, '20');
    $inputs.=__('Mobile');
    $inputs.=wf_TextInput('newmobile', '', $mobile, false, '20');
    $inputs.=__('email');
    $inputs.=wf_TextInput('newemail', '', $email, false, '20');
    $inputs.=wf_delimiter();

    $inputs.=wf_tag('fieldset');
    //address data form
    $inputs.=__('Address of service') . ' ';

    //new address creation form
    $inputs.=web_ExpressAddressOccupancyForm();

    $inputs.=wf_delimiter();


    //additional address fields
    $inputs.=__('Registration address') . ' ';
    $inputs.=zb_JSHider();
    $inputs.=web_PaddressUnhideBox();

    $inputs.=web_HidingDiv('paddress');
    $inputs.=__('City');
    $inputs.=wf_TextInput('newpcity', '', $pcity, false, '20');
    $inputs.=__('Street');
    $inputs.=wf_TextInput('newpstreet', '', $pstreet, false, '20');
    $inputs.=__('Build');
    $inputs.=wf_TextInput('newpbuild', '', $pbuild, false, '5');
    $inputs.=__('Apartment');
    $inputs.=wf_TextInput('newpapt', '', $papt, false, '5');
    $inputs.=wf_tag('div', true);
    $inputs.=wf_tag('fieldset', true);

    $inputs.=wf_delimiter();

    $inputs.=__('Tariff');
    $inputs.=web_ExpressTariffSelector('newtariff', $currenttariff);
    $inputs.=__('Service');
    $inputs.=web_ExpressServiceSelectorReg();
    $inputs.=__('IP');
    $inputs.=wf_tag('span', false, '', 'id="dipbox"');
    $allservices = multinet_get_services();
    ;
    if (!empty($allservices)) {
        $firstService = $allservices[0];
        $firstNet = $firstService['netid'];
        @$ip_proposal = multinet_get_next_freeip('nethosts', 'ip', $firstNet);
        if (empty($ip_proposal)) {
            show_window('', wf_modalOpened(__('Error'), __('No free IP available in selected pool'), '400', '250'));
        }
    } else {
        $ip_proposal = __('Error');
    }

    $inputs.=wf_TextInput('editip', '', $ip_proposal, false, '20');
    $inputs.=wf_tag('span', true);
    //dummy login proposal
    $login = zb_RegLoginProposal('', '', '', '', $ip_proposal);

    $inputs.=__('MAC');
    $inputs.=wf_TextInput('newmac', '', $mac, false, '20');
    $inputs.=__('Login');
    $inputs.=wf_TextInput('newlogin', '', $login, false, '20');

    $inputs.=wf_delimiter();

    $inputs.=__('Notes');
    $inputs.=wf_TextInput('newnotes', '', $notes, false, '120');
    $inputs.=wf_HiddenInput('expresscardreg', 'true');
    $inputs.=wf_delimiter();
    $inputs.=wf_Submit('Let register that user');

    $expresscardform = wf_Form("", "POST", $inputs, 'expresscard');
    show_window(__('Express card user register'), $expresscardform);
}

/**
 * Passport data editing form
 * 
 * @param $login - user login
 * @param $passportdata - user passport data array
 * 
 * @return void
 * 
 */
function web_PassportDataEditFormshow($login, $passportdata) {
    $alladdress = zb_AddressGetFulladdresslist();
    @$useraddress = $alladdress[$login];

    //extracting passport data
    if (!empty($passportdata)) {
        $birthdate = $passportdata['birthdate'];
        $passportnum = $passportdata['passportnum'];
        $passportdate = $passportdata['passportdate'];
        $passportwho = $passportdata['passportwho'];
        $pcity = $passportdata['pcity'];
        $pstreet = $passportdata['pstreet'];
        $pbuild = $passportdata['pbuild'];
        $papt = $passportdata['papt'];
        $pinn = $passportdata['pinn'];
    } else {
        $birthdate = '';
        $passportnum = '';
        $passportdate = '';
        $passportwho = '';
        $pcity = '';
        $pstreet = '';
        $pbuild = '';
        $papt = '';
        $pinn = '';
    }

    //form construction
    $inputs = wf_tag('h3') . __('Passport data') . wf_tag('h3', true);
    $inputs.=wf_DatePickerPreset('editbirthdate', $birthdate, true);
    $inputs.=__('Birth date');
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('editpassportnum', __('Passport number'), $passportnum, false, '35');
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('editpassportwho', __('Issuing authority'), $passportwho, false, '35');
    $inputs.=wf_delimiter();
    $inputs.=wf_DatePickerPreset('editpassportdate', $passportdate, true);
    $inputs.=__('Date of issue');
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('editpinn', __('Identification code'), $pinn, false, '10');
    $inputs.=wf_delimiter();

    $inputs.= wf_tag('h3') . __('Registration address') . wf_tag('h3', true);
    $inputs.=wf_TextInput('editpcity', __('City'), $pcity, false, '20');
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('editpstreet', __('Street'), $pstreet, false, '20');
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('editpbuild', __('Build'), $pbuild, false, '5');
    $inputs.=wf_delimiter();
    $inputs.=wf_TextInput('editpapt', __('Apartment'), $papt, false, '5');
    $inputs.=wf_delimiter();
    $inputs.=wf_Submit(__('Save'));

    $form = wf_Form('', 'POST', $inputs, 'glamour');
    show_window(__('Edit') . ' ' . __('passport data') . ' ' . $useraddress, $form);
}

/**
 * Returns users passport data 
 * 
 * @param string $login
 * @return string
 */
function web_UserPassportDataShow($login) {
    $login = mysql_real_escape_string($login);
    $passportdata = zb_UserPassportDataGet($login);
    if (!empty($passportdata)) {
        $cells = wf_TableCell(__('Birth date'));
        $cells.= wf_TableCell($passportdata['birthdate']);
        $rows = wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Passport number'));
        $cells.= wf_TableCell($passportdata['passportnum']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Issuing authority'));
        $cells.= wf_TableCell($passportdata['passportwho']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Date of issue'));
        $cells.= wf_TableCell($passportdata['passportdate']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Identification code'));
        $cells.= wf_TableCell($passportdata['pinn']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Registration address'));
        $cells.= wf_TableCell($passportdata['pcity'] . ' ' . $passportdata['pstreet'] . ' ' . $passportdata['pbuild'] . '/' . $passportdata['papt']);
        $rows.= wf_TableRow($cells, 'row3');

        $result = wf_TableBody($rows, '100%', '0');
    } else {
        $result = __('User passport data is empty') . ' ' . __('You can fill them with the appropriate module');
    }

    if (cfr('PDATA')) {
        $result.=wf_delimiter();
        $result.=wf_Link("?module=pdataedit&username=" . $login, __('Edit') . ' ' . __('passport data'), false, 'ubButton');
    }
    return ($result);
}

?>

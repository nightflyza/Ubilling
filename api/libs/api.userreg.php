<?php

/**
 * Returns random alpha-numeric string of some lenght
 * 
 * @param int $size
 * @return string
 */
function zb_rand_string($size = 4) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
    }

    return ($string);
}

/**
 * Returns random numeric string of some lenght
 * 
 * @param int $size
 * @return string
 */
function zb_rand_digits($size = 4) {
    $characters = '0123456789';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
    }

    return ($string);
}

/**
 * Returns array of apartments located in some build
 * 
 * @param int $buildid
 * @return array
 */
function zb_AddressGetBuildApts($buildid) {
    $buildid = vf($buildid, 3);
    $query = "SELECT * from `apt` WHERE `buildid`='" . $buildid . "'";
    $allapts = simple_queryall($query);
    $result = array();
    if (!empty($allapts)) {
        foreach ($allapts as $io => $each) {
            $result[$each['apt']] = $each['id'];
        }
    }
    return ($result);
}

/**
 * Returns array of apartments located in some build
 * 
 * @param int $buildid
 * @return array
 */
function zb_AddressGetBuildAptIds($buildid) {
    $buildid = vf($buildid, 3);
    $query = "SELECT `id`,`apt` from `apt` WHERE `buildid`='" . $buildid . "'";
    $allapts = simple_queryall($query);
    $result = array();
    if (!empty($allapts)) {
        foreach ($allapts as $io => $each) {
            $result[$each['id']] = $each['apt'];
        }
    }
    return ($result);
}

/**
 * Returns apt ocupancy check javascript code
 * 
 * @param int $buildid
 * @param string $apt
 * @param string $login
 * 
 * @return void/string on error
 */
function web_AddressBuildShowAptsCheck($buildid, $apt = '', $login = '') {
    $result = '';
    $buildid = vf($buildid, 3);

    $messages = new UbillingMessageHelper();
    if (empty($apt)) {
        $result .= $messages->getStyledMessage(__('Are you sure you want to keep the homeless from this user'), 'warning');
    } else {
        $allapts = zb_AddressGetBuildAptIds($buildid);
        $someoneLiveHere = false;
        $busyApts = array(); //IDs of apts which is busy
        if (!empty($allapts)) {
            foreach ($allapts as $aptid => $aptnum) {
                if (!empty($aptnum)) {
                    if ($aptnum == $apt) {
                        $someoneLiveHere = true;
                        $busyApts[$aptid] = $aptnum;
                    }
                }
            }
        }
        //display of users which lives in this apt
        if ($someoneLiveHere) {
            $result .= $messages->getStyledMessage(__('The apartment has one lives, we have nothing against, just be warned'), 'warning');
            $allAddress = zb_AddressGetAddressAllData();

            if (!empty($allAddress)) {
                $similarAddressUsers = array();
                foreach ($allAddress as $io => $each) {
                    if (isset($busyApts[$each['aptid']])) {
                        if ($each['login'] != $login) {
                            $similarAddressUsers[$each['login']] = $each['login'];
                        }
                    }
                }
                $result .= web_UserArrayShower($similarAddressUsers);
            }
        }
    }
    //modal window width control
    if (!empty($result)) {
        $result .= wf_tag('div', false, '', 'style="width:900px;"') . wf_tag('div', true);
    }
    return ($result);
}

/**
 * Returns wizard-like new user location form
 * 
 * @return string
 */
function web_UserRegFormLocation() {
    global $registerSteps;
    global $ubillingConfig;

    $aptsel = '';
    $servicesel = '';
    $currentStep = 0;
    if (!isset($_POST['citysel'])) {
        $citysel = web_CitySelectorAc(); // onChange="this.form.submit();
        $streetsel = '';
    } else {
        $citydata = zb_AddressGetCityData($_POST['citysel']);
        $citysel = $citydata['cityname'] . wf_HiddenInput('citysel', $citydata['id']);
        $streetsel = web_StreetSelectorAc($citydata['id']);
        $currentStep = 1;
    }

    if (isset($_POST['streetsel'])) {
        $streetdata = zb_AddressGetStreetData($_POST['streetsel']);
        $streetsel = $streetdata['streetname'] . wf_HiddenInput('streetsel', $streetdata['id']);
        $buildsel = web_BuildSelectorAc($_POST['streetsel']);
        $currentStep = 2;
    } else {
        $buildsel = '';
    }

    if (isset($_POST['buildsel'])) {
        $submit_btn = '';
        $builddata = zb_AddressGetBuildData($_POST['buildsel']);
        $buildsel = $builddata['buildnum'] . wf_HiddenInput('buildsel', $builddata['id']);
        $aptsel = web_AptCreateForm();
        $servicesel = multinet_service_selector();
        $currentStep = 3;
//contrahens user diff
        $alter_conf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
        if (isset($alter_conf['LOGIN_GENERATION'])) {
            if ($alter_conf['LOGIN_GENERATION'] == 'DEREBAN') {
                $agentCells = wf_TableCell(zb_RegContrAhentSelect('regagent', $alter_conf['DEFAULT_ASSIGN_AGENT']));
                $agentCells .= wf_TableCell(__('Contrahent name'));
                $submit_btn .= wf_TableRow($agentCells, 'row2');
            }
        }

        $submit_btn .= wf_tag('tr', false, 'row3');
        $submit_btn .= wf_tag('td', false);
        $submit_btn .= wf_Submit(__('Save'));
        $submit_btn .= wf_tag('td', true);
        $submit_btn .= wf_tag('td', false);
        $submit_btn .= wf_tag('td', true);
        $submit_btn .= wf_tag('tr', true);
    } else {
        $submit_btn = '';
    }


    $formInputs = wf_tag('tr', false, 'row3');
    $formInputs .= wf_tag('td', false, '', 'width="50%"') . $citysel . wf_tag('td', true);
    $formInputs .= wf_tag('td', false) . __('City') . wf_tag('td', true);
    $formInputs .= wf_tag('tr', true);

    $formInputs .= wf_tag('tr', false, 'row3');
    $formInputs .= wf_tag('td', false) . $streetsel . wf_tag('td', true);
    $formInputs .= wf_tag('td', false) . __('Street') . wf_tag('td', true);
    $formInputs .= wf_tag('tr', true);

    $formInputs .= wf_tag('tr', false, 'row3');
    $formInputs .= wf_tag('td', false) . $buildsel . wf_tag('td', true);
    $formInputs .= wf_tag('td', false) . __('Build') . wf_tag('td', true);
    $formInputs .= wf_tag('tr', true);

    $formInputs .= wf_tag('tr', false, 'row3');
    $formInputs .= wf_tag('td', false) . $aptsel . wf_tag('td', true);
    $formInputs .= wf_tag('td', false) . __('Apartment') . wf_tag('td', true);
    $formInputs .= wf_tag('tr', true);

    $formInputs .= wf_tag('tr', false, 'row3');
    $formInputs .= wf_tag('td', false) . $servicesel . wf_tag('td', true);
    $formInputs .= wf_tag('td', false) . __('Service') . wf_tag('td', true);
    $formInputs .= wf_tag('tr', true);
    $formInputs .= wf_tag('br');
    $formInputs .= $submit_btn;

    $formData = wf_Form('', 'POST', $formInputs);
    $form = wf_TableBody($formData, '100%', '0', 'glamour');
    $form .= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
    $form .= wf_StepsMeter($registerSteps, $currentStep);
    return($form);
}

/**
 * Returns array of all already busy user logins
 * 
 * @return array
 */
function zb_AllBusyLogins() {
    $query = "SELECT `login` from `users`";
    $alluserlogins = simple_queryall($query);
    $result = array();
    if (!empty($alluserlogins)) {
        foreach ($alluserlogins as $io => $each) {
            $result[$each['login']] = $each['login'];
        }
    }
    return ($result);
}

/**
 * Filters user login for only allowed symbols
 * 
 * @param string $login
 * @return string
 */
function zb_RegLoginFilter($login) {
    $login = str_replace(' ', '_', $login);
    $result = preg_replace("#[^a-z0-9A-Z_]#Uis", '', $login);
    return ($result);
}

/**
 * Returns new user login proposal by some params
 * 
 * @param string $cityalias
 * @param string $streetalias
 * @param string $buildnum
 * @param string $apt
 * @param string $ip_proposal
 * @param int    $agentid
 * @return string
 */
function zb_RegLoginProposal($cityalias, $streetalias, $buildnum, $apt, $ip_proposal, $agentid = '') {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $result = '';
    if (isset($alterconf['LOGIN_GENERATION'])) {
        $type = $alterconf['LOGIN_GENERATION'];
//default address based generation
        if ($type == 'DEFAULT') {
            $result = $cityalias . $streetalias . $buildnum . 'ap' . $apt . '_' . zb_rand_string();
            $result = zb_RegLoginFilter($result);
        }

//same as default but without random
        if ($type == 'ONLYADDRESS') {
            $result = $cityalias . $streetalias . $buildnum . 'ap' . $apt;
            $result = zb_RegLoginFilter($result);
        }

//use an timestamp
        if ($type == 'TIMESTAMP') {
            $result = time();
        }

//use an timestamp md5 hash
        if ($type == 'TIMESTAMPMD5') {
            $result = md5(time());
        }

//use an next incremented number
        if ($type == 'INCREMENT') {
            $busylogins = zb_AllBusyLogins();
            for ($i = 1; $i < 100000; $i++) {
                if (!isset($busylogins[$i])) {
                    $result = $i;
                    break;
                }
            }
        }

//use four digits increment with zero prefix
        if ($type == 'INCREMENTFOUR') {
            $busylogins = zb_AllBusyLogins();
            $prefixSize = 4;
            for ($i = 1; $i < 100000; $i++) {
                $nextIncrementProposal = sprintf('%0' . $prefixSize . 'd', $i);
                if (!isset($busylogins[$nextIncrementProposal])) {
                    $result = $nextIncrementProposal;
                    break;
                }
            }
        }

//use five five digits increment with zero prefix
        if ($type == 'INCREMENTFIVE') {
            $busylogins = zb_AllBusyLogins();
            $prefixSize = 5;
            for ($i = 1; $i < 100000; $i++) {
                $nextIncrementProposal = sprintf('%0' . $prefixSize . 'd', $i);
                if (!isset($busylogins[$nextIncrementProposal])) {
                    $result = $nextIncrementProposal;
                    break;
                }
            }
        }

//use six digits increment with zero prefix
        if ($type == 'INCREMENTSIX') {
            $busylogins = zb_AllBusyLogins();
            $prefixSize = 6;
            for ($i = 1; $i < 1000000; $i++) {
                $nextIncrementProposal = sprintf('%0' . $prefixSize . 'd', $i);
                if (!isset($busylogins[$nextIncrementProposal])) {
                    $result = $nextIncrementProposal;
                    break;
                }
            }
        }

//use four digits increment
        if ($type == 'INCREMENTFOURREV') {
            $busylogins = zb_AllBusyLogins();
            $prefixSize = 4;
            for ($i = 1; $i < 100000; $i++) {
//$nextIncrementRevProposal = sprintf('%0' . $prefixSize . 'd', $i);
//$nextIncrementRevProposal = strrev($nextIncrementRevProposal);
                $nextIncrementRevProposal = sprintf('%-0' . $prefixSize . 's', $i);
                if (!isset($busylogins[$nextIncrementRevProposal])) {
                    $result = $nextIncrementRevProposal;
                    break;
                }
            }
        }

//use five digits increment
        if ($type == 'INCREMENTFIVEREV') {
            $busylogins = zb_AllBusyLogins();
            $prefixSize = 5;
            for ($i = 1; $i < 100000; $i++) {
//$nextIncrementRevProposal = sprintf('%0' . $prefixSize . 'd', $i);
//$nextIncrementRevProposal = strrev($nextIncrementRevProposal);
                $nextIncrementRevProposal = sprintf('%-0' . $prefixSize . 's', $i);
                if (!isset($busylogins[$nextIncrementRevProposal])) {
                    $result = $nextIncrementRevProposal;
                    break;
                }
            }
        }

//use six digits increment
        if ($type == 'INCREMENTSIXREV') {
            $busylogins = zb_AllBusyLogins();
            $prefixSize = 6;
            for ($i = 1; $i < 1000000; $i++) {
//$nextIncrementRevProposal = sprintf('%0' . $prefixSize . 'd', $i);
//$nextIncrementRevProposal = strrev($nextIncrementRevProposal);
                $nextIncrementRevProposal = sprintf('%-0' . $prefixSize . 's', $i);
                if (!isset($busylogins[$nextIncrementRevProposal])) {
                    $result = $nextIncrementRevProposal;
                    break;
                }
            }
        }

//use an proposed IP last two octets
        if ($type == 'IPBASED') {
            $ip_tmp = str_replace('.', 'x', $ip_proposal);
            $result = $ip_tmp;
        }

//use an proposed IP last two octets
        if ($type == 'IPBASEDLAST') {
            $ip_tmp = explode('.', $ip_proposal);
            if (($ip_tmp[2] < 100) AND ( $ip_tmp[2] >= 10)) {
                $ip_tmp[2] = '0' . $ip_tmp[2];
            }
            if (($ip_tmp[3] < 100) AND ( $ip_tmp[3] >= 10)) {
                $ip_tmp[3] = '0' . $ip_tmp[3];
            }
            if ($ip_tmp[2] < 10) {
                $ip_tmp[2] = '00' . $ip_tmp[2];
            }
            if ($ip_tmp[3] < 10) {
                $ip_tmp[3] = '00' . $ip_tmp[3];
            }

            $result = $ip_tmp[2] . $ip_tmp[3];
        }

// just random string as login
        if ($type == 'RANDOM') {
            $result = zb_rand_string(10);
        }

// 8 random digits
        if ($type == 'RANDOM8') {
            $result = zb_rand_digits(8);
        }

// 4 random digits - yeah, shoot that fucking leg
        if ($type == 'RANDOM4') {
            $result = zb_rand_digits();
        }

// just random string as login
        if ($type == 'RANDOMSAFE') {
            $randomStringProposal = zb_rand_string(10);
            $filteredChars = array('q', 'Q', 'i', 'I', 'l', 'L', 'j', 'J', 'o', 'O', '1', '0', 'g', 'G');
            $result = str_replace($filteredChars, 'x', $randomStringProposal);
        }

//contrahent based model
        if ($type == 'DEREBAN') {
            $busylogins = zb_AllBusyLogins();
            $agentPrefix = $agentid;
            $prefixSize = 6;
            for ($i = 1; $i < 1000000; $i++) {
                $nextIncrementDerProposal = $agentPrefix . sprintf('%0' . $prefixSize . 'd', $i);
                if (!isset($busylogins[$nextIncrementDerProposal])) {
                    $result = $nextIncrementDerProposal;
                    break;
                }
            }
        }

//Like DEFAULT but increment in the end. Increment counter unique per every alias.
//So it can be unique for city, or for city + every street if alias for street was set.
        if ($type == 'VSRAT_INCREMENT') {
            $busylogins = zb_AllBusyLogins();
            for ($i = 1; $i < 100000; $i++) {
                $proposal = $cityalias . $streetalias . '_' . $i;
                if (!isset($busylogins[$proposal])) {
                    $result = $proposal;
                    break;
                }
            }
        }


/////  if wrong option - use DEFAULT
        if (empty($result)) {
            $result = $cityalias . $streetalias . $buildnum . 'ap' . $apt . '_' . zb_rand_string();
            $result = zb_RegLoginFilter($result);
        }
    } else {
        die(strtoupper('you have missed a essential option. before update read release notes motherfucker!'));
    }

    return ($result);
}

/**
 * Returns new user password proposal
 * 
 * @return string
 */
function zb_RegPasswordProposal() {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    if ((isset($alterconf['PASSWORD_GENERATION_LENGHT'])) AND ( isset($alterconf['PASSWORD_TYPE']))) {
        if ($alterconf['PASSWORD_TYPE']) {
            $password = zb_rand_string($alterconf['PASSWORD_GENERATION_LENGHT']);
        } else {
            $password = zb_rand_digits($alterconf['PASSWORD_GENERATION_LENGHT']);
        }
    } else {
        die(strtoupper('you have missed a essential option. before update read release notes motherfucker!'));
    }
    return ($password);
}

/**
 * Returns alert if generated user login have rscripd incompatible lenght
 * 
 * @param string $login
 * @return string
 */
function zb_CheckLoginRscriptdCompat($login) {
    $maxRsLen = 31;
    $maxStLen = 42;
    $loginLen = strlen($login);
    if (($loginLen >= $maxRsLen)) {
//rscriptd notice
        if ($loginLen < $maxStLen) {
            $alert = __('Attention generated login longer than') . ' ' . $maxRsLen . ' ' . __('bytes') . '. (' . $loginLen . ') ' . __('This can lead to the inability to manage this user on remote NAS running rscriptd') . '. ';
            $alert .= __('Perhaps you need to shorten the alias, or use a different model for the generation of logins') . '.';
            $result = wf_modalOpened(__('Warning'), $alert, '500', '200');
        }
//stargazer incompat notice
        if ($loginLen >= $maxStLen) {
            $alert = __('Attention generated login longer than') . ' ' . $maxStLen . ' ' . __('bytes') . '. (' . $loginLen . ') ' . __('And is not compatible with Stargazer') . '. ';
            $alert .= __('Perhaps you need to shorten the alias, or use a different model for the generation of logins') . '.';
            $result = wf_modalOpened(__('Error'), $alert, '500', '200');
        }
    } else {
        $result = '';
    }
    return ($result);
}

/**
 * Returns register last step form
 * 
 * @param array $newuser_data
 * @return string
 */
function web_UserRegFormNetData($newuser_data) {
    global $registerSteps;
    $currentStep = 4;
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    if ($alterconf['BRANCHES_ENABLED']) {
        global $branchControl;
    }
    $safe_mode = $alterconf['SAFE_REGMODE'];
    $citydata = zb_AddressGetCityData($newuser_data['city']);
    $cityalias = zb_TranslitString($citydata['cityalias']);
    $streetdata = zb_AddressGetStreetData($newuser_data['street']);
    $streetalias = zb_TranslitString($streetdata['streetalias']);
    $buildata = zb_AddressGetBuildData($newuser_data['build']);
    $buildnum = zb_TranslitString($buildata['buildnum']);
    if (empty($newuser_data['apt'])) {
        $newuser_data['apt'] = 0;
    }
    $apt = zb_TranslitString($newuser_data['apt']);
//assign some agent from previously selected form
    if (isset($alterconf['LOGIN_GENERATION'])) {
        if ($alterconf['LOGIN_GENERATION'] == 'DEREBAN') {
            $agentPrefixID = $newuser_data['contrahent'];
        } else {
            $agentPrefixID = '';
        }
    } else {
        $agentPrefixID = '';
    }

    $ip_proposal = multinet_get_next_freeip('nethosts', 'ip', multinet_get_service_networkid($newuser_data['service']));
    $login_proposal = zb_RegLoginProposal($cityalias, $streetalias, $buildnum, $apt, $ip_proposal, $agentPrefixID);
    $password_proposal = zb_RegPasswordProposal();


    if (empty($ip_proposal)) {
        $alert = wf_tag('script', false, '', 'type="text/javascript"');
        $alert .= 'alert("' . __('Error') . ': ' . __('No free IP available in selected pool') . '");';
        $alert .= wf_tag('script', true);
        print($alert);
        rcms_redirect("?module=multinet");
        die();
    }

//protect important options
    if ($safe_mode) {
        $modifier = 'READONLY';
    } else {
        $modifier = '';
    }
    $form = '';
    $addressCheck = web_AddressBuildShowAptsCheck($buildata['id'], $apt);
    if (!empty($addressCheck)) {
        $form .= wf_modalOpenedAuto(__('Warning'), $addressCheck, '800', '300');
    }
    $form .= wf_tag('table', false, 'glamour', 'width="100%" border="0"');
    $form .= wf_tag('form', false, '', ' action="" method="POST"');

    $form .= wf_tag('tr', false, 'row3');
    $form .= wf_tag('td', false, '', 'width="65%"');
    $form .= wf_tag('input', false, '', 'type="text" name="login" value="' . $login_proposal . '" ' . $modifier);
    $form .= wf_tag('td', true);
    $form .= wf_tag('td', false);
    $form .= __('Login') . ' ' . zb_CheckLoginRscriptdCompat($login_proposal);
    $form .= wf_tag('td', true);
    $form .= wf_tag('tr', true);

    $form .= wf_tag('tr', false, 'row3');
    $form .= wf_tag('td', false);
    $form .= wf_tag('input', false, '', 'type="text" name="password" value="' . $password_proposal . '" ' . $modifier);
    $form .= wf_tag('td', true);
    $form .= wf_tag('td', false);
    $form .= __('Password');
    $form .= wf_tag('td', true);
    $form .= wf_tag('tr', true);

    $form .= wf_tag('tr', false, 'row3');
    $form .= wf_tag('td', false);
    $ipPattern = 'pattern="^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$" placeholder="0.0.0.0" title="' . __('The IP address format can be') . ': 192.1.1.1"';
    $form .= wf_tag('input', false, '', 'type="text" name="IP" value="' . $ip_proposal . '" ' . $ipPattern . ' ' . $modifier);
    $form .= wf_tag('td', true);
    $form .= wf_tag('td', false);
    $form .= __('IP');
    $form .= wf_tag('td', true);
    $form .= wf_tag('tr', true);

    if (isset($alterconf['USERREG_MAC_INPUT_ENABLED']) and $alterconf['USERREG_MAC_INPUT_ENABLED']) {
        $form .= wf_tag('tr', false, 'row3');
        $form .= wf_tag('td', false);
        $form .= wf_tag('input', false, '', 'type="text" name="userMAC"');
        $form .= wf_tag('td', true);
        $form .= wf_tag('td', false);
        $form .= __('MAC');
        $form .= wf_tag('td', true);
        $form .= wf_tag('tr', true);
    }

    if ($alterconf['BRANCHES_ENABLED']) {
        $form .= wf_tag('tr', false, 'row3');
        $form .= wf_tag('td', false);
        $form .= $branchControl->branchSelector('reguserbranchid') . ' ';
        if ((!cfr('BRANCHES')) OR ( cfr('ROOT'))) {
            $form .= wf_CheckInput('reguserwithnobranch', __('Register user with no branch'), false, true);
        }
        $form .= wf_tag('td', true);
        $form .= wf_tag('td', false);
        $form .= __('Branch');
        $form .= wf_tag('td', true);
        $form .= wf_tag('tr', true);
    }

    if (@$alterconf['ONUAUTO_USERREG']) {
        $ponAPIObject = new PONizer();

        $allOLTs = $ponAPIObject->getAllOltDevices();
        $models = array();
        $modelsData = $ponAPIObject->getAllModelsData();
        if (!empty($modelsData)) {
            foreach ($modelsData as $io => $each) {
                $models[$each['id']] = $each['modelname'];
            }
        }

        $form .= wf_tag('tr', false, 'row3');
        $form .= wf_tag('tr', true, 'row3');
        $form .= wf_tag('tr', false, 'row3');
        $form .= wf_tag('tr', true, 'row3');

        $form .= wf_tag('tr', false, 'row3');
        $form .= wf_tag('td', false, '', 'style="padding-left: 15px"');
        $form .= wf_tag('h3', false, '', 'style="color: #000"');
        $form .= __('Associate ONU with subscriber');
        $form .= wf_tag('h3', true);
        $form .= wf_tag('td', true);
        $form .= wf_tag('td', false);
        $form .= wf_tag('td', true);
        $form .= wf_tag('tr', true);

        if (empty($allOLTs)) {
            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false, '', 'style="text-align: center;" colspan="2"');
            $form .= wf_tag('h3', false, '', 'style="color: #000"');
            $form .= __('No OLT devices found - can not associate ONU');
            $form .= wf_tag('h3', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);
            $form .= wf_HiddenInput('nooltsfound', 'true');
        } else {
            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false);
            $form .= wf_Selector('oltid', $allOLTs, '', '', true, false, 'OLTSelector');
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '
                $(document).ready(function() {
                    getUnknownONUList($(\'#OLTSelector\').val());
                });
        
                $(\'#OLTSelector\').change(function(){
                    getUnknownONUList($(this).val());
                });
                
                function getUnknownONUList(OLTID) {                
                    $.ajax({
                        type: "GET",
                        url: "?module=userreg",
                        data: {
                                getunknownlist:true, 
                                oltid:OLTID,
                                selectorid:"UnknonwnsSelectorID",
                                selectorname:"UnknonwnsSelector"
                              },
                        success: function(result) {
                                    $(\'#UnknonwnsSelBlock\').html(result);
                                 }
                    });     
                }
                ';
            $form .= wf_tag('script', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('td', false);
            $form .= __('OLT device') . wf_tag('sup') . '*' . wf_tag('sup', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);

            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false);
            $form .= wf_Selector('onumodelid', $models, '', '', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('td', false);
            $form .= __('ONU model') . wf_tag('sup') . '*' . wf_tag('sup', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);

            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false);
            $form .= wf_tag('input', false, '', 'type="text" name="onuip" value="" ');
            $form .= wf_CheckInput('onuipproposal', __('Make ONU IP same as subscriber IP'), false, false);
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '$(\'[name = onuipproposal]\').change(function() {                            
                    if ( $(this).is(\':checked\') ) {
                        $(\'[name = onuip]\').attr("readonly", "readonly");
                        $(\'[name = onuip]\').css(\'background-color\', \'#CECECE\')
                    } else {
                        $(\'[name = onuip]\').removeAttr("readonly");               
                        $(\'[name = onuip]\').css(\'background-color\', \'#FFFFFF\') 
                    }                            
                });';
            $form .= wf_tag('script', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('td', false);
            $form .= __('ONU IP');
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);

            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false);
            $form .= wf_tag('input', false, '', 'type="text" name="onumac" id="onumacid" value="" ');
//$form.= wf_delimiter();
            $form .= '&nbsp&nbsp' . __('or choose MAC from unknown ONU\'s list on chosen OLT') . '&nbsp&nbsp';
            $form .= wf_tag('div', false, '', 'id="UnknonwnsSelBlock" style="display:inline-block"');
            $form .= wf_tag('div', true);
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '$(document).on("change", "#UnknonwnsSelectorID", function(){
                    $(\'#onumacid\').val($(this).val());                    
                });';
            $form .= wf_tag('script', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('td', false);
            $form .= __('ONU MAC') . wf_tag('sup') . '*' . wf_tag('sup', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);

            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false);
            $form .= wf_tag('input', false, '', 'type="text" name="onuserial" value="" ');
            $form .= wf_tag('td', true);
            $form .= wf_tag('td', false);
            $form .= __('ONU serial');
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);

            $form .= wf_tag('tr', false, 'row3');
            $form .= wf_tag('td', false);
            $form .= wf_tag('a', false, 'ubButton', 'href="" class="ubButton" id="onuassignment1"');
            $form .= __('Check if ONU is assigned to any login already');
            $form .= wf_tag('a', true);
            $form .= wf_tag('script', false, '', 'type="text/javascript"');
            $form .= '$(\'#onuassignment1\').click(function(evt){
                        evt.preventDefault();
                        
                        if ( typeof( $(\'input[name=onumac]\').val() ) === "string" && $(\'input[name=onumac]\').val().length > 0 ) {
                            $.ajax({
                                type: "GET",
                                url: "?module=ponizer",
                                data: {action:\'checkONUAssignment\', onumac:$(\'input[name=onumac]\').val()},
                                success: function(result) {
                                            $(\'#onuassignment2\').text(result);
                                         }
                            });
                        } else {$(\'#onuassignment2\').text(\'\');}
                       
                        return false;                
                    });';
            $form .= wf_tag('script', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('td', false);
            $form .= wf_tag('p', false, '', 'id="onuassignment2" style="font-weight: 600; color: #000"');
            $form .= wf_tag('p', true);
            $form .= wf_tag('td', true);
            $form .= wf_tag('tr', true);
        }
    }

    $form .= wf_tag('table', true);

    $form .= wf_HiddenInput('repostdata', base64_encode(serialize($newuser_data)));
    $form .= wf_Submit(__('Let register that user'));
    $form .= wf_tag('form', true);

    $form .= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
    $form .= wf_StepsMeter($registerSteps, $currentStep);
    return($form);
}

/**
 * Puts userreg log entry into database
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_UserRegisterLog($login) {
    $date = curdatetime();
    $admin = whoami();
    $login = vf($login);
    $address = zb_AddressGetFulladdresslist();
    $address = $address[$login];
    $address = mysql_real_escape_string($address);
    $query = "INSERT INTO `userreg` (`id` ,`date` ,`admin` ,`login` ,`address`) "
            . "VALUES (NULL , '" . $date . "', '" . $admin . "', '" . $login . "', '" . $address . "');";
    nr_query($query);
}

/**
 * Checks is some IP unique?
 * 
 * @param string $ip
 * @return bool
 */
function zb_ip_unique($ip) {
    $ip = mysql_real_escape_string($ip);
    $query = "SELECT `login` from `users` WHERE `ip`='" . $ip . "'";
    $usersbyip = simple_queryall($query);
    if (!empty($usersbyip)) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Checks is some MAC unique?
 * 
 * @param string $mac
 * @return bool
 */
function zb_mac_unique($mac) {
    $ip = mysql_real_escape_string($mac);
    $query = "SELECT `mac` from `nethosts` WHERE `mac`='" . $mac . "'";
    $usersbymac = simple_queryall($query);
    if (!empty($usersbymac)) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Performs an user registration
 * 
 * @global object $billing
 * @param array $user_data
 * @param bool $goprofile
 */
function zb_UserRegister($user_data, $goprofile = true) {
    global $billing, $ubillingConfig;
    $billingconf = $ubillingConfig->getBilling();
    $alterconf = $ubillingConfig->getAlter();
    $registerUserONU = $ubillingConfig->getAlterParam('ONUAUTO_USERREG');
    $needONUAssignment = false;

// Init all of needed user data
    $login = vf($user_data['login']);
    $login = zb_RegLoginFilter($login);

    $password = vf($user_data['password']);
    $ip = $user_data['IP'];
    $cityid = $user_data['city'];
    $streetid = $user_data['street'];
    $buildid = $user_data['build'];
    @$entrance = $user_data['entrance'];
    @$floor = $user_data['floor'];
    $apt = $user_data['apt'];
    $serviceid = $user_data['service'];

//ONU auto assign options
    if ($registerUserONU and ! empty($user_data['oltid'])) {
        $OLTID = $user_data['oltid'];
        $ONUModelID = $user_data['onumodelid'];
        $ONUIP = $user_data['onuip'];
        $ONUMAC = $user_data['onumac'];
        $ONUSerial = $user_data['onuserial'];
        $needONUAssignment = !empty($ONUMAC);
    }

    if (isset($user_data['userMAC']) and ! empty($user_data['userMAC'])) {
        $mac = strtolower($user_data['userMAC']);
    } elseif ($billingconf['REGRANDOM_MAC']) {
// if random mac needed
// funny random mac, yeah? :)
        $mac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
    } else {
        $mac = null;
    }

    $netid = multinet_get_service_networkid($serviceid);
    $busylogins = zb_AllBusyLogins();
//check login lenght
    $maxStLen = 42;
    $loginLen = strlen($login);
    if ($loginLen > $maxStLen) {
        log_register("HUGELOGIN REGISTER TRY (" . $login . ")");
        $alert = __('Attention generated login longer than') . ' ' . $maxStLen . ' ' . __('bytes') . '. (' . $login . ' > ' . $loginLen . ') ' . __('And is not compatible with Stargazer') . '.';
        die($alert);
    }


// empty login validation
    if (empty($login)) {
        $alert = wf_tag('script', false, '', 'type="text/javascript"');
        $alert .= 'alert("' . __('Error') . ': ' . __('Empty login') . '");';
        $alert .= wf_tag('script', true);
        print($alert);
        rcms_redirect("?module=userreg");
        die();
    }

//duplicate login validation
    if (isset($busylogins[$login])) {
        $alert = wf_tag('script', false, '', 'type="text/javascript"');
        $alert .= 'alert("' . __('Error') . ': ' . __('Duplicate login') . '");';
        $alert .= wf_tag('script', true);
        print($alert);
        rcms_redirect("?module=userreg");
        die();
    }

//last checks
    if (!zb_ip_unique($ip)) {
        $alert = wf_tag('script', false, '', 'type="text/javascript"');
        $alert .= 'alert("' . __('Error') . ': ' . __('This IP is already used by another user') . '");';
        $alert .= wf_tag('script', true);
        print($alert);
        rcms_redirect("?module=userreg");
        die();
    }


// registration subroutine
    $billing->createuser($login);
    log_register("StgUser REGISTER (" . $login . ")");
    $billing->setpassword($login, $password);
    log_register("StgUser (" . $login . ") PASSWORD `" . $password . "`");
    $billing->setip($login, $ip);
    log_register("StgUser (" . $login . ") IP `" . $ip . "`");
    zb_AddressCreateApartment($buildid, $entrance, $floor, $apt);
    zb_AddressCreateAddress($login, zb_AddressGetLastid());
    multinet_add_host($netid, $ip, $mac);
    zb_UserCreateRealName($login, '');
    zb_UserCreatePhone($login, '', '');
    zb_UserCreateContract($login, '');
    zb_UserCreateEmail($login, '');
    zb_UserCreateSpeedOverride($login, 0);
    zb_UserRegisterLog($login);


// if AlwaysOnline to new user needed
    if ($billingconf['REGALWONLINE']) {
        $alwaysonline = 1;
        $billing->setao($login, $alwaysonline);
        log_register('CHANGE AlwaysOnline (' . $login . ') ON ' . $alwaysonline);
    }

// if we want to disable detailed stats to new user by default
    if ($billingconf['REGDISABLEDSTAT']) {
        $dstat = 1;
        $billing->setdstat($login, $dstat);
        log_register('CHANGE dstat (' . $login . ') ON ' . $dstat);
    }

//set contract same as login for this user
    if (isset($alterconf['CONTRACT_SAME_AS_LOGIN'])) {
        if ($alterconf['CONTRACT_SAME_AS_LOGIN']) {
            $newUserContract = $login;
            $contractDate = date("Y-m-d");
            zb_UserChangeContract($login, $newUserContract);
            zb_UserContractDateCreate($newUserContract, $contractDate);
        }
    }

//cemetery processing
    if (isset($alterconf['CEMETERY_ENABLED'])) {
        if ($alterconf['CEMETERY_ENABLED']) {
            if ($alterconf['CEMETERY_ENABLED'] == 2) {
                $cemetery = new Cemetery(false);
                $cemetery->setDead($login);
            }
        }
    }

//contract autogeneration
    if (isset($alterconf['CONTRACT_AUTOGEN']) and empty($alterconf['CONTRACT_SAME_AS_LOGIN'])) {
        if ($alterconf['CONTRACT_AUTOGEN']) {
            $contract_proposal = '';
            $allcontracts = zb_UserGetAllContracts();
            $top_offset = 100000;
//contract generation mode default
            if ($alterconf['CONTRACT_GENERATION_DEFAULT']) {
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

//setting generated contract to new user
            if (!isset($allcontracts[$contract_proposal])) {
                $contractDate = date("Y-m-d");
                zb_UserChangeContract($login, $contract_proposal);
                zb_UserContractDateCreate($contract_proposal, $contractDate);
            }
        }
    }

//branches assign for newly created user
    if ($alterconf['BRANCHES_ENABLED']) {
        global $branchControl;
        if ((wf_CheckPost(array('reguserbranchid'))) AND ( !wf_CheckPost(array('reguserwithnobranch')))) {
            $newUserBranchId = vf($_POST['reguserbranchid'], 3);
            $branchControl->userAssignBranch($newUserBranchId, $login);
        }
    }

// ONU assign for newly created user
    if ($registerUserONU and $needONUAssignment) {
        $PONAPIObject = new PONizer();

        if ($PONAPIObject->checkMacUnique($ONUMAC)) {
            $PONAPIObject->onuCreate($ONUModelID, $OLTID, $ONUIP, $ONUMAC, $ONUSerial, $login);
        } else {
            $ONUID = $PONAPIObject->getONUIDByMAC($ONUMAC);
            $PONAPIObject->onuAssign($ONUID, $login);
        }
    }

///////////////////////////////////
    if ($goprofile) {
        rcms_redirect("?module=userprofile&username=" . $login . '&justregistered=true');
    }
}

?>

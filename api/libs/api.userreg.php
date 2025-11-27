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
 * Returns some easy-to-remember password proposal
 * 
 * @param int $len
 * 
 * @return string
 */
function zb_PasswordGenerate($len = 8) {
    $result = '';
    if ($len >= 6 && ( $len % 2 ) !== 0) {
        $len = 8;
    }
    $length = $len - 2; // Makes room for a two-digit number on the end
    $conso = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z');
    $vocal = array('a', 'e', 'i', 'u');
    srand((float) microtime() * 1000000);
    $max = $length / 2;
    for ($i = 1; $i <= $max; $i++) {
        $result .= $conso[rand(0, sizeof($conso) - 1)];
        $result .= $vocal[rand(0, sizeof($vocal) - 1)];
    }
    $result .= rand(10, 99);

    return ($result);
}

/**
 * Returns two-hands typing optimized password proposal
 * 
 * @param int $len
 * 
 * @return string
 */
function zb_PasswordGenerateTH($len = 8) {
    $leftHand = array('q', 'w', 'e', 'r', 't', 'a', 's', 'd', 'f', 'g', 'z', 'x', 'c', 'v', 'b', '2', '3', '4', '5', '6');
    $rightHand = array('y', 'u', 'p', 'h', 'j', 'k', 'n', 'm', '7', '8', '9');
    $password = '';
    $left = true;

    for ($i = 0; $i < $len; $i++) {
        if ($left) {
            $password .= $leftHand[array_rand($leftHand)];
            $left = false;
        } else {
            $password .= $rightHand[array_rand($rightHand)];
            $left = true;
        }
    }

    return $password;
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
    $allapts = zb_AddressGetBuildAptIds($buildid);
    $someoneLiveHere = false;
    $busyApts = array(); //IDs of apts which is busy
    if (!empty($allapts)) {
        foreach ($allapts as $aptid => $aptnum) {
            if ($aptnum == $apt) {
                $someoneLiveHere = true;
                $busyApts[$aptid] = $aptnum;
            }
        }
    }


//display of users which lives in this apt
    if ($someoneLiveHere) {
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

            if (!empty($similarAddressUsers)) {
                $result .= $messages->getStyledMessage(__('The apartment has one lives, we have nothing against, just be warned'), 'warning');
            }

//additional cosmetic delimiter for binder module
            if (!empty($login)) {
                $result .= wf_delimiter(0);
            }

            if (!empty($similarAddressUsers)) {
                $result .= web_UserArrayShower($similarAddressUsers);
            }
        }
    }

//modal window width control for cosmetic purposes
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
    $addressExten = '';
    $addressExtendedOn = $ubillingConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED');

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

        if ($addressExtendedOn) {
            $addressExten = web_AddressExtenCreateForm();
        }

        $servicesel = multinet_service_selector();
        $freeIpStatsFlag = $ubillingConfig->getAlterParam('USERREG_FREEIP_STATS');
        if ($freeIpStatsFlag == 1 OR $freeIpStatsFlag == 3) {
            $servicesel .= wf_modalAuto(wf_img('skins/icon_whois_small.png', __('IP usage stats')), __('IP usage stats'), web_FreeIpStats());
        }
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

    if ($addressExtendedOn) {
        $formInputs .= wf_tag('tr', false, 'row3');
        $formInputs .= wf_tag('td', false) . $addressExten . wf_tag('td', true);
        $formInputs .= wf_tag('td', false) . __('Extended address info') . wf_tag('td', true);
        $formInputs .= wf_tag('tr', true);
    }

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
 * Returns new user password proposal
 * 
 * @return string
 */
function zb_RegPasswordProposal() {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $password_proposal = '';
    if ((isset($alterconf['PASSWORD_GENERATION_LENGHT'])) AND ( isset($alterconf['PASSWORD_TYPE']))) {
        $passwordsType = (isset($alterconf['PASSWORD_TYPE'])) ? $alterconf['PASSWORD_TYPE'] : 1;
        $passwordsLenght = (isset($alterconf['PASSWORD_GENERATION_LENGHT'])) ? $alterconf['PASSWORD_GENERATION_LENGHT'] : 8;

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
            default :
                $password_proposal = zb_rand_string(8);
                break;
        }
    } else {
        die(strtoupper('you have missed a essential option. before update read release notes motherfucker!'));
    }
    return ($password_proposal);
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
    global $ubillingConfig;
    $currentStep = 4;
    $form = '';
    $alterconf = $ubillingConfig->getAlter();
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
    $login_proposal = '';
    $loginGenerator = new SayMyName($cityalias, $streetalias, $buildnum, $apt, $ip_proposal, $agentPrefixID);
    try {
        $login_proposal = $loginGenerator->getLogin();
    } catch (Exception $exception) {
        show_error(__('Strange exception') . ': ' . $exception->getMessage());
    }
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

    $addressCheck = web_AddressBuildShowAptsCheck($buildata['id'], $apt);
    if (!empty($addressCheck)) {
        $form .= wf_modalOpenedAuto(__('Warning'), $addressCheck, '800', '300');
    }
    $form .= wf_tag('table', false, 'glamour', 'width="100%" border="0"');
    $form .= wf_tag('form', false, '', ' action="" method="POST"');

    $form .= wf_tag('tr', false, 'row3');
    $form .= wf_tag('td', false, '', 'width="65%"');
    if ($safe_mode) {
        $form .= wf_tag('input', false, '', 'type="text" name="login" value="' . $login_proposal . '" READONLY');
    } else {
        $form .= wf_TextInput('login', '', $login_proposal, false, '', 'login');
    }
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
        $form .= wf_TextInput('userMAC', '', '', false, 12, 'mac');
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
            $branchCheckboxFlag = ($ubillingConfig->getAlterParam('USERREG_NO_BRANCH_DEFAULT')) ? true : false;
            $form .= wf_CheckInput('reguserwithnobranch', __('Register user with no branch'), false, $branchCheckboxFlag);
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
                if (@$alterconf['ONUMODELS_FILTER']) {
                    if (ispos($each['modelname'], 'ONU')) {
                        $models[$each['id']] = $each['modelname'];
                    }
                } else {
                    $models[$each['id']] = $each['modelname'];
                }
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
 * Assigns some user tags to the $login from the default tags list
 *
 * @param $login
 * @param $defaultTagsList
 *
 * @return void
 */
function assignDefaultTags($login, $defaultTagsList) {
    if (!empty($login) and !empty($defaultTagsList)) {
        $defaultTagsList = explode(',', $defaultTagsList);

        foreach ($defaultTagsList as $io => $eachTagID) {
            $tmpTagID = trim($eachTagID);
            stg_add_user_tag($login, $tmpTagID);
        }
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
    $addressExtendedOn = $ubillingConfig->getAlterParam('ADDRESS_EXTENDED_ENABLED');
    $registerUserONU = $ubillingConfig->getAlterParam('ONUAUTO_USERREG');
    $contractTemplateStr = $ubillingConfig->getAlterParam('CONTRACT_GEN_TEMPLATE', '');
    $defaultTagsList = $ubillingConfig->getAlterParam('USERREG_DEFAULT_TAGS_LIST', '');
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

    if ($addressExtendedOn) {
        $postCode = $user_data['postalcode'];
        $extenTown = $user_data['towndistr'];
        $extenAddr = $user_data['addressexten'];
    }

//ONU auto assign options
    if ($registerUserONU and !empty($user_data['oltid'])) {
        $OLTID = $user_data['oltid'];
        $ONUModelID = $user_data['onumodelid'];
        $ONUIP = $user_data['onuip'];
        $ONUMAC = $user_data['onumac'];
        $ONUSerial = $user_data['onuserial'];
        $needONUAssignment = !empty($ONUMAC);
    }

    if (isset($user_data['userMAC']) and !empty($user_data['userMAC'])) {
        $mac = strtolower($user_data['userMAC']);
    } elseif ($billingconf['REGRANDOM_MAC']) {
// if random mac needed
// funny random mac, yeah? :)
        $mac = zb_MacGetRandom();
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
    log_register("STG USER REGISTER (" . $login . ")");
    $billing->setpassword($login, $password);
    log_register("STG USER (" . $login . ") PASSWORD `" . $password . "`");
    $billing->setip($login, $ip);
    log_register("STG USER (" . $login . ") IP `" . $ip . "`");
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
        log_register('ALWAYSONLINE CHANGE (' . $login . ') ON `' . $alwaysonline . '`');
    }

// if we want to disable detailed stats to new user by default
    if ($billingconf['REGDISABLEDSTAT']) {
        $dstat = 1;
        $billing->setdstat($login, $dstat);
        log_register('DSTAT CHANGE (' . $login . ') ON `' . $dstat . '`');
    }

// new users registers as frozen by default
    if (isset($billingconf['REGFROZEN'])) {
        if ($billingconf['REGFROZEN']) {
            $billing->setpassive($login, 1);
            log_register('PASSIVE CHANGE (' . $login . ') ON `1`');
        }
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
            $useContractTemplate = !empty($contractTemplateStr);
            $top_offset = 100000;

//contract template is ON
            if ($useContractTemplate) {
                $contractTemplateSplitted = zb_GetBaseContractTemplateSplitted();
                $startContractTplPart = $contractTemplateSplitted[0];
                $endContractTplPart = $contractTemplateSplitted[1];
                $digitContractTplPart = $contractTemplateSplitted[2];
                $digitBlockLength = zb_GetContractDigitBlockTplParams($digitContractTplPart, true);
            }

//contract generation mode default
            if ($alterconf['CONTRACT_GENERATION_DEFAULT']) {
                for ($i = 1; $i < $top_offset; $i++) {
                    if ($useContractTemplate) {
                        $contractDigitBlock = zb_GenContractDigitBlock($digitBlockLength, $i);
                        $tmpContract = $startContractTplPart . $contractDigitBlock . $endContractTplPart;
                    } else {
                        $tmpContract = $i;
                    }

                    if (!isset($allcontracts[$tmpContract])) {
                        $contract_proposal = $tmpContract;
                        break;
                    }
                }
            } else {
//alternate generation method
                $max_contract = max(array_keys($allcontracts));

                if ($useContractTemplate) {
                    $contractDigitBlock = zb_ExtractContractDigitPart($max_contract, $digitBlockLength, true);
                    $contract_proposal = $startContractTplPart . $contractDigitBlock . $endContractTplPart;
                } else {
                    if (!is_int($contract_proposal)) {
                        $contract_proposal=0;
                    }
                    $contract_proposal = $max_contract + 1;
                }
            }

            if (empty($contract_proposal) and $useContractTemplate) {
                $contract_proposal = zb_GenBaseContractFromTemplate();
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
        if ((wf_CheckPost(array('reguserbranchid'))) AND (!wf_CheckPost(array('reguserwithnobranch')))) {
            $newUserBranchId = vf($_POST['reguserbranchid'], 3);
            $branchControl->userAssignBranch($newUserBranchId, $login);
        }
    }

// ONU assign for newly created user
    if ($registerUserONU and $needONUAssignment) {
        $PONAPIObject = new PONizer();

        if ($PONAPIObject->checkOnuUnique($ONUMAC)) {
            $PONAPIObject->onuCreate($ONUModelID, $OLTID, $ONUIP, $ONUMAC, $ONUSerial, $login);
        } else {
            $ONUID = $PONAPIObject->getOnuIDbyIdent($ONUMAC);
            $PONAPIObject->onuAssign($ONUID, $login);
        }
    }

    if ($addressExtendedOn) {
        zb_AddAddressExtenSave($login, false, $postCode, $extenTown, $extenAddr);
    }

    //static OpenPayz payment IDs registration
    if ($alterconf['OPENPAYZ_SUPPORT']) {
        if ($alterconf['OPENPAYZ_STATIC_ID']) {
            $openPayz = new OpenPayz(false, true);
            $openPayz->registerStaticPaymentId($login);
        }
    }

    // default user tags list processing
    if (!empty($defaultTagsList)) {
        assignDefaultTags($login, $defaultTagsList);
    }

///////////////////////////////////
    if ($goprofile) {
        rcms_redirect("?module=userprofile&username=" . $login . '&justregistered=true');
    }
}

<?php

/**
 *  Returns all data from multinet hosts
 * 
 *  @return  array
 */
function zb_MultinetGetAllData() {
    $query = "SELECT * from `nethosts`";
    $result = array();
    $allhosts = simple_queryall($query);
    if (!empty($allhosts)) {
        foreach ($allhosts as $io => $eachhost) {
            $result[$eachhost['ip']]['ip'] = $eachhost['ip'];
            $result[$eachhost['ip']]['mac'] = $eachhost['mac'];
            $result[$eachhost['ip']]['netid'] = $eachhost['netid'];
            $result[$eachhost['ip']]['id'] = $eachhost['id'];
        }
    }

    return ($result);
}

/**
 * Returns NAS params by netid
 * 
 * @param  int   $netid - multinet network ID
 * @param  array $allnasdata - array of all NAS data
 * 
 * @return  array
 */
function zb_NasGetParams($netid, $allnasdata) {
    $netid = vf($netid, 3);
    $result = array();

    if (!empty($allnasdata)) {
        foreach ($allnasdata as $io => $eachnas) {
            if ($eachnas['netid'] == $netid) {
                $result = $eachnas;
            }
        }
    }
    return ($result);
}

/**
 * Return all CF content by login
 * 
 * @param   string $login - existing user login
 * @param   array  $allcfdata - all CF data array
 * @return  array
 */
function zb_cfGetContent($login, $allcfdata) {
    $login = mysql_real_escape_string($login);
    $result = array();

    if (!empty($allcfdata)) {
        foreach ($allcfdata as $io => $eachcf) {
            if ($eachcf['login'] == $login) {
                $result[$eachcf['typeid']] = $eachcf['content'];
            }
        }
    }
    return ($result);
}

/**
 * Fetch all Payment IDs from database as virtualid=>login
 * 
 * @return array
 */
function zb_TemplateGetAllOPCustomers() {
    $result = array();
    $query = "SELECT * from `op_customers`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['realid']] = $each['virtualid'];
        }
    }
    return ($result);
}

/**
 *  Returns all data about current userbase
 *  which used for templatizing functions
 * 
 *  @return  array
 */
function zb_TemplateGetAllUserData() {
    $altcfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");

    $userdata = array();
    $alluserdata = zb_UserGetAllStargazerData();
    $tariffspeeds = zb_TariffGetAllSpeeds();
    $tariffprices = zb_TariffGetPricesAll();
    $multinetdata = zb_MultinetGetAllData();
    $allcontracts = zb_UserGetAllContracts();
    $allcontracts = array_flip($allcontracts);
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslist();
    $allemails = zb_UserGetAllEmails();
    $allnasdata = zb_NasGetAllData();
    $allcfdata = cf_FieldsGetAll();
    $allpdata = zb_UserPassportDataGetAll();

    if ($altcfg['OPENPAYZ_REALID']) {
        $allopcustomers = zb_TemplateGetAllOPCustomers();
    }

    if (!empty($alluserdata)) {
        foreach ($alluserdata as $io => $eachuser) {
            $userdata[$eachuser['login']]['login'] = $eachuser['login'];
            $userdata[$eachuser['login']]['password'] = $eachuser['Password'];
            $userdata[$eachuser['login']]['userhash'] = crc16($eachuser['login']);
            $userdata[$eachuser['login']]['tariff'] = $eachuser['Tariff'];
            @$userdata[$eachuser['login']]['tariffprice'] = $tariffprices[$eachuser['Tariff']];
            $userdata[$eachuser['login']]['cash'] = $eachuser['Cash'];
            $userdata[$eachuser['login']]['credit'] = $eachuser['Credit'];
            $userdata[$eachuser['login']]['down'] = $eachuser['Down'];
            $userdata[$eachuser['login']]['passive'] = $eachuser['Passive'];
            $userdata[$eachuser['login']]['ao'] = $eachuser['AlwaysOnline'];
            @$userdata[$eachuser['login']]['contract'] = $allcontracts[$eachuser['login']];
            @$userdata[$eachuser['login']]['realname'] = $allrealnames[$eachuser['login']];
            @$userdata[$eachuser['login']]['address'] = $alladdress[$eachuser['login']];
            @$userdata[$eachuser['login']]['email'] = $allemails[$eachuser['login']];
            //openpayz payment ID
            if ($altcfg['OPENPAYZ_REALID']) {
                @$userdata[$eachuser['login']]['payid'] = $allopcustomers[$eachuser['login']];
            } else {
                @$userdata[$eachuser['login']]['payid'] = ip2int($eachuser['IP']);
            }
            //traffic params
            $userdata[$eachuser['login']]['traffic'] = $eachuser['D0'] + $eachuser['U0'];
            $userdata[$eachuser['login']]['trafficdown'] = $eachuser['D0'];
            $userdata[$eachuser['login']]['trafficup'] = $eachuser['U0'];

            //net params
            $userdata[$eachuser['login']]['ip'] = $eachuser['IP'];
            @$userdata[$eachuser['login']]['mac'] = $multinetdata[$eachuser['IP']]['mac'];
            @$userdata[$eachuser['login']]['netid'] = $multinetdata[$eachuser['IP']]['netid'];
            @$userdata[$eachuser['login']]['hostid'] = $multinetdata[$eachuser['IP']]['id'];
            //nas data
            @$usernas = zb_NasGetParams($multinetdata[$eachuser['IP']]['netid'], $allnasdata);
            @$userdata[$eachuser['login']]['nasid'] = $usernas['id'];
            @$userdata[$eachuser['login']]['nasip'] = $usernas['nasip'];
            @$userdata[$eachuser['login']]['nasname'] = $usernas['nasname'];
            @$userdata[$eachuser['login']]['nastype'] = $usernas['nastype'];

            if (isset($tariffspeeds[$eachuser['Tariff']])) {
                $userdata[$eachuser['login']]['speeddown'] = $tariffspeeds[$eachuser['Tariff']]['speeddown'];
                $userdata[$eachuser['login']]['speedup'] = $tariffspeeds[$eachuser['Tariff']]['speedup'];
            } else {
                //if no tariff speed defined zero speed by default
                $userdata[$eachuser['login']]['speeddown'] = 0;
                $userdata[$eachuser['login']]['speedup'] = 0;
            }

            //CF data
            $usercfdata = zb_cfGetContent($eachuser['login'], $allcfdata);
            if (!empty($usercfdata)) {
                foreach ($usercfdata as $cd => $eachcf) {
                    $userdata[$eachuser['login']]['cf'][$cd] = $eachcf;
                }
            }
            //passport data
            @$userdata[$eachuser['login']]['birthdate'] = $allpdata[$eachuser['login']]['birthdate'];
            @$userdata[$eachuser['login']]['passportnum'] = $allpdata[$eachuser['login']]['passportnum'];
            @$userdata[$eachuser['login']]['passportdate'] = $allpdata[$eachuser['login']]['passportdate'];
            @$userdata[$eachuser['login']]['passportwho'] = $allpdata[$eachuser['login']]['passportwho'];
            @$userdata[$eachuser['login']]['pcity'] = $allpdata[$eachuser['login']]['pcity'];
            @$userdata[$eachuser['login']]['pstreet'] = $allpdata[$eachuser['login']]['pstreet'];
            @$userdata[$eachuser['login']]['pbuild'] = $allpdata[$eachuser['login']]['pbuild'];
            @$userdata[$eachuser['login']]['papt'] = $allpdata[$eachuser['login']]['papt'];
        }
    }

    return ($userdata);
}

/**
 *  Replaces all known macro in template with current per-user values
 *  
 *  @param   string $template raw template 
 *  @param   array $alluserdata collected userdata 
 *  @return  string
 */
function zb_TemplateReplaceAll($template, $alluserdata) {
    $result = '';
    if (!empty($alluserdata)) {
        foreach ($alluserdata as $io => $each) {
            $result.=$template;
            //known macro
            $result = str_ireplace('{LOGIN}', $each['login'], $result);
            $result = str_ireplace('{PASSWORD}', $each['password'], $result);
            $result = str_ireplace('{USERHASH}', $each['userhash'], $result);
            $result = str_ireplace('{TARIFF}', $each['tariff'], $result);
            $result = str_ireplace('{TARIFFPRICE}', $each['tariffprice'], $result);
            $result = str_ireplace('{CASH}', $each['cash'], $result);
            $result = str_ireplace('{CREDIT}', $each['credit'], $result);
            $result = str_ireplace('{DOWN}', $each['down'], $result);
            $result = str_ireplace('{PASSIVE}', $each['passive'], $result);
            $result = str_ireplace('{AO}', $each['ao'], $result);
            $result = str_ireplace('{CONTRACT}', $each['contract'], $result);
            $result = str_ireplace('{REALNAME}', $each['realname'], $result);
            $result = str_ireplace('{ADDRESS}', $each['address'], $result);
            $result = str_ireplace('{EMAIL}', $each['email'], $result);
            $result = str_ireplace('{PAYID}', $each['payid'], $result);
            $result = str_ireplace('{TRAFFIC}', $each['traffic'], $result);
            $result = str_ireplace('{TRAFFICDOWN}', $each['trafficdown'], $result);
            $result = str_ireplace('{TRAFFICUP}', $each['trafficup'], $result);
            $result = str_ireplace('{IP}', $each['ip'], $result);
            $result = str_ireplace('{MAC}', $each['mac'], $result);
            $result = str_ireplace('{NETID}', $each['netid'], $result);
            $result = str_ireplace('{HOSTID}', $each['hostid'], $result);
            $result = str_ireplace('{NASID}', $each['nasid'], $result);
            $result = str_ireplace('{NASIP}', $each['nasip'], $result);
            $result = str_ireplace('{NASNAME}', $each['nasname'], $result);
            $result = str_ireplace('{NASTYPE}', $each['nastype'], $result);
            $result = str_ireplace('{SPEEDDOWN}', $each['speeddown'], $result);
            $result = str_ireplace('{SPEEDUP}', $each['speedup'], $result);
            $result = str_ireplace('{PBIRTH}', $each['birthdate'], $result);
            $result = str_ireplace('{PNUM}', $each['passportnum'], $result);
            $result = str_ireplace('{PDATE}', $each['passportdate'], $result);
            $result = str_ireplace('{PWHO}', $each['passportwho'], $result);
            $result = str_ireplace('{PCITY}', $each['pcity'], $result);
            $result = str_ireplace('{PSTREET}', $each['pstreet'], $result);
            $result = str_ireplace('{PBUILD}', $each['pbuild'], $result);
            $result = str_ireplace('{PAPT}', $each['papt'], $result);
            //custom fields extract
            if (ispos($result, '{CFIELD:')) {
                $split = explode('{CFIELD:', $result);
                $cfid = vf($split[1], 3);
                $result = str_ireplace('{CFIELD:' . $cfid . '}', @$each['cf'][$cfid], $result);
            }
            //print macro
            $printsub = '<script language="javascript"> 
                        window.print();
                    </script>';
            $result = str_ireplace('{PRINTME}', $printsub, $result);
        }
    }
    return ($result);
}

/**
 *  Replaces all known macro in template with per-user values for selected user
 *
 *  @param  string  $login existing user login
 *  @param  string  $template raw template 
 *  @param  array   $alluserdata collected userdata 
 *  @return  string
 */
function zb_TemplateReplace($login, $template, $alluserdata) {
    $result = '';
    if (!empty($alluserdata)) {
        $result.=$template;
        //known macro
        $result = str_ireplace('{LOGIN}', $alluserdata[$login]['login'], $result);
        $result = str_ireplace('{PASSWORD}', $alluserdata[$login]['password'], $result);
        $result = str_ireplace('{USERHASH}', $alluserdata[$login]['userhash'], $result);
        $result = str_ireplace('{TARIFF}', $alluserdata[$login]['tariff'], $result);
        $result = str_ireplace('{TARIFFPRICE}', $alluserdata[$login]['tariffprice'], $result);
        $result = str_ireplace('{CASH}', $alluserdata[$login]['cash'], $result);
        $result = str_ireplace('{ROUNDCASH}', round($alluserdata[$login]['cash'], 2), $result);
        $result = str_ireplace('{CURDATE}', curdate(), $result);
        $result = str_ireplace('{CREDIT}', $alluserdata[$login]['credit'], $result);
        $result = str_ireplace('{DOWN}', $alluserdata[$login]['down'], $result);
        $result = str_ireplace('{PASSIVE}', $alluserdata[$login]['passive'], $result);
        $result = str_ireplace('{AO}', $alluserdata[$login]['ao'], $result);
        $result = str_ireplace('{CONTRACT}', $alluserdata[$login]['contract'], $result);
        $result = str_ireplace('{REALNAME}', $alluserdata[$login]['realname'], $result);
        $result = str_ireplace('{ADDRESS}', $alluserdata[$login]['address'], $result);
        $result = str_ireplace('{EMAIL}', $alluserdata[$login]['email'], $result);
        $result = str_ireplace('{PAYID}', $alluserdata[$login]['payid'], $result);
        $result = str_ireplace('{TRAFFIC}', $alluserdata[$login]['traffic'], $result);
        $result = str_ireplace('{TRAFFICDOWN}', $alluserdata[$login]['trafficdown'], $result);
        $result = str_ireplace('{TRAFFICUP}', $alluserdata[$login]['trafficup'], $result);
        $result = str_ireplace('{IP}', $alluserdata[$login]['ip'], $result);
        $result = str_ireplace('{MAC}', $alluserdata[$login]['mac'], $result);
        $result = str_ireplace('{NETID}', $alluserdata[$login]['netid'], $result);
        $result = str_ireplace('{HOSTID}', $alluserdata[$login]['hostid'], $result);
        $result = str_ireplace('{NASID}', $alluserdata[$login]['nasid'], $result);
        $result = str_ireplace('{NASIP}', $alluserdata[$login]['nasip'], $result);
        $result = str_ireplace('{NASNAME}', $alluserdata[$login]['nasname'], $result);
        $result = str_ireplace('{NASTYPE}', $alluserdata[$login]['nastype'], $result);
        $result = str_ireplace('{SPEEDDOWN}', $alluserdata[$login]['speeddown'], $result);
        $result = str_ireplace('{SPEEDUP}', $alluserdata[$login]['speedup'], $result);
        $result = str_ireplace('{PBIRTH}', $alluserdata[$login]['birthdate'], $result);
        $result = str_ireplace('{PNUM}', $alluserdata[$login]['passportnum'], $result);
        $result = str_ireplace('{PDATE}', $alluserdata[$login]['passportdate'], $result);
        $result = str_ireplace('{PWHO}', $alluserdata[$login]['passportwho'], $result);
        $result = str_ireplace('{PCITY}', $alluserdata[$login]['pcity'], $result);
        $result = str_ireplace('{PSTREET}', $alluserdata[$login]['pstreet'], $result);
        $result = str_ireplace('{PBUILD}', $alluserdata[$login]['pbuild'], $result);
        $result = str_ireplace('{PAPT}', $alluserdata[$login]['papt'], $result);
        //custom fields extract
        if (ispos($result, '{CFIELD:')) {
            $split = explode('{CFIELD:', $result);
            $cfid = vf($split[1], 3);
            $result = str_ireplace('{CFIELD:' . $cfid . '}', @$alluserdata[$login]['cf'][$cfid], $result);
        }
        //print macro
        $printsub = '<script language="javascript"> 
                        window.print();
                    </script>';
        $result = str_ireplace('{PRINTME}', $printsub, $result);
    }
    return ($result);
}

/**
 * Shows all available HTML document templates
 * 
 * @param string $username
 */
function zb_DocsShowAllTemplates($username = '') {
    $docpath = DATA_PATH . 'documents/';
    $headerspath = $docpath . 'headers/';
    $templatespath = $docpath . 'templates/';
    $allheaders = rcms_scandir($headerspath);

    if ($username != '') {
        $userlink = '&username=' . $username;
    } else {
        $userlink = '';
    }

    $tablecells = wf_TableCell(__('Document name'));
    $tablecells.=wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');

    if (!empty($allheaders)) {
        foreach ($allheaders as $eachdoc) {
            if (file_exists($templatespath . $eachdoc)) {
                $documenttitle = file_get_contents($headerspath . $eachdoc);
                $printlink = '<a href="?module=pl_documents' . $userlink . '&printtemplate=' . $eachdoc . '" target="_BLANK">' . $documenttitle . '</a>';
                $actionlinks = wf_JSAlert("?module=pl_documents" . $userlink . "&deletetemplate=" . $eachdoc, web_delete_icon(), 'Removing this may lead to irreparable results');
                $actionlinks.= wf_JSAlert("?module=pl_documents" . $userlink . "&edittemplate=" . $eachdoc, web_edit_icon(), 'Are you serious');

                $tablecells = wf_TableCell($printlink);
                $tablecells.=wf_TableCell($actionlinks);
                $tablerows.= wf_TableRow($tablecells, 'row3');
            }
        }
    }

    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');

    show_window(__('Available document templates'), $result);
}

/**
 * Returns HTML document template body by its name
 * 
 * @param string $template
 * @return string
 */
function zb_DocsLoadTemplate($template) {
    $docpath = DATA_PATH . 'documents/';
    $templatespath = $docpath . 'templates/';
    $result = file_get_contents($templatespath . $template);
    return ($result);
}

/**
 * Returns HTML document template title by its name
 * 
 * @param string $template
 * @return string
 */
function zb_DocsLoadTemplateTitle($template) {
    $docpath = DATA_PATH . 'documents/';
    $headerspath = $docpath . 'headers/';
    $result = file_get_contents($headerspath . $template);
    return ($result);
}

/**
 * Parses HTML document template with some user data
 * 
 * @param string $template
 * @param string $login
 * @return string
 */
function zb_DocsParseTemplate($template, $login) {
    $templatebody = zb_DocsLoadTemplate($template);
    $alluserdata = zb_TemplateGetAllUserData();
    $result = zb_TemplateReplace($login, $templatebody, $alluserdata);
    return ($result);
}

/**
 * Deletes HTML document template from FS
 * 
 * @param string $template
 */
function zb_DocsDeleteTemplate($template) {
    $docpath = DATA_PATH . 'documents/';
    $headerspath = $docpath . 'headers/';
    $templatespath = $docpath . 'templates/';
    rcms_delete_files($headerspath . $template);
    rcms_delete_files($templatespath . $template);
    log_register("DOCS TEMPLATE DELETE " . $template);
}

/**
 * Shows HTML document template creation form
 * 
 * @return void
 */
function zb_DocsTemplateAddForm() {
    $inputs = wf_TextInput('newtemplatetitle', __('New template title'), '', true, '50');
    $inputs.= wf_TextArea('newtemplatebody', '', '', true, '80x20');
    $inputs.= wf_Submit('Create');
    $addform = wf_Form('', 'POST', $inputs, 'glamour');
    show_window(__('Create new template'), $addform);
}

/**
 * Shows HTML document template editing form
 * 
 * @return void
 */
function zb_DocsTemplateEditForm($template) {
    $templatebody = zb_DocsLoadTemplate($template);
    $templatetitle = zb_DocsLoadTemplateTitle($template);
    $inputs = wf_TextInput('edittemplatetitle', __('Edit template title'), $templatetitle, true, '50');
    $inputs.= wf_TextArea('edittemplatebody', '', $templatebody, true, '80x20');
    $inputs.= wf_Submit('Save');
    $addform = wf_Form('', 'POST', $inputs, 'glamour');
    show_window(__('Edit document template'), $addform);
}

/**
 * Creates new HTML document template
 * 
 * @param string $title
 * @param string $body
 * 
 * @return void
 */
function zb_DocsTemplateCreate($title, $body) {
    $docpath = DATA_PATH . 'documents/';
    $headerspath = $docpath . 'headers/';
    $templatespath = $docpath . 'templates/';
    $newtemplateid = time();
    file_put_contents($headerspath . $newtemplateid, $title);
    file_put_contents($templatespath . $newtemplateid, $body);
    log_register("DOCS TEMPLATE CREATE " . $newtemplateid);
}

/**
 * Edits existing HTML document template
 * 
 * @param string $template
 * @param string $title
 * @param string $body
 */
function zb_DocsTemplateEdit($template, $title, $body) {
    $docpath = DATA_PATH . 'documents/';
    $headerspath = $docpath . 'headers/';
    $templatespath = $docpath . 'templates/';
    $edittemplateid = $template;
    file_put_contents($headerspath . $edittemplateid, $title);
    file_put_contents($templatespath . $edittemplateid, $body);
    log_register("DOCS TEMPLATE CHANGE " . $edittemplateid);
}

?>

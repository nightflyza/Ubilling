<?php

/**
 * Returns array of all currently dead devices
 * 
 * @return array
 */
function zb_SwitchesGetAllDead() {
    $dead_switches_raw = zb_StorageGet('SWDEAD');
    if (!$dead_switches_raw) {
        $result = array();
    } else {
        $result = unserialize($dead_switches_raw);
    }
    return ($result);
}

/**
 * Returns array of each curently dead switches death time
 * 
 * @return array
 */
function zb_SwitchesGetAllDeathTime() {
    $result = array();
    $query = "SELECT `ip`,`date` from `deathtime`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['ip']] = $each['date'];
        }
    }

    return ($result);
}

/**
 * Function than sets dead switch time
 * 
 * @param $ip Switch IP
 * 
 * @return void
 */
function zb_SwitchDeathTimeSet($ip) {
    $ip = mysql_real_escape_string($ip);
    $curdatetime = curdatetime();
    $query = "INSERT INTO `deathtime` (`id` ,`ip` ,`date`) VALUES (NULL , '" . $ip . "', '" . $curdatetime . "');";
    nr_query($query);
}

/**
 * Function than resurrects dead switch :)
 * 
 * @param $ip Switch IP
 * 
 * @return void
 */
function zb_SwitchDeathTimeResurrection($ip) {
    $ip = mysql_real_escape_string($ip);
    $query = "DELETE from `deathtime` WHERE `ip`='" . $ip . "'";
    nr_query($query);
}

/**
 * Returns array of all available snmp model templates
 * 
 * @return array
 */
function zb_SwitchModelsSnmpTemplatesGetAll() {
    $allSnmpTemplates_raw = sp_SnmpGetAllModelTemplates();
    $allSnmpTemplates = array('' => __('No'));
    if (!empty($allSnmpTemplates_raw)) {
        foreach ($allSnmpTemplates_raw as $io => $each) {
            $allSnmpTemplates[$io] = $each['define']['DEVICE'];
        }
    }
    return ($allSnmpTemplates);
}

/**
 * Returns switch model add form
 * 
 * @return string
 */
function web_SwitchModelAddForm() {
    $allSnmpTemplates = zb_SwitchModelsSnmpTemplatesGetAll();
    $addinputs = wf_TextInput('newsm', 'Model', '', true);
    $addinputs.=wf_TextInput('newsmp', 'Ports', '', true, '5');
    $addinputs.=wf_Selector('newsst', $allSnmpTemplates, 'SNMP template', '');
    $addinputs.=wf_delimiter() . web_add_icon() . ' ' . wf_Submit('Create');
    $addform = wf_Form('', 'POST', $addinputs, 'glamour');
    $result = $addform;
    return ($result);
}

/**
 * Returns list of all available switch models
 * 
 * @return string
 */
function web_SwitchModelsShow() {
    $query = 'SELECT * from `switchmodels`';
    $allmodels = simple_queryall($query);

    $tablecells = wf_TableCell(__('ID'));
    $tablecells.=wf_TableCell(__('Model'));
    $tablecells.=wf_TableCell(__('Ports'));
    $tablecells.=wf_TableCell(__('SNMP template'));
    $tablecells.=wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');
    /**
     * Now its time to break up with the system
     * Our reasons are clear and listed
     * Come on and change the cause of the history
     * Take off disguise of that rotten mystery
     */
    if (!empty($allmodels)) {
        foreach ($allmodels as $io => $eachmodel) {

            $tablecells = wf_TableCell($eachmodel['id']);
            $tablecells.=wf_TableCell($eachmodel['modelname']);
            $tablecells.=wf_TableCell($eachmodel['ports']);
            $tablecells.=wf_TableCell($eachmodel['snmptemplate']);
            $switchmodelcontrols = wf_JSAlert('?module=switchmodels&deletesm=' . $eachmodel['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $switchmodelcontrols.=wf_Link('?module=switchmodels&edit=' . $eachmodel['id'], web_edit_icon());
            $tablecells.=wf_TableCell($switchmodelcontrols);
            $tablerows.=wf_TableRow($tablecells, 'row3');
        }
    }

    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');




    return ($result);
}

/**
 * Returns array of all available switch models
 * 
 * @return array
 */
function zb_SwitchModelsGetAll() {
    $query = "SELECT * from `switchmodels`";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Return some switch model data by id
 * 
 * @param int $modelid
 * @return array
 */
function zb_SwitchModelGetData($modelid) {
    $modelid = vf($modelid, 3);
    $query = "SELECT * from `switchmodels` where `id`='" . $modelid . "'";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns switch model selector
 * 
 * @param string $selectname Name of input element
 * @return string
 */
function web_SwitchModelSelector($selectname = 'switchmodelid') {
    $tmpArr = array();
    $allmodels = zb_SwitchModelsGetAll();
    if (!empty($allmodels)) {
        foreach ($allmodels as $io => $each) {
            $tmpArr[$each['id']] = $each['modelname'];
        }
    }
    $selector = wf_Selector($selectname, $tmpArr, __('Model'), '', false);
    return ($selector);
}

/**
 * Creates new switch model in database
 * 
 * @param string $name
 * @param string $ports
 * @param string $snmptemplate
 */
function ub_SwitchModelAdd($name, $ports, $snmptemplate = '') {
    $ports = vf($ports, 3);
    $nameClean = mysql_real_escape_string($name);
    $snmptemplate = mysql_real_escape_string($snmptemplate);
    if (empty($ports)) {
        $ports = 'NULL';
    } else {
        $ports = "'" . $ports . "'";
    }

    if (empty($snmptemplate)) {
        $snmptemplate = 'NULL';
    } else {
        $snmptemplate = "'" . $snmptemplate . "'";
    }
    $query = "INSERT INTO `switchmodels` (`id` ,`modelname` ,`ports`,`snmptemplate`) VALUES (NULL , '" . $nameClean . "', " . $ports . "," . $snmptemplate . ");";
    nr_query($query);
    log_register('SWITCHMODEL ADD `' . $name . '`');
}

/**
 * Deletes switch model from database by its ID
 * 
 * @param integer $modelid
 * 
 * @return void
 */
function ub_SwitchModelDelete($modelid) {
    $modelid = vf($modelid, 3);
    $query = 'DELETE FROM `switchmodels` WHERE `id` = "' . $modelid . '"';
    nr_query($query);
    log_register('SWITCHMODEL DELETE  [' . $modelid . ']');
}

/**
 * Returns switch ID selector
 * 
 * @param string $name Input element name
 * @param string $label Input element label
 * 
 * @return string
 */
function web_SwitchUplinkSelector($name, $label = '', $selected = '') {
    $tmpArr = array('' => '-');

    $query = "SELECT * from `switches` WHERE `desc` NOT LIKE '%NP%' AND `geo` != '' ORDER BY `location` ASC;";
    $allswitches = simple_queryall($query);
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $each) {
            $tmpArr[$each['id']] = $each['location'] . ' - ' . $each['ip'];
        }
    }


    $result = wf_Selector($name, $tmpArr, $label, $selected, false);
    return ($result);
}

/**
 * Returns switch creation form
 * 
 * @return string
 */
function web_SwitchFormAdd() {
    $addinputs = wf_TextInput('newip', 'IP', '', true, 20);
    $addinputs.=wf_TextInput('newlocation', 'Location', '', true, 30);
    $addinputs.=wf_TextInput('newdesc', 'Description', '', true, 30);
    $addinputs.=wf_TextInput('newsnmp', 'SNMP community', '', true, 20);
    $addinputs.=wf_TextInput('newgeo', 'Geo location', '', true, 20);
    $addinputs.=web_SwitchModelSelector('newswitchmodel');
    $addinputs.= wf_tag('br');
    $addinputs.=web_SwitchUplinkSelector('newparentid', __('Uplink switch'), '');
    $addinputs.= wf_tag('br');

    $addinputs.=wf_tag('br');
    $addinputs.=wf_Submit('Save');
    $addform = wf_Form("", 'POST', $addinputs, 'glamour');
    return($addform);
}

/**
 * Returns switch mini-map
 * 
 * @param array $switchdata
 * @return string
 */
function web_SwitchMiniMap($switchdata) {
    global $ubillingConfig;
    $ymconf = $ubillingConfig->getYmaps();
    $result = '';
    $result.= wf_tag('div', false, '', 'id="swmap" class="glamour" style="width: 97%; height:300px;"') . wf_tag('div', true);
    $result.=wf_delimiter();
    $placemarks = sm_MapDrawSwitches();
    $placemarks.=sm_MapDrawSwitchUplinks($switchdata['id']);
    $radius = 30;
    $area = sm_MapAddCircle($switchdata['geo'], $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), __('Search area'));
    $result.= sm_MapInitQuiet($switchdata['geo'], $ymconf['FINDING_ZOOM'], $ymconf['TYPE'], $area . $placemarks, '', $ymconf['LANG']);
    $result.= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
    return ($result);
}

/**
 * Shows list of all available downlink switches
 * 
 * @param int $switchId
 * 
 * @return void
 */
function web_SwitchDownlinksList($switchId) {
    $switchId = vf($switchId, 3);
    $all = zb_SwitchesGetAll();
    $downlinks = array();
    $result = '';
    if (!empty($all)) {
        if (!empty($switchId)) {
            foreach ($all as $io => $each) {
                if ($each['parentid'] == $switchId) {
                    $downlinks[$each['id']] = $each;
                }
            }
        }
    }

    if (!empty($downlinks)) {
        $allModels = zb_SwitchModelsGetAllTag();
        $cells = wf_TableCell(__('ID'));
        $cells.=wf_TableCell(__('IP'));
        $cells.=wf_TableCell(__('Location'));
        $cells.=wf_TableCell(__('Model'));
        $cells.=wf_TableCell(__('SNMP community'));
        $cells.=wf_TableCell(__('Geo location'));
        $cells.=wf_TableCell(__('Description'));
        $cells.=wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($downlinks as $io => $each) {
            $cells = wf_TableCell($each['id']);
            $cells.=wf_TableCell($each['ip']);
            $cells.=wf_TableCell($each['location']);
            $cells.=wf_TableCell(@$allModels[$each['modelid']]);
            $cells.=wf_TableCell($each['snmp']);
            $cells.=wf_TableCell($each['geo']);
            $cells.=wf_TableCell($each['desc']);
            $actLinks = wf_Link('?module=switches&edit=' . $each['id'], web_edit_icon(), false);
            $cells.=wf_TableCell($actLinks);
            $rows.= wf_TableRow($cells, 'row3');
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        show_window(__('Downlinks'), $result);
    }
}

/**
 * Returns switch edit form for some existing device ID
 * 
 * @param int $switchid
 * @return string
 */
function web_SwitchEditForm($switchid) {
    global $ubillingConfig;
    $switchid = vf($switchid, 3);
    $altCfg = $ubillingConfig->getAlter();
    $result = '';
    $allswitchmodels = zb_SwitchModelsGetAllTag();
    $switchdata = zb_SwitchGetData($switchid);

    $editinputs = wf_Selector('editmodel', $allswitchmodels, 'Model', $switchdata['modelid'], true);
    $editinputs.=wf_TextInput('editip', 'IP', $switchdata['ip'], true, 20);
    $editinputs.=wf_TextInput('editlocation', 'Location', $switchdata['location'], true, 30);
    $editinputs.=wf_TextInput('editdesc', 'Description', $switchdata['desc'], true, 30);
    $editinputs.=wf_TextInput('editsnmp', 'SNMP community', $switchdata['snmp'], true, 20);
    $editinputs.=wf_TextInput('editgeo', 'Geo location', $switchdata['geo'], true, 20);
    $editinputs.=web_SwitchUplinkSelector('editparentid', __('Uplink switch'), $switchdata['parentid']);
    $editinputs.= wf_tag('br');

    $editinputs.=wf_Submit('Save');
    $result.= wf_Form('', 'POST', $editinputs, 'glamour');
    $result.= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);

    $result.=wf_delimiter();

    $result.=wf_Link('?module=switches', __('Back'), false, 'ubButton');
    if (cfr('SWITCHPOLL')) {
        $fdbCacheName = 'exports/' . $switchdata['ip'] . '_fdb';
        if (file_exists($fdbCacheName)) {
            $result.=wf_Link('?module=switchpoller&fdbfor=' . $switchdata['ip'], wf_img('skins/menuicons/switchpoller.png') . ' ' . __('Current FDB cache'), false, 'ubButton');
        }

        if (!empty($switchdata['snmp'])) {
            $result.=wf_Link('?module=switchpoller&switchid=' . $switchid, wf_img('skins/snmp.png') . ' ' . __('SNMP query'), false, 'ubButton');
        }
    }

    if (!empty($switchdata['ip'])) {
        $result.=wf_AjaxLink('?module=switches&backgroundicmpping=' . $switchdata['ip'], wf_img('skins/ping_icon.png') . ' ' . __('ICMP ping'), 'icmppingcontainer', false, 'ubButton');
    }

    if (isset($altCfg['SW_WEBNAV'])) {
        if ($altCfg['SW_WEBNAV']) {
            $result.=' ' . wf_tag('a', false, 'ubButton', 'href="http://' . $switchdata['ip'] . '" target="_BLANK"') . wf_img('skins/ymaps/globe.png') . ' ' . __('Go to the web interface') . wf_tag('a', true) . ' ';
        }
    }


    if (cfr('SWITCHEDIT')) {
        if (!ispos($switchdata['desc'], 'NP')) {
            $result.=wf_JSAlertStyled('?module=switchreplace&switchid=' . $switchid, wf_img('skins/duplicate_icon.gif') . ' ' . __('Replacement'), __('Are you serious'), 'ubButton') . ' ';
        }
    }

    if (cfr('SWITCHESEDIT')) {
        $result.= wf_JSAlertStyled('?module=switches&switchdelete=' . $switchid, web_delete_icon() . ' ' . __('Delete'), 'Removing this may lead to irreparable results', 'ubButton');
    }

    if (!empty($switchdata['ip'])) {
        $result.=wf_AjaxLoader();
        $result.=wf_AjaxContainer('icmppingcontainer');
    }




    return ($result);
}

/**
 * Returns array of all available switches with its full data
 * 
 * @return array
 */
function zb_SwitchesGetAll() {
    $query = 'SELECT * FROM `switches` ORDER BY `id` DESC';
    $allswitches = simple_queryall($query);
    return ($allswitches);
}

/**
 * Returns array of all available switches with its full data ordered by location
 * 
 * @return array
 */
function zb_SwitchesGetAllLocationOrder() {
    $query = 'SELECT * FROM `switches` ORDER BY `location` ASC';
    $allswitches = simple_queryall($query);
    return ($allswitches);
}

/**
 * Return geo data in ip->geo format
 * 
 * @return array
 */
function zb_SwitchesGetAllGeo() {
    $query = "SELECT `ip`,`geo` from `switches`";
    $alldata = simple_queryall($query);
    $result = array();
    if (!empty($alldata)) {
        foreach ($alldata as $io => $each) {
            $result[$each['ip']] = $each['geo'];
        }
    }
    return ($result);
}

/**
 * Return geo data in ip->geo format
 * 
 * @return array
 */
function zb_SwitchesGetAllGeoId() {
    $query = "SELECT `id`,`geo` from `switches`";
    $alldata = simple_queryall($query);
    $result = array();
    if (!empty($alldata)) {
        foreach ($alldata as $io => $each) {
            $result[$each['id']] = $each['geo'];
        }
    }
    return ($result);
}

/**
 * Returns switch data by its ID
 * 
 * @param int $switchid
 * @return array
 */
function zb_SwitchGetData($switchid) {
    $switchid = vf($switchid, 3);
    $query = "SELECT * FROM `switches` WHERE `id`='" . $switchid . "' ";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns switch models array in format modelid=>name
 * 
 * @return array
 */
function zb_SwitchModelsGetAllTag() {
    $allmodels = zb_SwitchModelsGetAll();
    $result = array();
    if (!empty($allmodels)) {
        foreach ($allmodels as $io => $eachmodel) {
            $result[$eachmodel['id']] = $eachmodel['modelname'];
        }
    }
    return ($result);
}

/**
 * Returns result of fast icmp ping
 * 
 * @param string $ip devide IP to ping
 * @return bool
 */
function zb_PingICMP($ip) {
    $globconf = parse_ini_file(CONFIG_PATH . "billing.ini");
    $ping = $globconf['PING'];
    $sudo = $globconf['SUDO'];
    $ping_command = $sudo . ' ' . $ping . ' -i 0.01 -c 1 ' . $ip;
    $ping_result = shell_exec($ping_command);
    if (strpos($ping_result, 'ttl')) {
        return (true);
    } else {
        return(false);
    }
}

/**
 * Returns web led indicator for some device with fast ICMP ping
 * 
 * @param string $ip device ip to check
 * @return string
 */
function zb_SwitchAlive($ip) {
    if (zb_PingICMP($ip)) {
        $result = web_green_led();
    } else {
        $result = web_red_led();
    }
    return ($result);
}

/**
 * Logs array of switches to deadlog (timemachine)
 *  
 * @param int   $currenttime current timestamp
 * @param array $deadSwitches dead switches array
 */
function zb_SwitchesDeadLog($currenttime, $deadSwitches) {
    $date = curdatetime();
    $timestamp = $currenttime;
    $logData = serialize($deadSwitches);
    $query = "INSERT INTO `switchdeadlog` (`id` ,`date` ,`timestamp` ,`swdead`)
              VALUES (
              NULL , '" . $date . "', '" . $timestamp . "', '" . $logData . "');";
    nr_query($query);
}

/**
 * Performs all switches ping test and returns dead devices array
 * 
 * @global object $ubillingConfig
 * @return array
 */
function zb_SwitchesRepingAll() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $allswitches = zb_SwitchesGetAllLocationOrder();
    $deadswitches = array();
    $deathTime = zb_SwitchesGetAllDeathTime();


    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {

            if (!ispos($eachswitch['desc'], 'NP')) {
                if (!zb_PingICMP($eachswitch['ip'])) {
                    $secondChance = zb_PingICMP($eachswitch['ip']);
                    if (!$secondChance) {
                        $lastChance = zb_PingICMP($eachswitch['ip']);
                        if (!$lastChance) {
                            if (empty($altCfg['SWITCH_PING_CUSTOM_SCRIPT'])) {
                                //yep, switch looks like it really down
                                $deadswitches[$eachswitch['ip']] = $eachswitch['location'];
                                if (!isset($deathTime[$eachswitch['ip']])) {
                                    zb_SwitchDeathTimeSet($eachswitch['ip']);
                                }
                            } else {
                                //really last-last chance
                                $customTestCommand = $altCfg['SWITCH_PING_CUSTOM_SCRIPT'] . ' ' . $eachswitch['ip'];
                                $customScriptRun = shell_exec($customTestCommand);
                                $customScriptRun = trim($customScriptRun);
                                if ($customScriptRun != '1') {
                                    $deadswitches[$eachswitch['ip']] = $eachswitch['location'];
                                    if (!isset($deathTime[$eachswitch['ip']])) {
                                        zb_SwitchDeathTimeSet($eachswitch['ip']);
                                    }
                                } else {
                                    zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                                }
                            }
                        } else {
                            zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                        }
                    } else {
                        zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                    }
                } else {
                    zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                }
            }
        }
    }

    $newdata = serialize($deadswitches);
    zb_StorageSet('SWDEAD', $newdata);
    return ($deadswitches);
}

/**
 * Returns list of all available switches devices with its controls. Also catches ajaxping and forcereping events.
 * 
 * @return string
 */
function web_SwitchesShow() {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $allswitches = zb_SwitchesGetAll();
    $modelnames = zb_SwitchModelsGetAllTag();
    $currenttime = time();
    $reping_timeout = $alterconf['SW_PINGTIMEOUT'];
    $deathTime = zb_SwitchesGetAllDeathTime();

    //counters
    $countTotal = 0;
    $countAlive = 0;
    $countDead = 0;
    $countNp = 0;
    $countOnMap = 0;
    $countSwpoll = 0;
    $countMtsigmon = 0;
    $countOlt = 0;
    $countLinked = 0;


    //non realtime switches pinging
    $last_pingtime = zb_StorageGet('SWPINGTIME');

    if (!$last_pingtime) {
        zb_SwitchesRepingAll();
        zb_StorageSet('SWPINGTIME', $currenttime);
        $last_pingtime = $currenttime;
    } else {
        if ($currenttime > ($last_pingtime + ($reping_timeout * 60))) {
            // normal timeout reping sub here
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
        }
    }

    //force total reping and update cache
    if (wf_CheckGet(array('forcereping'))) {
        zb_SwitchesRepingAll();
        zb_StorageSet('SWPINGTIME', $currenttime);
        if (wf_CheckGet(array('ajaxping'))) {
            $dead_raw = zb_StorageGet('SWDEAD');
            $deathTime = zb_SwitchesGetAllDeathTime();
            $deadarr = array();
            $ajaxResult = '';
            if ($dead_raw) {
                $deadarr = unserialize($dead_raw);
                if (!empty($deadarr)) {
                    //there is some dead switches
                    $deadcount = sizeof($deadarr);
                    if ($alterconf['SWYMAP_ENABLED']) {
                        //getting geodata
                        $switchesGeo = zb_SwitchesGetAllGeo();
                    }
                    //ajax container
                    $ajaxResult.=wf_tag('div', false, '', 'id="switchping"');

                    foreach ($deadarr as $ip => $switch) {
                        if ($alterconf['SWYMAP_ENABLED']) {
                            if (isset($switchesGeo[$ip])) {
                                if (!empty($switchesGeo[$ip])) {
                                    $devicefind = wf_Link('?module=switchmap&finddevice=' . $switchesGeo[$ip], wf_img('skins/icon_search_small.gif', __('Find on map'))) . ' ';
                                } else {
                                    $devicefind = '';
                                }
                            } else {
                                $devicefind = '';
                            }
                        } else {
                            $devicefind = '';
                        }
                        //check morgue records for death time
                        if (isset($deathTime[$ip])) {
                            $deathClock = wf_img('skins/clock.png', __('Switch dead since') . ' ' . $deathTime[$ip]) . ' ';
                        } else {
                            $deathClock = '';
                        }

                        //switch location link
                        $switchLocator = wf_Link('?module=switches&gotoswitchbyip=' . $ip, web_edit_icon(__('Go to switch')));

                        //add switch as dead
                        $ajaxResult.=$devicefind . ' ' . $switchLocator . ' ' . $deathClock . $ip . ' - ' . $switch . '<br>';
                    }
                } else {
                    $ajaxResult = __('Switches are okay, everything is fine - I guarantee');
                }
            }
            $ajaxResult.=wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s");
            print($ajaxResult);
            //darkvoid update
            $notifyArea = new DarkVoid();
            $notifyArea->flushCache();

            die();
        }
    }

    //load dead switches cache
    $dead_switches_raw = zb_StorageGet('SWDEAD');
    if (!$dead_switches_raw) {
        $dead_switches = array();
    } else {
        $dead_switches = unserialize($dead_switches_raw);
    }

    //create new ADcomments object if enabled 
    if ($alterconf['ADCOMMENTS_ENABLED']) {
        $adcomments = new ADcomments('SWITCHES');
    }

    $tablecells = wf_TableCell(__('ID'));
    $tablecells.=wf_TableCell(__('IP'));
    $tablecells.=wf_TableCell(__('Location'));
    $tablecells.=wf_TableCell(__('Active'));
    $tablecells.=wf_TableCell(__('Model'));
    $tablecells.=wf_TableCell(__('SNMP community'));
    $tablecells.=wf_TableCell(__('Geo location'));
    $tablecells.=wf_TableCell(__('Description'));
    $tablecells.=wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');
    $lighter = 'onmouseover="this.className = \'row2\';" onmouseout="this.className = \'row3\';" ';

    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            if (isset($dead_switches[$eachswitch['ip']])) {
                if (isset($deathTime[$eachswitch['ip']])) {
                    $obituary = __('Switch dead since') . ' ' . $deathTime[$eachswitch['ip']];
                } else {
                    $obituary = '';
                }
                $aliveled = web_red_led($obituary);
                $aliveflag = '0';
                $countDead++;
            } else {
                if (strpos($eachswitch['desc'], 'NP') === false) {
                    $aliveled = web_green_led();
                    $aliveflag = '1';
                    $countAlive++;
                } else {
                    $aliveled = web_yellow_led();
                    $aliveflag = '2';
                    $countNp++;
                }
            }


            $tablecells = wf_TableCell($eachswitch['id']);
            $tablecells.=wf_TableCell($eachswitch['ip'], '', '', 'sorttable_customkey="' . ip2int($eachswitch['ip']) . '"');
            $tablecells.=wf_TableCell($eachswitch['location']);
            $tablecells.=wf_TableCell($aliveled, '', '', 'sorttable_customkey="' . $aliveflag . '"');
            $tablecells.=wf_TableCell(@$modelnames[$eachswitch['modelid']]);
            $tablecells.=wf_TableCell($eachswitch['snmp']);
            $tablecells.=wf_TableCell($eachswitch['geo']);
            $tablecells.=wf_TableCell($eachswitch['desc']);
            $switchcontrols = '';
            if (cfr('SWITCHESEDIT')) {
                $switchcontrols.=wf_Link('?module=switches&edit=' . $eachswitch['id'], web_edit_icon());
            }


            if (cfr('SWITCHPOLL')) {
                if ((!empty($eachswitch['snmp'])) AND ( ispos($eachswitch['desc'], 'SWPOLL'))) {
                    $switchcontrols.='&nbsp;' . wf_Link('?module=switchpoller&switchid=' . $eachswitch['id'], wf_img('skins/snmp.png', __('SNMP query')));
                    $countSwpoll++;
                }
            }

            if ($alterconf['SWYMAP_ENABLED']) {
                if (!empty($eachswitch['geo'])) {
                    $switchcontrols.=wf_Link('?module=switchmap&finddevice=' . $eachswitch['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')));
                    $countOnMap++;
                }

                if (!empty($eachswitch['parentid'])) {
                    $switchcontrols.= wf_Link('?module=switchmap&finddevice=' . $eachswitch['geo'] . '&showuplinks=true&traceid=' . $eachswitch['id'], wf_img('skins/ymaps/uplinks.png', __('Uplink switch')));
                    $countLinked++;
                }
            }

            if (ispos($eachswitch['desc'], 'MTSIGMON')) {
                $countMtsigmon++;
            }

            if (ispos($eachswitch['desc'], 'OLT')) {
                $countOlt++;
            }

            if ($alterconf['ADCOMMENTS_ENABLED']) {
                $switchcontrols.=$adcomments->getCommentsIndicator($eachswitch['id']);
            }

            if (isset($alterconf['SW_WEBNAV'])) {
                if ($alterconf['SW_WEBNAV']) {
                    $switchcontrols.=' ' . wf_tag('a', false, '', 'href="http://' . $eachswitch['ip'] . '" target="_BLANK"') . wf_img('skins/ymaps/globe.png', __('Go to the web interface')) . wf_tag('a', true);
                }
            }

            $tablecells.=wf_TableCell($switchcontrols);
            $tablerows.=wf_tag('tr', false, 'row3', $lighter);
            $tablerows.=$tablecells;
            $tablerows.=wf_tag('tr', true);
            $countTotal++;
        }
    }
    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');

    $result.=wf_img('skins/icon_active.gif') . ' ' . __('Alive switches') . ' - ' . ($countAlive + $countNp) . ' (' . $countAlive . '+' . $countNp . ')' . wf_tag('br');
    $result.=wf_img('skins/icon_inactive.gif') . ' ' . __('Dead switches') . ' - ' . $countDead . wf_tag('br');
    $result.=wf_img('skins/yellow_led.png') . ' ' . __('NP switches') . ' - ' . $countNp . wf_tag('br');
    $result.=wf_img('skins/snmp.png') . ' ' . __('SWPOLL query') . ' - ' . $countSwpoll . wf_tag('br');
    $result.=wf_img('skins/wifi.png') . ' ' . __('MTSIGMON devices') . ' - ' . $countMtsigmon . wf_tag('br');
    $result.=wf_img('skins/pon_icon.gif') . ' ' . __('OLT devices') . ' - ' . $countOlt . wf_tag('br');

    $result.=wf_img('skins/icon_search_small.gif') . ' ' . __('Placed on map') . ' - ' . $countOnMap . wf_tag('br');
    $result.=wf_img('skins/ymaps/uplinks.png') . ' ' . __('Have uplinks') . ' - ' . $countLinked . wf_tag('br');

    $result.=wf_tag('br') . wf_tag('b') . __('Total') . ': ' . $countTotal . wf_tag('b', true) . wf_tag('br');


    return ($result);
}

/**
 * Creates new switch device in database
 * 
 * @param int    $modelid
 * @param string $ip
 * @param string $desc
 * @param string $location
 * @param string $snmp
 * @param string $geo
 * @param int    $parentid
 */
function ub_SwitchAdd($modelid, $ip, $desc, $location, $snmp, $geo, $parentid = '') {
    $modelid = vf($modelid, 3);
    $ip = mysql_real_escape_string($ip);
    $desc = mysql_real_escape_string($desc);
    $location = mysql_real_escape_string($location);
    $snmp = mysql_real_escape_string($snmp);
    $parentid = vf($parentid, 3);
    if (!empty($parentid)) {
        $parentid = "'" . $parentid . "'";
    } else {
        $parentid = 'NULL';
    }
    $query = "INSERT INTO `switches` (`id` ,`modelid` ,`ip` ,`desc` ,`location` ,`snmp`,`geo`,`parentid`) "
            . "VALUES ('', '" . $modelid . "', '" . $ip . "', '" . $desc . "', '" . $location . "', '" . $snmp . "','" . $geo . "', " . $parentid . " );";
    nr_query($query);
    $lastid = simple_get_lastid('switches');
    log_register('SWITCH ADD [' . $lastid . '] IP `' . $ip . '` ON LOC `' . $location . '`');
    show_window(__('Add switch'), __('Was added new switch') . ' ' . $ip . ' ' . $location);
}

/**
 * Checks is switch parent for someone?
 * 
 * @param int $switchid
 * @return bool
 */
function ub_SwitchIsParent($switchid) {
    $switchid = vf($switchid, 3);
    $result = false;
    $query = "SELECT `id` from `switches` WHERE `parentid`='" . $switchid . "';";
    $raw = simple_query($query);
    if (!empty($raw)) {
        $result = true;
    }
    return ($result);
}

/**
 * Flushes child switches for some switch
 * 
 * @param int $switchid
 */
function ub_SwitchFlushChilds($switchid) {
    $switchid = vf($switchid, 3);
    $query = "UPDATE `switches` SET `parentid`=NULL WHERE `parentid`='" . $switchid . "';";
    nr_query($query);
    log_register('SWITCH FLUSH CHILDS [' . $switchid . ']');
}

/**
 * Deletes switch from database by its ID
 * 
 * @param int $switchid existing switch database ID
 */
function ub_SwitchDelete($switchid) {
    $switchid = vf($switchid);
    $switchdata = zb_SwitchGetData($switchid);
    $query = "DELETE from `switches` WHERE `id`='" . $switchid . "'";
    nr_query($query);
    log_register('SWITCH DELETE [' . $switchid . '] IP ' . $switchdata['ip'] . ' LOC ' . $switchdata['location']);
}

/**
 * Returns dead switches json data for timemachine calendar view
 * 
 * @return string
 */
function ub_JGetSwitchDeadLog() {
    $cyear = curyear();

    $query = "SELECT `id`,`date`,`timestamp`,`swdead` from `switchdeadlog` WHERE `date` LIKE '" . $cyear . "-%' ORDER BY `id` ASC";
    $alldead = simple_queryall($query);

    $i = 1;
    $logcount = sizeof($alldead);
    $result = '';

    if (!empty($alldead)) {
        foreach ($alldead as $io => $eachdead) {
            if ($i != $logcount) {
                $thelast = ',';
            } else {
                $thelast = '';
            }

            $startdate = strtotime($eachdead['date']);
            $startdate = date("Y, n-1, j", $startdate);
            $deadData_raw = $eachdead['swdead'];
            $deadData = unserialize($deadData_raw);
            $deadcount = sizeof($deadData);


            $result.="
                      {
                        title: '" . date("H:i:s", $eachdead['timestamp']) . " - (" . $deadcount . ")',
                        start: new Date(" . $startdate . "),
                        end: new Date(" . $startdate . "),
                        className : 'undone',
                        url: '?module=switches&timemachine=true&snapshot=" . $eachdead['id'] . "'
		      }
                    " . $thelast;
            $i++;
        }
    }
    return ($result);
}

/**
 * Renders dead switches top
 * 
 * @return string
 */
function web_DeadSwitchesTop() {
    $topThreshold = 10;
    $result = '';
    $cyear = curyear();
    $query = "SELECT `id`,`date`,`timestamp`,`swdead` from `switchdeadlog` WHERE `date` LIKE '" . $cyear . "-%' ORDER BY `id` ASC";
    $rawData = simple_queryall($query);
    $topTmp = array();
    $totalCount = 0;

    if (!empty($rawData)) {
        foreach ($rawData as $io => $each) {
            if (!empty($each['swdead'])) {
                $deadData = unserialize($each['swdead']);
                if (!empty($deadData)) {
                    foreach ($deadData as $eachDeadIp => $eachDeadName) {
                        if (isset($topTmp[$eachDeadIp])) {
                            $topTmp[$eachDeadIp]['count'] ++;
                        } else {
                            $topTmp[$eachDeadIp]['count'] = 1;
                            $topTmp[$eachDeadIp]['name'] = $eachDeadName;
                        }
                        $totalCount++;
                    }
                }
            }
        }
    }

    if (!empty($topTmp)) {
        $cells = wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('Location'));
        $cells.= wf_TableCell(__('Count'));
        $cells.= wf_TableCell(__('Visual'));
        $rows = wf_TableRow($cells, 'row1');


        foreach ($topTmp as $io => $each) {
            if ($each['count'] > $topThreshold) {
                $cells = wf_TableCell($io);
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell($each['count']);
                $cells.= wf_TableCell(web_bar($each['count'], $totalCount));
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
    }

    return ($result);
}

/**
 * Shows time machine snapshot by its ID
 * 
 * @param int $snapshotid
 */
function ub_SwitchesTimeMachineShowSnapshot($snapshotid) {
    $snapshotid = vf($snapshotid, 3);
    $query = "SELECT * from `switchdeadlog` WHERE `id`='" . $snapshotid . "'";
    $deaddata = simple_query($query);
    $deathTime = zb_SwitchesGetAllDeathTime();

    if (!empty($deaddata)) {
        $deadarr = unserialize($deaddata['swdead']);

        $cells = wf_TableCell(__('Switch dead since'));
        $cells.= wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('Location'));
        $rows = wf_TableRow($cells, 'row1');


        if (!empty($deadarr)) {
            foreach ($deadarr as $ip => $location) {
                $cells = wf_TableCell(@$deathTime[$ip]);
                $cells.= wf_TableCell($ip);
                $cells.= wf_TableCell($location);
                $rows.=wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        show_window(__('Dead switches') . ' ' . $deaddata['date'], $result);
        show_window('', wf_Link("?module=switches&timemachine=true", 'Back', false, 'ubButton'));
    }
}

/**
 * Flushes time machine switchdead log table
 * 
 * @return void
 */
function ub_SwitchesTimeMachineCleanup() {
    $query = "TRUNCATE TABLE `switchdeadlog`;";
    nr_query($query);
    log_register("SWITCH TIMEMACHINE FLUSH");
}

/**
 * Returns time machine search form
 * 
 * @return string
 */
function web_SwitchTimeMachineSearchForm() {
    $inputs = wf_TextInput('switchdeadlogsearch', __('Location') . ', ' . __('IP'), '', false, 30);
    $inputs.= wf_Submit(__('Search'));
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Do the search in dead switches time machine
 * 
 * @param string $query
 * @return string
 */
function ub_SwitchesTimeMachineSearch($request) {
    $request = strtolower_utf8($request);
    $result = '';
    $query = "SELECT * from `switchdeadlog` ORDER BY `id` DESC";
    $raw = simple_queryall($query);
    $deadcount = 0;

    $tmpArr = array();
    if (!empty($raw)) {
        foreach ($raw as $io => $each) {
            if (!empty($each)) {
                $switchData = unserialize($each['swdead']);
                foreach ($switchData as $switchIp => $switchLocation) {

                    if ((ispos(strtolower_utf8($switchIp), $request)) OR ( ispos(strtolower_utf8($switchLocation), $request))) {
                        $searchId = zb_rand_string(8);
                        $tmpArr[$searchId]['date'] = $each['date'];
                        $tmpArr[$searchId]['ip'] = $switchIp;
                        $tmpArr[$searchId]['location'] = $switchLocation;
                    }
                }
            }
        }
    }

    if (!empty($tmpArr)) {
        $cells = wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('Location'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($tmpArr as $ia => $eachResult) {
            $cells = wf_TableCell($eachResult['date']);
            $cells.= wf_TableCell($eachResult['ip']);
            $cells.= wf_TableCell($eachResult['location']);
            $rows.= wf_TableRow($cells, 'row3');
            $deadcount++;
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        $result.= __('Total') . ': ' . $deadcount;
    } else {
        $result = __('Nothing found');
    }
    return ($result);
}

/**
 * Returns NP switches replacement form
 * 
 * @param int $fromSwitchId
 * 
 * @return string
 */
function zb_SwitchReplaceForm($fromSwitchId) {
    $fromSwitchId = vf($fromSwitchId, 3);
    $result = '';
    $query = "SELECT * from `switches` WHERE `desc` LIKE '%NP%' ORDER BY `id` DESC";
    $raw = simple_queryall($query);
    $paramsNp = array();
    $employee = array();
    $employee = ts_GetActiveEmployee();

    if (!empty($raw)) {
        foreach ($raw as $io => $eachNp) {
            $paramsNp[$eachNp['id']] = $eachNp['location'] . ' - ' . $eachNp['ip'];
        }
    }

    $inputs = wf_HiddenInput('switchreplace', $fromSwitchId);
    $inputs.= wf_Selector('toswtichreplace', $paramsNp, 'NP ' . __('Switch'), '', false);
    $inputs.= wf_Selector('replaceemployeeid', $employee, __('Worker'), '', false);
    $inputs.= wf_Submit('Save');
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    $result.= wf_CleanDiv();
    $result.= wf_delimiter();
    $result.= wf_Link('?module=switches&edit=' . $fromSwitchId, __('Back'), false, 'ubButton');
    return ($result);
}

/**
 * Performs switch replacement in database
 * 
 * @param int $fromId
 * @param int $toId
 * @param int $employeeid
 * 
 * @return void
 */
function zb_SwitchReplace($fromId, $toId, $employeeId) {
    $fromId = vf($fromId, 3);
    $toId = vf($toId, 3);
    $employeeId = vf($employeeId, 3);
    $allEmployees = ts_GetAllEmployee();
    $fromData = zb_SwitchGetData($fromId);
    $toData = zb_SwitchGetData($toId);
    if (!empty($fromData)) {
        //copy geo coordinates to new switch
        simple_update_field('switches', 'geo', $fromData['geo'], "WHERE `id`='" . $toId . "'");
        //setting new description and remove NP flag
        $newDescriptionTo = str_replace('NP', 'm:' . @$allEmployees[$employeeId], $toData['desc']);
        simple_update_field('switches', 'desc', $newDescriptionTo, "WHERE `id`='" . $toId . "'");
        //copy location
        simple_update_field('switches', 'location', $fromData['location'], "WHERE `id`='" . $toId . "'");
        //copy switch parent ID
        if (!empty($fromData['parentid'])) {
            simple_update_field('switches', 'parentid', $fromData['parentid'], "WHERE `id`='" . $toId . "'");
        } else {
            $parentId_q = "UPDATE `switches` SET `parentid`=NULL WHERE `id`='" . $toId . "';";
            nr_query($parentId_q);
        }
        //moving childs if it present
        simple_update_field('switches', 'parentid', $toId, "WHERE `parentid`='" . $fromId . "'");

        // doing old switch cleanup and disabling it
        simple_update_field('switches', 'geo', '', "WHERE `id`='" . $fromId . "'");
        $newFromLocation = __('removed from') . ': ' . $fromData['location'];
        simple_update_field('switches', 'location', $newFromLocation, "WHERE `id`='" . $fromId . "'");
        $newFromDesc = 'NP u:' . @$allEmployees[$employeeId];
        simple_update_field('switches', 'desc', $newFromDesc, "WHERE `id`='" . $fromId . "'");
        $parentIdFrom_q = "UPDATE `switches` SET `parentid`=NULL WHERE `id`='" . $fromId . "';";
        nr_query($parentIdFrom_q);
        log_register("SWITCH REPLACE FROM [" . $fromId . "] TO [" . $toId . "] EMPLOYEE [" . $employeeId . "]");
    } else {
        show_error(__('Strange exeption') . ': FROM_SWITCH_EMPTY_DATA');
    }
}

/**
 * Trys to detect switch ID by its IP
 * 
 * @param string $ip
 * @return int
 */
function zb_SwitchGetIdbyIP($ip) {
    $result = '';
    $ip = mysql_real_escape_string($ip);
    $query = "SELECT `id`,`ip` from `switches` WHERE `ip`='" . $ip . "' LIMIT 1;";
    $raw = simple_query($query);
    if (!empty($raw)) {
        $result = $raw['id'];
    }
    return ($result);
}


?>

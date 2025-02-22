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
 * Returns array of each curently dead switches death time as ip=>datetime
 * 
 * @return array
 */
function zb_SwitchesGetAllDeathTime() {
    $result = array();
    $deathTimeDb = new NyanORM('deathtime');
    $all = $deathTimeDb->getAll();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['ip']] = $each['date'];
        }
    }
    return ($result);
}

/**
 * Sets dead switch death time
 * 
 * @param $ip Switch IP
 * 
 * @return void
 */
function zb_SwitchDeathTimeSet($ip) {
    $ip = ubRouting::filters($ip, 'mres');
    $curdatetime = curdatetime();
    $deathTimeDb = new NyanORM('deathtime');
    $deathTimeDb->data('ip', $ip);
    $deathTimeDb->data('date', $curdatetime);
    $deathTimeDb->create();
}

/**
 * Function than resurrects dead switch :)
 * 
 * @param $ip Switch IP
 * 
 * @return void
 */
function zb_SwitchDeathTimeResurrection($ip) {
    $ip = ubRouting::filters($ip, 'mres');
    $deathTimeDb = new NyanORM('deathtime');
    $deathTimeDb->where('ip', '=', $ip);
    $deathTimeDb->delete();
}

/**
 * Returns array of all available snmp model templates as name=>device description
 * 
 * @return array
 */
function zb_SwitchModelsSnmpTemplatesGetAll() {
    $allSnmpTemplatesRaw = sp_SnmpGetAllModelTemplates();
    $allSnmpTemplates = array('' => __('None'));
    if (!empty($allSnmpTemplatesRaw)) {
        foreach ($allSnmpTemplatesRaw as $io => $each) {
            if (isset($each['define'])) {
                if (isset($each['define']['DEVICE'])) {
                    $allSnmpTemplates[$io] = $each['define']['DEVICE'];
                } else {
                    $allSnmpTemplates[$io] = '⚠️ ' . __('Template') . ' ' . $io . ' - ' . __('is corrupted');
                }
            } else {
                $allSnmpTemplates[$io] = '⚠️ ' . __('Template') . ' ' . $io . ' - ' . __('is corrupted');
            }
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
    $addinputs .= wf_TextInput('newsmp', 'Ports', '', true, '5');
    $addinputs .= wf_Selector('newsst', $allSnmpTemplates, 'SNMP template', '');
    $addinputs .= wf_delimiter() . web_add_icon() . ' ' . wf_Submit('Create');
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
    global $ubillingConfig;
    $result = '';
    $allmodels = zb_SwitchModelsGetAll();
    $allSwitches = zb_SwitchesGetAll();
    $allSnmpTemplates = zb_SwitchModelsSnmpTemplatesGetAll();
    $modelsCount = array();

    //switch devices count
    if (!empty($allSwitches)) {
        foreach ($allSwitches as $io => $eachSwitchData) {
            if (isset($modelsCount[$eachSwitchData['modelid']])) {
                $modelsCount[$eachSwitchData['modelid']]++;
            } else {
                $modelsCount[$eachSwitchData['modelid']] = 1;
            }
        }
    }

    //PON devices count
    if ($ubillingConfig->getAlterParam('PON_ENABLED')) {
        $onuDevicesDb = new NyanORM('pononu');
        $onuDevicesDb->selectable(array('id', 'onumodelid'));
        $allOnu = $onuDevicesDb->getAll();
        if (!empty($allOnu)) {
            foreach ($allOnu as $io => $eachOnuData) {
                if (isset($modelsCount[$eachOnuData['onumodelid']])) {
                    $modelsCount[$eachOnuData['onumodelid']]++;
                } else {
                    $modelsCount[$eachOnuData['onumodelid']] = 1;
                }
            }
        }
    }

    /**
     * Now its time to break up with the system
     * Our reasons are clear and listed
     * Come on and change the cause of the history
     * Take off disguise of that rotten mystery
     */
    if (!empty($allmodels)) {
        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Model'));
        $tablecells .= wf_TableCell(__('Devices'));
        $tablecells .= wf_TableCell(__('Ports'));
        $tablecells .= wf_TableCell(__('SNMP template'));
        $tablecells .= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        foreach ($allmodels as $io => $eachmodel) {
            $availDevicesCount = (isset($modelsCount[$eachmodel['id']])) ? $modelsCount[$eachmodel['id']] : 0;
            $snmpLabel = '';
            $snmpTemplate = $eachmodel['snmptemplate'];
            if (!empty($snmpTemplate)) {
                if (isset($allSnmpTemplates[$snmpTemplate])) {
                    $snmpLabel .= $allSnmpTemplates[$snmpTemplate];
                } else {
                    $snmpLabel .= __('Template') . ' ' . $snmpTemplate . ' - ' . __('Not exists');
                    show_error(__('Template') . ' ' . $snmpTemplate . ' ' . __('for') . ' ' . __('Equipment models') . ' ' . __('ID') . ' [' . $eachmodel['id'] . ']' . ' - ' . __('Not exists'));
                }
            }
            $tablecells = wf_TableCell($eachmodel['id']);
            $tablecells .= wf_TableCell($eachmodel['modelname']);
            $tablecells .= wf_TableCell($availDevicesCount);
            $tablecells .= wf_TableCell($eachmodel['ports']);
            $tablecells .= wf_TableCell($snmpLabel);
            $switchmodelcontrols = wf_JSAlert('?module=switchmodels&deletesm=' . $eachmodel['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $switchmodelcontrols .= wf_Link('?module=switchmodels&edit=' . $eachmodel['id'], web_edit_icon());
            $tablecells .= wf_TableCell($switchmodelcontrols);
            $tablerows .= wf_TableRow($tablecells, 'row5');
        }
        $result .= wf_TableBody($tablerows, '100%', '0', 'sortable');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }



    return ($result);
}

/**
 * Returns array of all available switch models
 * 
 * @return array
 */
function zb_SwitchModelsGetAll() {
    global $ubillingConfig;
    $sortByModelName = $ubillingConfig->getAlterParam('DEVICES_LISTS_SORT_BY_MODELNAME');

    $query = "SELECT * from `switchmodels`";
    $result = simple_queryall($query);

    if (!empty($result) and $sortByModelName) {
        $result = zb_sortArray($result, 'modelname');
    }

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
 * @param array $allmodels available models array
 * @return string
 */
function web_SwitchModelSelector($selectname = 'switchmodelid', $allmodels = array()) {
    $tmpArr = array();
    if (empty($allmodels)) {
        $allmodels = zb_SwitchModelsGetAll();
    }
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
    $newId = simple_get_lastid('switchmodels');
    log_register('SWITCHMODEL ADD `' . $name . '`  [' . $newId . ']');
}

/**
 * Deletes switch model from database by its ID
 * 
 * @param integer $modelId
 * 
 * @return void/string on error
 */
function ub_SwitchModelDelete($modelId) {
    $modelId = ubRouting::filters($modelId, 'int');
    $result = '';
    if (!empty($modelId)) {
        $switches = new NyanORM('switches');
        $switches->where('modelid', '=', $modelId);
        $switches->selectable('id');
        $switchesUsingThisModel = $switches->getAll();

        $ponOnus = new NyanORM('pononu');
        $ponOnus->where('onumodelid', '=', $modelId);
        $ponOnus->selectable('id');
        $onuUsingThisModel = $ponOnus->getAll();
        //is this model used by some devices?
        if (empty($switchesUsingThisModel) and empty($onuUsingThisModel)) {
            $switchModels = new NyanORM('switchmodels');
            $switchModels->where('id', '=', $modelId);
            $switchModels->delete();
            log_register('SWITCHMODEL DELETE  [' . $modelId . ']');
        } else {
            $result .= __('You know, we really would like to let you perform this action, but our conscience does not allow us to do');
            log_register('SWITCHMODEL DELETE  [' . $modelId . '] FAIL IN_USE');
        }
    } else {
        $result .= __('Model') . ' ' . __('is empty');
    }
    return ($result);
}

/**
 * Returns switch ID selector
 * 
 * @param string $name Input element name
 * @param string $label Input element label
 * @param int $selected preselected in switch ID
 * @param int $currentSwitchId switch for whom this widget showed
 * @param bool $notSearchable Explictly disables searchable functionality
 * 
 * @return string
 */
function web_SwitchUplinkSelector($name, $label = '', $selected = '', $currentSwitchId = '', $notSearchable = false) {
    global $ubillingConfig;
    $result = '';
    $tmpArr = array('' => '-');
    $validSwitches = array();
    $allswitchesRaw = array();
    $searchableFlag = ($ubillingConfig->getAlterParam('SWITCHUPL_SEARCHBL')) ? true : false;
    if ($notSearchable) {
        $searchableFlag = false;
    }

    $query = "SELECT * from `switches` WHERE `desc` NOT LIKE '%NP%' AND `geo` != '' ORDER BY `location` ASC;";
    $allswitches = simple_queryall($query);
    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $each) {
            //switches with geo and without NP
            $validSwitches[$each['id']] = $each;
        }
    }

    if (!empty($allswitches)) {
        //checks for preventing loops
        $alllinks = array();
        $tmpSwitches = zb_SwitchesGetAll("ORDER BY `location` ASC");
        if (!empty($tmpSwitches)) {
            foreach ($tmpSwitches as $io => $each) {
                //transform array to id=>switchdata
                $allswitchesRaw[$each['id']] = $each;
            }

            //making id=>parentid array
            foreach ($tmpSwitches as $io => $each) {
                $alllinks[$each['id']] = $each['parentid'];
            }
        }

        foreach ($allswitchesRaw as $io => $each) {
            if ((sm_CheckLoop($alllinks, $currentSwitchId, $each['id'])) and ($each['id'] != $currentSwitchId)) {
                if (isset($validSwitches[$each['id']])) {
                    $tmpArr[$each['id']] = $each['location'] . ' - ' . $each['ip'];
                }
            }
        }
    }

    if ($searchableFlag) {
        $result .= wf_SelectorSearchable($name, $tmpArr, $label, $selected, false);
    } else {
        $result .= wf_Selector($name, $tmpArr, $label, $selected, false);
    }
    return ($result);
}

/**
 * Returns switch creation form
 * 
 * @return string
 */
function web_SwitchFormAdd() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $swGroupsEnabled = $ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED');
    $equipmentModels = zb_SwitchModelsGetAll();
    if (!empty($equipmentModels)) {
        $addinputs = wf_TextInput('newip', 'IP', '', true, 20, 'ip');
        $addinputs .= wf_TextInput('newlocation', 'Location', '', true, 30);
        $addinputs .= wf_TextInput('newdesc', 'Description', '', true, 30);
        $addinputs .= wf_TextInput('newsnmp', 'SNMP community', '', true, 20);
        $addinputs .= wf_TextInput('newsnmpwrite', 'SNMP write community', '', true, 20);
        if ($altCfg['SWITCHES_EXTENDED']) {
            $addinputs .= wf_TextInput('newswid', 'Switch ID', '', true, 20, 'mac');
        }
        $addinputs .= wf_TextInput('newgeo', 'Geo location', '', true, 20, 'geo');
        $addinputs .= web_SwitchModelSelector('newswitchmodel', $equipmentModels);
        $addinputs .= wf_tag('br');
        $addinputs .= web_SwitchUplinkSelector('newparentid', __('Uplink switch'), '', '', true);
        $addinputs .= wf_tag('br');

        if (cfr('SWITCHGROUPS') and $swGroupsEnabled) {
            $switchGroups = new SwitchGroups();
            $addinputs .= $switchGroups->renderSwitchGroupsSelector('newswgroup') . wf_delimiter();
        }

        $addinputs .= wf_tag('br');
        $addinputs .= wf_Submit('Save');
        $addform = wf_Form("", 'POST', $addinputs, 'glamour');
    } else {
        $messages = new UbillingMessageHelper();
        $errorNotice = __('Equipment models') . ': ' . __('Not exists');
        $addform = $messages->getStyledMessage($errorNotice, 'error');
    }
    return ($addform);
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
    $result .= wf_tag('div', false, '', 'id="ubmap" class="glamour" style="width: 97%; height:300px;"') . wf_tag('div', true);
    $result .= wf_delimiter();
    $placemarks = sm_MapDrawSwitches();
    $placemarks .= sm_MapDrawSwitchUplinks($switchdata['id']);
    $radius = 30;
    $area = sm_MapAddCircle($switchdata['geo'], $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), __('Search area'));
    $result .= generic_MapInit($switchdata['geo'], $ymconf['FINDING_ZOOM'], $ymconf['TYPE'], $area . $placemarks, '', $ymconf['LANG']);
    $result .= wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
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
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $switchesExtended = $ubillingConfig->getAlterParam('SWITCHES_EXTENDED');

    if ($switchesExtended) {
        $switchesUplinks = new SwitchUplinks();
        $switchesUplinks->loadAllUplinksData();
    }

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

    //load dead switches cache
    $dead_switches_raw = zb_StorageGet('SWDEAD');
    if (!$dead_switches_raw) {
        $dead_switches = array();
    } else {
        $dead_switches = unserialize($dead_switches_raw);
    }
    $deathTime = zb_SwitchesGetAllDeathTime();

    if (!empty($downlinks)) {
        $allModels = zb_SwitchModelsGetAllTag();
        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('IP'));
        if ($switchesExtended) {
            $cells .= wf_TableCell(__('Uplink'));
            //separate uplink port 
            if ($switchesExtended == 3) {
                $cells .= wf_TableCell(__('Port'));
            }
        }
        $cells .= wf_TableCell(__('Location'));
        $cells .= wf_TableCell(__('Active'));
        $cells .= wf_TableCell(__('Model'));
        $cells .= wf_TableCell(__('SNMP community'));
        $cells .= wf_TableCell(__('Geo location'));
        $cells .= wf_TableCell(__('Description'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($downlinks as $io => $each) {

            if (isset($dead_switches[$each['ip']])) {
                if (isset($deathTime[$each['ip']])) {
                    $obituary = __('Switch dead since') . ' ' . $deathTime[$each['ip']];
                } else {
                    $obituary = '';
                }
                $aliveled = web_red_led($obituary) . ' ' . __('No');
                $aliveflag = '0';
            } else {
                if (strpos($each['desc'], 'NP') === false) {
                    $aliveled = web_green_led() . ' ' . __('Yes');
                    $aliveflag = '1';
                } else {
                    $aliveled = web_yellow_led() . ' ' . __('NP');
                    $aliveflag = '2';
                }
            }

            $cells = wf_TableCell($each['id']);
            $cells .= wf_TableCell($each['ip']);
            if ($switchesExtended) {
                $includePortFlag = ($switchesExtended == 2) ? true : false;
                $cells .= wf_TableCell($switchesUplinks->getUplinkTinyDesc($each['id'], $includePortFlag));
                //separate uplink port
                if ($switchesExtended == 3) {
                    $cells .= wf_TableCell($switchesUplinks->getUplinkPort($each['id']));
                }
            }
            $cells .= wf_TableCell($each['location']);
            $cells .= wf_TableCell($aliveled);
            $cells .= wf_TableCell(@$allModels[$each['modelid']]);
            $cells .= wf_TableCell($each['snmp']);
            $cells .= wf_TableCell($each['geo']);
            $cells .= wf_TableCell($each['desc']);
            $actLinks = wf_Link('?module=switches&edit=' . $each['id'], web_edit_icon(), false);
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, 'row3');
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        show_window(__('Downlinks'), $result);
    }
}

/**
 * Returns switch edit form for some existing device ID aka "switch profile"
 * 
 * @param int $switchid
 * @return string
 */
function web_SwitchEditForm($switchid) {
    global $ubillingConfig;
    $swGroupsEnabled = $ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED');
    $switchid = vf($switchid, 3);
    $altCfg = $ubillingConfig->getAlter();
    $result = '';
    $mainForm = '';
    $rightContainer = '';
    $allswitchmodels = zb_SwitchModelsGetAllTag();
    $switchdata = zb_SwitchGetData($switchid);

    $editinputs = wf_Selector('editmodel', $allswitchmodels, 'Model', $switchdata['modelid'], true);
    $editinputs .= wf_TextInput('editip', 'IP', $switchdata['ip'], true, 20, 'ip');
    $editinputs .= wf_TextInput('editlocation', 'Location', $switchdata['location'], true, 30);
    $editinputs .= wf_TextInput('editdesc', 'Description', $switchdata['desc'], true, 30);
    if (cfr('SWITCHESEDIT')) {
        $editinputs .= wf_TextInput('editsnmp', 'SNMP community', $switchdata['snmp'], true, 20);
        $editinputs .= wf_TextInput('editsnmpwrite', 'SNMP write community', $switchdata['snmpwrite'], true, 20);
    }
    if ($altCfg['SWITCHES_EXTENDED']) {
        $macVenControl = '';
        if ((!empty($switchdata['swid'])) and ($altCfg['MACVEN_ENABLED'])) {
            if (cfr('MACVEN')) {
                $macVenControl = wf_AjaxLink('?module=macvendor&mac=' . $switchdata['swid'] . '&raw=true', wf_img('skins/macven.gif', __('Device vendor')), 'swvendorcontainer', false, '');
                $swvendorStyle = 'style="text-align: left; font-size:150%;  font-weight: bold;"';
                $rightContainer .= wf_tag('div', false, '', 'id="swvendorcontainer"' . $swvendorStyle) . '' . wf_tag('div', true);
            }
        }
        $editinputs .= wf_TextInput('editswid', __('Switch ID') . ' (MAC) ' . $macVenControl, $switchdata['swid'], true, 20, 'mac');
    }
    $editinputs .= wf_TextInput('editgeo', 'Geo location', $switchdata['geo'], true, 20, 'geo');
    if (!empty($switchdata['parentid'])) {
        $uplinkSwitchLabel = wf_Link('?module=switches&edit=' . $switchdata['parentid'], wf_img_sized('skins/icon_ok.gif', '', '10', '10') . ' ' . __('Uplink switch'), false, '');
    } else {
        $uplinkSwitchLabel = wf_img_sized('skins/icon_minus.png', '', '10', '10') . ' ' . __('Uplink switch');
    }
    $editinputs .= web_SwitchUplinkSelector('editparentid', $uplinkSwitchLabel, $switchdata['parentid'], $switchid);

    //switch uplink detailed data here
    if ($ubillingConfig->getAlterParam('SWITCHES_EXTENDED')) {
        $swUplink = new SwitchUplinks($switchid);
        //saving changes if required
        if (ubRouting::checkPost($swUplink::ROUTE_SWID)) {
            $swUplink->save();
            ubRouting::nav($swUplink::URL_SWPROFILE . ubRouting::post($swUplink::ROUTE_SWID));
        }
        $editinputs .= wf_delimiter(0) . $swUplink->renderSwitchUplinkData();
        if (cfr('SWITCHESEDIT')) {
            if (!ubRouting::checkGet($swUplink::ROUTE_EDITINTERFACE)) {
                $editinputs .= ' ' . wf_Link($swUplink::URL_SWPROFILE . $switchid . '&' . $swUplink::ROUTE_EDITINTERFACE . '=true', '⬇️');
            } else {
                $editinputs .= ' ' . wf_Link($swUplink::URL_SWPROFILE . $switchid, '⬆️');
                $editinputs .= wf_delimiter(0) . $swUplink->renderEditForm();
            }
        }
    }

    //switch auth data directory enabled?
    if ($ubillingConfig->getAlterParam('SWITCHES_AUTH_ENABLED')) {
        $swAuth = new SwitchAuth($switchid);
        $swAuthData = $swAuth->getAuthData($switchid);
        if (empty($swAuthData)) {
            $authLabel = '🔒 ' . __('Device authorization data not set');
        } else {
            $authLabel = '🔑 ' . __('Device authorization data available');
        }
        $editinputs .= wf_delimiter(0);
        if (cfr('SWITCHESEDIT')) {
            $editinputs .= wf_Link($swAuth::URL_ME . '&' . $swAuth::ROUTE_DEVID . '=' . $switchid, $authLabel);
        } else {
            $editinputs .= $authLabel;
        }
    }

    $editinputs .= wf_tag('br');

    if (cfr('SWITCHGROUPS') and $swGroupsEnabled) {
        $switchGroups = new SwitchGroups();
        $editinputs .= $switchGroups->renderSwitchGroupsSelector('editswgroup', $switchid) . wf_delimiter();
    }

    if (cfr('SWITCHESEDIT')) {
        $editinputs .= wf_delimiter(0);
        $editinputs .= wf_Submit('Save');
    }
    $mainForm .= wf_Form('', 'POST', $editinputs, 'glamour');

    //main interface grid
    if (!empty($switchdata['ip'])) {
        $rightContainer .= wf_AjaxLoader();
        $rightContainer .= wf_AjaxContainer('icmppingcontainer');
    }


    $boxStyle = 'style="flex: 1; width: 50%; box-sizing: border-box; padding: 10px;"';
    $result .= wf_tag('div', false, '', 'style="display: flex; flex-wrap: wrap;"');

    $result .= wf_tag('div', false, '', $boxStyle);
    $result .= $mainForm;
    $result .= wf_tag('div', true);

    $result .= wf_tag('div', false, '', $boxStyle);
    $result .= $rightContainer;
    $result .= wf_tag('div', true);

    $result .= wf_tag('div', true);

    $result .= wf_CleanDiv();

    $result .= wf_delimiter();

    $result .= wf_BackLink('?module=switches');
    if (cfr('SWITCHPOLL')) {
        $fdbCacheName = 'exports/' . $switchdata['ip'] . '_fdb';
        if (file_exists($fdbCacheName)) {
            $fdbControls = wf_Link('?module=switchpoller&fdbfor=' . $switchdata['ip'], wf_img('skins/menuicons/switchpoller.png') . ' ' . __('FDB cache'), false, 'ubButton');
            $fdbControls .= wf_Link('?module=fdbarchive&switchidfilter=' . $switchid, wf_img('skins/fdbarchive.png') . ' ' . __('FDB') . ' ' . __('Archive'), false, 'ubButton');
            $result .= wf_modalAuto(wf_img('skins/menuicons/switchpoller.png') . ' ' . __('FDB'), __('FDB'), $fdbControls, 'ubButton');
        }

        if ((!empty($switchdata['snmp'])) and (ispos($switchdata['desc'], 'SWPOLL'))) {
            $result .= wf_Link('?module=switchpoller&switchid=' . $switchid, wf_img('skins/snmp.png') . ' ' . __('SNMP data'), false, 'ubButton');
        }
    }

    if (!empty($switchdata['ip'])) {
        $result .= wf_AjaxLink('?module=switches&backgroundicmpping=' . $switchdata['ip'], wf_img('skins/ping_icon.png') . ' ' . __('ICMP ping'), 'icmppingcontainer', false, 'ubButton');
    }

    if (isset($altCfg['SW_WEBNAV'])) {
        if ($altCfg['SW_WEBNAV']) {
            $result .= ' ' . wf_tag('a', false, 'ubButton', 'href="http://' . $switchdata['ip'] . '" target="_BLANK"') . wf_img('skins/ymaps/globe.png') . ' ' . __('Go to the web interface') . wf_tag('a', true) . ' ';
        }
    }

    if (cfr('SWITCHESEDIT')) {
        if (!ispos($switchdata['desc'], 'NP')) {
            $result .= wf_JSAlertStyled('?module=switchreplace&switchid=' . $switchid, wf_img('skins/duplicate_icon.gif') . ' ' . __('Replacement'), __('Are you serious'), 'ubButton') . ' ';
        }
    }

    if (cfr('SWITCHESEDIT')) {
        if (empty($switchdata['geo'])) {
            $result .= wf_Link('?module=switchmap&locfinder=true&placesw=' . $switchid, wf_img('skins/ymaps/target.png') . ' ' . __('Place on map'), false, 'ubButton');
        }
    }

    if (cfr('SWITCHESEDIT')) {
        $result .= wf_AjaxLink('?module=switchhistory&ajax=true&switchid=' . $switchid, wf_img('skins/log_icon_small.png') . ' ' . __('History'), 'icmppingcontainer', false, 'ubButton') . ' ';
    }

    if (cfr('SWCASH')) {
        if (ispos($switchdata['desc'], 'SWCASH')) {
            if (@$altCfg['SW_CASH_ENABLED']) {
                $result .= wf_Link('?module=swcash&switchid=' . $switchid, wf_img('skins/ukv/dollar.png') . ' ' . __('Financial data'), false, 'ubButton');
            }
        }
    }

    if (cfr('TASKMAN')) {
        if (!empty($switchdata['location'])) {
            if (!ts_isMeBranchCursed()) {
                $taskCreateForm = ts_TaskCreateFormUnified($switchdata['location'], '', '', '', '', '');
                $taskCreateModal = wf_modalAuto(wf_img('skins/createtask_16.png', __('Create task')) . ' ' . __('Task'), __('Create task'), $taskCreateForm, 'ubButton');
                $result .= $taskCreateModal;
            }
        }
    }

    if (cfr('REPORTSWPORT')) {
        if (@$altCfg['SWITCHPORT_IN_PROFILE']) {
            $result .= wf_Link('?module=report_switchportassign&switchid=' . $switchid, wf_img('skins/icon_user_16.gif') . ' ' . __('Switch port assign'), false, 'ubButton');
        }
    }

    if (cfr('SWITCHESEDIT')) {
        $deletionUrl = '?module=switches&switchdelete=' . $switchid;
        $cancelUrl = '?module=switches&edit=' . $switchid;
        $deletionAlert = __('Removing this may lead to irreparable results');
        $delDialogTitle = __('Delete') . ' ' . __('Switch') . ': ' . $switchdata['location'] . '?';
        $result .= wf_ConfirmDialog($deletionUrl, web_delete_icon() . ' ' . __('Delete'), $deletionAlert, 'ubButton', $cancelUrl, $delDialogTitle);
    }

    //SWPOLL proposal
    if (!empty($switchdata['ip'])) {
        if (!ispos($switchdata['desc'], 'SWPOLL') and (!ispos($switchdata['desc'], 'NP')) and (!ispos($switchdata['desc'], 'OLT'))) {
            //this is not OLT
            if (!ispos($switchdata['desc'], 'AP') and (!ispos($switchdata['desc'], 'MTSIGMON'))) {
                //Or some wireless access point
                if (!empty($switchdata['snmp'])) {
                    //with some non empty snmp read comunity
                    if (!empty($switchdata['modelid'])) {
                        $allModelsSnmpTemplates = sp_SnmpGetModelTemplatesAssoc();
                        if (isset($allModelsSnmpTemplates[$switchdata['modelid']])) {
                            //device model have some SNMP template assigned
                            $messages = new UbillingMessageHelper();
                            $result .= $messages->getStyledMessage(__('It looks like this device can be polled using SNMP if you specify SWPOLL in the notes'), 'info');
                        }
                    }
                }
            }
        }
    }

    return ($result);
}

/**
 * Returns array of all available switches with its full data
 * 
 * @param string $order
 * 
 * @return array
 */
function zb_SwitchesGetAll($order = '') {
    if (empty($order)) {
        $order = 'ORDER BY `id` DESC';
    }
    $query = 'SELECT * FROM `switches` ' . $order . ';';
    $allswitches = simple_queryall($query);
    return ($allswitches);
}

/**
 * Returns array of all available switches with its full data with some %mask% in description as switchId=>switchData
 * 
 * @param string $mask
 * 
 * @return array
 */
function zb_SwitchesGetAllMask($mask = '') {
    $result = array();
    if (!empty($mask)) {
        $where = "WHERE `desc` LIKE '%" . $mask . "%'";
    } else {
        $where = '';
    }
    $query = 'SELECT * FROM `switches` ' . $where . ';';
    $allSwitches = simple_queryall($query);
    if (!empty($allSwitches)) {
        foreach ($allSwitches as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
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
 * Return geo data in id->geo format
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
 * 
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
        return (false);
    }
}

/**
 * Returns result of fast ICMP ping with ability to set timeout in seconds(floating values allowed)
 *
 * @param string $ip
 * @param int $timeout
 *
 * @return bool
 */
function zb_PingICMPTimeout($ip, $timeout = 0) {
    $globconf = parse_ini_file(CONFIG_PATH . "billing.ini");
    $ping = $globconf['PING'];
    $sudo = $globconf['SUDO'];
    $pingt_imeout = '';

    if (!empty($timeout)) {
        $curOS = php_uname('s');
        $pingt_imeout = (($curOS == 'FreeBSD') ? '-t ' : '-w ') . $timeout . ' ';
    }

    $ping_command = $sudo . ' ' . $ping . ' -i 0.01 -c 1 ' . $pingt_imeout . $ip;
    $ping_result = shell_exec($ping_command);
    if (strpos($ping_result, 'ttl')) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns result of slow icmp ping with some retries count
 * 
 * @param string $ip devide IP to ping
 * @param int $retries number of retries to check host
 * 
 * @return bool
 */
function zb_PingICMPHope($ip, $retries = 3) {
    $result = false;
    $count = 0;
    for ($count = 0; $count < $retries; $count++) {
        deb($count);
        if (zb_PingICMP($ip)) {
            deb('true');
            $result = true;
            break;
        }
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
    $switchRepingProcess = new StarDust('SWPING');
    $altCfg = $ubillingConfig->getAlter();
    $deadswitches = array();
    $fastPingFlag = $ubillingConfig->getAlterParam('FASTPING_ENABLED');
    if ($fastPingFlag) {
        $fastPing = new FastPing();
    }

    if ($switchRepingProcess->notRunning()) {
        $switchRepingProcess->start();
        $deathTime = zb_SwitchesGetAllDeathTime();
        $allswitches = zb_SwitchesGetAllLocationOrder();
        if (!empty($allswitches)) {
            foreach ($allswitches as $io => $eachswitch) {
                if (!empty($eachswitch['ip']) and !ispos($eachswitch['desc'], 'NP')) {
                    if (!$fastPingFlag) {
                        //regular per-device ICMP polling
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
                    } else {
                        //fast ping query
                        if ($fastPing->isDead($eachswitch['ip'])) {
                            zb_SwitchDeathTimeSet($eachswitch['ip']);
                            $deadswitches[$eachswitch['ip']] = $eachswitch['location'];
                            if (!isset($deathTime[$eachswitch['ip']])) {
                                zb_SwitchDeathTimeSet($eachswitch['ip']);
                            }
                        } else {
                            zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                        }
                    }
                }
            }
        }

        $newdata = serialize($deadswitches);
        zb_StorageSet('SWDEAD', $newdata);
        $switchRepingProcess->stop();
    }
    return ($deadswitches);
}

/**
 * Performs switches alive state check
 * 
 * @return void
 */
function zb_SwitchesForcePing() {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $allswitches = zb_SwitchesGetAll();
    $modelnames = zb_SwitchModelsGetAllTag();
    $currenttime = time();
    $reping_timeout = $alterconf['SW_PINGTIMEOUT'];
    $deathTime = zb_SwitchesGetAllDeathTime();
    $fastPingFlag = $ubillingConfig->getAlterParam('FASTPING_ENABLED');

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
        if ($fastPingFlag) {
            $fastPing = new FastPing();
            $fastPing->repingDevices();
        }
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
                    $ajaxResult .= wf_tag('div', false, '', 'id="switchping"');

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
                        $ajaxResult .= $devicefind . ' ' . $switchLocator . ' ' . $deathClock . $ip . ' - ' . $switch . '<br>';
                    }
                } else {
                    $ajaxResult = __('Switches are okay, everything is fine - I guarantee');
                }
            }
            $ajaxResult .= wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s");
            print($ajaxResult);
            //darkvoid update
            $notifyArea = new DarkVoid();
            $notifyArea->flushCache();

            die();
        } else {
            rcms_redirect('?module=switches');
        }
    }
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
                    $ajaxResult .= wf_tag('div', false, '', 'id="switchping"');

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
                        $ajaxResult .= $devicefind . ' ' . $switchLocator . ' ' . $deathClock . $ip . ' - ' . $switch . '<br>';
                    }
                } else {
                    $ajaxResult = __('Switches are okay, everything is fine - I guarantee');
                }
            }
            $ajaxResult .= wf_delimiter() . __('Cache state at time') . ': ' . date("H:i:s");
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
    $tablecells .= wf_TableCell(__('IP'));
    $tablecells .= wf_TableCell(__('Location'));
    $tablecells .= wf_TableCell(__('Active'));
    $tablecells .= wf_TableCell(__('Model'));
    $tablecells .= wf_TableCell(__('SNMP community'));
    $tablecells .= wf_TableCell(__('Geo location'));
    $tablecells .= wf_TableCell(__('Description'));
    $tablecells .= wf_TableCell(__('Actions'));
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
            $tablecells .= wf_TableCell($eachswitch['ip'], '', '', 'sorttable_customkey="' . ip2int($eachswitch['ip']) . '"');
            $tablecells .= wf_TableCell($eachswitch['location']);
            $tablecells .= wf_TableCell($aliveled, '', '', 'sorttable_customkey="' . $aliveflag . '"');
            $tablecells .= wf_TableCell(@$modelnames[$eachswitch['modelid']]);
            $tablecells .= wf_TableCell($eachswitch['snmp']);
            $tablecells .= wf_TableCell($eachswitch['geo']);
            $tablecells .= wf_TableCell($eachswitch['desc']);
            $switchcontrols = '';
            if (cfr('SWITCHESEDIT')) {
                $switchcontrols .= wf_Link('?module=switches&edit=' . $eachswitch['id'], web_edit_icon());
            }

            if (cfr('SWITCHPOLL')) {
                if ((!empty($eachswitch['snmp'])) and (ispos($eachswitch['desc'], 'SWPOLL'))) {
                    $switchcontrols .= '&nbsp;' . wf_Link('?module=switchpoller&switchid=' . $eachswitch['id'], wf_img('skins/snmp.png', __('SNMP query')));
                    $countSwpoll++;
                }
            }

            if ($alterconf['SWYMAP_ENABLED']) {
                if (!empty($eachswitch['geo'])) {
                    $switchcontrols .= wf_Link('?module=switchmap&finddevice=' . $eachswitch['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')));
                    $countOnMap++;
                }

                if (!empty($eachswitch['parentid'])) {
                    $switchcontrols .= wf_Link('?module=switchmap&finddevice=' . $eachswitch['geo'] . '&showuplinks=true&traceid=' . $eachswitch['id'], wf_img('skins/ymaps/uplinks.png', __('Uplink switch')));
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
                $switchcontrols .= $adcomments->getCommentsIndicator($eachswitch['id']);
            }

            if (isset($alterconf['SW_WEBNAV'])) {
                if ($alterconf['SW_WEBNAV']) {
                    $switchcontrols .= ' ' . wf_tag('a', false, '', 'href="http://' . $eachswitch['ip'] . '" target="_BLANK"') . wf_img('skins/ymaps/globe.png', __('Go to the web interface')) . wf_tag('a', true);
                }
            }

            $tablecells .= wf_TableCell($switchcontrols);
            $tablerows .= wf_tag('tr', false, 'row3', $lighter);
            $tablerows .= $tablecells;
            $tablerows .= wf_tag('tr', true);
            $countTotal++;
        }
    }
    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');

    $result .= wf_img('skins/icon_active.gif') . ' ' . __('Alive switches') . ' - ' . ($countAlive + $countNp) . ' (' . $countAlive . '+' . $countNp . ')' . wf_tag('br');
    $result .= wf_img('skins/icon_inactive.gif') . ' ' . __('Dead switches') . ' - ' . $countDead . wf_tag('br');
    $result .= wf_img('skins/yellow_led.png') . ' ' . __('NP switches') . ' - ' . $countNp . wf_tag('br');
    $result .= wf_img('skins/snmp.png') . ' ' . __('SWPOLL query') . ' - ' . $countSwpoll . wf_tag('br');
    $result .= wf_img('skins/wifi.png') . ' ' . __('MTSIGMON devices') . ' - ' . $countMtsigmon . wf_tag('br');
    $result .= wf_img('skins/pon_icon.gif') . ' ' . __('OLT devices') . ' - ' . $countOlt . wf_tag('br');

    $result .= wf_img('skins/icon_search_small.gif') . ' ' . __('Placed on map') . ' - ' . $countOnMap . wf_tag('br');
    $result .= wf_img('skins/ymaps/uplinks.png') . ' ' . __('Have uplinks') . ' - ' . $countLinked . wf_tag('br');

    $result .= wf_tag('br') . wf_tag('b') . __('Total') . ': ' . $countTotal . wf_tag('b', true) . wf_tag('br');

    return ($result);
}

/**
 * Returns JQDT switches list container
 * 
 * @return string
 */
function web_SwitchesRenderList() {
    $result = '';
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $swGroupsEnabled = $ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED');
    $switchesExtended = $ubillingConfig->getAlterParam('SWITCHES_EXTENDED');
    $switchesCompactFlag = $ubillingConfig->getAlterParam('SWITCHES_LIST_COMPACT');
    $summaryCache = 'exports/switchcounterssummary.dat';

    $columns = array('ID', 'IP');

    if ($alterconf['SWITCHES_SNMP_MAC_EXORCISM']) {
        $columns[] = ('MAC');
    }

    if ($switchesExtended) {
        $columns[] = __('Uplink');
        //separate port column
        if ($switchesExtended == 3) {
            $columns[] = __('Port');
        }
    }

    array_push($columns, 'Location', 'Active', 'Model');
    if (!$switchesCompactFlag) {
        array_push($columns, 'SNMP community', 'Geo location');
    }
    $columns[] = 'Description';

    if ($swGroupsEnabled) {
        $columns[] = 'Group';
    }

    $columns[] = 'Actions';

    $opts = '"order": [[ 0, "desc" ]]';
    $result = wf_JqDtLoader($columns, '?module=switches&ajaxlist=true', false, __('Switch'), 100, $opts);
    if (file_exists($summaryCache)) {
        $result .= file_get_contents($summaryCache);
    }
    return ($result);
}

/**
 * Renders ajax switches list data
 * 
 * @return string
 */
function zb_SwitchesRenderAjaxList() {
    $result = '';
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();

    $swGroupsEnabled = $ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED');
    $switchesExtended = $ubillingConfig->getAlterParam('SWITCHES_EXTENDED');
    $switchesCompactFlag = $ubillingConfig->getAlterParam('SWITCHES_LIST_COMPACT');

    $allswitchgroups = '';
    if ($swGroupsEnabled) {
        $switchGroups = new SwitchGroups();
        $allswitchgroups = $switchGroups->getSwitchesIdsWithGroupsData();
    }

    if ($switchesExtended) {
        $switchesUplinks = new SwitchUplinks();
        $switchesUplinks->loadAllUplinksData();
    }

    $allswitches = zb_SwitchesGetAll();
    $modelnames = zb_SwitchModelsGetAllTag();
    $deathTime = zb_SwitchesGetAllDeathTime();
    $summaryCache = 'exports/switchcounterssummary.dat';
    $jsonAAData = array();

    //counters
    $countTotal = 0;
    $countAlive = 0;
    $countDead = 0;
    $countNp = 0;
    $countOnMap = 0;
    $countSwpoll = 0;
    $countMtsigmon = 0;
    $countAP = 0;
    $countOlt = 0;
    $countLinked = 0;

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

    if (!empty($allswitches)) {
        foreach ($allswitches as $io => $eachswitch) {
            $jsonItem = array();

            if (isset($dead_switches[$eachswitch['ip']])) {
                if (isset($deathTime[$eachswitch['ip']])) {
                    $obituary = __('Switch dead since') . ' ' . $deathTime[$eachswitch['ip']];
                } else {
                    $obituary = '';
                }
                $aliveled = web_red_led($obituary) . ' ' . __('No');
                $aliveflag = '0';
                $countDead++;
            } else {
                if (strpos($eachswitch['desc'], 'NP') === false) {
                    $aliveled = web_green_led() . ' ' . __('Yes');
                    $aliveflag = '1';
                    $countAlive++;
                } else {
                    $aliveled = web_yellow_led() . ' ' . __('NP');
                    $aliveflag = '2';
                    $countNp++;
                }
            }

            $jsonItem[] = $eachswitch['id'];
            $jsonItem[] = $eachswitch['ip'];

            if ($alterconf['SWITCHES_SNMP_MAC_EXORCISM']) {
                $deviceMac = '';
                $deviceMacCache = 'exports/' . $eachswitch['ip'] . '_MAC';

                if (file_exists($deviceMacCache)) {
                    $deviceMacData = file_get_contents($deviceMacCache);
                    if (check_mac_format($deviceMacData)) {
                        if ($alterconf['SWITCHES_EXTENDED'] and $deviceMacData != $eachswitch['swid']) {
                            $deviceMac = $deviceMacData . ' ' . wf_img('skins/createtask.gif', __('MAC mismatch')) . ' ' . __('Oh no');
                        } else {
                            $deviceMac = $deviceMacData;
                        }
                    }
                }

                $jsonItem[] = $deviceMac;
            }

            if ($switchesExtended) {
                $includePortFlag = ($switchesExtended == 2) ? true : false;
                $jsonItem[] = $switchesUplinks->getUplinkTinyDesc($eachswitch['id'], $includePortFlag);
                //port as separate column?
                if ($switchesExtended == 3) {
                    $jsonItem[] = $switchesUplinks->getUplinkPort($eachswitch['id']);
                }
            }

            $jsonItem[] = $eachswitch['location'];
            $jsonItem[] = $aliveled;
            $jsonItem[] = @$modelnames[$eachswitch['modelid']];
            if (!$switchesCompactFlag) {
                $jsonItem[] = $eachswitch['snmp'];
                $jsonItem[] = $eachswitch['geo'];
            }
            $jsonItem[] = $eachswitch['desc'];

            if ($swGroupsEnabled) {
                $jsonItem[] = (isset($allswitchgroups[$eachswitch['id']])) ? $allswitchgroups[$eachswitch['id']]['groupname'] : '';
            }

            $switchcontrols = '';
            if (cfr('SWITCHES')) {
                $switchcontrols .= wf_Link('?module=switches&edit=' . $eachswitch['id'], web_edit_icon());
            }

            if (cfr('SWITCHPOLL')) {
                if ((!empty($eachswitch['snmp'])) and (ispos($eachswitch['desc'], 'SWPOLL'))) {
                    $switchcontrols .= '&nbsp;' . wf_Link('?module=switchpoller&switchid=' . $eachswitch['id'], wf_img('skins/snmp.png', __('SNMP query')));
                    $countSwpoll++;
                }
            }

            if ($alterconf['SWYMAP_ENABLED']) {
                if (!empty($eachswitch['geo'])) {
                    if (cfr('SWITCHMAP')) {
                        $switchcontrols .= wf_Link('?module=switchmap&finddevice=' . $eachswitch['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')));
                    }
                    $countOnMap++;
                }

                if (!empty($eachswitch['parentid'])) {
                    if (cfr('SWITCHMAP')) {
                        $switchcontrols .= wf_Link('?module=switchmap&finddevice=' . $eachswitch['geo'] . '&showuplinks=true&traceid=' . $eachswitch['id'], wf_img('skins/ymaps/uplinks.png', __('Uplink switch')));
                    }
                    $countLinked++;
                }

                if ((empty($eachswitch['geo'])) and (!ispos($eachswitch['desc'], 'NP'))) {
                    if ((cfr('SWITCHESEDIT')) and (cfr('SWITCHMAP'))) {
                        $switchcontrols .= wf_Link('?module=switchmap&locfinder=true&placesw=' . $eachswitch['id'], wf_img('skins/ymaps/target.png', __('Place on map')), false, '');
                    }
                }
            }

            if (ispos($eachswitch['desc'], 'MTSIGMON')) {
                $countMtsigmon++;
            }

            if (ispos($eachswitch['desc'], 'OLT')) {
                $countOlt++;
            }

            if (ispos($eachswitch['desc'], 'AP')) {
                $countAP++;
            }

            if ($alterconf['ADCOMMENTS_ENABLED']) {
                $switchcontrols .= $adcomments->getCommentsIndicator($eachswitch['id']);
            }

            if (isset($alterconf['SW_WEBNAV'])) {
                if ($alterconf['SW_WEBNAV']) {
                    $switchcontrols .= ' ' . wf_tag('a', false, '', 'href="http://' . $eachswitch['ip'] . '" target="_BLANK"') . wf_img('skins/ymaps/globe.png', __('Go to the web interface')) . wf_tag('a', true);
                }
            }

            if (@$alterconf['SW_CASH_ENABLED']) {
                if (ispos($eachswitch['desc'], 'SWCASH')) {
                    $swCashUrl = SwitchCash::URL_ME . '&' . SwitchCash::ROUTE_EDIT . '=' . $eachswitch['id'];
                    $switchcontrols .= wf_Link($swCashUrl, wf_img('skins/ukv/dollar.png', __('Financial data')));
                }
            }

            $jsonItem[] = $switchcontrols;
            $countTotal++;
            $jsonAAData[] = $jsonItem;
        }
    }

    $countersSummary = wf_tag('br');
    $countersSummary .= wf_img('skins/icon_active.gif') . ' ' . __('Alive switches') . ' - ' . ($countAlive + $countNp) . ' (' . $countAlive . '+' . $countNp . ')' . wf_tag('br');
    $countersSummary .= wf_img('skins/icon_inactive.gif') . ' ' . __('Dead switches') . ' - ' . $countDead . wf_tag('br');
    $countersSummary .= wf_img('skins/yellow_led.png') . ' ' . __('NP switches') . ' - ' . $countNp . wf_tag('br');
    $countersSummary .= wf_img('skins/snmp.png') . ' ' . __('SWPOLL query') . ' - ' . $countSwpoll . wf_tag('br');
    $countersSummary .= wf_img('skins/wifi.png') . ' ' . __('MTSIGMON devices') . ' - ' . $countMtsigmon . wf_tag('br');
    $countersSummary .= wf_img('skins/pon_icon.gif') . ' ' . __('OLT devices') . ' - ' . $countOlt . wf_tag('br');
    $countersSummary .= wf_img('skins/wifi.png') . ' ' . __('AP devices') . ' - ' . $countAP . wf_tag('br');
    $countersSummary .= wf_img('skins/icon_search_small.gif') . ' ' . __('Placed on map') . ' - ' . $countOnMap . wf_tag('br');
    $countersSummary .= wf_img('skins/ymaps/uplinks.png') . ' ' . __('Have uplinks') . ' - ' . $countLinked . wf_tag('br');
    $countersSummary .= wf_tag('br') . wf_tag('b') . __('Total') . ': ' . $countTotal . wf_tag('b', true) . wf_tag('br');
    file_put_contents($summaryCache, $countersSummary);

    $jsonList = array("aaData" => $jsonAAData);

    return (json_encode($jsonList));
}

/**
 * Updates existing switch data
 * 
 * @global object $ubillingConfig
 * 
 * @return void
 */
function ub_SwitchSave($switchid) {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $switchid = ubRouting::filters($switchid, 'int');
    // some non-parameterized shit here, PFFFFF
    simple_update_field('switches', 'modelid', ubRouting::post('editmodel'), "WHERE `id`='" . $switchid . "'");
    simple_update_field('switches', 'ip', ubRouting::post('editip'), "WHERE `id`='" . $switchid . "'");
    simple_update_field('switches', 'location', ub_SanitizeData(ubRouting::post('editlocation'), false), "WHERE `id`='" . $switchid . "'");
    simple_update_field('switches', 'desc', ub_SanitizeData(ubRouting::post('editdesc'), false), "WHERE `id`='" . $switchid . "'");
    simple_update_field('switches', 'snmp', ubRouting::post('editsnmp'), "WHERE `id`='" . $switchid . "'");
    simple_update_field('switches', 'snmpwrite', ubRouting::post('editsnmpwrite'), "WHERE `id`='" . $switchid . "'");

    if ($altCfg['SWITCHES_EXTENDED']) {
        simple_update_field('switches', 'swid', ubRouting::post('editswid'), "WHERE `id`='" . $switchid . "'");
    }

    simple_update_field('switches', 'geo', preg_replace('/[^-?0-9\.,]/i', '', ubRouting::post('editgeo')), "WHERE `id`='" . $switchid . "'");

    if (ubRouting::post('editparentid') != $switchid) {
        //checks for preventing loops
        $alllinks = array();
        $tmpSwitches = zb_SwitchesGetAll();
        if (!empty($tmpSwitches)) {
            //transform array to id=>switchdata
            foreach ($tmpSwitches as $io => $each) {
                $allswitches[$each['id']] = $each;
            }

            //making id=>parentid array
            foreach ($tmpSwitches as $io => $each) {
                $alllinks[$each['id']] = $each['parentid'];
            }
        }
        if (sm_CheckLoop($alllinks, $switchid, ubRouting::post('editparentid'))) {
            simple_update_field('switches', 'parentid', ubRouting::post('editparentid'), "WHERE `id`='" . $switchid . "'");
        }
    }

    $swGroupsEnabled = $ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED');
    if ($swGroupsEnabled) {
        $switchGroups = new SwitchGroups();
        $switchAlreadyInGroup = $switchGroups->getSwitchGroupBySwitchId($switchid);

        if (empty($switchAlreadyInGroup) and ubRouting::post('editswgroup')) {
            $query = "INSERT INTO `switch_groups_relations` (`switch_id`, `sw_group_id`) VALUES (" . $switchid . ", " . ubRouting::post('editswgroup') . ")";
            nr_query($query);
        } elseif (ubRouting::post('editswgroup')) {
            if (ubRouting::post('editswgroup') == '0') {
                $switchGroups->removeSwitchFromGroup($switchid);
            } else {
                simple_update_field('switch_groups_relations', 'sw_group_id', ubRouting::post('editswgroup'), "WHERE `switch_id`='" . $switchid . "'");
            }
        }
    }

    log_register('SWITCH CHANGE [' . $switchid . ']' . ' IP ' . ubRouting::post('editip') . " LOC `" . ubRouting::post('editlocation') . "`");
}

/**
 * Creates new switch device in database
 * 
 * @param int    $modelid
 * @param string $ip
 * @param string $desc
 * @param string $location
 * @param string $snmp
 * @param string $swid
 * @param string $geo
 * @param int    $parentid
 */
function ub_SwitchAdd($modelid, $ip, $desc, $location, $snmp, $swid, $geo, $parentid = '', $snmpwrite = '', $switchgroupid = '') {
    $modelid = ubRouting::filters($modelid, 'int');
    $ip = ubRouting::filters($ip, 'mres');
    $desc = ub_SanitizeData($desc);
    $location = ub_SanitizeData($location);
    $snmp = ubRouting::filters($snmp, 'mres');
    $snmpwrite = ubRouting::filters($snmpwrite, 'mres');
    $swid = ubRouting::filters($swid, 'mres');
    $parentid = ubRouting::filters($parentid, 'int');
    if (!empty($parentid)) {
        $parentid = "'" . $parentid . "'";
    } else {
        $parentid = 'NULL';
    }
    $query = "INSERT INTO `switches` (`id` ,`modelid` ,`ip` ,`desc` ,`location` ,`snmp`,`swid`,`geo`,`parentid`,`snmpwrite`) "
        . "VALUES ('', '" . $modelid . "', '" . $ip . "', '" . $desc . "', '" . $location . "', '" . $snmp . "', '" . $swid . "','" . $geo . "', " . $parentid . ",'" . $snmpwrite . "' );";
    nr_query($query);

    $lastid = simple_get_lastid('switches');

    if (!empty($switchgroupid)) {
        $query = "INSERT INTO `switch_groups_relations` (`switch_id`, `sw_group_id`) VALUES (" . $lastid . ", " . $switchgroupid . ")";
        nr_query($query);
    }

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
    global $ubillingConfig;
    $swGroupsEnabled = $ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED');

    $switchid = vf($switchid);
    $switchdata = zb_SwitchGetData($switchid);
    $query = "DELETE from `switches` WHERE `id`='" . $switchid . "'";
    nr_query($query);

    if ($swGroupsEnabled) {
        $switchGroups = new SwitchGroups();
        $switchGroups->removeSwitchFromGroup($switchid);
    }

    $switchesExtended = $ubillingConfig->getAlterParam('SWITCHES_EXTENDED');
    if ($switchesExtended) {
        $switchesUplinks = new SwitchUplinks();
        $switchesUplinks->flush($switchid);
    }

    $swAuth = $ubillingConfig->getAlterParam('SWITCHES_AUTH_ENABLED');
    if ($swAuth) {
        $switchAuth = new SwitchAuth($switchid);
        $switchAuth->flushAuthData($switchid);
    }

    $query = 'DELETE FROM `switches_qinq` WHERE `switchid` = "' . $switchid . '"';
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

            $result .= "
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
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    if (isset($altCfg['SWITCH_PING_INTERVAL'])) {
        $repingInterval = $altCfg['SWITCH_PING_INTERVAL'] * 60;
    } else {
        $repingInterval = 0;
    }
    $topThreshold = 0;
    $result = '';

    $cmonth = curmonth();
    $query = "SELECT `id`,`date`,`timestamp`,`swdead` from `switchdeadlog` WHERE `date` LIKE '" . $cmonth . "-%' ORDER BY `id` ASC";
    $rawData = simple_queryall($query);
    $topTmp = array();
    $totalCount = 0;
    $totaldeadTime = 0;

    if (!empty($rawData)) {
        foreach ($rawData as $io => $each) {
            if (!empty($each['swdead'])) {
                $deadData = unserialize($each['swdead']);
                if (!empty($deadData)) {
                    foreach ($deadData as $eachDeadIp => $eachDeadName) {
                        if (isset($topTmp[$eachDeadIp])) {
                            $topTmp[$eachDeadIp]['count']++;
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
        $cells .= wf_TableCell(__('Location'));
        $cells .= wf_TableCell(__('Count'));
        if ($repingInterval) {
            $cells .= wf_TableCell(__('Time'));
        }
        $cells .= wf_TableCell(__('Visual'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($topTmp as $io => $each) {
            if ($each['count'] >= $topThreshold) {
                $cells = wf_TableCell($io);
                $cells .= wf_TableCell($each['name']);
                $cells .= wf_TableCell($each['count']);
                if ($repingInterval) {
                    $deadTime = $each['count'] * $repingInterval;
                    $cells .= wf_TableCell(zb_formatTime($deadTime));
                    $totaldeadTime += $deadTime;
                }
                $cells .= wf_TableCell(web_bar($each['count'], $totalCount), '', '', 'sorttable_customkey="' . $each['count'] . '"');
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        if ($repingInterval) {
            $cells = wf_TableCell(__('Total'));
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell('');
            $cells .= wf_TableCell(zb_formatTime($totaldeadTime));
            $cells .= wf_TableCell('');
            $rows .= wf_TableRow($cells, 'row2');
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
        $cells .= wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('Location'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($deadarr)) {
            foreach ($deadarr as $ip => $location) {
                $cells = wf_TableCell(@$deathTime[$ip]);
                $cells .= wf_TableCell($ip);
                $cells .= wf_TableCell($location);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        show_window(__('Dead switches') . ' ' . $deaddata['date'], $result);
        show_window('', wf_BackLink("?module=switches&timemachine=true"));
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
    $inputs .= wf_Submit(__('Search'));
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Returns 
 * 
 * @param string $switchIp
 * 
 * @return array
 */
function ub_SwitchesTimeMachineGetByIp($switchIp) {
    $result = array();
    $query = "SELECT * from `switchdeadlog` ORDER BY `id` DESC";
    $raw = simple_queryall($query);
    if (!empty($raw)) {
        foreach ($raw as $io => $each) {

            if (!empty($each)) {
                $logData = unserialize($each['swdead']);
                if (isset($logData[$switchIp])) {
                    $result[$each['date']] = $logData[$switchIp];
                }
            }
        }
    }
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

                    if ((ispos(strtolower_utf8($switchIp), $request)) or (ispos(strtolower_utf8($switchLocation), $request))) {
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
        $cells .= wf_TableCell(__('IP'));
        $cells .= wf_TableCell(__('Location'));
        $rows = wf_TableRow($cells, 'row1');

        foreach ($tmpArr as $ia => $eachResult) {
            $cells = wf_TableCell($eachResult['date']);
            $cells .= wf_TableCell($eachResult['ip']);
            $cells .= wf_TableCell($eachResult['location']);
            $rows .= wf_TableRow($cells, 'row3');
            $deadcount++;
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        $result .= __('Total') . ': ' . $deadcount;
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
    $query = "SELECT * from `switches` WHERE `desc` LIKE '%NP%' ORDER BY `ip` DESC";
    $raw = simple_queryall($query);
    $paramsNp = array();
    $employee = array();
    $employee = ts_GetActiveEmployee();

    if (!empty($raw)) {
        foreach ($raw as $io => $eachNp) {
            $paramsNp[$eachNp['id']] = $eachNp['ip'] . ' - ' . $eachNp['location'];
        }
    }

    $inputs = wf_HiddenInput('switchreplace', $fromSwitchId);
    $inputs .= wf_SelectorSearchable('toswtichreplace', $paramsNp, 'NP ' . __('Switch'), '', false);
    $inputs .= wf_SelectorSearchable('replaceemployeeid', $employee, __('Worker'), '', false);
    $inputs .= wf_Submit('Save');
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    $result .= wf_CleanDiv();
    $result .= wf_delimiter();
    $result .= wf_BackLink('?module=switches&edit=' . $fromSwitchId);
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
    global $ubillingConfig;
    $fromId = ubRouting::filters($fromId, 'int');
    $toId = ubRouting::filters($toId, 'int');
    $employeeId = ubRouting::filters($employeeId, 'int');

    $switchesDb = new NyanORM('switches');
    $allEmployees = ts_GetAllEmployee();
    $fromData = zb_SwitchGetData($fromId);
    $toData = zb_SwitchGetData($toId);

    if (!empty($fromData)) {
        //updating new switch device
        $switchesDb->where('id', '=', $toId);
        //copy geo coordinates to new switch
        $switchesDb->data('geo', $fromData['geo']);
        //setting new description and remove NP flag
        $newDescriptionTo = str_replace('NP', 'm:' . @$allEmployees[$employeeId], $toData['desc']);
        $switchesDb->data('desc', $newDescriptionTo);
        //copy location
        $switchesDb->data('location', $fromData['location']);
        //copy switch parent ID
        if (!empty($fromData['parentid'])) {
            $switchesDb->data('parentid', $fromData['parentid']);
        } else {
            //or dropping if not set before
            $switchesDb->data('parentid', 'NULL');
        }
        //saving new switch
        $switchesDb->save();

        //updating old switch device
        $switchesDb->where('id', '=', $fromId);

        // doing old switch cleanup and disabling it
        $switchesDb->data('geo', ''); //not located anywhere now
        $newFromLocation = __('removed from') . ': ' . $fromData['location'];
        $switchesDb->data('location', $newFromLocation); //unmouned from somwhere
        $newFromDesc = 'NP u:' . @$allEmployees[$employeeId];
        $switchesDb->data('desc', $newFromDesc); // NP + employee name
        $switchesDb->data('parentid', 'NULL'); // unmounted switch have no parents
        //saving old switch device
        $switchesDb->save();

        //moving childs if it present
        $switchesDb->where('parentid', '=', $fromId);
        $switchesDb->data('parentid', $toId);
        $switchesDb->save();

        //update user switchportassigns
        if ($ubillingConfig->getAlterParam('SWITCHPORT_IN_PROFILE')) {
            if ($ubillingConfig->getAlterParam('USER_SWITCHPORT_AUTOREPLACE')) {
                $switchPortAssignDb = new NyanORM('switchportassign');
                $switchPortAssignDb->where('switchid', '=', $fromId);
                $switchPortAssignDb->data('switchid', $toId);
                $switchPortAssignDb->save();
                // update qinq swithc delegation
                if ($ubillingConfig->getAlterParam('QINQ_ENABLED') and $ubillingConfig->getAlterParam('QINQ_SWITCH_AUTOREPLACE')) {
                    $switchesQinqDb = new NyanORM('switches_qinq');
                    $switchesQinqDb->where('switchid', '=', $fromId);
                    $switchesQinqDb->data('switchid', $toId);
                    $switchesQinqDb->save();
                }
            }
        }

        //log this replace
        log_register('SWITCH REPLACE FROM [' . $fromId . '] TO [' . $toId . '] EMPLOYEE [' . $employeeId . ']');
    } else {
        show_error(__('Strange exception') . ': FROM_SWITCH_EMPTY_DATA');
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

/**
 * Returns switch profile link with some square brackets
 * 
 * @param int $switchId
 * 
 * @return string
 */
function web_SwitchProfileLink($switchId) {
    $result = ' [' . trim(wf_Link('?module=switches&edit=' . $switchId, $switchId)) . '] ';
    return ($result);
}

/**
 * Returns all available users switches assigns as login=>switchid,switchip,port,location,label
 * 
 * @return array
 */
function zb_SwitchesGetAssignsAll() {
    $result = array();
    $allSwitches = array();
    $allSwitchesTmp = zb_SwitchesGetAll();
    if (!empty($allSwitchesTmp)) {
        foreach ($allSwitchesTmp as $io => $each) {
            $allSwitches[$each['id']] = $each;
        }

        $switchAssigns_q = "SELECT * from `switchportassign`";
        $switchAssignsTmp = simple_queryall($switchAssigns_q);
        if (!empty($switchAssignsTmp)) {
            foreach ($switchAssignsTmp as $io => $each) {
                if (isset($allSwitches[$each['switchid']])) {
                    $switchData = $allSwitches[$each['switchid']];
                    $result[$each['login']]['switchid'] = $switchData['id'];
                    $result[$each['login']]['switchip'] = $switchData['ip'];
                    $result[$each['login']]['port'] = $each['port'];
                    $result[$each['login']]['location'] = $switchData['location'];
                    $result[$each['login']]['label'] = $switchData['ip'] . ' - ' . $switchData['location'] . ' ' . __('Port') . ' ' . $each['port'];
                }
            }
        }
    }

    return ($result);
}

/**
 * Returns background switch ICMP ping
 * 
 * @global object $ubillingConfig
 * 
 * @return void
 */
function zb_SwitchBackgroundIcmpPing($ip) {
    global $ubillingConfig;
    $billingConf = $ubillingConfig->getBilling();
    $command = $billingConf['SUDO'] . ' ' . $billingConf['PING'] . ' -i 0.01 -c 10  ' . $ip;
    $icmpPingResult = shell_exec($command);

    die(wf_tag('pre') . $icmpPingResult . wf_tag('pre', true));
}

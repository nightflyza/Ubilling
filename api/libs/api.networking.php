<?php

/**
 * Returns IP usage stats for available networks
 * 
 * @return array
 */
function multinet_getFreeIpStats() {
    $result = array();
    $allServices = array();
    $allNets = array();
    $nethostsUsed = array();

    $servicesTmp = multinet_get_services();
    $netsTmp = multinet_get_all_networks();
    $neth_q = "SELECT COUNT(id) as count, netid from `nethosts` group by `netid`";
    $nethTmp = simple_queryall($neth_q);

    if (!empty($nethTmp)) {
        foreach ($nethTmp as $io => $each) {
            $nethostsUsed[$each['netid']] = $each['count'];
        }
    }

    if (!empty($servicesTmp)) {
        foreach ($servicesTmp as $io => $each) {
            $allServices[$each['netid']] = $each['desc'];
        }
    }

    if (!empty($netsTmp)) {
        foreach ($netsTmp as $io => $each) {
            $totalIps = multinet_expand_network($each['startip'], $each['endip']);
            $allNets[$each['id']]['desc'] = $each['desc'];
            $allNets[$each['id']]['total'] = count($totalIps);
            //finding used hosts count
            if (isset($nethostsUsed[$each['id']])) {
                $allNets[$each['id']]['used'] = $nethostsUsed[$each['id']];
            } else {
                $allNets[$each['id']]['used'] = 0;
            }
            //finding network associated service
            if (isset($allServices[$each['id']])) {
                $allNets[$each['id']]['service'] = $allServices[$each['id']];
            } else {
                $allNets[$each['id']]['service'] = '';
            }
        }
    }

    return ($allNets);
}

/**
 * Renders IP usage stats in existing networks. Reacts to allnets GET parameter.
 * 
 * @return string
 */
function web_FreeIpStats() {
    $result = '';
    $data = multinet_getFreeIpStats();

    //checking service filters
    if (wf_CheckGet(array('allnets'))) {
        $servFlag = false;
    } else {
        $servFlag = true;
    }

    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('Network/CIDR'));
    $cells .= wf_TableCell(__('Total') . ' ' . __('IP'));
    $cells .= wf_TableCell(__('Used') . ' ' . __('IP'));
    $cells .= wf_TableCell(__('Free') . ' ' . __('IP'));
    $cells .= wf_TableCell(__('Service'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($data)) {
        foreach ($data as $io => $each) {
            if ($servFlag) {
                if (!empty($each['service'])) {
                    $appendResult = true;
                } else {
                    $appendResult = false;
                }
            } else {
                $appendResult = true;
            }

            if ($appendResult) {
                $free = $each['total'] - $each['used'];
                $fontColor = ($free <= 5) ? '#a90000' : '';
                $cells = wf_TableCell($io);
                $cells .= wf_TableCell($each['desc']);
                $cells .= wf_TableCell($each['total']);
                $cells .= wf_TableCell($each['used']);
                $cells .= wf_TableCell(wf_tag('font', false, '', 'color="' . $fontColor . '"') . $free . wf_tag('font', false));
                $cells .= wf_TableCell($each['service']);
                $rows .= wf_TableRow($cells, 'row5');
            }
        }
    }


    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
    return ($result);
}

/**
 * Renders list of available networks
 * 
 * @global object $ubillingConfig
 * 
 * @return void
 */
function multinet_show_available_networks() {
    global $ubillingConfig;
    $query = "SELECT * from `networks`";
    $networks = simple_queryall($query);
    $cells = wf_TableCell(__('ID'));
    $cells .= wf_TableCell(__('First IP'));
    $cells .= wf_TableCell(__('Last IP'));
    $cells .= wf_TableCell(__('Network/CIDR'));
    $cells .= wf_TableCell(__('Network type'));
    if ($ubillingConfig->getAlterParam('FREERADIUS_ENABLED')) {
        $cells .= wf_TableCell(__('Use Radius'));
    }
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');
    if (!empty($networks)) {
        foreach ($networks as $network) {
            $cells = wf_TableCell($network['id']);
            $cells .= wf_TableCell($network['startip']);
            $cells .= wf_TableCell($network['endip']);
            $cells .= wf_TableCell($network['desc']);
            $cells .= wf_TableCell($network['nettype']);
            if ($ubillingConfig->getAlterParam('FREERADIUS_ENABLED')) {
                $cells .= wf_TableCell(web_bool_led($network['use_radius']));
            }
            $actions = wf_JSAlert('?module=multinet&deletenet=' . $network['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actions .= wf_JSAlert('?module=multinet&editnet=' . $network['id'], web_edit_icon(), 'Are you serious');
            if ($ubillingConfig->getAlterParam('FREERADIUS_ENABLED') && $network['use_radius']) {
                $actions .= wf_Link('?module=freeradius&netid=' . $network['id'], web_icon_freeradius('Set RADIUS-attributes'));
            }
            $cells .= wf_TableCell($actions);
            $rows .= wf_TableRow($cells, 'row5');
        }
    }
    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    $statsControls = wf_Link('?module=multinet&freeipstats=true', wf_img_sized('skins/icon_stats.gif', __('IP usage stats'), 16));
    show_window(__('Networks') . ' ' . $statsControls, $result);
}

/**
 * Renders network editing form
 * 
 * @global object $ubillingConfig
 * 
 * @param int $netid
 * 
 * @return void
 */
function multinet_show_neteditform($netid) {
    global $ubillingConfig;
    $netid = vf($netid, 3);
    $netdata = multinet_get_network_params($netid);

    $useRadArr = array('0' => __('No'), '1' => __('Yes'));

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('netedit', 'true');
    $inputs .= wf_TextInput('editstartip', __('First IP') . $sup, $netdata['startip'], true, '20', 'ip');
    $inputs .= wf_TextInput('editendip', __('Last IP') . $sup, $netdata['endip'], true, '20', 'ip');
    $inputs .= multinet_nettype_selector($netdata['nettype']) . ' ' . __('Network type') . wf_tag('br');
    $inputs .= wf_TextInput('editdesc', __('Network/CIDR') . $sup, $netdata['desc'], true, '20', 'net-cidr');
    if ($ubillingConfig->getAlterParam('FREERADIUS_ENABLED')) {
        $inputs .= wf_Selector('edituse_radius', $useRadArr, __('Use Radius'), $netdata['use_radius'], true);
    } else {
        $inputs .= wf_HiddenInput('edituse_radius', '0');
    }
    $inputs .= wf_Submit(__('Save'));

    $form = wf_Form('', "POST", $inputs, 'glamour');

    $form .= wf_BackLink('?module=multinet');
    show_window(__('Edit'), $form);
}

/**
 * Renders service editing form
 * 
 * @param int $serviceid
 * 
 * @return void
 */
function multinet_show_serviceeditform($serviceid) {
    $serviceid = vf($serviceid, 3);
    $servicedata = multinet_get_service_params($serviceid);
    $inputs = wf_HiddenInput('serviceedit', 'true');
    $inputs .= multinet_network_selector($servicedata['netid']) . ' ' . __('Service network') . wf_tag('br');
    $inputs .= wf_TextInput('editservicename', __('Service description') . wf_tag('sup') . '*' . wf_tag('sup', true), $servicedata['desc'], true, 15);
    $inputs .= wf_Submit(__('Save'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');
    $form .= wf_BackLink('?module=multinet');
    show_window(__('Edit'), $form);
}

/**
 * Deletes some host from nethosts by its IP
 * 
 * @param string $ip
 * 
 * @return void
 */
function multinet_delete_host($ip) {
    $query = "DELETE from `nethosts` WHERE `ip`='" . $ip . "'";
    nr_query($query);
    log_register("DELETE MultiNetHost " . $ip);
}

/**
 * Returns networks selector control
 * 
 * @param int $currentnetid
 * 
 * @return string
 */
function multinet_network_selector($currentnetid = '') {
    $allnetworks = multinet_get_all_networks();
    $tmpArr = array();
    if (!empty($allnetworks)) {
        foreach ($allnetworks as $io => $eachnetwork) {
            $tmpArr[$eachnetwork['id']] = $eachnetwork['desc'];
        }
    }

    $result = wf_Selector('networkselect', $tmpArr, '', $currentnetid, false);
    return ($result);
}

/**
 * Returns unprocessed array of available networks with their data
 * 
 * @return array
 */
function multinet_get_all_networks() {
    $query = "SELECT * from `networks`";
    $result = simple_queryall($query);
    return($result);
}

/**
 * Returns selector of available networks types
 * 
 * @param string $curnettype
 * 
 * @return string
 */
function multinet_nettype_selector($curnettype = '') {
    $params = array(
        'dhcpstatic' => 'DHCP static hosts',
        'dhcpdynamic' => 'DHCP dynamic hosts',
        'dhcp82' => 'DHCP option 82',
        'dhcp82_vpu' => 'DHCP option 82 + vlan per user',
        'dhcp82_bdcom' => 'DHCP option 82 + mac onu (BDCOM)',
        'dhcp82_zte' => 'DHCP option 82 + mac onu (ZTE)',
        'pppstatic' => 'PPP static network',
        'pppdynamic' => 'PPP dynamic network',
        'other' => 'Other type'
    );

    $result = wf_Selector('nettypesel', $params, '', $curnettype, false);

    return ($result);
}

/**
 * Renders network creation form
 * 
 * @global object $ubillingConfig
 * 
 * @return void
 */
function multinet_show_networks_create_form() {
    global $ubillingConfig;

    $useRadArr = array('0' => __('No'), '1' => __('Yes'));

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('addnet', 'true');
    $inputs .= wf_TextInput('firstip', __('First IP') . $sup, '', true, '20', 'ip');
    $inputs .= wf_TextInput('lastip', __('Last IP') . $sup, '', true, '20', 'ip');
    $inputs .= multinet_nettype_selector() . ' ' . __('Network type') . wf_tag('br');
    $inputs .= wf_TextInput('desc', __('Network/CIDR') . $sup, '', true, '20', 'net-cidr');
    if ($ubillingConfig->getAlterParam('FREERADIUS_ENABLED')) {
        $inputs .= wf_Selector('use_radius', $useRadArr, __('Use Radius'), '', true);
        $inputs .= wf_tag('br');
    } else {
        $inputs .= wf_HiddenInput('use_radius', '0');
    }
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');

    show_window(__('Add network'), $form);
}

/**
 * Renders available services list with some controls
 * 
 * @return void
 */
function multinet_show_available_services() {
    $allservices = multinet_get_services();

    $tablecells = wf_TableCell(__('ID'));
    $tablecells .= wf_TableCell(__('Network'));
    $tablecells .= wf_TableCell(__('Service name'));
    $tablecells .= wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');

    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            $netdesc = multinet_get_network_params($eachservice['netid']);
            $tablecells = wf_TableCell($eachservice['id']);
            $tablecells .= wf_TableCell($netdesc['desc']);
            $tablecells .= wf_TableCell($eachservice['desc']);
            $actionlinks = wf_JSAlert('?module=multinet&deleteservice=' . $eachservice['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actionlinks .= wf_JSAlert('?module=multinet&editservice=' . $eachservice['id'], web_edit_icon(), 'Are you serious');
            $tablecells .= wf_TableCell($actionlinks);
            $tablerows .= wf_TableRow($tablecells, 'row5');
        }
    }

    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
    show_window(__('Services'), $result);
}

/**
 * Returns array of available services
 * 
 * @return array
 */
function multinet_get_services() {
    global $ubillingConfig;

    if ($ubillingConfig->getAlterParam('DROPDOWN_LISTS_IPSERVICE_ORDER_BY_DESCR')) {
        $query = "SELECT * FROM `services` ORDER BY `desc`";
    } else {
        $query = "SELECT * FROM `services` ORDER BY `id`";
    }
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns services selector control
 * 
 * @global object $ubillingConfig
 * @global object $branchControl
 * 
 * @return string
 */
function multinet_service_selector() {
    global $ubillingConfig;
    $tmpArr = array();
    if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
        global $branchControl;
        $branchControl->loadServices();
    }

    $allservices = multinet_get_services();
    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
                if ($branchControl->isMyService($eachservice['id'])) {
                    $tmpArr[$eachservice['id']] = $eachservice['desc'];
                }
            } else {
                $tmpArr[$eachservice['id']] = $eachservice['desc'];
            }
        }
    }

    $result = wf_Selector('serviceselect', $tmpArr, '', '', false, false);
    return ($result);
}

/**
 * Renders network service creation form
 * 
 * @return void
 */
function multinet_show_service_add_form() {
    $inputs = wf_HiddenInput('serviceadd', 'true');
    $inputs .= multinet_network_selector() . ' ' . __('Service network') . wf_tag('br');
    $inputs .= wf_TextInput('servicename', __('Service description'), '', true, 15);
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');
    show_window(__('Add service'), $form);
}

/**
 * Creates new multinet network in database
 * 
 * @param string $desc
 * @param string $firstip
 * @param string $lastip
 * @param string $nettype
 * @param int $use_radius
 * 
 * @return void
 */
function multinet_add_network($desc, $firstip, $lastip, $nettype, $use_radius) {
    $desc = mysql_real_escape_string($desc);
    $firstip = vf($firstip);
    $lastip = vf($lastip);
    $nettype = vf($nettype);
    $query = "INSERT INTO `networks` (`id`, `desc`, `startip`, `endip`, `nettype`, `use_radius` ) VALUES
              (NULL, '" . $desc . "', '" . $firstip . "', '" . $lastip . "', '" . $nettype . "', '" . $use_radius . "');";
    nr_query($query);
    log_register('ADD MultiNetNet `' . $desc . '`');
}

/**
 * Checks is network used by some network hosts or not
 * 
 * @param int $network_id
 * 
 * @return bool
 */
function multinet_network_is_used($network_id) {
    $network_id = vf($network_id, 3);
    $query = "SELECT `id` from `nethosts` WHERE `netid`='" . $network_id . "'";
    $allhosts = simple_query($query);
    if (!empty($allhosts)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Deletes some multinet network from database
 * 
 * @param type $network_id
 * 
 * @return void
 */
function multinet_delete_network($network_id) {
    $network_id = vf($network_id, 3);
    $query = "DELETE FROM `networks` WHERE `id`='" . $network_id . "'";
    nr_query($query);
    log_register('DELETE MultiNetNet [' . $network_id . ']');
}

/**
 * Creates new network service in database
 * 
 * @param int $net
 * @param string $desc
 * 
 * @return void
 */
function multinet_add_service($net, $desc) {
    $net = vf($net, 3);
    $desc = mysql_real_escape_string($desc);
    $query = "INSERT INTO `services` (`id`,`netid`,`desc` ) VALUES (NULL, '" . $net . "', '" . $desc . "');";
    nr_query($query);
    log_register('ADD MultiNetNetService `' . $desc . '`');
}

/**
 * Returns array of existing network parameters
 * 
 * @param int $network_id
 * 
 * @return array
 */
function multinet_get_network_params($network_id) {
    $network_id = vf($network_id, 3);
    $query = 'SELECT * from `networks` WHERE `id`="' . $network_id . '"';
    $result = simple_query($query);
    return($result);
}

/**
 * Returns array of existing service parameters
 * 
 * @param int $serviceid
 * 
 * @return array
 */
function multinet_get_service_params($serviceid) {
    $serviceid = vf($serviceid, 3);
    $query = 'SELECT * from `services` WHERE `id`="' . $serviceid . '"';
    $result = simple_query($query);
    return($result);
}

/**
 * Deletes existing network service from database
 * 
 * @param int $service_id
 * 
 * @return void
 */
function multinet_delete_service($service_id) {
    $service_id = vf($service_id, 3);
    $query = "DELETE FROM `services` WHERE `id`='" . $service_id . "'";
    nr_query($query);
    log_register('DELETE MultiNetService [' . $service_id . ']');
}

/**
 * Returns list of all existing dhcp-oriented networks
 * 
 * @return array
 */
function multinet_get_dhcp_networks() {
    $query = "SELECT * from `networks` where `nettype` LIKE 'dhcp%'";
    $alldhcps = simple_queryall($query);
    return($alldhcps);
}

/**
 * Returns dhcp handler data by network ID
 * 
 * @param int $netid
 * 
 * @return array
 */
function dhcp_get_data_by_netid($netid) {
    $netid = vf($netid, 3);
    $query = "SELECT * from `dhcp` where `netid`='" . $netid . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Rebuilds dhcp subnet config with some static hosts
 * 
 * @param int $netid
 * @param string $confname
 * 
 * @return void
 */
function handle_dhcp_rebuild_static($netid, $confname) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
// check haz it .conf name or not?
    if (!empty($confname)) {
        $confpath = 'multinet/' . $confname;
        $allhosts = simple_queryall($query);
        $result = '';
        if (!empty($allhosts)) {
            foreach ($allhosts as $io => $eachhost) {
                $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                $result .= '
   host ' . $dhcphostname . ' {
   hardware ethernet ' . $eachhost['mac'] . ';
   fixed-address ' . $eachhost['ip'] . ';
   }' . "\n";
            }

            file_put_contents($confpath, $result);
        } else {
            file_put_contents($confpath, $result);
        }
    }
}

/**
 * Rebuilds dhcp subnet config with option82 hosts
 * 
 * @param int $netid
 * @param string $confname
 * 
 * @return void
 */
function handle_dhcp_rebuild_option82($netid, $confname) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
    if (!empty($confname)) {
        $confpath = 'multinet/' . $confname;
        $allhosts = simple_queryall($query);
        $result = '';
        if (!empty($allhosts)) {
            foreach ($allhosts as $io => $eachhost) {
                $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                $options = explode('|', $eachhost['option']);
                $customTemplate = file_get_contents(CONFIG_PATH . "dhcp/option82.template");
                if (empty($customTemplate)) {
                    $customTemplate = '
       class "{HOSTNAME}" {
        match if binary-to-ascii (16, 8, "", option agent.remote-id) = "{REMOTEID}" and binary-to-ascii (10, 8, "", option agent.circuit-id) = "{CIRCUITID}";
       }

        pool {
        range {IP};
        allow members of "{HOSTNAME}";
        }
        ' . "\n";
                }

                if (isset($options[1])) {
                    $parseTemplate = $customTemplate;
                    $parseTemplate = str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
                    $parseTemplate = str_ireplace('{REMOTEID}', $options[0], $parseTemplate);
                    $parseTemplate = str_ireplace('{CIRCUITID}', $options[1], $parseTemplate);
                    $parseTemplate = str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);

                    $result .= $parseTemplate;
                }
            }

            file_put_contents($confpath, $result);
        } else {
            file_put_contents($confpath, $result);
        }
    }
}

/**
 * Rebuilds dhcp subnet config with option82 VPU hosts
 * 
 * @param int $netid
 * @param string $confname
 * 
 * @return void
 */
function handle_dhcp_rebuild_option82_vpu($netid, $confname) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
    if (!empty($confname)) {
        $confpath = 'multinet/' . $confname;
        $allhosts = simple_queryall($query);
        $allVlans = GetAllUserVlan();
        $allIps = GetAllUserIp();
        $result = '';
        if (!empty($allhosts)) {
            foreach ($allhosts as $io => $eachhost) {
                $login = $allIps[$eachhost['ip']];
                if (isset($allVlans[$login])) {
                    //$netid         = GetNetidByIp($eachhost['ip']);
                    $remote = GetTermRemoteByNetid($netid);
                    $vlan = $allVlans[$login];
                    $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                    $customTemplate = file_get_contents(CONFIG_PATH . "dhcp/option82_vpu.template");
                    if (!empty($vlan)) {
                        if (empty($customTemplate)) {
                            $customTemplate = '
class "{HOSTNAME}" { match if binary-to-ascii (16, 8, "", option agent.remote-id) = "{REMOTEID}" and binary-to-ascii(10, 16, "", substring(option agent.circuit-id,2,2)) = "{CIRCUITID}"; }
pool {
range {IP};
allow members of "{HOSTNAME}";
}
' . "\n";
                        }
                        $parseTemplate = $customTemplate;
                        $parseTemplate = str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
                        $parseTemplate = str_ireplace('{CIRCUITID}', $vlan, $parseTemplate);
                        $parseTemplate = str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
                        $parseTemplate = str_ireplace('{REMOTEID}', $remote, $parseTemplate);
                        $result .= $parseTemplate;
                    }
                }
            }
            file_put_contents($confpath, $result);
        } else {
            file_put_contents($confpath, $result);
        }
    }
}

/**
 * Rebuilds dhcp subnet config with option82 bdcom hosts
 * 
 * @param int $netid
 * @param string $confname
 * 
 * @return void
 */
function handle_dhcp_rebuild_option82_bdcom($netid, $confname) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
    if (!empty($confname)) {
        $confpath = 'multinet/' . $confname;
        $allhosts = simple_queryall($query);
        $allOnu = GetAllUserOnu();
        $allIps = GetAllUserIp();
        $result = '';
        if (!empty($allhosts)) {
            foreach ($allhosts as $io => $eachhost) {
                $login = $allIps[$eachhost['ip']];
                $mac = '';
                if (isset($allOnu[$login]) AND ! empty($allOnu[$login])) {
                    $macFull = explode(":", $allOnu[$login]['mac']);
                    foreach ($macFull as $eachOctet) {
                        $validOctet = preg_replace('/^0/', '', $eachOctet);
                        $mac .= $validOctet . ':';
                    }
                    $mac_len = strlen($mac);
                    $mac = substr($mac, 0, $mac_len - 1);
                    $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                    $customTemplate = file_get_contents(CONFIG_PATH . "dhcp/option82_bdcom.template");
                    if (empty($customTemplate)) {
                        $customTemplate = '
class "{HOSTNAME}" { match if binary-to-ascii(16,8,":",substring(option agent.remote-id,0,6)) = "{CIRCUITID}"; }
pool {
range {IP};
allow members of "{HOSTNAME}";
}
' . "\n";
                    }
                    $parseTemplate = $customTemplate;
                    $parseTemplate = str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
                    $parseTemplate = str_ireplace('{CIRCUITID}', $mac, $parseTemplate);
                    $parseTemplate = str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
                    $result .= $parseTemplate;
                }
            }
            file_put_contents($confpath, $result);
        } else {
            file_put_contents($confpath, $result);
        }
    }
}

/**
 * Rebuilds dhcp subnet config with option82 zte hosts
 * 
 * @param int $netid
 * @param string $confname
 * 
 * @return void
 */
function handle_dhcp_rebuild_option82_zte($netid, $confname) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
    if (!empty($confname)) {
        $confpath = 'multinet/' . $confname;
        $allhosts = simple_queryall($query);
        $allOnu = GetAllUserOnu();
        $allIps = GetAllUserIp();
        $allOltSnmpTemplates = loadOltSnmpTemplates();
        $result = '';
        if (!empty($allhosts)) {
            foreach ($allhosts as $io => $eachhost) {
                $login = $allIps[$eachhost['ip']];
                $onuId = '';
                $onuIdentifier = '';
                if (isset($allOnu[$login]) AND ! empty($allOnu[$login])) {
                    $oltId = $allOnu[$login]['oltid'];

                    if (isset($allOltSnmpTemplates[$oltId])) {
                        if ($allOltSnmpTemplates[$oltId]['signal']['SIGNALMODE'] == 'ZTE') {
                            $onuIdentifier = explode(":", $allOnu[$login]['mac']);
                        } elseif ($allOltSnmpTemplates[$oltId]['signal']['SIGNALMODE'] == 'ZTE_GPON') {
                            if (!empty($allOnu[$login]['serial'])) {
                                $onuIdentifier = explode(":", $allOnu[$login]['serial']);
                            }
                        }
                    }

                    if (!empty($onuIdentifier)) {
                        foreach ($onuIdentifier as $eachOctet) {
                            $onuId .= strtoupper($eachOctet);
                        }
                        $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                        $customTemplate = file_get_contents(CONFIG_PATH . "dhcp/option82_zte.template");
                        if (empty($customTemplate)) {
                            $customTemplate = '
class "{HOSTNAME}" { match if substring(option agent.circuit-id,49,12) = "{CIRCUITID}"; }
pool {
range {IP};
allow members of "{HOSTNAME}";
}
' . "\n";
                        }
                        $parseTemplate = $customTemplate;
                        $parseTemplate = str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
                        $parseTemplate = str_ireplace('{CIRCUITID}', $onuId, $parseTemplate);
                        $parseTemplate = str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
                        $result .= $parseTemplate;
                    }
                }
            }
            file_put_contents($confpath, $result);
        } else {
            file_put_contents($confpath, $result);
        }
    }
}

/**
 * Generates some static ppp secrets file
 * 
 * @param int $netid
 * 
 * @return void
 */
function handle_ppp_rebuild_static($netid) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
    $confpath = 'multinet/ppp.' . $netid . '.static';
    $allhosts = simple_queryall($query);
    $result = '';
    if (!empty($allhosts)) {
        foreach ($allhosts as $io => $eachhost) {
            $accdata_q = "SELECT `login`,`Password` from `users` WHERE `IP`='" . $eachhost['ip'] . "'";
            $accdata = simple_query($accdata_q);
            $result .= $accdata['login'] . ' ' . $accdata['Password'] . ' ' . $eachhost['ip'] . "\n";
        }
    }
    file_put_contents($confpath, $result);
}

/**
 * Generates dynamic ppp secrets file
 * 
 * @param int $netid
 * 
 * @return void
 */
function handle_ppp_rebuild_dynamic($netid) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";
    $confpath = 'multinet/ppp.' . $netid . '.dynamic';
    $allhosts = simple_queryall($query);
    $result = '';
    if (!empty($allhosts)) {
        foreach ($allhosts as $io => $eachhost) {
            $accdata_q = "SELECT `login`,`Password` from `users` WHERE `IP`='" . $eachhost['ip'] . "'";
            $accdata = simple_query($accdata_q);
            $result .= $accdata['login'] . ' ' . $accdata['Password'] . "\n";
        }
    }
    file_put_contents($confpath, $result);
}

/**
 * Returns template with replaced macro
 * 
 * @param string $templatebody
 * @param array $templatedata
 * 
 * @return string
 */
function multinet_ParseTemplate($templatebody, $templatedata) {
    foreach ($templatedata as $field => $data) {
        $templatebody = str_ireplace($field, $data, $templatebody);
    }
    return($templatebody);
}

/**
 * Converts CIDR mask into decimal like 24 => 255.255.255.0
 * 
 * @param int $mask_bits
 * 
 * @return string 
 */
function multinet_cidr2mask($mask_bits) {
    if ($mask_bits > 31 || $mask_bits < 0)
        return("0.0.0.0");
    $host_bits = 32 - $mask_bits;
    $num_hosts = pow(2, $host_bits) - 1;
    $netmask = ip2int("255.255.255.255") - $num_hosts;
    return int2ip($netmask);
}

/**
 * Rebuilds dhcp global config file
 * 
 * @global object $ubillingConfig
 * 
 * @return void
 */
function multinet_rebuild_globalconf() {
    global $ubillingConfig;
    $global_template = file_get_contents("config/dhcp/global.template");
    $subnets_template = file_get_contents("config/dhcp/subnets.template");
    $alldhcpsubnets_q = "SELECT `id`,`netid` from `dhcp` ORDER BY `id` ASC";
    $alldhcpsubnets = simple_queryall($alldhcpsubnets_q);
    $allMembers_q = "SELECT `ip` from `nethosts` WHERE `option` != 'NULL'";
    $allMembers = simple_queryall($allMembers_q);
    $membersMacroContent = '';
    $vlanMembersMacroContent = '';
    $onuMembersMacroContent = '';

    if (!empty($allMembers)) {
        foreach ($allMembers as $ix => $eachMember) {
            $memberClass = 'm' . str_replace('.', 'x', $eachMember['ip']);
            ;
            $membersMacroContent .= 'deny members of "' . $memberClass . '";' . "\n";
        }
    }

    if ($ubillingConfig->getAlterParam('VLANGEN_SUPPORT')) {
        $vlanMembers_q = "SELECT `ip` FROM `users` WHERE `login` IN(SELECT `login` FROM `vlanhosts`);";
        $allVlanMembers = simple_queryall($vlanMembers_q);
        if (!empty($allVlanMembers)) {
            foreach ($allVlanMembers as $ivl => $eachVlanMember) {
                $memberVlanClass = 'm' . str_replace('.', 'x', $eachVlanMember['ip']);
                $vlanMembersMacroContent .= 'deny members of "' . $memberVlanClass . '";' . "\n";
            }
        }
    }

    $onuMembers_q = "SELECT `ip` FROM `nethosts` WHERE `netid` IN (SELECT `id` FROM `networks` WHERE `nettype` = 'dhcp82_bdcom' or `nettype` = 'dhcp82_zte');";
    $allOnuMembers = simple_queryall($onuMembers_q);
    if (!empty($allOnuMembers)) {
        foreach ($allOnuMembers as $index => $eachOnuMember) {
            $memberOnuClass = 'm' . str_replace('.', 'x', $eachOnuMember['ip']);
            $onuMembersMacroContent .= 'deny members of "' . $memberOnuClass . '";' . "\n";
        }
    }

    $subnets = '';
    if (!empty($alldhcpsubnets)) {
        foreach ($alldhcpsubnets as $io => $eachnet) {
            $netdata = multinet_get_network_params($eachnet['netid']);
            $templatedata['{STARTIP}'] = $netdata['startip'];
            $templatedata['{ENDIP}'] = $netdata['endip'];
            $templatedata['{CIDR}'] = explode('/', $netdata['desc']);
            $templatedata['{NETWORK}'] = $templatedata['{CIDR}'][0];
            $templatedata['{CIDR}'] = $templatedata['{CIDR}'][1];
            $templatedata['{ROUTERS}'] = int2ip(ip2int($templatedata['{STARTIP}']) + 1);
            $templatedata['{MASK}'] = multinet_cidr2mask($templatedata['{CIDR}']);
            $dhcpdata = dhcp_get_data_by_netid($eachnet['netid']);
            if (isset($dhcpdata['confname'])) {
                $templatedata['{HOSTS}'] = $dhcpdata['confname'];
// check if override?
                if (!empty($dhcpdata['dhcpconfig'])) {
                    $currentsubtpl = $dhcpdata['dhcpconfig'];
                } else {
                    $currentsubtpl = $subnets_template;
                }
                $subnets .= multinet_ParseTemplate($currentsubtpl, $templatedata) . "\n";
            }
        }
    }

    $globdata['{SUBNETS}'] = $subnets;
    $globdata['{DENYMEMBERS}'] = $membersMacroContent;
    $globdata['{DENYVLANGENMEMBERS}'] = $vlanMembersMacroContent;
    $globdata['{DENYONUMEMBERS}'] = $onuMembersMacroContent;
    $globconf = multinet_ParseTemplate($global_template, $globdata);
    file_write_contents("multinet/dhcpd.conf", $globconf);
}

function multinet_rebuild_all_handlers() {
    $allnets = multinet_get_all_networks();
    if (!empty($allnets)) {
        foreach ($allnets as $io => $eachnet) {
            if ($eachnet['nettype'] == 'dhcpstatic') {
                $dhcpdata = dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_static($eachnet['id'], @$dhcpdata['confname']);
//deb('REBUILD NETWORK:'.$eachnet['id'].'|'.$dhcpdata['confname']);
            }
            if ($eachnet['nettype'] == 'dhcp82') {
                $dhcpdata82 = dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_option82($eachnet['id'], $dhcpdata82['confname']);
            }

            if ($eachnet['nettype'] == 'dhcp82_vpu') {
                $dhcpdata82_vpu = dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_option82_vpu($eachnet['id'], $dhcpdata82_vpu['confname']);
            }

            if ($eachnet['nettype'] == 'dhcp82_bdcom') {
                $dhcpdata82_bdcom = dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_option82_bdcom($eachnet['id'], $dhcpdata82_bdcom['confname']);
            }

            if ($eachnet['nettype'] == 'dhcp82_zte') {
                $dhcpdata82_zte = dhcp_get_data_by_netid($eachnet['id']);
                handle_dhcp_rebuild_option82_zte($eachnet['id'], $dhcpdata82_zte['confname']);
            }

            if ($eachnet['nettype'] == 'pppstatic') {
                handle_ppp_rebuild_static($eachnet['id']);
            }
            if ($eachnet['nettype'] == 'pppdynamic') {
                handle_ppp_rebuild_dynamic($eachnet['id']);
            }
        }
    }
    //rebuilding global conf 
    multinet_rebuild_globalconf();
    //restarting dhcpd
    multinet_RestartDhcp();
    //debarr(dhcp_get_data_by_netid(5));
}

/**
 * Creates new network host in database
 * 
 * @param int $netid
 * @param string $ip
 * @param string $mac
 * @param string $option
 * 
 * @return void
 */
function multinet_add_host($netid, $ip, $mac = 'NULL', $option = 'NULL') {
    $query = "INSERT INTO `nethosts` (`id` ,`ip` ,`mac` ,`netid` ,`option`) VALUES
             (NULL , '" . $ip . "', '" . $mac . "', '" . $netid . "', '" . $option . "');";
    nr_query($query);
    log_register("ADD MultiNetHost `" . $ip . '`');
}

/**
 * Changes existing network host MAC address by host IP
 * 
 * @param string $ip
 * @param string $newmac
 * 
 * @return void
 */
function multinet_change_mac($ip, $newmac) {
    $newmac = strtolower($newmac);
    $query = "UPDATE `nethosts` SET `mac` = '" . $newmac . "' WHERE `ip` = '" . $ip . "' ;";
    nr_query($query);
    log_register("CHANGE MultiNetHostMac " . $ip . " " . $newmac);
    zb_UserGetAllDataCacheClean();
}

/**
 * Extracts all IPs between another two. 
 * Preserving broadcasts, net address and first IP for NAS.
 * 
 * @param string $first_ip
 * @param string $last_ip
 * 
 * @return array
 */
function multinet_expand_network($first_ip, $last_ip) {
    $first = ip2int($first_ip);
    $last = ip2int($last_ip);
    for ($i = $first; $i <= $last; $i++) {
        $totalnet[] = int2ip($i);
    }
    if (!empty($totalnet)) {
        foreach ($totalnet as $eachip) {
            if (preg_match("#\.(0|1|255)$#", $eachip)) {
                unset($eachip);
            }
            if (isset($eachip)) {
                $filterednet[] = $eachip;
            }
        }
    }
    return($filterednet);
}

/**
 * Returns all free and unused IP addresses for some network ID
 * 
 * @param type $table
 * @param type $field
 * @param type $network_id
 * 
 * @return array
 */
function multinet_get_all_free_ip($table, $field, $network_id) {
    $network_spec = multinet_get_network_params($network_id);
    $first_ip = $network_spec['startip'];
    $last_ip = $network_spec['endip'];
    $clear_ips = array();
    $full_network_pool = multinet_expand_network($first_ip, $last_ip);
    $current_state_q = "SELECT `" . $field . "` from `" . $table . "` WHERE `netid` = '" . $network_id . "'";
    $all_current_used_ip = simple_queryall($current_state_q);
    if (!empty($all_current_used_ip)) {
        foreach ($all_current_used_ip as $io => $usedip) {
            $clear_ips[] = $usedip[$field];
        }
        $free_ip_pool = array_diff($full_network_pool, $clear_ips);
    } else {
        $free_ip_pool = $full_network_pool;
    }
    return($free_ip_pool);
}

/**
 * Returns first free and unused IP for some network ID
 * 
 * @param string $table
 * @param string $field
 * @param int $network_id
 * 
 * @return string
 */
function multinet_get_next_freeip($table, $field, $network_id) {
    $all_free_ips = multinet_get_all_free_ip($table, $field, $network_id);
    $temp = array_keys($all_free_ips);
    return(@$all_free_ips[$temp[0]]);
}

/**
 * Returns network ID by associated service ID
 * 
 * @param int $service_id
 * 
 * @return int
 */
function multinet_get_service_networkid($service_id) {
    $service_id = vf($service_id);
    $query = "SELECT `netid` from `services` WHERE `id`='" . $service_id . "'";
    $service_network = simple_query($query);
    $service_network = $service_network['netid'];
    return($service_network);
}

/**
 * Checks is some IP between another two
 * 
 * @param string $user_ip
 * @param string $ip_begin
 * @param string $ip_end
 * 
 * @return bool
 */
function multinet_checkIP($user_ip, $ip_begin, $ip_end) {
    return (ip2int($user_ip) >= ip2int($ip_begin) && ip2int($user_ip) <= ip2int($ip_end));
}

/**
 * Converts bytes into human-readable values like Kb, Mb, Gb...
 * 
 * @global object $ubillingConfig
 * 
 * @param int $fs
 * 
 * @return string
 */
function stg_convert_size($fs) {
    global $ubillingConfig;
    $alter_conf = $ubillingConfig->getAlter();
    $traffsize = trim($alter_conf['TRAFFSIZE']);
    if ($traffsize == 'float') {
        if ($fs >= (1073741824 * 1024))
            $fs = round($fs / (1073741824 * 1024) * 100) / 100 . " Tb";
        elseif ($fs >= 1073741824)
            $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
        elseif ($fs >= 1048576)
            $fs = round($fs / 1048576 * 100) / 100 . " Mb";
        elseif ($fs >= 1024)
            $fs = round($fs / 1024 * 100) / 100 . " Kb";
        else
            $fs = $fs . " b";
        return ($fs);
    }

    if ($traffsize == 'b') {
        return ($fs);
    }

    if ($traffsize == 'Kb') {
        $fs = round($fs / 1024 * 100) / 100 . " Kb";
        return ($fs);
    }

    if ($traffsize == 'Mb') {
        $fs = round($fs / 1048576 * 100) / 100 . " Mb";
        return ($fs);
    }
    if ($traffsize == 'Gb') {
        $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
        return ($fs);
    }

    if ($traffsize == 'Tb') {
        $fs = round($fs / (1073741824 * 1024) * 100) / 100 . " Tb";
        return ($fs);
    }
}

/**
 * Convert bytes to human-readable Gb values. Much faster than stg_convert_size()
 * 
 * @param int $fs
 * 
 * @return string
 */
function zb_TraffToGb($fs) {
    $fs = round($fs / 1073741824, 2) . " Gb";
    return ($fs);
}

/**
 * Returns array of available tariff speeds as tariffname=>array(speeddown/speedup)
 * 
 * @return array
 */
function zb_TariffGetAllSpeeds() {
    global $ubillingConfig;
    $query = "SELECT * from `speeds`";
    $allspeeds = simple_queryall($query);
    $result = array();
    if (!empty($allspeeds)) {
        foreach ($allspeeds as $io => $eachspeed) {
            $result[$eachspeed['tariff']]['speeddown'] = $eachspeed['speeddown'];
            $result[$eachspeed['tariff']]['speedup'] = $eachspeed['speedup'];
            if ($ubillingConfig->getAlterParam('BURST_ENABLED')) {
                $result[$eachspeed['tariff']]['burstdownload'] = $eachspeed['burstdownload'];
                $result[$eachspeed['tariff']]['burstupload'] = $eachspeed['burstupload'];
                $result[$eachspeed['tariff']]['bursttimedownload'] = $eachspeed['bursttimedownload'];
                $result[$eachspeed['tariff']]['burstimetupload'] = $eachspeed['burstimetupload'];
            }
        }
    }
    return($result);
}

/**
 * Creates new tariff speed record in database
 * 
 * @param type $tariff
 * @param type $speeddown
 * @param type $speedup
 * @param type $burstdownload
 * @param type $burstupload
 * @param type $bursttimedownload
 * @param type $burstimetupload
 * 
 * @return void
 */
function zb_TariffCreateSpeed($tariff, $speeddown, $speedup, $burstdownload = '', $burstupload = '', $bursttimedownload = '', $burstimetupload = '') {
    $tariff = mysql_real_escape_string($tariff);
    $speeddown = vf($speeddown);
    $speedup = vf($speedup);
    $burstdownload = vf($burstdownload);
    $burstupload = vf($burstupload);
    $bursttimedownload = vf($bursttimedownload);
    $burstimetupload = vf($burstimetupload);
    $query = "INSERT INTO `speeds` (`id` , `tariff` , `speeddown` , `speedup` , `burstdownload` , `burstupload` , `bursttimedownload` , `burstimetupload`) VALUES
    (NULL , '" . $tariff . "', '" . $speeddown . "', '" . $speedup . "', '" . $burstdownload . "', '" . $burstupload . "', '" . $bursttimedownload . "', '" . $burstimetupload . "');";
    nr_query($query);
    log_register('CREATE TariffSpeed `' . $tariff . '` ' . $speeddown . ' ' . $speedup . ' ' . $burstdownload . ' ' . $burstupload . ' ' . $bursttimedownload . ' ' . $burstimetupload);
}

/**
 * Deletes tariff speed from database
 * 
 * @param string $tariff
 * 
 * @return void
 */
function zb_TariffDeleteSpeed($tariff) {
    $tariff = mysql_real_escape_string($tariff);
    $query = "DELETE from `speeds` where `tariff`='" . $tariff . "'";
    nr_query($query);
    log_register('DELETE TariffSpeed `' . $tariff . '`');
}

/**
 * Returns array of tariff-based signup prices as tariff=>price
 * 
 * @return array
 */
function zb_TariffGetAllSignupPrices() {
    $query = "SELECT * FROM `signup_prices_tariffs`";
    $results = simple_queryall($query);
    $return = array();
    if (!empty($results)) {
        foreach ($results as $result) {
            $return[$result['tariff']] = $result['price'];
        }
    }
    return ($return);
}

/**
 * Creates new tariff-based signup price in database
 * 
 * @param string $tariff
 * @param float $price
 * 
 * @return void
 */
function zb_TariffCreateSignupPrice($tariff, $price) {
    $query = "INSERT INTO `signup_prices_tariffs` (`tariff`, `price`) VALUES ('" . $tariff . "', '" . $price . "')";
    nr_query($query);
    log_register('CREATE TariffSignupPrice ' . $tariff . ' ' . $price);
}

/**
 * Deletes tariff-based signup price from database
 * 
 * @param string $tariff
 * 
 * @return void
 */
function zb_TariffDeleteSignupPrice($tariff) {
    $query = "DELETE FROM `signup_prices_tariffs` WHERE `tariff` = '" . $tariff . "'";
    nr_query($query);
    log_register('DELETE TariffSignupPrice ' . $tariff);
}

/**
 * Returns network host MAC address by its IP
 * 
 * @param string $ip
 * 
 * @return string
 */
function zb_MultinetGetMAC($ip) {
    $query = "SELECT `mac` from `nethosts` WHERE `ip`='" . $ip . "'";
    $result = simple_query($query);
    $result = $result['mac'];
    return($result);
}

/**
 * Returns user IP addres by its login
 * 
 * @param string $login
 * 
 * @return string
 */
function zb_UserGetIP($login) {
    $userdata = zb_UserGetStargazerData($login);
    $userip = $userdata['IP'];
    return ($userip);
}

/**
 * Returns array of all available traffic directions
 * 
 * @return array
 */
function zb_DirectionsGetAll() {
    $query = "SELECT * from `directions`";
    $allrules = simple_queryall($query);
    return ($allrules);
}

/**
 * Deletes existing traffic direction from database
 * 
 * @param int $directionid
 * 
 * @return void
 */
function zb_DirectionDelete($directionid) {
    $directionid = vf($directionid, 3);
    $query = "DELETE FROM `directions` WHERE `id`='" . $directionid . "'";
    nr_query($query);
    log_register('DELETE TrafficClass [' . $directionid . ']');
    rcms_redirect("?module=rules");
}

/**
 * Returns traffic direction data
 * 
 * @param int $directionid
 *  
 * @return array
 */
function zb_DirectionGetData($directionid) {
    $directionid = vf($directionid, 3);
    $query = "SELECT * from `directions` WHERE `id`='" . $directionid . "'";
    $data = simple_query($query);
    return($data);
}

/**
 * Creates new traffic direction in database
 * 
 * @param int $rulenumber
 * @param string $rulename
 * 
 * @return void
 */
function zb_DirectionAdd($rulenumber, $rulename) {
    $rulenumber = vf($rulenumber);
    $rulename = mysql_real_escape_string($rulename);
    $query = "INSERT INTO `directions` (`id` , `rulenumber` , `rulename`) VALUES
        (NULL , '" . $rulenumber . "', '" . $rulename . "');";
    nr_query($query);
    log_register("ADD TrafficClass `" . $rulenumber . '` `' . $rulename . '`');
}

/**
 * Creates new NAS in database
 * 
 * @param int $netid
 * @param string $nasip
 * @param string $nasname
 * @param string $nastype
 * @param string $bandw
 * 
 * @return void
 */
function zb_NasAdd($netid, $nasip, $nasname, $nastype, $bandw) {
    $netid = vf($netid, 3);
    $nasname = mysql_real_escape_string($nasname);
    $nastype = vf($nastype);
    $bandw = mysql_real_escape_string($bandw);
    $nasip = mysql_real_escape_string($nasip);
    $query = "INSERT INTO `nas` (`id` ,`netid` , `nasip` , `nasname` , `nastype` , `bandw`) VALUES
              (NULL , '" . $netid . "', '" . $nasip . "', '" . $nasname . "',  '" . $nastype . "', '" . $bandw . "' );";
    nr_query($query);
    log_register('NAS ADD `' . $nasip . '`');
}

/**
 * Returns all available NAS data
 * 
 * @return array
 */
function zb_NasGetAllData() {
    $query = "SELECT * from `nas`";
    $allnas = simple_queryall($query);
    return($allnas);
}

/**
 * Returns some existing NAS parameters
 * 
 * @param int $nasid
 * 
 * @return array
 */
function zb_NasGetData($nasid) {
    $nasid = vf($nasid, 3);
    $query = "SELECT * from `nas` WHERE `id`='" . $nasid . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Gets NAS IP-address, using its id
 * 
 * @param int $nasid
 * 
 * @return string  
 */
function zb_NasGetIpById($nasid) {
    $nasid = vf($nasid);
    $query = "SELECT `nasip` FROM `nas` WHERE `id` = '" . $nasid . "'";
    $result = simple_query($query);
    return ($result['nasip']);
}

/**
 * Decodes and unserializes data from base64 encoding
 * 
 * @param   int     $nasid  NAS'es id to update options
 * @param   array   $data   Options
 * 
 * @return  array           Options
 */
function zb_NasOptionsGet($nasid) {
    $result = array();
    if (!empty($nasid)) {
        $query = "SELECT `options` FROM `nas` WHERE `id` = " . $nasid . ";";
        $result = simple_queryall($query);
        if (!empty($result)) {
            foreach ($result as $data) {
                $decoded = base64_decode($data['options']);
                $result = unserialize($decoded);
            }
        }
    }
    return $result;
}

/**
 * Deletes NAS from database
 * 
 * @param int $nasid
 * 
 * @return void
 */
function zb_NasDelete($nasid) {
    $nasid = vf($nasid, 3);
    $query = "DELETE from `nas` WHERE `id`='" . $nasid . "'";
    nr_query($query);
    log_register('NAS DELETE [' . $nasid . ']');
}

/**
 * Saves rscriptd NAS servers config and sends HUP signal to stargazer
 * 
 * @return void
 */
function zb_NasConfigSave() {
    $ub_conf = rcms_parse_ini_file(CONFIG_PATH . "billing.ini");
    $query = "SELECT * from `nas` WHERE `nastype`='rscriptd'";
    $result = '';
    $allnas = simple_queryall($query);
    if (!empty($allnas)) {
        foreach ($allnas as $io => $eachnas) {
            $net_q = multinet_get_network_params($eachnas['netid']);
            $net_cidr = $net_q['desc'];
            $result .= $net_cidr . ' ' . $eachnas['nasip'] . "\n";
        }
    }
    file_put_contents('remote_nas.conf', $result);

    if ($ub_conf['STGNASHUP']) {
        $sig_command = $ub_conf['SUDO'] . ' ' . $ub_conf['KILL'] . ' -1' . ' `' . $ub_conf['CAT'] . ' ' . $ub_conf['STGPID'] . '`';
        shell_exec($sig_command);
        log_register("SIGHUP STG ");
    }
}

/**
 * Restarts ISC-DHCPD server
 * 
 * @return void
 */
function multinet_RestartDhcp() {
    $config = rcms_parse_ini_file(CONFIG_PATH . 'billing.ini');
    $sudo = $config['SUDO'];
    $dhcpd = $config['RC_DHCPD'];
    $command = $sudo . ' ' . $dhcpd . ' restart';
    shell_exec($command);
    log_register("RESTART DHCPD");
}

/**
 * Returns NAS id by associated network ID
 * 
 * @param int $netid
 * 
 * @return int
 */
function zb_NasGetByNet($netid) {
    $netid = vf($netid, 3);
    $query = "SELECT `id` from `nas` WHERE `netid`='" . $netid . "'";
    $nasid = simple_query($query);
    $nasid = @$nasid['id'];
    return($nasid);
}

/**
 * Returns network ID by some IP address
 * 
 * @param string $ip
 * 
 * @return int
 */
function zb_NetworkGetByIp($ip) {
    $allnets = multinet_get_all_networks();
    if (!empty($allnets)) {
        foreach ($allnets as $io => $eachnet) {
            $completenet = multinet_checkIP($ip, $eachnet['startip'], $eachnet['endip']);
            if ($completenet) {
                $result = $eachnet['id'];
                break;
            } else {
                $result = false;
            }
        }
    }

    return($result);
}

/**
 * Gets the Bandwidthd URL by user's IP address from database
 * 
 * @param   string     $ip     User's IP address
 * 
 * @return  string    Bandwidthd URL
 */
function zb_BandwidthdGetUrl($ip) {
    $netid = zb_NetworkGetByIp($ip);
    $nasid = zb_NasGetByNet($netid);
    $nasdata = zb_NasGetData($nasid);
    $bandwidthd_url = @$nasdata['bandw'];

    if (!empty($bandwidthd_url)) {
        return $bandwidthd_url;
    } else
        return false;
}

/**
 * Generates ghaph images links:
 * 
 * @param   string      $ip      User's IP address, for whitch links are generated
 * 
 * @return  array       Graph links
 */
function zb_BandwidthdGenLinks($ip) {
    global $ubillingConfig;
    $zbxGraphsEnabled = $ubillingConfig->getAlterParam('ZABBIX_USER_TRAFFIC_GRAPHS');
    $zbxGraphsSearchIdnetify = ($ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHIDENTIFY')) ? $ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHIDENTIFY') : 'MAC';
    $zbxGraphsSearchField = ($ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHFIELD')) ? $ubillingConfig->getAlterParam('ZABBIX_GRAPHS_SEARCHFIELD') : 'name';
    $zbxGraphsExtended = wf_getBoolFromVar($ubillingConfig->getAlterParam('ZABBIX_GRAPHS_EXTENDED'));
    $mlgUseMikrotikGraphs = wf_getBoolFromVar($ubillingConfig->getAlterParam('MULTIGEN_USE_ROS_TRAFFIC_GRAPHS'));

    $bandwidthd_url = zb_BandwidthdGetUrl($ip);
    $netid = zb_NetworkGetByIp($ip);
    $nasid = zb_NasGetByNet($netid);
    $nasdata = zb_NasGetData($nasid);
    $nastype = ($mlgUseMikrotikGraphs) ? 'mikrotik' : $nasdata['nastype'];
    $zbxAllGraphs = array();

    if ($zbxGraphsEnabled) {
        $zbxAllGraphs = getCachedZabbixNASGraphIDs();
    }

    if (!empty($zbxAllGraphs) and isset($zbxAllGraphs[$nasdata['nasip']])) {
        $userSearchIdentify = ($zbxGraphsSearchIdnetify == 'MAC') ? zb_MultinetGetMAC($ip) : $ip;
        $urls = getZabbixUserGraphLinks($ip, $zbxGraphsSearchField, $userSearchIdentify, $zbxAllGraphs, $zbxGraphsExtended);
    } else {
// RouterOS graph model:
        if ($nastype == 'mikrotik') {
            // Get user's IP array:
            $alluserips = zb_UserGetAllIPs();
            $alluserips = array_flip($alluserips);
            if (!ispos($bandwidthd_url, 'pppoe') and ! $mlgUseMikrotikGraphs) {
// Generate graphs paths:
                $urls['dayr'] = $bandwidthd_url . '/' . $alluserips[$ip] . '/daily.gif';
                $urls['days'] = null;
                $urls['weekr'] = $bandwidthd_url . '/' . $alluserips[$ip] . '/weekly.gif';
                $urls['weeks'] = null;
                $urls['monthr'] = $bandwidthd_url . '/' . $alluserips[$ip] . '/monthly.gif';
                $urls['months'] = null;
                $urls['yearr'] = $bandwidthd_url . '/' . $alluserips[$ip] . '/yearly.gif';
                $urls['years'] = null;
            } elseif ($mlgUseMikrotikGraphs) {
                $urls['dayr'] = $bandwidthd_url . '/' . 'mlg_' . $ip . '/daily.gif';
                $urls['days'] = null;
                $urls['weekr'] = $bandwidthd_url . '/' . 'mlg_' . $ip . '/weekly.gif';
                $urls['weeks'] = null;
                $urls['monthr'] = $bandwidthd_url . '/' . 'mlg_' . $ip . '/monthly.gif';
                $urls['months'] = null;
                $urls['yearr'] = $bandwidthd_url . '/' . 'mlg_' . $ip . '/yearly.gif';
            } else {
                $urls['dayr'] = $bandwidthd_url . $alluserips[$ip] . '>/daily.gif';
                $urls['days'] = null;
                $urls['weekr'] = $bandwidthd_url . $alluserips[$ip] . '>/weekly.gif';
                $urls['weeks'] = null;
                $urls['monthr'] = $bandwidthd_url . $alluserips[$ip] . '>/monthly.gif';
                $urls['months'] = null;
                $urls['yearr'] = $bandwidthd_url . $alluserips[$ip] . '>/yearly.gif';
                $urls['years'] = null;
            }
        } else {
// Banwidthd graphs model:
            $urls['dayr'] = $bandwidthd_url . '/' . $ip . '-1-R.png';
            $urls['days'] = $bandwidthd_url . '/' . $ip . '-1-S.png';
            $urls['weekr'] = $bandwidthd_url . '/' . $ip . '-2-R.png';
            $urls['weeks'] = $bandwidthd_url . '/' . $ip . '-2-S.png';
            $urls['monthr'] = $bandwidthd_url . '/' . $ip . '-3-R.png';
            $urls['months'] = $bandwidthd_url . '/' . $ip . '-3-S.png';
            $urls['yearr'] = $bandwidthd_url . '/' . $ip . '-4-R.png';
            $urls['years'] = $bandwidthd_url . '/' . $ip . '-4-S.png';
        }
    }

    return($urls);
}

/**
 * Returns exploded array of some multi-lined strings
 * 
 * @param string $data
 * 
 * @return array
 */
function explodeRows($data) {
    $result = explode("\n", $data);
    return ($result);
}

/**
 * Returns new unknown MAC addresses parsed from NMLEASES in some table-view
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function zb_NewMacShow() {
    global $ubillingConfig;
    $billing_config = $ubillingConfig->getBilling();
    $alter_config = $ubillingConfig->getAlter();
    $allarp = array();
    $sudo = $billing_config['SUDO'];
    $cat = $billing_config['CAT'];
    $grep = $billing_config['GREP'];
    $tail = $billing_config['TAIL'];
    $leases = $alter_config['NMLEASES'];
    $leasemark = $alter_config['NMLEASEMARK'];
    $command = $sudo . ' ' . $cat . ' ' . $leases . ' | ' . $grep . ' "' . $leasemark . '" | ' . $tail . ' -n 200';
    $rawdata = shell_exec($command);
    $allusedMacs = zb_getAllUsedMac();
    $result = '';

//fdb cache preprocessing  
    $fdbData_raw = rcms_scandir('./exports/', '*_fdb');
    if (!empty($fdbData_raw)) {
        $fdbArr = sn_SnmpParseFdbCacheArray($fdbData_raw);
        $fdbColumn = true;
    } else {
        $fdbArr = array();
        $fdbColumn = false;
    }

    $cells = wf_TableCell(__('MAC'));
    if (!empty($fdbColumn)) {
        $cells .= wf_TableCell(__('Switch'));
    }
    if ($ubillingConfig->getAlterParam('MACVEN_ENABLED')) {
        $cells .= wf_TableCell(__('Manufacturer'));
    }
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($rawdata)) {
        $cleardata = exploderows($rawdata);
        foreach ($cleardata as $eachline) {
            preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
            if (!empty($matches)) {
                $allarp[] = $matches[0];
            }
            if ($alter_config['NMLEASES_EXTEND']) {
                $eachline = preg_replace('/([a-f0-9]{2})(?![\s\]\/])([\.\:\-]?)/', '\1:', $eachline);
                preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
                if (!empty($matches[0])) {
                    $allarp[] = $matches[0];
                }
            }
        }
        $un_arr = array_unique($allarp);
        if (!empty($un_arr)) {
            if ($ubillingConfig->getAlterParam('MACVEN_ENABLED')) {
                //adding ajax loader
                $result .= wf_AjaxLoader();
            }
            foreach ($un_arr as $io => $eachmac) {
                if (zb_checkMacFree($eachmac, $allusedMacs)) {
                    $cells = wf_TableCell(@$eachmac);
                    if (!empty($fdbColumn)) {
                        $cells .= wf_TableCell(sn_SnmpParseFdbExtract(@$fdbArr[$eachmac]));
                    }

                    if ($ubillingConfig->getAlterParam('MACVEN_ENABLED')) {
                        $containerName = 'NMRSMCNT_' . zb_rand_string(8);
                        $lookupVendorLink = wf_AjaxLink('?module=macvendor&mac=' . @$eachmac . '&raw=true', wf_img('skins/macven.gif', __('Device vendor')), $containerName, false, '');
                        $lookupVendorLink .= wf_tag('span', false, '', 'id="' . $containerName . '"') . '' . wf_tag('span', true);
                        $cells .= wf_TableCell($lookupVendorLink, '350');
                    }
                    $rows .= wf_TableRow($cells, 'row3');
                }
            }
        }
    }

    $result .= wf_TableBody($rows, '100%', '0', 'sortable');


    return($result);
}

/**
 * Checks is MAC unused by someone?
 * 
 * @param string $mac
 * 
 * @return bool 
 */
function multinet_mac_free($mac) {
    $query = "SELECT `id` from `nethosts` WHERE `mac`='" . $mac . "'";
    $res = simple_query($query);
    if (!empty($res)) {
        return(false);
    } else {
        return(true);
    }
}

/**
 * Returns all used MAC addresses from database
 * 
 * @return array
 */
function zb_getAllUsedMac() {
    $query = "SELECT `ip`,`mac` from `nethosts`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[strtolower($each['mac'])] = $each['ip'];
        }
    }
    return ($result);
}

/**
 * Checks is MAC unused by full list of used MACs
 * 
 * @param string $mac
 * @param array $allused
 * 
 * @return bool
 */
function zb_checkMacFree($mac, $allused) {
    $mac = strtolower($mac);
    if (isset($allused[$mac])) {
        return (false);
    } else {
        return (true);
    }
}

/**
 * Check mac for valid format
 * 
 * @param string $mac
 * 
 * @return bool
 */
function check_mac_format($mac) {
    $mask = '/^[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}$/i';
//really shitty mac
    if ($mac == '00:00:00:00:00:00') {
        return (false);
    }

    if (preg_match($mask, $mac)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Returns list of all network hosts networks as IPs => netid
 * 
 * @return array
 */
function zb_UserGetNetidsAll() {
    $query = "SELECT * from `nethosts`";
    $result = array();
    $allhosts = simple_queryall($query);
    if (!empty($allhosts)) {
        foreach ($allhosts as $io => $eachhost) {
            $result[$eachhost['ip']] = $eachhost['netid'];
        }
    }
    return ($result);
}

/**
 * Sends SIGHUP signal to stargazer
 * 
 * @return void
 */
function zb_StargazerSIGHUP() {
    $ub_conf = rcms_parse_ini_file(CONFIG_PATH . "billing.ini");
    if ($ub_conf['STGNASHUP']) {
        $sig_command = $ub_conf['SUDO'] . ' ' . $ub_conf['KILL'] . ' -1' . ' `' . $ub_conf['CAT'] . ' ' . $ub_conf['STGPID'] . '`';
        shell_exec($sig_command);
        log_register("SIGHUP STG");
    }
}

/**
 * Extracts IP address from string
 * 
 * @param string $data
 * 
 * @return string
 */
function zb_ExtractIpAddress($data) {
    preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $data, $matches);

    if (!empty($matches[0])) {
        return ($matches[0]);
    } else {
        return (false);
    }
}

/**
 * Extracts MAC address from string
 * 
 * @param string $data
 * 
 * @return string
 */
function zb_ExtractMacAddress($data) {
    $result = '';
    preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $data, $matches);
    if (!empty($matches)) {
        $result = $matches[0];
    }
    return ($result);
}

/**
 * Converts IP to integer value
 * 
 * @param string $src
 * 
 * @return int
 */
function ip2int($src) {
    $t = explode('.', $src);
    return count($t) != 4 ? 0 : 256 * (256 * ((float) $t[0] * 256 + (float) $t[1]) + (float) $t[2]) + (float) $t[3];
}

/**
 * Converts integer into IP
 * 
 * @param int $src
 * 
 * @return string
 */
function int2ip($src) {
    $s1 = (int) ($src / 256);
    $i1 = $src - 256 * $s1;
    $src = (int) ($s1 / 256);
    $i2 = $s1 - 256 * $src;
    $s1 = (int) ($src / 256);
    return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

/**
 * Removes some separator from MAC address
 * 
 * @param string $mac
 * @param string $separator
 * 
 * @return string
 */
function RemoveMacAddressSeparator($mac, $separator = array(':', '-', '.')) {
    return str_replace($separator, '', $mac);
}

/**
 * Adds some MAC separator into MAC
 * 
 * @param string $mac
 * @param string $separator
 * 
 * @return string
 */
function AddMacSeparator($mac, $separator = ':') {
    return join($separator, str_split($mac, 2));
}

/**
 * Yet another MAC format validator. Use check_mac_format() in real life.
 * 
 * @param string $mac
 * 
 * @return bool
 */
function IsMacValid($mac) {
    return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac) == 1);
}

/**
 * And Another MAC format validator. I rly dont know what for. Use check_mac_format() in real life. 
 * 
 * @param string $mac
 * 
 * @return bool
 */
function IsMacAddressValid($mac) {
    $validator = new Zend_Validate_Regex('/([a-fA-F0-9]{2}[:|\-]?){6}/');
    return $validator->isValid($mac);
}

/**
 * Gets Zabbix graphs data for NASes from cache
 *
 * @return string
 */
function getCachedZabbixNASGraphIDs() {
    global $ubillingConfig;
    $result = '';
    $cache = new UbillingCache();
    $cacheTime = ($ubillingConfig->getAlterParam('ZABBIX_GRAPHSIDS_CACHE_LIFETIME')) ? $ubillingConfig->getAlterParam('ZABBIX_GRAPHSIDS_CACHE_LIFETIME') : 1800;
    $result = $cache->getCallback('ZABBIX_GRAPHS_IDS', function () {
        return (getZabbixNASGraphIDs());
    }, $cacheTime
    );

    return ($result);
}

/**
 * Gets Zabbix graphs data for NASes
 *
 * @return array
 */
function getZabbixNASGraphIDs() {
    $zbx = new ZabbixAPI();
    $allNAS = zb_NasGetAllData();
    $allNASGraphs = array();
    $zbxAuthToken = $zbx->getAuthToken();

    if (!empty($allNAS) and ! empty($zbxAuthToken)) {
        foreach ($allNAS as $eachNAS) {
            $reqParams = array('filter' => array('ip' => $eachNAS['nasip']));
            $zbxNASData = json_decode($zbx->runQuery('host.get', $reqParams), true);

            if (!empty($zbxNASData['result'])) {
                $zbxNASHostID = $zbxNASData['result'][0]['hostid'];

                $reqParams = array('filter' => array('hostid' => $zbxNASHostID));
                $zbxNASGraphs = json_decode($zbx->runQuery('graph.get', $reqParams), true);

                if (!empty($zbxNASGraphs['result'])) {
                    $allNASGraphs[$eachNAS['nasip']] = $zbxNASGraphs['result'];
                }
            }
        }
    }

    return ($allNASGraphs);
}

/**
 * Generates array with links to user's traffic graphs
 *
 * @param $userIP
 * @param $fieldToSearch
 * @param $dataToSearch
 * @param array $zbxAllGraphs
 * @return array
 */
function getZabbixUserGraphLinks($userIP, $fieldToSearch, $dataToSearch, $zbxAllGraphs = array(), $zbxExtended = false) {
    if (empty($zbxAllGraphs)) {
        $allGraphs = getCachedZabbixNASGraphIDs();
    } else {
        $allGraphs = $zbxAllGraphs;
    }

    $bandwidthd_url = rtrim(zb_BandwidthdGetUrl($userIP), '/') . '/';
    $netid = zb_NetworkGetByIp($userIP);
    $nasid = zb_NasGetByNet($netid);
    $nasdata = zb_NasGetData($nasid);
    $graphURL = array();
    $graphID = '';

    if (!empty($allGraphs) and isset($allGraphs[$nasdata['nasip']])) {
        $allNASGraphs = $allGraphs[$nasdata['nasip']];

        foreach ($allNASGraphs as $eachGraph) {
            $searchStr = $eachGraph[$fieldToSearch];

            if (stripos($searchStr, $dataToSearch) !== false) {
                $graphID = $eachGraph['graphid'];
                break;
            }
        }
    }

    $graphURL['dayr'] = $bandwidthd_url . 'chart2.php?graphid=' . $graphID . '&period=86400';
    $graphURL['days'] = null;
    $graphURL['weekr'] = $bandwidthd_url . 'chart2.php?graphid=' . $graphID . '&period=604800';
    $graphURL['weeks'] = null;
    $graphURL['monthr'] = $bandwidthd_url . 'chart2.php?graphid=' . $graphID . '&period=2592000';
    $graphURL['months'] = null;
    $graphURL['yearr'] = $bandwidthd_url . 'chart2.php?graphid=' . $graphID . '&period=31536000';
    $graphURL['years'] = null;
    $graphURL['5mins'] = $bandwidthd_url . 'chart2.php?graphid=' . $graphID . '&period=300';
    $graphURL['zbxlink'] = $bandwidthd_url . 'charts.php?graphid=' . $graphID . '&fullscreen=0';
    $graphURL['zbxexten'] = $zbxExtended;

    return ($graphURL);
}

?>

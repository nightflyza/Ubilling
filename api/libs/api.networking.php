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
            $allServices[$each['netid']]['desc'] = $each['desc'];
            $allServices[$each['netid']]['servid'] = $each['id'];
        }
    }

    if (!empty($netsTmp)) {
        foreach ($netsTmp as $io => $each) {
            $allNets[$each['id']]['desc'] = $each['desc'];
            $allNets[$each['id']]['total'] = multinet_totalips_count($each['startip'], $each['endip']);
            //finding used hosts count
            if (isset($nethostsUsed[$each['id']])) {
                $allNets[$each['id']]['used'] = $nethostsUsed[$each['id']];
            } else {
                $allNets[$each['id']]['used'] = 0;
            }
            //finding network associated service
            if (isset($allServices[$each['id']])) {
                $allNets[$each['id']]['service'] = $allServices[$each['id']]['desc'];
                $allNets[$each['id']]['serviceid'] = $allServices[$each['id']]['servid'];
            } else {
                $allNets[$each['id']]['service'] = '';
                $allNets[$each['id']]['serviceid'] = '';
            }
            //free IPs counter
            $allNets[$each['id']]['free'] = $allNets[$each['id']]['total'] - $allNets[$each['id']]['used'];
        }
    }

    return ($allNets);
}

/**
 * Fast count of possible pool size for some IPs range
 * 
 * @param string $first_ip
 * @param string $last_ip
 * 
 * @return int
 */
function multinet_totalips_count($first_ip, $last_ip) {
    $first = ip2int($first_ip);
    $last = ip2int($last_ip);
    $result = 0;
    for ($i = $first; $i <= $last; $i++) {
        $curIpOffset = int2ip($i);
        if (!preg_match("#\.(0|1|255)$#", $curIpOffset)) {
            $result++;
        }
    }
    return ($result);
}

/**
 * Renders IP usage stats in existing networks. Reacts to allnets GET parameter.
 * 
 * @global object $ubillingConfig
 * @global object $branchControl
 * 
 * @return string
 */
function web_FreeIpStats() {
    global $ubillingConfig;
    $result = '';
    $data = multinet_getFreeIpStats();

    //checking service filters
    if (wf_CheckGet(array('allnets'))) {
        $servFlag = false;
    } else {
        $servFlag = true;
    }

    // branches support
    if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
        global $branchControl;
        $branchControl->loadServices();
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

            //branch resctrictions control
            if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
                if ($branchControl->isMyService($each['serviceid'])) {
                    $appendResult = true;
                } else {
                    $appendResult = false;
                }
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
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');
    if (!empty($networks)) {
        foreach ($networks as $network) {
            $cells = wf_TableCell($network['id']);
            $cells .= wf_TableCell($network['startip']);
            $cells .= wf_TableCell($network['endip']);
            $cells .= wf_TableCell($network['desc']);
            $cells .= wf_TableCell($network['nettype']);
            $actions = wf_JSAlert('?module=multinet&deletenet=' . $network['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $actions .= wf_JSAlert('?module=multinet&editnet=' . $network['id'], web_edit_icon(), 'Are you serious');
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

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('netedit', 'true');
    $inputs .= wf_TextInput('editstartip', __('First IP') . $sup, $netdata['startip'], true, '20', 'ip');
    $inputs .= wf_TextInput('editendip', __('Last IP') . $sup, $netdata['endip'], true, '20', 'ip');
    $inputs .= multinet_nettype_selector($netdata['nettype']) . ' ' . __('Network type') . wf_tag('br');
    $inputs .= wf_TextInput('editdesc', __('Network/CIDR') . $sup, $netdata['desc'], true, '20', 'net-cidr');
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
    log_register("MULTINET DELETE HOST `" . $ip . "`");
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
    return ($result);
}

/**
 * Returns unprocessed array of available networks with their data as netid=>netdata
 * 
 * @return array
 */
function multinet_get_all_networks_assoc() {
    $result = array();
    $query = "SELECT * from `networks`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns selector of available networks types
 * 
 * @param string $curnettype
 * 
 * @return string
 */
function multinet_nettype_selector($curnettype = '') {
    global $ubillingConfig;
    $dhcpFlag = $ubillingConfig->getAlterParam('DHCP_ENABLED');
    $opt82Flag = $ubillingConfig->getAlterParam('OPT82_ENABLED');
    $pppFlag = $ubillingConfig->getAlterParam('PPP_ENABLED');

    $params = array();
    if ($dhcpFlag) {
        $params += array(
            'dhcpstatic' => 'DHCP static hosts',
            'dhcpdynamic' => 'DHCP dynamic hosts',
        );
    }

    if ($opt82Flag) {
        $params += array(
            'dhcp82' => 'DHCP option 82',
            'dhcp82_vpu' => 'DHCP option 82 + vlan per user',
            'dhcp82_bdcom' => 'DHCP option 82 + mac onu (BDCOM)',
            'dhcp82_zte' => 'DHCP option 82 + mac onu (ZTE)',
        );
    }

    if ($pppFlag) {
        $params += array(
            'pppstatic' => 'PPP static network',
            'pppdynamic' => 'PPP dynamic network',
        );
    }
    $params += array(
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

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_HiddenInput('addnet', 'true');
    $inputs .= wf_TextInput('firstip', __('First IP') . $sup, '', true, '20', 'ip');
    $inputs .= wf_TextInput('lastip', __('Last IP') . $sup, '', true, '20', 'ip');
    $inputs .= multinet_nettype_selector() . ' ' . __('Network type') . wf_tag('br');
    $inputs .= wf_TextInput('desc', __('Network/CIDR') . $sup, '', true, '20', 'net-cidr');
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    show_window(__('Add network'), $form);
}

/**
 * Renders available services list with some controls
 * 
 * @return void
 */
function multinet_show_available_services() {
    $allservices = multinet_get_services();
    $allNetworkParams = multinet_get_all_networks_assoc();

    $tablecells = wf_TableCell(__('ID'));
    $tablecells .= wf_TableCell(__('Network'));
    $tablecells .= wf_TableCell(__('Service name'));
    $tablecells .= wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');

    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            $netdesc = (empty($allNetworkParams[$eachservice['netid']]) ? __('Network does not exist anymore') : $allNetworkParams[$eachservice['netid']]['desc']);

            $tablecells = wf_TableCell($eachservice['id']);
            $tablecells .= wf_TableCell($netdesc);
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
    $allNetsStats = array();
    if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
        global $branchControl;
        $branchControl->loadServices();
    }

    $freeIpStatsFlag = $ubillingConfig->getAlterParam('USERREG_FREEIP_STATS');
    if ($freeIpStatsFlag == 2 or $freeIpStatsFlag == 3) {
        $allNetsStats = multinet_getFreeIpStats();
    }

    $allservices = multinet_get_services();

    if (!empty($allservices)) {
        foreach ($allservices as $io => $eachservice) {
            if ($ubillingConfig->getAlterParam('BRANCHES_ENABLED')) {
                if ($branchControl->isMyService($eachservice['id'])) {
                    $tmpArr[$eachservice['id']] = $eachservice['desc'];
                    //optional free IP stats for branch restricted users
                    if (isset($allNetsStats[$eachservice['netid']])) {
                        $tmpArr[$eachservice['id']] .= ' (' . __('Free') . ' ' . $allNetsStats[$eachservice['netid']]['free'] . ')';
                    }
                }
            } else {
                $tmpArr[$eachservice['id']] = $eachservice['desc'];
                //optional free IP stats for all nets
                if (isset($allNetsStats[$eachservice['netid']])) {
                    $tmpArr[$eachservice['id']] .= ' (' . __('Free') . ' ' . $allNetsStats[$eachservice['netid']]['free'] . ')';
                }
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
 * 
 * @return void
 */
function multinet_add_network($desc, $firstip, $lastip, $nettype) {
    $desc = mysql_real_escape_string($desc);
    $firstip = vf($firstip);
    $lastip = vf($lastip);
    $nettype = vf($nettype);
    $query = "INSERT INTO `networks` (`id`, `desc`, `startip`, `endip`, `nettype`, `use_radius` ) VALUES
              (NULL, '" . $desc . "', '" . $firstip . "', '" . $lastip . "', '" . $nettype . "', '0');";
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
    if (empty($result)) {
        $result = array();
    }
    return ($result);
}

/**
 * Returns array of existing network parameters
 * 
 * @param var $login
 * 
 * @return array
 */
function multinet_get_network_params_by_login($login) {
    $result = array();
    $query = 'SELECT `networks`.* FROM `users`
            INNER JOIN `nethosts` USING (`ip`)
            INNER JOIN `networks` ON  `nethosts`.`netid` = `networks`.`id`
            WHERE `login`="' . $login . '"';
    $result = simple_query($query);

    return ($result);
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
    return ($result);
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
    return ($alldhcps);
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
    return ($result);
}

/**
 * Returns all dhcp handlers data as netid=>dhcpdata
 * 
 * @return array
 */
function dhcp_get_all_data_assoc() {
    $result = array();
    $query = "SELECT * from `dhcp`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['netid']] = $each;
        }
    }
    return ($result);
}

/**
 * Rebuilds dhcp subnet config with some static hosts
 * 
 * @param int $netid
 * @param string $confname
 * @param bool $ddns
 * @param array $loginIps
 * @param array $allNetHosts
 * 
 * @return void
 */
function handle_dhcp_rebuild_static($netid, $confname, $ddns = false, $loginIps = array(), $allNetHosts = array()) {
    $query = "SELECT * from `nethosts` WHERE `netid`='" . $netid . "'";

    // check haz it .conf name or not?
    if (!empty($confname)) {
        $allhosts = array();
        if (!empty($allNetHosts)) {
            foreach ($allNetHosts as $io => $each) {
                if ($each['netid'] == $netid) {
                    $allhosts[] = $each;
                }
            }
        }
        $confpath = 'multinet/' . $confname;

        $result = '';
        if (!empty($allhosts)) {
            foreach ($allhosts as $io => $eachhost) {
                //default IP based hosts
                if (!$ddns) {
                    $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                } else {
                    if (isset($loginIps[$eachhost['ip']])) {
                        $dhcphostname = $loginIps[$eachhost['ip']];
                    } else {
                        $dhcphostname = 'unknown' . zb_rand_string(8);
                    }
                }

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
    $query = "SELECT `login`,`users`.`ip` as `ip`,`option`,`port`,`switches`.`ip` AS `swip`,`swid`
              FROM `users` 
              INNER JOIN `nethosts` USING (ip)
              LEFT JOIN (SELECT * FROM `switchportassign`) as switchportassign USING (login)
              LEFT JOIN `switches` ON (`switchportassign`.switchid=`switches`.`id`)
              WHERE `netid` = '" . $netid . "'";
    if (!empty($confname)) {
        $confpath = 'multinet/' . $confname;
        $allhosts = simple_queryall($query);
        $result = '';
        if (!empty($allhosts)) {
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
            foreach ($allhosts as $io => $eachhost) {
                $parseTemplate = $customTemplate;
                $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                $options = explode('|', $eachhost['option']);

                if (isset($options[1])) {
                    $parseTemplate = str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
                    $parseTemplate = str_ireplace('{REMOTEID}', $options[0], $parseTemplate);
                    $parseTemplate = str_ireplace('{CIRCUITID}', $options[1], $parseTemplate);
                    $parseTemplate = str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
                    $parseTemplate = str_ireplace('{SWITCHIP}', $eachhost['swip'], $parseTemplate);
                    $parseTemplate = str_ireplace('{SWITCHMAC}', $eachhost['swid'], $parseTemplate);
                    $parseTemplate = str_ireplace('{SWITCHPORT}', $eachhost['port'], $parseTemplate);
                    $result .= $parseTemplate;
                } else {
                    if (preg_match('/{SWITCHIP}|{SWITCHMAC}|{PORT}/', $customTemplate)) {
                        $parseTemplate = str_ireplace('{HOSTNAME}', $dhcphostname, $parseTemplate);
                        $parseTemplate = str_ireplace('{IP}', $eachhost['ip'], $parseTemplate);
                        $parseTemplate = str_ireplace('{SWITCHIP}', $eachhost['swip'], $parseTemplate);
                        $parseTemplate = str_ireplace('{SWITCHMAC}', $eachhost['swid'], $parseTemplate);
                        $parseTemplate = str_ireplace('{SWITCHPORT}', $eachhost['port'], $parseTemplate);
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
            $customTemplate = file_get_contents(CONFIG_PATH . "dhcp/option82_vpu.template");
            if (empty($customTemplate)) {
                $customTemplate = '
class "{HOSTNAME}" { match if binary-to-ascii (16, 8, "", option agent.remote-id) = "{REMOTEID}" and binary-to-ascii(10, 16, "", substring(option agent.circuit-id,2,2)) = "{CIRCUITID}"; }
pool {
range {IP};
allow members of "{HOSTNAME}";
}
' . "\n";
            }
            foreach ($allhosts as $io => $eachhost) {
                $login = $allIps[$eachhost['ip']];
                if (isset($allVlans[$login])) {
                    //$netid         = GetNetidByIp($eachhost['ip']);
                    $remote = GetTermRemoteByNetid($netid);
                    $vlan = $allVlans[$login];
                    $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);
                    if (!empty($vlan)) {
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
            foreach ($allhosts as $io => $eachhost) {
                $login = $allIps[$eachhost['ip']];
                $mac = '';
                if (isset($allOnu[$login]) and !empty($allOnu[$login])) {
                    $macFull = explode(":", $allOnu[$login]['mac']);
                    foreach ($macFull as $eachOctet) {
                        $validOctet = preg_replace('/^0/', '', $eachOctet);
                        $mac .= $validOctet . ':';
                    }
                    $mac_len = strlen($mac);
                    $mac = substr($mac, 0, $mac_len - 1);
                    $dhcphostname = 'm' . str_replace('.', 'x', $eachhost['ip']);

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
            foreach ($allhosts as $io => $eachhost) {
                $login = $allIps[$eachhost['ip']];
                $onuId = '';
                $onuIdentifier = '';
                if (isset($allOnu[$login]) and !empty($allOnu[$login])) {
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
    return ($templatebody);
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
        return ("0.0.0.0");
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
    $allNetsData = multinet_get_all_networks_assoc();
    $alldhcpsubnets = dhcp_get_all_data_assoc();

    $allMembers_q = "SELECT `ip` from `nethosts` WHERE `option` != 'NULL'";
    $allMembers = simple_queryall($allMembers_q);
    $membersMacroContent = '';
    $vlanMembersMacroContent = '';
    $onuMembersMacroContent = '';
    $subnets = '';

    if (!empty($allMembers)) {
        foreach ($allMembers as $ix => $eachMember) {
            $memberClass = 'm' . str_replace('.', 'x', $eachMember['ip']);;
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


    if (!empty($alldhcpsubnets)) {
        foreach ($alldhcpsubnets as $io => $eachnet) {
            //network really exists?
            if (isset($allNetsData[$eachnet['netid']])) {
                $netdata = $allNetsData[$eachnet['netid']];
                $templatedata['{STARTIP}'] = $netdata['startip'];
                $templatedata['{ENDIP}'] = $netdata['endip'];
                $templatedata['{CIDR}'] = explode('/', $netdata['desc']);
                $templatedata['{NETWORK}'] = $templatedata['{CIDR}'][0];
                $templatedata['{CIDR}'] = $templatedata['{CIDR}'][1];
                $templatedata['{ROUTERS}'] = int2ip(ip2int($templatedata['{STARTIP}']) + 1);
                $templatedata['{MASK}'] = multinet_cidr2mask($templatedata['{CIDR}']);
                $dhcpdata = $alldhcpsubnets[$eachnet['netid']];
                if (isset($dhcpdata['confname'])) {
                    $templatedata['{HOSTS}'] = $dhcpdata['confname'];
                    // check for override?
                    if (!empty($dhcpdata['dhcpconfig'])) {
                        $currentsubtpl = $dhcpdata['dhcpconfig'];
                    } else {
                        $currentsubtpl = $subnets_template;
                    }
                    $subnets .= multinet_ParseTemplate($currentsubtpl, $templatedata) . "\n";
                }
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

/**
 * Returns array of all available nethosts as id=>nethostdata
 * 
 * @return array
 */
function multinet_nethosts_get_all() {
    $result = array();
    $query = "SELECT * from `nethosts`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Performs rebuild of all networks handlers due their type
 * 
 * @return void
 */
function multinet_rebuild_all_handlers() {
    global $ubillingConfig;
    $dhcpEnabledFlag = $ubillingConfig->getAlterParam('DHCP_ENABLED');
    $opt82EnabledFlag = $ubillingConfig->getAlterParam('OPT82_ENABLED');
    $pppEnabledFlag = $ubillingConfig->getAlterParam('PPP_ENABLED');
    $ddnsFlag = $ubillingConfig->getAlterParam('DHCP_DDNS_ENABLED');

    if ($ddnsFlag) {
        $loginIps = zb_UserGetAllIPs();
        $loginIps = array_flip($loginIps); //IP=>login
        $useDdns = true;
    } else {
        $loginIps = array();
        $useDdns = false;
    }

    $allnets = multinet_get_all_networks_assoc(); //all networks basic params
    $allDhcpData = dhcp_get_all_data_assoc(); //all networks dhcpd data
    $allNethosts = multinet_nethosts_get_all(); //all available user nethosts data

    if (!empty($allnets)) {
        foreach ($allnets as $io => $eachnet) {
            switch ($eachnet['nettype']) {
                case 'dhcpstatic':
                    if ($dhcpEnabledFlag) {
                        if (isset($allDhcpData[$eachnet['id']])) {
                            $dhcpdata = $allDhcpData[$eachnet['id']];
                            handle_dhcp_rebuild_static($eachnet['id'], @$dhcpdata['confname'], $useDdns, $loginIps, $allNethosts);
                        }
                    }
                    break;

                case 'dhcp82':
                    if ($opt82EnabledFlag) {
                        if (isset($allDhcpData[$eachnet['id']])) {
                            $dhcpdata82 = $allDhcpData[$eachnet['id']];
                            handle_dhcp_rebuild_option82($eachnet['id'], $dhcpdata82['confname']);
                        }
                    }
                    break;

                case 'dhcp82_vpu':
                    if ($opt82EnabledFlag) {
                        if (isset($allDhcpData[$eachnet['id']])) {
                            $dhcpdata82_vpu = $allDhcpData[$eachnet['id']];
                            handle_dhcp_rebuild_option82_vpu($eachnet['id'], $dhcpdata82_vpu['confname']);
                        }
                    }
                    break;

                case 'dhcp82_bdcom':
                    if ($opt82EnabledFlag) {
                        if (isset($allDhcpData[$eachnet['id']])) {
                            $dhcpdata82_bdcom = $allDhcpData[$eachnet['id']];
                            handle_dhcp_rebuild_option82_bdcom($eachnet['id'], $dhcpdata82_bdcom['confname']);
                        }
                    }
                    break;

                case 'dhcp82_zte':
                    if ($opt82EnabledFlag) {
                        if (isset($allDhcpData[$eachnet['id']])) {
                            $dhcpdata82_zte = $allDhcpData[$eachnet['id']];
                            handle_dhcp_rebuild_option82_zte($eachnet['id'], $dhcpdata82_zte['confname']);
                        }
                    }
                    break;

                case 'pppstatic':
                    if ($pppEnabledFlag) {
                        handle_ppp_rebuild_static($eachnet['id']);
                    }
                    break;

                case 'pppdynamic':
                    if ($pppEnabledFlag) {
                        handle_ppp_rebuild_dynamic($eachnet['id']);
                    }
                    break;
            }
        }
    }

    if ($dhcpEnabledFlag or $opt82EnabledFlag) {
        //rebuilding global conf 
        multinet_rebuild_globalconf();
        //restarting dhcpd
        multinet_RestartDhcp();
    }
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
    log_register("MULTINET CREATE HOST  `" . $ip . '`');
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
    log_register("MULTINET CHANGE HOST `" . $ip . "` MAC `" . $newmac . "`");
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
    $filterednet = array();
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
    return ($filterednet);
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
    $clear_ips = array();
    $free_ip_pool = array();
    $network_spec = multinet_get_network_params($network_id);
    if (!empty($network_spec)) {
        $first_ip = $network_spec['startip'];
        $last_ip = $network_spec['endip'];
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
    }
    return ($free_ip_pool);
}

/**
 * Counts free IPs fast for certain network
 * 
 * @param int $network_id
 * 
 * @return int
 */
function multinet_get_free_count($network_id) {
    $network_spec = multinet_get_network_params($network_id);
    $desc = $network_spec['desc'];
    $desc_parts = explode("/", $desc);
    $network = $desc_parts[0];
    $cidr = $desc_parts[1];
    $first_ip = ip2long($network_spec['startip']);
    $last_ip = ip2long($network_spec['endip']);
    $count_all = $last_ip - $first_ip;
    $num_hosts = pow(2, 32 - $cidr);

    if ($count_all >= $num_hosts - (3 * ceil($num_hosts / 256))) {
        $count_all = $num_hosts - (3 * ceil($num_hosts / 256));
    }

    $query = "SELECT COUNT(*) as used FROM `nethosts` WHERE `netid`='" . $network_id . "'";
    $count_result = simple_query($query);
    $count_used = $count_result['used'];
    $free_count = $count_all - $count_used;
    return ($free_count);
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
    return (@$all_free_ips[$temp[0]]);
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
    if (!empty($service_network)) {
        $service_network = $service_network['netid'];
    } else {
        $service_network = 0;
    }
    return ($service_network);
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
 * @param int $fs
 * @param string $traffsize
 * 
 * @return string
 */
function zb_convertSize($fs, $traffsize = 'float') {
    if ($traffsize == 'float') {
        if ($fs >= (1073741824 * 1024))
            $fs = round($fs / (1073741824 * 1024) * 100) / 100 . ' ' . __('Tb');
        elseif ($fs >= 1073741824)
            $fs = round($fs / 1073741824 * 100) / 100 . ' ' . __('Gb');
        elseif ($fs >= 1048576)
            $fs = round($fs / 1048576 * 100) / 100 . ' ' . __('Mb');
        elseif ($fs >= 1024)
            $fs = round($fs / 1024 * 100) / 100 . ' ' . __('Kb');
        else
            $fs = $fs . ' ' . __('b');
        return ($fs);
    }

    if ($traffsize == 'b') {
        return ($fs);
    }

    if ($traffsize == 'Kb') {
        $fs = round($fs / 1024 * 100) / 100 . ' ' . __('Kb');
        return ($fs);
    }

    if ($traffsize == 'Mb') {
        $fs = round($fs / 1048576 * 100) / 100 . ' ' . __('Mb');
        return ($fs);
    }
    if ($traffsize == 'Gb') {
        $fs = round($fs / 1073741824 * 100) / 100 . ' ' . __('Gb');
        return ($fs);
    }

    if ($traffsize == 'Tb') {
        $fs = round($fs / (1073741824 * 1024) * 100) / 100 . ' ' . __('Tb');
        return ($fs);
    }
}

/**
 * Converts bytes into human-readable values like Kb, Mb, Gb, configurable via TRAFFSIZE alter option.
 * 
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
    return (zb_convertSize($fs, $traffsize));
}

/**
 * Convert bytes to human-readable Gb values. Much faster than stg_convert_size()/zb_convertSize
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
    return ($result);
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
    if ($speeddown == '') {
        $speeddown = 0;
    }
    if ($speedup == '') {
        $speedup = 0;
    }
    $burstdownload = vf($burstdownload);
    $burstupload = vf($burstupload);
    $bursttimedownload = vf($bursttimedownload);
    $burstimetupload = vf($burstimetupload);
    $query = "INSERT INTO `speeds` (`id` , `tariff` , `speeddown` , `speedup` , `burstdownload` , `burstupload` , `bursttimedownload` , `burstimetupload`) VALUES
    (NULL , '" . $tariff . "', '" . $speeddown . "', '" . $speedup . "', '" . $burstdownload . "', '" . $burstupload . "', '" . $bursttimedownload . "', '" . $burstimetupload . "');";
    nr_query($query);
    log_register('TARIFF CREATE `' . $tariff . '` SPEEDDOWN `' . $speeddown . '` SPEEDUP `' . $speedup . '`');
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
    return ($result);
}

/**
 * Returns full nethost data by its IP
 * 
 * @param string $ip
 * 
 * @return array
 */
function zb_MultinetGetNethostData($ip) {
    $query = "SELECT * from `nethosts` WHERE `ip`='" . $ip . "'";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns user IP address by its login
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
 * Returns user login by it's IP address
 *
 * @param $ip
 *
 * @return array|string
 */
function zb_UserGetLoginByIp($ip) {
    $result = '';

    if (!empty($ip)) {
        $query = "SELECT `login` from `users` where `IP`='" . $ip . "'";
        $queryResult = simple_query($query);

        if (!empty($queryResult['login'])) {
            $result = $queryResult['login'];
        }
    }

    return ($result);
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
    return ($data);
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
    $nasname = trim($nasname);
    $nastype = vf($nastype);
    $bandw = trim($bandw);
    $bandw = mysql_real_escape_string($bandw);
    $nasip = mysql_real_escape_string($nasip);
    $query = "INSERT INTO `nas` (`id` ,`netid` , `nasip` , `nasname` , `nastype` , `bandw`) VALUES
              (NULL , '" . $netid . "', '" . $nasip . "', '" . $nasname . "',  '" . $nastype . "', '" . $bandw . "' );";
    nr_query($query);
    $newId = simple_get_lastid('nas');
    log_register('NAS ADD [' . $newId . '] `' . $nasip . '`');
}

/**
 * Updates existing NAS parameters in database
 * 
 * @param int $nasid
 * @param string $nastype
 * @param string $nasip
 * @param string $nasname
 * @param string $nasbwdurl
 * @param int $netid
 * 
 * @return void
 */
function zb_NasUpdateParams($nasid, $nastype, $nasip, $nasname, $nasbwdurl, $netid) {
    $nasid = ubRouting::filters($nasid, 'int');
    $nastype = ubRouting::filters($nastype, 'mres');
    $nasip = ubRouting::filters($nasip, 'mres');
    $nasname = ubRouting::filters($nasname, 'mres');
    $nasname = trim($nasname);
    $nasbwdurl = trim(ubRouting::filters($nasbwdurl, 'mres'));
    $netid = ubRouting::filters($netid, 'int');

    $targetnas = "WHERE `id` = '" . $nasid . "'";
    simple_update_field('nas', 'nastype', $nastype, $targetnas);
    simple_update_field('nas', 'nasip', $nasip, $targetnas);
    simple_update_field('nas', 'nasname', $nasname, $targetnas);
    simple_update_field('nas', 'bandw', $nasbwdurl, $targetnas);
    simple_update_field('nas', 'netid', $netid, $targetnas);
    log_register('NAS EDIT [' . $nasid . '] `' . $nasip . '`');
}

/**
 * Returns all available NAS data
 * 
 * @return array
 */
function zb_NasGetAllData() {
    $query = "SELECT * from `nas`";
    $allnas = simple_queryall($query);
    return ($allnas);
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
    return ($result);
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
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['DHCP_ENABLED']) {
        $config = $ubillingConfig->getBilling();
        $sudo = $config['SUDO'];
        $dhcpd = $config['RC_DHCPD'];
        $command = $sudo . ' ' . $dhcpd . ' restart';
        shell_exec($command);
        log_register('DHCPD RESTART');
    }
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
    return ($nasid);
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

    return ($result);
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
 * Parses MAC addresses from a given source path and returns they as unique MAC array idx=>MAC
 *
 * @param string $source The path to the source file to read from.
 * @param int $lines The number of lines to read from the source file.
 * @param string $customLeaseMark An optional custom lease mark to use for filtering lines.
 * 
 * @return array
 */
function zb_MacParseSource($source, $lines = 200, $customLeaseMark = '') {
    global $ubillingConfig;
    $result = array();
    $allMacs = array();
    $source = trim($source);
    $extendFlag = ($ubillingConfig->getAlterParam('NMLEASES_EXTEND')) ? true : false;
    $leasemark = $ubillingConfig->getAlterParam('NMLEASEMARK');

    if ($customLeaseMark) {
        $leasemark = $customLeaseMark;
    }

    $billCfg = $ubillingConfig->getBilling();
    $sudo = $billCfg['SUDO'];
    $cat = $billCfg['CAT'];
    $grep = $billCfg['GREP'];
    $tail = $billCfg['TAIL'];

    $filter = '';
    if (!empty($leasemark)) {
        $filter .= ' | ' . $grep . ' "' . $leasemark . '" ';
    }

    $command = $sudo . ' ' . $cat . ' ' . $source . $filter . ' | ' . $tail . ' -n ' . $lines;
    $rawdata = shell_exec($command);

    // Aandene har begynt aa vise seg for meg
    // Stryk katten mot haarene
    // Salt i saaret
    // Svette

    if (!empty($source)) {
        if (!empty($rawdata)) {
            $cleardata = exploderows($rawdata);
            foreach ($cleardata as $eachline) {
                preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
                if (!empty($matches)) {
                    $allMacs[] = $matches[0];
                }
                if ($extendFlag) {
                    $eachline = preg_replace('/([a-f0-9]{2})(?![\s\]\/])([\.\:\-]?)/', '\1:', $eachline);
                    preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
                    if (!empty($matches[0])) {
                        $allMacs[] = $matches[0];
                    }
                }
            }
            $result = array_unique($allMacs);
        }
    }
    return ($result);
}

/**
 * Returns new unknown MAC addresses list parsed from NMLEASES and NMSOURCES_ADDITIONAL sources.
 * 
 * @global object $ubillingConfig
 * 
 * @return string
 */
function zb_NewMacShow() {
    global $ubillingConfig;
    $result = '';
    $allUsedMacs = zb_getAllUsedMac();
    $allMacs = array();
    $unknownMacCount = 0;
    $lineLimit = ($ubillingConfig->getAlterParam('NMLOOKUP_DEPTH')) ? $ubillingConfig->getAlterParam('NMLOOKUP_DEPTH') : 200;
    $leases = $ubillingConfig->getAlterParam('NMLEASES');
    $additionalSources = $ubillingConfig->getAlterParam('NMSOURCES_ADDITIONAL');
    $reverseFlag = ($ubillingConfig->getAlterParam('NMREVERSE')) ? true : false;
    $macvenFlag = ($ubillingConfig->getAlterParam('MACVEN_ENABLED')) ? true : false;
    $additionalMark = ($ubillingConfig->getAlterParam('NMLEASEMARK_ADDITIONAL')) ? $ubillingConfig->getAlterParam('NMLEASEMARK_ADDITIONAL') : '';
    if ($macvenFlag) {
        $result .= wf_AjaxLoader();
        //additional macven rights check
        if (!cfr('MACVEN')) {
            $macvenFlag = false;
        }
    }

    //parsing new MAC sources
    if (!empty($leases)) {
        $allMacs += zb_MacParseSource($leases, $lineLimit);
    }

    //and optional additional sources
    if (!empty($additionalSources)) {
        $additionalSources = explode(',', $additionalSources);
        if (!empty($additionalSources)) {
            foreach ($additionalSources as $io => $eachAdditionalSource) {
                $supSourceMacs = zb_MacParseSource($eachAdditionalSource, $lineLimit, $additionalMark);
                $allMacs = array_merge($allMacs, $supSourceMacs);
            }
        }
    }

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
    if ($fdbColumn) {
        $cells .= wf_TableCell(__('Switch'));
    }

    if ($macvenFlag) {
        $cells .= wf_TableCell(__('Manufacturer'));
    }

    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allMacs)) {
        if ($reverseFlag) {
            //revert array due usability reasons 
            $allMacs = array_reverse($allMacs);
        }

        foreach ($allMacs as $io => $eachmac) {
            if (zb_checkMacFree($eachmac, $allUsedMacs)) {
                $cells = wf_TableCell(@$eachmac);
                if (!empty($fdbColumn)) {
                    $cells .= wf_TableCell(sn_SnmpParseFdbExtract(@$fdbArr[$eachmac]));
                }

                if ($macvenFlag) {
                    $containerName = 'NMRSMCNT_' . zb_rand_string(8);
                    $lookupVendorLink = wf_AjaxLink('?module=macvendor&mac=' . @$eachmac . '&raw=true', wf_img('skins/macven.gif', __('Device vendor')), $containerName, false, '');
                    $lookupVendorLink .= wf_tag('span', false, '', 'id="' . $containerName . '"') . '' . wf_tag('span', true);
                    $cells .= wf_TableCell($lookupVendorLink, '350');
                }
                $rows .= wf_TableRow($cells, 'row5');
                $unknownMacCount++;
            }
        }
    }

    if ($unknownMacCount > 0) {
        $result .= wf_TableBody($rows, '100%', '0', 'sortable');
    } else {
        $messages = new UbillingMessageHelper();
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'info');
        $result .= wf_delimiter();
    }

    return ($result);
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
        return (false);
    } else {
        return (true);
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
    $result = $cache->getCallback(
        'ZABBIX_GRAPHS_IDS',
        function () {
            return (getZabbixNASGraphIDs());
        },
        $cacheTime
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

    if (!empty($allNAS) and !empty($zbxAuthToken)) {
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

/**
 * Gets Zabbix problems and actions by problems
 *
 * @return array
 */
function getZabbixProblems($switchIP) {
    global $ubillingConfig;
    $zbx = new ZabbixAPI();
    $zbxAuthToken = $zbx->getAuthToken();
    $problemActions = array();
    $switchIP = trim($switchIP);

    if (!empty($switchIP) and !empty($zbxAuthToken)) {
        /* Selectd problem level severities
          Possible values:
          0 - not classified;
          1 - informational;
          2 - warning;
          3 - medium;
          4 - high;
          5 - emergency.
         */
        if ($ubillingConfig->getAlterParam('ZABBIX_PROBLEM_SEVERITIES')) {
            $severities = explode(',', $ubillingConfig->getAlterParam('ZABBIX_PROBLEM_SEVERITIES'));
        } else {
            $severities = array('0', '1', '2', '3', '4', '5');
        }

        $reqParams = array('filter' => array('ip' => $switchIP));
        $zbxHostData = json_decode($zbx->runQuery('host.get', $reqParams), true);
        if (!empty($zbxHostData['result'])) {
            $zbxHostID = $zbxHostData['result'][0]['hostid'];
            $reqParams = array('hostids' => $zbxHostID, 'severities' => $severities, "selectAcknowledges" => "extend");
            $zbxProblemActions = json_decode($zbx->runQuery('problem.get', $reqParams), true);
            if (!empty($zbxProblemActions['result'])) {
                $problemActions = $zbxProblemActions['result'];
            }
        }
    }

    return ($problemActions);
}

/**
 * Converts string IPv4 address/netmask to a HEX representation:
 * 192.168.1.1 -> c0a80101
 * $upperCase       keep in mind it won't make HEX prefix to upper case, like '0x' -> '0X'
 * $dotSeparated    makes something like: 192.168.1.1 -> c0.a8.01.01
 * $hexPrefix       makes something like: 192.168.1.1 -> 0xc0a80101
 *
 * @param string $ip
 * @param bool $upperCase
 * @param bool $dotSeparated
 * @param bool $hexPrefix
 *
 * @return string
 */
function multinet_ip2hex($ip, $upperCase = false, $dotSeparated = false, $hexPrefix = false) {
    $hexIP = '';

    if (!empty($ip)) {
        if ($ip == '0.0.0.0' and $dotSeparated) {
            $hexIP = $ip;
        } else {
            $hexIP = dechex(ip2long($ip));

            if ($dotSeparated) {
                $hexIP = trim(chunk_split($hexIP, 2, '.'), '.');
            }

            if ($upperCase) {
                $hexIP = strtoupper($hexIP);
            }
        }

        if ($hexPrefix) {
            $hexIP = '0x' . $hexIP;
        }
    }

    return ($hexIP);
}

/**
 * Tries to get network CIDR from it's description for a given network ID
 *
 * @param $netID
 *
 * @return string
 */
function multinet_get_network_cidr_from_descr($netID) {
    $networkData = multinet_get_network_params($netID);
    $networkCIDR = (!empty($networkData['desc']) and ispos($networkData['desc'], '/')) ? substr($networkData['desc'], -2) : '';

    return ($networkCIDR);
}

/**
 * Simply gathers some network-essential info about user, like netID and NAS data
 *
 * @param $login
 *
 * @return array
 */
function getNASInfoByLogin($login) {
    $tQuery = "SELECT `login`, `ip`, `tNases`.`netid`, `tNases`.`nasip`, `tNases`.`nastype`, `tNases`.`options`
                    FROM `users`
                    LEFT JOIN
                      (SELECT `nethosts`.`netid`, `nethosts`.`ip`, `nas`.`netid` AS `nas_netid`, `nas`.`nasip`, `nas`.`nastype`, `nas`.`options`
                        FROM `nethosts`
                            LEFT JOIN `nas` ON `nas`.`netid` = `nethosts`.`netid`) AS tNases USING(`ip`)                
                    WHERE  `users`.`login` = '" . $login . "'";
    $tQueryResult = simple_query($tQuery);

    return ($tQueryResult);
}

/**
 * Converts MAC from it's DEC representation back to HEX, like
 * 32.87.175.9.99.125 => 20:57:AF:09:63:7D
 * or
 * 52:45:13:39:180:117 => 34:2D:0D:27:B4:75
 *
 * @param string $decMAC
 * @param string $inSeparator
 * @param string $outSeparator
 * @param false $reversed   - set to true if DEC MAC is reversed
 *
 * @return string
 */
function convertMACDec2Hex($decMAC, $inSeparator = '.', $outSeparator = ':', $reversed = false) {
    $hexMAC = '';

    if (!empty($decMAC)) {
        $decMACArr = explode($inSeparator, $decMAC);
        $decMACArr = ($reversed) ? array_reverse($decMACArr) : $decMACArr;

        foreach ($decMACArr as $decOctet) {
            $hexOctet = ($decOctet == '0' or $decOctet == 0) ? '00' : dechex($decOctet);

            if (strlen($hexOctet) < 2) {
                $hexOctet = '0' . $hexOctet;
            }

            $hexMAC .= $hexOctet;
        }

        $hexMAC = strtolower_utf8(AddMacSeparator($hexMAC, $outSeparator));
    }

    return ($hexMAC);
}

/**
 * Makes "our standard truncate" of the raw SNMPWalk output,
 * removing OID portion, OID value, leading and trailing dots and spaces
 * TIP: if you need to trim some $snmpData without OID portion already
 * - just set $oid parameter to an empty string
 *
 * @param string       $snmpData
 * @param string       $oid
 * @param string       $removeValue
 * @param bool         $rowsExplode
 * @param false        $returnAsStr
 *
 * @param array|string $oidValue
 *
 * @return array|string
 */
function trimSNMPOutput(
    $snmpData,
    $oid,
    $removeValue = '',
    $rowsExplode = false,
    $returnAsStr = false,
    $oidValue = array(
        'Counter32:',
        'Counter64:',
        'Gauge32:',
        'Gauge64:',
        'INTEGER:',
        'Hex-STRING:',
        'OID:',
        'Timeticks:',
        'STRING:',
        'Network Address:'
    )
) {
    $result = ($returnAsStr) ? '' : array('', '');

    if (!empty($snmpData)) {
        if (!is_array($oidValue)) {
            $oidValue = explode(',', $oidValue);
        }

        // removing OID portion
        $snmpData = str_replace($oid, '', $snmpData);
        // removing VALUE portion
        $snmpData = str_replace($oidValue, '', $snmpData);
        // removing some "specific" $removeValue
        $snmpData = str_replace($removeValue, '', $snmpData);

        if (!$returnAsStr) {
            if ($rowsExplode) {
                $snmpData = explodeRows($snmpData);
            } else {
                // trimming leading and trailing dots and spaces
                $snmpData = trim($snmpData, '. \n\r\t');
                $snmpData = explode('=', $snmpData);

                if (isset($snmpData[1])) {
                    // trimming possible extra spaces
                    $snmpData[0] = trim($snmpData[0]);
                    $snmpData[1] = trim($snmpData[1]);
                }
            }
        }

        $result = $snmpData;
    }

    return ($result);
}

/**
 * Returns array with range start and end IP from IP address with CIDR notation
 *
 * @param string $ipcidr
 * @param bool $excludeNetworkAddr
 * @param bool $excludeBroadcastAddr
 *
 * @return array startip/endip
 */
function ipcidrToStartEndIP($ipcidr, $excludeNetworkAddr = false, $excludeBroadcastAddr = false) {
    $range = array();
    $ipcidr = explode('/', $ipcidr);
    $startip = (ip2long($ipcidr[0])) & ((-1 << (32 - (int) $ipcidr[1])));
    $endip = $startip + pow(2, (32 - (int) $ipcidr[1])) - 1;
    $startip = ($excludeNetworkAddr ? $startip + 1 : $startip);
    $endip = ($excludeBroadcastAddr ? $endip - 1 : $endip);

    $range['startip'] = long2ip($startip);
    $range['endip'] = long2ip($endip);

    return ($range);
}

/**
 * Just returns random-generated MAC
 * 
 * @return string
 */
function zb_MacGetRandom() {
    $result = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
    return ($result);
}

/**
 * Checks have some IP valid format or not?
 * 
 * @param string $ip
 * 
 * @return bool
 */
function zb_isIPValid($ip) {
    $result = false;
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        $result = true;
    }
    return ($result);
}

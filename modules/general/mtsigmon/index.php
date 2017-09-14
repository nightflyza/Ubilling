<?php

if (cfr('MTSIGMON')) {

// Main code part

    $alter_config = $ubillingConfig->getAlter();
    if ($alter_config['MTSIGMON_ENABLED']) {
        $sigmon = new MTSIGMON();

        $allMonitoredDevices = $sigmon->getDevices();
        $allusermacs = zb_UserGetAllMACs();
        $alladdress = zb_AddressGetFullCityaddresslist();
        $alltariffs = zb_TariffsGetAllUsers();
        $allrealnames = zb_UserGetAllRealnames();
        $alluserips = zb_UserGetAllIPs();

        $result = '';
        $hlightmac = '';

        //hlight user mac sub
        if (isset($_GET['username'])) {
            $login = mysql_real_escape_string($_GET['username']);
            $userip = zb_UserGetIP($login);
            $usermac = zb_MultinetGetMAC($userip);
            $hlightmac = $usermac;
        }

        if (!empty($allMonitoredDevices)) {
            foreach ($allMonitoredDevices as $io => $eachdevice) {
                $userCounter = 0;
                $hostdata = $sigmon->deviceQuery($eachdevice['ip'], $eachdevice['community']);
                $result.= wf_tag('h2', false) . wf_img('skins/wifi.png') . ' ' . $eachdevice['location'] . ' - ' . $eachdevice['ip'] . wf_tag('h2', true);
                $tablecells = wf_TableCell(__('Full address'));
                $tablecells.= wf_TableCell(__('Real Name'));
                $tablecells.= wf_TableCell(__('Tariff'));
                $tablecells.= wf_TableCell(__('IP'));
                $tablecells.= wf_TableCell(__('MAC'));
                $tablecells.= wf_TableCell(__('Signal') . ' dBm');
                $tablerows = wf_TableRow($tablecells, 'row1');

                if (!empty($hostdata)) {
                    foreach ($hostdata as $eachmac => $eachsig) {
                        //signal coloring   
                        if ($eachsig < -79) {
                            $displaysig = wf_tag('font', false, '', 'color="#900000"') . $eachsig . wf_tag('font', true);
                        } elseif ($eachsig > -80 and $eachsig < -74) {
                            $displaysig = wf_tag('font', false, '', 'color="#FF5500"') . $eachsig . wf_tag('font', true);
                        } else {
                            $displaysig = wf_tag('font', false, '', 'color="#006600"') . $eachsig . wf_tag('font', true);
                        }

                        //user counter increment
                        $userCounter++;

                        //hightlighting user
                        if (!empty($hlightmac)) {
                            if ($hlightmac == $eachmac) {
                                $rowclass = 'siglight';
                            } else {
                                $rowclass = 'row3';
                            }
                        } else {
                            $rowclass = 'row3';
                        }

                        //extracting user profile link
                        if (array_search($eachmac, $allusermacs)) {
                            $backmaclogin = array_search($eachmac, $allusermacs);
                            @$backaddress = $alladdress[$backmaclogin];
                            $profilelink = wf_Link("?module=userprofile&username=" . $backmaclogin, web_profile_icon() . ' ' . $backaddress, false, '');
                            $realname = @$allrealnames[$backmaclogin];
                            $usertariff = @$alltariffs[$backmaclogin];
                            $userip = @$alluserips[$backmaclogin];
                        } else {
                            $profilelink = '';
                            $realname = '';
                            $usertariff = '';
                            $userip = '';
                        }

                        $tablecells = wf_TableCell($profilelink);
                        $tablecells.= wf_TableCell($realname);
                        $tablecells.= wf_TableCell($usertariff);
                        $tablecells.= wf_TableCell($userip);
                        $tablecells.= wf_TableCell($eachmac);
                        $tablecells.= wf_TableCell($displaysig);
                        $tablerows.= wf_TableRow($tablecells, $rowclass);
                    }

                    $result.= wf_TableBody($tablerows, '100%', '0', 'sortable');
                } else {
                    $result.= __('Empty reply received');
                }

                $result.=wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
                $result.=wf_tag('div', false, 'glamour') . __('Total') . ': ' . $userCounter . wf_tag('div', true);
                $result.=wf_tag('div', false, '', 'style="clear:both;"') . wf_tag('div', true);
                $result.=wf_delimiter();
            }
        } else {
            $result = __('No devices for signal monitoring found');
        }

        //if called as an user profile plugin
        if (isset($_GET['username'])) {
            $result.= wf_Link('?module=userprofile&username=' . $_GET['username'], __('Back'), true, 'ubButton');
        }

        //show final result
        show_window(__('Mikrotik signal monitor'), $result);
    } else {
        show_error(__('This module disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
<?php

if (cfr('OPTION82')) {
    $altercfg = $ubillingConfig->getAlter();
    if (@$altercfg['OPT82_ENABLED']) {
        $billing_config = $ubillingConfig->getBilling();

        /**
         * Parse option 82 leases from dhcpd.log
         * 
         * @param $billing_config - preprocessed billing.ini
         * @param $altercfg - preprocessed alter.ini
         * 
         * @return array
         */
        function opt82_LoadLeases($billing_config, $altercfg, $busyLeases) {
            $sudo = $billing_config['SUDO'];
            $cat = $billing_config['CAT'];
            $grep = $billing_config['GREP'];
            $tail = $billing_config['TAIL'];
            $leasePath = $altercfg['NMLEASES'];
            $leasemark = '(with opt82)';
            $command = $sudo . ' ' . $cat . ' ' . $leasePath . ' | ' . $grep . ' "' . $leasemark . '" | ' . $tail . ' -n 100';
            $rawData = shell_exec($command);
            $rawArr = array();
            $rawArr = explodeRows($rawData);
            $result = array();

            if (!empty($rawArr)) {
                foreach ($rawArr as $eachline) {
                    $explodeLine = preg_split('/\s+/', $eachline);
                    //log have normal format
                    if (isset($explodeLine[9]) AND ( isset($explodeLine[11])) AND ( isset($explodeLine[7]))) {
                        $leaseIp = $explodeLine[7];
                        $remoteId = $explodeLine[9];
                        $circuitID = $explodeLine[11];
                        //check for new lease?
                        if (!isset($busyLeases[$remoteId . '|' . $circuitID])) {
                            $result[$remoteId . '|' . $circuitID] = $remoteId . '->' . $circuitID;
                        }
                    }
                }
            }

            return ($result);
        }

        /**
         * get all busy option 82 leases
         * 
         * @return array - list of uses leases for isset check
         */
        function opt82_GetAllUsedLeases() {
            $query = "SELECT * from `nethosts` WHERE `option` IS NOT NULL";
            $allNethosts = simple_queryall($query);
            $result = array();
            if (!empty($allNethosts)) {
                foreach ($allNethosts as $io => $each) {
                    if (!empty($each['option'])) {
                        $result[] = $each['option'];
                    }
                }
                $result = array_flip($result);
            }
            return ($result);
        }

        /**
         * Get current nethost options by user`s login
         * 
         * @param $login - user`s login
         * 
         * @return array
         */
        function opt82_GetCurrentOptions($login) {
            $login = mysql_real_escape_string($login);
            $userIp = zb_UserGetIP($login);
            $nethost_q = "SELECT * from `nethosts` WHERE `ip`='" . $userIp . "'";
            $nethostRaw = simple_query($nethost_q);

            $result = array();
            $result['hostid'] = '';
            $result['hostip'] = '';
            $result['netid'] = '';
            $result['remoteid'] = '';
            $result['circuitid'] = '';


            if (!empty($nethostRaw)) {
                $result['hostid'] = $nethostRaw['id'];
                $result['hostip'] = $nethostRaw['ip'];
                $result['netid'] = $nethostRaw['netid'];

                if (!empty($nethostRaw['option'])) {
                    $explode = explode('|', $nethostRaw['option']);
                    if (isset($explode[1])) {
                        $result['remoteid'] = $explode[0];
                        $result['circuitid'] = $explode[1];
                    }
                }
            }
            return ($result);
        }

        /**
         * Returns nethost option modify form
         * 
         * @param $allLeases - all available leases parsed from log
         * @param $login - user`s  login 
         * 
         * @return string
         */
        function web_opt82_ShowForm($allLeases, $login) {
            $result = '';
            $currentData = opt82_GetCurrentOptions($login);

            $cells = wf_TableCell(__('IP'));
            $cells .= wf_TableCell($currentData['hostip']);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Remote-ID'));
            $cells .= wf_TableCell($currentData['remoteid']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Circuit-ID'));
            $cells .= wf_TableCell($currentData['circuitid']);
            $rows .= wf_TableRow($cells, 'row3');

            $currentTable = wf_TableBody($rows, '30%', '0', '');
            $result .= $currentTable;
            $result .= wf_delimiter();

            $inputs = wf_Selector('newopt82', $allLeases, __('New DHCP option 82'), '', true);
            $inputs .= wf_HiddenInput('edithostid', $currentData['hostid']);
            $inputs .= wf_HiddenInput('edithostip', $currentData['hostip']);
            $inputs .= wf_CheckInput('setrandomopt82', __('Set random'), true, false);
            $inputs .= wf_Submit(__('Save'));
            $form = wf_Form('', 'POST', $inputs, 'glamour');

            $result .= $form;
            $result .= wf_delimiter();
            $result .= web_UserControls($login);

            return ($result);
        }

        /**
         * Update some option in "remote-id|circuit-id" view into the nethosts 
         * 
         * @param $ip - nethost ip
         * @param $option - nethost option to set
         * 
         * @return void
         */
        function opt82_SetOption($ip, $option) {
            $query = "UPDATE `nethosts` SET `option`='" . $option . "' WHERE `ip`='" . $ip . "'";
            nr_query($query);
            log_register("OPT82 SET " . $ip . ' `' . $option . '`');
        }

        /**
         * checks for available dhcp82 networks
         * 
         * @return bool
         */
        function opt82_NetsAvailable() {
            $query = "SELECT `id` from `networks` WHERE `nettype`='dhcp82';";
            $rawData = simple_query($query);
            if (!empty($rawData)) {
                return (true);
            } else {
                return (false);
            }
        }

        if (wf_CheckGet(array('username'))) {
            $login = $_GET['username'];

            if (opt82_NetsAvailable()) {
                $busyLeases = opt82_GetAllUsedLeases();
                $allLeases = opt82_LoadLeases($billing_config, $altercfg, $busyLeases);

                if (wf_CheckPost(array('edithostid', 'edithostip'))) {
                    if (!isset($_POST['setrandomopt82'])) {
                        if (wf_CheckPost(array('newopt82'))) {
                            opt82_SetOption($_POST['edithostip'], $_POST['newopt82']);
                        } else {
                            show_error(__('No option 82 get'));
                        }
                    } else {
                        $randomRemoteId = '14:88:' . rand(10000, 99999);
                        $randomCircuitId = rand(10000, 99999);
                        $randomNewOpt = $randomRemoteId . '|' . $randomCircuitId;
                        opt82_SetOption($_POST['edithostip'], $randomNewOpt);
                    }

                    multinet_rebuild_all_handlers();
                    rcms_redirect("?module=pl_option82&username=" . $login);
                }
                show_window(__('Current value'), web_opt82_ShowForm($allLeases, $login));
            } else {
                show_error(__('No DHCP option 82 networks available'));
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

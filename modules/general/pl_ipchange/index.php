<?php

if (cfr('PLIPCHANGE')) {
    
    deb('Этот модуль ущербен и переписывается с нуля');

    class IpChange {

        protected $login = '';
        protected $currentIp = '';
        protected $currentMac = '';
        protected $billingCfg = array();
        protected $altCfg = array();
        protected $allServices = array();
        protected $customFlag = false;

        const URL_ME = '?module=pl_ipchange';

        public function __construct() {
            $this->loadConfigs();
            $this->loadServices();
        }

        protected function loadConfigs() {
            global $ubillingConfig;
            $this->billingCfg = $ubillingConfig->getBilling();
            $this->altCfg = $ubillingConfig->getAlter();
            if (isset($this->altCfg['IP_CUSTOM_SELECT'])) {
                if ($this->altCfg['IP_CUSTOM_SELECT']) {
                    $this->customFlag = true;
                }
            }
        }

        protected function loadServices() {
            $rawServices = multinet_get_services();
            if (!empty($rawServices)) {
                foreach ($rawServices as $io => $each) {
                    $this->allServices[$each['id']] = $each['desc'];
                }
            }
        }

        public function setLogin($login) {
            $this->login = mysql_real_escape_string($login);
        }

        public function initUserParams() {
            if (!empty($this->login)) {
                $this->currentIp = zb_UserGetIP($this->login);
                $this->currentMac = zb_MultinetGetMAC($this->currentIp);
            }
        }

        public function renderMainForm() {
            $result = '';
            $servSelector = array();
            if (!empty($this->allServices)) {

                foreach ($this->allServices as $serviceId => $serviceName) {
                    $servSelector[self::URL_ME . '&ajserviceid=' . $serviceId] = $serviceName;
                }
                //getting firs service ID
                reset($this->allServices);
                $defaultService = key($this->allServices);

                $result = wf_AjaxLoader();
                $inputs = wf_AjaxSelectorAC('ajcontainer', $servSelector, __('Select User new service'), '', false);
                $inputs.= wf_tag('br').wf_tag('br');
                $inputs.= wf_AjaxContainer('ajcontainer', '', $this->ajIpSelector($defaultService));
                $inputs.= wf_delimiter();
                $inputs.= wf_Submit(__('Save'));
                $result.= wf_Form("", 'POST', $inputs, 'floatpanels');
            } else {
                $messages = new UbillingMessageHelper();
                $result = $messages->getStyledMessage(__('No available services'), 'error');
            }
            return ($result);
        }

        public function ajIpSelector($serviceId) {
            $serviceId = vf($serviceId, 3);
            $result = '';
            if (isset($this->allServices[$serviceId])) {
                $networkId = multinet_get_service_networkid($serviceId);
                //default IP selection - first free
                if (!$this->customFlag) {
                    @$nextFreeIp = multinet_get_next_freeip('nethosts', 'ip', $networkId);
                    if (!empty($nextFreeIp)) {
                        $result = wf_HiddenInput('ipselector', $nextFreeIp) . ' ' . $nextFreeIp;
                        $result.= wf_HiddenInput('serviceselector', $serviceId);
                    } else {
                        $result = __('No free IP available in selected pool. Please fix it in networks and services module.');
                    }
                } else {
                    //custom IP selection box
                    $allFreeIpsRaw = multinet_get_all_free_ip('nethosts', 'ip', $networkId);
                    if (!empty($allFreeIpsRaw)) {
                        $allFreeIpsSelector = array();
                        foreach ($allFreeIpsRaw as $io => $each) {
                            $allFreeIpsSelector[$each] = $each;
                        }
                        $result = wf_Selector('ipselector', $allFreeIpsSelector, '', '', true);
                        $result.= wf_HiddenInput('serviceselector', $serviceId);
                    } else {
                        $result = __('No free IP available in selected pool. Please fix it in networks and services module.');
                    }
                }
            }
            return ($result);
        }

        public function renderCurrentIp() {
            $result = wf_tag('h2', false, 'floatpanels', '') . ' ' . $this->currentIp . wf_tag('h2', true);
            $result.= wf_CleanDiv();
            return ($result);
        }

    }

    $ipChange = new IpChange();

    if (wf_CheckGet(array('username'))) {
        //user is here
        $ipChange->setLogin($_GET['username']);
        $ipChange->initUserParams();

        //rendering interface
        show_window(__('Current user IP'), $ipChange->renderCurrentIp());
        show_window(__('Change user IP'), $ipChange->renderMainForm());
    } else {
        show_error(__('Something went wrong'));
    }

    if (wf_CheckGet(array('ajserviceid'))) {
        die($ipChange->ajIpSelector($_GET['ajserviceid']));
    }

    //old creepy staff here 

    if (wf_CheckGet(array('username'))) {
        $login = mysql_real_escape_string($_GET['username']);
        $current_ip = zb_UserGetIP($login); // getting IP by login
        $current_mac = zb_MultinetGetMAC($current_ip); //extracting current user MAC
        $billingConf = $ubillingConfig->getBilling(); //getting billing.ini config
        $alterConnf = $ubillingConfig->getAlter(); //getting alter.ini config

        /**
         * Returns new user service select form
         * 
         * @return string
         */
        function web_IPChangeFormService() {
            $allServices = array();
            $servSelector = array();
            $rawServices = multinet_get_services();

            if (!empty($rawServices)) {
                foreach ($rawServices as $io => $each) {
                    $allServices[$each['id']] = $each['desc'];
                }


                if (!empty($allServices)) {
                    foreach ($allServices as $serviceId => $serviceName) {
                        $servSelector['?module=pl_ipchange&ajipselector=' . $serviceId] = $serviceName;
                    }
                }

                $result = wf_AjaxLoader();
                $inputs = wf_AjaxSelectorAC('ajcontainer', $servSelector, __('New IP service'), '', false);
                $inputs.= wf_AjaxContainer('ajcontainer', '', 'contenthere');
                $inputs.= wf_delimiter();
                $inputs.= wf_Submit(__('Save'));
                $result.= wf_Form("", 'POST', $inputs, 'floatpanels');
            } else {
                $messages = new UbillingMessageHelper();
                $result = $messages->getStyledMessage(__('No available services'), 'error');
            }
            return($result);
        }

        /**
         * Returns array with subnets usage stats
         * 
         * @return array
         */
        function zb_FreeIpStats() {
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
         * Renders subnets usage stats
         * 
         * @return string
         */
        function web_FreeIpStats() {
            $result = '';
            $data = zb_FreeIpStats();

            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Network/CIDR'));
            $cells.= wf_TableCell(__('Total') . ' ' . __('IP'));
            $cells.= wf_TableCell(__('Used') . ' ' . __('IP'));
            $cells.= wf_TableCell(__('Free') . ' ' . __('IP'));
            $cells.= wf_TableCell(__('Service'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($data)) {
                foreach ($data as $io => $each) {
                    $free = $each['total'] - $each['used'];
                    $fontColor = ($free <= 5) ? '#a90000' : '';
                    $cells = wf_TableCell($io);
                    $cells.= wf_TableCell($each['desc']);
                    $cells.= wf_TableCell($each['total']);
                    $cells.= wf_TableCell($each['used']);
                    $cells.= wf_TableCell(wf_tag('font', false, '', 'color="' . $fontColor . '"') . $free . wf_tag('font', false));
                    $cells.= wf_TableCell($each['service']);
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
            return ($result);
        }

        /**
         * Flushes all old user`s networking data and applies new one
         * 
         * @param   string   $current_ip        current users`s IP
         * @param   string   $current_mac       current users`s MAC address
         * @param   int      $new_multinet_id   new network ID extracted from service
         * @param   string   $new_free_ip       new IP address which be applied for user
         * @param   string   $login             existing stargazer user login
         * 
         * @return void
         */
        function zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login) {
            global $billing;
            global $billingConf;

            //force user disconnect
            if ($billingConf['RESET_AO']) {
                $billing->setao($login, 0);
            } else {
                $billing->setdown($login, 1);
            }

            $billing->setip($login, $new_free_ip);
            multinet_delete_host($current_ip);
            multinet_add_host($new_multinet_id, $new_free_ip, $current_mac);
            multinet_rebuild_all_handlers();
            multinet_RestartDhcp();

            //back teh user online
            if ($billingConf['RESET_AO']) {
                $billing->setao($login, 1);
            } else {
                $billing->setdown($login, 0);
            }
        }

        // primary module part    
        if (isset($_POST['serviceselect'])) {
            $new_multinet_id = multinet_get_service_networkid($_POST['serviceselect']);
            @$new_free_ip = multinet_get_next_freeip('nethosts', 'ip', $new_multinet_id);
            if (empty($new_free_ip)) {
                $alert = wf_tag('script', false, '', 'type="text/javascript"') . 'alert("' . __('Error') . ': ' . __('No free IP available in selected pool') . '");' . wf_tag('script', true);
                print($alert);
                rcms_redirect("?module=multinet");
                die();
            }

            zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip, $login);
            log_register("CHANGE MultiNetIP (" . $login . ") FROM " . $current_ip . " ON " . $new_free_ip . "");
            rcms_redirect("?module=pl_ipchange&username=" . $login);
        } else {
            //commented due develop
//            show_window(__('Current user IP'), wf_tag('h2', false, 'floatpanels', '') . ' ' . $current_ip . wf_tag('h2', true) . '<br clear="both" />');
//            show_window(__('Change user IP'), web_IPChangeFormService());
//            show_window(__('IP usage stats'), web_FreeIpStats());
        }

        show_window('', web_UserControls($login));
    } else {
        show_error(__('Something went wrong'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

<?php

if (cfr('PLIPCHANGE')) {

    class IpChange {

        /**
         * Contains current user login
         *
         * @var string
         */
        protected $login = '';

        /**
         * Contains current user IP
         *
         * @var string
         */
        protected $currentIp = '';

        /**
         * Contains current user MAC
         *
         * @var string
         */
        protected $currentMac = '';

        /**
         * Contains system billing.ini config as key=>value
         *
         * @var array
         */
        protected $billingCfg = array();

        /**
         * Contains system alter.ini config as key=>value
         *
         * @var array
         */
        protected $altCfg = array();

        /**
         * Contains available network services as id=>name
         *
         * @var array
         */
        protected $allServices = array();

        /**
         * Flag of IP_CUSTOM_SELECT optional option state
         *
         * @var bool
         */
        protected $customFlag = false;

        /**
         * message helper object placeholder
         *
         * @var object
         */
        protected $messages = '';

        const URL_ME = '?module=pl_ipchange';

        public function __construct() {
            $this->loadConfigs();
            $this->initMessages();
            $this->loadServices();
        }

        /**
         * Loads system configs and inits customFlag property
         * 
         * @global object  $ubillingConfig
         * 
         * @return void
         */
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

        /**
         * Inits message helper object for further usage
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Loads available network services
         * 
         * @return void
         */
        protected function loadServices() {
            $rawServices = multinet_get_services();
            if (!empty($rawServices)) {
                foreach ($rawServices as $io => $each) {
                    $this->allServices[$each['id']] = $each['desc'];
                }
            }
        }

        /**
         * Sets current user login
         * 
         * @param string $login
         * 
         * @return void
         */
        public function setLogin($login) {
            $this->login = mysql_real_escape_string($login);
        }

        /**
         * Inits user params as current IP and MAC
         * 
         * @return void
         */
        public function initUserParams() {
            if (!empty($this->login)) {
                $this->currentIp = zb_UserGetIP($this->login);
                $this->currentMac = zb_MultinetGetMAC($this->currentIp);
            }
        }

        /**
         * Renders service and IP selection dialog 
         * 
         * @return string
         */
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
                $inputs.= wf_tag('br') . wf_tag('br');
                $inputs.= wf_AjaxContainer('ajcontainer', '', $this->ajIpSelector($defaultService));
                $inputs.= wf_delimiter();
                $inputs.= wf_Submit(__('Save'));
                $result.= wf_Form("", 'POST', $inputs, 'floatpanels');
            } else {
                $result = $this->messages->getStyledMessage(__('No available services'), 'error');
            }
            return ($result);
        }

        /**
         * Returns IP selector ajax container content
         * 
         * @param int $serviceId
         * 
         * @return string
         */
        public function ajIpSelector($serviceId) {
            $serviceId = vf($serviceId, 3);
            $result = '';
            if (isset($this->allServices[$serviceId])) {
                $networkId = multinet_get_service_networkid($serviceId);
                //default IP selection - first free
                if (!$this->customFlag) {
                    @$nextFreeIp = multinet_get_next_freeip('nethosts', 'ip', $networkId);
                    if (!empty($nextFreeIp)) {
                        $result = wf_HiddenInput('ipselector', $nextFreeIp) . ' ' . $nextFreeIp . ' (' . __('first free for this service') . ')';
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

        /**
         * Renders current IP styled notification
         * 
         * @return string
         */
        public function renderCurrentIp() {
            $result = wf_tag('h2', false, 'floatpanels', '') . ' ' . $this->currentIp . wf_tag('h2', true);
            $result = $this->messages->getStyledMessage(wf_tag('h2', false, '', '') . __('Current user IP') . ': ' . $this->currentIp . wf_tag('h2', true), 'info');
            $result.= wf_CleanDiv();
            return ($result);
        }

        /**
         * Returns IP usage stats for available networks
         * 
         * @return array
         */
        protected function getFreeIpStats() {
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
         * Renders IP usage stats in existing networks
         * 
         * @return string
         */
        public function renderFreeIpStats() {
            $result = '';
            $data = $this->getFreeIpStats();

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
         * Checks have current user IP existing network host
         * 
         * @return bool
         */
        protected function isNethostExists() {
            $result = true;
            $query = "SELECT * from `nethosts` WHERE `ip`='" . $this->currentIp . "'";
            $data = simple_query($query);
            if (empty($data)) {
                $result = false;
            }
            return ($result);
        }

        /**
         * Performs user changing IP subroutine
         * 
         * @global object $billing
         * @param int $serviceId
         * @param string $newIp
         * 
         * @return void/string
         */
        public function changeUserIp($serviceId, $newIp) {
            global $billing;
            $result = '';
            $serviceId = vf($serviceId, 3);
            $networkId = multinet_get_service_networkid($serviceId);

            if (isset($this->allServices[$serviceId])) {
                if (zb_ip_unique($newIp)) {
                    if (!empty($this->currentIp)) {
                        if ($this->isNethostExists()) {

                            //force user disconnect
                            if ($this->billingCfg['RESET_AO']) {
                                $billing->setao($this->login, 0);
                            } else {
                                $billing->setdown($this->login, 1);
                            }

                            $billing->setip($this->login, $newIp);
                            multinet_delete_host($this->currentIp);
                            multinet_add_host($networkId, $newIp, $this->currentMac);
                            log_register("CHANGE MultiNetIP (" . $this->login . ") FROM " . $this->currentIp . " ON " . $newIp . "");
                            multinet_rebuild_all_handlers();
                            multinet_RestartDhcp();

                            //back teh user online
                            if ($this->billingCfg['RESET_AO']) {
                                $billing->setao($this->login, 1);
                            } else {
                                $billing->setdown($this->login, 0);
                            }
                        } else {
                            log_register("CHANGE FAIL MultiNetIP (" . $this->login . ") FROM " . $this->currentIp . " ON " . $newIp . " NO_NETHOST");
                            $result = __('No existing nethost for current IP');
                        }
                    } else {
                        log_register("CHANGE FAIL MultiNetIP (" . $this->login . ") FROM " . $this->currentIp . " ON " . $newIp . " EMPTY_IP");
                        $result = __('Something went wrong') . ': ' . __('empty current IP');
                    }
                } else {
                    log_register("CHANGE FAIL MultiNetIP (" . $this->login . ") FROM " . $this->currentIp . " ON " . $newIp . " IP_DUPLICATE");
                    $result = __('This IP is already used by another user');
                }
            } else {
                log_register("CHANGE FAIL MultiNetIP (" . $this->login . ") FROM " . $this->currentIp . " ON " . $newIp . " NO_SERVICE");
                $result = __('Unexistent service');
            }
            return ($result);
        }

    }

//creating object
    $ipChange = new IpChange();

//rendering ajax IP selector container data
    if (wf_CheckGet(array('ajserviceid'))) {
        die($ipChange->ajIpSelector($_GET['ajserviceid']));
    }

    if (wf_CheckGet(array('username'))) {
        //user is here
        $userLogin = $_GET['username'];
        $ipChange->setLogin($userLogin);
        $ipChange->initUserParams();

        //change IP if required
        if (wf_CheckPost(array('ipselector', 'serviceselector'))) {
            $changeResult = $ipChange->changeUserIp($_POST['serviceselector'], $_POST['ipselector']);
            if (empty($changeResult)) {
                rcms_redirect($ipChange::URL_ME . '&username=' . $userLogin);
            } else {
                show_error($changeResult);
            }
        }

        //rendering interface
        show_window('', $ipChange->renderCurrentIp());
        show_window(__('Change user IP'), $ipChange->renderMainForm());
        show_window(__('IP usage stats'), $ipChange->renderFreeIpStats());
        show_window('', web_UserControls($userLogin));
    } else {
        show_error(__('Something went wrong'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

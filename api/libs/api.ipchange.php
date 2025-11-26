<?php

/**
 * User IP changing/management implementation
 */
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

    /**
     * is database locking feature enabled
     * 
     * @var bool
     */
    protected $dbLockEnabled = false;

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
        if (isset($this->altCfg['DB_LOCK_ENABLED'])) {
            $this->dbLockEnabled = $this->altCfg['DB_LOCK_ENABLED'];
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
        if ($this->altCfg['BRANCHES_ENABLED']) {
            global $branchControl;
            $branchControl->loadServices();
        }
        $result = '';
        $servSelector = array();
        if (!empty($this->allServices)) {

            foreach ($this->allServices as $serviceId => $serviceName) {
                if ($this->altCfg['BRANCHES_ENABLED']) {
                    if ($branchControl->isMyService($serviceId)) {
                        $servSelector[self::URL_ME . '&ajserviceid=' . $serviceId] = $serviceName;
                    }
                } else {
                    $servSelector[self::URL_ME . '&ajserviceid=' . $serviceId] = $serviceName;
                }
            }
            //getting firs service ID
            reset($this->allServices);
            $defaultService = key($this->allServices);

            $result = wf_AjaxLoader();
            $inputs = wf_AjaxSelectorAC('ajcontainer', $servSelector, __('Select User new service'), '', false);
            $inputs .= wf_tag('br') . wf_tag('br');
            $inputs .= wf_AjaxContainer('ajcontainer', '', $this->ajIpSelector($defaultService));
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form("", 'POST', $inputs, 'floatpanels');
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
                    $result .= wf_HiddenInput('serviceselector', $serviceId);
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
                    $result .= wf_HiddenInput('serviceselector', $serviceId);
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
        $result .= wf_CleanDiv();
        return ($result);
    }

    /**
     * Renders IP usage stats in existing networks
     * 
     * @return string
     */
    public function renderFreeIpStats() {
        $result = '';
        $controls = wf_Link(self::URL_ME . '&username=' . $this->login, wf_img('skins/done_icon.png') . ' ' . __('Services'), false, 'ubButton');
        $controls .= wf_Link(self::URL_ME . '&username=' . $this->login . '&allnets=true', wf_img('skins/categories_icon.png') . ' ' . __('All networks'), false, 'ubButton');

        $result .= web_FreeIpStats();
        $result .= $controls;
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
        //set lock or wait until previous lock will be released
        //lock name "ipBind" is shared between userreg and pl_ipchange
        if ($this->dbLockEnabled) {
            $dbLockQuery = 'SELECT GET_LOCK("ipBind",1) AS result';
            $dbLock = false;
            while (!$dbLock) {
                $dbLockCheck = simple_query($dbLockQuery);
                $dbLock = $dbLockCheck['result'];
            }
        }
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
                        log_register("MULTINET IP CHANGE (" . $this->login . ") FROM `" . $this->currentIp . "` ON `" . $newIp . "`");
                        multinet_rebuild_all_handlers();
                        multinet_RestartDhcp();
                        zb_UserGetAllDataCacheClean();

                        if ($this->altCfg['MULTIGEN_ENABLED']) {
                            if ($this->altCfg['MULTIGEN_POD_ON_IP_CHANGE']) {
                                $newUserData = zb_UserGetAllData($this->login);
                                $newUserData = $newUserData[$this->login];

                                $userData = $newUserData;
                                $userData['ip'] = $this->currentIp;
                                $mlg = new MultiGen();
                                if ($this->altCfg['MULTIGEN_POD_ON_IP_CHANGE'] == 2) {
                                    $mlg->podOnExternalEvent($this->login, $userData, $newUserData);
                                    $mlg->podOnExternalEvent($this->login, $newUserData);
                                }
                                if ($this->altCfg['MULTIGEN_POD_ON_IP_CHANGE'] == 1) {
                                    $mlg->podOnExternalEvent($this->login, $newUserData);
                                }
                            }
                        }

                        //back teh user online
                        if ($this->billingCfg['RESET_AO']) {
                            $billing->setao($this->login, 1);
                        } else {
                            $billing->setdown($this->login, 0);
                        }

                        //optional arp cleanup here
                        if (@$this->altCfg['IPCHANGE_ARP_CLEANUP']) {
                            $command = $this->billingCfg['SUDO'] . ' arp -d ' . $this->currentIp;
                            shell_exec($command);
                        }
                    } else {
                        log_register("MULTINET IP CHANGE FAIL (" . $this->login . ") FROM `" . $this->currentIp . "` ON `" . $newIp . "` NO_NETHOST");
                        $result = __('No existing nethost for current IP');
                    }
                } else {
                    log_register("MULTINET IP CHANGE FAIL (" . $this->login . ") FROM `" . $this->currentIp . "` ON `" . $newIp . "` EMPTY_IP");
                    $result = __('Something went wrong') . ': ' . __('empty current IP');
                }
            } else {
                log_register("MULTINET IP CHANGE FAIL (" . $this->login . ") FROM `" . $this->currentIp . "` ON `" . $newIp . "` IP_DUPLICATE");
                $result = __('This IP is already used by another user');
            }
        } else {
            log_register("MULTINET IP CHANGE FAIL (" . $this->login . ") FROM `" . $this->currentIp . "` ON `" . $newIp . "` NO_SERVICE");
            $result = __('Unexistent service');
        }
        //release lock
        if ($this->dbLockEnabled) {
            $dbUnlockQuery = 'SELECT RELEASE_LOCK("ipBind")';
            nr_query($dbUnlockQuery);
        }
        return ($result);
    }

}

?>
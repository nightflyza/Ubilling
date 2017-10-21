<?php

class ArpDiag {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains default module URL
     */
    const URL_ME = '?module=arpdiag';

    /**
     * Creates new instance of object
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
    }

    /**
     * Loads system configs into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Inits new instance of message helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Renders control panel
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result.= wf_Link(self::URL_ME, wf_img('skins/log_icon_small.png') . ' ' . __('Last events'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&arptable=true', wf_img('skins/icon_search_small.gif') . ' ' . __('Local ARP table'), false, 'ubButton');
        return ($result);
    }

    /**
     * Returns all switches IP as ip=>array(location,id)
     * 
     * @return array
     */
    protected function getAllSwitchesIps() {
        $result = array();
        $query = "SELECT * from `switches`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['ip']]['location'] = $each['location'];
                $result[$each['ip']]['id'] = $each['id'];
                $result[$each['ip']]['mac'] = $each['swid'];
            }
        }
        return ($result);
    }

    /**
     * Returns row class for some event
     * 
     * @param string $event
     * 
     * @return string
     */
    protected function getEventClass($event) {
        $result = 'row3';
        if (ispos($event, 'attemp')) {
            $result = 'ukvbankstadup';
        }

        if (ispos($event, 'moved from')) {
            $result = 'undone';
        }

        if (ispos($event, 'new station')) {
            $result = 'todaysig';
        }


        if (ispos($event, 'ETHERTYPE')) {
            $result = 'sigcemeteryuser';
        }

        if (ispos($event, 'hardware')) {
            $result = 'donetask';
        }

        if (ispos($event, 'flip flop')) {
            $result = 'undone';
        }

        if (ispos($event, 'using my IP')) {
            $result = 'rowerror';
        }
        return ($result);
    }

    /**
     * Renders arp events report
     * 
     * @return string
     */
    public function renderReport() {
        $log_path = $this->altCfg['ARPDIAG_LOG'];
        $sudo_path = $this->billCfg['SUDO'];
        $cat_path = $this->billCfg['CAT'];
        $grep_path = $this->billCfg['GREP'];
        $command = $sudo_path . ' ' . $cat_path . ' ' . $log_path . ' | ' . $grep_path . ' "arp"';
        $rawdata = shell_exec($command);
        $tablerows = '';
        if (!empty($rawdata)) {
            $splitdata = explodeRows($rawdata);
            if (!empty($splitdata)) {
                foreach ($splitdata as $eachrow) {
                    if (!empty($eachrow)) {
                        $rowclass = $this->getEventClass($eachrow);
                        $tablecells = wf_TableCell($eachrow);
                        $tablerows.=wf_TableRow($tablecells, $rowclass);
                    }
                }
            }

            $result = wf_TableBody($tablerows, '100%', '0', '');
        } else {
            $result = $this->messages->getStyledMessage(__('It seems there is nothing unusual'), 'info');
        }
        return ($result);
    }

    /**
     * Returns clickable if possible host link by its IP
     * 
     * @param array $allUserIps
     * @param array $allUserAddress
     * @param array $allSwitchesIps
     * @param string $ip
     * 
     * @return string
     */
    protected function getHostLink($allUserIps, $allUserAddress, $allSwitchesIps, $ip) {
        $result = '';
        $userUrl = '?module=userprofile&username=';
        $switchUrl = '?module=switches&edit=';
        if (isset($allUserIps[$ip])) {
            $result = wf_Link($userUrl . $allUserIps[$ip], web_profile_icon(__('User')) . ' ' . @$allUserAddress[$allUserIps[$ip]], false);
        } else {
            if (isset($allSwitchesIps[$ip])) {
                $result = wf_Link($switchUrl . $allSwitchesIps[$ip]['id'], wf_img_sized('skins/menuicons/switches.png', __('Switch'), '11', '13') . ' ' . $allSwitchesIps[$ip]['location'], false);
            }
        }
        return ($result);
    }

    /**
     * Returns device MAC controls if required
     * 
     * @param string $ip
     * @param string $mac
     * @param array $allUserIps
     * @param array $allUserIpMacs
     * @param array $allSwitchesIps
     * 
     * @return string
     */
    protected function getMacControls($ip, $mac, $allUserIps, $allUserIpMacs, $allSwitchesIps) {
        $result = '';
        //normal user
        if (isset($allUserIps[$ip])) {
            if (isset($allUserIpMacs[$ip])) {
                if (($allUserIpMacs[$ip] != $mac) AND (!empty($mac))) {
                    $result = wf_img('skins/createtask.gif') . ' ' . __('MAC mismatch');
                } else {
                    $result = wf_img_sized('skins/icon_ok.gif', '', 10, 10) . ' ' . __('Ok');
                }
            }
        } else {
            //Switches ID management enabled
            if ($this->altCfg['SWITCHES_EXTENDED']) {
                //registered switches directory device
                if (isset($allSwitchesIps[$ip])) {
                    if (empty($allSwitchesIps[$ip]['mac'])) {
                        if (check_mac_format($mac)) {
                            if (cfr('SWITCHESEDIT')) {
                                $result = wf_Link(self::URL_ME . '&arptable=true&swassign=' . $allSwitchesIps[$ip]['id'] . '&swmac=' . $mac, wf_img_sized('skins/add_icon.png', '', 10, 10) . ' ' . __('Assign'));
                            }
                        }
                    } else {
                        //switch already have mac
                        if ($mac != $allSwitchesIps[$ip]['mac']) {
                            $result = wf_img('skins/createtask.gif') . ' ' . __('MAC mismatch');
                        } else {
                            $result = wf_img_sized('skins/icon_ok.gif', '', 10, 10) . ' ' . __('Normal');
                        }
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Assigns some switch MAC if required
     * 
     * @param int $switchId
     * @param string $mac
     * 
     * @return void
     */
    public function assignSwitchMac($switchId, $mac) {
        $switchId = vf($switchId, 3);
        $macF = mysql_real_escape_string($mac);
        if (cfr('SWITCHESEDIT')) {
            $where = "WHERE `id`='" . $switchId . "';";
            simple_update_field('switches', 'swid', $macF, $where);
            log_register('SWITCH CHANGE [' . $switchId . '] MAC `' . $mac . '`');
        }
    }

    /**
     * Returns JSON for actual ARP table
     * 
     * @return void
     */
    public function ajaxReplyArp() {
        $json = new wf_JqDtHelper();
        $command = 'arp -a';
        $raw = shell_exec($command);
        if (!empty($raw)) {

            $allUserAddress = zb_AddressGetFulladdresslistCached();
            $allUserIps = zb_UserGetAllIPs();
            $allUserIps = array_flip($allUserIps);
            $allUserIpMacs = zb_UserGetAllIpMACs();
            $allSwitchesIps = $this->getAllSwitchesIps();
            $allSwitchesMac = array();

            $raw = explodeRows($raw);

            if (!empty($raw)) {
                foreach ($raw as $io => $each) {
                    if (!empty($each)) {
                        /**
                         * And all you see are the cards that you can play
                         * And you thought you'd get away with these games
                         * And all you see are the cards there in your hand
                         * And you thought that you have everything planned
                         */
                        $ip = zb_ExtractIpAddress($each);
                        $mac = zb_ExtractMacAddress($each);
                        $hostType = $this->getHostLink($allUserIps, $allUserAddress, $allSwitchesIps, $ip, $mac);
                        $jsonItem[] = $ip;
                        $jsonItem[] = $mac;
                        $jsonItem[] = $hostType;
                        $jsonItem[] = $this->getMacControls($ip, $mac, $allUserIps, $allUserIpMacs, $allSwitchesIps);
                        $json->addRow($jsonItem);
                        unset($jsonItem);
                    }
                }
            }
        }
        $json->getJson();
    }

    /**
     * Renders localhost ARP table placeholder
     * 
     * @return string
     */
    public function renderArpTable() {
        $result = wf_JqDtLoader(array('IP', 'MAC', 'Host', 'Actions'), self::URL_ME . '&ajaxarp=true', true, __('Host'), '100');
        return ($result);
    }

}

if (cfr('ARPDIAG')) {
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['ARPDIAG_ENABLED']) {
        $arpDiag = new ArpDiag();
        if (wf_CheckGet(array('ajaxarp'))) {
            $arpDiag->ajaxReplyArp();
        }
        show_window('', $arpDiag->renderPanel());

        if (!wf_CheckGet(array('arptable'))) {
            show_window(__('Diagnosing problems with the ARP'), $arpDiag->renderReport());
        } else {
            //switch MAC assign
            if (wf_CheckGet(array('swassign', 'swmac'))) {
                $arpDiag->assignSwitchMac($_GET['swassign'], $_GET['swmac']);
                rcms_redirect($arpDiag::URL_ME . '&arptable=true');
            }
            show_window(__('Local ARP table'), $arpDiag->renderArpTable());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

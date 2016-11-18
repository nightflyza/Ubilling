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
                $result = wf_Link($switchUrl . $allSwitchesIps[$ip]['id'], wf_img_sized('skins/menuicons/switches.png',__('Switch'),'11','13') . ' ' . $allSwitchesIps[$ip]['location'], false);
            }
        }
        return ($result);
    }

    /**
     * Renders localhost ARP table
     * 
     * @return string
     */
    public function renderArpTable() {
        $result = '';
        $command = 'arp -a';
        $raw = shell_exec($command);

        if (!empty($raw)) {
            $allUserAddress = zb_AddressGetFulladdresslistCached();
            $allUserIps = zb_UserGetAllIPs();
            $allUserIps = array_flip($allUserIps);
            $allSwitchesIps = $this->getAllSwitchesIps();

            $raw = explodeRows($raw);
            $cells = wf_TableCell(__('IP'));
            $cells.= wf_TableCell(__('MAC'));
            $cells.= wf_TableCell(__('Host'));

            $rows = wf_TableRow($cells, 'row1');

            if (!empty($raw)) {
                foreach ($raw as $io => $each) {
                    if (!empty($each)) {
                        $ip = zb_ExtractIpAddress($each);
                        $mac = zb_ExtractMacAddress($each);
                        $hostType = $this->getHostLink($allUserIps, $allUserAddress, $allSwitchesIps, $ip);
                        $cells = wf_TableCell($ip, '', '', 'sorttable_customkey="' . ip2int($ip) . '"');
                        $cells.= wf_TableCell($mac);
                        $cells.= wf_TableCell($hostType);
                        $rows.= wf_TableRow($cells, 'row5');
                    }
                }
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Nothing found'), 'warning');
        }

        return ($result);
    }

}

if (cfr('ARPDIAG')) {
    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['ARPDIAG_ENABLED']) {
        $arpDiag = new ArpDiag();

        show_window('', $arpDiag->renderPanel());
        if (!wf_CheckGet(array('arptable'))) {
            show_window(__('Diagnosing problems with the ARP'), $arpDiag->renderReport());
        } else {
            show_window(__('Local ARP table'), $arpDiag->renderArpTable());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>

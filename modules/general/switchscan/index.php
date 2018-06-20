<?php

if (cfr('SWITCHESEDIT')) {

    class SwitchScan {

        /**
         * Contains all available switches data
         *
         * @var array
         */
        protected $allSwitchesData = array();

        /**
         * Contains all available switches IPs as ip=>id
         *
         * @var array
         */
        protected $allSwitchesIp = array();

        /**
         * Array for IPs scanning
         *
         * @var array
         */
        protected $scanIps = array();

        /**
         * Contains array of available network CIDR masks
         *
         * @var array
         */
        protected $availMasks = array();

        /**
         * System messages helper placeholder
         *
         * @var object
         */
        protected $messages = '';

        /**
         * Creates new switch scan instance
         * 
         * @return void
         */
        public function __construct() {
            $this->availMasks = array(29 => 29, 28 => 28, 27 => 27, 26 => 26, 25 => 25, 24 => 24, 23 => 23, 22 => 22, 21 => 21, 20 => 20, 19 => 19, 18 => 18);
            $this->initMessages();
            $this->loadSwitches();
        }

        /**
         * Initializes message helper object for further usage
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Loads available switches from database and do some preprocessing
         * 
         * @return void
         */
        protected function loadSwitches() {
            $tmp = zb_SwitchesGetAll();
            if (!empty($tmp)) {
                foreach ($tmp as $io => $each) {
                    $this->allSwitchesData[$each['id']] = $each;
                    if (!empty($each['ip'])) {
                        $this->allSwitchesIp[$each['ip']] = $each['id'];
                    }
                }
            }
        }

        /**
         * Renders search form
         * 
         * @return string
         */
        public function renderForm() {
            $result = '';
            /**
             *          Am             C
             * Я знав тебе пацанкою малою
             * Em                 D
             * І ноль вніманія на тебе обращав
             * А як прийшов із армії додому
             * Тебе побачив і на дупу впав
             */
            $curNet = (wf_CheckPost(array('searchnetdevs'))) ? $_POST['searchnetdevs'] : '';
            $curCidr = (wf_CheckPost(array('searchnetcidr'))) ? $_POST['searchnetcidr'] : '';
            $inputs = wf_TextInput('searchnetdevs', __('Network') . ' /', $curNet, false, 20);
            $inputs.= wf_Selector('searchnetcidr', $this->availMasks, __('CIDR'), $curCidr, false);
            $inputs.= wf_Submit(__('Search'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * Renders search form
         * 
         * @return string
         */
        public function renderFormFree() {
            $result = '';
            $curNet = (wf_CheckPost(array('freenetdevs'))) ? $_POST['freenetdevs'] : '';
            $curCidr = (wf_CheckPost(array('freecidr'))) ? $_POST['freenetcidr'] : '';
            $inputs = wf_TextInput('freenetdevs', __('Network') . ' /', $curNet, false, 20);
            $inputs.= wf_Selector('freenetcidr', $this->availMasks, __('CIDR'), $curCidr, false);
            $inputs.= wf_Submit(__('Show'));
            $result.=wf_Form('', 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * Returns first/last IPs from some network
         * 
         * @param string $cidr
         * 
         * @return array
         */
        protected function getIpRange($cidr) {
            list($ip, $mask) = explode('/', $cidr);
            $maskBinStr = str_repeat("1", $mask) . str_repeat("0", 32 - $mask);      //net mask binary string
            $inverseMaskBinStr = str_repeat("0", $mask) . str_repeat("1", 32 - $mask); //inverse mask
            $ipLong = ip2int($ip);
            $ipMaskLong = bindec($maskBinStr);
            $inverseIpMaskLong = bindec($inverseMaskBinStr);
            $netWork = $ipLong & $ipMaskLong;
            $start = $netWork + 1; //ignore network ID(eg: 192.168.1.0)
            $end = ($netWork | $inverseIpMaskLong) - 1; //ignore brocast IP(eg: 192.168.1.255)
            return array('firstIP' => $start, 'lastIP' => $end);
        }

        /**
         * Returns array of some IPs in network range as index=>ip
         * 
         * @param string $cidr
         * 
         * @return array
         */
        protected function getEachIpInRange($cidr) {
            $ips = array();
            $range = $this->getIpRange($cidr);
            for ($ip = $range['firstIP']; $ip <= $range['lastIP']; $ip++) {
                $ips[] = int2ip($ip);
            }
            return $ips;
        }

        /**
         * Preprocesses network range for further actions
         * 
         * @param string $network
         * @param int $cidr
         * 
         * @return void/string on error
         */
        public function extractIpData($network, $cidr) {
            $result = '';
            $networkCidr = $network . '/' . $cidr;
            if (!empty($networkCidr)) {
                if ((ispos($networkCidr, '/')) AND ( filter_var($network, FILTER_VALIDATE_IP))) {
                    $this->scanIps = $this->getEachIpInRange($networkCidr);
                } else {
                    $result = __('Wrong network format');
                }
            } else {
                $result = __('Wrong network format');
            }
            return ($result);
        }

        /**
         * Perform search of unregistered devices
         * 
         * @return string
         */
        public function searchDevices() {
            $result = '';
            if (!empty($this->scanIps)) {
                foreach ($this->scanIps as $io => $eachIp) {
                    if (!isset($this->allSwitchesIp[$eachIp])) {
                        if (zb_PingICMP($eachIp)) {
                            $result.=$this->messages->getStyledMessage(__('Unknown device') . ' ' . $eachIp, 'warning');
                        }
                    }
                }
            }

            if (empty($result)) {
                $result.=$this->messages->getStyledMessage(__('Nothing found'), 'success');
            }
            return ($result);
        }

        /**
         * Returns list of free IPs not used in some network by switch devicess
         * 
         * @return string
         */
        public function lookupFreeIPs() {
            $result = '';
            $tmpArr = array();
            if (!empty($this->scanIps)) {
                foreach ($this->scanIps as $io => $eachIp) {
                    if (!isset($this->allSwitchesIp[$eachIp])) {
                        $tmpArr[] = $eachIp;
                    }
                }
            }

            if (!empty($tmpArr)) {
                $cells = wf_TableCell(__('IP'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($tmpArr as $io => $each) {
                    $cells = wf_TableCell($each, '', '', 'sorttable_customkey="' . ip2int($each) . '"');
                    $rows.= wf_TableRow($cells, 'row5');
                }

                $result.=wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result.=$this->messages->getStyledMessage(__('Nothing found'), 'warning');
            }
            return ($result);
        }

    }

    $scan = new SwitchScan();

    //rendering of some interface
    show_window('', wf_BackLink('?module=switches'));
    show_window(__('Scan for unknown devices'), $scan->renderForm());
    show_window(__('Scan for free IPs'), $scan->renderFormFree());


    //searching for unknown devices
    if (wf_CheckPost(array('searchnetdevs', 'searchnetcidr'))) {
        $extractResult = $scan->extractIpData($_POST['searchnetdevs'], $_POST['searchnetcidr']);
        if (empty($extractResult)) {
            show_window(__('Search results'), $scan->searchDevices());
        } else {
            show_error($extractResult);
        }
    }

    //looking for some freee IPs
    if (wf_CheckPost(array('freenetdevs', 'freenetcidr'))) {
        $extractResult = $scan->extractIpData($_POST['freenetdevs'], $_POST['freenetcidr']);
        if (empty($extractResult)) {
            show_window(__('Free IPs'), $scan->lookupFreeIPs());
        } else {
            show_error($extractResult);
        }
    }
} else {
    show_error(__('Access denied'));
}
?>
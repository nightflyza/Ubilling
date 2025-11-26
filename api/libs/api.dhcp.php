<?php

/**
 * ISC-DHCPD server management class
 */
class UbillingDHCP {

    /**
     * Contains available DHCP networks
     *
     * @var array
     */
    protected $allDhcpNets = array();

    /**
     * Contains available multinet networks
     *
     * @var array
     */
    protected $allMultinetNets = array();

    /**
     * Default message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Pregenerated DHCP configs path
     */
    const MULTINET_PATH = 'multinet/';

    /**
     * Default module URL
     */
    const URL_ME = '?module=dhcp';

    /**
     * DHCP config generation templates path
     */
    const TEMPLATES_PATH = 'config/dhcp/';

    public function __construct() {
        $this->loadMultinetNets();
        $this->loadDhcpNets();
        $this->initMessages();
    }

    /**
     * Loads existing DHCP subnets from database into protected property
     */
    protected function loadDhcpNets() {
        $query = "SELECT * from `dhcp` ORDER BY `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allDhcpNets[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads all of multinet networks for further usage
     * 
     * @return void
     */
    protected function loadMultinetNets() {
        $query = "SELECT * from `networks` ORDER BY `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allMultinetNets[$each['id']] = $each;
            }
        }
    }

    /**
     * Creates new instance of message helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Renders list of available DHCP networks with some controls
     * 
     * @return string
     */
    public function renderNetsList() {
        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Network/CIDR'));
        $cells .= wf_TableCell(__('DHCP custom subnet template'));
        $cells .= wf_TableCell(__('DHCP config name'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allDhcpNets)) {
            foreach ($this->allDhcpNets as $io => $eachnet) {
                $rowClass = 'row5';
                if (isset($this->allMultinetNets[$eachnet['netid']])) {
                    $cidr = $this->allMultinetNets[$eachnet['netid']]['desc'];
                } else {
                    $cidr = __('Network does not exist anymore');
                }
                $cells = wf_TableCell($eachnet['id']);
                $cells .= wf_TableCell($cidr);
                $cells .= wf_TableCell(web_bool_led($eachnet['dhcpconfig']));
                $cells .= wf_TableCell($eachnet['confname']);
                $actLinks = wf_JSAlert('?module=dhcp&delete=' . $eachnet['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
                $actLinks .= wf_Link('?module=dhcp&edit=' . $eachnet['id'], web_edit_icon(), false);
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('No available DHCP networks found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders main module controls
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Add DHCP network'), __('Add DHCP network'), $this->addForm(), 'ubButton') . ' ';
        $result .= wf_Link('?module=dhcplog', wf_img('skins/log_icon_small.png') . ' ' . __('View log'), false, 'ubButton') . ' ';
        if (cfr('ROOT')) {
            $result .= wf_Link('?module=dhcpzen', wf_img('skins/zen.png') . ' ' . __('DHCP') . ' ' . __('Zen'), false, 'ubButton') . ' ';
        }
        $result .= wf_Link(self::URL_ME . '&restartserver=true', wf_img('skins/refresh.gif') . ' ' . __('Restart DHCP server'), false, 'ubButton');

        return ($result);
    }

    /**
     * Renders generated configs previews list
     * 
     * @return string
     */
    public function renderConfigPreviews() {
        $result = '';
        if (!empty($this->allDhcpNets)) {
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('Network/CIDR'));
            $cells .= wf_TableCell(__('DHCP config name'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            if (file_exists(self::MULTINET_PATH . 'dhcpd.conf')) {
                $dhcpdconf = str_replace("\n", '<br>', file_get_contents(self::MULTINET_PATH . 'dhcpd.conf'));
            } else {
                $dhcpdconf = $this->messages->getStyledMessage(__('File not found') . ': dhcpd.conf', 'error');
            }

            $actLinks = wf_Link(self::URL_ME . '&downloadconfig=dhcpd.conf', web_icon_download(), false) . ' ';
            $actLinks .= wf_modal(web_icon_search(__('Preview') . ' dhcpd.conf'), 'dhcpd.conf', $dhcpdconf, '', 800, 600);

            $cells = wf_TableCell('-');
            $cells .= wf_TableCell('-');
            $cells .= wf_TableCell('dhcpd.conf');
            $cells .= wf_TableCell($actLinks);
            $rows .= wf_TableRow($cells, 'row5');

            foreach ($this->allDhcpNets as $io => $eachnet) {
                $subconfname = trim($eachnet['confname']);
                if (file_exists(self::MULTINET_PATH . $subconfname)) {
                    $subconfdata = str_replace("\n", '<br>', file_get_contents(self::MULTINET_PATH . $subconfname));
                } else {
                    $subconfdata = $this->messages->getStyledMessage(__('File not found') . ': ' . $subconfname, 'error');
                }

                $actLinks = wf_Link(self::URL_ME . '&downloadconfig=' . $subconfname, web_icon_download(), false);
                $actLinks .= wf_modal(web_icon_search(__('Preview') . ' ' . $subconfname), $subconfname, $subconfdata, '', 800, 600);

                $cells = wf_TableCell($eachnet['id']);
                $netLabel = '';
                if (isset($this->allMultinetNets[$eachnet['netid']])) {
                    $netLabel = $this->allMultinetNets[$eachnet['netid']]['desc'];
                } else {
                    $netLabel = __('Network does not exist anymore');
                }
                $cells .= wf_TableCell($netLabel);
                $cells .= wf_TableCell($subconfname);
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('No available DHCP configs found'), 'info');
        }
        return ($result);
    }

    /**
     * Returns json array of all available DHCP configs for remote deploy
     * 
     * @return string
     */
    public function getConfigsRemote() {
        $result = array();
        if (!empty($this->allDhcpNets)) {

            if (file_exists(self::MULTINET_PATH . 'dhcpd.conf')) {
                $dhcpdconf = file_get_contents(self::MULTINET_PATH . 'dhcpd.conf');
            } else {
                $dhcpdconf = '#EX_DHCPDCONF_NOT_EXISTS';
            }

            //main config
            $result['dhcpd.conf']['content'] = $dhcpdconf;

            foreach ($this->allDhcpNets as $io => $eachnet) {
                $subconfname = trim($eachnet['confname']);
                if (file_exists(self::MULTINET_PATH . $subconfname)) {
                    $subconfdata = file_get_contents(self::MULTINET_PATH . $subconfname);
                } else {
                    $subconfdata = '#' . $subconfname . '_NOT_EXISTS';
                }

                $result[$subconfname]['content'] = $subconfdata;
            }
        }

        $result = json_encode($result);
        return($result);
    }

    /**
     * Downloads pregenerated DHCP config
     * 
     * @param string $filename
     * 
     * @return void
     */
    public function downloadConfig($filename) {
        $filename = vf($filename);
        if (file_exists(self::MULTINET_PATH . $filename)) {
            zb_DownloadFile(self::MULTINET_PATH . $filename, 'text');
        } else {
            show_error(__('File not found') . ': ' . $filename);
        }
    }

    /**
     * Downloads DHCP config template
     * 
     * @param string $filename
     * 
     * @return void
     */
    public function downloadTemplate($filename) {
        $filename = vf($filename);
        if (file_exists(self::TEMPLATES_PATH . $filename)) {
            zb_DownloadFile(self::TEMPLATES_PATH . $filename, 'text');
        } else {
            show_error(__('File not found') . ': ' . $filename);
        }
    }

    /**
     * Renders DHCP config templates previews
     * 
     * @return string
     */
    public function renderConfigTemplates() {
        $allTemplates = rcms_scandir(self::TEMPLATES_PATH);
        $result = '';
        if (!empty($allTemplates)) {
            $cells = wf_TableCell(__('Filename'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($allTemplates as $eachfilename) {
                $templateData = file_get_contents(self::TEMPLATES_PATH . $eachfilename);
                $templateData = nl2br($templateData);
                $actLinks = wf_Link(self::URL_ME . '&downloadtemplate=' . $eachfilename, web_icon_download(), false) . ' ';
                $actLinks .= wf_modal(web_icon_search(__('Preview') . ' ' . $eachfilename), $eachfilename, $templateData, '', 800, 600) . ' ';

                $cells = wf_TableCell($eachfilename);
                $cells .= wf_TableCell(__($actLinks));
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Checks is multinet netid used for one of DHCP nets or not
     * 
     * @param int $netId
     * 
     * @return bool
     */
    protected function isNetUnused($netId) {
        $result = true;
        if (!empty($this->allDhcpNets)) {
            foreach ($this->allDhcpNets as $io => $each) {
                if ($each['netid'] == $netId) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is config name unused?
     * 
     * @param string $filename
     * 
     * @return bool
     */
    public function isConfigNameFree($filename) {
        $result = true;
        $filename = vf($filename);
        $filename = trim($filename);
        if (!empty($this->allDhcpNets)) {
            foreach ($this->allDhcpNets as $io => $each) {
                if ($each['confname'] == $filename) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Renders network selector with not set DHCP handlers
     * 
     * @return string
     */
    protected function networkSelector() {
        $tmpArr = array();
        $result = '';
        /**
         * V čůráckým vobdobí, kdy mě serou fronty,
         * sem na pokraji sil, už nepomáhaj jointy.
         * Rodinu nemám, večeřim sám, žeru šproty,
         * Vánoce zmrdem na Štědrej den do rachoty.
         */
        if (!empty($this->allMultinetNets)) {
            foreach ($this->allMultinetNets as $io => $each) {
                if ($this->isNetUnused($each['id'])) {
                    $tmpArr[$each['id']] = $each['desc'];
                }
            }

            if (!empty($tmpArr)) {
                $result = wf_Selector('networkselect', $tmpArr, __('Network'), '', true);
            }
        }
        return ($result);
    }

    /**
     * Returns DHCP network data by its id
     * 
     * @param int $dhcpid
     * 
     * @return array
     */
    public function getNetworkData($dhcpid) {
        $result = array();
        if (isset($this->allDhcpNets)) {
            $result = $this->allDhcpNets[$dhcpid];
        }
        return($result);
    }

    /**
     * Returns DHCP network addition form
     * 
     * @return string
     */
    public function addForm() {
        $result = '';
        //any multinet nets available
        if (!empty($this->allMultinetNets)) {
            $networkSelector = $this->networkSelector();
            //some of it have no DHCP handlers
            if (!empty($networkSelector)) {
                $inputs = $networkSelector;
                $inputs .= wf_HiddenInput('adddhcp', 'true');
                $inputs .= wf_TextInput('dhcpconfname', __('DHCP config name'), '', true, '20');
                $inputs .= wf_Submit(__('Create'));
                $result = wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result = $this->messages->getStyledMessage(__('All networks already has DHCP configured'), 'info');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No networks for DHCP setup available'), 'error');
        }
        return ($result);
    }

    /**
     * Renders network template editing form
     * 
     * @param int $dhcpid
     * 
     * @return string
     */
    public function editForm($dhcpid) {
        $dhcpid = vf($dhcpid, 3);
        $result = '';

        if (isset($this->allDhcpNets[$dhcpid])) {
            $dhcpnetdata = $this->getNetworkData($dhcpid);
            $inputs = wf_TextInput('editdhcpconfname', __('DHCP config name'), $dhcpnetdata['confname'], true, 20);
            $inputs .= __('DHCP custom subnet template') . wf_tag('br');
            $inputs .= wf_TextArea('editdhcpconfig', '', $dhcpnetdata['dhcpconfig'], true, '60x10');
            $inputs .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result .= wf_CleanDiv();
            $result .= wf_BackLink(self::URL_ME);
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong'), 'errors');
        }


        return ($result);
    }

    /**
     * Creates new DHCP network
     * 
     * @param int $netid
     * @param string $dhcpconfname
     * 
     * @return void
     */
    public function createNetwork($netid, $dhcpconfname) {
        $netid = vf($netid, 3);
        $dhcpconfname = vf($dhcpconfname);
        $dhcpconfname = trim($dhcpconfname);
        $query = "INSERT INTO `dhcp` (`id` ,`netid` , `dhcpconfig` , `confname`)
                  VALUES (NULL , '" . $netid . "', '', '" . $dhcpconfname . "');";
        nr_query($query);
        $newID = simple_get_lastid('dhcp');
        log_register('DHCP NET CREATE [' . $newID . '] NETWORK [' . $netid . ']');
    }

    /**
     * Updates existing DHCP network handler
     * 
     * @param int $dhcpid
     * @param string $dhcpconfname
     * @param string $dhcpconfig
     * 
     * @return void
     */
    public function updateNetwork($dhcpid, $dhcpconfname, $dhcpconfig) {
        $dhcpid = vf($dhcpid, 3);
        $dhcpconfname = vf($dhcpconfname);
        $dhcpconfname = trim($dhcpconfname);
        $dhcpconfig = mysql_real_escape_string($dhcpconfig);
        $query = "UPDATE `dhcp` SET `dhcpconfig` = '" . $dhcpconfig . "',"
                . "`confname` = '" . $dhcpconfname . "' WHERE `id` ='" . $dhcpid . "';";
        nr_query($query);
        log_register('DHCP NET CHANGE [' . $dhcpid . ']');
    }

    /**
     * Deletes existing DHCP network
     * 
     * @param int $dhcpid
     * 
     * @return void
     */
    public function deleteNetwork($dhcpid) {
        $dhcpid = vf($dhcpid, 3);
        $query = "DELETE from `dhcp` WHERE `id`='" . $dhcpid . "'";
        nr_query($query);
        log_register('DHCP NET DELETE [' . $dhcpid . ']');
    }

    /**
     * Rebuilds all configs and restarts DHCP server
     * 
     * @return void
     */
    public function restartDhcpServer() {
        multinet_rebuild_all_handlers();
    }

}


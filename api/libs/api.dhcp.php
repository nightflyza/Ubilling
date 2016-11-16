<?php

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
        $query = "SELECT * from `dhcp`";
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
        $query = "SELECT * from `networks`";
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
        $cells.= wf_TableCell(__('Network/CIDR'));
        $cells.= wf_TableCell(__('DHCP custom subnet template'));
        $cells.= wf_TableCell(__('DHCP config name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allDhcpNets)) {
            foreach ($this->allDhcpNets as $io => $eachnet) {
                $rowClass = 'row5';
                if (isset($this->allMultinetNets[$eachnet['netid']])) {
                    $cidr = $this->allMultinetNets[$eachnet['netid']]['desc'];
                } else {
                    $cidr = __('Deleted');
                }
                $cells = wf_TableCell($eachnet['id']);
                $cells.= wf_TableCell($cidr);
                $cells.= wf_TableCell(web_bool_led($eachnet['dhcpconfig']));
                $cells.= wf_TableCell($eachnet['confname']);
                $actLinks = wf_JSAlert('?module=dhcp&delete=' . $eachnet['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
                $actLinks.= wf_Link('?module=dhcp&edit=' . $eachnet['id'], web_edit_icon(), false);
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('No available DHCP networks found'), 'info');
        }
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
            if (file_exists(self::MULTINET_PATH . 'dhcpd.conf')) {
                $dhcpdconf = str_replace("\n", '<br>', file_get_contents(self::MULTINET_PATH . 'dhcpd.conf'));
            } else {
                $dhcpdconf = $this->messages->getStyledMessage(__('File not found') . ': dhcpd.conf', 'error');
            }

            $result = wf_modal('dhcpd.conf', 'dhcpd.conf', $dhcpdconf, 'ubButton', 800, 600) . wf_delimiter();

            foreach ($this->allDhcpNets as $io => $eachnet) {
                $subconfname = trim($eachnet['confname']);
                if (file_exists(self::MULTINET_PATH . $subconfname)) {
                    $subconfdata = str_replace("\n", '<br>', file_get_contents(self::MULTINET_PATH . $subconfname));
                } else {
                    $subconfdata = $this->messages->getStyledMessage(__('File not found') . ': ' . $subconfname, 'error');
                }

                $result.=wf_modal($subconfname, $subconfname, $subconfdata, 'ubButton', 800, 600) . wf_delimiter();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('No available DHCP configs found'), 'info');
        }
        return ($result);
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
            foreach ($allTemplates as $each) {
                $templateData = file_get_contents(self::TEMPLATES_PATH . $each);
                $templateData = nl2br($templateData);
                $result.= wf_modal($each, $each, $templateData, 'ubButton', 800, 600) . ' ';
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Returns DHCP network addition form
     * 
     * @return string
     */
    public function addForm() {
        $inputs = multinet_network_selector() . ' ' . __('Network') . wf_tag('br');
        $inputs.= wf_HiddenInput('adddhcp', 'true');
        $inputs.= wf_TextInput('dhcpconfname', __('DHCP config name'), '', true, '20');
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');

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
        $query = "INSERT INTO `dhcp` (
                    `id` ,
                    `netid` ,
                    `dhcpconfig` ,
                    `confname`
                    )
                    VALUES (
                    NULL , '" . $netid . "', '', '" . $dhcpconfname . "'
                    );
             ";
        nr_query($query);
        $newID = simple_get_lastid('dhcp');
        log_register('CREATE DHCPNet [' . $newID . '] NETWORK [' . $netid . ']');
    }

}

?>
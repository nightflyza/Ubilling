<?php

class MultiGen {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains available networks as id=>data
     *
     * @var array
     */
    protected $allNetworks = array();

    /**
     * Contains available NAS servers as id=>data
     *
     * @var array
     */
    protected $allNas = array();

    /**
     * Contains array of NASes served networks as netid=>nas
     *
     * @var array
     */
    protected $networkNases = array();

    /**
     * Contains available nethosts as ip=>data
     *
     * @var array
     */
    protected $allNetHosts = array();

    /**
     * Contains array of nethosts to networks bindings like ip=>netid
     *
     * @var array
     */
    protected $nethostsNetworks = array();

    /**
     * Creates new MultiGen instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->loadNetworks();
        $this->loadNases();
        $this->loadNethosts();
    }

    /**
     * Loads reqired configss
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads existing networks from database
     * 
     * @return void
     */
    protected function loadNetworks() {
        $networksRaw = multinet_get_all_networks();
        if (!empty($networksRaw)) {
            foreach ($networksRaw as $io => $each) {
                $this->allNetworks[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing NAS servers from database
     * 
     * @return void
     */
    protected function loadNases() {
        $nasesRaw = zb_NasGetAllData();
        if (!empty($nasesRaw)) {
            foreach ($nasesRaw as $io => $each) {
                $this->allNas[$each['id']] = $each;
                $this->networkNases[$each['netid']] = $each['id'];
            }
        }
    }

    /**
     * Loads existing nethosts from database
     * 
     * @return void
     */
    protected function loadNethosts() {
        $query = "SELECT * from `nethosts`";
        $nethostsRaw = simple_queryall($query);
        if (!empty($nethostsRaw)) {
            foreach ($nethostsRaw as $io => $each) {
                $this->allNetHosts[$each['ip']] = $each;
                $this->nethostsNetworks[$each['ip']] = $each['netid'];
            }
        }
    }

}

/**
 * Returns list of available free Juniper NASes
 * 
 * @return string
 */
function web_MultigenListClients() {
    $result = __('Nothing found');
    $query = "SELECT * from `mg_clients`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        $cells = wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('NAS name'));
        $cells.= wf_TableCell(__('Radius secret'));
        $rows = wf_TableRow($cells, 'row1');
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each['nasname']);
            $cells.= wf_TableCell($each['shortname']);
            $cells.= wf_TableCell($each['secret']);
            $rows.= wf_TableRow($cells, 'row3');
        }
        $result = wf_TableBody($rows, '100%', '0', 'sortable');
    }

    return ($result);
}

?>
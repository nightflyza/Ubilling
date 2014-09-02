<?php

//
//  signup reporting API
//
/*
 * Base signup requests handling class
 */

class SignupRequests {

    protected $requests = array();
    protected $perpage = 50;
    protected $altcfg = array();

    const URL_WHOIS = 'http://whois.domaintools.com/';

    public function __construct() {
        $this->loadAlter();
    }

    /*
     * loads actual alter config into private property
     * 
     * @return void
     */

    protected function loadAlter() {
        global $ubillingConfig;
        $this->altcfg = $ubillingConfig->getAlter();
        $this->perpage = $this->altcfg['TICKETS_PERPAGE'];
    }

    /*
     * returns available signup requests count
     * 
     * @return int
     */

    protected function getCount() {
        $query = "SELECT COUNT(`id`) from `sigreq`";
        $result = simple_query($query);
        return ($result['COUNT(`id`)']);
    }

    /*
     * loads signup requests into private data property
     * 
     * @return void
     */

    protected function loadRequests($from, $to) {
        $from = vf($from, 3);
        $to = vf($to, 3);
        $query = "SELECT * from `sigreq` ORDER BY `date` DESC LIMIT " . $from . "," . $to . ";";
        $allreqs = simple_queryall($query);

        if (!empty($allreqs)) {
            $this->requests = $allreqs;
        }
    }

    /*
     * renders available signups data
     * 
     * @return void
     */

    public function renderList() {
        $totalcount = $this->getCount();

        if (!wf_CheckGet(array('page'))) {
            $current_page = 1;
        } else {
            $current_page = vf($_GET['page'], 3);
        }

        if ($totalcount > $this->perpage) {
            $paginator = wf_pagination($totalcount, $this->perpage, $current_page, "?module=sigreq", 'ubButton');
            $this->loadRequests($this->perpage * ($current_page - 1), $this->perpage);
        } else {
            $paginator = '';
            $this->loadRequests(0, $this->perpage);
        }
        $result = '';

        $tablecells = wf_TableCell(__('ID'));
        $tablecells.= wf_TableCell(__('Date'));
        $tablecells.= wf_TableCell(__('Full address'));
        $tablecells.= wf_TableCell(__('Real Name'));
        $tablecells.= wf_TableCell(__('Processed'));
        $tablecells.= wf_TableCell(__('Actions'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        if (!empty($this->requests)) {
            foreach ($this->requests as $io => $eachreq) {

                $tablecells = wf_TableCell($eachreq['id']);
                $tablecells.= wf_TableCell($eachreq['date']);
                if (empty($eachreq['apt'])) {
                    $apt = 0;
                } else {
                    $apt = $eachreq['apt'];
                }
                $reqaddr = $eachreq['street'] . ' ' . $eachreq['build'] . '/' . $apt;
                $tablecells.= wf_TableCell($reqaddr);
                $tablecells.= wf_TableCell($eachreq['realname']);
                $tablecells.= wf_TableCell(web_bool_led($eachreq['state']));
                $actlinks = wf_Link('?module=sigreq&showreq=' . $eachreq['id'], 'Show', true, 'ubButton');
                $tablecells.= wf_TableCell($actlinks);
                $tablerows.= wf_TableRow($tablecells, 'row3');
            }
        }

        $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
        $result.=$paginator;

        show_window(__('Available signup requests'), $result);
    }

    /*
     * returns signup request data by selected ID
     * 
     * @param int $requid Existing signup request ID
     * 
     * @return array
     */

    protected function getData($reqid) {
        $requid = vf($reqid, 3);
        $query = "SELECT * from `sigreq` WHERE `id`='" . $reqid . "'";
        $result = simple_query($query);
        return($result);
    }

    /*
     * shows selected signup request by its ID
     * 
     * @param int $requid Existing signup request ID
     * 
     * @return void
     */

    public function showRequest($reqid) {
        $requid = vf($reqid, 3);
        $reqdata = $this->getData($reqid);

        if (empty($reqdata['apt'])) {
            $apt = 0;
        } else {
            $apt = $reqdata['apt'];
        }

        $shortaddress = $reqdata['street'] . ' ' . $reqdata['build'] . '/' . $apt;
        $taskCreateControls = wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), ts_TaskCreateFormSigreq($shortaddress, $reqdata['phone']), '', '420', '500');

        $cells = wf_TableCell(__('Date'));
        $cells.=wf_TableCell($reqdata['date'] . ' ' . $taskCreateControls);
        $rows = wf_TableRow($cells, 'row3');

        $whoislink = self::URL_WHOIS . $reqdata['ip'];
        $iplookup = wf_Link($whoislink, $reqdata['ip'], false, '');

        $cells = wf_TableCell(__('IP'));
        $cells.=wf_TableCell($iplookup);
        $rows.= wf_TableRow($cells, 'row3');

        $reqAddress=$reqdata['street'] . ' ' . $reqdata['build'] . '/' . $apt;
        
        //Construct capability create form if enabled
        if ($this->altcfg['CAPABDIR_ENABLED']) {
            $capabDir=new CapabilitiesDirectory(true);
            $capabCreateForm=$capabDir->createForm($reqAddress, $reqdata['phone'], $reqdata['service'].' '.$reqdata['notes']);
            $capabControl = wf_modal(wf_img('skins/icon_add.gif',__('Available connection capabilities')),__('Create'), $capabCreateForm, '', '400', '300');
        } else {
            $capabControl = '';
        }

        $cells = wf_TableCell(__('Full address'));
        $cells.=wf_TableCell($reqAddress.' ' . $capabControl);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Real Name'));
        $cells.=wf_TableCell($reqdata['realname']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Phone'));
        $cells.=wf_TableCell($reqdata['phone']);
        $rows.= wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Service'));
        $cells.=wf_TableCell($reqdata['service']);
        $rows.=wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Processed'));
        $cells.=wf_TableCell(web_bool_led($reqdata['state']));
        $rows.=wf_TableRow($cells, 'row3');

        $cells = wf_TableCell(__('Notes'));
        $cells.=wf_TableCell($reqdata['notes']);
        $rows.=wf_TableRow($cells, 'row3');

        $result = wf_TableBody($rows, '100%', '0', 'glamour');


        $actlinks = wf_Link('?module=sigreq', __('Back'), false, 'ubButton');
        if ($reqdata['state'] == 0) {
            $actlinks.=wf_Link('?module=sigreq&reqdone=' . $reqid, __('Close'), false, 'ubButton');
        } else {
            $actlinks.=wf_Link('?module=sigreq&requndone=' . $reqid, __('Open'), false, 'ubButton');
        }

        $deletelink = ' ' . wf_JSAlert("?module=sigreq&deletereq=" . $reqid, web_delete_icon(), 'Are you serious');

        show_window(__('Signup request') . ': ' . $reqid . $deletelink, $result);
        show_window('', $actlinks);
    }

    /*
     * Marks signup request as done in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */

    public function setDone($reqid) {
        $requid = vf($reqid, 3);
        simple_update_field('sigreq', 'state', '1', "WHERE `id`='" . $reqid . "'");
        log_register('SIGREQ DONE [' . $reqid . ']');
    }

    /*
     * Marks signup request as undone in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */

    public function setUnDone($reqid) {
        $requid = vf($reqid, 3);
        simple_update_field('sigreq', 'state', '0', "WHERE `id`='" . $reqid . "'");
        log_register('SIGREQ UNDONE [' . $reqid . ']');
    }

    /*
     * Deletes signup request as done in database
     * 
     * @param int $reqid Existing request ID
     * 
     * @return void
     */

    public function deleteReq($reqid) {
        $requid = vf($reqid, 3);
        $query = "DELETE from `sigreq` WHERE `id`='" . $reqid . "'";
        nr_query($query);
        log_register('SIGREQ DELETE [' . $reqid . ']');
    }

    /*
     * Gets all undone requests count, used by taskbar notifier
     * 
     * @return int
     */

    public function getAllNewCount() {
        $query = "SELECT COUNT(`id`) from `sigreq` WHERE `state`='0'";
        $result = simple_query($query);
        $result = $result['COUNT(`id`)'];
        return ($result);
    }

}

?>
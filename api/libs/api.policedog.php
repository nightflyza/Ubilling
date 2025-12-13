<?php

/**
 * Allows control apper of some MAC address in billing reality
 */
class PoliceDog {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing.ini config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Contains all available MAC data as id=>macdata
     *
     * @var array
     */
    protected $macData = array();

    /**
     * Contains all MAC-s to search as mac=>id
     *
     * @var array
     */
    protected $allMacs = array();

    /**
     * Contains actual wanted MAC alerts as id=>data
     *
     * @var array
     */
    protected $alerts = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains MACs already assigned for users as login=>MAC
     *
     * @var array
     */
    protected $usersMacs = array();

    /**
     * Contains police dog database abstraction layer
     * 
     * @var object
     */
    protected $policeDogDb='';
    
    /**
     * Contains police dog alerts database abstraction layer
     * 
     * @var object
     */
    protected $policeDogAlertsDb='';
    
    /**
     * Contains weblogs database abstraction layer
     * 
     * @var object
     */
    protected $weblogsDb='';

    /**
     * Contains pononu database abstraction layer
     * 
     * @var object
     */
    protected $pononuDb='';

    /**
     * Contains fdbarchive database abstraction layer
     * 
     * @var object
     */
    protected $fdbArchiveDb='';


    // some predefined stuff here
    const URL_ME = '?module=policedog';
    const TABLE_POLICEDOG = 'policedog';
    const TABLE_POLICEDOGALERTS = 'policedogalerts';
    const TABLE_WEBLOGS = 'weblogs';
    const TABLE_PONONU = 'pononu';
    const TABLE_FDBARCHIVE = 'fdbarchive';
    const OPTION_DHCP='DHCP_ENABLED';
    const OPTION_PON='PON_ENABLED';

    /**
     * Creates new PoliceDog instance
     */
    public function __construct() {
        $this->loadConfig();
        $this->initDbs();
        $this->loadMacData();
        $this->loadUsersMacs();
        $this->loadAlerts();
        $this->initMessages();
    }

    /**
     * Loads system alter config for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Inits system message helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits database abstraction layers
     * 
     * @return void
     */
    protected function initDbs() {
        $this->policeDogDb = new NyanORM(self::TABLE_POLICEDOG);
        $this->policeDogAlertsDb = new NyanORM(self::TABLE_POLICEDOGALERTS);
        $this->weblogsDb = new NyanORM(self::TABLE_WEBLOGS);
        $this->pononuDb = new NyanORM(self::TABLE_PONONU);
        $this->fdbArchiveDb = new NyanORM(self::TABLE_FDBARCHIVE);
    }

    /**
     * Loads current MAC-s data into protected property
     * 
     * @return void
     */
    protected function loadMacData() {
        $all = $this->policeDogDb->getAll();
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->macData[$each['id']] = $each;
                $this->allMacs[$each['mac']] = $each['id'];
            }
        }
    }

    /**
     * Loads available police dog alerts
     * 
     * @return void
     */
    protected function loadAlerts() {
        $this->alerts = $this->policeDogAlertsDb->getAll('id');
    }

    /**
     * Loads available users macs into database
     * 
     * @rerutn void
     */
    protected function loadUsersMacs() {
        $all = zb_UserGetAllMACs();
        if (!empty($all)) {
            $this->usersMacs = array_flip($all);
        }
    }

    /**
     * Renders module control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        $result.= wf_modalAuto(web_icon_create() . ' ' . __('Upload new MACs'), __('Upload new MACs'), $this->renderUploadForm(), 'ubButton');
        $result.= wf_Link(self::URL_ME, wf_img('skins/undone_icon.png') . ' ' . __('Wanted MAC database'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&show=fastscan', wf_img('skins/icon_search_small.gif') . ' ' . __('Fast scan'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&show=deepscan', wf_img('skins/track_icon.png') . ' ' . __('Deep scan'), false, 'ubButton');
        return ($result);
    }

    /**
     * Renders MAC uploading form
     * 
     * @return string
     */
    protected function renderUploadForm() {
        $result = '';
        $inputs = __('One MAC address per line') . wf_tag('br');
        $inputs.= wf_TextArea('newmacupload', '', '', true, '50x10');
        $inputs.= wf_TextInput('newnotes', __('Notes'), '', true, '40');
        $inputs.= wf_Submit(__('Upload'));
        $result.= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders wanted MAC addresses database
     * 
     * @return string
     */
    public function renderWandedMacList() {
        $result = '';
        $columns = array(__('ID'), __('Date'), __('MAC'), __('Notes'), __('Actions'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result.= wf_JqDtLoader($columns, self::URL_ME . '&show=ajwlist', false, __('MAC'), 50, $opts);
        return ($result);
    }

    /**
     * Deletes some MAC from database
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteWantedMac($id) {
        $id = ubRouting::filters($id, 'int');
        if (isset($this->macData[$id])) {
            $deleteMac = $this->macData[$id]['mac'];
            $this->policeDogDb->where('id','=',$id);
            $this->policeDogDb->delete();
            log_register('POLICEDOG DELETE MAC `' . $deleteMac . '`');
        }
    }

    /**
     * Renders ajax data reply with available MAC data list
     * 
     * @return void
     */
    public function renderWantedMacListAjaxReply() {
        $result = '';
        $jsonAAData = array();

        if (!empty($this->macData)) {
            foreach ($this->macData as $io => $each) {
                $jsonItem = array();
                $jsonItem[] = $each['id'];
                $jsonItem[] = $each['date'];
                $jsonItem[] = $each['mac'];
                $jsonItem[] = $each['notes'];
                $actLinks = wf_JSAlert(self::URL_ME . '&delmacid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $jsonItem[] = $actLinks;
                $jsonAAData[] = $jsonItem;
            }
        }

        $result = array("aaData" => $jsonAAData);
        die(json_encode($result));
        die($result);
    }

    /**
     * Creates new MAC address database records
     * 
     * @return void/string
     */
    public function catchCreateMacRequest() {
        $result = '';
        $count = 0;
        if (ubRouting::checkPost('newmacupload')) {
                $macsRaw = explodeRows(ubRouting::post('newmacupload'));
                if (!empty($macsRaw)) {
                    $curDate = curdatetime();
                    $newNotes = '';
                    if (ubRouting::checkPost('newnotes')) {
                        $newNotes = ubRouting::post('newnotes', 'mres');
                    }

                    foreach ($macsRaw as $io => $eachmac) {
                        $insertMac = trim($eachmac);
                        $insertMac = ubRouting::filters($insertMac, 'mres');
                        $insertMac = strtolower_utf8($insertMac);
                        if (!empty($insertMac)) {
                            if (check_mac_format($insertMac)) {
                                if (!isset($this->allMacs[$insertMac])) {
                                    $this->policeDogDb->data('date', $curDate);
                                    $this->policeDogDb->data('mac', $insertMac);
                                    $this->policeDogDb->data('notes', $newNotes);
                                    $this->policeDogDb->create();
                                    $count++;
                                } else {
                                    $result.= $this->messages->getStyledMessage(__('MAC duplicate') . ': ' . $insertMac, 'warning');
                                }
                            } else {
                                $result.= $this->messages->getStyledMessage(__('This MAC have wrong format') . ': ' . $insertMac, 'error');
                            }
                        }
                    }
                    log_register('POLICEDOG UPLOAD `' . $count . '` MAC');
                }
            
        }
        return ($result);
    }

    /**
     * Checks is MAC already alerted or not?
     * 
     * @param string $mac
     * 
     * @return bool
     */
    protected function isNotAlertedYet($mac) {
        $result = true;
        if (!empty($this->alerts)) {
            foreach ($this->alerts as $io => $each) {
                if ($each['mac'] == $mac) {
                    $result = false;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Performs fast database scan for wanted MAC addresses
     * 
     * @return void
     */
    public function fastScan() {
        $curDate = curdatetime();
        if (!empty($this->allMacs)) {
            foreach ($this->allMacs as $eachmac => $eachId) {
                if (isset($this->usersMacs[$eachmac])) {
                    $detectedLogin = $this->usersMacs[$eachmac];
                    if ($this->isNotAlertedYet($eachmac)) {
                        $this->policeDogAlertsDb->data('date', $curDate);
                        $this->policeDogAlertsDb->data('mac', $eachmac);
                        $this->policeDogAlertsDb->data('login', $detectedLogin);
                        $this->policeDogAlertsDb->create();
                        log_register('POLICEDOG MAC `' . $eachmac . '` ALERT `' . $detectedLogin . '`');
                    }
                }
            }
        }
    }

    /**
     * Renders fast scan interface with current alerts
     * 
     * @return string
     */
    public function renderFastScan() {
        $result = '';
        
        if (!empty($this->alerts)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('MAC'));
            $cells.= wf_TableCell(__('Assigned'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->alerts as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['mac']);
                $assignedLabel ='';
                if (!empty($each['login'])) {
                 $assignedLabel = wf_Link(UserProfile::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                }
                $cells.= wf_TableCell($assignedLabel);
                $actLinks = wf_JSAlertStyled(self::URL_ME . '&delalertid=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert(), '');
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result.= $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }

        $result.=wf_delimiter();
        $result.=wf_Link(self::URL_ME . '&show=fastscan&forcefast=true', wf_img('skins/refresh.gif') . ' ' . __('Renew'), true, 'ubButton');
        return ($result);
    }

    /**
     * Deletes existing alert from database
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteAlert($id) {
        $id = vf($id, 3);
        if (isset($this->alerts[$id])) {
            $alertData = $this->alerts[$id];
            $this->policeDogAlertsDb->where('id','=',$id);
            $this->policeDogAlertsDb->delete();
            log_register('POLICEDOG DELETE ALERT [' . $id . '] MAC `' . $alertData['mac'] . '`');
        }
    }

    /**
     * Performs and renders deep scan results
     * 
     * @return string
     */
    public function renderDeepScan() {
        set_time_limit(0);
        $result = '';
        if (!empty($this->allMacs)) {
            //nethosts scanning
            $nethostsAlerts = '';
            $nethostsAlertsTmp = array();
            if (!empty($this->allMacs)) {
                foreach ($this->allMacs as $eachmac => $eachId) {
                    if (isset($this->usersMacs[$eachmac])) {
                        if (!isset($nethostsAlertsTmp[$eachmac])) {
                            $nethostsAlerts.= $this->messages->getStyledMessage(__('Wanted MAC assigned to user') . ': ' . $eachmac, 'error');
                            $nethostsAlertsTmp[$eachmac] = $eachmac;
                        }
                    }
                }

                if (!empty($nethostsAlerts)) {
                    $result.=$nethostsAlerts;
                } else {
                    $result.=$this->messages->getStyledMessage(__('No wanted MAC assigned to existing users'), 'success');
                }
            }

            //DHCP logs parsing
            if ($this->altCfg[self::OPTION_DHCP]) {
            $cat_path = $this->billCfg['CAT'];
            $sudo_path = $this->billCfg['SUDO'];
            $tail_path = $this->billCfg['TAIL'];
            $leasefile = $this->altCfg['NMLEASES'];
            $command = $sudo_path . ' ' . $cat_path . ' ' . $leasefile . ' | ' . $tail_path . ' -n 10000';
            $rawDhcp = shell_exec($command);
            $dhcpAlerts = '';
            $dhcpAlertsTmp = array();
            if (!empty($rawDhcp)) {
                $rawDhcp = explodeRows($rawDhcp);
                if (!empty($rawDhcp)) {
                    foreach ($rawDhcp as $eachLine) {
                        $macExtract = zb_ExtractMacAddress($eachLine);
                        if (!empty($macExtract)) {
                            if (isset($this->allMacs[$macExtract])) {
                                if (!isset($dhcpAlertsTmp[$macExtract])) {
                                    $dhcpAlerts.=$this->messages->getStyledMessage(__('DHCP request from') . ': ' . $macExtract, 'error');
                                    $dhcpAlertsTmp[$macExtract] = $macExtract;
                                }
                            }
                        }
                    }
                }

                if (!empty($dhcpAlerts)) {
                    $result.=$dhcpAlerts;
                } else {
                    $result.=$this->messages->getStyledMessage(__('No wanted MAC DHCP requests detected'), 'success');
                }
            }
            }

            //FDB cache processing
            $fdbCachePath = 'exports/';
            $fdbAlerts = '';
            $fdbMacTmp = array();
            $fdbAlertsTmp = array();
            $allFdb = rcms_scandir($fdbCachePath, '*_fdb');
            if (!empty($allFdb)) {
                foreach ($allFdb as $io => $eachFdbFile) {
                    $fdbData = file_get_contents($fdbCachePath . $eachFdbFile);
                    $fdbData = unserialize($fdbData);
                    if (!empty($fdbData)) {
                        foreach ($fdbData as $fdbmac => $port) {
                            $fdbMacTmp[$fdbmac] = $fdbmac;
                        }
                    }
                }

                if (!empty($fdbMacTmp)) {
                    foreach ($fdbMacTmp as $io => $eachFdbMac) {
                        if (isset($this->allMacs[$eachFdbMac])) {
                            if (!isset($fdbAlertsTmp[$eachFdbMac])) {
                                $fdbAlerts.=$this->messages->getStyledMessage(__('Wanted MAC occurs in FDB') . ': ' . $eachFdbMac, 'error');
                                $fdbAlersTmp[$eachFdbMac] = $eachFdbMac;
                            }
                        }
                    }
                }
            }

            if (!empty($fdbAlerts)) {
                $result.= $fdbAlerts;
            } else {
                $result.= $this->messages->getStyledMessage(__('No wanted MAC in FDB cache detected'), 'success');
            }


            //FDB archive processing
            $fdbArchiveAlerts = '';
            $fdbArchiveRaw = $this->fdbArchiveDb->getAll();
            if (!empty($fdbArchiveRaw)) {
                foreach ($fdbArchiveRaw as $io => $eachFdbArchiveRec) {
                    foreach ($this->allMacs as $eachMac => $eachId) {
                        if (ispos($eachFdbArchiveRec['data'], $eachMac)) {
                            $occurParams='';
                            $occurParams.= '('.$eachFdbArchiveRec['date'].' '.__('on').' '.$eachFdbArchiveRec['devip'].')';
                            $fdbArchiveAlerts.= $this->messages->getStyledMessage(__('Wanted MAC occurs in').' '.__('FDB').' '.__('Archive')  . ': ' . $eachMac . ' ' . $occurParams, 'error');
                        }
                    }
                }
            }

            if (!empty($fdbArchiveAlerts)) {
                $result.= $fdbArchiveAlerts;
            } else {
                $result.= $this->messages->getStyledMessage(__('No wanted MAC dectected in').' '.__('FDB').' '.__('Archive'), 'success');
            }

            //weblogs assigns parsing
            $logAlerts = '';
            $logAlertsTmp = array();
            $this->weblogsDb->where('event','not like','%POLICEDOG%');
            $this->weblogsDb->where('event','like','%MAC%');
            $this->weblogsDb->selectable('event');
            $weblogsRaw = $this->weblogsDb->getAll();
            if (!empty($weblogsRaw)) {
                foreach ($weblogsRaw as $io => $eachEvent) {
                    $macExtract = zb_ExtractMacAddress($eachEvent['event']);
                    if (!empty($macExtract)) {
                        if (isset($this->allMacs[$macExtract])) {
                            if (!isset($logAlertsTmp[$macExtract])) {
                                $logAlerts.= $this->messages->getStyledMessage(__('Wanted MAC occurs in event logs') . ': ' . $macExtract, 'error');
                                $logAlertsTmp[$macExtract] = $macExtract;
                            }
                        }
                    }
                }
            }
            if (!empty($logAlerts)) {
                $result.=$logAlerts;
            } else {
                $result.= $this->messages->getStyledMessage(__('No wanted MAC in event logs detected'), 'success');
            }

            //PON devices processing
            if ($this->altCfg[self::OPTION_PON]) {
                $ponAlerts = '';
                $ponAlertsTmp = array();
                $ponRaw = $this->pononuDb->getAll();
                if (!empty($ponRaw)) {
                    foreach ($ponRaw as $io => $eachPonMac) {
                        $eachPonMac = $eachPonMac['mac'];
                        if (isset($this->allMacs[$eachPonMac])) {
                            if (!isset($ponAlertsTmp[$eachPonMac])) {
                                $ponAlerts.= $this->messages->getStyledMessage(__('Wanted MAC occurs in PON ONU devices') . ': ' . $eachPonMac, 'error');
                                $ponAlertsTmp[$eachPonMac] = $eachPonMac;
                            }
                        }
                    }
                }
                if (!empty($ponAlerts)) {
                    $result.= $ponAlerts;
                } else {
                    $result.= $this->messages->getStyledMessage(__('No wanted MAC in PON ONU  devices detected'), 'success');
                }
            }
        } else {
            $result.= $this->messages->getStyledMessage(__('Wanted MAC database') . ': ' . __('No'), 'warning');
        }
        return ($result);
    }

}


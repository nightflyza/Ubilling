<?php

/*
 * Agent assigns report class
 */

class agentAssignReport {

    protected $allassigns = array();
    protected $allassignsstrict = array();
    protected $assigns = array();
    protected $agents = array();
    protected $agentsNamed = array();
    protected $users = array();
    protected $alladdress = array();
    protected $excludeTariffs = array();
    protected $altcfg = array();
    protected $agentsumm = array();
    protected $agentPrint = array();
    protected $userTariffs = array();
    protected $userContracts = array();
    protected $userRealnames = array();
    protected $cashtypes = array();
    protected $excludeCount = 0;
    protected $excludeSumm = 0;

    const EXPORT_PATH = './exports/';
    const PRINT_TEMPLATE = './config/printableheaders.tpl';

    public function __construct() {
        $this->loadAllAssigns();
        $this->loadAllAssignsStrict();
        $this->loadUsers();
        $this->loadTariffExcludes();
        $this->excludeUsersTariffMask();
        $this->loadAgents();
        $this->agentsPreprocessNamed();
        $this->loadAddress();
        $this->assignsPreprocess();
    }

    /**
     * loads available assigns from database into private prop
     * 
     * @return void
     */
    protected function loadAllAssigns() {
        $this->allassigns = zb_AgentAssignGetAllData();
    }

    /**
     * loads available assigns from database into private prop
     * 
     * @return void
     */
    protected function loadAllAssignsStrict() {
        $this->allassignsstrict = zb_AgentAssignStrictGetAllData();
    }

    /**
     * loads all available users logins into private prop
     * 
     * @return void
     */
    protected function loadUsers() {
        $this->users = zb_UserGetAllStargazerData();
    }

    /**
     * Load tariff excludes from config
     * 
     * @return void
     */
    protected function loadTariffExcludes() {
        global $ubillingConfig;
        $this->altcfg = $ubillingConfig->getAlter();
        if (isset($this->altcfg['AGENT_ASSIGN_EXCLUDE_TARIFFS'])) {
            if (!empty($this->altcfg['AGENT_ASSIGN_EXCLUDE_TARIFFS'])) {
                $this->excludeTariffs = explode(',', $this->altcfg['AGENT_ASSIGN_EXCLUDE_TARIFFS']);
            }
        }
    }

    /**
     * Excludes users in private users property by tariff mask
     * 
     * @return void 
     */
    protected function excludeUsersTariffMask() {
        if (!empty($this->users)) {
            if (!empty($this->excludeTariffs)) {
                foreach ($this->users as $io => $eachUser) {
                    foreach ($this->excludeTariffs as $ia => $eachTariffMask) {
                        if (ispos($eachUser['Tariff'], $eachTariffMask)) {
                            unset($this->users[$io]);
                        }
                    }
                }
            }
        }
    }

    /**
     * loads contragent data into protected prop
     * 
     * @return void
     */
    protected function loadAgents() {
        $tmpArr = array();
        $tmpArr = zb_ContrAhentGetAllData();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->agents[$each['id']] = $each;
            }
        }
    }

    /**
     * load all user address into private prop
     * 
     * @return void
     */
    protected function loadAddress() {
        $this->alladdress = zb_AddressGetFullCityaddresslist();
    }

    /**
     * preprocess all users into assigns private prop
     * 
     * @return void
     */
    protected function assignsPreprocess() {
        if (!empty($this->users)) {
            foreach ($this->users as $userid => $eachuser) {
                $assignedAgentId = zb_AgentAssignCheckLoginFast($eachuser['login'], $this->allassigns, @$this->alladdress[$eachuser['login']], $this->allassignsstrict);
                if (!empty($assignedAgentId)) {
                    $this->assigns[$eachuser['login']] = $assignedAgentId;
                } else {
                    $this->assigns[$eachuser['login']] = '';
                }
            }
        }
    }

    /**
     * public getter for private assigns property
     * 
     * @return array
     */
    public function getAssigns() {
        return ($this->assigns);
    }

    /**
     * preprocess available agents into labeled private prop
     * 
     * @return void
     */
    protected function agentsPreprocessNamed() {
        if (!empty($this->agents)) {
            foreach ($this->agents as $io => $each) {
                $this->agentsNamed[$each['id']] = $each['contrname'];
            }
        }
    }

    /**
     * public getter for named agents
     * 
     * @return array
     */
    public function getAgentsNamed() {
        return ($this->agentsNamed);
    }

    /**
     * returns payments search form
     * 
     * @return string
     */
    public function paymentSearchForm() {
        //try to save calendar states
        if (wf_CheckPost(array('datefrom', 'dateto'))) {
            $curdate = $_POST['dateto'];
            $yesterday = $_POST['datefrom'];
        } else {
            $curdate = date("Y-m-d", time() + 60 * 60 * 24);
            $yesterday = curdate();
        }

        //try to save cashtype selector state
        if (wf_CheckPost(array('cashtypeid'))) {
            $currentCashtypeId = $_POST['cashtypeid'];
        } else {
            //cash money by default
            $currentCashtypeId = 1;
        }

        $allcashtypes = zb_CashGetAlltypes();
        $cashTypesArr = array();
        if (!empty($allcashtypes)) {
            foreach ($allcashtypes as $io => $each) {
                $cashTypesArr[$each['id']] = __($each['cashtype']);
            }
        }
        $cashTypesArr['any'] = __('Any');

        $inputs = __('Date');
        $inputs.= wf_DatePickerPreset('datefrom', $yesterday) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', $curdate) . ' ' . __('To') . ' ';
        $inputs.= wf_Selector('cashtypeid', $cashTypesArr, __('Cash type'), $currentCashtypeId, false);
        $inputs.= wf_HiddenInput('dosearch', 'true');
        $inputs.= wf_Submit(__('Search'));

        $result = wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * loads all users tariffs from database
     * 
     * @return void
     */
    protected function loadUserTariffs() {
        $this->userTariffs = zb_TariffsGetAllUsers();
    }

    /**
     * loads all user contracts 
     * 
     * @return void
     */
    protected function loadUserContracts() {
        $this->userContracts = zb_UserGetAllContracts();
        $this->userContracts = array_flip($this->userContracts);
    }

    /**
     * loads all users realnames
     * 
     * @return void
     */
    protected function loadUserRealnames() {
        $this->userRealnames = zb_UserGetAllRealnames();
    }

    /**
     * loads available cash types
     * 
     * @return void
     */
    protected function loadCashTypes() {
        $tmpArr = zb_CashGetAlltypes();
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->cashtypes[$each['id']] = $each['cashtype'];
            }
        }
    }

    /**
     * fill agentstats by some payments
     * 
     * @param string $login Existing ubilling user login
     * @param float  $summ Payment summ
     * 
     * @return void
     */
    protected function fillAgentStats($login, $summ) {
        if (isset($this->assigns[$login])) {
            $agentId = $this->assigns[$login];
            if (isset($this->agentsumm[$agentId])) {
                $this->agentsumm[$agentId]['summ'] = $this->agentsumm[$agentId]['summ'] + $summ;
                $this->agentsumm[$agentId]['count'] = $this->agentsumm[$agentId]['count'] + 1;
            } else {
                $this->agentsumm[$agentId]['summ'] = $summ;
                $this->agentsumm[$agentId]['count'] = 1;
            }
        } else {
            //excluded cash counters
            $this->excludeCount++;
            $this->excludeSumm = $this->excludeSumm + $summ;
        }
    }

    /**
     * Prepares per-agent CSV data for future printing 
     * 
     * @return void
     */
    protected function fillPrintData($payment) {
        if (isset($this->assigns[$payment['login']])) {
            $this->agentPrint[$this->assigns[$payment['login']]][] = $payment;
        }
    }

    /**
     * stores private agentPrint property for future printing and download
     * filename: self::EXPORT_PATH.'report_agentfinance.printdataraw'
     * 
     * @return void
     */
    protected function savePrintData() {
        if (!empty($this->agentPrint)) {
            $arrayToStore = serialize($this->agentPrint);
            file_put_contents(self::EXPORT_PATH . 'report_agentfinance.prindataraw', $arrayToStore);
        }
    }

    /**
     * form printable result by default tablestyle, template stores in self::PRINT_TEMPLATE
     * 
     * @param string $data Raw html data to preprocess
     * @param string $title Replaces macro {PAGE_TITLE}
     * 
     * @return string
     */
    protected function parsePrintable($data, $title = '') {
        if (file_exists(self::PRINT_TEMPLATE)) {
            $template = file_get_contents(self::PRINT_TEMPLATE);
            $template = str_replace('{PAGE_TITLE}', $title, $template);
            $result = $template . $data;
        } else {
            $result = $data;
        }
        return ($result);
    }

    /**
     * extracts data from agentPring cache for future printing in HTML
     * 
     * @param int $agentid Existing agent ID in database
     * 
     * @return void
     */
    public function exportHtml($agentid) {
        $tmpArr = array();
        $result = '';

        if (!empty($this->altcfg)) {
            $altercfg = $this->altcfg;
        } else {
            global $ubillingConfig;
            $this->altcfg = $ubillingConfig->getAlter();
            $altercfg = $this->altcfg;
        }


        if (file_exists(self::EXPORT_PATH . 'report_agentfinance.prindataraw')) {
            $rawData = file_get_contents(self::EXPORT_PATH . 'report_agentfinance.prindataraw');
            $tmpArr = unserialize($rawData);
            $allservicenames = zb_VservicesGetAllNamesLabeled();
            $this->loadUserRealnames();
            $this->loadCashTypes();
            if (!empty($tmpArr)) {
                if (isset($tmpArr[$agentid])) {
                    if (!empty($tmpArr[$agentid])) {
                        //table header
                        $result.=wf_tag('h2') . @$this->agentsNamed[$agentid] . wf_tag('h2', true);
                        $cells = wf_TableCell(__('ID'));
                        $cells.= wf_TableCell(__('Date'));
                        $cells.= wf_TableCell(__('Cash'));
                        $cells.= wf_TableCell(__('Login'));
                        if ($altercfg['FINREP_CONTRACT']) {
                            $this->loadUserContracts();
                            $cells.= wf_TableCell(__('Contract'));
                        }
                        $cells.= wf_TableCell(__('Full address'));
                        $cells.= wf_TableCell(__('Real Name'));
                        if ($altercfg['FINREP_TARIFF']) {
                            $this->loadUserTariffs();
                            $cells.= wf_TableCell(__('Tariff'));
                        }
                        $cells.= wf_TableCell(__('Contrahent name'));
                        $cells.= wf_TableCell(__('Payment type'));
                        $cells.= wf_TableCell(__('Notes'));
                        $cells.= wf_TableCell(__('Admin'));
                        $rows = wf_TableRow($cells, 'row1');

                        foreach ($tmpArr[$agentid] as $io => $each) {
                            $cells = wf_TableCell($each['id']);
                            $cells.= wf_TableCell($each['date']);
                            $cells.= wf_TableCell($each['summ']);
                            $cells.= wf_TableCell($each['login']);
                            if ($altercfg['FINREP_CONTRACT']) {
                                $cells.= wf_TableCell($this->userContracts[$each['login']]);
                            }
                            $cells.= wf_TableCell(@$this->alladdress[$each['login']]);
                            $cells.= wf_TableCell(@$this->userRealnames[$each['login']]);
                            if ($altercfg['FINREP_TARIFF']) {
                                $cells.= wf_TableCell(@$this->userTariffs[$each['login']]);
                            }
                            $cells.= wf_TableCell(@$this->agentsNamed[$this->assigns[$each['login']]]);
                            $cells.= wf_TableCell(__(@$this->cashtypes[$each['cashtypeid']]));
                            //payment notes translation
                            if ($altercfg['TRANSLATE_PAYMENTS_NOTES']) {
                                $paynote = zb_TranslatePaymentNote($each['note'], $allservicenames);
                            } else {
                                $paynote = $each['note'];
                            }
                            $cells.= wf_TableCell($paynote);
                            $cells.= wf_TableCell($each['admin']);
                            $rows.= wf_TableRow($cells, 'row3');
                        }

                        $result.=wf_TableBody($rows, '100%', 1, 'printable');
                        //adds ending for templating
                        $result.=wf_tag('body', true);
                        $result.=wf_tag('html', true);
                    }
                }
            }
        }
        $result = $this->parsePrintable($result, @$this->agentsNamed[$agentid]);
        die($result);
    }

    /**
     * extracts data from agentPring cache for future export in CSV
     * 
     * @param int $agentid Existing agent ID in database
     * 
     * @return void
     */
    public function exportCSV($agentid) {
        $tmpArr = array();
        $result = '';

        if (!empty($this->altcfg)) {
            $altercfg = $this->altcfg;
        } else {
            global $ubillingConfig;
            $this->altcfg = $ubillingConfig->getAlter();
            $altercfg = $this->altcfg;
        }


        if (file_exists(self::EXPORT_PATH . 'report_agentfinance.prindataraw')) {
            $rawData = file_get_contents(self::EXPORT_PATH . 'report_agentfinance.prindataraw');
            $tmpArr = unserialize($rawData);
            $allservicenames = zb_VservicesGetAllNamesLabeled();
            $this->loadUserRealnames();
            $this->loadCashTypes();
            if (!empty($tmpArr)) {
                if (isset($tmpArr[$agentid])) {
                    if (!empty($tmpArr[$agentid])) {
                        //CSV header
                        $result.=__('ID') . ';' . __('Date') . ';' . __('Cash') . ';' . __('Login') . ';' . __('Full address') . ';' . __('Real Name') . ';' . __('Contrahent name') . ';' . __('Payment type') . ';' . __('Notes') . ';' . __('Admin') . "\n";
                        //CSV data
                        foreach ($tmpArr[$agentid] as $io => $each) {
                            $summ = str_replace('.', ',', $each['summ']); //need for normal summ in excel
                            $result.=$each['id'] . ';' . $each['date'] . ';' . $summ . ';' . $each['login'] . ';' . @$this->alladdress[$each['login']] . ';' . @$this->userRealnames[$each['login']] . ';' . @$this->agentsNamed[$this->assigns[$each['login']]] . ';' . __(@$this->cashtypes[$each['cashtypeid']]) . ';' . zb_TranslatePaymentNote($each['note'], $allservicenames) . ';' . $each['admin'] . "\n";
                        }
                    }
                }
            }
        }
        $saveCsvName = self::EXPORT_PATH . 'report_agentfinance_' . $agentid . '_' . zb_rand_string(8) . '.csv';
        $result = iconv('utf-8', 'windows-1251', $result);
        file_put_contents($saveCsvName, $result);
        zb_DownloadFile($saveCsvName, 'csv');
        die();
    }

    /**
     * extracts data from agentPring cache for future export in CSV
     * 
     * @param int $agentid Existing agent ID in database
     * 
     * @return void
     */
    public function exportCSV2($agentid) {
        $tmpArr = array();
        $result = '';
        $shortAddres = zb_AddressGetFulladdresslistCached();

        if (!empty($this->altcfg)) {
            $altercfg = $this->altcfg;
        } else {
            global $ubillingConfig;
            $this->altcfg = $ubillingConfig->getAlter();
            $altercfg = $this->altcfg;
        }


        if (file_exists(self::EXPORT_PATH . 'report_agentfinance.prindataraw')) {
            $rawData = file_get_contents(self::EXPORT_PATH . 'report_agentfinance.prindataraw');
            $tmpArr = unserialize($rawData);
            $allservicenames = zb_VservicesGetAllNamesLabeled();
            $this->loadUserRealnames();
            $this->loadCashTypes();
            if (!empty($tmpArr)) {
                if (isset($tmpArr[$agentid])) {
                    if (!empty($tmpArr[$agentid])) {
                        //CSV header
                        $result.= __('Date') . ';' . __('Cash') . ';' . __('Full address') . ';' . __('Real Name') . ';' . __('Notes') . "\n";
                        //CSV data
                        foreach ($tmpArr[$agentid] as $io => $each) {
                            $summ = str_replace('.', ',', $each['summ']); //need for normal summ in excel
                            $timeStamp = strtotime($each['date']);
                            $newDate = date("Y-m-d", $timeStamp);
                            $result.=$newDate . ';' . $summ . ';' . @$shortAddres[$each['login']] . ';' . @$this->userRealnames[$each['login']] . ';' . zb_TranslatePaymentNote($each['note'], $allservicenames) . "\n";
                        }
                    }
                }
            }
        }
        $saveCsvName = self::EXPORT_PATH . 'report_agentfinance_' . $agentid . '_' . zb_rand_string(8) . '.csv';
        $result = iconv('utf-8', 'windows-1251', $result);
        file_put_contents($saveCsvName, $result);
        zb_DownloadFile($saveCsvName, 'csv');
        die();
    }

    /**
     * do the payments search via some data interval
     * 
     * @return string
     */
    public function paymentSearch($datefrom, $dateto, $cashtypeid) {

        if (!empty($this->altcfg)) {
            $altercfg = $this->altcfg;
        } else {
            global $ubillingConfig;
            $this->altcfg = $ubillingConfig->getAlter();
            $altercfg = $this->altcfg;
        }

        $datefrom = mysql_real_escape_string($datefrom);
        $dateto = mysql_real_escape_string($dateto);
        $this->loadUserRealnames();
        $this->loadCashTypes();
        $allservicenames = zb_VservicesGetAllNamesLabeled();
        $result = '';
        if ($cashtypeid != 'any') {
            $cashtypeid = vf($cashtypeid, 3);
            $cashtypeSub_q = "`cashtypeid`='" . $cashtypeid . "' AND ";
        } else {
            $cashtypeSub_q = '';
        }

        $query = "SELECT * from `payments` WHERE " . $cashtypeSub_q . " `date`  BETWEEN '" . $datefrom . "' AND '" . $dateto . "' AND `summ`> '0' ;";
        $allPayments = simple_queryall($query);
        $totalCount = 0;
        $totalSumm = 0;

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('Login'));
        if ($altercfg['FINREP_CONTRACT']) {
            $this->loadUserContracts();
            $cells.= wf_TableCell(__('Contract'));
        }
        $cells.= wf_TableCell(__('Full address'));
        $cells.= wf_TableCell(__('Real Name'));
        if ($altercfg['FINREP_TARIFF']) {
            $this->loadUserTariffs();
            $cells.= wf_TableCell(__('Tariff'));
        }
        $cells.= wf_TableCell(__('Contrahent name'));
        $cells.= wf_TableCell(__('Payment type'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Admin'));
        $rows = wf_TableRow($cells, 'row1');


        if (!empty($allPayments)) {
            foreach ($allPayments as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($each['date']);
                $cells.= wf_TableCell($each['summ']);
                $cells.= wf_TableCell(wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ' . $each['login'], false, ''));
                if ($altercfg['FINREP_CONTRACT']) {
                    $cells.= wf_TableCell($this->userContracts[$each['login']]);
                }
                $cells.= wf_TableCell(@$this->alladdress[$each['login']]);
                $cells.= wf_TableCell(@$this->userRealnames[$each['login']]);
                if ($altercfg['FINREP_TARIFF']) {
                    $cells.= wf_TableCell(@$this->userTariffs[$each['login']]);
                }
                $cells.= wf_TableCell(@$this->agentsNamed[$this->assigns[$each['login']]]);
                $cells.= wf_TableCell(__(@$this->cashtypes[$each['cashtypeid']]));
                //payment notes translation
                if ($altercfg['TRANSLATE_PAYMENTS_NOTES']) {
                    $paynote = zb_TranslatePaymentNote($each['note'], $allservicenames);
                } else {
                    $paynote = $each['note'];
                }
                $cells.= wf_TableCell($paynote);
                $cells.= wf_TableCell($each['admin']);
                $rows.= wf_TableRow($cells, 'row3');

                //fill stats
                $this->fillAgentStats($each['login'], $each['summ']);
                $this->fillPrintData($each);
                $totalCount++;
                $totalSumm = $totalSumm + $each['summ'];
            }
        }

        //show per agent stats
        if (!empty($this->agentsumm)) {
            $agCells = wf_TableCell(__('Contrahent name'));
            $agCells.= wf_TableCell(__('Count'));
            $agCells.= wf_TableCell(__('Sum'));
            $agCells.= wf_TableCell(__('Actions'));
            $agRows = wf_TableRow($agCells, 'row1');

            foreach ($this->agentsumm as $eachAgentId => $eachAgentStat) {
                $exportControls = wf_Link("?module=report_agentfinance&exportcsvagentid=" . $eachAgentId, wf_img('skins/excel.gif', __('Export') . ' ' . __('full')), false, '').' ';
                $exportControls.= wf_Link("?module=report_agentfinance&exportcsvagentidshort=" . $eachAgentId, wf_img('skins/excel.gif', __('Export') . ' ' . __('short')), false, '').' ';
                $exportControls.= wf_Link("?module=report_agentfinance&exporthtmlagentid=" . $eachAgentId, wf_img('skins/icon_print.png', __('Print')), false, '');
                $agCells = wf_TableCell($this->agentsNamed[$eachAgentId]);
                $agCells.= wf_TableCell($eachAgentStat['count']);
                $agCells.= wf_TableCell($eachAgentStat['summ']);
                $agCells.= wf_TableCell($exportControls);
                $agRows.= wf_TableRow($agCells, 'row3');
            }

            $result.=wf_TableBody($agRows, '50%', 0, 'sortable');
            $result.=wf_tag('span', false, 'glamour') . __('Excluded payments count') . ': ' . $this->excludeCount . wf_tag('span', true);
            $result.=wf_tag('span', false, 'glamour') . __('Excluded cash') . ': ' . $this->excludeSumm . wf_tag('span', true);

            //save per agent printing data for future usage
            $this->savePrintData();
        }

        $result.= wf_TableBody($rows, '100%', 0, 'sortable');
        $result.=wf_tag('span', false, 'glamour') . __('Count') . ': ' . $totalCount . wf_tag('span', true);
        $result.=wf_tag('span', false, 'glamour') . __('Total payments') . ': ' . $totalSumm . wf_tag('span', true);
        return ($result);
    }

}

?>
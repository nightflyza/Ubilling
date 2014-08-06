<?php

if (cfr('SCREPORT')) {

    $altCfg=$ubillingConfig->getAlter();
    if ($altCfg['SCREP_ENABLED']) { 
    
    class ReportSelfCredit {

        protected $data = array();
        protected $chartdata = '';
        protected $tabledata='';
        protected $curyear=0;
        protected $yearsumm=0;
        protected $usertariffs=array();
        protected $tariffstats=array();
       
 

        public function __construct() {
            //sets display year
            if (wf_CheckPost(array('setyear'))) {
                $this->curyear= vf($_POST['setyear'],3);
            } else {
                $this->curyear=  curyear();
            }
            
            //load actual month data
            $this->loadData();
            //load user tariffs
            $this->loadTariffs();
         
        }

        /*
         * parse data from payments table and stores it into protected data prop
         * 
         * @return void
         */

        protected function loadData() {
            $curmonth = date("m");
            $query = "SELECT * from `payments` WHERE `note` LIKE 'SCFEE' AND `date` LIKE '".$this->curyear."-" . $curmonth . "%' ORDER BY `id` DESC";
            $alldata = simple_queryall($query);

            if (!empty($alldata)) {
                foreach ($alldata as $io => $each) {
                    $this->data[$each['id']]['id'] = $each['id'];
                    $this->data[$each['id']]['date'] = $each['date'];
                    $newSum = abs($each['summ']);
                    $this->data[$each['id']]['summ'] = $newSum;
                    $this->data[$each['id']]['login'] = $each['login'];
                }
            }
        }
        
        /*
         * loads all users tariffs into protected usertariffs prop
         * 
         * @return void
         */
        protected function loadTariffs() {
            $query="SELECT `login`,`Tariff` from `users`";
            $all=  simple_queryall($query);
            if (!empty($all) ) {
                foreach ($all as $io=>$each) {
                    $this->usertariffs[$each['login']]=$each['Tariff'];
                }
            }
        }

        /*
         * returns protected property data
         * 
         * @return array
         */

        public function getData() {
            $result = $this->data;
            return ($result);
        }
        
         /*
         * returns protected property year
         * 
         * @return array
         */

        public function getYear() {
            $result = $this->curyear;
            return ($result);
        }

        /*
         * returns summ of self credit payments by year/month
         * 
         * @param $year target year
         * @param $month month number
         * 
         * @return string
         */

        protected function getMonthSumm($year, $month) {
            $year = vf($year);
            $query = "SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `note` LIKE 'SCFEE'";
            $result = simple_query($query);
            $result = abs($result['SUM(`summ`)']);
            return($result);
        }

        /*
         * returns count of self credit payments by year/month
         * 
         * @param $year target year
         * @param $month month number
         * 
         * @return string
         */

        protected function getMonthCount($year, $month) {
            $year = vf($year);
            $query = "SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `note` LIKE 'SCFEE'";
            $result = simple_query($query);
            $result = $result['COUNT(`id`)'];
            return($result);
        }
        
        /*
         * returns summ of self credit payments by year
         * 
         * @param $year target year
         * 
         * @return string
         */
        protected function getYearSumm($year) {
            $year=vf($year);
            $query = "SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '" . $year . "-%' AND `note` LIKE 'SCFEE'";
            $result = simple_query($query);
            $result=$result['SUM(`summ`)'];
            $result=abs($result);
            return ($result);
        }

        /*
         * parse data from payments table and stores it into protected monthdata prop
         * 
         * @return void
         */

        protected function loadMonthData() {
            $months = months_array();
            $year = $this->curyear;
            $yearSumm=$this->getYearSumm($year);
            
            $this->chartdata = __('Month') . ',' . __('Count') . ',' . __('Cash') . "\n";
            
            $cells=wf_TableCell('');
            $cells.=wf_TableCell(__('Month'));
            $cells.=wf_TableCell(__('Payments count'));
            $cells.=wf_TableCell(__('Our final profit'));
            $cells.=wf_TableCell(__('Visual'));
            $this->tabledata=wf_TableRow($cells,'row1');

            foreach ($months as $eachmonth => $monthname) {
                $month_summ = $this->getMonthSumm($year, $eachmonth);
                $paycount = $this->getMonthCount($year, $eachmonth);
                $this->chartdata.=$year . '-' . $eachmonth . '-30,' . $paycount .','.$month_summ. "\n";
                
            $cells=wf_TableCell($eachmonth);
            $cells.=wf_TableCell(rcms_date_localise($monthname));
            $cells.=wf_TableCell($paycount);
            $cells.=wf_TableCell($month_summ);
            $cells.=wf_TableCell(web_bar($paycount, $yearSumm));
            $this->tabledata.=wf_TableRow($cells,'row3');
            $this->yearsumm=$this->yearsumm+$month_summ;
            }
        }

        /*
         * renders aself credit report using protected data property
         * 
         * @return string
         */

        public function render() {
            $allAddress = zb_AddressGetFulladdresslist();
            $allRealNames = zb_UserGetAllRealnames();
            $allUserCash= zb_CashGetAllUsers();
            $totalCount = 0;
            $totalSumm = 0;
            $result='';
            
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Full address'));
            $cells.= wf_TableCell(__('Tariff'));
            $cells.= wf_TableCell(__('Balance'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    $totalCount++;
                    @$usertariff=$this->usertariffs[$each['login']];
                    // fill tariff stats 
                    if (!empty($usertariff)) {
                        if (isset($this->tariffstats[$usertariff])) {
                            $this->tariffstats[$usertariff]++;
                        } else {
                            $this->tariffstats[$usertariff]=1;
                        }
                    }
                    @$usercash=$allUserCash[$each['login']];
                    
                    $totalSumm = $totalSumm + $each['summ'];
                    $cells = wf_TableCell($each['id']);
                    $cells.= wf_TableCell($each['date']);
                    $cells.= wf_TableCell($each['summ']);
                    $loginLink = wf_Link("?module=userprofile&username=" . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                    $cells.= wf_TableCell($loginLink);
                    $cells.= wf_TableCell(@$allRealNames[$each['login']]);
                    $cells.= wf_TableCell(@$allAddress[$each['login']]);
                    $cells.= wf_TableCell($usertariff);
                    $cells.= wf_TableCell($usercash);
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }
            
            $result.= wf_tag('div', false, 'glamour') . __('Count') . ': ' . $totalCount . wf_tag('div', true);
            $result.= wf_tag('div', false, 'glamour') . __('Our final profit') . ': ' . $totalSumm . wf_tag('div', true);
            $result.= wf_tag('div', false, '', 'style="clear:both;"').wf_tag('div', 'true');
            $result.= wf_TableBody($rows, '100%', '0', 'sortable');
            
            return ($result);
        }

        /*
         * renders aself credit report using protected data property
         * 
         * @return string
         */

        public function renderMonthGraph() {
            $this->loadMonthData();
            $result='';
            $result.=wf_TableBody($this->tabledata, '100%', '0', 'sortable');
            $result.=wf_tag('span', false, 'glamour').__('Our final profit').': '.$this->yearsumm.wf_tag('span', true);
            $result.= wf_delimiter();
            $result.= wf_tag('div', false, 'dashtask', '');
            $result.= wf_Graph($this->chartdata, '800', '400', false);
            $result.= wf_tag('div', true);
            return ($result);
        }
        
        /*
         * returns year selector
         * 
         * @return string
         */
        public function yearSelector() {
            $inputs=  wf_YearSelector('setyear', '', false);
            $inputs.= wf_Submit(__('Show'));
            $result=  wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        }
        
        /*
         * returns tariffs graph
         * 
         * @return string
         */
        public function renderTariffsGraph() {
            $result='';
            if (!empty($this->tariffstats)) {
                $cells= wf_TableCell(__('Tariff'));
                $cells.= wf_TableCell(__('Count'));
                $cells.= wf_TableCell(__('Visual'));
                $rows = wf_TableRow($cells, 'row1');
                
                foreach ($this->tariffstats as $tariffName=>$countCredits) {
                    $cells= wf_TableCell($tariffName);
                    $cells.= wf_TableCell($countCredits);
                    $cells.= wf_TableCell(web_bar($countCredits, sizeof($this->data)));
                    $rows.= wf_TableRow($cells, 'row3');
                }
                
                $graphs=  wf_TableBody($rows, '100%', '0', 'sortable');
                $result=  wf_modal(wf_img('skins/icon_stats.gif', __('Tariffs')), __('Tariffs'), $graphs, '', '800', '600');
            }
            return ($result);
        }
        
        }

    /*
     * controller & view
     */

    $screport = new ReportSelfCredit();
    
    if (!wf_CheckGet(array('showgraph'))) {
        show_window('', wf_Link('?module=report_selfcredit&showgraph=true', __('Self credit dynamic over the year'), false, 'ubButton'));
        $curmonthReport=$screport->render();
        $graps=$screport->renderTariffsGraph();
        show_window(__('Self credit report').' '.$graps, $curmonthReport);
    } else {
        show_window(__('Year'), $screport->yearSelector());
        show_window('', wf_Link('?module=report_selfcredit', __('Back'), false, 'ubButton'));
        show_window(__('Self credit dynamic over the year').' '.$screport->getYear(),$screport->renderMonthGraph());
    }
    
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }  
    
} else {
    show_error(__('You cant control this module'));
}
?>
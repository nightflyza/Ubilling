<?php

if (cfr('REPORTFINANCE')) {

    class ReportArpu {

        protected $payments = array();
        protected $data = array();
        protected $userTariffs = array();
        protected $config = '';
        protected $lines = array();
        protected $totalsum = 0;
        protected $year = '';
        protected $month = '';

        public function __construct() {
            //loads module config
            $this->loadConfig();

            //sets required year/month filters
            $this->setDateFilters();

            //get all login=>tariffs pairs
            $this->loadUserTariffs();

            //loads current month data
            $this->loadPayments();
        }

        /**
         * Sets required year/month filter properties
         * 
         * @return void
         */
        protected function setDateFilters() {
            if (wf_CheckPost(array('yearsel'))) {
                $this->year = vf($_POST['yearsel']);
            } else {
                $this->year = date("Y");
            }

            if (wf_CheckPost(array('monthsel'))) {
                $this->month = vf($_POST['monthsel']);
            } else {
                $this->month = date("m");
            }
        }

        /**
         * gets all user payments by current month and stores it into payments prop
         * 
         * @return void
         */
        protected function loadPayments() {
            $query = "SELECT * from `payments` WHERE `date` LIKE '" . $this->year . '-' . $this->month . "-%' AND `summ`>0;";
            $all = simple_queryall($query);
            if (!empty($all)) {
                $this->payments = $all;
            }
        }

        /**
         * Loads config from database
         * 
         * @return void
         */
        protected function loadConfig() {
            $config = zb_StorageGet('ARPU_LINES');
            $this->config = $config;
            if (!empty($config)) {
                $raw = explode(',', $config);
                if (!empty($raw)) {
                    foreach ($raw as $io => $each) {
                        $clearLine = trim($each);
                        $this->lines[$clearLine] = $clearLine;
                    }
                }
            }
        }

        /**
         * loads all user tariffs from database
         * 
         * @retun void
         */
        protected function loadUserTariffs() {
            $this->userTariffs = zb_TariffsGetAllUsers();
        }

        /**
         * parses previously extracted payments and preprocess it to data private prop
         * 
         * @return void
         */
        public function parsePayments() {
            if (!empty($this->lines)) {
                foreach ($this->lines as $io => $eachline) {
                    //setting empty tariff line counters
                    $this->data[$eachline]['summ'] = 0;
                    $this->data[$eachline]['count'] = 0;

                    if (!empty($this->payments)) {
                        foreach ($this->payments as $ia => $eachpayment) {
                            $userTariff = @$this->userTariffs[$eachpayment['login']];
                            if (ispos($eachline, '*')) {
                                $searchLine = str_replace('*', '', $eachline);
                                if (ispos($userTariff, $searchLine)) {
                                    $this->data[$eachline]['summ'] = $this->data[$eachline]['summ'] + $eachpayment['summ'];
                                    $this->data[$eachline]['count'] ++;
                                    $this->totalsum = $this->totalsum + $eachpayment['summ'];
                                }
                            } else {
                                if ($userTariff == $eachline) {
                                    $this->data[$eachline]['summ'] = $this->data[$eachline]['summ'] + $eachpayment['summ'];
                                    $this->data[$eachline]['count'] ++;
                                    $this->totalsum = $this->totalsum + $eachpayment['summ'];
                                }
                            }
                        }
                    } else {
                        show_error(__('No payments found'));
                    }
                }
            } else {
                show_error(__('Undefined tariff lines'));
            }
        }

        /**
         * returns tariff lines config form
         * 
         * @return string
         */
        protected function configForm() {
            $inputs = wf_TextInput('newtarifflines', __('Tariff lines masks, comma separated') . '. ' . __('You can use the * character as a symbol of lax compliance line.'), $this->config, true, '40');
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        }

        /**
         * saves tariff lines config to database
         * 
         * @param string $newlines    new lines, comma separated
         * 
         * @return void
         */
        public function saveConfig($newlines) {
            zb_StorageSet('ARPU_LINES', $newlines);
            log_register('ARPUREPORT CHANGE CONFIG');
        }

        /**
         * returns module control panel
         * 
         * @return string
         */
        protected function panel() {
            $result = wf_Link('?module=report_finance', __('Back'), false, 'ubButton');
            $result.= wf_modal(web_icon_settings() . ' ' . __('Settings'), __('Settings'), $this->configForm(), 'ubButton', '700', '200');
            $result.=wf_delimiter();
            $monthArr = months_array_localized();
            $inputs = wf_YearSelector('yearsel', __('Year'), false);
            $inputs.= wf_Selector('monthsel', $monthArr, __('Month'), $this->month, false);
            $inputs.= wf_Submit(__('Show'));
            $result.= wf_Form('', 'POST', $inputs, 'glamour');
            $result.=wf_CleanDiv();


            return ($result);
        }

        /**
         * Renders report by private data prop
         * 
         * @return string
         */
        public function render() {
            $cells = wf_TableCell(__('Tariff line'));
            $cells.= wf_TableCell(__('Payments count'));
            $cells.= wf_TableCell(__('ARPU'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Visual'));
            $rows = wf_TableRow($cells, 'row1');

            $result = $this->panel();

            if (!empty($this->data)) {
                foreach ($this->data as $line => $data) {
                    $monthArpu = ($data['count'] != 0) ? $mountArpu = round(($data['summ'] / $data['count']), 2) : 0;
                    $cells = wf_TableCell($line);
                    $cells.= wf_TableCell($data['count']);
                    $cells.= wf_TableCell($monthArpu);
                    $cells.= wf_TableCell($data['summ']);
                    $cells.= wf_TableCell(web_bar($data['summ'], $this->totalsum), '', '', 'sorttable_customkey="' . $data['summ'] . '"');
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result.= wf_TableBody($rows, '100%', '0', 'sortable');

            return ($result);
        }

    }

    $arpuReport = new ReportArpu();
    //config data controller
    if (wf_CheckPost(array('newtarifflines'))) {
        $arpuReport->saveConfig($_POST['newtarifflines']);
        rcms_redirect("?module=report_arpu");
    } else {
        //or some report rendering
        $arpuReport->parsePayments();
        show_window(__('Tariff lines ARPU report'), $arpuReport->render());
    }
} else {
    show_error(__('You cant control this module'));
}
?>

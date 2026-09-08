<?php

if (cfr('REPORTAUTOFREEZE')) {

    class ReportAutoFreeze {

        /**
         * Autofreeze data
         *
         * @var array
         */
        protected $data = array();

        /**
         * Array of currently frozen users
         *
         * @var array
         */
        protected $frozen = array();

        /**
         * Contains user array of users unfrozen in some month
         *
         * @var array
         */
        protected $unfrozen = array();

        /**
         * Contains default date offset
         *
         * @var string
         */
        protected $interval = '';

        /**
         * System messages object placeholder
         *
         * @var object
         */
        protected $messages ='';

        public function __construct($date = '') {
            $this->initMessages();
            $this->loadData($date);
            $this->loadFrozen();
            $this->interval = $date;
        }


        /**
         * Initializes system messages object
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * parse data from system weblog and store it into private property
         * 
         * @return void
         */
        protected function loadData($date = '') {
            if (!empty($date)) {
                $wherePostfix = " AND `date` LIKE '" . $date . "%';";
            } else {
                $wherePostfix = ' LIMIT 50;';
            }
            $query = "SELECT * from `weblogs` WHERE `event` LIKE 'AUTOFREEZE (%'" . $wherePostfix;
            $alldata = simple_queryall($query);

            if (!empty($alldata)) {
                foreach ($alldata as $io => $each) {
                    $this->data[$each['id']]['id'] = $each['id'];
                    $this->data[$each['id']]['date'] = $each['date'];
                    $this->data[$each['id']]['admin'] = $each['admin'];
                    $this->data[$each['id']]['ip'] = $each['ip'];

                    $parse = explode(' ', $each['event']);

//all elements available
                    if (sizeof($parse) == 5) {
                        $userLogin = str_replace('(', '', $parse[1]);
                        $userLogin = str_replace(')', '', $userLogin);
                        $userBalance = $parse[4];
                        $this->data[$each['id']]['login'] = $userLogin;
                        $this->data[$each['id']]['balance'] = $userBalance;
                    }
                }
            }
        }

        /**
         * load currently frozen users into pvt frozen prop
         * 
         * @return void 
         */
        protected function loadFrozen() {
            $query = "SELECT `login` from `users` WHERE `Passive`='1'";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->frozen[$each['login']] = $each['login'];
                }
            }
        }

        /**
         * returns private propert data
         * 
         * @return array
         */
        public function getData() {
            $result = $this->data;
            return ($result);
        }

        /**
         * renders autofreeze report by existing private data prop
         * 
         * @return string
         */
        public function render() {
            $result = '';
            $logins = array();
            $dateColumn = array();
            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    if (isset($each['login']) and !empty($each['login'])) {
                        $login = $each['login'];
                        $logins[$login] = $login;
                        $dateColumn[$login] = $each['date'];
                    }
                }
            }

            if (!empty($logins)) {
                $extraColumns = array();
                $extraColumns['Date'] = $dateColumn;
                $result .= web_UserArrayShower($logins, $extraColumns, true);
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
            return ($result);
        }

        /**
         * renders currently frozen users private frozen prop
         * 
         * @return string
         */
        public function renderFrozen() {
            $result = web_UserArrayShower($this->frozen,array(), true);
            return ($result);
        }

        /**
         * Rdenders date search controls
         * 
         * @return string
         */
        public function renderResDateForm() {
            $result = '';
            $showYear = curyear();
            $showMonth = date("m");
            //previous data preset
            if (ubRouting::checkPost(array('showyear', 'showmonth'))) {
                $showYear = ubRouting::post('showyear', 'int');
                $showMonth = ubRouting::post('showmonth', 'int');
            }
            $inputs = wf_YearSelectorPreset('showyear', __('Year'), false, $showYear) . ' ';
            $inputs .= wf_MonthSelector('showmonth', __('Month'), $showMonth, false) . ' ';
            $inputs .= wf_Submit(__('Show'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            return($result);
        }

        /**
         * Renders resurrection report for current month by default
         * 
         * @return string
         */
        public function renderResurrected() {
            $result = '';
            $showYear = curyear();
            $showMonth = date("m");
            if (ubRouting::checkPost(array('showyear', 'showmonth'))) {
                $showYear = ubRouting::post('showyear', 'int');
                $showMonth = ubRouting::post('showmonth', 'int');
            }
            $showDate = $showYear . '-' . $showMonth;
            $weblogs = new NyanORM('weblogs');
            $weblogs->where('date', 'LIKE', $showDate . '-%');
            $weblogs->where('event', 'LIKE', 'PASSIVE CHANGE%ON `0`');
            $dataRaw = $weblogs->getAll();
            if (!empty($dataRaw)) {
                foreach ($dataRaw as $io => $each) {
                    $event = htmlspecialchars($each['event']);
                    if (preg_match('!\((.*?)\)!si', $event, $tmpLoginMatches)) {
                        @$loginExtracted = $tmpLoginMatches[1];
                        if (!empty($loginExtracted)) {
                            $this->unfrozen[$loginExtracted] = $loginExtracted;
                        }
                    }
                }
            }
            $result .= web_UserArrayShower($this->unfrozen,array(), true);
            $result .= wf_tag('br');
            $result .= wf_tag('b') . __('Date') . wf_tag('b', true) . ': ' . $showDate;
            return($result);
        }

        /**
         * renders form for date selecting
         * 
         * @return string
         */
        public function dateForm() {
            $inputs = wf_DatePickerPreset('date', $this->interval);
            $inputs .= __('By date') . ' ';
            $inputs .= wf_Submit(__('Show'));
            $inputs .= ' ' . wf_Link("?module=report_autofreeze&showfrozen=true", wf_img('skins/icon_passive.gif') . ' ' . __('Currently frozen'), false, 'ubButton');
            $inputs .= ' ' . wf_Link("?module=report_autofreeze&resurrected=true", wf_img('skins/pigeon_icon.png') . ' ' . __('Resurrected'), false, 'ubButton');
            $result = wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        }

    }

    $datePush = (ubRouting::checkPost(array('date'))) ? $dateSelector = ubRouting::post('date') : $dateSelector = '';
    $autoFreezeReport = new ReportAutoFreeze($dateSelector);
//default route
    if (!ubRouting::checkGet(array('showfrozen')) AND ! ubRouting::checkGet('resurrected')) {
        show_window('', $autoFreezeReport->dateForm());
        show_window(__('Autofreeze report'), $autoFreezeReport->render());
    } else {
        if (ubRouting::checkGet('showfrozen')) {
            show_window('', wf_BackLink('?module=report_autofreeze'));
            show_window(__('Currently frozen'), $autoFreezeReport->renderFrozen());
        }

        if (ubRouting::checkGet('resurrected')) {
            show_window('', wf_BackLink('?module=report_autofreeze'));
            show_window('', $autoFreezeReport->renderResDateForm());
            show_window(__('Resurrected'), $autoFreezeReport->renderResurrected());
        }
    }
} else {
    show_error(__('You cant control this module'));
}

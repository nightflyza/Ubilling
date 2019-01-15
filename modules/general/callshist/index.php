<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['CALLSHIST_ENABLED']) {
    if (cfr('CALLSHIST')) {

        class CallsHistory {

            /**
             * Contains system alter config as key=>value
             *
             * @var array
             */
            protected $altCfg = array();

            /**
             * Calls log data source table
             *
             * @var string
             */
            protected $dataSource = '';

            /**
             * Contains previously loaded calls
             *
             * @var array
             */
            protected $allCalls = array();

            /**
             * May contains login filter for calls
             *
             * @var string
             */
            protected $loginSearch = '';

            /**
             * URL of user profile route
             */
            const URL_PROFILE = '?module=userprofile&username=';

            /**
             * Default module URL
             */
            const URL_ME = '?module=callshist';

            /**
             * Creates new CallsHistory instance
             * 
             * @return void
             */
            public function __construct() {
                $this->loadConfig();
            }

            /**
             * Sets user login to filter
             * 
             * @param string $login
             * 
             * @return void
             */
            public function setLogin($login = '') {
                $this->loginSearch = mysql_real_escape_string($login);
            }

            /**
             * Loads required configs and sets some options
             * 
             * @global object $ubillingConfig
             * 
             * @return void
             */
            protected function loadConfig() {
                global $ubillingConfig;
                $this->altCfg = $ubillingConfig->getAlter();
                $this->dataSource = AskoziaNum::LOG_TABLE;
            }

            /**
             * Loads some calls list into protected property
             * 
             * @return void
             */
            protected function loadCalls() {
                $where = (!empty($this->loginSearch)) ? " WHERE `login`='" . $this->loginSearch . "'" : '';
                $query = "SELECT * from `" . $this->dataSource . "` " . $where;
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->allCalls[$each['id']] = $each;
                    }
                }
            }

            /**
             * Renders calls log container
             * 
             * @return string
             */
            public function renderCalls() {
                $result = '';
                $columns = array('Date', 'Number', 'Address', 'Real Name', 'Tariff');
                $opts = '"order": [[ 0, "desc" ]]';
                $loginFilter = (!empty($this->loginSearch)) ? '&username=' . $this->loginSearch : '';
                $result.=wf_JqDtLoader($columns, self::URL_ME . '&ajaxcalls=true' . $loginFilter, false, __('Calls'), 100, $opts);
                return ($result);
            }

            /**
             * Renders ajax data source with loaded calls history
             * 
             * @return void
             */
            public function renderCallsAjaxList() {
                //loading some data
                $this->loadCalls();
                $allUserData = zb_UserGetAllDataCache();
                $json = new wf_JqDtHelper();
                if (!empty($this->allCalls)) {
                    foreach ($this->allCalls as $io => $each) {
                        if (!empty($each['login'])) {
                            $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon()) . ' ' . @$allUserData[$each['login']]['fulladress'];
                            $userRealName = @$allUserData[$each['login']]['realname'];
                            $userTariff = @$allUserData[$each['login']]['Tariff'];
                        } else {
                            $userLink = '';
                            $userRealName = '';
                            $userTariff = '';
                        }
                        $data[] = $each['date'];
                        $data[] = $each['number'];
                        $data[] = $userLink;
                        $data[] = $userRealName;
                        $data[] = $userTariff;
                        $json->addRow($data);
                        unset($data);
                    }
                }
                $json->getJson();
            }

        }

        $report = new CallsHistory();

        /**
         * main codepart
         */
        if (wf_CheckGet(array('username'))) {
            //setting some login filtering if required
            $report->setLogin($_GET['username']);
        }

        //rendering report json data
        if (wf_CheckGet(array('ajaxcalls'))) {
            $report->renderCallsAjaxList();
        }

        //rendering report container
        show_window(__('Calls history'), $report->renderCalls());

        if (wf_CheckGet(array('username'))) {
            //optional profile-return links
            $controlsLinks = wf_BackLink($report::URL_PROFILE . $_GET['username']) . ' ';
            $controlsLinks.= wf_Link($report::URL_ME, wf_img('skins/done_icon.png').' '.__('All calls'), false, 'ubButton');
            show_window('', $controlsLinks);
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>
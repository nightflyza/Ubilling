<?php

if (cfr('COVID')) {
    $altCfg = $ubillingConfig->getAlter();
    if (@$altCfg['COVID19_ENABLED']) {

        class Covid19 {

            /**
             * Contains system alter config as key=>value
             *
             * @var array
             */
            protected $altCfg = array();

            /**
             * System caching object placeholder
             *
             * @var object
             */
            protected $cache = '';

            /**
             * Contains system remote URL abstraction instance
             *
             * @var object
             */
            protected $omaeUrl = '';

            /**
             * Contains system message helper
             *
             * @var object
             */
            protected $messages = '';

            /**
             * Contains default country name to display
             *
             * @var string
             */
            protected $country = 'Ukraine';

            /**
             * Contains raw data array from cache or remote data source
             *
             * @var array
             */
            protected $rawData = array();

            /**
             * Default raw data caching timeout in seconds
             */
            const CACHE_TIMEOUT = 21600;

            /**
             * Default data source URL
             */
            const DATA_SOURCE = 'http://ubilling.net.ua/covid19/';

            /**
             * Default module route
             */
            const URL_ME = '?module=covid19';

            /**
             * Creates new instance of COVID-19 :P
             */
            public function __construct() {
                $this->initMessages();
                $this->loadAlter();
                $this->initCache();
                $this->initOmae();
                $this->loadData();
            }

            /**
             * Loads alter config into protected prop for further usage
             * 
             * @global object $ubillingConfig
             * 
             * @return void
             */
            protected function loadAlter() {
                global $ubillingConfig;
                $this->altCfg = $ubillingConfig->getAlter();
            }

            /**
             * Inits message helper instance
             * 
             * @return void
             */
            protected function initMessages() {
                $this->messages = new UbillingMessageHelper();
            }

            /**
             * Inits system caching engine
             * 
             * @return void
             */
            protected function initCache() {
                $this->cache = new UbillingCache();
            }

            /**
             * Inits remote URL abstraction layer
             * 
             * @return void
             */
            protected function initOmae() {
                $this->omaeUrl = new OmaeUrl();
                $this->omaeUrl->setUserAgent('Ubilling COVID-19 Info');
            }

            /**
             * Loads data from cache or from remote data source
             * 
             * @return void
             */
            protected function loadData() {
                $this->rawData = $this->cache->get('COVID19', self::CACHE_TIMEOUT);
                if (empty($this->rawData)) {
                    $remoteData = $this->omaeUrl->response(self::DATA_SOURCE);
                    if (!empty($remoteData)) {
                        $this->rawData = json_decode($remoteData, true);
                        $this->cache->set('COVID19', $this->rawData, self::CACHE_TIMEOUT);
                    }
                }
            }

            /**
             * Returns default chart options
             * 
             * @return string
             */
            protected function getChartOptions() {
                $result = "'focusTarget': 'category',
                                'hAxis': {
                                'color': 'none',
                                    'baselineColor': 'none',
                            },
                                'vAxis': {
                                'color': 'none',
                                    'baselineColor': 'none',
                            },
                                'curveType': 'function',
                                'pointSize': 5,
                                'crosshair': {
                                trigger: 'none'
                            },
                            series: {
                            0: { color: '#c7a20e' },
                            1: { color: '#d20009' },
                            2: { color: '#79c20d' },
                            },
                            ";
                
                return($result);
            }

            /**
             * Returns country selection form
             * 
             * @return string
             */
            protected function countrySelector() {
                $result = '';

                //country preset override
                if (ubRouting::checkPost('showcountry')) {
                    $this->country = ubRouting::post('showcountry');
                }

                $dataTmp = array();
                if (!empty($this->rawData)) {
                    foreach ($this->rawData as $eachCountry => $someData) {
                        $dataTmp[$eachCountry] = $eachCountry;
                    }

                    $inputs = wf_Selector('showcountry', $dataTmp, __('Country'), $this->country, false) . ' ';
                    $inputs .= wf_Submit(__('Show'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                }
                return($result);
            }

            /**
             * Renders COVID-19 causes report by some country
             * 
             * @return string
             */
            public function renderCountry() {
                $result = '';
                if (!empty($this->rawData)) {
                    if (isset($this->rawData[$this->altCfg['COVID19_ENABLED']])) {
                        //valid country name
                        $this->country = $this->altCfg['COVID19_ENABLED'];
                    }

                    $result .= $this->countrySelector();

                    if (isset($this->rawData[$this->country])) {
                        /**
                         * Struct:
                         *  [0] => Array
                         * (
                         * [date] => 2020-1-22
                         * [confirmed] => 0
                         * [deaths] => 0
                         * [recovered] => 0
                         * )
                         * [1]=> Array....
                         */
                        $countryTimeline = $this->rawData[$this->country];
                        if (!empty($countryTimeline)) {
                            $chartsOptions = $this->getChartOptions();

                            $charsDataTotal[] = array(__('Date'), __('Confirmed'), __('Deaths'), __('Recovered'));
                            $charsDataMonth[] = array(__('Date'), __('Confirmed'), __('Deaths'), __('Recovered'));
                            $curMonth = curmonth() . '-';
                            foreach ($countryTimeline as $io => $each) {
                                $timeStamp = strtotime($each['date']); //need to be transformed to Y-m-d
                                $date = date("Y-m-d", $timeStamp);
                                if (ispos($date, $curMonth)) {
                                    $charsDataMonth[] = array($date, $each['confirmed'], $each['deaths'], $each['recovered']);
                                }
                                $charsDataTotal[] = array($date, $each['confirmed'], $each['deaths'], $each['recovered']);

                                $lastData = $each;
                            }


                            $countryDeathPercent = zb_PercentValue($lastData['confirmed'], $lastData['deaths']);

                            $result .= $this->messages->getStyledMessage(__('Confirmed') . ' ' . $lastData['confirmed'], 'warning');
                            $result .= $this->messages->getStyledMessage(__('Deaths') . ' ' . $lastData['deaths'] . ' (' . $countryDeathPercent . '%)', 'error');
                            $result .= $this->messages->getStyledMessage(__('Recovered') . ' ' . $lastData['recovered'], 'success');

                            $result .= wf_gchartsLine($charsDataMonth, __('Month'), '100%', '300px;', $chartsOptions);
                            $result .= wf_gchartsLine($charsDataTotal, __('All time'), '100%', '300px;', $chartsOptions);
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Nothing to show'), 'warning');
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': EX_WRONG_COUNTRY', 'warning');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
                }
                return($result);
            }

            /**
             * Renders COVID-19 world causes report
             * 
             * @return string
             */
            public function renderWorld() {
                $result = '';
                if (!empty($this->rawData)) {
                    $chartsOptions = $this->getChartOptions();
                    $curMonth = curmonth() . '-';
                    $totalTmp = array();

                    $charsDataTotal[] = array(__('Date'), __('Confirmed'), __('Deaths'), __('Recovered'));
                    $charsDataMonth[] = array(__('Date'), __('Confirmed'), __('Deaths'), __('Recovered'));

                    foreach ($this->rawData as $eachCountry => $eachTimeline) {
                        if (!empty($eachTimeline)) {
                            foreach ($eachTimeline as $io => $each) {
                                $timeStamp = strtotime($each['date']); //need to be transformed to Y-m-d
                                $date = date("Y-m-d", $timeStamp);
                                if (isset($totalTmp[$date])) {
                                    $totalTmp[$date]['confirmed'] += $each['confirmed'];
                                    $totalTmp[$date]['deaths'] += $each['deaths'];
                                    $totalTmp[$date]['recovered'] += $each['recovered'];
                                } else {
                                    $totalTmp[$date]['confirmed'] = $each['confirmed'];
                                    $totalTmp[$date]['deaths'] = $each['deaths'];
                                    $totalTmp[$date]['recovered'] = $each['recovered'];
                                }
                            }
                        }
                    }

                    if (!empty($totalTmp)) {
                        foreach ($totalTmp as $date => $each) {
                            if (ispos($date, $curMonth)) {
                                $charsDataMonth[] = array($date, $each['confirmed'], $each['deaths'], $each['recovered']);
                            }
                            $charsDataTotal[] = array($date, $each['confirmed'], $each['deaths'], $each['recovered']);

                            $lastData = $each;
                        }

                        $worldDeathPercent = zb_PercentValue($lastData['confirmed'], $lastData['deaths']);

                        $result .= $this->messages->getStyledMessage(__('Confirmed') . ' ' . $lastData['confirmed'], 'warning');
                        $result .= $this->messages->getStyledMessage(__('Deaths') . ' ' . $lastData['deaths'] . ' (' . $worldDeathPercent . '%)', 'error');
                        $result .= $this->messages->getStyledMessage(__('Recovered') . ' ' . $lastData['recovered'], 'success');

                        $result .= wf_gchartsLine($charsDataMonth, __('Month'), '100%', '300px;', $chartsOptions);
                        $result .= wf_gchartsLine($charsDataTotal, __('All time'), '100%', '300px;', $chartsOptions);
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Empty reply received'), 'error');
                }
                return($result);
            }

            /**
             * Renders default module controls
             * 
             * @return string
             */
            public function panel() {
                $result = '';
                $result .= wf_Link(self::URL_ME, wf_img('skins/country.png') . ' ' . __('Country'), false, 'ubButton');
                $result .= wf_Link(self::URL_ME . '&world=true', wf_img('skins/ymaps/globe.png') . ' ' . __('World'), false, 'ubButton');
                return($result);
            }

        }

        $covid = new Covid19();
        show_window('', $covid->panel());
        if (ubRouting::checkGet('world')) {
            show_window(__('COVID-19'), $covid->renderWorld());
        } else {
            show_window(__('COVID-19'), $covid->renderCountry());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
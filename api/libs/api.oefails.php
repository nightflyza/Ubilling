<?php

class OeFails {

    /**
     * Contains system alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System caching abstraction layer placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default caching timeout 
     *
     * @var int
     */
    protected $cacheTimeout = 600;

    /**
     * Contains default data source URL or file path
     *
     * @var string
     */
    protected $dataSource = '';

    /**
     * Contains raw data received from data source
     *
     * @var string
     */
    protected $rawData = '';

    /**
     * Contains basically preprocessed data
     *
     * @var array
     */
    protected $parsedData = array();

    /**
     * Contains default city filters for extraction
     *
     * @var string
     */
    protected $cityFilter = '';

    /**
     * Contains scheduled power outages string mask
     *
     * @var string
     */
    protected $scheduledMask = '';

    /**
     * Contains emergency power outages string mask
     *
     * @var string
     */
    protected $emergencyMask = '';

    /**
     * Contains default cache key name
     */
    const CACHE_KEY = 'OEFAILS';

    /**
     * Contains basic module controller URL
     */
    const URL_ME = '?module=oefails';

    /**
     * Options string parsing offsets
     */
    const OFFSET_SOURCE = 0;
    const OFFSET_FILTER = 1;
    const OFFSET_EMERG = 2;
    const OFFSET_SCHED = 3;

    /**
     * Creates new fails instance
     * 
     * @param string $dataSource
     */
    public function __construct($dataSource = '') {
        $this->loadAlter();
        $this->setOptions();
        $this->initCache();
        $this->setDataSource($dataSource);
    }

    /**
     * Loads alter config file into protected prop
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
     * Sets config based options for current instance
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg['OEFAILS_OPTIONS'])) {
            $optionsString = @$this->altCfg['OEFAILS_OPTIONS'];
            $options = explode('|', $optionsString);
            if (!empty($options)) {
                $this->setDataSource($options[self::OFFSET_SOURCE]);
                $this->setFilter($options[self::OFFSET_FILTER]);
                if (isset($options[self::OFFSET_EMERG])) {
                    $this->scheduledMask = $options[self::OFFSET_SCHED];
                    $this->emergencyMask = $options[self::OFFSET_EMERG];
                }
            } else {
                throw new Exception('EX_EMPTY_OPTIONS');
            }
        } else {
            throw new Exception('EX_NO_OPTIONS');
        }
    }

    /**
     * Inits caching object for further usage
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Sets data source file path/URL into obj prop
     * 
     * @param string $dataSource
     * 
     * @return void
     */
    protected function setDataSource($dataSource = '') {
        if (!empty($dataSource)) {
            $this->dataSource = $dataSource;
        }
    }

    /**
     * Sets city filter for data extraction
     * 
     * @param string $mask
     * 
     * @return void
     */
    protected function setFilter($mask = '') {
        if (!empty($mask)) {
            $this->cityFilter = $mask;
        }
    }

    /**
     * Gets raw CSV data from datasource and stores it in protected property
     * 
     * @return void
     */
    protected function getRawData() {
        $this->rawData = $this->cache->get(self::CACHE_KEY, $this->cacheTimeout);
        if (empty($this->rawData)) {
            $this->rawData = file_get_contents($this->dataSource);
            $this->cache->set(self::CACHE_KEY, $this->rawData, $this->cacheTimeout);
        }
    }

    /**
     * Returns basically preprocessed and filtered data
     * 
     * @return void
     */
    protected function parseData() {
        $this->getRawData();
        if (!empty($this->rawData)) {
            $rawTmp = explodeRows($this->rawData);
            $filteringRequired = (!empty($this->cityFilter)) ? true : false;
            if (!empty($rawTmp)) {
                foreach ($rawTmp as $io => $eachLine) {
                    if ($filteringRequired) {
                        if (ispos($eachLine, $this->cityFilter)) {
                            $this->parsedData[] = str_getcsv($eachLine);
                        }
                    } else {
                        $this->parsedData[] = $eachLine;
                    }
                }
            }
        }
    }

    /**
     * Basic data preprocessing method. May be customizable in far far future.
     * Now its hardcodded for oe.if.ua data format
     * 
     * @return void
     */
    public function ajGetData($dateFilter = '', $allTime = false) {
        $this->parseData();
        $json = new wf_JqDtHelper();
        $magicNumber = 3; //f**k that s**t
        $dateFilter = (!empty($dateFilter)) ? $dateFilter : curdate();
        if ($allTime) {
            $dateFilter = '';
        }
        if (!empty($this->parsedData)) {
            foreach ($this->parsedData as $io => $each) {
                $indexOffset = 0;
                $region = $each[0];
                $cityName = $each[1];
                $address = $each[2];

                $recordsCount = sizeof($each);
                if ($recordsCount >= $magicNumber) {
                    foreach ($each as $index => $failRecord) {
                        if ($indexOffset >= $magicNumber) {
                            $data[] = $region;
                            $data[] = $cityName;
                            $data[] = $address;
                            $failRecord = str_replace('{', '', $failRecord);
                            $failRecord = str_replace('}', '', $failRecord);
                            $failText = $failRecord;
                            $typeIcon = '';
                            if (!empty($this->emergencyMask) and ! empty($this->scheduledMask)) {
                                $typeIcon = '';
                                if (ispos($failText, $this->emergencyMask)) {
                                    $typeIcon = web_red_led() . ' ' . trim($this->emergencyMask, ',');
                                    $failText = str_replace($this->emergencyMask, '', $failText);
                                }

                                if (ispos($failText, $this->scheduledMask)) {
                                    $typeIcon = web_yellow_led() . ' ' . trim($this->scheduledMask, ',');
                                    $failText = str_replace($this->scheduledMask, '', $failText);
                                }
                            }
                            $data[] = $typeIcon;
                            $data[] = $failText;
                            //some date filtering
                            if (!empty($dateFilter)) {
                                if (ispos($failText, $dateFilter)) {
                                    $json->addRow($data);
                                }
                            } else {
                                //all time data
                                $json->addRow($data);
                            }
                            unset($data);
                        }
                        $indexOffset++;
                    }
                }
            }
        }
        $json->getJson();
    }

    /**
     * Renders power outages list container 
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        //some controls here
        $curdateFilter = (ubRouting::checkPost('datefilter')) ? ubRouting::post('datefilter') : curdate();
        $alltimeCall = (ubRouting::checkPost('alltime')) ? '&alltime=true' : '';

        $inputs = wf_DatePickerPreset('datefilter', $curdateFilter) . ' ';
        $inputs .= wf_CheckInput('alltime', __('All time'), false, ubRouting::checkPost('alltime')) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        $columns = array(__('District'), __('City'), __('Address'), __('Type'), __('Date') . '/' . __('Time'));
        $opts = '';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajaxlist=true&datefilter=' . $curdateFilter . $alltimeCall, false, __('Power outages'), 100, $opts);
        return($result);
    }

}

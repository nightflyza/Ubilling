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
    protected $cacheTimeout = 300;

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
        $optionsString = $this->altCfg['OEFAILS_OPTIONS'];
        $options = explode('|', $optionsString);
        if (!empty($options)) {
            $this->setDataSource($options[self::OFFSET_SOURCE]);
            $this->setFilter($options[self::OFFSET_FILTER]);
        } else {
            throw new Exception('EX_EMPTY_OPTIONS');
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
     * 
     * @return void
     */
    public function ajGetData() {
        $this->parseData();
        $json = new wf_JqDtHelper();
        if (!empty($this->parsedData)) {
            foreach ($this->parsedData as $io => $each) {
                $region=$each[0]; 
                $cityName=$each[1];
                $address=$each[2];
                
                
                $json->addRow($data);
                unset($data);
            }
            $json->getJson();
        }
    }

    /**
     * Renders power outages list container 
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        $columns = array('1','2','3','4','5');
        $opts = '';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajaxlist=true', false, __('Power outages'), 100, $opts);
        return($result);
    }

}

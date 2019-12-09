<?php

class ItSaTrap {

    /**
     * Contains SNMP data log path or 
     *
     * @var string
     */
    protected $dataSource = '';

    /**
     * Contains billing.ini config file as key=>value 
     *
     * @var array
     */
    protected $billingCfg = '';

    /**
     * Contains default limit of lines received from local data source
     *
     * @var int
     */
    protected $lineLimit = 200;

    /**
     * key-value storage key of file path/URL of traps source
     */
    const DATA_SOURCE_KEY = 'ITSATRAPSOURCE';

    /**
     * key-value storage key of lines parse limit of traps data source
     */
    const DATA_LINES_KEY = 'ITSATRAPLINES';

    /**
     * Contains control module basic URL
     */
    const URL_ME = '?module=itsatrap';

    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads some configuration files and options for further usage
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->dataSource = zb_StorageGet(self::DATA_SOURCE_KEY);
        $lineLimitCfg = zb_StorageGet(self::DATA_LINES_KEY);
        if (!empty($lineLimitCfg)) {
            $this->lineLimit = $lineLimitCfg;
        }
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Returns raw data from data source if defined
     * 
     * @return string
     */
    protected function getRawData() {
        $result = '';
        if (!empty($this->dataSource)) {
            if (ispos($this->dataSource, 'http')) {
                $result = file_get_contents($this->dataSource);
            } else {
                $command = $this->billingCfg['SUDO'] . ' ' . $this->billingCfg['TAIL'] . ' -n ' . $this->lineLimit . ' ' . $this->dataSource;
                $result = shell_exec($command);
            }
        }
        return($result);
    }

    /**
     * Returns module configuration form
     * 
     * @return string
     */
    public function renderConfigForm() {
        $result = '';
        $inputs = wf_TextInput('newdatasource', __('Data source file path or URL'), $this->dataSource, true, 40);
        $inputs .= wf_TextInput('newlineslimit', __('Lines limit for processing'), $this->lineLimit, true, 4);
        //TODO: other options like trap types
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Saves data source configuration if its changed
     * 
     * @return void
     */
    public function saveBasicConfig() {
        $newDataSource = ubRouting::post('newdatasource', 'mres');
        if ($newDataSource != $this->dataSource) {
            zb_StorageSet(self::DATA_SOURCE_KEY, $newDataSource);
            log_register('ITSATRAP CHANGE DATASOURCE');
        }
        $newLinesLimit = ubRouting::post('newlineslimit', 'int');
        if ($newLinesLimit != $this->lineLimit) {
            zb_StorageSet(self::DATA_LINES_KEY, $newLinesLimit);
            log_register('ITSATRAP CHANGE LIMIT `'.$newLinesLimit.'`');
        }
    }

}

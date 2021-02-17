<?php

/**
 * Performs radius.log analyze to detect successful and failed auth attempts
 */
class CobainsShotgun {

    /**
     * Contains default raw-data source path. 
     * May be configurable in future.
     *
     * @var string
     */
    protected $dataSource = '/var/log/radius.log';

    /**
     * Contains auth data text marker to extract from datasource
     *
     * @var string
     */
    protected $authDataMask = 'Auth:';

    /**
     * Mask of successful auth in log
     *
     * @var string
     */
    protected $authOkMask = 'Login OK:';

    /**
     * System billing.ini config as key=>value
     * 
     * @var object
     */
    protected $billCfg = array();

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
    }

    /**
     * Preloads required system configs for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Returns array of filtered auth strings from datasource
     * 
     * @return array
     */
    protected function getRawData() {
        $result = array();
        $command = $this->billCfg['SUDO'] . ' ' . $this->billCfg['CAT'] . ' ' . $this->dataSource . ' | ' . $this->billCfg['GREP'] . ' "' . $this->authDataMask . '"';
        $result = shell_exec($command);
        if (!empty($result)) {
            $result = explodeRows($result);
        }
        return($result);
    }

    /**
     * Render report
     * 
     * @return string
     */
    public function renderReport() {
        $result = '';
        $rawData = $this->getRawData();
        $usernameCounters = array();
        if (!empty($rawData)) {
            foreach ($rawData as $io => $eachLine) {
                if (!empty($eachLine)) {
                    $userName = zb_ExtractMacAddress($eachLine);
                    if (!empty($userName)) {
                        //prefill attempts counters
                        if (!isset($usernameCounters[$userName])) {
                            $usernameCounters[$userName]['ok'] = 0;
                            $usernameCounters[$userName]['fail'] = 0;
                        }

                        //counting attempts for each username
                        if (ispos($eachLine, $this->authOkMask)) {
                            $usernameCounters[$userName]['ok'] ++;
                        } else {
                            $usernameCounters[$userName]['fail'] ++;
                        }
                    }
                }
            }

            if (!empty($usernameCounters)) {
                $cells = wf_TableCell(__('Username') . ' ' . __('Radius'));
                $cells .= wf_TableCell(__('Success'));
                $cells .= wf_TableCell(__('Failed'));
                $cells .= wf_TableCell(__('Total'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($usernameCounters as $eachUserName => $eachStats) {
                    $cells = wf_TableCell($eachUserName);
                    $cells .= wf_TableCell($eachStats['ok']);
                    $cells .= wf_TableCell($eachStats['fail']);
                    $cells .= wf_TableCell($eachStats['ok'] + $eachStats['fail']);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }

        return($result);
    }

}

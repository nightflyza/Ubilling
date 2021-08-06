<?php

/**
 * Performs radius.log analyze to detect successful and failed auth attempts
 */
class CobainsShotgun {

    /**
     * Pre-defines Routes
     */
    const URL_ME = '?module=cobainsshotgun';
    const ROUTE_ZEN = 'zenmode';

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
     * Extracts username from square brackets
     * 
     * @param string $string
     * 
     * @return string
     */
    public function extractUserName($string) {
        $result = '';
        if (preg_match('!\[(.*?)\]!si', $string, $tmpArr)) {
            $result = $tmpArr[1];
        }
        return($result);
    }

    /**
     * Renders module controls panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/icon_shotgun.png', __('Shotgun')) . ' ' . __('Shotgun'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ZEN . '=true', wf_img('skins/zen.png', __('Zen')) . ' ' . __('Zen'), false, 'ubButton') . ' ';
        return ($result);
    }

    /**
     * Renders CobainsShotgun UI for ZenFlow
     *
     * @param $linesToRead
     * @return string
     */
    public function renderReportZen($linesToRead = 200) {
        // Variable that stores UI elements.
        $result = '';

        // Check if file exists.
        if (!file_exists($this->dataSource)) {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('File not exist') . ': ' . $this->dataSource, 'error');
            return $result;
        }

        // Get data from 'radius.log'.
        $command = $this->billCfg['SUDO'] . ' ' . $this->billCfg['CAT'] . ' ' . $this->dataSource . ' | '
                . $this->billCfg['GREP'] . ' "' . $this->authDataMask . '" ' . ' | '
                . $this->billCfg['TAIL'] . ' -r ' . ' -n ' . $linesToRead;

        $cmdResult = shell_exec($command);

        // Check if we have any data.
        if (empty($cmdResult)) {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
            return $result;
        }

        $cmdResult = explodeRows($cmdResult);
        $rows = '';

        // Transform received data into Table-like UI element
        foreach ($cmdResult as $singleRow) {
            $cells = wf_TableCell(htmlentities(strip_tags($singleRow)));
            $rows .= wf_TableRow($cells, 'row5');
        }

        $result .= wf_TableBody($rows, '100%', 0, '', 'style="font-family: monospace;"');

        return ($result);
    }

    /**
     * Render the report. What did you expect?
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
                    $userName = $this->extractUserName($eachLine);
                    $userMac = zb_ExtractMacAddress($eachLine);
                    if (empty($userName)) {
                        $userName = __('Empty') . ' / ' . $userMac;
                    }
                    if (!empty($userName)) {
                        //prefill attempts counters
                        if (!isset($usernameCounters[$userName])) {
                            $usernameCounters[$userName]['ok'] = 0;
                            $usernameCounters[$userName]['fail'] = 0;
                            $usernameCounters[$userName]['mac'] = $userMac;
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
                $cells .= wf_TableCell(__('MAC'));
                $cells .= wf_TableCell(__('Success'));
                $cells .= wf_TableCell(__('Failed'));
                $cells .= wf_TableCell(__('Total'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($usernameCounters as $eachUserName => $eachStats) {
                    $cells = wf_TableCell($eachUserName);
                    $cells .= wf_TableCell($eachStats['mac']);
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

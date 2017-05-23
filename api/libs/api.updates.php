<?php

class UbillingUpdateManager {

    /**
     * Systema alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * System mysql.ini config as key=>value
     *
     * @var array
     */
    protected $mySqlCfg = array();

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Available mysql dumps for apply as release=>filename
     *
     * @var array
     */
    protected $allDumps = array();

    /**
     * Contains available configs updates as release=>configdata
     *
     * @var array
     */
    protected $allConfigs = array();

    /**
     * Contais configs filenames as shortid=>filename
     *
     * @var array
     */
    protected $configFileNames = array();

    const DUMPS_PATH = 'content/updates/sql/';
    const CONFIGS_PATH = 'content/updates/configs/';
    const URL_ME = '?module=updatemanager';
    const URL_RELNOTES = 'wiki.ubilling.net.ua/doku.php?id=relnotes#section';

    /**
     * Creates new update manager instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadSystemConfigs();
        $this->initMessages();
        $this->setConfigFilenames();
        $this->loadDumps();
        $this->loadConfigs();
    }

    /**
     * Loads all required system config files into protected props for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadSystemConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->mySqlCfg = rcms_parse_ini_file(CONFIG_PATH . 'mysql.ini');
    }

    /**
     * Inits system messages helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads available mysql dumps filenames into protected prop
     * 
     * @return void
     */
    protected function loadDumps() {
        $dumpsTmp = rcms_scandir(self::DUMPS_PATH, '*.sql');
        if (!empty($dumpsTmp)) {
            foreach ($dumpsTmp as $io => $each) {
                $release = str_replace('.sql', '', $each);
                $this->allDumps[$release] = $each;
            }
        }
    }

    /**
     * Loads available configs update into protected prop
     * 
     * @return void
     */
    protected function loadConfigs() {
        $configsTmp = rcms_scandir(self::CONFIGS_PATH, '*.ini');
        if (!empty($configsTmp)) {
            foreach ($configsTmp as $io => $each) {
                $release = str_replace('.ini', '', $each);
                $fileContent = rcms_parse_ini_file(self::CONFIGS_PATH . $each, true);
                $this->allConfigs[$release] = $fileContent;
            }
        }
    }

    /**
     * Sets shortid=>filename configs associations array
     * 
     * @return void
     */
    protected function setConfigFilenames() {
        $this->configFileNames = array(
            'alter' => 'alter.ini',
            'billing' => 'billing.ini',
            'ymaps' => 'ymaps.ini',
        );
    }

    /**
     * Returns list of files which was updated in some release
     * 
     * @param string $release
     * 
     * @return string
     */
    protected function getReleaseConfigFiles($release) {
        $result = '';
        if (isset($this->allConfigs[$release])) {
            if (!empty($this->allConfigs[$release])) {
                foreach ($this->allConfigs[$release] as $shortid => $data) {
                    $filename = (isset($this->configFileNames[$shortid])) ? $this->configFileNames[$shortid] : $shortid;
                    $result .= $filename . ' ';
                }
            }
        }
        return($result);
    }

    /**
     * Renders list of sql dumps available for applying
     * 
     * @return string
     */
    public function renderSqlDumpsList() {
        $result = '';
        if (!empty($this->allDumps)) {
            $cells = wf_TableCell(__('Ubilling release'));
            $cells .= wf_TableCell(__('Details'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDumps as $release => $filename) {
                $relnotesUrl = self::URL_RELNOTES . str_replace('.', '', $release);
                $relnotesLink = wf_Link('http://' . $relnotesUrl, __('Release notes') . ' ' . $release, false, '');
                $alertText = __('Are you serious') . ' ' . __('Apply') . ' Ubilling ' . $release . '?';
                $actLink = wf_JSAlert(self::URL_ME . '&applysql=' . $release, wf_img('skins/icon_restoredb.png', __('Apply')), $alertText);
                $cells = wf_TableCell($release);
                $cells .= wf_TableCell($relnotesLink);
                $cells .= wf_TableCell($actLink);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Renders list of available config files updates
     * 
     * @return string
     */
    public function renderConfigsList() {
        $result = '';
        if (!empty($this->allConfigs)) {
            $cells = wf_TableCell(__('Ubilling release'));
            $cells .= wf_TableCell(__('Details'));
            $cells .= wf_TableCell(__('Files'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDumps as $release => $filename) {
                $relnotesUrl = self::URL_RELNOTES . str_replace('.', '', $release);
                $relnotesLink = wf_Link('http://' . $relnotesUrl, __('Release notes') . ' ' . $release, false, '');
                $actLink = wf_Link(self::URL_ME . '&showconfigs=' . $release, web_icon_search(__('Show')));
                $cells = wf_TableCell($release);
                $cells .= wf_TableCell($relnotesLink);
                $cells .= wf_TableCell($this->getReleaseConfigFiles($release));
                $cells .= wf_TableCell($actLink);
                $rows .= wf_TableRow($cells, 'row5');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Applies mysql dump to current database
     * 
     * @param string $release
     * 
     * @return string
     */
    public function applyMysqlDump($release) {
        $result = '';
        $release = trim($release);
        $release = vf($release);
        if (isset($this->allDumps[$release])) {
            if (wf_CheckPost(array('applyconfirm', 'applysqldump'))) {
                $fileName = self::DUMPS_PATH . $this->allDumps[$release];
                $applyCommand = $this->altCfg['MYSQL_PATH'] . ' -u ' . $this->mySqlCfg['username'] . ' -p' . $this->mySqlCfg['password'] . ' ' . $this->mySqlCfg['db'] . ' --default-character-set=utf8 < ' . $fileName . ' 2>&1; echo $?';
                $result .= $this->messages->getStyledMessage(__('MySQL dump applying result below'), 'info');
                $result .= wf_CleanDiv();
                $result .= wf_tag('pre', false, '', 'style="width:100%;overflow:auto"') . shell_exec($applyCommand) . wf_tag('pre', true);
                $result .= wf_BackLink(self::URL_ME);
            } else {
                if ((!wf_CheckPost(array('applyconfirm'))) AND ( wf_CheckPost(array('applysqldump')))) {
                    $result .= $this->messages->getStyledMessage(__('You are not mentally prepared for this'), 'error');
                    $result .= wf_delimiter();
                    $result .= wf_BackLink(self::URL_ME . '&applysql=' . $release);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Caution: these changes can not be undone.'), 'warning');
                    $result .= wf_tag('br');
                    $inputs = __('Apply changes for Ubilling release') . ' ' . $release . '?';
                    $inputs .= wf_tag('br');
                    $inputs .= wf_tag('br');
                    $inputs .= wf_HiddenInput('applysqldump', 'true');
                    $inputs .= wf_CheckInput('applyconfirm', __('I`m ready'), true, false);
                    $inputs .= wf_tag('br');
                    $inputs .= wf_Submit(__('Apply'));

                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                    $result .= wf_CleanDiv();

                    $result .= wf_delimiter();
                    $result .= wf_BackLink(self::URL_ME);
                }
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Wrong release'), 'error');
        }

        return ($result);
    }

}

?>
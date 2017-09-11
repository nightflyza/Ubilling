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
        $this->ConnectDB();
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
     * Sets shortid=>filename with path configs associations array
     * 
     * @return void
     */
    protected function setConfigFilenames() {
        $this->configFileNames = array(
            'alter' => 'config/alter.ini',
            'billing' => 'config/billing.ini',
            'ymaps' => 'config/ymaps.ini',
            'userstats' => 'userstats/config/userstats.ini',
        );
    }

    /**
     * Initialises connection with Ubilling database server and selects needed db
     *
     * @param MySQL Connection Id $connection
     * 
     * @return MySQLDB
     */
    protected function ConnectDB() {
        $this->DBConnection = new DbConnect($this->mySqlCfg['server'], $this->mySqlCfg['username'], $this->mySqlCfg['password'], $this->mySqlCfg['db'], true);
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
     * Apply Mysql Dump and returns results
     * 
     * @param string $release
     * 
     * @return string
     */
    protected function DoSqlDump($release) {
        $result = '';
        if (!empty($release)) {
            $fileName = self::DUMPS_PATH . $this->allDumps[$release];
            $file = explode(';', file_get_contents($fileName));
            $sql_dumps = array_diff($file, array(''));  // Delete empty data Array
            $sql_array = array_map('trim', $sql_dumps);

            // Open DB connection and set character 
            $this->DBConnection->open();
            $this->DBConnection->query("set character_set_client='" . $this->mySqlCfg['character'] . "'");
            $this->DBConnection->query("set character_set_results='" . $this->mySqlCfg['character'] . "'");
            $this->DBConnection->query("set collation_connection='" . $this->mySqlCfg['character'] . "_general_ci'");

            foreach ($sql_array as $query) {
                if (!empty($query)) {
                    $this->DBConnection->query($query);
                    if (!$this->DBConnection->error()) {
                        $result .= $this->messages->getStyledMessage(wf_tag('b', false) . __('Done') . ': ' . wf_tag('b', true) . wf_tag('pre', false) . $query . wf_tag('pre', true), 'success') . wf_tag('br');
                    } else {
                        $result .= $this->messages->getStyledMessage(wf_tag('b', false) . __('Error') . ': ' . wf_tag('b', true) . $this->DBConnection->error() . wf_tag('pre', false) . $query . wf_tag('pre', true), 'error') . wf_tag('br');
                    }
                }
            }
            $this->DBConnection->close();
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
            foreach ($this->allConfigs as $release => $filename) {
                $relnotesUrl = self::URL_RELNOTES . str_replace('.', '', $release);
                $relnotesLink = wf_Link('http://' . $relnotesUrl, __('Release notes') . ' ' . $release, false, '');
                $alertText = __('Are you serious') . ' ' . __('Apply') . ' Ubilling ' . $release . '?';
                $actLink = wf_JSAlert(self::URL_ME . '&showconfigs=' . $release, wf_img('skins/icon_addrow.png', __('Apply')), $alertText);

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
                $result .= $this->messages->getStyledMessage(__('MySQL dump applying result below'), 'info');
                $result .= wf_CleanDiv();
                log_register('UPDMGR APPLY SQL RELEASE `' . $release . '`');
                $result .= $this->DoSqlDump($release);
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
            log_register('UPDMGR FAIL SQL RELEASE `' . $release . '`');
        }

        return ($result);
    }

    /**
     * Renders interface and applies new options to some config files
     * 
     * @param string $release
     * 
     * @return string
     */
    public function applyConfigOptions($release) {
        $result = '';
        $release = trim($release);
        $release = vf($release);
        $newOptsCount = 0;
        if (isset($this->allConfigs[$release])) {
            $releaseData = $this->allConfigs[$release];
            if (!empty($releaseData)) {
                foreach ($releaseData as $configId => $configOptions) {
                    @$configName = $this->configFileNames[$configId];
                    if (!empty($configName)) {
                        if (file_exists($configName)) {
                            $currentConfigOptions = rcms_parse_ini_file($configName);
                            $result.=$this->messages->getStyledMessage(__('Existing config file') . ': ' . $configName, 'success');
                            //some logging
                            if (wf_CheckPost(array('applyconfigoptions', 'applyconfirm'))) {
                                //Initial line break and update header
                                $configUpdateHeader = "\n";
                                $configUpdateHeader.=';release ' . $release . ' update' . "\n";
                                file_put_contents($configName, $configUpdateHeader, FILE_APPEND);
                                log_register('UPDMGR APPLY CONFIG `' . $configId . '` RELEASE `' . $release . '`');
                            }
                            if (!empty($configOptions)) {
                                foreach ($configOptions as $optionName => $optionContent) {
                                    if (!isset($currentConfigOptions[$optionName])) {
                                        $newOptsCount++;
                                        $result.=$this->messages->getStyledMessage(__('New option') . ': ' . $optionName . ' ' . __('will be added with value') . ' ' . $optionContent, 'info');
                                        if (wf_CheckPost(array('applyconfigoptions', 'applyconfirm'))) {
                                            $saveOptions = $optionName . '=' . $optionContent . "\n";
                                            file_put_contents($configName, $saveOptions, FILE_APPEND);
                                            $result.=$this->messages->getStyledMessage(__('Option added') . ': ' . $optionName . '= ' . $optionContent, 'success');
                                            $newOptsCount--;
                                        }
                                    } else {
                                        $result.=$this->messages->getStyledMessage(__('Option already exists, will be ignored') . ': ' . $optionName, 'warning');
                                    }
                                }
                            }
                        } else {
                            $result.=$this->messages->getStyledMessage(__('Wrong config path') . ': ' . $configName, 'error');
                        }
                    } else {
                        $result.=$this->messages->getStyledMessage(__('Unknown config') . ': ' . $configId, 'error');
                    }
                }
                //confirmation checkbox notice
                if ((wf_CheckPost(array('applyconfigoptions'))) AND ( !wf_CheckPost(array('applyconfirm')))) {
                    $result .= $this->messages->getStyledMessage(__('You are not mentally prepared for this'), 'error');
                }

                //apply form assembly
                if ($newOptsCount > 0) {
                    $result .= wf_tag('br');
                    $inputs = __('Apply changes for Ubilling release') . ' ' . $release . '?';
                    $inputs .= wf_tag('br');
                    $inputs .= wf_tag('br');
                    $inputs .= wf_HiddenInput('applyconfigoptions', 'true');
                    $inputs .= wf_CheckInput('applyconfirm', __('I`m ready'), true, false);
                    $inputs .= wf_tag('br');
                    $inputs .= wf_Submit(__('Apply'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                    $result.= wf_CleanDiv();
                } else {
                    $result.=$this->messages->getStyledMessage(__('Everything is fine. All required options for release') . ' ' . $release . ' ' . __('is on their places.'), 'success');
                }
            }
            $result.=wf_CleanDiv();
            $result.=wf_delimiter();
            $result.=wf_BackLink(self::URL_ME);
        } else {
            $result.= $this->messages->getStyledMessage(__('Wrong release'), 'error');
            log_register('UPDMGR FAIL CONF RELEASE `' . $release . '`');
        }

        return ($result);
    }

}

class UbillingUpdateStuff {

    /**
     * Contains system billing.ini as key=>value
     *
     * @var array
     */
    protected $billingCfg = array();

    /**
     * Wget path
     *
     * @var string
     */
    protected $wgetPath = '/usr/local/bin/wget';

    /**
     * Tar archiver path
     *
     * @var string
     */
    protected $tarPath = '/usr/bin/tar';

    /**
     * system sudo path
     *
     * @var string
     */
    protected $sudoPath = '/usr/local/bin/sudo';

    /**
     * Gzip archiver path
     *
     * @var gzip
     */
    protected $gzipPath = '/usr/bin/gzip';

    public function __construct() {
        $this->loadConfig();
        $this->setOptions();
    }

    /**
     * Loads all required configs
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Sets custom paths to required software
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->billingCfg['SUDO'])) {
            $this->sudoPath = $this->billingCfg['SUDO'];
        }

        if (isset($this->billingCfg['WGET_PATH'])) {
            $this->wgetPath = $this->billingCfg['WGET_PATH'];
        }

        if (isset($this->billingCfg['TAR_PATH'])) {
            $this->tarPath = $this->billingCfg['TAR_PATH'];
        }

        if (isset($this->billingCfg['GZIP_PATH'])) {
            $this->gzipPath = $this->billingCfg['GZIP_PATH'];
        }
    }

    /**
     * Changes access rights for some directory to be writable
     * 
     * @param string $directory
     * 
     * @return void
     */
    public function fixAccessRights($directory) {
        $command = $this->sudoPath . ' chmod -R 777 ' . $directory;
        shell_exec($command);
    }

    /**
     * Downloads file from remote host
     * 
     * @param string $url
     * @param string $directory
     * @param string $filename
     * 
     * @return void
     */
    public function downloadRemoteFile($url, $directory, $filename = '') {
        if ($filename) {
            $wgetOptions = '--output-document=' . $directory . $filename . ' ';
        } else {
            $wgetOptions = '--directory-prefix=' . $directory . basename($url) . ' ';
        }
        $wgetOptions.= '--no-check-certificate ';
        if (file_exists($directory)) {
            if (!is_writable($directory)) {
                throw new Exception('DOWNLOAD_DIRECTORY_NOT_WRITABLE');
            }
            $command = $this->wgetPath . ' ' . $wgetOptions . ' ' . $url;
            shell_exec($command);
        } else {
            throw new Exception('DOWNLOAD_DIRECTORY_NOT_EXISTS');
        }
    }

    /**
     * Extracts tar.gz archive to some path
     * 
     * @param string $archivePath
     * @param string $extractPath
     * 
     * @return void
     */
    public function extractTgz($archivePath, $extractPath) {
        if (file_exists($archivePath)) {
            if (is_readable($archivePath)) {
                if (file_exists($extractPath)) {
                    if (!is_writable($extractPath)) {
                        $this->fixAccessRights($extractPath);
                    }
                    //unpacking archive
                    $command = $this->tarPath . ' zxvf ' . $archivePath . ' -C ' . $extractPath;
                    shell_exec($command);
                } else {
                    throw new Exception('EXTRACT_DIRECTORY_NOT_EXISTS');
                }
            }
        }
    }

}

?>
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

    const DUMPS_PATH = 'content/updates/sql/';
    const URL_ME = '?module=updatemanager';
    const URL_RELNOTES = 'wiki.ubilling.net.ua/doku.php?id=relnotes#section';

    /**
     * Creates new update manager instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->loadDumps();
    }

    /**
     * Loads all required config files into protected props for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
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
     * Renders list of sql dumps available for applying
     * 
     * @return string
     */
    public function renderSqlDumpsList() {
        $result = '';
        if (!empty($this->allDumps)) {
            $cells = wf_TableCell(__('Ubilling release'));
            $cells.= wf_TableCell(__('Details'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allDumps as $release => $filename) {
                $relnotesUrl = self::URL_RELNOTES . str_replace('.', '', $release);
                $relnotesLink = wf_Link('http://' . $relnotesUrl, __('Release notes') . ' ' . $release, false, '');
                $actLink = wf_Link(self::URL_ME . '&applysql=' . $release, wf_img('skins/icon_restoredb.png', __('Apply')), false, '');
                $cells = wf_TableCell($release);
                $cells.= wf_TableCell($relnotesLink);
                $cells.= wf_TableCell($actLink);
                $rows.= wf_TableRow($cells, 'row5');
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');
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
            if (wf_CheckPost(array('applyconfirm','applysqldump'))) {
                $fileName = self::DUMPS_PATH . $this->allDumps[$release];
                $applyCommand = $this->altCfg['MYSQL_PATH'] . ' -u ' . $this->mySqlCfg['username'] . ' -p' . $this->mySqlCfg['password'] . ' ' . $this->mySqlCfg['db'] . ' --default-character-set=utf8 < ' . $fileName;
                $result.=$this->messages->getStyledMessage(__('MySQL dump applying result below'), 'info');
                $result.=wf_CleanDiv();
                $result.= wf_tag('pre') . shell_exec($applyCommand) . wf_tag('pre', true);
            } else {
                $inputs=  wf_HiddenInput('applysqldump', 'true');
                $inputs.= wf_CheckInput('applyconfirm', __('I`m ready'), false, false);
                $inputs.= wf_Submit(__('Apply'));
                $result.=wf_Form('', 'POST', $inputs, 'glamour');
                $result.=wf_CleanDiv();
                $result.=wf_BackLink(self::URL_ME);
                        
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Wrong release'), 'error');
        }
        return ($result);
    }

}

?>
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
    const URL_RELNOTES = 'http://wiki.ubilling.net.ua/doku.php?id=relnotes#section';

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
                $relnotesLink = wf_Link($relnotesUrl, __('Release notes') . ' ' . $release, false, '');
                $actLink = wf_Link(self::URL_ME . '&applysql=' . $release, wf_img('skins/icon_restoredb.png',__('Apply')), false, '');
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

}
?>
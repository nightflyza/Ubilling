<?php

class CrontabEditor {

    /**
     * Contains billing.ini config file as key=>value
     *
     * @var array
     */
    protected $billingCfg = array();

    /**
     * Contains current crontab state
     *
     * @var string
     */
    protected $currentCrontab = '';

    /**
     * Contains temporary file path used for crontab IO
     */
    const TMP_FILE_PATH = 'exports/crontab_tmp';

    /**
     * Contains basic module routing URL
     */
    const URL_ME = '?module=crontabeditor';

    /**
     * Contains default back URL
     */
    const URL_BACK = '?module=sysconf';

    /**
     * Creates new crontab editor instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->loadCrontab();
    }

    /**
     * Loads required configs into protected properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->billingCfg = $ubillingConfig->getBilling();
    }

    /**
     * Loads current crontab state into protected property
     * 
     * @return void
     */
    protected function loadCrontab() {
        $command = $this->billingCfg['SUDO'] . ' crontab -l';
        $this->currentCrontab = shell_exec($command);
        file_put_contents(self::TMP_FILE_PATH, $this->currentCrontab);
    }

    /**
     * Returns current host system name
     * 
     * @return string
     */
    public function getSystemName() {
        $result = '';
        $command = "uname";
        $hostSystem = shell_exec($command);
        $result = trim($hostSystem);
        return($result);
    }

    /**
     * Returns current crontab state
     * 
     * @return string
     */
    public function getCurrentCrontab() {
        return($this->currentCrontab);
    }

    /**
     * Renders crontab editing interface form
     * 
     * @return string
     */
    public function renderEditForm() {
        $result = '';
        $result .= web_FileEditorForm(self::TMP_FILE_PATH, $this->currentCrontab);
        return($result);
    }

    /**
     * Saves received editor form content into temporary file
     * 
     * @return void
     */
    public function saveTempCrontab() {
        if (ubRouting::checkPost(array('editfilepath'))) {
            if (ubRouting::post('editfilepath') == self::TMP_FILE_PATH) {
                $newCrontab = ubRouting::post('editfilecontent');
                if (ispos($newCrontab, "\r\n")) {
                    //cleanup to unix EOL
                    $newCrontab = str_replace("\r\n", "\n", $newCrontab);
                }
                file_put_contents(self::TMP_FILE_PATH, $newCrontab);
            }
        }
    }

    /**
     * Installs new crontab jobs into system crontab
     * 
     * @return void/string on error
     */
    public function installNewCrontab() {
        $result = '';
        if (file_exists(self::TMP_FILE_PATH)) {
            $tempFileContants = file_get_contents(self::TMP_FILE_PATH);
            //is something changed?
            if ($tempFileContants != $this->currentCrontab) {
                $command = $this->billingCfg['SUDO'] . ' crontab ' . self::TMP_FILE_PATH;
                $installResult = shell_exec($command);
                log_register('CRONTABEDITOR NEW CRONTAB INSTALLED');
            } else {
                $result .= __('Nothing changed');
            }
        } else {
            $result .= __('File') . ' ' . self::TMP_FILE_PATH . ' ' . __('Not exists');
        }
        return($result);
    }

}

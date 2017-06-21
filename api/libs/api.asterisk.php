<?php

class Asterisk {

    /**
     * Contains Ubstorage data for Asterisk as key=>value
     *
     * @var array
     */
    public $config = array();
    const URL_ME = '?module=asterisk';

    public function __construct () {
        $this->AsteriskLoadConf();
    }

    /**
     * Load Asterisk config
     * 
     * @return array
     */
    protected function AsteriskLoadConf() {
        $this->config = $this->AsteriskGetConf();

    }

    /**
     * Gets Asterisk config from DB, or sets default values
     * 
     * @return array
     */
    protected function AsteriskGetConf() {
        $result = array();
        //getting url
        $host = zb_StorageGet('ASTERISK_HOST');
        if (empty($host)) {
            $host = 'localhost';
            zb_StorageSet('ASTERISK_HOST', $host);
        }
        //getting login
        $login = zb_StorageGet('ASTERISK_LOGIN');
        if (empty($login)) {
            $login = 'asterisk';
            zb_StorageSet('ASTERISK_LOGIN', $login);
        }

        //getting DB name
        $db = zb_StorageGet('ASTERISK_DB');
        if (empty($db)) {
            $db = 'asteriskdb';
            zb_StorageSet('ASTERISK_DB', $db);
        }
        //getting CDR table name
        $table = zb_StorageGet('ASTERISK_TABLE');
        if (empty($table)) {
            $table = 'cdr';
            zb_StorageSet('ASTERISK_TABLE', $table);
        }

        //getting password
        $password = zb_StorageGet('ASTERISK_PASSWORD');
        if (empty($password)) {
            $password = 'password';
            zb_StorageSet('ASTERISK_PASSWORD', $password);
        }
        //getting caching time
        $cache = zb_StorageGet('ASTERISK_CACHETIME');
        if (empty($cache)) {
            $cache = '1';
            zb_StorageSet('ASTERISK_CACHETIME', $cache);
        }

        $result['host'] = $host;
        $result['db'] = $db;
        $result['table'] = $table;
        $result['login'] = $login;
        $result['password'] = $password;
        $result['cachetime'] = $cache;
        return ($result);
    }

    /**
     * Returns Asterisk module configuration form
     * 
     * @return string
     */
    public function AsteriskConfigForm() {
        global $asteriskHost, $asteriskDb, $asteriskTable, $asteriskLogin, $asteriskPassword, $asteriskCacheTime;
        $result = wf_Link('?module=asterisk', __('Back'), true, 'ubButton') . wf_delimiter();
        $inputs = wf_TextInput('newhost', __('Asterisk host'), $this->config['host'], true);
        $inputs.= wf_TextInput('newdb', __('Database name'), $this->config['db'], true);
        $inputs.= wf_TextInput('newtable', __('CDR table name'), $this->config['table'], true);
        $inputs.= wf_TextInput('newlogin', __('Database login'), $this->config['login'], true);
        $inputs.= wf_TextInput('newpassword', __('Database password'), $this->config['password'], true);
        $inputs.= wf_TextInput('newcachetime', __('Cache time'), $this->config['cachetime'], true);
        $inputs.= wf_Submit(__('Save'));
        $result.= wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns number aliases aka phonebook form
     * 
     * @return string 
     */
    public function AsteriskAliasesForm() {
        global $numAliases;
        $createinputs = wf_TextInput('newaliasnum', __('Phone'), '', true);
        $createinputs.=wf_TextInput('newaliasname', __('Alias'), '', true);
        $createinputs.=wf_Submit(__('Create'));
        $createform = wf_Form('', 'POST', $createinputs, 'glamour');
        $result = $createform;

        if (!empty($numAliases)) {
            $delArr = array();
            foreach ($numAliases as $num => $eachname) {
                $delArr[$num] = $num . ' - ' . $eachname;
            }
            $delinputs = wf_Selector('deletealias', $delArr, __('Delete alias'), '', false);
            $delinputs.= wf_Submit(__('Delete'));
            $delform = wf_Form('', 'POST', $delinputs, 'glamour');
            $result.= $delform;
        }

        return ($result);
    }

    /**
     * Returns CDR date selection form
     * 
     * @return string
     */
    public function panel() {
        global $user_login;
        $inputs = '';
        if (cfr('ASTERISK')) {
            $inputs.=wf_Link(self::URL_ME . '&config=true', wf_img('skins/icon_extended.png') . ' ' . __('Settings'), false, 'ubButton') . ' ';
        }
        $inputs.= wf_DatePickerPreset('datefrom', curdate()) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', curdate()) . ' ' . __('To');
        if (!isset($user_login)) {
            $inputs.= wf_Trigger('countnum', 'Показать самых назойливых', false);
        }
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }
}

?>

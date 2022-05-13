<?php

/**
 * MultiGen Custom NAS configuration implementation
 */
class MultigenECN {

    /**
     * Database abstraction layer placeholder
     *
     * @var object
     */
    protected $nasDb = '';

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains all available custom-NAS data as id=>data
     *
     * @var array
     */
    protected $allNasData = array();

    /**
     * some predefined URLs, routes, etc...
     */
    const URL_ME = '?module=multigennascustom';
    const DATA_TABLE = 'mlg_nascustom';
    const ROUTE_DELETE = 'deleteecnid';
    const PROUTE_NEWIP = 'newecnip';
    const PROUTE_NEWNAME = 'newecnname';
    const PROUTE_NEWSECRET = 'newecnsecret';
    const PROUTE_EDID = 'editecnid';
    const PROUTE_EDNAME = 'editecnname';
    const PROUTE_EDSECRET = 'editecnsecret';

    /**
     * Creates new extra chromosome NAS instance
     */
    public function __construct() {
        $this->initMessages();
        $this->initDb();
        $this->loadAllNasData();
    }

    /**
     * Inits system message helper for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->nasDb = new NyanORM(self::DATA_TABLE);
    }

    /**
     * Loads all available custom NAS data from database
     * 
     * @return void
     */
    protected function loadAllNasData() {
        $this->allNasData = $this->nasDb->getAll('id');
    }

    /**
     * Renders available custom NAS-es list
     * 
     * @return string
     */
    public function renderList() {
        $result = '';
        if (!empty($this->allNasData)) {
            /**
             *   O       o O       o O       o
             *   | O   o | | O   o | | O   o |
             *   | | O | | | | O | | | | O | |
             *   | o   O | | o   O | | o   O |
             *   o       O o       O o       O
             */
            $cells = wf_TableCell(__('ID'));
            $cells .= wf_TableCell(__('IP'));
            $cells .= wf_TableCell(__('NAS name'));
            $cells .= wf_TableCell(__('RADIUS secret'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allNasData as $nasId => $eachNasData) {
                $cells = wf_TableCell($nasId);
                $cells .= wf_TableCell($eachNasData['ip']);
                $cells .= wf_TableCell($eachNasData['name']);
                $cells .= wf_TableCell($eachNasData['secret']);
                $deleteUrl = self::URL_ME . '&' . self::ROUTE_DELETE . '=' . $nasId;
                $cancelUrl = self::URL_ME;
                $delDialogTitle = __('Delete') . ' ' . __('NAS') . ' ' . $eachNasData['ip'] . '?';
                $actLinks = '';
                $actLinks .= wf_ConfirmDialog($deleteUrl, web_delete_icon(), $this->messages->getDeleteAlert(), '', $cancelUrl, $delDialogTitle);
                $editDialogTitle = __('Edit') . ' ' . __('NAS') . ' ' . $eachNasData['ip'];
                $actLinks .= wf_modalAuto(web_edit_icon(), $editDialogTitle, $this->renderEditForm($nasId));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return($result);
    }

    /**
     * Renders new custom NAS creation form
     * 
     * @return string
     */
    public function renderCreateForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_NEWIP, __('IP'), '', true, 15, 'ip');
        $inputs .= wf_TextInput(self::PROUTE_NEWNAME, __('NAS name'), '', true, 20, '');
        $inputs .= wf_TextInput(self::PROUTE_NEWSECRET, __('RADIUS secret'), '', true, 20, 'alphanumeric');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders custom NAS editing form
     * 
     * @param int $nasId
     * 
     * @return string
     */
    protected function renderEditForm($nasId) {
        $result = '';
        if (isset($this->allNasData[$nasId])) {
            $nasData = $this->allNasData[$nasId];
            $inputs = wf_HiddenInput(self::PROUTE_EDID, $nasId);
            $inputs .= wf_TextInput(self::PROUTE_EDNAME, __('NAS name'), $nasData['name'], true, 20, '');
            $inputs .= wf_TextInput(self::PROUTE_EDSECRET, __('RADIUS secret'), $nasData['secret'], true, 20, 'alphanumeric');
            $inputs .= wf_delimiter(0);
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('NAS') . ' [' . $nasId . '] ' . __('Not exists'), 'error');
        }
        return($result);
    }

    /**
     * Checks is NAS IP address not used for any of other custom NAS
     * 
     * @param string $ip
     * 
     * @return bool
     */
    protected function isIpFree($ip) {
        $result = true;
        $ip = trim($ip);
        if (!empty($this->allNasData)) {
            foreach ($this->allNasData as $io => $each) {
                if ($each['ip'] == $ip) {
                    $result = false;
                }
            }
        }
        return($result);
    }

    /**
     * Returns something to indicate that NAS have custom configuration
     * 
     * @param string $ip
     * 
     * @return string
     */
    public function getIndicator($ip) {
        $result = '';
        if (!$this->isIpFree($ip)) {
            $result .= ' ' . wf_img_sized('skins/dna_icon.png', __('Extra chromosome NAS'), 12);
        }
        return($result);
    }

    /**
     * Creates new custom NAS in database
     * 
     * @param string $ip
     * @param string $name
     * @param string $secret
     * 
     * @return void/string on error
     */
    public function create($ip, $name, $secret) {
        $ipF = ubRouting::filters($ip, 'mres');
        $nameF = ubRouting::filters($name, 'mres');
        $secretF = ubRouting::filters($secret, 'mres');
        $result = '';
        if (!empty($ipF) AND ! empty($nameF) AND ! empty($secretF)) {
            if (zb_isIPValid($ipF)) {
                if ($this->isIpFree($ipF)) {
                    $this->nasDb->data('ip', $ipF);
                    $this->nasDb->data('name', $nameF);
                    $this->nasDb->data('secret', $secret);
                    $this->nasDb->create();
                    $newId = $this->nasDb->getLastId();
                    log_register('MULTIGEN ECN CREATE SUCCESS [' . $newId . '] `' . $ip . '`');
                } else {
                    $result = __('IP duplicate') . ': ' . $ipF;
                    log_register('MULTIGEN ECN CREATE FAIL `' . $ip . '` DUPLICATE');
                }
            } else {
                $result = __('Wrong IP') . ': ' . $ipF;
                log_register('MULTIGEN ECN CREATE FAIL `' . $ip . '` WRONG');
            }
        } else {
            $result = __('Not all of required fields are filled');
            log_register('MULTIGEN ECN CREATE FAIL INCOMPLETE_DATA');
        }
        return($result);
    }

    /**
     * Saves changes for some custom NAS in database
     * 
     * @param int $nasId
     * @param string $name
     * @param string $secret
     * 
     * @return void/string on error
     */
    public function save($nasId, $name, $secret) {
        $nasId = ubRouting::filters($nasId, 'int');
        $nameF = ubRouting::filters($name, 'mres');
        $secretF = ubRouting::filters($secret, 'mres');
        $result = '';
        if (!empty($nasId) AND ! empty($nameF) AND ! empty($secretF)) {
            if (isset($this->allNasData[$nasId])) {
                $this->nasDb->where('id', '=', $nasId);
                $this->nasDb->data('name', $nameF);
                $this->nasDb->data('secret', $secretF);
                $this->nasDb->save();
                log_register('MULTIGEN ECN SAVE SUCCESS [' . $nasId . ']');
            } else {
                $result = __('NAS') . ' [' . $nasId . '] ' . __('Not exists');
                log_register('MULTIGEN ECN SAVE FAIL [' . $nasId . '] NOT_EXISTS');
            }
        } else {
            $result = __('Not all of required fields are filled');
            log_register('MULTIGEN ECN SAVE FAIL INCOMPLETE_DATA');
        }
        return($result);
    }

    /**
     * Deletes existing custom NAS configuration from database
     * 
     * @param int $nasId
     * 
     * @return void/string on error
     */
    public function delete($nasId) {
        $nasId = ubRouting::filters($nasId, 'int');
        $result = '';
        if (!empty($nasId)) {
            if (isset($this->allNasData[$nasId])) {
                $nasData = $this->allNasData[$nasId];
                $nasIp = $nasData['ip'];
                $this->nasDb->where('id', '=', $nasId);
                $this->nasDb->delete();
                log_register('MULTIGEN ECN DELETE SUCCESS [' . $nasId . '] `' . $nasIp . '`');
            } else {
                $result = __('NAS') . ' [' . $nasId . '] ' . __('Not exists');
                log_register('MULTIGEN ECN DELETE FAIL [' . $nasId . '] NOT_EXISTS');
            }
        } else {
            $result = __('ID') . ' ' . __('NAS') . ' ' . __('is empty');
            log_register('MULTIGEN ECN CREATE FAIL ID_EMPTY');
        }
        return($result);
    }

}

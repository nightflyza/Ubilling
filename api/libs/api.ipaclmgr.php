<?php

/**
 * IP/Networks ACL management
 */
class IpACLMgr {

    /**
     * Contais all existing IP ACLs as ip=>notes
     *
     * @var array
     */
    protected $allowedIps = array();

    /**
     * Contais all existing nets ACLs as network network=>notes
     *
     * @var array
     */
    protected $allowedNets = array();

    /**
     * Contains current administrator IP address
     *
     * @var string
     */
    protected $myIp = '';

    /**
     * System message helper placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some predefined URLs, routes, etc...
     */
    const URL_ME = '?module=ipaclmgr';
    const ROUTE_DELIPACL = 'deleteipacl';
    const ROUTE_DELNETACL = 'deletenetwacl';
    const PROUTE_NEWIPACLIP = 'newipacl';
    const PROUTE_NEWIPACLNOTE = 'newipaclnote';
    const PROUTE_EDIPACLIP = 'editipacl';
    const PROUTE_EDIPACLNOTE = 'editipaclnote';
    const PROUTE_NEWNETACLNET = 'newnetaclsubnet';
    const PROUTE_NEWNETACLNOTE = 'newnetaclnote';
    const PROUTE_EDNETACLNET = 'editnetaclsubnet';
    const PROUTE_EDNETACLNOTE = 'editnetaclnote';
    const COLOR_ALERT = 'f40000';
    const COLOR_HERE = '007b09';

    /**
     * Creates new IP ACL manager instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initMessages();
        $this->setMyIp();
        $this->loadAclIps();
        $this->loadAclNets();
    }

    /**
     * Sets current administrator IP address
     * 
     * @return void
     */
    protected function setMyIp() {
        $this->myIp = $_SERVER['REMOTE_ADDR'];
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
     * Loads all existing IP ACLs into protected property
     * 
     * @return void
     */
    protected function loadAclIps() {
        $tmp = rcms_scandir(IPACLALLOWIP_PATH);
        if (!empty($tmp)) {
            foreach ($tmp as $io => $eachIp) {
                $this->allowedIps[$eachIp] = file_get_contents(IPACLALLOWIP_PATH . $eachIp);
            }
        }
    }

    /**
     * Loads all existing networks ACLs into protected property
     * 
     * @return void
     */
    protected function loadAclNets() {
        $tmp = rcms_scandir(IPACLALLOWNETS_PATH);
        if (!empty($tmp)) {
            foreach ($tmp as $io => $eachNet) {
                $eachNetCidr = str_replace('_', '/', $eachNet);
                $this->allowedNets[$eachNetCidr] = file_get_contents(IPACLALLOWNETS_PATH . $eachNet);
            }
        }
    }

    /**
     * Renders module controls panel
     * 
     * @return string
     */
    public function renderControls() {
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $result = '';
        $result .= wf_BackLink('?module=sysconf') . ' ';
        $result .= wf_modalAuto(wf_img('skins/icon_ip.png') . ' ' . __('Allow access form some IP'), __('Allow access form some IP'), $this->renderIpAclCreateForm(), 'ubButton');
        $result .= wf_modalAuto(wf_img('skins/icon_net.png') . ' ' . __('Allow access form some subnet'), __('Allow access form some subnet'), $this->renderNetAclCreateForm(), 'ubButton');
        $result .= wf_modalAuto(wf_img('skins/question.png') . ' ' . __('Who am i') . '?', __('Who am i') . '?', $this->renderMyCurrentIp(), 'ubButton');
        if (!@$billCfg['IPACL_ENABLED']) {
            $result .= $this->messages->getStyledMessage(__('IP Access restrictions is disabled now'), 'warning');
        }
        //           .-"-.            .-"-.            .-"-.           .-"-.
        //         _/_-.-_\_        _/.-.-.\_        _/.-.-.\_       _/.-.-.\_
        //        / __} {__ \      /|( o o )|\      ( ( o o ) )     ( ( o o ) )
        //       / //  "  \\ \    | //  "  \\ |      |/  "  \|       |/  "  \|
        //      / / \'---'/ \ \  / / \'---'/ \ \      \'/^\'/         \ .-. /
        //      \ \_/`"""`\_/ /  \ \_/`"""`\_/ /      /`\ /`\         /`"""`\
        //       \           /    \           /      /  /|\  \       /       \        
        return($result);
    }

    /**
     * Paints some text into some color
     * 
     * @param string $text
     * @param string $color
     * 
     * @return string
     */
    protected function colorize($text, $color = '') {
        $result = '';
        if (!empty($color)) {
            $result .= wf_tag('font', false, '', 'style="color:#' . $color . ';"');
            $result .= $text;
            $result .= wf_tag('font', true);
        } else {
            $result .= $text;
        }
        return($result);
    }

    /**
     * Returns list of available IP ACLs with some controls
     * 
     * @return string
     */
    public function renderIpAclsList() {
        $result = '';
        if (!empty($this->allowedIps)) {
            $cells = wf_TableCell(__('IP'), '20%');
            $cells .= wf_TableCell(__('Notes'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allowedIps as $eachIp => $eachNote) {
                $specialNotes = '';
                $deleteNote = '';
                if ($eachIp == $this->myIp) {
                    $specialNotes .= ' ' . $this->colorize(__('This is you'), self::COLOR_HERE);

                    $deleteNote .= $this->colorize(__('Think twice. This may block access for you') . '!', self::COLOR_ALERT);
                    $deleteNote .= wf_delimiter(0);
                }
                $deleteNote .= $this->messages->getDeleteAlert();

                $cells = wf_TableCell($eachIp, '', '', 'sorttable_customkey="' . ip2int($eachIp) . '"');
                $cells .= wf_TableCell($eachNote . $specialNotes);
                $deleteUrl = self::URL_ME . '&' . self::ROUTE_DELIPACL . '=' . $eachIp;

                $actLinks = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $deleteNote, '', self::URL_ME, __('Delete') . ' ' . $eachIp . '?') . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $eachIp, $this->renderIpAclEditForm($eachIp));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            if (empty($this->allowedIps) AND empty($this->allowedNets)) {
                $result = $this->messages->getStyledMessage(__('Access is allowed from anywhere'), 'success');
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        }
        return($result);
    }

    /**
     * Renders IP ACL creation form
     * 
     * @return string
     */
    protected function renderIpAclCreateForm() {
        $result = '';
        $ipPreset = '';
        $notesPreset = '';
        $formLabel = '';
        if (empty($this->allowedIps) AND empty($this->allowedNets)) {
            $ipPreset = $this->myIp;
            $notesPreset = whoami();
            $formLabel = __('Allow yourself access first, then access from all other addresses will be restricted');
        }
        $inputs = wf_TextInput(self::PROUTE_NEWIPACLIP, __('IP'), $ipPreset, false, 20, 'ip') . ' ';
        $inputs .= wf_TextInput(self::PROUTE_NEWIPACLNOTE, __('Notes'), $notesPreset, false, 30) . ' ';
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= $formLabel;
        return($result);
    }

    /**
     * Renders IP ACL notes edit form
     * 
     * @param string $ip
     * 
     * @return string
     */
    protected function renderIpAclEditForm($ip) {
        $result = '';
        if (!empty($ip)) {
            if (isset($this->allowedIps[$ip])) {
                $inputs = wf_HiddenInput(self::PROUTE_EDIPACLIP, $ip);
                $inputs .= wf_TextInput(self::PROUTE_EDIPACLNOTE, __('Notes'), $this->allowedIps[$ip], false, 30) . ' ';
                $inputs .= wf_Submit(__('Save'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('IP') . ' ' . __('Unknown'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('IP') . ' ' . __('is empty'), 'error');
        }
        return($result);
    }

    /**
     * Creates new IP ACL
     * 
     * @param string $ip
     * @param string $notes
     * 
     * @return void/string on error
     */
    public function createIpAcl($ip, $notes = '') {
        $result = '';
        $ip = trim($ip);
        if (!empty($ip)) {
            if (zb_isIPValid($ip)) {
                if (!isset($this->allowedIps[$ip])) {
                    if ($ip != '127.0.0.1') {
                        file_put_contents(IPACLALLOWIP_PATH . $ip, $notes);
                        log_register('IPACL CREATE IP `' . $ip . '`');
                    } else {
                        $result .= __('Access from localhost is always enabled by default');
                        log_register('IPACL CREATE FAIL IP `' . $ip . '` LOCALHOST');
                    }
                } else {
                    $result .= __('This IP is already allowed') . ': ' . $ip;
                    log_register('IPACL CREATE FAIL IP `' . $ip . '` DUPLICATE');
                }
            } else {
                $result = __('IP') . ' ' . __('wrong');
                log_register('IPACL CREATE FAIL IP `' . $ip . '` WRONG_FORMAT');
            }
        } else {
            $result = __('IP') . ' ' . __('is empty');
            log_register('IPACL CREATE FAIL IP EMPTY');
        }
        return($result);
    }

    /**
     * Edits new IP ACL notes
     * 
     * @param string $ip
     * @param string $notes
     * 
     * @return void/string on error
     */
    public function saveIpAcl($ip, $notes = '') {
        $result = '';
        $ip = trim($ip);
        if (!empty($ip)) {
            if (zb_isIPValid($ip)) {
                if (isset($this->allowedIps[$ip])) {
                    file_put_contents(IPACLALLOWIP_PATH . $ip, $notes);
                    log_register('IPACL EDIT IP `' . $ip . '`');
                } else {
                    $result .= __('IP') . ' ' . __('Unknown') . ': ' . $ip;
                    log_register('IPACL EDIT FAIL IP `' . $ip . '` NOT_EXISTS');
                }
            } else {
                $result = __('IP') . ' ' . __('wrong');
                log_register('IPACL EDIT FAIL IP `' . $ip . '` WRONG_FORMAT');
            }
        } else {
            $result = __('IP') . ' ' . __('is empty');
            log_register('IPACL EDIT FAIL IP EMPTY');
        }
        return($result);
    }

    /**
     * Deletes existing IP ACL
     * 
     * @param string $ip
     * 
     * @return void/string on error
     */
    public function deleteIpAcl($ip) {
        $result = '';
        if (!empty($ip)) {
            if (isset($this->allowedIps[$ip])) {
                unlink(IPACLALLOWIP_PATH . $ip);
            } else {
                $result = __('IP') . ' ' . __('Unknown');
                log_register('IPACL DELETE FAIL IP `' . $ip . '` UNKNOWN');
            }
        } else {
            $result = __('IP') . ' ' . __('is empty');
            log_register('IPACL DELETE FAIL IP EMPTY');
        }
        return($result);
    }

    /**
     * Returns list of available networks ACLs with some controls
     * 
     * @return string
     */
    public function renderNetAclsList() {
        $result = '';
        if (!empty($this->allowedNets)) {
            $cells = wf_TableCell(__('Network') . '/' . __('CIDR'), '20%');
            $cells .= wf_TableCell(__('First IP') . ' - ' . __('Last IP'));
            $cells .= wf_TableCell(__('Notes'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allowedNets as $eachNetwork => $eachNote) {
                $eachNetId = str_replace('/', '_', $eachNetwork);
                $specialNotes = '';
                $deleteNote = '';
                $networkParams = ipcidrToStartEndIP($eachNetwork);
                if (multinet_checkIP($this->myIp, $networkParams['startip'], $networkParams['endip'])) {
                    $specialNotes .= ' ' . $this->colorize(__('You are here'), self::COLOR_HERE);

                    $deleteNote .= $this->colorize(__('Think twice. This may block access for you') . '!', self::COLOR_ALERT);
                    $deleteNote .= wf_delimiter(0);
                }
                $deleteNote .= $this->messages->getDeleteAlert();

                $cells = wf_TableCell($eachNetwork);
                $cells .= wf_TableCell($networkParams['startip'] . ' - ' . $networkParams['endip']);
                $cells .= wf_TableCell($eachNote . $specialNotes);
                $deleteUrl = self::URL_ME . '&' . self::ROUTE_DELNETACL . '=' . $eachNetId;
                $dialogTitle = __('Delete') . ' ' . $eachNetwork . '?';
                $actLinks = wf_ConfirmDialog($deleteUrl, web_delete_icon(), $deleteNote, '', self::URL_ME, $dialogTitle) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . $eachNetwork, $this->renderNetAclEditForm($eachNetwork));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            if (empty($this->allowedIps) AND empty($this->allowedNets)) {
                $result = $this->messages->getStyledMessage(__('Access is allowed from anywhere'), 'success');
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing to show'), 'info');
            }
        }
        return($result);
    }

    /**
     * Renders network ACL creation form
     * 
     * @return string
     */
    protected function renderNetAclCreateForm() {
        $result = '';
        $inputs = wf_TextInput(self::PROUTE_NEWNETACLNET, __('Network') . '/' . __('CIDR'), '', false, 20, 'net-cidr') . ' ';
        $inputs .= wf_TextInput(self::PROUTE_NEWNETACLNOTE, __('Notes'), '', false, 30) . ' ';
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders network ACL notes edit form
     * 
     * @param string $netcidr
     * 
     * @return string
     */
    protected function renderNetAclEditForm($netcidr) {
        $result = '';
        if (!empty($netcidr)) {
            if (isset($this->allowedNets[$netcidr])) {
                $inputs = wf_HiddenInput(self::PROUTE_EDNETACLNET, $netcidr);
                $inputs .= wf_TextInput(self::PROUTE_EDNETACLNOTE, __('Notes'), $this->allowedNets[$netcidr], false, 30) . ' ';
                $inputs .= wf_Submit(__('Save'));
                $result .= wf_Form('', 'POST', $inputs, 'glamour');
            } else {
                $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Network') . ' ' . __('Unknown'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Network') . ' ' . __('is empty'), 'error');
        }
        return($result);
    }

    /**
     * Creates new network ACL
     * 
     * @param string $netcidr
     * @param string $notes
     * 
     * @return void/string on error
     */
    public function createNetAcl($netcidr, $notes = '') {
        $result = '';
        if (!empty($netcidr)) {
            if (!isset($this->allowedNets[$netcidr])) {
                $netId = str_replace('/', '_', $netcidr);
                file_put_contents(IPACLALLOWNETS_PATH . $netId, $notes);
                log_register('IPACL CREATE NET `' . $netcidr . '`');
            } else {
                $result .= __('This network is already allowed') . ': ' . $netcidr;
                log_register('IPACL CREATE FAIL NET `' . $netcidr . '` DUPLICATE');
            }
        } else {
            $result = __('Network') . ' ' . __('is empty');
            log_register('IPACL CREATE FAIL NET EMPTY');
        }
        return($result);
    }

    /**
     * Edits network ACL notes
     * 
     * @param string $netcidr
     * @param string $notes
     * 
     * @return void/string on error
     */
    public function saveNetAcl($netcidr, $notes = '') {
        $result = '';
        if (!empty($netcidr)) {
            if (isset($this->allowedNets[$netcidr])) {
                $netId = str_replace('/', '_', $netcidr);
                file_put_contents(IPACLALLOWNETS_PATH . $netId, $notes);
                log_register('IPACL EDIT NET `' . $netcidr . '`');
            } else {
                $result .= __('Network') . ' ' . __('Unknown') . ': ' . $netcidr;
                log_register('IPACL EDIT FAIL NET `' . $netcidr . '` NOT_EXISTS');
            }
        } else {
            $result = __('Network') . ' ' . __('is empty');
            log_register('IPACL EDIT FAIL NET EMPTY');
        }
        return($result);
    }

    /**
     * Deletes existing network ACL
     * 
     * @param string $net
     * 
     * @return void/string on error
     */
    public function deleteNetAcl($netId) {
        $result = '';
        if (!empty($netId)) {
            $netCidr = str_replace('_', '/', $netId);
            if (isset($this->allowedNets[$netCidr])) {
                unlink(IPACLALLOWNETS_PATH . $netId);
                log_register('IPACL DELETE NET `' . $netCidr . '`');
            } else {
                $result = __('Network') . ' ' . $netCidr . ' ' . __('Unknown');
                log_register('IPACL DELETE FAIL NET `' . $netCidr . '` UNKNOWN');
            }
        } else {
            $result = __('Network') . ' ' . __('is empty');
            log_register('IPACL DELETE FAIL NET EMPTY');
        }
        return($result);
    }

    /**
     * Returns current adminitstator IP address
     * 
     * @return string
     */
    protected function renderMyCurrentIp() {
        $result = '';
        $result .= wf_tag('div', false, '', 'style="width:400px;"');
        $result .= $this->messages->getStyledMessage(__('Your IP address now is') . ': ' . $this->myIp, 'info');
        $result .= wf_tag('div', true);
        return($result);
    }

}

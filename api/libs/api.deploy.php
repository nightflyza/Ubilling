<?php

class Avarice {

    private $data = array();
    private $serial = '';
    private $raw = array();

    public function __construct() {
        $this->getSerial();
        $this->load();
    }

    /**
     * encodes data string by some sey
     * 
     * @param $data data to encode
     * @param $key  encoding key
     * 
     * @return binary
     */
    protected function xoror($data, $key) {
        $result = '';
        for ($i = 0; $i < strlen($data);) {
            for ($j = 0; $j < strlen($key); $j++, $i++) {
                @$result .= $data{$i} ^ $key{$j};
            }
        }
        return($result);
    }

    /**
     * pack xorored binary data into storable ascii data
     * 
     * @param $data
     * 
     * 
     * @return string
     */
    protected function pack($data) {
        $data = base64_encode($data);
        return ($data);
    }

    /**
     * unpack packed ascii data into xorored binary
     * 
     * @param $data
     * 
     * 
     * @return string
     */
    protected function unpack($data) {
        $data = base64_decode($data);
        return ($data);
    }

    /**
     * loads all stored licenses into private data prop
     * 
     * @return void
     */
    protected function load() {
        if (!empty($this->serial)) {
            $query = "SELECT * from `ubstorage` WHERE `key` LIKE 'AVLICENSE_%'";
            $keys = simple_queryall($query);
            if (!empty($keys)) {
                foreach ($keys as $io => $each) {
                    if (!empty($each['value'])) {
                        $unpack = $this->unpack($each['value']);
                        $unenc = $this->xoror($unpack, $this->serial);
                        @$unenc = unserialize($unenc);
                        if (!empty($unenc)) {
                            if (isset($unenc['AVARICE'])) {
                                if (isset($unenc['AVARICE']['SERIAL'])) {
                                    if ($this->serial == $unenc['AVARICE']['SERIAL']) {
                                        if (isset($unenc['AVARICE']['MODULE'])) {
                                            if (!empty($unenc['AVARICE']['MODULE'])) {
                                                $this->data[$unenc['AVARICE']['MODULE']] = $unenc[$unenc['AVARICE']['MODULE']];
                                                $this->raw[$unenc['AVARICE']['MODULE']]['LICENSE'] = $each['value'];
                                                $this->raw[$unenc['AVARICE']['MODULE']]['MODULE'] = $unenc['AVARICE']['MODULE'];
                                                $this->raw[$unenc['AVARICE']['MODULE']]['KEY'] = $each['key'];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * gets ubilling system key into private key prop
     * 
     * @return void
     */
    protected function getSerial() {
        $hostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
        $hostid = simple_query($hostid_q);
        if (!empty($hostid)) {
            if (isset($hostid['value'])) {
                $this->serial = $hostid['value'];
            }
        }
    }

    /**
     * checks module license availability
     * 
     * @param $module module name to check
     * 
     * @return bool
     */
    protected function check($module) {
        if (!empty($module)) {
            if (isset($this->data[$module])) {
                return (true);
            } else {
                return(false);
            }
        }
    }

    /**
     * returns module runtime 
     * 
     * @return array
     */
    public function runtime($module) {
        $result = array();
        if ($this->check($module)) {
            $result = $this->data[$module];
        }
        return ($result);
    }

    /**
     * returns list available license keys
     * 
     * @return array
     */
    public function getLicenseKeys() {
        return ($this->raw);
    }

    /**
     * check license key before storing it
     * 
     * @param $key string key to check valid format
     * 
     * @return bool
     */
    protected function checkLicenseValidity($key) {
        @$key = $this->unpack($key);
        @$key = $this->xoror($key, $this->serial);
        @$key = unserialize($key);
        if (!empty($key)) {
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * public function that deletes key from database
     * 
     * @param $keyname string identify key into database
     * 
     * @return void
     */
    public function deleteKey($keyname) {
        $keyname = mysql_real_escape_string($keyname);
        $query = "DELETE from `ubstorage` WHERE `key` = '" . $keyname . "';";
        nr_query($query);
        log_register("AVARICE DELETE KEY `" . $keyname . '`');
    }

    /**
     * installs new license key
     * 
     * @param $key string valid license key
     * 
     * @return bool
     */
    public function createKey($key) {
        $key = mysql_real_escape_string($key);
        if ($this->checkLicenseValidity($key)) {
            $keyname = 'AVLICENSE_' . zb_rand_string('8');
            $query = "INSERT INTO `ubstorage` (`id`, `key`, `value`) VALUES (NULL, '" . $keyname . "', '" . $key . "');";
            nr_query($query);
            log_register("AVARICE INSTALL KEY `" . $keyname . '`');
            return(true);
        } else {
            log_register("AVARICE TRY INSTALL WRONG KEY");
            return (false);
        }
    }

    /**
     * updates existing license key
     */
    public function updateKey($index, $key) {
        if ($this->checkLicenseValidity($key)) {
            simple_update_field('ubstorage', 'value', $key, "WHERE `key`='" . $index . "'");
            log_register("AVARICE UPDATE KEY `" . $index . '`');
            return(true);
        } else {
            log_register("AVARICE TRY UPDATE WRONG KEY");
            return (false);
        }
    }

}

/**
 * Renders available license keys with all of required controls 
 * 
 * @return void
 */
function zb_LicenseLister() {
    $avarice = new Avarice();
    $all = $avarice->getLicenseKeys();


    $cells = wf_TableCell(__('License key'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            //construct edit form
            $editinputs = wf_HiddenInput('editdbkey', $each['KEY']);
            $editinputs.= wf_TextArea('editlicense', '', $each['LICENSE'], true, '50x10');
            $editinputs.= wf_Submit(__('Save'));
            $editform = wf_Form("", 'POST', $editinputs, 'glamour');
            $editcontrol = wf_modal(web_edit_icon(), __('Edit') . ' ' . $each['MODULE'], $editform, '', '500', '300');
            //construct deletion controls
            $deletecontrol = wf_JSAlert('?module=licensekeys&licensedelete=' . $each['KEY'], web_delete_icon(), __('Removing this may lead to irreparable results'));

            $cells = wf_TableCell($each['MODULE']);
            $cells.= wf_TableCell($deletecontrol . ' ' . $editcontrol);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }


    //constructing license creation form
    $addinputs = wf_TextArea('createlicense', '', '', true, '50x10');
    $addinputs.= wf_Submit(__('Save'));
    $addform = wf_Form("", 'POST', $addinputs, 'glamour');
    $addcontrol = wf_modal(wf_img('skins/icon_add.gif') . ' ' . __('Install license key'), __('Install license key'), $addform, 'ubButton', '500', '300');

    $result = wf_TableBody($rows, '100%', 0, '');
    $result.= $addcontrol;
    show_window(__('Installed license keys'), $result);
}

?>

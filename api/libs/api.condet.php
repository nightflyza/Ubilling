<?php

/*
 * Connection (signup) details base class
 */

class ConnectionDetails {

    protected $allDetails = array();

    public function __construct() {
        $this->loadAllData();
    }

    /**
     * Loads all connection details from database and
     * stores into private prop as login=>dataarray
     * 
     * @return void
     */
    protected function loadAllData() {
        $query = "SELECT * from `condet`";
        $raw = simple_queryall($query);
        if (!empty($raw)) {
            foreach ($raw as $io => $each) {
                $this->allDetails[$each['login']] = $each;
            }
        }
    }

    /**
     * Returns array of connection details by user login
     * 
     * @param string $login
     * @return array
     */
    public function getByLogin($login) {
        if (isset($this->allDetails[$login])) {
            $result = $this->allDetails[$login];
        } else {
            $result = array();
        }
        return ($result);
    }

    /**
     * Creates new DB entry for some login
     * 
     * @param string $login
     * @param string $seal
     * @param string $length
     * @param string $price
     * 
     * @return void
     */
    protected function create($login, $seal, $length, $price) {
        $login = mysql_real_escape_string($login);
        $seal = mysql_real_escape_string($seal);
        $length = mysql_real_escape_string($length);
        $price = mysql_real_escape_string($price);
        $query = "INSERT INTO `condet` (`id`,`login`,`seal`,`length`,`price`) VALUES (NULL,'" . $login . "','" . $seal . "','" . $length . "', '" . $price . "');";
        nr_query($query);
    }

    /**
     * Updates existing DB entry for some login
     * 
     * @param string $login
     * @param string $seal
     * @param string $length
     * @param string $price
     * 
     * @return void
     */
    protected function update($login, $seal, $length, $price) {
        $login = mysql_real_escape_string($login);
        simple_update_field('condet', 'seal', $seal, "WHERE `login`='" . $login . "';");
        simple_update_field('condet', 'length', $length, "WHERE `login`='" . $login . "';");
        simple_update_field('condet', 'price', $price, "WHERE `login`='" . $login . "';");
    }

    /**
     * Sets login connection data into database in needed way
     * 
     * @param string $login
     * @param string $seal
     * @param string $length
     * @param string $price
     * 
     * @return void
     */
    public function set($login, $seal, $length, $price) {
        if (isset($this->allDetails[$login])) {
            $this->update($login, $seal, $length, $price);
        } else {
            $this->create($login, $seal, $length, $price);
        }
        log_register('CONDET SET (' . $login . ') SEAL `' . $seal . '` LENGTH `' . $length . '` PRICE `' . $price . '`');
    }

    /**
     * Retuns connection details edit form
     * 
     * @param string $login
     * @return string
     */
    public function editForm($login) {
        $login = mysql_real_escape_string($login);
        $currentData = $this->getByLogin($login);

        $inputs = wf_TextInput('newseal', __('Cable seal'), @$currentData['seal'], true, '40');
        $inputs.= wf_TextInput('newlength', __('Cable length') . ', ' . __('m'), @$currentData['length'], true, '5');
        $inputs.= wf_TextInput('newprice', __('Signup price'), @$currentData['price'], true, '5');
        $inputs.= wf_HiddenInput('editcondet', 'true');
        $inputs.= wf_tag('br');
        $inputs.= wf_Submit(__('Save'));

        $result = wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders connection details data for profile and edit form
     * 
     * @param string $login
     * @return string
     */
    public function renderData($login) {
        $currentData = $this->getByLogin($login);
        $result = '';
        if (!empty($currentData)) {
            if (!empty($currentData['seal'])) {
                $result.=__('Seal') . ': ' . $currentData['seal'] . ' ';
            }

            if (!empty($currentData['price'])) {
                $result.=__('Cost') . ': ' . $currentData['price'] . ' ';
            }

            if (!empty($currentData['length'])) {
                $result.=__('Cable') . ': ' . $currentData['length'] . __('m');
            }
        }
        return ($result);
    }

    /*
      Now it's too late, too late to live
      and my conscience killing me
      so am I alive
      but I'm not free

      and for all of you that can relate to this too
      and for all of you that can relate to this too
     */

    /**
     * Returns array of all existing cable seals
     * 
     * @return array
     */
    public function getAllSeals() {
        $result = array();
        if (!empty($this->allDetails)) {
            foreach ($this->allDetails as $io => $each) {
                if (!empty($each['seal'])) {
                    $result[$each['login']] = $each['seal'];
                }
            }
        }
        return ($result);
    }

    /**
     * Returns display container of available connection details
     * 
     * @return string
     */
    public function renderReportBody() {
        $columns = array('Address', 'Real Name', 'IP', 'Tariff', 'Active', 'Cash', 'Credit', 'Seal', 'Cost', 'Cable');
        $result = wf_JqDtLoader($columns, '?module=report_condet&ajax=true', true, 'users');

        return ($result);
    }

    /**
     * Returns display container of available connection details
     * 
     * @return string
     */
    public function renderReportBodyUkv() {
        $columns = array('Address', 'Real Name', 'Tariff', 'Connected', 'Cash', 'Seal');
        $result = wf_JqDtLoader($columns, '?module=report_condet&ajaxukv=true', true, 'users');

        return ($result);
    }

    /**
     * Returns JSON reply for jquery datatables with full list of available connection details
     * 
     * @return void
     */
    public function ajaxGetData() {
        $query = "SELECT * from `condet`;";
        $all = simple_queryall($query);
        $alladdress = zb_AddressGetFulladdresslist();
        $allrealnames = zb_UserGetAllRealnames();
        $allStgData_raw = zb_UserGetAllStargazerData();
        $userData = array();
        if (!empty($allStgData_raw)) {
            foreach ($allStgData_raw as $io => $each) {
                $userData[$each['login']] = $each;
            }
        }

        $result = '{ 
                  "aaData": [ ';

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $profileLink = wf_Link('?module=userprofile&username=' . $each['login'], web_profile_icon() . ' ', false);
                $profileLink = str_replace('"', '', $profileLink);
                $profileLink = str_replace("'", '', $profileLink);
                $profileLink = trim($profileLink);

                $userAddress = @$alladdress[$each['login']];
                $userAddress = str_replace("'", '`', $userAddress);
                $userAddress = str_replace('"', '``', $userAddress);
                $userAddress = trim($userAddress);

                $userRealname = @$allrealnames[$each['login']];
                $userRealname = str_replace("'", '`', $userRealname);
                $userRealname = str_replace('"', '``', $userRealname);
                $userRealname = trim($userRealname);

                @$cash = $userData[$each['login']]['Cash'];
                @$credit = $userData[$each['login']]['Credit'];

                $act = wf_img('skins/icon_active.gif') . __('Yes');
                //finance check
                if ($cash < '-' . $credit) {
                    $act = wf_img('skins/icon_inactive.gif') . __('No');
                }


                $act = str_replace('"', '', $act);
                $act = trim($act);

                $result.='
                    [
                    "' . $profileLink . $userAddress . '",
                    "' . $userRealname . '",
                    "' . @$userData[$each['login']]['IP'] . '",
                    "' . @$userData[$each['login']]['Tariff'] . '",
                    "' . $act . '",
                    "' . $cash . '",
                    "' . $credit . '",
                    "' . $each['seal'] . '",
                    "' . $each['price'] . '",
                    "' . $each['length'] . '",
                    "' . 'CREDIT' . '"
                    ],';
            }
        }

        $result = substr($result, 0, -1);

        $result.='] 
        }';

        die($result);
    }

    /**
     * Returns JSON reply for jquery datatables with full list of available connection details
     * 
     * @return void
     */
    public function ajaxGetDataUkv() {
        $ukv = new UkvSystem();

        $query = "SELECT * from `ukv_users` WHERE `cableseal` != '' ;";
        $all = simple_queryall($query);

        $result = '{ 
                  "aaData": [ ';

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $profileLink = wf_Link('?module=ukv&users=true&showuser=' . $each['id'], web_profile_icon() . ' ', false);
                $profileLink = str_replace('"', '', $profileLink);
                $profileLink = str_replace("'", '', $profileLink);
                $profileLink = trim($profileLink);

                $userAddress = @$ukv->userGetFullAddress($each['id']);
                $userAddress = str_replace("'", '`', $userAddress);
                $userAddress = str_replace('"', '``', $userAddress);
                $userAddress = trim($userAddress);

                $userRealname = $each['realname'];
                $userRealname = str_replace("'", '`', $userRealname);
                $userRealname = str_replace('"', '``', $userRealname);
                $userRealname = trim($userRealname);



                $act = wf_img('skins/icon_active.gif') . __('Yes');
                //finance check
                if (!$each['active']) {
                    $act = wf_img('skins/icon_inactive.gif') . __('No');
                }

                $act = str_replace('"', '', $act);
                $act = trim($act);

                $result.='
                    [
                    "' . $profileLink . $userAddress . '",
                    "' . $userRealname . '",
                    "' . $ukv->tariffGetName($each['tariffid']) . '",
                    "' . $act . '",
                    "' . $each['cash'] . '",
                    "' . $each['cableseal'] . '"
                    ],';
            }
        }

        $result = substr($result, 0, -1);

        $result.='] 
        }';

        die($result);
    }

}

?>
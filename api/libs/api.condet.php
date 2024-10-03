<?php

/**
 * Connection (signup) details base class
 */
class ConnectionDetails {

    /**
     * Contains all signup details data
     *
     * @var array
     */
    protected $allDetails = array();

    /**
     * Connection/signup details database abstraction layer
     *
     * @var object
     */
    protected $condetDb = '';

    const TABLE_CONDER = 'condet';
    const URL_ME = '?module=condetedit&username=';

    /**
     * Creates new condet instance
     * 
     * @return void
     */
    public function __construct() {
        $this->initDb();
        $this->loadAllData();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initDb() {
        $this->condetDb = new NyanORM(self::TABLE_CONDER);
    }

    /**
     * Loads all connection details from database and
     * stores into private prop as login=>dataarray
     * 
     * @return void
     */
    protected function loadAllData() {
        $this->allDetails = $this->condetDb->getAll('login');
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
     * @param int $length
     * @param string $price
     * @param int $term
     * 
     * @return void
     */
    protected function create($login, $seal, $length, $price, $term = 0) {
        $login = ubRouting::filters($login, 'mres');
        $seal = ubRouting::filters($seal, 'mres');
        $length = ubRouting::filters($length, 'int');
        $price = ubRouting::filters($price, 'mres');
        $term = ubRouting::filters($term, 'int');

        $this->condetDb->data('login', $login);
        $this->condetDb->data('seal', $seal);
        $this->condetDb->data('length', $length);
        $this->condetDb->data('price', $price);
        $this->condetDb->data('term', $term);
        $this->condetDb->create();
    }

    /**
     * Deletes signup details from database
     * 
     * @param string $login
     * 
     * @return void
     */
    public function delete($login) {
        $login = ubRouting::filters($login, 'mres');
        $this->condetDb->where('login', '=', $login);
        $this->condetDb->delete();
    }

    /**
     * Updates existing DB entry for some login
     * 
     * @param string $login
     * @param string $seal
     * @param int $length
     * @param string $price
     * @param int $term
     * 
     * @return void
     */
    protected function update($login, $seal, $length, $price, $term = 0) {
        $login = ubRouting::filters($login, 'mres');
        $length = ubRouting::filters($length, 'int');

        $this->condetDb->data('seal', $seal);
        $this->condetDb->data('length', $length);
        $this->condetDb->data('price', $price);
        $this->condetDb->data('term', $term);
        $this->condetDb->where('login', '=', $login);
        $this->condetDb->save();
    }

    /**
     * Sets login connection data into database in needed way
     * 
     * @param string $login
     * @param string $seal
     * @param string $length
     * @param string $price
     * @param int $term
     * 
     * @return void
     */
    public function set($login, $seal, $length, $price, $term = 0) {
        if (!zb_checkMoney($price)) {
            $price = 0;
        }
        if (isset($this->allDetails[$login])) {
            $this->update($login, $seal, $length, $price, $term);
        } else {
            $this->create($login, $seal, $length, $price, $term);
        }

        log_register('CONDET SET (' . $login . ') SEAL `' . $seal . '` LENGTH `' . $length . '` PRICE `' . $price . '`');
    }

    /**
     * Retuns connection details edit form
     * 
     * @param string $login
     * 
     * @return string
     */
    public function editForm($login) {
        $login = ubRouting::filters($login, 'mres');
        $currentData = $this->getByLogin($login);

        $inputs = wf_TextInput('newseal', __('Cable seal'), @$currentData['seal'], true, '40');
        $inputs .= wf_TextInput('newlength', __('Cable length') . ', ' . __('m'), @$currentData['length'], true, 5, 'digits');
        $inputs .= wf_TextInput('newprice', __('Signup price'), @$currentData['price'], true, 5, 'finance');
        $inputs .= wf_TextInput('newterm', __('Signup term'), @$currentData['term'], true, 5, 'digits');
        $inputs .= wf_HiddenInput('editcondet', 'true');
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Save'));

        $result = wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders connection details data for profile and edit form
     * 
     * @param string $login
     * 
     * @return string
     */
    public function renderData($login) {
        $currentData = $this->getByLogin($login);
        $result = '';
        if (!empty($currentData)) {
            if (!empty($currentData['seal'])) {
                $result .= __('Seal') . ': ' . $currentData['seal'] . ' ';
            }

            if (!empty($currentData['price'])) {
                $result .= __('Cost') . ': ' . $currentData['price'] . ' ';
            }

            if (!empty($currentData['length'])) {
                $result .= __('Cable') . ': ' . $currentData['length'] . __('m');
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
     * Returns array of all existing cable seals as login=>seal
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
     * Returns array of all existing details data as login=>condetData[seal,length,price,term]
     * 
     * @return array
     */
    public function getAllData() {
        return ($this->allDetails);
    }

    /**
     * Returns display container of available connection details
     * 
     * @return string
     */
    public function renderReportBody() {
        $columns = array('ID', 'Address', 'Real Name', 'IP', 'Tariff', 'Active', 'Cash', 'Credit', 'Seal', 'Cost', 'Cable');
        $result = wf_JqDtLoader($columns, '?module=report_condet&ajax=true', true, 'users');

        return ($result);
    }

    /**
     * Returns display container of available connection details
     * 
     * @return string
     */
    public function renderReportBodyUkv() {
        $columns = array('ID', 'Address', 'Real Name', 'Tariff', 'Connected', 'Cash', 'Seal');
        $result = wf_JqDtLoader($columns, '?module=report_condet&ajaxukv=true', true, 'users');

        return ($result);
    }

    /**
     * Returns JSON reply for jquery datatables with full list of available connection details
     * 
     * @return void
     */
    public function ajaxGetData() {
        $all = $this->condetDb->getAll();
        $allUserData = zb_UserGetAllDataCache();
        $rowData = array();
        $jsonData = new wf_JqDtHelper();



        if (!empty($all)) {
            foreach ($all as $io => $each) {
                if (isset($allUserData[$each['login']])) {
                    $userData = $allUserData[$each['login']];
                    $profileLink = wf_Link(UserProfile::URL_PROFILE . $each['login'], web_profile_icon() . ' ', false);
                    $cash = $userData['Cash'];
                    $credit = $userData['Credit'];
                    $act = wf_img('skins/icon_active.gif') . __('Yes');
                    //finance check
                    if ($cash < '-' . $credit) {
                        $act = wf_img('skins/icon_inactive.gif') . __('No');
                    }

                    $rowData[] = $each['id'];
                    $rowData[] = $profileLink . $userData['fulladress'];
                    $rowData[] = $userData['realname'];
                    $rowData[] = $userData['ip'];
                    $rowData[] = $userData['Tariff'];
                    $rowData[] = $act;
                    $rowData[] = $cash;
                    $rowData[] = $credit;
                    $rowData[] = $each['seal'];
                    $rowData[] = $each['price'];
                    $rowData[] = $each['length'];

                    $jsonData->addRow($rowData);
                    unset($rowData);
                }
            }
        }

        $jsonData->getJson();
    }

    /**
     * Returns JSON reply for jquery datatables with full list of available connection details
     * 
     * @return void
     */
    public function ajaxGetDataUkv() {
        $ukv = new UkvSystem();
        $jsonData = new wf_JqDtHelper();

        $query = "SELECT * from `ukv_users` WHERE `cableseal` != '' ;";
        $all = simple_queryall($query);
        $rowData = array();


        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $profileLink = wf_Link('?module=ukv&users=true&showuser=' . $each['id'], web_profile_icon() . ' ', false);
                $userAddress = @$ukv->userGetFullAddress($each['id']);
                $userRealname = $each['realname'];

                $act = wf_img('skins/icon_active.gif') . __('Yes');
                //finance check
                if (!$each['active']) {
                    $act = wf_img('skins/icon_inactive.gif') . __('No');
                }


                $rowData[] = $each['id'];
                $rowData[] = $profileLink . $userAddress;
                $rowData[] = $userRealname;
                $rowData[] = $ukv->tariffGetName($each['tariffid']);
                $rowData[] = $act;
                $rowData[] = $each['cash'];
                $rowData[] = $each['cableseal'];
                $jsonData->addRow($rowData);
                unset($rowData);
            }
        }

        $jsonData->getJson();
    }
}

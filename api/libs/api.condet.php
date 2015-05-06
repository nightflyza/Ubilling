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
        $query = "INSERT INTO `condet` (`id`,`login`,`seal`,`length`,`price`) VALUES (NULL,'" . $login . "','" . $seal . "','" . $length . "','" . $price . "');";
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
        $inputs.= wf_TextInput('newlength', __('Cable length'), @$currentData['length'], true, '5');
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
            $result = $currentData['seal'] . ' / ' . $currentData['length'] . ' / ' . $currentData['price'];
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
        $result=array();
        if (!empty($this->allDetails)) {
            foreach ($this->allDetails as $io=>$each) {
                if (!empty($each['seal'])) {
                    $result[$each['login']]=$each['seal'];
                }
            }
        }
        return ($result);
    }
}

?>
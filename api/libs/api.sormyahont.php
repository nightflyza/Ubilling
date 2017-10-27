<?php

class SormYahont {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains stargazer users table as login=>userdata
     *
     * @var array
     */
    protected $allUsers = array();

    /**
     * Contains users data with fields like address, realname, etc as login=>userdata
     *
     * @var array
     */
    protected $allUsersData = array();

    /**
     * Contains all contract dates as contract=>date
     *
     * @var array
     */
    protected $allContractDates = array();

    /**
     * Contains users passport data as login=>passportdata
     *
     * @var array
     */
    protected $AllPassportData = array();

    /**
     * Default branch ID
     *
     * @var int
     */
    protected $branchId = 1;

    /**
     * Ubilling database charset
     */
    const IN_CHARSET = 'utf-8';

    /**
     * Output charset
     */
    const OUT_CHARSET = 'windows-1251';

    /**
     * Default output CSV delimiter
     */
    const DELIMITER = ';';

    /**
     * Default CSV enclosure
     */
    const ENCLOSURE = '"';

    /**
     * Creates new SormYahont instance
     *
     * @return void 
     */
    public function __construct() {
        $this->loadAlter();
        $this->loadUsersData();
        $this->loadContractDates();
        $this->loadPassportData();
    }

    /**
     * Loads system alter config into protected property for further usage
     * 
     * @global type $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads users data from database into protected object props
     * 
     * @return void
     */
    protected function loadUsersData() {
        $this->allUsers = zb_UserGetAllStargazerDataAssoc();
        $this->allUsersData = zb_UserGetAllDataCache();
    }

    /**
     * Loads all contract dates
     * 
     * @return void
     */
    protected function loadContractDates() {
        $this->allContractDates = zb_UserContractDatesGetAll();
    }

    /**
     * Loads all users passport data
     * 
     * @return void
     */
    protected function loadPassportData() {
        $this->AllPassportData = zb_UserPassportDataGetAll();
    }

    /**
     * Encodes data to output charset before export
     * 
     * @param string $data
     * 
     * @return string
     */
    protected function changeCharset($data) {
        $data = iconv(self::IN_CHARSET, self::OUT_CHARSET, $data);
        return ($data);
    }

    /**
     * Converts single dimension array into CSV string data
     * 
     * @param array $fields
     * @param string $delimiter
     * @param string $enclosure
     * @param bool $encloseAll
     * @param bool $nullToMysqlNull
     * 
     * @return string
     */
    protected function arrayToCsv(array &$fields, $delimiter = ';', $enclosure = '"', $encloseAll = false, $nullToMysqlNull = false) {
        $delimiter_esc = preg_quote($delimiter, '/');
        $enclosure_esc = preg_quote($enclosure, '/');

        $output = array();
        foreach ($fields as $field) {
            if ($field === null && $nullToMysqlNull) {
                $output[] = 'NULL';
                continue;
            }

            // Enclose fields containing $delimiter, $enclosure or whitespace
            if ($encloseAll || preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field)) {
                $output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
            } else {
                $output[] = $field;
            }
        }

        return implode($delimiter, $output);
    }

    /**
     * Returns  user data squense 4.1
     * 
     * @return string
     */
    public function getUserData() {
        $result = '';
        if (!empty($this->allUsersData)) {
            foreach ($this->allUsersData as $io => $each) {
                $userLogin = $each['login'];
                $stgData = $this->allUsers[$userLogin];
                $userContract = $each['contract'];
                $userContractDate = @$this->allContractDates[$userContract];
                $userState = 1;
                //detecting user state
                if (($each['Cash'] <= $each['Credit']) OR ( $each['Passive'] == 1) OR ( $stgData['Down'] == 1) OR ( $each['AlwaysOnline'] == 0)) {
                    $userState = 0;
                }
                $dataTmp = array(
                    $this->branchId, //default branch
                    $userLogin, // login
                    $each['ip'], // ip
                    $each['email'], // email
                    $each['mobile'], // phone
                    $each['mac'], // mac
                    $userContractDate, //contract date
                    $userContract, //contract number
                    $userState, //user state
                    $userContractDate, //using contract date as service activation date
                    '', //using empty value as service deactivation date
                    0, // by default home user, may be we can detect corporative users (1) if CORPS_ENABLED
                    1, //single string user data fields
                    $each['realname'], //realname as single string
                    @$this->AllPassportData[$userLogin]['birthdate'], // birthdate
                    1, //single string passport data
                    //unsctruct passport data below
                    @$this->AllPassportData[$userLogin]['passportnum'].' '.@$this->AllPassportData[$userLogin]['passportdate'] .' '.@$this->AllPassportData[$userLogin]['passportwho'],
                    1, // i guess 1 is passport
                    '', //empty user bank
                    '', //empty bank account
                    //corporate users data below, now its unprocessed
                    '', //empty corp name
                    '', //empty INN
                    '', //empty contact person
                    '', //empty phones/faxes
                    '', //empty corp bank name
                    '', //empty corp bank account
                    1, // single string address data
                    $each['fulladress'], //single string address
                    1, //single string address
                    $each['fulladress'], //using user address as device address
                );
                $result.= $this->arrayToCsv($dataTmp, self::DELIMITER, self::ENCLOSURE, true) . PHP_EOL;
            }
        }
        //$result=  $this->changeCharset($result);
        return ($result);
    }

}

?>
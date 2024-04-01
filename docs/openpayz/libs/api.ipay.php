<?php

// API DOC:  https://walletmc.ipay.ua/doc.php
// notification API: http://store.nightfly.biz/st/1512477073/ipay_notify.pdf
class IpayMasterPass {

    /**
     * merchant sign key
     *
     * @var string
     */
    protected $sign_key = '';

    /**
     * Current requests date/time
     *
     * @var string
     */
    protected $curtime = '';

    /**
     * Merchants name
     *
     * @var string
     */
    protected $mch_id = '';

    /**
     * Default dialogs language
     *
     * @var string
     */
    protected $lang = 'ru';

    /**
     * Contains current merchant login
     *
     * @var string
     */
    protected $login = '';

    /**
     * Contains all available user mobile phones
     *
     * @var array
     */
    protected $allUserPhones = array();

    /**
     * Contains all available openpayz customers
     *
     * @var arrays
     */
    protected $allCustomers = array();

    /**
     * Ipay MasterPass API URL
     */
    const URL_API = 'https://walletmc.ipay.ua/';

    /**
     * Creates new object instance
     * 
     * @param string $mch_id
     * @param string $sign_key
     * @param string $lang
     * @param string $login
     * 
     * @return void
     */
    public function __construct($mch_id, $sign_key, $lang = '', $login = '') {
        $this->setTime();
        $this->setSign($sign_key);
        $this->setMchId($mch_id);
        $this->setLang($lang);
        $this->setLogin($login);
        $this->loadCustomers();
        $this->loadPhones();
    }

    /**
     * Sets current date/time for further requests
     * 
     * @return void
     */
    protected function setTime() {
        $this->curtime = date("Y-m-d H:i:s");
    }

    /**
     * Sets merchant name
     * 
     * @param string $mch_id
     * 
     * @return void
     */
    protected function setMchId($mch_id) {
        $this->mch_id = $mch_id;
    }

    /**
     * Sets default language
     * 
     * @param string $lang
     * 
     * @return void
     */
    protected function setLang($lang = '') {
        if (!empty($lang)) {
            $this->lang = $lang;
        }
    }

    /**
     * Sets current merch login
     * 
     * @param string $login
     * 
     * @return void
     */
    protected function setLogin($login = '') {
        if (!empty($login)) {
            $this->login = $login;
        }
    }

    /**
     * Sets private sing key
     * 
     * @param string $sign_key
     * 
     * @return void
     */
    protected function setSign($sign_key) {
        $this->sign_key = $sign_key;
    }

    /**
     * Loads all users phones from database for further usage
     * 
     * @return void
     */
    protected function loadPhones() {
        $query = "SELECT * from `phones`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUserPhones[$each['login']] = $each['mobile'];
            }
        }
    }

    /**
     * Loads customers array for further usage as vitrualid=>realid
     * 
     * @return void
     */
    protected function loadCustomers() {
        $this->allCustomers = op_CustomersGetAll();
    }

    /**
     * Returns some customers phone
     * 
     * @param string $customer_id
     * 
     * @return string
     */
    protected function getPhoneByCustomerId($customer_id) {
        $result = '';
        if (isset($this->allCustomers[$customer_id])) {
            $customerLogin = $this->allCustomers[$customer_id];
            if (isset($this->allUserPhones[$customerLogin])) {
                $result = $this->allUserPhones[$customerLogin];
            }
        }
        return ($result);
    }

    /**
     * Returns encoded auth key
     * 
     * @param string $data
     * 
     * @return string
     */
    protected function makeSign($data) {
        $result = md5($data . $this->sign_key);
        return ($result);
    }

    /**
     * Pushes some json POST data to API and returns result
     * 
     * @param string $request
     * 
     * @return array
     */
    protected function pushJsonRequest($request) {
        $curl = curl_init(self::URL_API);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        $json_response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            die('Error: call to URL ' . self::URL_API . ' failed with status ' . $status . ', response ' . $json_response . ', curl_error ' . curl_error($curl) . ', curl_errno ' . curl_errno($curl));
        }
        curl_close($curl);
        $result = json_decode($json_response, true);
        return ($result);
    }

    /**
     * Make phone format as 380xxxxxxxxxx
     * 
     * @param string $mobile
     * 
     * @return string
     */
    protected function normalizePhoneFormat($mobile) {
        $mobile = vf($mobile, 3);
        $len = strlen($mobile);
        //all is ok
        if ($len != 12) {
            switch ($len) {
                case 11:
                    $mobile = '3' . $mobile;
                    break;
                case 10:
                    $mobile = '38' . $mobile;
                    break;
                case 9:
                    $mobile = '380' . $mobile;
                    break;
            }
        }
        return ($mobile);
    }

    /**
     * Returns widget session for some user and mobile
     * 
     * @param string $user_id
     * 
     * @return array
     */
    public function InitWidgetSession($user_id) {
        $result = '';
        $userMobile = $this->getPhoneByCustomerId($user_id);
        $userMobile = $this->normalizePhoneFormat($userMobile);
        $request = '{
        "request": {
        "auth": {
            "login": "' . $this->login . '",
            "time": "' . $this->curtime . '",
            "sign": "' . $this->makeSign($this->curtime) . '"
        },
        "action": "InitWidgetSession",
        "body": {
            "msisdn": "' . $userMobile . '",
            "user_id": "' . $user_id . '",
            "pmt_desc": "Internet service for:' . $user_id . '",
            "pmt_info": {
                "acc": "' . $user_id . '"
                }
                }
                }
            }';

        if (!empty($userMobile)) {
            $result = $this->pushJsonRequest($request);
        }

        return ($result);
    }

    /**
     * Returns widget code for some session
     * 
     * @param string $session
     * 
     * @return string
     */
    public function getWidgetCode($session) {
        $result = '
            <script type="text/javascript" src="https://widgetmp.ipay.ua/widget.js"></script>
            <a href="#" onclick="MasterpassWidget.open(
            {
                partner: \'' . $this->mch_id . '\',
                lang: \'' . $this->lang . '\', 
                session: \'' . $session . '\'
            },
            function() {},
            function() {},
            function() {}
            );">
            <img src="https://widgetmp.ipay.ua/mp-button.svg">
            </a>';
        return ($result);
    }

}

/**
 * IPAY checkout API: https://checkout.ipay.ua/doc
 */
class IpayZ {

    protected $merchId = '';
    protected $signKey = '';
    protected $salt = '';
    protected $lang = 'ua';
    protected $urlSuccess = '';
    protected $urlFail = '';
    protected $currency = '';
    protected $lifeTime = 24;
    protected $payDesc = '';
    protected $UrlApi = 'https://api.ipay.ua';
    protected $api = '';

    public function __construct() {
        $this->setIOptions();
        $this->setSalt();
    }

    protected function setIOptions() {
        $conf = parse_ini_file('config/ipayz.ini');
        $this->merchId = $conf['MERCHANT_ID'];
        $this->signKey = $conf['SIGN'];
        $this->lang = $conf['LANG'];
        $this->urlSuccess = $conf['URL_OK'];
        $this->urlFail = $conf['URL_FAIL'];
        $this->currency = $conf['CURRENCY'];
        $this->lifeTime = $conf['TIMEOUT'];
        $this->payDesc = $conf['PAYMENT_DESC'];
        $this->UrlApi = $conf['API_URL'];
    }

    protected function setSalt() {
        $this->salt = sha1(microtime(true));
    }

    public function makeSign() {
        $result = hash_hmac('sha512', $this->salt, $this->signKey);
        return($result);
    }

    protected function pushRequest($request) {
        $curl = curl_init($this->UrlApi);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, 'data=' . $request . '&');
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($status != 200) {
            die('Error: call to URL ' . $this->UrlApi . ' failed with status ' . $status . ', response ' . $response . ', curl_error ' . curl_error($curl) . ', curl_errno ' . curl_errno($curl));
        }
        curl_close($curl);
        $result = $response;
        return ($result);
    }

    public function paymentCreate($paymentId = '', $summ = '') {
        $result = '';
        $xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>';
        $xml = '<payment>
                <auth>
                    <mch_id>' . $this->merchId . '</mch_id>
                    <salt>' . $this->salt . '</salt>
                    <sign>' . $this->makeSign() . '</sign>
                </auth>
                <urls>
                    <good>' . $this->urlSuccess . '</good>
                    <bad>' . $this->urlFail . '</bad>
                </urls>
                <transactions>
                    <transaction>
                        <amount>' . $summ . '</amount>
                        <currency>' . $this->currency . '</currency>
                        <desc>' . $this->payDesc . '</desc>
                        <info>{"acc":' . $paymentId . '}</info>
                    </transaction>
                </transactions>
                <lifetime>' . $this->lifeTime . '</lifetime>
                <lang>' . $this->lang . '</lang>
            </payment>';

        $reply = $this->pushRequest($xml);
        if (!empty($reply)) {
            $replyArr = xml2array($reply);
            if (isset($replyArr['payment'])) {
                $result = $replyArr['payment']['url'];
            } else {
                die('WRONG_REPLY');
            }
        } else {
            die('EMPTY_REPYLY');
        }
        return($result);
    }

}

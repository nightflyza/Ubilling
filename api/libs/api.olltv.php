<?php

/**
 * Oll.tv ispAPI class
 * @author          Prakapas Andriy <prakapas@general-servers.com>
 * @copyright       2016 GeneralServers LLC
 * @license         http://www.apache.org/licenses/LICENSE-2.0
 * @version         2.1.0 - for same ispAPI version 2.1.0
 * @link            https://general-servers.com
 * @link            https://github.com/General-Servers/Oll.tv
 */
class OllTv {
    /* Oll.tv constants */

    const OTV_URL = 'http://oll.tv/';            // main production url
    const OTV_URL_DEV = 'http://dev.oll.tv/';    // development url for test
    const OTV_URL_API = 'ispAPI';                // main api url
    const OTV_URL_AUTH = 'auth2';                // auth url

    /* log constants */
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3;

    /**
     * Client ispAPI login
     * Assign in constructor
     * @var string
     */
    protected $_login;

    /**
     * Client ispAPI password
     * Assign in constructor
     * @var string
     */
    protected $_password;

    /**
     * ispAPI hash
     * Hash return ispAPI; using in all requests
     * @var string
     */
    protected $_hash;

    /**
     * Result variable
     * Return ispAPI
     * @var string
     */
    protected $_result;

    /**
     * ispAPI url
     * @var string
     */
    protected $_url;


    /* log vars */

    /**
     * Path to log file
     * @var string
     */
    protected $_log;

    /**
     * Log level variable
     * 0 - not show any messages
     * 1 - only Errors - DEFAULT
     * 2 - Errors, Warnings
     * 3 - Errors, Warnings and Informations
     * @var integer
     */
    protected $_logLevel = 1;

    /**
     * Log message type
     * Related with $_logLevel var
     * @var array
     */
    protected $_logType = array(
        0 => '',
        1 => 'Error',
        2 => 'Warning',
        3 => 'Info'
    );

    /* --end log vars */


    /* error vars */

    /**
     * ispAPI errors array:
     * key - error status
     * value has short message and full description
     * @var array
     */
    protected $_errors = array(
        109 => array(
            'message' => 'Hash expired',
            'description' => 'Время действия хеша истекло или хеш не верен'
        ),
        110 => array(
            'message' => 'Authorization missed',
            'description' => 'Хеш не указан'
        ),
        111 => array(
            'message' => 'Auth failed',
            'description' => 'Неверный логин или пароль'
        ),
        112 => array(
            'message' => 'Login empty',
            'description' => 'Не указан логин'
        ),
        113 => array(
            'message' => 'Password empty',
            'description' => 'Не указан пароль'
        ),
        115 => array(
            'message' => 'Email already exists',
            'description' => 'Указанный имейл уже есть в БД'
        ),
        116 => array(
            'message' => 'Email validation failed',
            'description' => 'Формат указанного имейла неверен'
        ),
        117 => array(
            'message' => 'Result user account does not match provided',
            'description' => 'Не указан аккаунт или он не совпадает с аккаунтом на который подвязано устройство'
        ),
        119 => array(
            'message' => 'Device with provided mac or/and serial number already exist',
            'description' => 'Устройство с указанным мак-адресом и/или серийным номером уже присуствует в БД и за кем-то закреплено'
        ),
        120 => array(
            'message' => 'Wrong date format',
            'description' => "Неверный формат даты"
        ),
        200 => array(
            'message' => 'Required fields missed',
            'description' => "Остутствуют необходимые параметры"
        ),
        201 => array(
            'message' => 'Field email is required',
            'description' => "Отсутствует необходимый параметр email"
        ),
        203 => array(
            'message' => 'Neither mac nor serial_number was found in your request',
            'description' => "Отсутствуют параметры mac и serial_number"
        ),
        205 => array(
            'message' => 'Field new_email is required',
            'description' => "Отсутствует необходимый параметр new_email"
        ),
        301 => array(
            'message' => 'Registration failed. Contact technical support',
            'description' => "Ошибка добавления устройства пользователю или регистрации нового пользователя"
        ),
        302 => array(
            'message' => 'Wrong MAC address',
            'description' => "Неверный формат мак-адреса"
        ),
        303 => array(
            'message' => 'Wrong Serial number',
            'description' => "Неверный формат серийного номера"
        ),
        304 => array(
            'message' => 'Invalid binding code',
            'description' => "Неверный код привязки устройства"
        ),
        305 => array(
            'message' => 'No devices can be binded by this code',
            'description' => "Достигнут лимит кол-ва устройств, которые можно привязать по указанному коду привязки"
        ),
        404 => array(
            'message' => "Account not found",
            'description' => "Пользователь не найден в БД"
        ),
        405 => array(
            'message' => "Not eligible device_type",
            'description' => "Недопустимое значение в параметре device_type"
        ),
        406 => array(
            'message' => "Device not found in our DB",
            'description' => "Устройство не найдено в БД или оно отвязано от пользователя",
        ),
        407 => array(
            'message' => "Subscription not found",
            'description' => "Подписка, указанная в параметрах sub_id, new_sub_id или old_sub_id, не найдена"
        ),
        408 => array(
            'message' => "Subscription order violation",
            'description' => "Нарушение очерёдности отключения или включения услуги согласно подписке"
        ),
        501 => array(
            'message' => "Access denied",
            'description' => "Устройство привязано к пользователю другого провайдера"
        ),
        504 => array(
            'message' => "User already deactivated",
            'description' => "Услуга была уже выключена ранее"
        ),
        505 => array(
            'message' => "User is attached to another operator",
            'description' => "Пользователь привязан к другому провайдеру"
        ),
        506 => array(
            'message' => "Account is not active",
            'description' => "Аккаунт пользователя не активен"
        )
    );

    /**
     * Last message array
     * You can get last message by type even when log has disabled
     * @var array
     */
    protected $_lastMessage = array(
        0 => '', // empty
        1 => '', // last error message
        2 => '', // last warning message
        3 => ''  // last information message
    );

    /* --end error vars */

    /**
     * Assign last message
     * @param   string   $message  message text
     * @param   integer  $type     message type
     * @return  boolean
     */
    protected function _setLastMessage($message, $type) {
        // verify arguments
        if (!is_string($message) || !is_numeric($type)) {
            return false;
        }
        // verify type in last message array
        if (!isset($this->_lastMessage[$type])) {
            return false;
        }

        // assign message by type
        $this->_lastMessage[$type] = $message;
        return true;
    }

    /**
     * Write to log file
     * @param   string    $message    message text
     * @param   integer   $type       type of message
     * @return  boolean
     */
    protected function _toLog($message, $type = 1) {
        // verify arguments
        if (empty($message) || !is_string($message)) {
            return false;
        }
        // assign last message variable
        $this->_setLastMessage($message, $type);

        // verify log level
        if ($this->_logLevel == 0 || $type == 0 || !isset($this->_logType[$type])) {
            return false;
        }
        // compare $_logLevel and message type
        if ($type > $this->_logLevel) {
            return false;
        }
        // prepare message type
        $type = $this->_logType[$type] . ': ';
        // append write to file and get result
        $res = file_put_contents($this->_log, date('Y-m-d H:i:s') . ' ' . $type . $message . ";\n", FILE_APPEND);
        return (bool) $res;
    }

    /**
     * Method prepare default account data
     * @param  array $params parametters array
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @return array
     */
    protected function _prepareAccountDefaultData($params) {
        // init return array
        $args = array();

        // try find 'account'
        if (!empty($params['account'])) {
            $args = array(
                'account' => $params['account']
            );
        } else {
            $this->_toLog('[' . __FUNCTION__ . '] - `account` not found in parametter array', self::INFO);
        }

        // try find 'email'
        if (!empty($params['email']) && filter_var($params['email'], FILTER_VALIDATE_EMAIL)) {
            $args = array(
                'email' => $params['email']
            );
        } else {
            $this->_toLog('[' . __FUNCTION__ . '] - `email` not found in parametter array or `email` is not valid email', self::INFO);
        }

        // try find 'id'
        if (!empty($params['id']) && is_numeric($params['id'])) {
            $args = array(
                'id' => $params['id']
            );
        } else {
            $this->_toLog('[' . __FUNCTION__ . '] - `id` not found in parametter array', self::INFO);
        }

        // try find 'ds_account'
        if (!empty($params['ds_account'])) {
            $args = array(
                'ds_account' => $params['ds_account']
            );
        } else {
            $this->_toLog('[' . __FUNCTION__ . '] - `ds_account` not found in parametter array', self::INFO);
        }

        return $args;
    }

    /**
     * Method prepare purchase type
     * @param  string $type
     * accept parametters:
     * subs_free_device — new contract - 24 months and equipment for 1 uah
     * subs_buy_device — new contract - buy equipment
     * subs_rent_device — new contract - rent equipment
     * subs_no_device — new contract - no equipment
     * subs_renew — restore the current contract
     * subs_negative_balance - money stop
     *
     * @return string|false
     */
    protected function _preparePurchaseType($type) {
        // init types array
        $typeArray = array('subs_free_device', 'subs_buy_device', 'subs_rent_device', 'subs_no_device', 'subs_renew', 'subs_negative_balance');
        // verify argument
        if (empty($type) || !in_array($type, $typeArray)) {
            $this->_toLog('[' . __FUNCTION__ . '] - purchase type is not correct');
            return false;
        }
        // return type
        return $type;
    }

    /**
     * Method prepare device type
     * @param  string $type
     * accept parametters:
     * device_free — new contract - 24 months and equipment for 1 uah
     * device_buy — new contract - buy equipment
     * device_rent — new contract - rent equipment
     * device_change — service replace the current equipment
     *
     * @return string|false
     */
    protected function _prepareDeviceType($type) {
        // init types array
        $typeArray = array('device_free', 'device_buy', 'device_rent', 'device_change');
        // verify argument
        if (empty($type) || !in_array($type, $typeArray)) {
            $this->_toLog('[' . __FUNCTION__ . '] - device type is not correct');
            return false;
        }
        // return type
        return $type;
    }

    /**
     * Read result from ispAPI
     * @param   string   $result  result string
     * @return  boolean
     */
    protected function _readResult($result) {
        // verify result var
        if (empty($result) || !is_string($result)) {
            $this->_toLog('[' . __FUNCTION__ . '] - cannot read result; maybe empty or not string value: ' . var_export($result, true));
            return false;
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - read result: ' . var_export($result, true), self::INFO);

        // decode from string
        $json = json_decode($result);
        // check json errors
        // commented due old PHP compatibility reasons
        //$error = json_last_error_msg();
        $error = print_r($json, true);
        if (!$json) {
            $this->_toLog('[' . __FUNCTION__ . '] - has json error: ' . $error);
            return false;
        }

        // all looks good
        $this->_result = $json;
        return true;
    }

    /**
     * Return ispAPI result
     * @return object|false
     */
    protected function _return() {
        // verify result
        if (empty($this->_result) || !is_object($this->_result) || !isset($this->_result->status)) {
            $this->_toLog('[' . __FUNCTION__ . '] - API result is bad type: ' . var_export($this->_result, true));
            return false;
        }

        // log warnings
        if (!empty($this->_result->warnings)) {
            $this->_toLog('[' . __FUNCTION__ . '] - API warnings: ' . print_r($this->_result->warnings, true));
        }

        // verify result status
        if ($this->_result->status !== 0) {
            // prepare error
            $error = '';

            if (!isset($this->_errors[$this->_result->status])) {
                if (isset($this->_result->message)) {
                    $error = $this->_result->message;
                } else {
                    $error = '[' . __FUNCTION__ . '] - API return false status';
                }
            } else {
                // prepare error string
                $error = 'Code #' . $this->_result->status;
                $error .= ' ' . $this->_errors[$this->_result->status]['message'];
                $error .= ' - ' . $this->_errors[$this->_result->status]['description'];
            }

            // log error
            $this->_toLog($error);
            // return false
            return false;
        }
        // all looks good
        else {
            // log action
            $this->_toLog('[' . __FUNCTION__ . '] - return API result', self::INFO);
            // return result
            return $this->_result;
        }
    }

    /**
     * Create ispAPI url
     * @param   boolean  $testMode  testing mode flag, default - false
     * @return  boolean
     */
    protected function _createUrl($testMode = false) {
        // verify argument
        if (!is_bool($testMode)) {
            $this->_toLog('[' . __FUNCTION__ . '] - $testMode not is boolean type; set to default `false`', self::WARNING);
            $testMode = false;
        }

        // assign url
        if ($testMode) {
            $this->_toLog('[' . __FUNCTION__ . '] - $testMode is `true`; set url to: ' . self::OTV_URL_DEV, self::INFO);
            $this->_url = self::OTV_URL_DEV;
        } else {
            $this->_toLog('[' . __FUNCTION__ . '] - $testMode is `false`; set url to: ' . self::OTV_URL, self::INFO);
            $this->_url = self::OTV_URL;
        }
        // add ispAPI link to url
        $this->_url .= self::OTV_URL_API;
        return true;
    }

    /**
     * Authenticate to ispAPI and assign hash
     * @return boolean
     */
    protected function _auth() {
        // verify login and pass
        if (empty($this->_login)) {
            $this->_toLog('[' . __FUNCTION__ . '] - login is empty');
            return false;
        }
        if (empty($this->_password)) {
            $this->_toLog('[' . __FUNCTION__ . '] - password is empty');
            return false;
        }



        // try to connect
        $res = $this->_sendToAPI(self::OTV_URL_AUTH, array(
            'login' => $this->_login,
            'password' => $this->_password
        ));



        // verify result and hash
        if (!$res || empty($this->_result->hash)) {
            $this->_toLog('[' . __FUNCTION__ . '] - false authenticate');
            return false;
        }

        // assign hash
        $this->_hash = $this->_result->hash;
        return true;
    }

    /**
     * Method send ispAPI action by CURL
     * @param   string   $method  ispAPI method
     * @param   array    $args    POST arguments
     * @return  object|false
     */
    protected function _sendToAPI($method, $args) {
        // verify arguments
        if (!is_string($method)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$method` must to be string;');
            return false;
        }
        if (!is_array($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$args` is not array;');
            return false;
        }

        // add hash to arguments
        if ($method !== self::OTV_URL_AUTH) {
            $args['hash'] = $this->_hash;
        }

        // create curl link
        $curlLink = $this->_url . '/' . $method;

        // prepare data for log
        $logArgs = $args;
        // disable password - not show in logs!
        if (!empty($logArgs['password'])) {
            $logArgs['password'] = '*************';
        }
        // lof info
        $this->_toLog('[' . __FUNCTION__ . '] - send request to url: ' . $curlLink, self::INFO);
        $this->_toLog('[' . __FUNCTION__ . '] - send request data: ' . var_export($logArgs, true), self::INFO);

        // create and send curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $curlLink);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        // curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/html;charset=utf-8'));
        // send post if need
        if (!empty($args)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // execute curl
        $response = curl_exec($ch);
        //PHP 8.0+ has no need to close curl resource anymore
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch); // Deprecated in PHP 8.5
        }

        // read result
        $this->_readResult($response);
        // return formed result by function _return()
        return $this->_return();
    }

    /**
     * Constructor
     * @depends _createUrl, _auth
     * @param string   $login     client login
     * @param string   $pass      client password
     * @param boolean  $testMode  test mode flag
     * @param string   $log       path to log file
     */
    public function __construct($login, $pass, $testMode = false, $log = '', $logLevel = 1) {
        // assign log file
        $this->_log = $log;
        // assign log level
        $logLevel = (int) $logLevel;
        $this->_logLevel = ($logLevel < 0 || $logLevel > 3) ? 1 : $logLevel;

        // assign login and password
        $this->_login = $login;
        $this->_password = $pass;

        // create API url
        $this->_createUrl($testMode);
        // try authenticate
        $this->_auth();
    }

    /**
     * Return last message
     * @param   integer  $type message type, default - 1 mean Error
     * @return  string|false
     */
    public function getLastMessage($type = 1) {
        // verify arguments
        if (!is_numeric($type) || !isset($this->_lastMessage[$type])) {
            return false;
        }
        // return message
        return $this->_lastMessage[$type];
    }

    /* ispAPI functions */

    /* users functions */

    /**
     * Verify user email
     * @param   string   $email   user email
     * @return  integer|false    false - smth is wrong; 0 - not exist; 1 - exist
     */
    public function emailExists($email) {
        // verify argument
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$email` is not valid email address');
            return false;
        }
        // prepare arguments array
        $args = array(
            'email' => $email
        );
        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return result
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Return account object
     * @param   string  $account  user account in provider base
     * @return  mixed             return object - user account or 0
     */
    public function accountExists($account) {
        // verify argument
        if (empty($account)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$account` is empty');
            return 0;
        }
        // prepare arguments array
        $args = array(
            'account' => $account
        );
        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return 0;
        }
    }

    /**
     * Add new user
     * @param string $email     user email
     * @param string $account   user account in provider base
     * @param array  $addParams additional params
     * birth_date (YYYY-MM-DD or DD.MM.YYYY)
     * gender (M or F, default: M)
     * firstname (default: «Гость»/«Гостья»)
     * password (will generate automatic - if not set, must be longer 8 chars)
     * lastname
     * phone (example: 0501234567)
     * region
     * receive_news (value 1 or 0, default: 1)
     * send_registration_email (whether send the user a registration message, value 1 or 0, default: 1)
     * index (zip code or other identifier binding regional subscriber)
     *
     * @return  string|false   string - user ID; false - smth is wrong
     */
    public function addUser($email, $account, $addParams = array()) {
        // verify arguments
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$email` is not valid email address');
            return false;
        }
        // verify argument
        if (empty($account)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$account` is empty');
            return false;
        }

        // prepare arguments array
        $args = array(
            'email' => $email,
            'account' => $account,
        );
        // prepare birth date
        if (!empty($addParams['birth_date']) && strtotime($addParams['birth_date'])) {
            $args['birth_date'] = date('Y-m-d', strtotime($addParams['birth_date']));
        }
        // prepare gender
        if (!empty($addParams['gender']) && in_array($addParams['gender'], array('M', 'F'))) {
            $args['gender'] = $addParams['gender'];
        }
        // prepare firstname
        if (!empty($addParams['firstname'])) {
            $args['firstname'] = $addParams['firstname'];
        } else {
            $args['firstname'] = (isset($args['gender']) && $args['gender'] === 'F') ? 'Гостья' : 'Гость';
        }
        // prepare lastname
        if (!empty($addParams['lastname'])) {
            $args['lastname'] = $addParams['lastname'];
        }

        // prepare password
        if (!empty($addParams['password']) && strlen($addParams['password']) >= 8) {
            $args['password'] = $addParams['password'];
        } else {
            $this->_toLog('[' . __FUNCTION__ . '] - user password is empty or shorter than 8 chars; generate automatic', self::WARNING);
        }

        // prepare phone
        if (!empty($addParams['phone']) && is_string($addParams['phone']) &&
                preg_match('/^\d{10,}$/', $addParams['phone']) && $addParams['phone'][0] === '0'
        ) {
            $args['phone'] = $addParams['phone'];
        }
        // prepare region
        if (!empty($addParams['region'])) {
            $args['region'] = $addParams['region'];
        }
        // prepare receive_news
        if (!empty($addParams['receive_news']) && is_numeric($addParams['receive_news'])) {
            $args['receive_news'] = (int) (bool) $addParams['receive_news'];
        }
        // prepare send_registration_email
        if (!empty($addParams['send_registration_email']) && is_numeric($addParams['send_registration_email'])) {
            $args['send_registration_email'] = (int) (bool) $addParams['send_registration_email'];
        }
        // prepare index
        if (!empty($addParams['index'])) {
            $args['index'] = $addParams['index'];
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        debarr($res);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return 0;
        }
    }

    /**
     * Return user list bound to provider
     * @param   integer  $offset  offset
     * @param   integer  $limit   limit
     * @return  array
     */
    public function getUserList($offset = 0, $limit = 1000) {
        // verify arguments
        if (!is_numeric($offset) || $offset < 0) {
            // log info
            $this->_toLog('[' . __FUNCTION__ . '] - `$offset` is incorrect, set to 0', self::WARNING);
            $offset = 0;
        }
        if (!is_numeric($limit) || $limit <= 0 || $limit > 1000) {
            // log info
            $this->_toLog('[' . __FUNCTION__ . '] - `$limit` is incorrect, set to default 1000', self::WARNING);
            $limit = 1000;
        }
        // prepare arguments array
        $args = array(
            'offset' => $offset,
            'limit' => $limit
        );
        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return array();
        }
    }

    /**
     * Assign provider account and bind user to account
     * @param   string  $email    user email
     * @param   string  $account  account
     * @return  integer|false     integer - status
     */
    public function changeAccount($email, $account) {
        // verify arguments
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$email` is not valid email address');
            return false;
        }
        // verify argument
        if (empty($account)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$account` is empty');
            return false;
        }

        // prepare arguments array
        $args = array(
            'email' => $email,
            'account' => $account,
        );
        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /**
     * Unbind user from provider by params
     * @param  array $params
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @return integer|false       integer - status
     */
    public function deleteAccount($params) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /**
     * Change user email
     * @param   string  $email     current user email
     * @param   string  $newEmail  new user email
     * @return  integer|false      integer - status
     */
    public function changeEmail($email, $newEmail) {
        // verify arguments
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$email` is not valid email address');
            return false;
        }
        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$newEmail` is not valid email address');
            return false;
        }

        // prepare arguments array
        $args = array(
            'email' => $email,
            'new_email' => $newEmail
        );

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /**
     * Return user information
     * @param array $params
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @return object|false
     */
    public function getUserInfo($params) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Return user information
     * @param array $params parametters array
     * accept parametters:
     *
     * one from following is required:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * additionals:
     * birth_date (YYYY-MM-DD or DD.MM.YYYY)
     * gender (M or F, default: M)
     * password (must be longer than 8 chars)
     * firstname
     * lastname
     * phone
     * region
     * index (zip code or other identifier binding regional subscriber)
     *
     * @return integer|false    integer - status
     */
    public function changeUserInfo($params) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }

        // prepare arguments array
        // required parametters
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // additional parametters
        // prepare birth date
        if (!empty($addParams['birth_date']) && strtotime($addParams['birth_date'])) {
            $args['birth_date'] = date('Y-m-d', strtotime($addParams['birth_date']));
        }
        // prepare gender
        if (!empty($addParams['gender']) && in_array($addParams['gender'], array('M', 'F'))) {
            $args['gender'] = $addParams['gender'];
        }
        // prepare firstname
        if (!empty($addParams['firstname'])) {
            $args['firstname'] = $addParams['firstname'];
        } else {
            $args['firstname'] = (isset($args['gender']) && $args['gender'] === 'F') ? 'Гостья' : 'Гость';
        }
        // prepare lastname
        if (!empty($addParams['lastname'])) {
            $args['lastname'] = $addParams['lastname'];
        }

        // prepare password
        if (!empty($addParams['password']) && strlen($addParams['password']) >= 8) {
            $args['password'] = $addParams['password'];
        }

        // prepare phone
        if (!empty($addParams['phone']) && is_string($addParams['phone']) &&
                preg_match('/^\d{10,}$/', $addParams['phone']) && $addParams['phone'][0] === '0'
        ) {
            $args['phone'] = $addParams['phone'];
        }
        // prepare region
        if (!empty($addParams['region'])) {
            $args['region'] = $addParams['region'];
        }
        // prepare index
        if (!empty($addParams['index'])) {
            $args['index'] = $addParams['index'];
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Reset parent control
     * @param array $params
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @return integer|false       false or integer - status
     */
    public function resetParentControl($params) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /* --end users functions */


    /* purchases functions */

    /**
     * Enable bundle
     * @param  array $params
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @param  string $subId purchase identificator
     * @param  string $type
     * accept parametters:
     * subs_free_device — new contract - 24 months and equipment for 1 uah
     * subs_buy_device — new contract -  - buy equipment
     * subs_rent_device — new contract - rent equipment
     * subs_no_device — new contract - no equipment
     * subs_renew — restore the current contract
     *
     * @return integer|false
     */
    public function enableBundle($params, $subId, $type) {
        // verify arguments
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }
        if (empty($subId)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$subId` is empty');
            return false;
        }
        if (empty($type)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$type` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // assign subId
        $args['sub_id'] = $subId;

        // prepare type
        $type = $this->_preparePurchaseType($type);
        // verify type
        if (!$type) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$type` is not correct');
            return false;
        }
        // assign type
        $args['type'] = $type;

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Disable bundle
     * @param  array $params
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @param  string $subId description
     * @param  string $type
     * accept parametters:
     * subs_free_device — new contract - 24 months and equipment for 1 uah
     * subs_buy_device — new contract -  - buy equipment
     * subs_rent_device — new contract - rent equipment
     * subs_no_device — new contract - no equipment
     * subs_renew — restore the current contract
     *
     * @return integer|false
     */
    public function disableBundle($params, $subId, $type) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty;');
            return false;
        }
        if (empty($subId)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$subId` is empty');
            return false;
        }
        if (empty($type)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$type` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // assign subId
        $args['sub_id'] = $subId;

        // prepare type
        $type = $this->_preparePurchaseType($type);
        // verify type
        if (!$type) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$type` is not correct');
            return false;
        }
        // assign type
        $args['type'] = $type;

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Check user bundle
     * @param  array $params
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @param  string $subId description
     *
     * @return integer|false
     */
    public function checkBundle($params, $subId) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }
        if (empty($subId)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$subId` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // assign subId
        $args['sub_id'] = $subId;

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Change use bundle-subscription
     * @param  array  $params   account params array
     * accept parametters:
     * account OR email OR id OR ds_account
     * examples:
     * array('account' => 'test')
     * array('email' => 'test@test.com')
     * array('id' => 42)
     * array('ds_account' => 'test')
     *
     * @param  string  $oldSubId old subscription
     * @param  string  $newSubId new subscription
     * @return integer|false         false or integer - status
     */
    public function changeBundle($params, $oldSubId, $newSubId) {
        // verify parametters
        if (empty($params)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$params` is empty');
            return false;
        }
        if (empty($oldSubId)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$oldSubId` is empty');
            return false;
        }
        if (empty($newSubId)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$newSubId` is empty');
            return false;
        }

        // prepare arguments array
        $args = $this->_prepareAccountDefaultData($params);
        // verify arguments
        if (empty($args)) {
            $this->_toLog('[' . __FUNCTION__ . '] - account parametter not found in `$params`');
            return false;
        }

        // assign subscriptions
        $args['old_sub_id'] = $oldSubId;
        $args['new_sub_id'] = $newSubId;

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /**
     * Get active provider's purchases
     * @param  string  $startDate  start date of reporting period
     * @param  integer $page       page number
     * @return object|false
     */
    public function getAllPurchases($startDate, $page = 1) {
        // verify arguments
        if (empty($startDate) || !strtotime($startDate)) {
            $this->_toLog('[' . __FUNCTION__ . '] - parametter `$startDate` is not valida date type');
            return false;
        }
        if (!is_numeric($page) || $page <= 0) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$page` is not numeric os less than 0');
            return false;
        }

        // prepare arguments
        $args = array(
            'start_date' => $startDate,
            'page' => $page
        );

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /* --end purchases functions */


    /* devices functions */

    /**
     * Add device and bind to user
     * $account is required
     * $serialNumber or $mac is required, but advisable required TWO
     * $binding_code is required for providers that work with purchases for access to additional devices
     * $addParams may assigns 'device_type', 'device_model', 'type'
     *
     * @param   string  $account        user account
     * @param   string  $serialNumber   device serial number
     * @param   string  $mac            mac address
     * @param   string  $binding_code   code for binding
     * @param   array   $addParams      additional params
     * @return  integer|false           false or integer - status
     */
    public function addDevice($account, $serialNumber = null, $mac = null, $binding_code = null, $addParams = array()) {
        // verify arguments
        if (empty($account)) {
            $this->_toLog('[' . __FUNCTION__ . '] - `$account` not set');
            return false;
        }
        if (empty($serialNumber) && empty($mac)) {
            $this->_toLog('[' . __FUNCTION__ . '] - must set `$serialNumber` or `$mac` or both');
            return false;
        }

        // prepare arguments
        $args = array(
            'account' => $account
        );

        if (!empty($serialNumber)) {
            $args['serial_number'] = $serialNumber; // assign serial number
        }
        if (!empty($mac)) {
            $args['mac'] = $mac; // assign mac
        }
        if (!empty($binding_code)) {
            $args['binding_code'] = $binding_code; // assign binding code
        }

        // assign additional params
        if (!empty($addParams['device_type'])) {
            $args['device_type'] = $addParams['device_type']; // assign device type
        }
        if (!empty($addParams['device_model'])) {
            $args['device_model'] = $addParams['device_model']; // assign device model
        }
        // assign device type
        if (!empty($addParams['type'])) {
            $args['type'] = $this->_prepareDeviceType($addParams['type']);
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /**
     * Unbind device from user
     * $serialNumber or $mac is required, but advisable required TWO
     * $account is NOT required
     * $type may assigns:
     * device_break_contract - end of contract
     * device_change - equipment problem
     *
     * @param   string  $serialNumber  device serial number
     * @param   string  $mac           device mac address
     * @param   string  $account       user account
     * @param   string  $type          device type
     * @return  mixed                  false or result
     */
    public function delDevice($serialNumber = null, $mac = null, $account = null, $type = null) {
        // verify arguments
        if (empty($serialNumber) && empty($mac)) {
            $this->_toLog('[' . __FUNCTION__ . '] - must set `$serialNumber` or `$mac` or both');
            return false;
        }

        // init arguments array
        $args = array();

        if (!empty($serialNumber)) {
            $args['serial_number'] = $serialNumber; // assign serial number
        }
        if (!empty($mac)) {
            $args['mac'] = $mac; // assign mac
        }
        if (!empty($account)) {
            $args['account'] = $account; // assign account
        }
        if (!empty($type) && in_array($type, array('device_break_contract', 'device_change'))) {
            $args['type'] = $type;
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->status)) {
            return $res->status;
        } else {
            return false;
        }
    }

    /**
     * Check device
     * $serialNumber or $mac is required, but advisable required TWO
     *
     * @param   string  $serialNumber  device serial number
     * @param   string  $mac           device mac address
     * @return  mixed                  false or 0 or object
     */
    public function deviceExists($serialNumber = null, $mac = null) {
        // verify arguments
        if (empty($serialNumber) && empty($mac)) {
            $this->_toLog('[' . __FUNCTION__ . '] - must set `$serialNumber` or `$mac` or both');
            return false;
        }

        // init arguments array
        $args = array();

        if (!empty($serialNumber)) {
            $args['serial_number'] = $serialNumber; // assign serial number
        }
        if (!empty($mac)) {
            $args['mac'] = $mac; // assign mac
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /**
     * Return device list
     * @param   string   $account  user account
     * @param   string   $email    user email
     * @param   integer  $offset   offset parametter
     * @param   integer  $limit    limit parametter no more than 1000
     * @return  array|false
     */
    public function getDeviceList($account = null, $email = null, $offset = 0, $limit = 1000) {
        // verify arguments
        if (!is_numeric($offset) || $offset < 0) {
            // log info
            $this->_toLog('[' . __FUNCTION__ . '] - `$offset` is incorrect, set to 0', self::WARNING);
            $offset = 0;
        }
        if (!is_numeric($limit) || $limit <= 0 || $limit > 1000) {
            // log info
            $this->_toLog('[' . __FUNCTION__ . '] - `$limit` is incorrect, set to default 1000', self::WARNING);
            $limit = 1000;
        }

        // init arguments array
        $args = array(
            'offset' => $offset,
            'limit' => $limit
        );

        if (!empty($account)) {
            $args['account'] = $account; // assign account
        }
        if (empty($args['account']) && !empty($email)) {
            $args['email'] = $email; // assign email
        }

        // log info
        $this->_toLog('[' . __FUNCTION__ . '] - send params: ' . var_export($args, true), self::INFO);
        // run request to API and get result
        $res = $this->_sendToAPI(__FUNCTION__, $args);
        // verify and return
        if (isset($res->data)) {
            return $res->data;
        } else {
            return false;
        }
    }

    /* --end devices functions */

    /* --end API functions */
}

// end class

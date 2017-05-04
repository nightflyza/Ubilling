<?php
/**
 * CoPayCo API (CoPayCo Application Programming Interface)
 *
 * This file is part of CoPayCo system
 * Copyright (C) 2010 CoPayCo, http://www.copayco.com/
 *
 * @author: Alexandr Nosov (alex@copayco.com)
 * @version: 2.1.5
 */
class copayco_api
{
    /**
     * CoPayCo submit URL
     */
    const SUBMIT_URL      = 'https://www.copayco.com/pay.php';
    /**
     * CoPayCo submit test URL
     */
    const SUBMIT_URL_TEST = 'https://www.test.copayco.com/pay.php';

    /**
     * CoPayCo notification URL
     */
    const NOTIFICATION_URL      = 'https://www.copayco.com/notify_delivering.php';
    /**
     * CoPayCo notification test URL
     */
    const NOTIFICATION_URL_TEST = 'https://www.test.copayco.com/notify_delivering.php';

    /**
     * Key of transaction id
     */
    const TA_ID_KEY = 'ta_id';

    /**
     * Key of request type
     */
    const REQUEST_TYPE_KEY = 'request_type';

    /**
     * Key of signature
     */
    const SIGNATURE_KEY = 'signature';

    /**
     * Key of signature
     */
    const PAYMENT_MODE_KEY = 'payment_mode';

    /**
     * Key of status
     */
    const STATUS_KEY = 'status';

    /**
     * @var array Allowed request fields (From Merchant to CoPAYCo)
     */
    private static $aReqFields = array(
        'shop_id'      => TRUE,
        'ta_id'        => TRUE,
        'amount'       => TRUE,
        'currency'     => TRUE,
        'description'  => FALSE,
        'custom'       => FALSE,
        'payment_mode' => FALSE,
        'charset'      => FALSE,
        'lang'         => FALSE,
        'order_no'     => FALSE,
        'purpose'      => FALSE,
        'date_time'    => FALSE,
        'random'       => FALSE,
        'signature'    => FALSE,
    );

    /**
     * @var array Allowed check fields (From CoPAYCo to Merchant)
     */
    private static $aCheckFields = array(
        'request_type' => TRUE,
        'ta_id'        => TRUE,
        'amount'       => TRUE,
        'currency'     => TRUE,
        'custom'       => FALSE,
        'payment_mode' => FALSE,
        'client_ip'    => TRUE,
        'date_time'    => FALSE,
        'random'       => FALSE,
        'signature'    => TRUE,
    );

    /**
     * @var array Allowed perform fields (From CoPAYCo to Merchant)
     */
    private static $aPerformFields = array(
        'request_type' => TRUE,
        'status'       => TRUE,
        'ta_id'        => TRUE,
        'cpc_ta_id'    => TRUE,
        'amount'       => TRUE,
        'currency'     => TRUE,
        'custom'       => FALSE,
        'payment_mode' => FALSE,
        'date_time'    => FALSE,
        'random'       => FALSE,
        'signature'    => TRUE,
    );

    /**
     * @var array Allowed notification fields (From Merchant to CoPAYCo)
     */
    private static $aNotificationFields = array(
        'shop_id'    => TRUE,
        'ta_id'      => TRUE,
        'amount'     => TRUE,
        'currency'   => FALSE,
        'state'      => TRUE,
        'date_time'  => TRUE,
        'random'     => FALSE,
        'signature'  => TRUE,
        'return_url' => FALSE,
    );

    /**
     * @var array Correspondence field-data to current property
     */
    private static $aCorrespondence = array(
        'shop_id'      => 'iShopId',
        'ta_id'        => 'sTaId',
        'amount'       => 'nAmount',
        'currency'     => 'sCurrency',
//        'status'       => '',
        'description'  => 'sDescription',
        'custom'       => 'mCustom',
        'payment_mode' => 'aPaymentMode',
        'cpc_ta_id'    => 'iCPCTaId',
        'charset'      => 'sCharset',
        'lang'         => 'sLanguage',
        'order_no'     => 'sOrderNo',
        'purpose'      => 'sPurpose',
        'date_time'    => 'sDateTime',
        'random'       => 'iRand',
        'state'        => 'sState',
        'return_url'   => 'sReturnUrl',
    );

    /**
     * @var array Fill property automatically by HTTP-vars
     */
    private static $aAutoFill = array(
        //'aPaymentMode',
        'iCPCTaId',
        'sDateTime',
        'iRand',
    );

    /**
     * @var array Field number (for error code)
     */
    private static $aFieldNumber = array(
        'shop_id'      => 1,
        'ta_id'        => 2,
        'amount'       => 3,
        'currency'     => 4,
        'description'  => 5,
        'custom'       => 6,
        'payment_mode' => 7,
        'request_type' => 9,
        'status'       => 10,
        'state'        => 11,
        'return_url'   => 12,
        'client_ip'    => 20,
        'cpc_ta_id'    => 30,
        'order_no'     => 60,
        'purpose'      => 61,
        'charset'      => 70,
        'lang'         => 71,
        'date_time'    => 80,
        'random'       => 81,
        'signature'    => 99,
    );

    /**
     * @var array Allowed currencies
     */
    private static $aLegalCurrencies = array(
        'UAH',
        'RUB',
        'USD',
        'EUR',
    );

    /**
     * @var array Allowed payment modes
     */
    private static $aLegalPaymentMode = array(
        'paycard',
        'account',
        'ecurrency',
        'copayco',
        'terminal',
        'sms',
    );

    /**
     * @var array Allowed languages
     */
    private static $aLegalLanguages = array(
        'ru',
        'en',
        'ua',
    );

    /**
     * @var array Allowed Charsets
     */
    private static $aLegalCharsets = array(
        'utf-8',
        'windows-1251',
        'koi8-r',
        'koi8-u',
    );

    /**
     * @var boolean Test mode
     */
    private $bTestMode = FALSE;

    /**
     * @var boolean Test mode
     */
    private $sNotifyMode = 'curl';

    /**
     * @var string Error Message
     */
    private $sErrMsg = NULL;

    /**
     * @var string Signature Key
     */
    private $sSignKey = NULL;

    /**
     * @var integer Use Random number in The request
     */
    private $iUseRand = 2;

    /**
     * @var integer Shop ID
     */
    protected $iShopId = NULL;

    /**
     * @var string Merchant's Transaction ID
     */
    protected $sTaId = NULL;

    /**
     * @var string CoPAYCo's Transaction ID
     */
    protected $iCPCTaId = NULL;

    /**
     * @var numeric Amount of payment
     */
    protected $nAmount = NULL;

    /**
     * @var string Currency of payment
     */
    protected $sCurrency = NULL;

    /**
     * @var string Description of payment
     */
    protected $sDescription = NULL;

    /**
     * @var mixed Custom field
     */
    protected $mCustom = NULL;

    /**
     * @var array Payment Mode (paycard, account, copayco)
     */
    protected $aPaymentMode = array();

    /**
     * @var string
     */
    protected $sCharset = NULL;

    /**
     * @var string
     */
    protected $sLanguage = NULL;

    /**
     * @var string
     */
    protected $sOrderNo = NULL;

    /**
     * @var string
     */
    protected $sPurpose = NULL;

    /**
     * @var string Date/time from server which makes request
     */
    protected $sDateTime = NULL;

    /**
     * @var string Rantom number (0-1024)
     */
    protected $iRand = NULL;

    /**
     * @var string Transaction State
     */
    protected $sState = NULL;

    /**
     * @var string Return Url (for HTML-form)
     */
    protected $sReturnUrl = NULL;

    /**
     * Constructor of copayco
     * @param string $sSignKey
     */
    protected function __construct($sSignKey)
    {
        $aConf = $this->get_config($sSignKey);

        // Set Signature Key
        if (!empty($aConf['sign_key'])) {
            $this->sSignKey = $aConf['sign_key'];
        }

        // Set Shop ID
        if (!empty($aConf['shop_id'])) {
            $this->iShopId = $aConf['shop_id'];
        }

        // Set Notify mode
        if (!empty($aConf['notify_mode'])) {
            if (!in_array($aConf['notify_mode'], array('curl'))) {
                throw new copayco_exception('Incorrect notify mode: "' . $aConf['notify_mode'] . '".', 10001);
            }
            $this->sNotifyMode = $aConf['notify_mode'];
        }

        // Set Charset
        if (isset($aConf['charset'])) {
            $this->set_charset($aConf['charset']);
        }

        // Set Random value
        if (isset($aConf['use_rand'])) {
            $this->iUseRand = $aConf['use_rand'];
        }

        // Set Test mode
        if (isset($aConf['test_mode'])) {
            $this->bTestMode = !empty($aConf['test_mode']);
        }
    } // function __construct

    /**
     * Get instance of copayco API
     * @param string $sSignKey
     * @return copayco
     */
    public static function instance($sSignKey = NULL)
    {
        return new copayco_api($sSignKey);
    } // function instance


    // -------------- Preparing data -------------- \\

    /**
     * Set main data of copayco payment
     * @param string $sTaId
     * @param numeric $nAmount
     * @param integer $iShopId
     * @param string $sCurrency
     */
    public function set_main_data($sTaId, $nAmount, $sCurrency = 'UAH', $iShopId = NULL)
    {
        // Check ID of TA
        if (empty($sTaId)) {
            throw new copayco_exception('Transaction ID can\'t be empty.', 10020);
        }

        // Check Amount
        if (empty($nAmount)) {
            throw new copayco_exception('Amount of payment can\'t be equal to 0.', 10030);
        }
        if (!is_numeric($nAmount)) {
            throw new copayco_exception('Amount must be numeric value.', 10031);
        }
        if ($nAmount < 0) {
            throw new copayco_exception('Amount must be greater than 0.', 10032);
        }

        // Check shop ID
        if (!empty($iShopId)) {
            $this->iShopId   = $iShopId;
        } elseif (empty($this->iShopId)) {
            throw new copayco_exception('Shop ID isn\'t set.', 10010);
        }

        // Check Currency
        if (empty($sCurrency) || !in_array($sCurrency, self::$aLegalCurrencies)) {
            throw new copayco_exception('Currency must be equal to "' . implode('", "', self::$aLegalCurrencies) . '".', 10040);
        }

        $this->sTaId     = $sTaId;
        $this->nAmount   = round($nAmount * 100);
        $this->sCurrency = $sCurrency;
    } // function set_main_data

    /**
     * Set description of copayco payment
     * @param string $sDescription
     */
    public function set_description($sDescription)
    {
        if (!is_scalar($sDescription)) {
            throw new copayco_exception('Description must have a scalar value.', 10050);
        }
        $sDescription = (string)$sDescription;
        if (strlen($sDescription) > 255) {
            throw new copayco_exception('Description can\'t be more than 255 symbols.', 10051);
        }

        $this->sDescription = $sDescription;
    } // function set_description

    /**
     * Set custom field of copayco payment
     * @param mixed $mCustom
     */
    public function set_custom_field($mCustom)
    {
        if (!is_scalar($mCustom)) {
            throw new copayco_exception('Custom field must have a scalar value.', 10060);
        }
        $mCustom = (string)$mCustom;
        if (strlen($mCustom) > 255) {
            throw new copayco_exception('Custom field can\'t be more than 255 symbols.', 10061);
        }

        $this->mCustom = $mCustom;
    } // function set_custom_field

    /**
     * Set payment mode of copayco payment
     * @param mixed $mPaymentMode
     */
    public function set_payment_mode($mPaymentMode)
    {
        if (empty($mPaymentMode)) {
            throw new copayco_exception('Payment Mode can\'t be empty.', 20070);
        }

        if (is_array($mPaymentMode)) {
            foreach ($mPaymentMode as $v) {
                $this->set_payment_mode($v);
            }
        } else {
            if (!is_scalar($mPaymentMode) || !in_array($mPaymentMode, self::$aLegalPaymentMode)) {
                throw new copayco_exception('Payment Mode must be equal to "' . implode('", "', self::$aLegalPaymentMode) . '".', 20071);
            }

            if (!in_array($mPaymentMode, $this->aPaymentMode)) {
                $this->aPaymentMode[] = $mPaymentMode;
            }
        }
    } // function set_payment_mode

    /**
     * Clear payment mode of copayco payment
     */
    public function clear_payment_mode()
    {
        $this->aPaymentMode = array();
    } // function clear_payment_mode

    /**
     * Set charset
     * @param string $sCharset
     */
    public function set_charset($sCharset)
    {
        $sCharset = strtolower($sCharset);
        if (!in_array($sCharset, self::$aLegalCharsets)) {
            throw new copayco_exception('Incorrect Charset: "' . $sCharset . '".', 20700);
        }
        $this->sCharset = $sCharset;
    } // function set_charset

    /**
     * Set language
     * @param string $sLanguage
     */
    public function set_language($sLanguage)
    {
        $sLanguage = strtolower($sLanguage);
        if (!in_array($sLanguage, self::$aLegalLanguages)) {
            throw new copayco_exception('Incorrect Language: "' . $sLanguage . '".', 20710);
        }
        $this->sLanguage = $sLanguage;
    } // function set_language

    /**
     * Set order_no
     * @param string $sOrderNo
     */
    public function set_order_no($sOrderNo)
    {
        $this->sOrderNo = $sOrderNo;
    } // function set_order_no

    /**
     * Set purpose
     * @param string $sPurpose
     */
    public function set_purpose($sPurpose)
    {
        $this->sPurpose = $sPurpose;
    } // function set_purpose

    /**
     * Get form fields
     * @param array $aAttr        2-x array (first key - field name; second key - attribute name; value - attribute value)
     * @param string $sSeparator  field separator (for example: "\t", "\n", etc). If $sSeparator is null, method return array of tags, but isn't string
     * @return mixed              string OR array
     */
    public function get_form_fields($aAttr = array(), $sSeparator = '')
    {
        $mRet = is_null($sSeparator) ? array() : '';
        $this->set_date_and_rand();
        foreach ($this->prepare_send_data() as $k => $v) {
            $sTag = '<input type="' . (isset($aAttr[$k]['type']) ? $aAttr[$k]['type'] : 'hidden') . '" name="' . $k . '" value="' . htmlspecialchars($v) . '"';
            $sTag .= (isset($aAttr[$k]) ? $this->get_additional_attr($aAttr[$k]) : '') . ' />';
            if (is_null($sSeparator)) {
                $mRet[] = $sTag;
            } else {
                $mRet .= $sTag . $sSeparator;
            }
        }
        return $mRet;
    } // function get_form_fields

    /**
     * Get request URI
     * @param string $sSeparator  -- GET-separator ('&' OR '&amp;')
     * @return string
     */
    public function get_request_uri($sSeparator = '&amp;')
    {
        $sRet = '';
        $this->set_date_and_rand();
        foreach ($this->prepare_send_data() as $k => $v) {
            $sRet .= ($sRet ? $sSeparator : '') . $k . '=' . urlencode($v);
        }
        return $this->get_submit_url() . '?' . $sRet;
    } // function get_request_uri

    /**
     * Get submit url, taking into account the test mode
     * @return string
     */
    public function get_submit_url()
    {
        return $this->bTestMode ? self::SUBMIT_URL_TEST : self::SUBMIT_URL;
    } // function get_submit_url

    /**
     * Get notification url, taking into account the test mode
     * @return string
     */
    public function get_notification_url()
    {
        return $this->bTestMode ? self::NOTIFICATION_URL_TEST : self::NOTIFICATION_URL;
    } // function get_submit_url

    // -------------- Check data (1-st answer) -------------- \\

    /**
     * Check full data of copayco payment
     * @return boolean
     */
    public function check_data()
    {
        if ($this->get_request_type() != 'check') {
            throw new copayco_exception('It isn\'t check request.', 30091);
        }
        $this->define_http_val();
        return $this->check_http_fields(self::$aCheckFields, TRUE);
    } // function check_data

    /**
     * Set error message
     * @param string $sMsg
     */
    public function set_error_message($sErrMsg)
    {
        if ($sErrMsg) {
            $this->sErrMsg = $sErrMsg;
        }
    } // function set_error_message

    /**
     * Output first answer for copayco server
     * @param string $sCharset
     */
    public function output_check_answer($sCharset = NULL)
    {
        $sMsg = $this->get_request_type() == 'check' ? ($this->sErrMsg ? $this->sErrMsg : 'ok') : 'It isn\'t check request.';
        $this->output_header($sMsg, $sCharset);
        echo $sMsg;
    } // function output_first_answer

    // -------------- Perform data (2-nd answer) -------------- \\

    /**
     * Get transaction status
     * @return string
     */
    public function get_perform_status()
    {
        if ($this->get_request_type() != 'perform') {
            throw new copayco_exception('It isn\'t perform request.', 30092);
        }
        $this->define_http_val();
        if ($this->check_http_fields(self::$aPerformFields)) {
            return $this->get_http_val(self::STATUS_KEY);
        }
        throw new copayco_exception('Perform status isn\'t set.', 30093);
    } // function get_perform_status

    /**
     * Get CoPAYCo's transaction id
     * @return string
     */
    public function get_copayco_ta_id()
    {
        return $this->iCPCTaId;
    } // function get_copayco_ta_id

    /**
     * Check is transaction status reserved?
     * @return boolean
     */
    public function is_reserved()
    {
        return $this->get_perform_status() == 'reserved';
    } // function is_reserved

    /**
     * Check is transaction status finished?
     * @return boolean
     */
    public function is_finished()
    {
        return $this->get_perform_status() == 'finished';
    } // function is_finished

    /**
     * Check is transaction status canceled?
     * @return boolean
     */
    public function is_canceled()
    {
        return $this->get_perform_status() == 'canceled';
    } // function is_canceled

    /**
     * Output first answer for copayco server
     * @param string $sCharset
     */
    public function output_perform_answer($sCharset = NULL)
    {
        $sMsg = $this->get_request_type() == 'perform' ? ($this->sErrMsg ? $this->sErrMsg : 'ok') : 'It isn\'t perform request.';
        $this->output_header($sMsg, $sCharset);
        echo $sMsg;
    } // function output_perform_answer

    // -------------- Send delivering state: delivered OR canceled -------------- \\
    /**
     * Send "delivered" state about delivering of goods/service
     */
    public function send_delivered_state()
    {
        $this->send_notification('delivered');

    } // function send_delivered_state

    /**
     * Send "canceled" state about delivering of goods/service
     */
    public function send_canceled_state()
    {
        $this->send_notification('canceled');
    } // function send_canceled_state

    // -------------- Auxiliary methods -------------- \\

    /**
     * Get request type
     * @return string
     */
    final public function get_request_type()
    {
        $sKey = self::REQUEST_TYPE_KEY;
        $sType = $this->get_http_val($sKey);
        if (!in_array($sType, array('check', 'perform'))) {
            throw new copayco_exception('Field "' . $sKey . '" contain incorrect data.', 30002 + self::$aFieldNumber[$sKey] * 10);
        }
        return $sType;
    } // function get_request_type

    /**
     * Get transaction ID from HTTP-request (for check/perform)
     * @return mixed
     */
    public function get_ta_id()
    {
        return $this->get_http_val(self::TA_ID_KEY);
    } // function get_ta_id

    /**
     * Get full request data for "check" and "perform" operation
     * @return mixed
     */
    public function get_request_data()
    {
        $aFields = $this->get_request_type() == 'check' ? self::$aCheckFields : self::$aPerformFields;
        $aRet = array();
        foreach ($aFields as $k => $v) {
            $mVal = $this->get_http_val($k);
            if (!is_null($mVal)) {
                $aRet[$k] = $mVal;
            }
        }
        return $aRet;
    } // function get_request_data

    /**
     * Check signature
     * @return boolean
     */
    public function check_signature($sSignature)
    {
        return $this->sSignKey ? $this->get_signature() == $sSignature : TRUE;
    } // function check_signature

    /**
     * Get signature
     * @return string
     */
    final public function get_signature()
    {
        if ($this->sSignKey) {
            $sStr  = $this->sTaId . $this->nAmount . $this->sCurrency;
            $sStr .= empty($this->mCustom) || !empty($this->sState) ? '' : $this->mCustom;
            $sStr .= empty($this->sState)    ? '' : $this->sState;
            $sStr .= empty($this->sDateTime) ? '' : $this->sDateTime;
            $sStr .= empty($this->iRand)     ? '' : $this->iRand;
            $sStr .= $this->sSignKey;
            return  md5($sStr);
        } else {
            return NULL;
        }
    } // function get_signature

    /**
     * Get signature key
     * @return string
     */
    final public function get_sign_key()
    {
        return $this->sSignKey;
    } // function get_sign_key

    /**
     * Get field numbers
     * @return array
     */
    public static function get_field_number($sKey = NULL)
    {
        return empty($sKey) ? self::$aFieldNumber : (isset(self::$aFieldNumber[$sKey]) ? self::$aFieldNumber[$sKey] : NULL);
    } // function get_field_number


    // -------------- Private/Protected methods -------------- \\

    /**
     * Get config
     * @param string $sSignKey
     * @return string
     */
    protected function get_config($sSignKey)
    {
        $sConfPath = $this->get_config_path();
        $aRet = $sConfPath && file_exists($sConfPath) ? include($sConfPath) : array();

        if (!empty($sSignKey)) {
            $aRet['sign_key'] = $sSignKey;
        }

        return $aRet;
    } // function get_config

    /**
     * Get path to config-file
     * @return string
     */
    protected function get_config_path()
    {
        return dirname(__FILE__) . '/config.php';
    } // function get_config_path

    /**
     * Get additional attributes
     * @return string
     */
    protected function set_date_and_rand()
    {
        $this->sDateTime = date('Y-m-d H:i:s');
        if (!empty($this->iUseRand) && (empty($this->iRand) || $this->iUseRand > 1)) {
            $this->iRand = rand(1, 1024);
        }
    } // function set_date_and_rand

    /**
     * Get additional attributes
     * @param array $aAttr
     * @return string
     */
    protected function get_additional_attr($aAttr)
    {
        $sRet = '';
        if ($aAttr) {
            foreach ($aAttr as $k => $v) {
                if (!in_array($k, array('type', 'name', 'value'))) {
                    $sRet .= ' ' . $k . '="' . $v . '"';
                }
            }
        }
        return $sRet;
    } // function get_additional_attr

    /**
     * Prepare data for send (For "form" or "link"
     * @return array
     */
    protected function prepare_send_data()
    {
        $aRet = array();
        foreach (self::$aReqFields as $k => $v) {
            $mVal = $this->get_property_val($k, $v);
            if ($mVal) {
                if ($k == self::PAYMENT_MODE_KEY) {
                    if (count($mVal) > 1) {
                        $i = 0;
                        foreach ($mVal as $v1) {
                            $aRet[$k . '[' . $i++ . ']'] = $v1;
                        }
                    } else {
                        $aRet[$k] = $mVal[0];
                    }
                } else {
                    $aRet[$k] = $mVal;
                }
            }
        }
        return $aRet;
    } // function prepare_send_data

    /**
     * Get value of property
     * @param string $sField
     * @param boolean $bRequired
     * @return mixed
     */
    protected function get_property_val($sField, $bRequired)
    {
        $nNum = self::$aFieldNumber[$sField] * 10;
        if ($sField == self::SIGNATURE_KEY) {
            $mPropVal = $this->get_signature();
        } else {
            $sProp    = self::$aCorrespondence[$sField];
            $mPropVal = $this->$sProp;
        }
        if ($bRequired && !$mPropVal) {
            throw new copayco_exception('Required field "' . $sField . '" isn\'t set.', 20001 + $nNum);
        }
        return $mPropVal;
    } // function get_property_val

    /**
     * Check POST/GET fields of HTTP-request
     * @param array $aCheckFields
     * @return boolean
     */
    protected function check_http_fields($aCheckFields, $bSetErrMsg = FALSE)
    {
        foreach ($aCheckFields as $k => $v) {
            $nNum = self::$aFieldNumber[$k] * 10;
            $mVal = $this->get_http_val($k);
            if ($v && is_null($mVal)) {
                $this->fix_error_message('Undefined required field "' . $k . '".', 30001 + $nNum, $bSetErrMsg);
            }
            $sProp = isset(self::$aCorrespondence[$k]) ? self::$aCorrespondence[$k] : NULL;
            if ($v && $sProp && $mVal != $this->$sProp) {
                $this->fix_error_message('Field "' . $k . '" contain incorrect data.', 30002 + $nNum, $bSetErrMsg);
            }
        }
        if (!$this->check_signature($this->get_http_val(self::SIGNATURE_KEY))) {
            $this->fix_error_message('Request contain incorrect signature.', 30992, $bSetErrMsg);
        }
        return TRUE;
    } // function check_http_fields

    /**
     * Fix error message for method "check_http_fields"
     * @param string $sErrMsg
     * @param integer $nCode
     * @param boolean $bSetErrMsg
     */
    protected function fix_error_message($sErrMsg, $nCode, $bSetErrMsg)
    {
        if ($bSetErrMsg) {
            $this->set_error_message($sErrMsg);
        }
        throw new copayco_exception($sErrMsg, $nCode);
    } // function fix_error_message

    /**
     * Define extendet POST/GET value of HTTP-request
     */
    protected function define_http_val()
    {
        $aCor = array_flip(self::$aCorrespondence);
        foreach (self::$aAutoFill as $v) {
            if (empty($this->$v)) {
                $this->$v = $this->get_http_val($aCor[$v]);
            }
        }
    } // function define_http_val

    /**
     * Get POST/GET value of HTTP-request
     * @param string $sKey
     * @return mixed
     */
    protected function get_http_val($sKey)
    {
        return isset($_POST[$sKey]) ? $_POST[$sKey] : (isset($_GET[$sKey]) ? $_GET[$sKey] : NULL);
    } // function get_http_val

    /**
     * Send notification about delivering
     * @param string $sState - TA state
     */
    protected function send_notification($sState)
    {
        $this->sState = $sState;

        $oCurl = curl_init($this->get_notification_url());
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        if ($this->bTestMode) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 0);
        }

        $this->set_date_and_rand();
        $aData = array();
        foreach (self::$aNotificationFields as $k => $v) {
            $aData[$k] = $this->get_property_val($k, $v);
        }
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $aData);

        curl_exec($oCurl);
        $sErr = curl_error($oCurl);
        curl_close($oCurl);

        if ($sErr) {
            throw new copayco_exception('There is CURL error ocured:' . $sErr, 50001);
        }
    } // function send_notification

    /**
     * Output HTTP-header
     * @param string $sMsg
     * @param string $sCharset
     */
    protected function output_header($sMsg, $sCharset = NULL)
    {
        if (empty($sCharset)) {
            $sCharset = empty($this->sCharset) ? 'utf-8' : $this->sCharset;
        }
        header('Content-Type: text/plain; charset=' . $sCharset);
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . strlen($sMsg));
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header(
            $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0' ?
            'Pragma: no-cache' :
            'Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0'
        ); //  max-age=0
    } // function output_header

} // class copayco_api










/**
 * Copayco exception class
 * Structure of error code
 * |XX|XX|X|
 * |  |  |_ serial number
 * |  |____ field number (See copayco_api::$aFieldNumber)
 * |_______ error type (10 - source data, 20 - make request, 30 - check data, 40 - perform data, 50 - notify delivering)
 *
 */
class copayco_exception extends Exception
{
    /**
     * Get error type code
     * @return boolean
     */
    public function get_error_type_code()
    {
        return substr($this->getCode(), 0, 2);
    } // function get_error_type_code

    /**
     * Get field code
     * @return boolean
     */
    public function get_field_code()
    {
        return substr($this->getCode(), 2, 2);
    } // function get_field_code

    /**
     * Get error serial number
     * @return boolean
     */
    public function get_error_serial_number()
    {
        return substr($this->getCode(), -1);
    } // function get_error_serial_number

    /**
     * Get field name
     * @return string
     */
    public function get_field_name()
    {
        $aFields = array_flip(copayco_api::get_field_number());
        $nCode = (int)$this->get_field_code();
        return $nCode && isset($aFields[$nCode]) ? $aFields[$nCode] : NULL;
    } // function get_field_name

} // class copayco_exception

?>
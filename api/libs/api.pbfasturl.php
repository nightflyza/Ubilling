<?php

/**
 * Privat24 fast payments URL helper
 */
class PBFastURL {
    /**
     * Contains ISP payment token
     *
     * @var string
     */
    protected $token = '';

    /**
     * Contains available prices listed in config option
     *
     * @var array
     */
    protected $pricesAvail = array();

    /**
     * Contains basic SMS text template placed before URL
     *
     * @var string
     */
    protected $template = '';

    /**
     * Full shortener service URL
     *
     * @var string
     */
    protected $shortener = '';

    /**
     * System message helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains user currency
     *
     * @var string
     */
    protected $currency = '';

    /**
     * SendDog enabled/disabled flag
     *
     * @var int
     */
    protected $sendDogFlag = 0;

    /**
     * Is preview checkbox enabled?
     *
     * @var int
     */
    protected $previewFlag = 0;

    /**
     * Contains default mobile numbers prefix
     *
     * @var string
     */
    protected $mobilePrefix = '';

    /**
     * Contains optional user login
     *
     * @var string
     */
    protected $userLogin = '';


    //some predefined stuff here
    const BASE_URL = 'https://next.privat24.ua/payments/form/';
    const OPTION_TOKEN = 'PB_FASTURL_TOKEN';
    const OPTION_PRICES = 'PB_FASTURL_PRICES';
    const OPTION_TEMPLATE = 'PB_FASTURL_TEMPLATE';
    const OPTION_SHORTENER = 'PB_FASTURL_SHORTENER';
    const OPTION_CURRENCY = 'TEMPLATE_CURRENCY';
    const OPTION_SENDDOG = 'SENDDOG_ENABLED';
    const OPTION_PREVIEW = 'PB_FASTURL_PREVIEW';
    const OPTION_MOBILE_PREFIX = 'REMINDER_PREFIX';
    const AGENT_PREFIX = 'UbillingPBFastURL';
    const PROUTE_PAYID = 'pbfupaymentid';
    const PROUTE_AMOUNT = 'pbfuamount';
    const PROUTE_CUST_AMOUNT = 'pbfucustomamount';
    const PROUTE_PREVIEW = 'pbfonlypreview';
    const PROUTE_PHONE = 'pbfuphonenumber';
    const STYLE_PATH = 'skins/pbfu.css';

    //
    //                         _._._                       _._._
    //                        _|   |_                     _|   |_
    //                        | ... |_._._._._._._._._._._| ... |
    //                        | ||| |   o ПРИВАТБАНК o    | ||| |
    //                        | """ |  """    """    """  | """ |
    //                   ())  |[-|-]| [-|-]  [-|-]  [-|-] |[-|-]|  ())
    //                  (())) |     |---------------------|     | (()))
    //                 (())())| """ |  """    """    """  | """ |(())())
    //                 (()))()|[-|-]|  :::   .-"-.   :::  |[-|-]|(()))()
    //                 ()))(()|     | |~|~|  |_|_|  |~|~| |     |()))(()
    //                    ||  |_____|_|_|_|__|_|_|__|_|_|_|_____|  ||
    //                 ~ ~^^ @@@@@@@@@@@@@@/=======\@@@@@@@@@@@@@@ ^^~ ~
    //                      ^~^~                                ~^~^
    public function __construct($login = '') {
        $this->initMessages();
        $this->setOptions();
        $this->setLogin($login);
    }

    /**
     * Sets the options for the current instance using global configuration parameters.
     *
     * This method retrieves various configuration parameters from the global 
     * `$ubillingConfig` object and assigns them to the instance variables.
     *
     * @global object $ubillingConfig The global configuration object.
     * 
     * @return void
     */
    protected function setOptions() {
        global $ubillingConfig;
        $this->token = $ubillingConfig->getAlterParam(self::OPTION_TOKEN);
        $pricesTmp = $ubillingConfig->getAlterParam(self::OPTION_PRICES);
        $this->pricesAvail = explode(',', $pricesTmp);
        $this->template = $ubillingConfig->getAlterParam(self::OPTION_TEMPLATE);
        $this->currency = $ubillingConfig->getAlterParam(self::OPTION_CURRENCY, 'грн');
        $this->shortener = $ubillingConfig->getAlterParam(self::OPTION_SHORTENER);
        $this->sendDogFlag = $ubillingConfig->getAlterParam(self::OPTION_SENDDOG);
        $this->previewFlag = $ubillingConfig->getAlterParam(self::OPTION_PREVIEW);
        $this->mobilePrefix = $ubillingConfig->getAlterParam(self::OPTION_MOBILE_PREFIX, '');
        if ($this->shortener) {
            //forcing trailing slash for shortener service URL
            if (substr($this->shortener, -1) != '/') {
                $this->shortener .= '/';
            }
        }
    }

    /**
     * Sets user login property
     *
     * @param string $login
     * 
     * @return void
     */
    protected function setLogin($login = '') {
        if (!empty($login)) {
            $this->userLogin = ubRouting::filters($login, 'login');
        }
    }

    /**
     * Inits message helper for further usage
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Generates a payment URL based on the provided payment ID and sum.
     *
     * @param string $paymentId The payment ID to include in the URL. Default is an empty string.
     * @param string $sum The sum to include in the URL. Default is an empty string.
     * 
     * @return string
     */
    public function getUrl($paymentId = '', $sum = '') {
        $result = '';
        $fullUrl = self::BASE_URL;
        $urlData = array();
        if ($this->token) {
            $urlData['token'] = $this->token;
            if (!empty($paymentId)) {
                $urlData['personalAccount'] = $paymentId;
            }
            if (!empty($sum)) {
                $urlData['sum'] = $sum;
            }
            $encodedData = json_encode($urlData);
            $fullUrl .= $encodedData;

            if ($this->shortener) {
                $shortenerService = new OmaeUrl($this->shortener . '?shorten=' . $fullUrl);
                $ubVer = file_get_contents('RELEASE');
                $agent = self::AGENT_PREFIX . '/' . trim($ubVer);
                $shortenerService->setUserAgent($agent);
                $newShortenId = $shortenerService->response();
                if (!empty($newShortenId) and $shortenerService->httpCode() == 200) {
                    $result .= $this->shortener . $newShortenId;
                }
            } else {
                $result .= $fullUrl;
            }
        }
        return ($result);
    }


    /**
     * Renders link preview
     *
     * @param string $data
     * 
     * @return string|void
     */
    protected function renderLinkPreview($data) {
        $result = '';
        if (!empty($data)) {
            $result .= wf_tag('div', false, 'pbfu-container');
            $result .= wf_tag('span', false, 'pbfu-text', 'id="pbfu-text"');
            $result .= $data;
            $result .= wf_tag('span', true);
            $result .= wf_tag('button', false, 'pbfu-copy-btn', 'onclick="pbfuCopyToClipboard()"');
            $result .= wf_img('skins/clipcopy.png', __('Copy'));
            $result .= __('Copy');
            $result .= wf_tag('button', true);
            $result .= wf_tag('div', true);
            $result .= wf_tag('script');
            $result .= 'function pbfuCopyToClipboard() {
                            const text = document.getElementById("pbfu-text").innerText;
                            navigator.clipboard.writeText(text).then(() => {
                                alert("' . __('Text copied to clipboard') . '");
                            }).catch(err => {
                                console.error("Failed to copy: ", err);
                            });
                    }';
            $result .= wf_tag('script', true);
        }
        return ($result);
    }

    /**
     * Catches SMS sending request and stores SMS into SendDog sending queue
     *
     * @return string|void
     */
    protected function catchSMSRequest() {
        $result = '';
        if ($this->sendDogFlag) {
            if (ubRouting::checkPost(array(self::PROUTE_PAYID, self::PROUTE_PHONE, self::PROUTE_AMOUNT))) {
                $paymentId = ubRouting::post(self::PROUTE_PAYID, 'mres');
                $mobileNumber = ubRouting::post(self::PROUTE_PHONE, 'mres');
                if ($this->mobilePrefix) {
                    $mobileNumber = str_replace($this->mobilePrefix, '', $mobileNumber);
                    $mobileNumber = $this->mobilePrefix . $mobileNumber;
                }

                $amount = ubRouting::post(self::PROUTE_AMOUNT, 'float');
                if (ubRouting::checkPost(self::PROUTE_CUST_AMOUNT)) {
                    $amount = ubRouting::post(self::PROUTE_CUST_AMOUNT, 'float');
                }
                $paymentUrl = $this->getUrl($paymentId, $amount);
                if (!empty($paymentUrl)) {
                    if (!empty($mobileNumber)) {
                        $smsText = $this->template . ' ' . $paymentUrl;
                        if (!ubRouting::checkPost(self::PROUTE_PREVIEW)) {
                            //SMS sending
                            $smsQueue = new UbillingSMS();
                            $smsQueue->sendSMS($mobileNumber, $smsText, false, 'PBFASTURL');
                            $result .= $this->messages->getStyledMessage($mobileNumber . ' ' . __('SMS') . ': ' . $smsText, 'success');
                            if (!empty($this->userLogin)) {
                                log_register('PBFASTURL SMS (' . $this->userLogin . ') NUM `' . $mobileNumber . '`');
                            }
                        } else {
                            //rendering preview
                            $result .= $this->renderLinkPreview($smsText);
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Mobile') . ' ' . __('is empty'), 'error');
                    }
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('URL') . ' ' . __('is empty'), 'error');
                }

                if (!empty($this->userLogin)) {
                    $result .= wf_delimiter();
                    $result .= wf_BackLink(UserProfile::URL_PROFILE . $this->userLogin, __('Back to user profile'), false, 'ubButton');
                }
            }
        }
        return ($result);
    }

    /**
     * Renders a form for processing a payment URL sending to user.
     *
     * @param string $paymentId The Payment ID of the user. Default is an empty string.
     * @param array $phones An array of phone numbers. Default is an empty array.
     * @param float $defaultAmount An default summ of payment placed on top of the list
     * 
     * @return string The HTML string of the rendered form or an error message if inputs are invalid.
     */
    public function renderForm($paymentId = '', $phones = array(), $defaultAmount = 0) {
        $result = '';
        $result .= wf_tag('link', false, '', 'type="text/css" href="' . self::STYLE_PATH . '" rel="stylesheet"');
        $phonesParams = array();
        if ($this->sendDogFlag) {
            if (!empty($phones)) {
                foreach ($phones as $io => $each) {
                    $phonesParams[$each] = $each;
                }
                if (!empty($paymentId)) {
                    //may be some form already pushed?
                    $sendingResult = $this->catchSMSRequest();
                    if (!empty($sendingResult)) {
                        $sendingResult = wf_tag('div', false, 'pbfu-result-container') . $sendingResult . wf_tag('div', true);
                        $result .= wf_modalOpenedAuto(__('Result'), $sendingResult);
                    }
                    //form construct
                    $inputs = wf_HiddenInput(self::PROUTE_PAYID, $paymentId);
                    $inputs .= wf_Selector(self::PROUTE_PHONE, $phonesParams, __('Mobile'), '', true);
                    $inputs .= wf_delimiter(0);
                    $firstPrice = true;

                    //default amount on top of the list
                    if ($defaultAmount > 0) {
                        $inputs .= wf_RadioInput(self::PROUTE_AMOUNT, $defaultAmount . ' ' . $this->currency, $defaultAmount, true, $firstPrice);
                        $firstPrice = false;
                    }
                    //config-defined prices
                    if (!empty($this->pricesAvail)) {
                        foreach ($this->pricesAvail as $io => $each) {
                            $eSum = trim($each);
                            $inputs .= wf_RadioInput(self::PROUTE_AMOUNT, $eSum . ' ' . $this->currency, trim($eSum), true, $firstPrice);
                            $firstPrice = false;
                        }
                    }
                    $inputs .= wf_TextInput(self::PROUTE_CUST_AMOUNT, __('Other'), '', true, 4, 'finance');
                    if ($this->previewFlag) {
                        $inputs .= wf_CheckInput(self::PROUTE_PREVIEW, __('Just show me link'), true, false);
                    }
                    $inputs .= wf_delimiter(0);
                    $inputs .= wf_Submit(__('Send SMS'));
                    $result .= wf_Form('', 'POST', $inputs, 'glamour');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Payment ID') . ': ' . __('is empty'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Phones') . ' ' . __('is empty'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('SendDog') . ': ' . __('disabled'), 'error');
        }
        return ($result);
    }
}

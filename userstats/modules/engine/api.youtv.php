<?php

/**
 * YouTV users frontend basic class
 */
class YTVInterface {

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains userstats config as key=>value
     *
     * @var array
     */
    protected $usConfig = array();

    /**
     * Contains service-side subscriber ID
     *
     * @var int
     */
    protected $subscriberId = 0;

    /**
     * Contains current instance subscriber data
     *
     * @var array
     */
    protected $subscriberData = array();

    /**
     * Contains remote subscriber full data
     *
     * @var array
     */
    protected $fullData = array();

    /**
     * Contains available tariffs data as serviceId=>tariffData
     *
     * @var array
     */
    protected $tariffsData = array();

    /**
     * Contains all users data as login=>userdata
     *
     * @var string
     */
    protected $allUsers = array();

    /**
     * Some predefined routes/URLs etc..
     */
    const URL_ME = '?module=omyoutv';
    const REQ_BASE = '&action=youtvui&';

    /**
     * Creates new instance
     * 
     * @param string $userLogin
     */
    public function __construct($userLogin) {
        if (!empty($userLogin)) {
            $this->loadConfig();
            $this->setLogin($userLogin);
            $this->loadUsers();
            $this->subscriberData = $this->getSubscriberData();
            if (!empty($this->subscriberData)) {
                $this->subscriberId = $this->subscriberData['subscriberid'];
                $this->fullData = $this->getFullData();
            }
            $this->tariffsData = $this->getTariffsData();
        } else {
            die('ERROR:NO_USER_LOGIN');
        }
    }

    /**
     * Sets current instance user login
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function setLogin($userLogin) {
        $this->myLogin = $userLogin;
    }

    /**
     * Preloads userstats config to protected property
     * 
     * @global array $us_config
     * 
     * @return void
     */
    protected function loadConfig() {
        global $us_config;
        $this->usConfig = $us_config;
    }

    /**
     * Performs some RemoteAPI request and returns its results as array
     * 
     * @param string $request
     * 
     * @return array/bool on error
     */
    protected function getRemoteData($request) {
        $result = false;
        if (!empty($request)) {
            $requestUrl = self::REQ_BASE . $request;
            $rawReply = zbs_remoteApiRequest($requestUrl);

            if (!empty($rawReply)) {
                $result = json_decode($rawReply, true);
            }
        }
        return($result);
    }

    /**
     * Returns some subscriber data assigned to s
     * 
     * @return array
     */
    protected function getSubscriberData() {
        $request = 'subdata=' . $this->myLogin;
        $result = $this->getRemoteData($request);

        return($result);
    }

    /**
     * Returns current subscriberId or void if user is unregistered yet.
     * 
     * @return int/void
     */
    public function getSubscriberId() {
        return($this->subscriberId);
    }

    /**
     * Checks is user use service?
     * 
     * @return bool
     */
    public function userUseService() {
        $result = false;
        if (!empty($this->subscriberData)) {
            if ($this->subscriberData['maintariff']) {
                $result = true;
            }
        }
        return($result);
    }

    /**
     * Returns available tariffs data
     * 
     * @return array
     */
    protected function getTariffsData() {
        $request = 'tardata=true';
        $result = $this->getRemoteData($request);
        return($result);
    }

    /**
     * Returns full subscriber data
     * 
     * @return array
     */
    protected function getFullData() {
        $request = 'fulldata=' . $this->myLogin;
        $result = $this->getRemoteData($request);
        return($result);
    }

    /**
     * Loads available users data from database
     * 
     * @return void
     */
    protected function loadUsers() {
        $query = "SELECT * from `users` WHERE `login`='" . $this->myLogin . "'";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allUsers[$each['login']] = $each;
            }
        }
    }

    /**
     * Renders standard bool led
     * 
     * @param mixed $state
     * 
     * @return string
     */
    protected function webBoolLed($state) {
        $iconsPath = zbs_GetCurrentSkinPath($this->usConfig) . 'iconz/';
        $result = ($state) ? la_img($iconsPath . 'anread.gif') : la_img($iconsPath . 'anunread.gif');
        return($result);
    }

    /**
     * Renders current subscription details
     * 
     * @return string
     */
    public function renderSubscriptionDetails() {
        $result = '';

        if (!empty($this->subscriberData) AND $this->subscriberData['active'] == 1) {
            $mainTariff = @$this->tariffsData[$this->subscriberData['maintariff']];


            $cells = la_TableCell(__('Active'));
            $cells .= la_TableCell(__('Tariff'));
            $cells .= la_TableCell(__('Primary'));
            $cells .= la_TableCell(__('Fee'));
            $rows = la_TableRow($cells, 'row1');
            if (!empty($mainTariff)) {
                $cells = la_TableCell($this->webBoolLed($this->subscriberData['active']));
                $cells .= la_TableCell($mainTariff['name']);
                $cells .= la_TableCell($this->webBoolLed($mainTariff['main']));
                $cells .= la_TableCell($mainTariff['fee'] . ' ' . $this->usConfig['currency']);
                $rows .= la_TableRow($cells, 'row1');
            }
            $result .= la_TableBody($rows, '100%', 0, 'resp-table');
        } else {
            $result = __('No subscriptions yet');
        }

        return($result);
    }

    /**
     * Check user balance for subscribtion availability
     * 
     * @return bool
     */
    protected function checkBalance() {
        $result = false;
        if (!empty($this->myLogin)) {
            if (isset($this->allUsers[$this->myLogin])) {
                $userBalance = $this->allUsers[$this->myLogin]['Cash'];
                if ($userBalance >= 0) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is user protected from his own stupidity?
     * 
     * @param int $tariffId
     * 
     * @return bool
     */
    protected function checkUserProtection($tariffId) {
        $tariffId = vf($tariffId, 3);
        $result = true;

        if (isset($this->tariffsData[$tariffId])) {
            $tariffFee = $this->tariffsData[$tariffId]['fee'];
            $userData = $this->allUsers[$this->myLogin];
            $userBalance = $userData['Cash'];
            if ($userBalance < $tariffFee) {
                $result = false;
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Checks is user subscribed for some tariff or not?
     * 
     * @param int $tariffid
     * 
     * @return bool
     */
    protected function isUserSubscribed($tariffid) {
        $result = false;
        if (!empty($this->subscriberData)) {
            if ($this->subscriberData['active']) {
                if ($this->subscriberData['maintariff'] == $tariffid) {
                    $result = true;
                }
            }
        }
        return ($result);
    }

    public function renderInfoForm(){

        $result = '';


        if (!empty($this->subscriberData) AND $this->subscriberData['active'] == 1) {


        $result .= la_tag('b') . __('Данные для авториазации:') . la_tag('b', true) . la_tag('br');
        $result .=  __('Логин') . ': '.  zbs_UserGetEmail($this->myLogin). la_tag('br') ;
        $result .= __('Пароль') . ': '. $this->allUsers[$this->myLogin]['Password']. la_delimiter(); ;

        $result .= '<!-- yotv -->
            <style>
                .space-1, .space-bottom-1 {
                    padding-bottom: 2rem!important;
                }


                .btn-dark {
                    color: #fff;
                    background-color: #221e1e;
                    border-color: #221e1e;
                    border-radius: .3125rem;
                }

                .transition-3d-hover {
                    transition: all .2s ease-in-out;
                }

                .ml-n8, .mx-n8 {
                    margin-left: -3.5rem!important;
                }
                .mr-n8, .mx-n8 {
                    margin-right: -3.5rem!important;
                }
                .mt-4, .my-4 {
                    margin-top: 1.5rem!important;
                }

                .mt-2, .my-2 {
                    margin-top: .5rem!important;
                }
                *, ::after, ::before {
                    box-sizing: border-box;
                }

                .align-items-center {
                    -ms-flex-align: center!important;
                    align-items: center!important;
                }
                .media {
                    display: -ms-flexbox;
                    display: flex;
                    -ms-flex-align: start;
                    align-items: flex-start;
                }
                *, ::after, ::before {
                    box-sizing: border-box;
                }

                .btn-xs {
                    font-weight: 400;
                    padding: .275rem .75rem;
                }

                .btn-wide {
                    min-width: 10rem;
                }

                .btn-wide {
                    min-width: 15rem;
                }

                .text-left {
                    text-align: left!important;
                }

                .mr-3, .mx-3 {
                    margin-right: 1rem!important;
                }
                .mt-1, .my-1 {
                    margin-top: .25rem!important;
                }
                *, ::after, ::before {
                    box-sizing: border-box;
                }


            </style>

            <div class="text-center"
                 style="background: url(//youtv.ua/assets/images/svg/components/abstract-shapes-19.svg) center no-repeat;">
                <h2 class="h3 w-80 text-center mr-auto ml-auto">Зручні додатки</h2>
                <p class="w-60 w-lg-80 text-center font-size-1 mr-auto ml-auto">Сучасні додатки youtv для різних
                    пристроїв.</p>
                <div class="mt-2 mx-n8">
                    <button type="button" class="btn btn-xs btn-dark btn-wide transition-3d-hover text-left mx-1"
                            onclick="window.open ( \'https://play.google.com/store/apps/details?id=ua.youtv.youtv&amp;hl=uk\' )">
<span class="media align-items-center">
<span class="mt-1 mr-3"><svg class="img-fluid" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 512 512"><path fill="#fff"
                                                         d="M325.3 234.3L104.6 13l280.8 161.2-60.1 60.1zM47 0C34 6.8 25.3 19.2 25.3 35.3v441.3c0 16.1 8.7 28.5 21.7 35.3l256.6-256L47 0zm425.2 225.6l-58.9-34.1-65.7 64.5 65.7 64.5 60.1-34.1c18-14.3 18-46.5-1.2-60.8zM104.6 499l280.8-161.2-60.1-60.1L104.6 499z"></path></svg></span>
<span class="media-body">
<span class="d-block mb-n1">Доступно в</span>  <br>
<span class="font-size-1">Google Play</span>
</span>
</span>
                    </button>

                    <button type="button" class="btn btn-xs btn-dark btn-wide transition-3d-hover text-left mx-1"
                            onclick="window.open ( \'https://apps.apple.com/us/app/you-tv-onlajn-tv/id1176282993?l=uk\' )">
<span class="media align-items-center">
<span class="mt-1 mr-3"><svg class="img-fluid" xmlns="http://www.w3.org/2000/svg" width="18" height="24"
                             viewBox="0 0 384 512"><path fill="#fff"
                                                         d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z"></path></svg></span>
<span class="media-body">
<span class="d-block mb-n1">Завантажити в</span>  <br>
<span class="font-size-1">App Store</span>
</span>
</span>
                    </button>
                </div>
                <div class="mt-2 mx-n8">

                    <button type="button" class="btn btn-xs btn-dark btn-wide transition-3d-hover text-left mx-1"
                            onclick="return false;">
<span class="media align-items-center">
<span class="mt-1 mr-3"><svg class="img-fluid" xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                             viewBox="0 0 24 24"><g><g><g><polygon fill="#fff"
                                                                   points="9.3,19.4 7.2,22.2 17.1,22.2 15,19.4"></polygon><path
                            fill="#fff"
                            d="M22.1,2.9H1.9C0.8,2.9,0,3.8,0,4.8v11.5c0,1,0.8,1.9,1.9,1.9h20.3c1,0,1.9-0.8,1.9-1.9V4.8C24,3.8,23.2,2.9,22.1,2.9z"></path></g></g></g></svg></span>
<span class="media-body">
<span class="d-block mb-n1">Доступно для</span>
    <br>
<span class="font-size-1">Smart TV</span>
</span>
</span>
                    </button>
                    <button type="button" class="btn btn-xs btn-dark btn-wide transition-3d-hover text-left mx-1"
                            onclick="window.open ( \'https://appgallery.huawei.com/#/app/C103041047\' )">
<span class="media align-items-center">
<span class="mt-1 mr-3"><svg class="img-fluid" xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                             viewBox="0 0 24 18" fill="#fff"><path
                d="M10.34,17a.1.1,0,0,0,0-.09A54.74,54.74,0,0,0,3.68,6.17s-2.1,2-1.95,4a3.5,3.5,0,0,0,1.21,2.39c1.83,1.78,6.27,4,7.3,4.52a.06.06,0,0,0,.09,0m-.68,1.52a.1.1,0,0,0-.09-.07h0l-7.38.25c.8,1.42,2.14,2.53,3.55,2.19a19.19,19.19,0,0,0,3.89-2.28h0c.05-.05,0-.09,0-.09m.11-.66c0-.06,0-.11,0-.11h0C6.49,15.62.21,12.28.21,12.28a4.35,4.35,0,0,0,2.53,5.37A4.88,4.88,0,0,0,4.15,18c.11,0,4.39,0,5.54,0a.08.08,0,0,0,.07-.05M10.25,3c-.32,0-1.19.22-1.19.22A3.44,3.44,0,0,0,6.65,5.49a4.39,4.39,0,0,0,0,2.33c.65,2.88,3.86,7.61,4.55,8.61a.11.11,0,0,0,.09,0,.08.08,0,0,0,.06-.1h0C12.43,5.8,10.25,3,10.25,3m2.44,13.45a.08.08,0,0,0,.11,0h0c.71-1,3.9-5.72,4.55-8.59a4.83,4.83,0,0,0,0-2.33,3.41,3.41,0,0,0-2.44-2.26A10.42,10.42,0,0,0,13.76,3s-2.19,2.8-1.12,13.37h0a.08.08,0,0,0,0,.08m1.75,2.05a.12.12,0,0,0-.08.05.09.09,0,0,0,0,.1h0a19.18,19.18,0,0,0,3.87,2.29s1.91.64,3.57-2.19l-7.39-.26Zm9.35-6.24s-6.27,3.35-9.51,5.53h0a.09.09,0,0,0,0,.11.11.11,0,0,0,.08.05H20a5,5,0,0,0,1.27-.29,4.33,4.33,0,0,0,2.54-5.39M13.68,17a.12.12,0,0,0,.1,0h0c1.06-.53,5.46-2.75,7.28-4.52a3.52,3.52,0,0,0,1.21-2.4c.14-2.06-1.94-4-1.94-4a54.19,54.19,0,0,0-6.67,10.75h0a.09.09,0,0,0,0,.11"
                transform="translate(0 -3)"></path></svg></span>
<span class="media-body">
<span class="d-block mb-n1">Завантажити в</span>  <br>
<span class="font-size-1">AppGallery</span>
</span>
</span>
                    </button>
                </div>
            </div>
            <br><br>
            <!-- yotv -->
        ';
        } else {
            $result = '';
        }

        return  $result;
    }

    /**
     * Renders available subscriptions list
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';

        $css = "<style>

.youtv-button-s {
    text-decoration: none !important;
    display: block;
    border-radius: 5px;
    color: #f44336;
    background-color: rgba(255,55,55,.1);
    margin: 0 auto;
    left: 0;
    bottom: 35px;
    text-transform: uppercase;
    right: 0;
    width: 65%;
    padding: 10px 10px 10px 10px;
}

.youtv-button-s:hover {
    background-color: #f44336;
    border-color: #f44336;
    color: white;
}

.youtv-col {
    position: relative;
    min-height: 1px;
    padding-left: 15px;
    padding-right: 15px;
}

@media (min-width: 992px){
    .youtv-col {
        width: 33.33333333%;
    }
}

@media (min-width: 992px){
    .youtv-col {
        float: left;
    }
}

.youtv-list {
    padding: 10px;
    font-size: 10pt;
}

.youtv-bl1 {
    margin-bottom: 15px;
    display: block;
    height: 340px;
    text-align: center;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, .2);
}

.youtv-bl1 p {

    padding: 5px;
    border-radius: 5px;
    color: white;
    background: #6cb121;
    margin: 0 auto;
    left: 0;
    bottom: 35px;
    text-transform: uppercase;
    right: 0;
    width: 75%;
}

.youtv-button {
    text-decoration: none !important;
    border-radius: 5px;
    display: block;
    float: left;
    color: white;
    background: #6cb121;
    margin: 10px auto;
    margin-left: 10px;
    left: 0;
    bottom: 35px;
    right: 0;
    padding: 10px 10px 10px 10px;
}

.youtv-button-u {
    text-decoration: none !important;
    display: block;
    border-radius: 5px;
    color: white;
    background: #a1a79c;
    margin: 0 auto;
    left: 0;
    bottom: 35px;
    text-transform: uppercase;
    right: 0;
    width: 65%;
    padding: 10px 10px 10px 10px;
}


.youtv-price {
    font-size: 35pt;
    padding-top: 20px;
    /* font-family: 'Myriad Pro Bold';*/
}

.youtv-red {
    margin-top: 10px;
    height: 40px;
    background: #f44336;
    color: white;
    font-size: 12pt;
    font-weight: bold;
    text-transform: uppercase;
    line-height: 40px;
}

.youtv-tariff-b s{
    background: rgba(85, 148, 27, 0.59);
}

.youtv-price sup {
    text-transform: uppercase;
    font-size: 15pt;
    /*font-family: 'Myriad Pro Regular';*/
}

</style>";
        $result .= $css;


        $result .= la_tag('b') . __('Attention!') . la_tag('b', true) . ' ';


        $result .= __('When activated subscription account will be charged fee the equivalent value of the subscription.') . la_delimiter();
        if (!empty($this->tariffsData)) {
            foreach ($this->tariffsData as $serviceId => $tariff) {

                $tariffFee = $tariff['fee'];

                $tariffInfo = la_tag('div', false, 'youtv-col') . la_tag('div', false, 'youtv-bl1');

                $tariffInfo .= la_tag('div', false, 'youtv-price');
                $tariffInfo .= la_tag('b', false, 's') . $tariffFee . la_tag('b', true, 's');
                $tariffInfo .= la_tag('sup', false) . $this->usConfig['currency'] . ' ' . la_tag('br') . ' ' . __('per month') . la_tag('sup', true);
                $tariffInfo .= la_tag('div', true, 'youtv-price');


                $tariffInfo .= la_tag('div', false, 'youtv-red s') . $tariff['name'] . la_tag('div', true, 'youtv-red s');
                $tariffInfo .= la_tag('br');

                if (!empty($tariff['chans'])) {
                    $desc = $tariff['chans'];
                } else {
                    $desc = '';
                }

                $descriptionLabel = $desc;

                $tariffInfo .= la_tag('div', false, 'youtv-list') . $descriptionLabel . la_tag('div', true, 'youtv-list');

                if ($this->checkBalance()) {

                    if ($this->isUserSubscribed($tariff['serviceid'])) {
                        $tariffInfo .= la_Link(self::URL_ME . '&unsubscribe=' . $tariff['serviceid'], __('Unsubscribe'), false, 'youtv-button-u');
                    } else {
                        if ($this->checkUserProtection($tariff['serviceid'])) {
                            $alertText = __('I have thought well and understand that I activate this service for myself not by chance and completely meaningfully and I am aware of all the consequences.');
                            $tariffInfo .= la_ConfirmDialog(self::URL_ME . '&subscribe=' . $tariff['serviceid'], __('Subscribe'), $alertText, 'youtv-button-s', self::URL_ME);
                        } else {
                            $tariffInfo .= la_tag('div', false, 'youtv-list') . __('The amount of money in your account is not sufficient to process subscription') . la_tag('div', true, 'youtv-list');
                        }
                    }
                } else {
                    $tariffInfo .= la_tag('div', false, 'youtv-list') . __('The amount of money in your account is not sufficient to process subscription') . la_tag('div', true, 'youtv-list');
                }

                $tariffInfo .= la_tag('div', true, 'youtv-bl1') . la_tag('div', true, 'youtv-col');


                $result .= $tariffInfo;
            }
        }
        return($result);
    }


    /**
     * Deactivates user service due deleting of tariff
     * 
     * @param int $tariffId
     * 
     * @return void
     */
    public function unsubscribe($tariffId) {
        $request = 'unsub=' . $tariffId . '&subid=' . $this->subscriberId;
        $this->getRemoteData($request);
    }

    /**
     * Activates new service for user
     * 
     * @param int $tariffId
     * 
     * @return void
     */
    public function subscribe($tariffId) {
        $request = 'subserv=' . $tariffId . '&sublogin=' . $this->myLogin;
        $this->getRemoteData($request);
    }

}

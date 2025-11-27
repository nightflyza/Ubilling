<?php

/**
 * Ubilling REST API implementation class
 */
class XMLAgent {
    /**
     * Defines the REST API responses format: XML or JSON
     * XML is a default
     *
     * @var string
     */
    protected $outputFormat = 'xml';

    /**
     * Placeholder for Ubilling UserStats config instance
     *
     * @var object
     */
    protected $usConfig = null;

    /**
     * Placeholder for PAYMENTS_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgPaymentsON = 0;

    /**
     * Placeholder for PAYMENTS_ONLYPOSITIVE "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgPaymentsOnlyPositive = 0;

    /**
     * Placeholder for PAYMENTS_DEPTH_LIMIT "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgPaymentsDepthLimit = 0;

    /**
     * Placeholder for AN_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgAnnouncementsON = 0;

    /**
     * Placeholder for TICKETING_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgTicketingON = 0;

    /**
     * Placeholder for UBA_XML_ADDRESS_STRUCT "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgAddressStructON = 0;

    /**
     * Placeholder for ONLINELEFT_COUNT "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgOnlineLeftCountON = 0;

    /**
     * Placeholder for OPENPAYZ_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgOpenPayzON = 0;

    /**
     * Placeholder for OPENPAYZ_REALID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgOpenPayzRealIDON = 0;

    /**
     * Placeholder for OPENPAYZ_URL "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgOpenPayzURL = '../openpayz/backend/';

    /**
     * Placeholder for OPENPAYZ_PAYSYS "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgOpenPayzPaySys = '';

    /**
     * Placeholder for currency "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgCurrency = 'UAH';

    /**
     * Placeholder for TC_ENABLED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgTariffCahngeEnabled = 0;

    /**
     * Placeholder for TC_EXTENDED_MATRIX "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgTariffCahngeMatrix = 0;

    /**
     * Placeholder for TC_TARIFFSALLOWED "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgTariffCahngeAllowedTo = '';

    /**
     * Placeholder for TC_TARIFFENABLEDFROM "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgTariffCahngeAllowedFrom = '';

    /**
     * Placeholder for UKV_ENABLED "userstats.ini" option
     *
     * @var bool
     */
    protected $uscfgUKVEnabled = false;

    /**
     * Placeholder for AF_ENABLED "userstats.ini" option
     *
     * @var bool
     */
    protected $uscfgFreezeSelfON = false;

    /**
     * Placeholder for AF_FREEZPRICE "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgFreezeSelfPrice = 0;

    /**
     * Placeholder for AF_FREEZPRICE_PERIOD "userstats.ini" option
     *
     * @var string
     */
    protected $uscfgFreezeSelfPricePeriod = 'day';

    /**
     * Placeholder for AF_TARIFFSALLOWED "userstats.ini" option
     *
     * @var string
     */
    protected $uscfgFreezeSelfTariffsAllowed = '';

    /**
     * Placeholder for AF_TARIFF_ALLOW_ANY "userstats.ini" option
     *
     * @var bool
     */
    protected $uscfgFreezeSelfTariffAllowAny = false;

    /**
     * Placeholder for AF_CASHTYPEID "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgFreezeSelfPriceCashTypeID = 1;

    /**
     * Placeholder for FREEZE_ALLOW_ON_NEGATIVE_BALANCE "userstats.ini" option
     *
     * @var bool
     */
    protected $uscfgFreezeIfNegativeBalance = false;

    /**
     * Placeholder for FREEZE_DAYS_CHARGE_ENABLED "userstats.ini" option
     *
     * @var bool
     */
    protected $uscfgFreezeDaysChargeON = false;

    /**
     * Placeholder for FREEZE_DAYS_INITIAL_AMOUNT "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgFreezeDaysInitAmount = 365;

    /**
     * Placeholder for FREEZE_DAYS_WORK_TO_RESTORE "userstats.ini" option
     *
     * @var int
     */
    protected $uscfgFreezeDaysWorkToRestore = 120;


    /**
     * Placeholder for the whole "opayz.ini" config contents
     *
     * @var int
     */
    protected $usOpayzCfg = array();

    /**
     * Placeholder for the whole "tariffmatrix.ini" config contents
     */
    protected $usTariffMatrixCfg = array();

    /**
     * Placeholder for XMLAGENT_DEBUG_ON "userstats.ini" option
     *
     * @var bool
     */
    protected $debug = false;

    /**
     * Placeholder for XMLAGENT_DEBUG_DEEP_ON "userstats.ini" option
     *
     * @var bool
     */
    protected $debugDeep = false;

    /**
     * Placeholder for XMLAGENT_EXTENDED_AUTH_ON "userstats.ini" option
     *
     * @var bool
     */
    protected $extendedAuthON = false;

    /**
     * Placeholder for XMLAGENT_SELF_UNFREEZE_ALLOWED "userstats.ini" option
     *
     * @var bool
     */
    protected $selfUnFreezeAllowed = false;

    /**
     * Contains auth key (MD5 hash of the current UB instance serial) from the incoming request
     *
     * @var string
     */
    protected $extendedAuthKey = '';


    const TICKET_TYPE_SUPPORT   = 'support_request';
    const TICKET_TYPE_SIGNUP    = 'signup_request';
    const DEBUG_FILE_PATH       = 'exports/xmlagent.debug';
    const TARIFF_MATRIX_CONFIG_PATH = 'config/tariffmatrix.ini';


    public function __construct($user_login = '') {
        $this->loadConfig();
        $this->loadOptions();
        $this->outputFormat = (ubRouting::checkGet('json') ? 'json' : 'xml');
        $this->extendedAuthKey = (ubRouting::checkGet('uberkey') ? ubRouting::get('uberkey') : '');
        $this->router($user_login);
    }


    /**
     * Get the UserStatsConfig instance
     *
     * @return void
     */
    protected function loadConfig() {
        $this->usConfig = new UserStatsConfig();
    }


    /**
     * Essential options loader
     *
     * @return void
     */
    protected function loadOptions() {
        $this->usOpayzCfg                       = $this->usConfig->getOpayzCfg();
        $this->usTariffMatrixCfg                = $this->usConfig->getTariffMatrixCfg();

        $this->uscfgPaymentsON                  = $this->usConfig->getUstasParam('PAYMENTS_ENABLED', 0);
        $this->uscfgPaymentsOnlyPositive        = $this->usConfig->getUstasParam('PAYMENTS_ONLYPOSITIVE', 0);
        $this->uscfgPaymentsDepthLimit          = $this->usConfig->getUstasParam('PAYMENTS_DEPTH_LIMIT', 0);
        $this->uscfgAnnouncementsON             = $this->usConfig->getUstasParam('AN_ENABLED', 0);
        $this->uscfgTicketingON                 = $this->usConfig->getUstasParam('TICKETING_ENABLED', 0);
        $this->uscfgAddressStructON             = $this->usConfig->getUstasParam('UBA_XML_ADDRESS_STRUCT', 0);
        $this->uscfgOnlineLeftCountON           = $this->usConfig->getUstasParam('ONLINELEFT_COUNT', 0);
        $this->uscfgOpenPayzON                  = $this->usConfig->getUstasParam('OPENPAYZ_ENABLED', 0);
        $this->uscfgOpenPayzRealIDON            = $this->usConfig->getUstasParam('OPENPAYZ_REALID', 0);
        $this->uscfgOpenPayzURL                 = $this->usConfig->getUstasParam('OPENPAYZ_URL', '../openpayz/backend/');
        $this->uscfgOpenPayzPaySys              = $this->usConfig->getUstasParam('OPENPAYZ_PAYSYS', 0);
        $this->uscfgCurrency                    = $this->usConfig->getUstasParam('currency', 'UAH');
        $this->uscfgTariffCahngeEnabled         = $this->usConfig->getUstasParam('TC_ENABLED', 0);
        $this->uscfgTariffCahngeMatrix          = $this->usConfig->getUstasParam('TC_EXTENDED_MATRIX', 0);
        $this->uscfgTariffCahngeAllowedTo       = $this->usConfig->getUstasParam('TC_TARIFFSALLOWED', '');
        $this->uscfgTariffCahngeAllowedFrom     = $this->usConfig->getUstasParam('TC_TARIFFENABLEDFROM', '');
        $this->debug                            = $this->usConfig->getUstasParam('XMLAGENT_DEBUG_ON', false);
        $this->debugDeep                        = $this->usConfig->getUstasParam('XMLAGENT_DEBUG_DEEP_ON', false);
        $this->extendedAuthON                   = $this->usConfig->getUstasParam('XMLAGENT_EXTENDED_AUTH_ON', false);
        $this->selfUnFreezeAllowed              = $this->usConfig->getUstasParam('XMLAGENT_SELF_UNFREEZE_ALLOWED', false);
        $this->uscfgUKVEnabled                  = $this->usConfig->getUstasParam('UKV_ENABLED', false);

        $this->uscfgFreezeSelfON                = $this->usConfig->getUstasParam('AF_ENABLED', false);
        $this->uscfgFreezeSelfPrice             = $this->usConfig->getUstasParam('AF_FREEZPRICE', 0);
        $this->uscfgFreezeSelfPricePeriod       = $this->usConfig->getUstasParam('AF_FREEZPRICE_PERIOD', 'day');
        $this->uscfgFreezeSelfTariffsAllowed    = $this->usConfig->getUstasParam('AF_TARIFFSALLOWED', false);;
        $this->uscfgFreezeSelfTariffAllowAny    = $this->usConfig->getUstasParam('AF_TARIFF_ALLOW_ANY', false);
        $this->uscfgFreezeSelfPriceCashTypeID   = $this->usConfig->getUstasParam('AF_CASHTYPEID', 1);
        $this->uscfgFreezeIfNegativeBalance     = $this->usConfig->getUstasParam('FREEZE_ALLOW_ON_NEGATIVE_BALANCE', false);
        $this->uscfgFreezeDaysChargeON          = $this->usConfig->getUstasParam('FREEZE_DAYS_CHARGE_ENABLED', false);
        $this->uscfgFreezeDaysInitAmount        = $this->usConfig->getUstasParam('FREEZE_DAYS_INITIAL_AMOUNT', 365);
        $this->uscfgFreezeDaysWorkToRestore     = $this->usConfig->getUstasParam('FREEZE_DAYS_WORK_TO_RESTORE', 120);
    }

    /**
     * Retrieves the UB serial from DB
     *
     * @return mixed|string
     */
    protected function getUBSerial() {
        $result = '';
        $dbUbstats = new NyanORM('ubstats');
        $dbUbstats->where('key', '=', 'ubid');
        $result = $dbUbstats->getAll('key');
        $result = empty($result) ? '' : $result['ubid']['value'];

        return($result);
    }

    /**
     * Chooses the destination according to GET params
     *
     * @param $user_login
     *
     * @return void
     */
    public function router($user_login) {
        $extenAuthSuccessful = true;
        $outputFormat        = $this->outputFormat;

        if ($this->extendedAuthON) {
            if (empty($this->extendedAuthKey)) {
                $extenAuthSuccessful = false;
            } else {
                $ubKey               = $this->getUBSerial();
                $extenAuthSuccessful = (md5($ubKey) === $this->extendedAuthKey);
            }
        }

        if (!$extenAuthSuccessful) {
            $this->renderResponse(array(array('reason' => 'wrong_uberauth')), 'error', '', $outputFormat);
        }

        $resultToRender = array();
        $mainSection    = 'data';
        $subSection     = '';
        $messages       = false;
        $restapiMethod  = '';
        $debugData      = '';
        $getUserData    = !(ubRouting::checkGet(array(
                                    'payments',
                                    'announcements',
                                    'tickets',
                                    'opayz',
                                    'agentassigned',
                                    'tariffvservices',
                                    'activetariffsvservices',
                                    'tarifftoswitchallowed',
                                    'feecharges',
                                    'ticketcreate',
                                    'freezedata',
                                    'dofreeze',
                                    'dounfreeze',
                                    'ukv',
                                    'annreadall',
                                    'justauth',
                                    ),
                                true, true)
                            );

        if (!empty($user_login)) {
            if ($getUserData) {
                $mainSection    = 'userdata';
                $restapiMethod  = 'getuserdata';
                $resultToRender = $this->getUserData($user_login);
            } else {
                if (ubRouting::checkGet('payments') and $this->uscfgPaymentsON) {
                    $subSection     = 'payment';
                    $resultToRender = $this->getUserPayments($user_login);
                }

                if (ubRouting::checkGet('tickets') and $this->uscfgTicketingON) {
                    $subSection     = 'ticket';
                    $resultToRender = $this->getUserTickets($user_login);
                }

                if (ubRouting::checkGet('opayz') and $this->uscfgOpenPayzON) {
                    $subSection     = 'paysys';
                    $resultToRender = $this->getUserOpenPayz($user_login);
                }

                if (ubRouting::checkGet('ukv') and $this->uscfgUKVEnabled) {
                    $restapiMethod  = 'ukv';
                    $resultToRender = $this->getUKVUserData($user_login);
                }

                if (ubRouting::checkGet('agentassigned')) {
                    $subSection     = 'agentdata';
                    $resultToRender = $this->getUserContrAgent($user_login);
                }

                if (ubRouting::checkGet('tariffvservices')) {
                    $subSection     = 'tariffvservices';
                    $resultToRender = $this->getUserTariffAndVservices($user_login);
                }

                if (ubRouting::checkGet('tarifftoswitchallowed')) {
                    $subSection     = 'tarifftoswitchallowed';
                    $resultToRender = $this->getTariffsToSwitchAllowed($user_login);
                }

                if (ubRouting::checkGet('feecharges')) {
                    $subSection     = 'feecharge';
                    $date_from      = ubRouting::checkGet('datefrom') ? ubRouting::get('datefrom') : '';
                    $date_to        = ubRouting::checkGet('dateto') ? ubRouting::get('dateto') : '';
                    $resultToRender = $this->getUserFeeCharges($user_login, $date_from, $date_to);
                }

                if (ubRouting::checkGet('freezedata')) {
                    $mainSection    = 'freezedata';
                    $resultToRender = $this->getUserFreezeData($user_login);
                }

                if (ubRouting::checkGet('dofreeze')) {
                    $mainSection    = 'dofreeze';
                    $resultToRender = $this->doFreeze($user_login);
                }

                if (ubRouting::checkGet('dounfreeze')) {
                    $mainSection    = 'dounfreeze';
                    $resultToRender = $this->doUNFreeze($user_login);
                }

                if (ubRouting::checkGet(array('ticketcreate', 'tickettext', 'tickettype'))
                    and ubRouting::get('tickettype') == self::TICKET_TYPE_SUPPORT
                ) {
                    $text           = base64_decode(ubRouting::get('tickettext','raw'));
                    $replyID        = ubRouting::checkGet('reply_id') ? ubRouting::get('reply_id') : 0;
                    $debugData      = empty($replyID) ? 'replyID: ' . $replyID . '  ' . $text : $text;
                    $restapiMethod  = 'supportticketcreate';
                    $resultToRender = $this->createSupportTicket($user_login, $text, $replyID);
                }
            }
        }

        if (ubRouting::checkGet('justauth')) {
            $restapiMethod  = 'justauth';
            $resultToRender = $this->justAuth($user_login);
        }

        if (ubRouting::checkGet('announcements') and $this->uscfgAnnouncementsON) {
            $messages       = true;
            $restapiMethod  = 'announcements';
            $resultToRender = $this->getAnnouncements($user_login);
        }

        if (ubRouting::checkGet('annreadall')) {
            $restapiMethod  = 'annreadall';
            $resultToRender = $this->markAllAnnouncementsAsRead($user_login);
        }

        if (ubRouting::checkGet('activetariffsvservices')) {
            $subSection     = 'activetariffsvservices';
            $resultToRender = $this->getAllTariffsVservices();
        }

        if (ubRouting::checkGet(array('ticketcreate', 'tickettype'))
            and ubRouting::get('tickettype') == self::TICKET_TYPE_SIGNUP) {

            $requestJSON    = file_get_contents("php://input");
            $debugData      = $requestJSON;
            $restapiMethod  = 'signupticketcreate';
            $resultToRender = $this->createSignUpRequest($requestJSON);
        }

        $restapiMethod  = (empty($restapiMethod) and !empty($subSection)) ? $subSection : (empty($mainSection) ? $restapiMethod : $mainSection);

        $this->debugLog($restapiMethod, $debugData);
        $this->renderResponse($resultToRender, $mainSection, $subSection, $outputFormat, $messages);
    }


    /**
     * Renders some data as XML/JSON
     *
     * @param array $data data array for rendering
     * @param string $mainSection all output data parent element tag name
     * @param string $subSection parent tag for each data qunique element tag name
     * @param string $format output format: xml or json
     * @param bool $messages is data contain announcements data for render
     *
     * @return void
     */
    public static function renderResponse($data, $mainSection = '', $subSection = '', $format = 'xml', $messages = false) {
        $result = '';
        //XML legacy output
        if ($format == 'xml') {
            $result .= '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;

            if (!empty($mainSection)) {
                $result .= '<' . $mainSection . '>' . PHP_EOL;
            }
            if (!empty($data)) {
                foreach ($data as $index => $record) {
                    if (!empty($subSection)) {
                        $result .= '<' . $subSection . '>' . PHP_EOL;
                    }

                    //normal data output
                    if (!$messages) {
                        foreach ($record as $tag => $value) {
                            $result .= "\t" . '<' . $tag . '>' . $value . '</' . $tag . '>' . PHP_EOL;
                        }
                    } else {
                        //announcements data output
                        $result .= '<message unic="' . $record['unic'] . '" title="' . $record['title'] . '">' . $record['text'] . '</message>' . PHP_EOL;
                    }

                    if (!empty($subSection)) {
                        $result .= '</' . $subSection . '>' . PHP_EOL;
                    }
                }
            }

            if (!empty($mainSection)) {
                $result .= '</' . $mainSection . '>' . PHP_EOL;
            }
        }

        //JSON data output
        if ($format == 'json') {
            $jsonData = array();
            $rcount = 0;

            if (!empty($data)) {
                foreach ($data as $index => $record) {
                    if (!empty($record)) {
                        foreach ($record as $tag => $value) {
                            if (!empty($subSection) OR $messages) {
                                $jsonData[$rcount][$tag] = $value;
                            } else {
                                $jsonData[$tag] = $value;
                            }
                        }
                    }

                    $rcount++;
                }
            }

            $result .= json_encode($jsonData);
        }

        //pushing result to client
        $contentType = 'text';

        if ($format == 'json') {
            $contentType = 'application/json';
        }

        header('Last-Modified: ' . gmdate('r'));
        header('Content-Type: ' . $contentType . '; charset=UTF-8');
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Pragma: no-cache");
        header('Access-Control-Allow-Origin: *');

        die($result);
    }


    /**
     * Data collector for "payments" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserPayments($login) {
        $payments = array();
        $positiveFilter =$this->uscfgPaymentsOnlyPositive;
        $depthLimit = $this->uscfgPaymentsDepthLimit;

        $allpayments = zbs_CashGetUserPayments($login, $positiveFilter, $depthLimit);

        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $eachpayment) {
                    $payments[$eachpayment['id']]['date']    = $eachpayment['date'];
                    $payments[$eachpayment['id']]['summ']    = $eachpayment['summ'];
                    $payments[$eachpayment['id']]['balance'] = $eachpayment['balance'];
            }
        }

        return ($payments);
    }

    /**
     * Sanitizes Telegram tags
     * 
     * @param string $text
     * 
     * @return string
     */
    protected function sanitizeTgTags($text) {
        $telegramAllowedTags = array(
            '<b>',
            '<strong>',
            '<i>',
            '<em>',
            '<u>',
            '<ins>',
            '<s>',
            '<strike>',
            '<del>',
            '<span>',
            '<code>',
            '<pre>',
            '<a>'
        );
        $text = strip_tags($text, $telegramAllowedTags);
        return ($text);
    }

    /**
     * Data collector for "justauth" request
     * 
     * @param string $login
     *
     * @return array
     */
    protected function justAuth($login) {
        $login = ubRouting::filters($login, 'login');
        $result = array();
        $result[] = array(
            'auth' => true,
            'login' => $login,
            'message' => __('Authentication successful')
        );
        return ($result);
    }

    /**
     * Data collector for "announcements" request
     * 
     * @param string $login
     *
     * @return array
     */
    protected function getAnnouncements($login='') {
        $login = ubRouting::filters($login, 'login');
        $annArr     = array();
        $allAnnouncements = zbs_AnnouncementsGetAll($login);

        if (!empty($allAnnouncements)) {
            foreach ($allAnnouncements as $ian => $eachAnnouncement) {
                $allTitle = strip_tags($eachAnnouncement['title']);
                $annText = $eachAnnouncement['text'];
                if ( $eachAnnouncement['type'] != 'html') {
                    $annText = strip_tags($annText);
                } else {
                    $annText = $this->sanitizeTgTags($annText);
                }
                
                
                $readFlag=($eachAnnouncement['annid']) ? 1 : 0;

                $annArr[] = array(
                    'unic' => $eachAnnouncement['id'],
                    'read' => $readFlag,
                    'type' => $eachAnnouncement['type'],
                    'title' => $allTitle,
                    'text' => $annText
                );
            }
        }

        return ($annArr);
    }

    /**
     * Data collector for "annreadall" request
     * 
     * @param string $login
     *
     * @return array
     */
    protected function markAllAnnouncementsAsRead($login='') {
        $login = ubRouting::filters($login, 'login');
        $allUnreadAnnouncements = $this->getAnnouncements($login);
        if (!empty($allUnreadAnnouncements)) {
            foreach ($allUnreadAnnouncements as $io => $eachUnreadAnnouncement) {
                if ($eachUnreadAnnouncement['read'] == 0) {
                    $this->markAnnouncementAsRead($eachUnreadAnnouncement['unic'],$login);
                }
            }
        }
        return (array());
    }

    /**
     * Marks an announcement as read
     * 
     * @param int $announcementId
     * @param string $login
     *
     * @return void
     */
    protected function markAnnouncementAsRead($announcementId,$login) {
        $announcementId = ubRouting::filters($announcementId, 'int');
        $login = ubRouting::filters($login, 'login');
        zbs_AnnouncementsLogPush($login, $announcementId);
    }


    /**
     * Data collector for "tickets" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserTickets($login) {
        $ticketsArr     = array();
        $myTickets      = array();
        $ticketsTable   = new NyanORM('ticketing');
        $ticketsTable->orderBy('date', 'DESC');
        $allTickets     = $ticketsTable->getAll();

        if (!empty($allTickets)) {
            foreach ($allTickets as $io => $each) {
                if ($each['from'] == $login or $each['to'] == $login or isset($myTickets[$each['replyid']])) {
                    $myTickets[$each['id']]             = $each['id'];
                    $ticketsArr[$each['id']]['id']      = $each['id'];
                    $ticketsArr[$each['id']]['date']    = $each['date'];
                    $ticketsArr[$each['id']]['from']    = $each['from'];
                    $ticketsArr[$each['id']]['to']      = $each['to'];
                    $ticketsArr[$each['id']]['replyid'] = $each['replyid'];
                    $ticketsArr[$each['id']]['status']  = $each['status'];
                    $ticketsArr[$each['id']]['text']    = $each['text'];
                }
            }
        }

        return ($ticketsArr);
    }


    /**
     * Data collector for "opayz" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserOpenPayz($login) {
        $opayzArr       = array();
        $paySys         = explode(",", $this->uscfgOpenPayzPaySys);
        $payDesc        = (empty($this->usOpayzCfg) ? array() : $this->usOpayzCfg);
        $opayzPaymentid = 0;

        if ($this->uscfgOpenPayzRealIDON) {
            $opayzPaymentid = zbs_PaymentIDGet($login);
        } else {
            $userdata = zbs_UserGetStargazerData($login);
            $opayzPaymentid = ip2int($userdata['IP']);
        }

        if (!empty($paySys)) {
            if (!empty($opayzPaymentid)) {
                foreach ($paySys as $io => $eachpaysys) {
                    if (isset($payDesc[$eachpaysys])) {
                        $paysys_desc = $payDesc[$eachpaysys];
                    } else {
                        $paysys_desc = '';
                    }

                    $paymentUrl = $this->uscfgOpenPayzURL . $eachpaysys . '/?customer_id=' . $opayzPaymentid;
                    $opayzArr[$eachpaysys]['name'] = $eachpaysys;
                    $opayzArr[$eachpaysys]['url'] = $paymentUrl;
                    $opayzArr[$eachpaysys]['description'] = $paysys_desc;
                }
            }
        }

        return ($opayzArr);
    }

    /**
     * Data collector for "ukv" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUKVUserData($login) {
        $result = array();
        $ukv=new UserstatsUkv();
        $userData=$ukv->getUserDataShort($login);
        $result=(!empty($userData)) ? array('ukvuserdata' => $userData) : array();
       
        return ($result);
    }


    /**
     * Data collector for "agentassigned" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserContrAgent($login) {
        $allAddress = zbs_AddressGetFulladdresslist();
        $userAddress = empty($allAddress) ? array() : $allAddress[$login];
        $agentData = zbs_AgentAssignedGetDataFast($login, $userAddress);
        $agentArray = empty($agentData) ? array() : array('agentdata' => $agentData);

        return ($agentArray);
    }


    /**
     * Data collector for "userdata" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserData($login) {
        $userdata = zbs_UserGetStargazerData($login);
        $alladdress = zbs_AddressGetFulladdresslist();

        if ($this->uscfgAddressStructON) {
            $alladdressStruct = zbs_AddressGetFulladdresslistStruct($login);
        } else {
            $alladdressStruct = array();
        }

        $allrealnames = zbs_UserGetAllRealnames();
        $contract = zbs_UserGetContract($login);
        $contractDate = zbs_UserGetContractDate($login);
        $email = zbs_UserGetEmail($login);
        $mobile = zbs_UserGetMobile($login);
        $phone = zbs_UserGetPhone($login);
        $apiVer = '2';

        $passive = $userdata['Passive'];
        $down = $userdata['Down'];

        //payment id handling
        if ($this->uscfgOpenPayzON) {
            if ($this->uscfgOpenPayzRealIDON) {
                $paymentid = zbs_PaymentIDGet($login);
            } else {
                $paymentid = ip2int($userdata['IP']);
            }
        } else {
            $paymentid = 0;
        }

        if ($userdata['CreditExpire'] != 0) {
            $credexpire = date("d-m-Y", $userdata['CreditExpire']);
        } else {
            $credexpire = 'No';
        }

        if ($userdata['TariffChange']) {
            $tariffNm = $userdata['TariffChange'];
        } else {
            $tariffNm = zbs_TariffLookupDWI($login);
            if (empty($tariffNm)) {
                $tariffNm = 'No';
            }
        }
        $traffdown = 0;
        $traffup = 0;
        $traffdgb = 0;
        $traffugb = 0;

        for ($i = 0; $i <= 9; $i++) {
            $traffdown = $traffdown + $userdata['D' . $i];
            $traffup = $traffup + $userdata['U' . $i];
        }

        $traffdgb = round($traffdown / 1073741824);
        $traffugb = round($traffup / 1073741824);

        if ($traffdgb == 0) {
            $traffdgb = 1;
        }

        if ($traffugb == 0) {
            $traffugb = 1;
        }

        // pasive state check
        if ($passive) {
            $passive_state = 'frozen';
        } else {
            $passive_state = 'active';
        }

        //down state check
        if ($down) {
            $down_state = ' + disabled';
        } else {
            $down_state = '';
        }

        // START OF ONLINELEFT COUNTING <<
        if ($this->uscfgOnlineLeftCountON) {
            // DEFINE VARS:
            $userBalance = $userdata['Cash'];
            if ($userBalance >= 0) {
                $balanceExpire = zbs_GetOnlineLeftCount($login, $userBalance, $userdata['Tariff'], true);
            } else {
                $balanceExpire = 'debt';
            }
        } else {
            $balanceExpire = 'No';
        }
        // >> END OF ONLINELEFT COUNTING

        $reqResult = array();
        $reqResult[] = array('address' => @$alladdress[$login]);

        if ($this->uscfgAddressStructON) {
            if (!empty($alladdressStruct)) {
                foreach ($alladdressStruct[$login] as $field => $value) {
                    $reqResult[] = array($field => $value);
                }
            }
        }

        $tariffData = zbs_UserGetTariffData($userdata['Tariff']);
        $tariffPeriod = isset($tariffData['period']) ? $tariffData['period'] : 'month';
        $vservicesPeriodON = $this->usConfig->getUstasParam('VSERVICES_CONSIDER_PERIODS', 0);
        $includeVServices = $this->usConfig->getUstasParam('ONLINELEFT_CONSIDER_VSERVICES', 0);
        $totalVsrvPrice = ($vservicesPeriodON) ? zbs_vservicesGetUserPricePeriod($login, $tariffPeriod) : zbs_vservicesGetUserPrice($login);
        $payedTillDate = (($balanceExpire !== 'No' and $balanceExpire !== 'debt' and is_numeric($balanceExpire))
                            ? date("d.m.Y", time() + ($balanceExpire * 24 * 60 * 60)) : 'none');

        $reqResult[] = array('realname' => @$allrealnames[$login]);
        $reqResult[] = array('login' => $login);
        $reqResult[] = array('cash' => @round($userdata['Cash'], 2));
        $reqResult[] = array('ip' => @$userdata['IP']);
        $reqResult[] = array('phone' => $phone);
        $reqResult[] = array('mobile' => $mobile);
        $reqResult[] = array('email' => $email);
        $reqResult[] = array('credit' => @$userdata['Credit']);
        $reqResult[] = array('creditexpire' => $credexpire);
        $reqResult[] = array('payid' => strval($paymentid));
        $reqResult[] = array('contract' => $contract);
        $reqResult[] = array('contractdate' => $contractDate);
        $reqResult[] = array('tariff' => $userdata['Tariff']);
        $reqResult[] = array('tariffalias' => __($userdata['Tariff']));
        $reqResult[] = array('tariffnm' => $tariffNm);
        $reqResult[] = array('traffdownload' => zbs_convert_size($traffdown));
        $reqResult[] = array('traffupload' => zbs_convert_size($traffup));
        $reqResult[] = array('trafftotal' => zbs_convert_size($traffdown + $traffup));
        $reqResult[] = array('accountstate' => $passive_state . $down_state);
        $reqResult[] = array('accountexpire' => $balanceExpire);
        $reqResult[] = array('payedtilldate' => $payedTillDate);
        $reqResult[] = array('payedtillvsrvincluded' => $includeVServices);
        $reqResult[] = array('vservicescost' => $totalVsrvPrice);
        $reqResult[] = array('currency' => $this->uscfgCurrency);
        $reqResult[] = array('version' => $apiVer);

        return ($reqResult);
    }


    /**
     * Data collector for "tariffvservices" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserTariffAndVservices($login) {
        $tariffvsrvs = array();
        $userTariff  = zbs_UserGetTariff($login);
        $tariffData  = (!empty($userTariff) ? zbs_UserGetTariffData($userTariff) : array());

        if (!empty($tariffData)) {
            $vsrvsData      = zbs_vservicesGetUsersAll($login, true, true);

            $tariffvsrvs[$tariffData['name']]['tariffname']       = $tariffData['name'];
            $tariffvsrvs[$tariffData['name']]['tariffprice']      = $tariffData['Fee'];
            $tariffvsrvs[$tariffData['name']]['tariffdaysperiod'] = $tariffData['period'];

            if (!empty($vsrvsData)) {
                $vsrvsData = $vsrvsData[$login];

                foreach ($vsrvsData as $eachID => $eachSrv) {
                    $tariffvsrvs[$eachID]['vsrvname']        = $eachSrv['vsrvname'];
                    $tariffvsrvs[$eachID]['vsrvprice']       = $eachSrv['price'];
                    $tariffvsrvs[$eachID]['vsrvdaysperiod']  = $eachSrv['daysperiod'];
                }
            }
        }

        return ($tariffvsrvs);
    }


    /**
     * Data collector for "activetariffsvservices" request
     *
     * @return array
     */
    protected function getAllTariffsVservices() {
        $alltariffsvservices = array();
        $tariffsNoLousy      = zbs_GetTariffsDataAll(true);
        $vservicesNoArchived = zbs_getVservicesAllWithNames(true);

        if (!empty($tariffsNoLousy)) {
            foreach ($tariffsNoLousy as $eachName => $eachRec) {
                $alltariffsvservices[$eachName]['tariffname']       = $eachRec['name'];
                $alltariffsvservices[$eachName]['tariffprice']      = $eachRec['Fee'];
                $alltariffsvservices[$eachName]['tariffdaysperiod'] = $eachRec['period'];
            }
        }

        if (!empty($vservicesNoArchived)) {
            foreach ($vservicesNoArchived as $eachID => $eachSrv) {
                $alltariffsvservices[$eachID]['vsrvname']        = $eachSrv['vsrvname'];
                $alltariffsvservices[$eachID]['vsrvprice']       = $eachSrv['price'];
                $alltariffsvservices[$eachID]['vsrvdaysperiod']  = $eachSrv['charge_period_days'];
            }
        }

        return ($alltariffsvservices);
    }


    /**
     * Returns tariff list the user is allowed to switch to
     *
     * @param $login
     *
     * @return array
     */
    protected function getTariffsToSwitchAllowed($login) {
        $userTariff       = zbs_UserGetTariff($login);
        $tariffsAllowedTo = array();
        $result           = array();

        if (!empty($userTariff)) {
            if ($this->uscfgTariffCahngeMatrix) {
                $tariffsAllowedTo = $this->getTariffMatrixAllowedTo($userTariff);
            } else {
                $tariffsAllowedFrom = explode(',', $this->uscfgTariffCahngeAllowedFrom);

                if (!empty($tariffsAllowedFrom) and in_array($userTariff, $tariffsAllowedFrom)) {
                    $tariffsAllowedTo = explode(',', $this->uscfgTariffCahngeAllowedTo);
                }
            }
        }

        if (!empty($tariffsAllowedTo)) {
            foreach ($tariffsAllowedTo as $io => $item) {
                $result[$io]['tariff'] = $item;
            }
        }

        return ($result);
    }


    /**
     * Data collector for "feecharges" request
     *
     * @param $login
     *
     * @return array
     */
    protected function getUserFeeCharges($login, $date_from = '', $date_to = '') {
        $feeCharges         = array();
        $vservicesLabeled   = zbs_VservicesGetAllNamesLabeled();
        $tmpFees            = zbs_GetUserDBFees($login, $date_from, $date_to);
        $tmpAdditionalFees  = zbs_GetUserAdditionalFees($login, $date_from, $date_to);
        $allFees            = zbs_concatArraysAvoidDuplicateKeys($tmpFees, $tmpAdditionalFees);

        if (!empty($allFees)) {
            ksort($allFees);
        }

        if (!empty($allFees)) {
            foreach ($allFees as $io => $eachFee) {
                $feeCharges[$io]['date']    = $eachFee['date'];
                $feeCharges[$io]['summ']    = $eachFee['summ'];
                $feeCharges[$io]['balance'] = $eachFee['from'];
                $feeCharges[$io]['note']    = ((ispos($eachFee['note'], 'Service:') and !empty($vservicesLabeled[$eachFee['note']]))
                                            ? $vservicesLabeled[$eachFee['note']] : $eachFee['note']);

                if ($eachFee['operation'] == 'Fee') {
                    $feeCharges[$io]['type'] = 'mainsrv';
                } elseif (ispos($eachFee['note'], 'Service:')) {
                    $feeCharges[$io]['type'] = 'virtualsrv';
                } else {
                    $feeCharges[$io]['type'] = 'other';
                }
            }
        }

        return ($feeCharges);
    }

    /**
     * Returns user's current freezing data and status
     *
     * @param $login
     *
     * @return array
     *
     * @throws Exception
     */
    protected function getUserFreezeData($login) {
        $freezeData             = array();
        $userdata               = zbs_UserGetStargazerData($login);
        $userBalance            = $userdata['Cash'];
        $frozenState            = $userdata['Passive'] == '1' ? 'frozen' : 'unfrozen';
        $userTariff             = $userdata['Tariff'];
        $userTariffData         = zbs_UserGetTariffData($userTariff);
        $userTariffFreezePrice  = empty($userTariffData) ? null : $userTariffData['PassiveCost'];

        $weblogsTable = new NyanORM('weblogs');
        $weblogsTable->selectable('date');
        $weblogsTable->whereRaw("`event` = 'PASSIVE CHANGE (" . $login . ") ON `1`'");
        $weblogsTable->orderBy('id', 'DESC');
        $weblogsTable->limit(1);
        $freezeDateFrom = $weblogsTable->getAll();
        $freezeDateFrom = empty($freezeDateFrom[0]) ? '' : $freezeDateFrom[0]['date'];
        $freezeDateTo   = '';
        $FrzDaysAmount           = null;
        $FrzDaysUsed             = null;
        $FrzDaysAvailable        =
        $WrkDaysToRestoreFrzDays = null;
        $DaysWorked              = null;
        $DaysLeftToWork          = null;

        if ($this->uscfgFreezeDaysChargeON) {
            $freezeDaysChargeData = zbs_getFreezeDaysChargeData($login);

            if (!empty($freezeDaysChargeData[0])) {
                $freezeDaysChargeData    = $freezeDaysChargeData[0];

                $FrzDaysAmount           = empty($freezeDaysChargeData['freeze_days_amount']) ? $this->uscfgFreezeDaysInitAmount : $freezeDaysChargeData['freeze_days_amount'];
                $FrzDaysUsed             = $freezeDaysChargeData['freeze_days_used'];
                $FrzDaysAvailable        = $FrzDaysAmount - $FrzDaysUsed;
                $WrkDaysToRestoreFrzDays = empty($freezeDaysChargeData['work_days_restore']) ? $this->uscfgFreezeDaysWorkToRestore : $freezeDaysChargeData['work_days_restore'];
                $DaysWorked              = $freezeDaysChargeData['days_worked'];
                $DaysLeftToWork          = $WrkDaysToRestoreFrzDays - $DaysWorked;
                $freezeDateTo            = (empty($freezeDateFrom) or empty($FrzDaysUsed)) ? '' : date('Y-m-d H:m:s',strtotime($freezeDateFrom . ' +' . $FrzDaysUsed . ' days'));
            }
        }

        $freezeData[] = array(
                        "result"                        => 'Success',
                        "message"                       => '',
                        "freezeSelfAvailable"           => ubRouting::filters($this->uscfgFreezeSelfON, 'fi', FILTER_VALIDATE_BOOLEAN),
                        "activationCost"                => (empty($this->uscfgFreezeSelfPrice) ? null : $this->uscfgFreezeSelfPrice), // якщо немає плати за самозаморозку - повертаємо null
                        "tariffsAllowedList"            => $this->uscfgFreezeSelfTariffsAllowed,
                        "tariffAllowedAny"              => ubRouting::filters($this->uscfgFreezeSelfTariffAllowAny, 'fi', FILTER_VALIDATE_BOOLEAN),
                        "negativeBalanceFreezeAllowed"  => ubRouting::filters($this->uscfgFreezeIfNegativeBalance, 'fi', FILTER_VALIDATE_BOOLEAN),
                        "userBalance"                   => $userBalance,
                        "userTariff"                    => $userTariff,
                        "userTariffFreezePrice"         => $userTariffFreezePrice,
                        "freezeStatus"                  => $frozenState,                // "frozen" or "unfrozen",
                        "dateFrom"                      => $freezeDateFrom,
                        "dateTo"                        => $freezeDateTo,               // empty string("") if freezeDaysChargeActive is false,
                        "freezeDaysChargeActive"        => ubRouting::filters($this->uscfgFreezeDaysChargeON, 'fi', FILTER_VALIDATE_BOOLEAN),
                        "freezeDaysTotal"               => $FrzDaysAmount,              // загальна початкова кількість днів заморожування, доступна для кожного абонента
                        "freezeDaysRestore"             => $WrkDaysToRestoreFrzDays,    // кількість днів, яка має бути фактично відпрацьована(і оплачена) абонентом для того, щоб відновити свій "баланс доступних днів заморозки" після його вичерпання
                        "freezeDaysUsed"                => $FrzDaysUsed,                // кількість використаних днів заморозки з доступного балансу
                        "freezeDaysAvailable"           => $FrzDaysAvailable,           // кількість доступних днів заморозки, що залишилсь на балансі
                        "freezeDaysWorked"              => $DaysWorked,                 // кількість днів, які вже було відпрацьовано(які фактично минули) з моменту вичерпання всіх доступних днів заморозки
                        "freezeDaysLeftToWork"          => $DaysLeftToWork              // кількість днів, які ще треба відпрацювати(які мають бути оплачені і фактично минути) для відновлення балансу доступних для заморозки днів до freezeDaysTotal
                      );

        return ($freezeData);
    }

    /**
     * Makes user frozen
     *
     * @param $login
     *
     * @return array
     */
    protected function doFreeze($login) {
        $result       = array();
        $userdata     = zbs_UserGetStargazerData($login);
        $userTariff   = $userdata['Tariff'];
        $userBalance  = $userdata['Cash'];
        $frozenState  = $userdata['Passive'];

        if ($frozenState != '1') {
            //lets freeze account
            billing_freeze($login);

            //push cash fee anyway
            zbs_PaymentLog($login, '-' . $this->uscfgFreezeSelfPrice, $this->uscfgFreezeSelfPriceCashTypeID, "AFFEE_XMLAGENT");
            billing_addcash($login, '-' . $this->uscfgFreezeSelfPrice);
            log_register('CHANGE Passive (' . $login . ') ON 1');
            log_register('XMLAGENT: REST API is the source of previous action');

            $result[] = array('result' => 'Success', 'message' => 'User \'' . $login . '\' has been frozen');
        } else {
            $result[] = array('result' => 'Failure', 'message' => 'User \'' . $login . '\' is already frozen');
        }

        return ($result);
    }


    /**
     * Makes user UNfrozen
     * Use with EXTREME CARE
     *
     * @param $login
     *
     * @return array
     */
    protected function doUNFreeze($login) {
        $result = array();

        if ($this->selfUnFreezeAllowed) {
            $userdata     = zbs_UserGetStargazerData($login);
            $frozenState  = $userdata['Passive'];

            if ($frozenState == '1') {
                //lets UNfreeze account
                executor('-u' . $login . ' -i 0');
                log_register('CHANGE Passive (' . $login . ') ON 0');
                log_register('XMLAGENT: REST API is the source of previous action');

                $result[] = array('result' => 'Success', 'message' => 'User \'' . $login . '\' has been UNfrozen');
            } else {
                $result[] = array('result' => 'Failure', 'message' => 'User \'' . $login . '\' is already UNfrozen');
            }
        } else {
            $result[] = array('result' => 'Failure', 'message' => 'Feature is not allowed');
        }

        return ($result);
    }

    /**
     * Support request creation routine
     *
     * @param $login
     * @param $tickettext
     * @param $replyID
     *
     * @return array[]
     * @throws Exception
     */
    protected function createSupportTicket($login, $tickettext, $replyID) {
        $ticketID = 0;
        $replyID  = empty($replyID) ? 'NULL' : $replyID;
        $result   = array();
        $ticketDeniedUsers=zbs_GetHelpdeskDeniedAll();

        if (!isset($ticketDeniedUsers[$login])) {
        if (!empty($login) and !empty($tickettext)) {
            $from = mysql_real_escape_string($login);
            $text = mysql_real_escape_string(strip_tags($tickettext));
            $date = curdatetime();

            $ticketDB = new NyanORM('ticketing');
            $ticketDB->dataArr(array(
                                'date'    => $date,                                
                                'status'  => '0',
                                'from'    => $from,
                                'text'    => $text
                               ));
            
            if (!empty($replyID) and $replyID != 'NULL') {
                $ticketDB->data('replyid', $replyID);
            }

            $ticketDB->create();
            $ticketID = $ticketDB->getLastId();
        }

        if (empty($ticketID)) {
            $result = array('ticket' => array('created' => 'error', 'id' => 0));
        } else {
            $result = array('ticket' => array('created' => 'success', 'id' => $ticketID));

            if (empty($replyID) or $replyID == 'NULL') {
                $logEvent = 'TICKET CREATE (' . $from . ') NEW [' . $ticketID . ']';
            } else {
                $logEvent = 'TICKET CREATE (' . $from . ') REPLY TO [' . $replyID . ']';
            }

            log_register($logEvent);
            //log_register('XMLAGENT: REST API is the source of previous action');
        }
    } else {
        $result = array('ticket' => array('created' => 'error', 'id' => 0));
    }

        return ($result);
    }


    /**
     * Sign up request creation routine
     *
     * @param $requestBody
     *
     * @return array[]
     * @throws Exception
     */
    protected function createSignUpRequest($requestBody) {
        $sigreqID = 0;
        $result   = array();

// expected $requestBody JSON structure:
/*
{
    "date": "2024-02-29 19:57:50",
    "state": 0,
    "ip": "app_IP_addr",
    "street": "Some_City Some_Street",
    "build": "111",
    "apt": "222",
    "realname": "FirstName LastName",
    "phone": "0551234567",
    "service": "Internet",
    "notes": "Some important notes here"
}
*/
        if (!empty($requestBody)) {
            $requestBody = json_decode($requestBody);
            $sigreqDB = new NyanORM('sigreq');
            $sigreqDB->dataArr($requestBody);
            $sigreqDB->create();
            $sigreqID = $sigreqDB->getLastId();
        }

        if (empty($sigreqID)) {
            $result = array('signup_request' => array('created' => 'error', 'id' => 0));
        } else {
            $result = array('signup_request' => array('created' => 'success', 'id' => $sigreqID));
            $logEvent = 'SIGNUP REQUEST CREATED WITH ID: ' . $sigreqID;
            log_register($logEvent);
            log_register('XMLAGENT: REST API is the source of previous action');
        }

        return ($result);
    }


    /**
     * Returns the tariffs list which current user tariff might be switched to
     * according to the current TariffMatrix settings
     *
     * Yep, this one, probably, should be somewhere in "tariffchanger" module
     * Well, it probably will, when "tariffchanger" will get a separate Class definition
     *
     * @param $userTariff
     *
     * @return false|string[]
     */
    public static function getTariffMatrixAllowedTo($userTariff) {
        $matrix = parse_ini_file(self::TARIFF_MATRIX_CONFIG_PATH);
        $result = false;

        if (!empty($matrix)) {
            if (isset($matrix[$userTariff])) {
                //extract tariff movement rules
                $result = explode(',', $matrix[$userTariff]);
            } else {
                //no tariff match
                $result = false;
            }
        } else {
            //no matrix entries
            $result = false;
        }

        return ($result);
    }

    /**
     * Writes some debbuggins to log or/and to a local file
     *
     * @param $debugData
     *
     * @return void
     */
    protected function debugLog($restapiMethod = '', $debugData = '') {
        if ($this->debug) {
            $requsterIP     = $_SERVER['REMOTE_ADDR'];
            $requestMethod  = $_SERVER['REQUEST_METHOD'];
            $requestURI     = $_SERVER['REQUEST_URI'];

            log_register('XMLAGENT: [ ' . $restapiMethod . ' ] was called from [ ' . $requsterIP . ' ] via HTTP ' . $requestMethod . ' with params: ' . $requestURI);
        }

        if ($this->debugDeep) {
            if (file_exists(self::DEBUG_FILE_PATH)) {
                file_put_contents(self::DEBUG_FILE_PATH,
                                  "========  START OF DEBUG RECORD  ========" . "\n" .
                                  curdatetime() . '    ' . $restapiMethod . "\n" .
                                  "Debug data:\n" . $debugData . "\n" .
                                  "********  GLOBAL ARRAYS  ********\n" .
                                  "SERVER:\n" . print_r($_SERVER, true) . "\n\n" .
                                  "RQUEST:\n" . print_r($_REQUEST, true) . "\n" .
                                  "========  END OF DEBUG RECORD  ========" . "\n\n\n\n",
                                  FILE_APPEND
                );
            } else {
                log_register('XMLAGENT: trying to use "deep" debugging, but no "exports" DIR found where it should be');
            }
        }
    }
}
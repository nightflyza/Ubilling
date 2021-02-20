<?php

/**
 * Logs viewing and searching basic class
 */
class EventView {

    /**
     * System messages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Default events limit to display
     *
     * @var int
     */
    protected $eventLimit = 100;

    /**
     * Contains current instance administrator login filter
     *
     * @var string
     */
    protected $filterAdmin = '';

    /**
     * Contains current instance event text filter
     *
     * @var string
     */
    protected $filterEventText = '';

    /**
     * Contains current instance date filter
     *
     * @var string
     */
    protected $filterDate = '';

    /**
     * weblogs table database abstraction layer
     *
     * @var object
     */
    protected $weblogsDb = '';

    /**
     * Contains available render limits presets
     *
     * @var array
     */
    protected $renderLimits = array();

    /**
     * Highlight user profiles flag
     *
     * @var bool
     */
    protected $profileLinksFlag = false;

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Database stats caching timeout in seconds
     *
     * @var int
     */
    protected $cacheTimeout = 3600;

    /**
     * Zen-mode refresh timeout in milliseconds
     *
     * @var int
     */
    protected $zenTimeout = 3000;

    /**
     * Predefined tables,routes, URLs, etc...
     */
    const TABLE_DATASOURCE = 'weblogs';
    const TABLE_USERREG = 'userreg';
    const CACHE_KEY = 'EVENTVIEWSTATS';
    const URL_ME = '?module=eventview';
    const ROUTE_LIMIT = 'onpage';
    const ROUTE_STATS = 'eventstats';
    const ROUTE_ZEN = 'zenmode';
    const ROUTE_AJAXZEN = 'aj';
    const ROUTE_DROPCACHE = 'forcecache';
    const PROUTE_FILTERADMIN = 'eventadmin';
    const PROUTE_FILTEREVENTTEXT = 'eventsearch';
    const PROUTE_FILTERDATE = 'eventdate';
    const PROUTE_PROFILELINKS = 'profilelinks';

//                       .-.
//                      |_:_|
//                     /(_Y_)\
//.                   ( \/M\/ )
// '.               _.'-/'-'\-'._
//   ':           _/.--'[[[[]'--.\_
//     ':        /_'  : |::"| :  '.\
//       ':     //   ./ |oUU| \.'  :\
//         ':  _:'..' \_|___|_/ :   :|
//           ':.  .'  |_[___]_|  :.':\
//            [::\ |  :  | |  :   ; : \
//             '-'   \/'.| |.' \  .;.' |
//             |\_    \  '-'   :       |
//             |  \    \ .:    :   |   |
//             |   \    | '.   :    \  |
//             /       \   :. .;       |
//            /     |   |  :__/     :  \\
//           |  |   |    \:   | \   |   ||
//          /    \  : :  |:   /  |__|   /|
//          |     : : :_/_|  /'._\  '--|_\
//          /___.-/_|-'   \  \
//                         '-'

    /**
     * Creates new EventView instance
     */
    public function __construct() {
        $this->initMessages();
        $this->initCache();
        $this->setRenderLimits();
        $this->setLimit();
        $this->setFilterDate();
        $this->setFilterAdmin();
        $this->setFilterEventText();
        $this->setProfileLinks();
        $this->initDatabase();
    }

    /**
     * Inits system messages helper
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits system cahe instance for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Sets events render limit if required
     * 
     * @return void
     */
    protected function setLimit() {
        $eventLimitRaw = ubRouting::get(self::ROUTE_LIMIT, 'int') ? ubRouting::get(self::ROUTE_LIMIT, 'int') : 100;
        //prevent memory overusage
        if (isset($this->renderLimits[$eventLimitRaw])) {
            $this->eventLimit = $eventLimitRaw;
        }
    }

    /**
     * Sets current instance administrator filter if required
     * 
     * @return void
     */
    protected function setFilterAdmin() {
        $this->filterAdmin = ubRouting::post(self::PROUTE_FILTERADMIN, 'mres') ? ubRouting::post(self::PROUTE_FILTERADMIN, 'mres') : '';
    }

    /**
     * Sets current instance event text filter if required
     * 
     * @return void
     */
    protected function setFilterEventText() {
        $this->filterEventText = ubRouting::post(self::PROUTE_FILTEREVENTTEXT, 'mres') ? ubRouting::post(self::PROUTE_FILTEREVENTTEXT, 'mres') : '';
    }

    /**
     * Sets current instance date filter if required
     * 
     * @return void
     */
    protected function setFilterDate() {
        $rawDate = ubRouting::post(self::PROUTE_FILTERDATE, 'mres') ? ubRouting::post(self::PROUTE_FILTERDATE, 'mres') : '';
        if (!empty($rawDate)) {
            if (zb_checkDate($rawDate)) {
                $this->filterDate = $rawDate;
            }
        }
    }

    /**
     * Sets possible render limits values
     * 
     * @return void
     */
    protected function setRenderLimits() {
        $this->renderLimits = array(
            50 => 50,
            100 => 100,
            200 => 200,
            500 => 500,
            800 => 800,
            1000 => 1000);
    }

    /**
     * Profile links highlight flag setup
     * 
     * @return void
     */
    protected function setProfileLinks() {
        if (ubRouting::checkPost(self::PROUTE_PROFILELINKS)) {
            $this->profileLinksFlag = true;
        }
    }

    /**
     * Inits weblogs database abstraction layer
     * 
     * @return void
     */
    protected function initDatabase() {
        $this->weblogsDb = new NyanORM(self::TABLE_DATASOURCE);
    }

    /**
     * Renders available event limits switching controls
     * 
     * @return string
     */
    protected function renderEventLimits() {
        $result = '';
        if (!empty($this->renderLimits)) {
            $result .= __('On page') . ': ';
            foreach ($this->renderLimits as $io => $each) {
                $hs = '';
                $he = '';
                if ($each == $this->eventLimit) {
                    $hs = wf_tag('b');
                    $he = wf_tag('b', true);
                }
                $result .= $hs . wf_Link(self::URL_ME . '&' . self::ROUTE_LIMIT . '=' . $each, $each, false) . $he . ' ';
            }
        }
        return($result);
    }

    /**
     * Preloads all events from database, applying all of required filters
     * 
     * @return array
     */
    protected function getAllEventsFiltered() {
        $result = array();
        $this->weblogsDb->orderBy('id', 'DESC'); //from newest to oldest
        //
            //date filters ignores default render limits
        if (!empty($this->filterDate)) {
            $this->eventLimit = 0; //show all of events by selected date
            $this->weblogsDb->where('date', 'LIKE', $this->filterDate . '%');
        }
        //apply administrator filter
        if (!empty($this->filterAdmin)) {
            $this->weblogsDb->where('admin', '=', $this->filterAdmin);
        }

        //apply event-text filter
        if (!empty($this->filterEventText)) {
            $this->weblogsDb->where('event', 'LIKE', '%' . $this->filterEventText . '%');
        }

        //setting query limits
        if (!empty($this->eventLimit)) {
            $this->weblogsDb->limit($this->eventLimit);
        }

        //getting events from database
        $result = $this->weblogsDb->getAll();

        return($result);
    }

    /**
     * Returns selector of administrator logins
     *
     * @return string
     */
    protected function adminSelector() {
        $all = rcms_scandir(USERS_PATH);
        $allLogins = array('' => '-');
        if (!empty($all)) {
            foreach ($all as $each) {
                $allLogins[$each] = $each;
            }
        }

        $allLogins['external'] = 'external';
        $allLogins['guest'] = 'guest';

        $result = wf_Selector(self::PROUTE_FILTERADMIN, $allLogins, __('Administrator'), $this->filterAdmin, false);
        return ($result);
    }

    /**
     * Renders form for setting event filters
     * 
     * @return string
     */
    protected function renderSearchForm() {
        $result = '';
        $inputs = __('By date') . ': ';
        $inputs .= wf_DatePickerPreset(self::PROUTE_FILTERDATE, $this->filterDate, true) . ' '; //date filter
        $inputs .= $this->adminSelector() . ' '; //administrator filter
        $inputs .= wf_CheckInput(self::PROUTE_PROFILELINKS, __('Highlight profiles'), false, $this->profileLinksFlag) . ' '; // profile links checkbox
        $inputs .= wf_TextInput(self::PROUTE_FILTEREVENTTEXT, __('Event'), $this->filterEventText, false, 30) . ' '; //event text mask
        $inputs .= wf_Submit(__('Search'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Renders weblogs search results
     * 
     * @return string
     */
    public function renderEventsReport() {
        $result = '';
        $zenMode = ubRouting::checkGet(self::ROUTE_ZEN) ? true : false;

        if (!$zenMode) {
            $result .= $this->renderEventLimits();
            $result .= wf_delimiter(0);
            $result .= $this->renderSearchForm();
        } else {
            $this->eventLimit = 50;
        }

        $allEvents = $this->getAllEventsFiltered();


        if (!empty($allEvents)) {
            $tablecells = wf_TableCell(__('ID'));
            $tablecells .= wf_TableCell(__('Date'));
            $tablecells .= wf_TableCell(__('Admin'));
            $tablecells .= wf_TableCell(__('IP'));
            $tablecells .= wf_TableCell(__('Event'));
            $tablerows = wf_TableRow($tablecells, 'row1');


            foreach ($allEvents as $io => $eachevent) {
                $event = htmlspecialchars($eachevent['event']);
                if ($this->profileLinksFlag) {
                    if (preg_match('!\((.*?)\)!si', $event, $tmpLoginMatches)) {
                        @$loginExtracted = $tmpLoginMatches[1];
                        if (!empty($loginExtracted)) {
                            if (!ispos($event, '((')) { // ignore UKV user id-s 
                                $userProfileLink = wf_Link('?module=userprofile&username=' . $loginExtracted, web_profile_icon() . ' ' . $loginExtracted);
                                $event = str_replace($loginExtracted, $userProfileLink, $event);
                            }
                        }
                    }
                }
                $tablecells = wf_TableCell($eachevent['id']);
                $tablecells .= wf_TableCell($eachevent['date']);
                $tablecells .= wf_TableCell($eachevent['admin']);
                $tablecells .= wf_TableCell($eachevent['ip']);
                $tablecells .= wf_TableCell($event);
                $tablerows .= wf_TableRow($tablecells, 'row5');
            }

            $result .= wf_TableBody($tablerows, '100%', 0, 'sortable');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }

        return($result);
    }

    /**
     * Renders module controls panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/log_icon_small.png', __('Events')) . ' ' . __('Events'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_STATS . '=true', web_icon_charts() . ' ' . __('Stats'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ZEN . '=true', wf_img('skins/zen.png', __('Zen')) . ' ' . __('Zen'), false, 'ubButton') . ' ';
        return($result);
    }

    /**
     * Returns database stats and performs it caching
     * 
     * @return array
     */
    protected function getEventStats() {
        $result = array();
        $current_monthlog = "logs_" . date("m") . "_" . date("Y") . "";

        //is current month logs table exists?
        if (zb_CheckTableExists($current_monthlog)) {
            $cmonth = date("Y-m-");
            $cday = date("d");

            //force cache cleanup
            if (ubRouting::checkGet(self::ROUTE_DROPCACHE)) {
                $this->cache->delete(self::CACHE_KEY);
                ubRouting::nav(self::URL_ME . '&' . self::ROUTE_STATS . '=true');
            }

            $cachedStats = $this->cache->get(self::CACHE_KEY, $this->cacheTimeout);
            //is cache expired?
            if (empty($cachedStats)) {
                $rawData = array();
                /**
                 * Using direct MySQL queries here instead of NyanORM - due memory economy purposes and preventing
                 * of multiple arrays reordering overheads. And laziness of course :P
                 */
                $reg_q = "SELECT COUNT(`id`) from `userreg` WHERE `date` LIKE '" . $cmonth . "%'";
                $regc = simple_query($reg_q);
                $regc = $regc['COUNT(`id`)'];

                $mac_q = "SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '" . $cmonth . "%' AND `event` LIKE 'CHANGE MultiNetHostMac%'";
                $macc = simple_query($mac_q);
                $macc = $macc['COUNT(`id`)'];

                $events_q = "SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '" . $cmonth . "%'";
                $eventsc = simple_query($events_q);
                $eventsc = $eventsc['COUNT(`id`)'];

                $switch_q = "SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '" . $cmonth . "%' AND `event` LIKE 'SWITCH ADD%'";
                $switchc = simple_query($switch_q);
                $switchc = $switchc['COUNT(`id`)'];

                $credit_q = "SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '" . $cmonth . "%' AND `event` LIKE 'CHANGE Credit%' AND `event` NOT LIKE '%CreditExpire%'";
                $creditc = simple_query($credit_q);
                $creditc = $creditc['COUNT(`id`)'];

                $pay_q = "SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '" . $cmonth . "%' AND `summ`>0";
                $payc = simple_query($pay_q);
                $payc = $payc['COUNT(`id`)'];

                $tarch_q = "SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '" . $cmonth . "%' AND `event` LIKE 'CHANGE TariffNM%'";
                $tarchc = simple_query($tarch_q);
                $tarchc = $tarchc['COUNT(`id`)'];

                $stg_q = "SELECT COUNT(`unid`) from `logs_" . date("m") . "_" . date("Y") . "`";
                $stgc = simple_query($stg_q);
                $stgc = $stgc['COUNT(`unid`)'];

                $sms_q = "SELECT COUNT(`id`) from`weblogs` WHERE `date` LIKE '" . $cmonth . "%' AND `event` LIKE 'USMS SEND SMS %'";
                $smsc = simple_query($sms_q);
                $smsc = $smsc['COUNT(`id`)'];

                $rawData['regc'] = $regc;
                $rawData['macc'] = $macc;
                $rawData['eventsc'] = $eventsc;
                $rawData['switchc'] = $switchc;
                $rawData['creditc'] = $creditc;
                $rawData['payc'] = $payc;
                $rawData['tarchc'] = $tarchc;
                $rawData['stgc'] = $stgc;
                $rawData['smsc'] = $smsc;
                //put new data to cache
                $result = $rawData;
                $this->cache->set(self::CACHE_KEY, $rawData, $this->cacheTimeout);
            } else {
                //just returning cached data
                $result = $cachedStats;
            }
        }

        return($result);
    }

    /**
     * Renders event stats by current month
     * 
     * @return string
     */
    public function renderEventStats() {
        $result = '';
        $eventStats = $this->getEventStats();
        if (!empty($eventStats)) {
            $cmonth = date("Y-m-");
            $cday = date("d");
            // workdays fix
            $weeks = ($cday / 7);
            $weeks = intval($weeks);

            if ($weeks >= 1) {
                $cday = $cday - (2 * $weeks);
            }

            $cells = wf_TableCell(__('What done') . '?');
            $cells .= wf_TableCell(__('Current month'));
            $cells .= wf_TableCell(__('Average per day'));
            $rows = wf_TableRow($cells, 'row1');

            $cells = wf_TableCell(__('Current month signups'));
            $cells .= wf_TableCell($eventStats['regc']);
            $cells .= wf_TableCell(round($eventStats['regc'] / $cday, 2));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('MAC changes'));
            $cells .= wf_TableCell(($eventStats['macc'] - $eventStats['regc']));
            $cells .= wf_TableCell(round((($eventStats['macc'] - $eventStats['regc']) / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Switches added'));
            $cells .= wf_TableCell(($eventStats['switchc']));
            $cells .= wf_TableCell(round(($eventStats['switchc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Credits set'));
            $cells .= wf_TableCell($eventStats['creditc']);
            $cells .= wf_TableCell(round(($eventStats['creditc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('Payments processed'));
            $cells .= wf_TableCell($eventStats['payc']);
            $cells .= wf_TableCell(round(($eventStats['payc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Planned changes to tariffs'));
            $cells .= wf_TableCell($eventStats['tarchc']);
            $cells .= wf_TableCell(round(($eventStats['tarchc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('SMS sended'));
            $cells .= wf_TableCell($eventStats['smsc']);
            $cells .= wf_TableCell(round(($eventStats['smsc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');


            $cells = wf_TableCell(__('External billing events'));
            $cells .= wf_TableCell($eventStats['eventsc']);
            $cells .= wf_TableCell(round(($eventStats['eventsc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');

            $cells = wf_TableCell(__('Internal billing events'));
            $cells .= wf_TableCell($eventStats['stgc']);
            $cells .= wf_TableCell(round(($eventStats['stgc'] / $cday), 2));
            $rows .= wf_TableRow($cells, 'row3');

            $result = wf_TableBody($rows, '100%', 0);
            $cacheCleanRoute = self::URL_ME . '&' . self::ROUTE_STATS . '=true' . '&' . self::ROUTE_DROPCACHE . '=true';
            $result .= __('From cache') . ' ' . wf_Link($cacheCleanRoute, wf_img('skins/icon_cleanup.png', __('Renew')));
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }

        return($result);
    }

    /**
     * Renders zen container
     * 
     * @return string
     */
    public function renderZenContainer() {
        $result = '';
        $container = 'zencontainer' . wf_InputId();
        $result .= wf_AjaxLoader();
        $result .= wf_AjaxContainer($container, '', $this->renderEventsReport());
        $dataUrl = self::URL_ME . '&' . self::ROUTE_ZEN . '=true' . '&' . self::ROUTE_AJAXZEN . '=true';
        $result .= wf_tag('script');
        $result .= '$(document).ready(function() {
                        setInterval(function(){ 
                            $.get("' . $dataUrl . '", function(data) {
                                $("#' . $container . '").html(data);
                        });
                    }, ' . $this->zenTimeout . ');
                });
                ';

        $result .= wf_tag('script', true);
        return($result);
    }

    /**
     * Render Zen-mode background results
     * 
     * @return void
     */
    public function renderZenAjData() {
        $result = $this->renderEventsReport();
        die($result);
    }

}

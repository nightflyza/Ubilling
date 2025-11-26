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
     * Contains current instance ip filter
     *
     * @var string
     */
    protected $filterIp = '';

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
     * Predefined tables,routes, URLs, etc...
     */
    const TABLE_DATASOURCE = 'weblogs';
    const URL_ME = '?module=eventview';
    const ROUTE_LIMIT = 'onpage';
    const ROUTE_ZEN = 'zenmode';
    const ROUTE_ZENPROFILES = 'zenprofiles';
    const PROUTE_FILTERADMIN = 'eventadmin';
    const PROUTE_FILTEREVENTTEXT = 'eventsearch';
    const PROUTE_FILTERDATE = 'eventdate';
    const PROUTE_FILTERIP = 'eventip';
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
        $this->setRenderLimits();
        $this->setLimit();
        $this->setFilterDate();
        $this->setFilterAdmin();
        $this->setFilterIp();
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
     * Sets current instance ip filter if required
     * 
     * @return void
     */
    protected function setFilterIp() {
        $this->filterIp = ubRouting::post(self::PROUTE_FILTERIP, 'mres') ? ubRouting::post(self::PROUTE_FILTERIP, 'mres') : '';
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
            1000 => 1000
        );
    }

    /**
     * Profile links highlight flag setup
     * 
     * @return void
     */
    protected function setProfileLinks() {
        if (ubRouting::checkPost(self::PROUTE_PROFILELINKS) or ubRouting::checkGet(self::ROUTE_ZENPROFILES)) {
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
        return ($result);
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

        //apply ip filter
        if (!empty($this->filterIp)) {
            $this->weblogsDb->where('ip', 'LIKE', '%' . $this->filterIp . '%');
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

        return ($result);
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
        $inputs .= wf_TextInput(self::PROUTE_FILTERIP, __('IP'), $this->filterIp, false, '15', 'ip') . ' ';
        $inputs .= wf_CheckInput(self::PROUTE_PROFILELINKS, __('Highlight profiles'), false, $this->profileLinksFlag) . ' '; // profile links checkbox
        $inputs .= wf_TextInput(self::PROUTE_FILTEREVENTTEXT, __('Event'), $this->filterEventText, false, 30) . ' '; //event text mask
        $inputs .= wf_Submit(__('Search'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders weblogs search results
     * 
     * @return string
     */
    public function renderEventsReport() {
        $result = '';
        $zenMode = ubRouting::checkGet(self::ROUTE_ZEN) ? true : false;

        if ($zenMode) {
            $this->eventLimit = 50;
        } else {
            $result .= $this->renderEventLimits();
            $result .= wf_delimiter(0);
            $result .= $this->renderSearchForm();
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
                            if (!ispos($event, '((') and !ispos($event, 'SWITCH')) { // ignore UKV user id-s and switch locations
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

        return ($result);
    }

    /**
     * Renders module controls panel
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        $result .= wf_Link(self::URL_ME, wf_img('skins/log_icon_small.png', __('Events')) . ' ' . __('Events'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ZEN . '=true', wf_img('skins/zen.png', __('Zen')) . ' ' . __('Zen'), false, 'ubButton') . ' ';
        return ($result);
    }

}

<?php

/**
 * TurboUsersList class provides high-performance user list server-side rendering and filtering functionality
 */
class TurboUsersList {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Default on-page users count
     *
     * @var int
     */
    protected $onPage = 50;

    /**
     * Contains count of users available
     *
     * @var int
     */
    protected $totalUsersCount = 0;

    /**
     * Contains filtered users count
     *
     * @var int
     */
    protected $filteredUsersCount = 0;

    /**
     * Contains all DN true-online users
     *
     * @var array
     */
    protected $allDnUsers = array();

    /**
     * Contains preloaded users data
     *
     * @var array
     */
    protected $wholeUsers = array();


    /**
     * Contains finances column rendering flag
     *
     * @var bool
     */
    protected $financesFlag = false;


    //some predefined stuff
    const URL_ME = '?module=online';


    /**
     * Creates new TurboUsersList instance and loads configuration
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
    }

    /**
     * Loads system configuration and sets feature flags
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->financesFlag = ($this->altCfg['FAST_CASH_LINK']) ? true : false;
    }

    /**
     * Returns custom CSS styling for the users list table
     * 
     * @return string 
     */
    protected function getCustomStyling() {
        $customStyling = wf_tag('style', false);
        $customStyling .= '
            .dataTable tr {
            height: 33px; !important;
            min-height: 33px; !important;
            }

            .dataTable td th {
            vertical-align: middle; !important;
            }

            .dataTable img {
            vertical-align: middle; !important;
            width:15px; !important;
            display: inline; !important;
            float:left; !important;
            }
            ';
        $customStyling .= wf_tag('style', true);
        return ($customStyling);
    }

    /**
     * Loads all DN (true-online) users into protected property
     * 
     * @return void
     */
    protected function loadAllDnUsers() {
        $this->allDnUsers = rcms_scandir(DATA_PATH . '/dn/');
        if (!empty($this->allDnUsers)) {
            $this->allDnUsers = array_flip($this->allDnUsers);
        }
    }

    /**
     * Renders the main users list interface with DataTables integration
     * 
     * @return string
     */
    public function renderUsersListContainer() {
        $filtercustomer = '';
        $opts = '"order": [[ 0, "asc" ]]';

        $columns = array();
        $columns[] = __('Full address');
        $columns[] = __('Real Name');
        $columns[] = __('IP');
        $columns[] = __('Tariff');
        $columns[] = __('Active');
        $columns[] = __('Online');
        $columns[] = __('Traffic');
        $columns[] = __('Balance');
        $columns[] = __('Credit');

        $filtercustomer = '';
        if (ubRouting::checkGet('searchquery')) {
            $filtercustomer = '&filtercustomer=' . ubRouting::get('searchquery');
        }

        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajax=true' . $filtercustomer, false, __('Users'), $this->onPage, $opts, false, '', '', true);
        $result .= $this->getCustomStyling();
        return ($result);
    }

    /**
     * Returns HTML label indicating user activity status
     * 
     * @param array $userData User data array
     * @return string 
     */
    protected function getActivityLabel($userData) {
        $result = web_bool_led(false) . ' ' . __('No');
        if (!empty($userData)) {
            if ($userData['Passive'] == 1 or $userData['Down'] == 1) {
                $result = web_yellow_led() . ' ' . __('No');
            } else {
                if ($userData['Cash'] >= '-' . $userData['Credit']) {
                    $result = web_bool_led(true) . ' ' . __('Yes');
                }
            }
        }
        return ($result);
    }

    /**
     * Returns HTML controls (links) for user actions
     * 
     * @param string $userLogin User login
     * @return string 
     */
    protected function getUserControls($userLogin) {
        $result = '';
        $result .= wf_Link('?module=traffstats&username=' . $userLogin, wf_img('skins/icon_stats_16.gif', __('Stats'))) . ' ';
        $result .= wf_Link('?module=userprofile&username=' . $userLogin, wf_img('skins/icon_user_16.gif', __('Profile'))) . ' ';
        if ($this->financesFlag) {
            $result .= wf_Link('?module=addcash&username=' . $userLogin . '#cashfield', wf_img('skins/icon_dollar_16.gif', __('Money')));
        }

        return ($result);
    }

    /**
     * Returns HTML label indicating user DN (true-online) status
     * 
     * @param string $userLogin User login
     * 
     * @return string
     */
    protected function getUserDnLabel($userLogin) {
        $result = web_bool_star(false) . ' ' . __('No');
        if (isset($this->allDnUsers[$userLogin])) {
            $result = web_bool_star(true) . ' ' . __('Yes');
        }
        return ($result);
    }

    /**
     * Performs users filtering, ordering and load for ajax list
     * 
     * @param string $filtercustomer Optional customer filter
     * 
     * @return void
     */
    public function usersLoader($filtercustomer = '') {
        $filtercustomer = ubRouting::filters($filtercustomer, 'mres');

        $this->onPage = (ubRouting::checkGet('iDisplayLength')) ? ubRouting::get('iDisplayLength') : $this->onPage;
        $this->totalUsersCount = simple_query("SELECT COUNT(*) from `users`");
        $this->totalUsersCount = $this->totalUsersCount['COUNT(*)'];

        $sortField = 'fulladdress';
        $sortDir = 'desc';
        if (ubRouting::checkGet('iSortCol_0', false)) {
            $sortingColumn = ubRouting::get('iSortCol_0', 'int');
            $sortDir = ubRouting::get('sSortDir_0', 'gigasafe');

            switch ($sortingColumn) {
                case 0:
                    $sortField = 'fulladdress';
                    break;
                case 1:
                    $sortField = 'realname';
                    break;
                case 2:
                    $sortField = 'ip';
                    break;
                case 3:
                    $sortField = 'Tariff';
                    break;
                case 6:
                    $sortField = 'totaltraff';
                    break;
                case 7:
                    $sortField = 'Cash';
                    break;
                case 8:
                    $sortField = 'Credit';
                    break;
            }
        }

        $offset = 0;
        if (ubRouting::checkGet('iDisplayStart')) {
            $offset = ubRouting::get('iDisplayStart', 'int');
        }

        //optional live search
        $searchQuery = ubRouting::get('sSearch', 'mres');

        $this->wholeUsers = zb_UserGetDataFiltered($searchQuery, $sortField, $sortDir, $offset, $this->onPage);

        //optional live search happens
        if ($searchQuery) {
            $this->filteredUsersCount = sizeof($this->wholeUsers);
        } else {
            $this->filteredUsersCount = $this->totalUsersCount;
        }
    }


    /**
     * Generates JSON response for DataTables with filtered user data
     * 
     * @param string $filtercustomer Optional customer filter
     * 
     * @return void
     */
    public function jsonUserList($filtercustomer = '') {
        $this->usersLoader($filtercustomer);
        $this->loadAllDnUsers();
        $json = new wf_JqDtHelper(true);
        $json->setTotalRowsCount($this->totalUsersCount);
        $json->setFilteredRowsCount($this->filteredUsersCount);
        if (!empty($this->wholeUsers)) {
            foreach ($this->wholeUsers as $io => $each) {
                $data[] = $this->getUserControls($each['login']) . ' ' . $each['fulladdress'];
                $data[] = $each['realname'];
                $data[] = $each['ip'];
                $data[] = $each['Tariff'];
                $data[] = $this->getActivityLabel($each);
                $data[] = $this->getUserDnLabel($each['login']);
                $data[] = $each['totaltraff'];
                $data[] = $each['Cash'];
                $data[] = $each['Credit'];
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }
}

<?php

/**
 * Debtors archive implementation
 */
class DebtrsArch {

    /**
     * Contains all users data as login=>usersData
     * 
     * @var array
     */
    protected $allUsersData=array();

    /**
     * Contains instance of database abstraction layer
     * 
     * @var object
     */
    protected $debtrsDb='';

    /**
     * Contains debtors as login=>state
     *
     * @var array
     */
    protected $currentDebtors=array();

    /**
     * System messages helper instance
     * 
     * @var object
     */
    protected $messages='';

    /**
     * Default DataTables options for users list
     * 
     * @var string
     */
    protected $dtUsOpts='order: [[ 0, "asc" ]], dom: \'<"F"lfB>rti<"F"ps>\',  buttons: [\'csv\', \'excel\', \'pdf\', \'print\']';


    /**
     * Some predefined stuff
     */
    const TABLE_ARCH='debtrsarch';
    const URL_ME='?module=debtrsarch';
    const ROUTE_ARCH='listpoints';
    const ROUTE_TIMEPOINT='rendertimepoint';
    const ROUTE_DIFF='diffpoints';
    const PROUTE_DIFF_ONE='diffone';
    const PROUTE_DIFF_TWO='difftwo';
    const PROUTE_NOFROZEN='excludefrozen';
    

    /**
     * You see no pain in my eyes
     * That is just my clever disguise
     * I will not cry to you
     * You will not see the truth
    */
    public function __construct() {
        $this->initMessages();
        $this->initDb();
    }

    /**
     * Inits database abstraction layer for further usage
     * 
     * @return void
     */
    protected function initDb() {
        $this->debtrsDb = new NyanORM(self::TABLE_ARCH);
    }

    /**
     * Inits system messages helper instance for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all users data from database into protected prop for further usage
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUsersData = zb_UserGetAllData();
    }
  
    /**
     * Loads current debtors from database into protected prop for further usage
     * 
     * @return void
     */
    protected function loadCurrentDebtors() {
        $this->loadUserData();
        if (!empty($this->allUsersData)) {
            foreach ($this->allUsersData as $eachLogin => $eachUserData) {
                $userState = zb_UserIsAlive($eachUserData);
                if ($userState != 1) {
                    $this->currentDebtors[$eachLogin] = $userState;
                }
            }
        }
    }

    /**
     * Stores current debtors into database
     * 
     * @return void
     */
    public function storeCurrentDebtors() {
        $this->loadCurrentDebtors();
        if (!empty($this->currentDebtors)) {
        $curDate = curdatetime();
        $curCount = sizeof($this->currentDebtors);
        $this->debtrsDb->data('date', $curDate);
        $this->debtrsDb->data('debtors', json_encode($this->currentDebtors));
        $this->debtrsDb->data('count', $curCount);
        $this->debtrsDb->create();
      }
    }


    /**
     * Returns array of all archive time points
     * 
     * @return array of dates
     */
    public function getArchiveTimePoints() {
        $result = array();
        $this->debtrsDb->orderBy('date', 'DESC');
        $this->debtrsDb->selectable('id,date,count');
        $result=$this->debtrsDb->getAll('id');
        $this->debtrsDb->selectable(); // flush selectable set
        return ($result);
    }


    /**
     * Renders archive container with time points list
     * 
     * @return string
     */
    public function renderArchive() {
        $result = '';
        $archiveTimePoints = $this->getArchiveTimePoints();
        $dataArr = array();
        if (!empty($archiveTimePoints)) {
            foreach ($archiveTimePoints as $eachTimePoint) {
                $timePointLink = wf_Link(self::URL_ME . '&' . self::ROUTE_TIMEPOINT . '=' . $eachTimePoint['id'], $eachTimePoint['date']);
                $dataArr[] = array($timePointLink, $eachTimePoint['count']);
            }
        }

        $columns = array(__('Date'), __('Count'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtEmbed($columns, $dataArr, false, __('records'), 100, $opts);
        return ($result);
    }


    /**
     * Returns time point data by its ID from database
     * 
     * @param int $id
     * 
     * @return array
     */
    public function getTimePointData($id) {
        $id = ubRouting::filters($id, 'int');
        $result = array();
        $this->debtrsDb->where('id', '=', $id);
        $rawResult=$this->debtrsDb->getAll();
        if (!empty($rawResult)) {
            $recordData = $rawResult[0];
            $result['date'] = $recordData['date'];
            $result['count'] = $recordData['count'];
            $decodedDebtors = json_decode($recordData['debtors'], true);
            if (is_array($decodedDebtors)) {
                $result['debtors'] = $decodedDebtors;
            } else {
                $result['debtors'] = array();
            }
        }
        return ($result);
    }


    /**
     * Renders time point data report
     *
     * @param int $id
     * 
     * @return string
     */
    public function renderTimePoint($id) {
        $result = '';
        $id = ubRouting::filters($id, 'int');
        $timePointData = $this->getTimePointData($id);
        if (!empty($timePointData)) {
            $timePointUsers=$timePointData['debtors'];
            $usersArray=array();
            if (!empty($timePointUsers)) {
                foreach ($timePointUsers as $eachLogin => $eachState) {
                    $usersArray[] = $eachLogin;
                }
            }
            
            $result.=web_UserArrayShower($usersArray, array(), true, $this->dtUsOpts);
            
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'). ' [' . $id . ']', 'error');
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
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_ARCH . '=true', wf_img('skins/icon_search.png') . ' ' . __('Debtors archive'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME . '&' . self::ROUTE_DIFF . '=true', wf_img('skins/diff_icon.png') . ' ' . __('Compare'), false, 'ubButton') . ' ';
        return ($result);
    }


    /**
     * Renders compare form by two time points
     * 
     * @return string
     */
    public function renderDiffForm() {
        $result = '';
        $archiveTimePoints = $this->getArchiveTimePoints();
        if (!empty($archiveTimePoints)) {
            $pointsTmp = array();
            foreach ($archiveTimePoints as $eachTimePoint) {
                $pointsTmp[$eachTimePoint['id']] = $eachTimePoint['date'] . ' - ' . $eachTimePoint['count'] . ' ' . __('users');
            }

            $currDiffOne = (ubRouting::checkPost(self::PROUTE_DIFF_ONE)) ? ubRouting::post(self::PROUTE_DIFF_ONE, 'int') : '';
            $currDiffTwo = (ubRouting::checkPost(self::PROUTE_DIFF_TWO)) ? ubRouting::post(self::PROUTE_DIFF_TWO, 'int') : '';
            $currNoFrozen = (ubRouting::checkPost(self::PROUTE_NOFROZEN)) ? true : false;

            $inputs = __('Compare') . ' ';
            $inputs .= wf_SelectorSearchable(self::PROUTE_DIFF_ONE, $pointsTmp, '', $currDiffOne, false) . ' ';
            $inputs .= __('and') . ' ';
            $inputs .= wf_SelectorSearchable(self::PROUTE_DIFF_TWO, $pointsTmp, '', $currDiffTwo, false) . ' ';
            $inputs .= wf_CheckInput(self::PROUTE_NOFROZEN, __('Ignore frozen'), false, $currNoFrozen);
            $inputs .= wf_Submit(__('Compare'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to compare'), 'info');
        }
        return ($result);
    }

    /**
     * Compares two time points and displays diff results
     * 
     * @param int $idOne
     * @param int $idTwo
     * @param bool $noFrozen
     * 
     * @return string
     */
    public function compareTimePoints($idOne, $idTwo, $noFrozen) {
        $result = '';
        $idOne = ubRouting::filters($idOne, 'int');
        $idTwo = ubRouting::filters($idTwo, 'int');
        $timePointOneData = $this->getTimePointData($idOne);
        $timePointTwoData = $this->getTimePointData($idTwo);
        $diffLogins = array();

        if (!empty($timePointOneData) and !empty($timePointTwoData)) {
            if ($idOne != $idTwo) {
                $pointOneUsers = (isset($timePointOneData['debtors']) and is_array($timePointOneData['debtors'])) ? $timePointOneData['debtors'] : array();
                $pointTwoUsers = (isset($timePointTwoData['debtors']) and is_array($timePointTwoData['debtors'])) ? $timePointTwoData['debtors'] : array();
                foreach ($pointOneUsers as $eachLogin => $stateOne) {
                    $stateTwo = (isset($pointTwoUsers[$eachLogin])) ? $pointTwoUsers[$eachLogin] : null;
                    if ($noFrozen) {
                        if ($stateOne == -1 or $stateTwo == -1) {
                            continue;
                        }
                    }
                    if ($stateOne != $stateTwo) {
                        $diffLogins[] = $eachLogin;
                    }
                }
                foreach ($pointTwoUsers as $eachLogin => $stateTwo) {
                    if (!isset($pointOneUsers[$eachLogin])) {
                        if ($noFrozen) {
                            if ($stateTwo == -1) {
                                continue;
                            }
                        }
                        $diffLogins[] = $eachLogin;
                    }
                }
                if (!empty($diffLogins)) {
                    $diffLogins = array_unique($diffLogins);
                }

                if (!empty($diffLogins)) {
                    $result .= web_UserArrayShower($diffLogins, array(), true, $this->dtUsOpts);
                } else {
                    $result .= $this->messages->getStyledMessage(__('No differences found'), 'info');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Time points should be different'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing found'), 'error');
        }
            
        return ($result);
    }

    /**
     *                ___
                    .;___`'.
                   /_  _ \  |
                   |a  a  \_/     ,__
                   | <     _)    _)(_)
            _,_     \_--' /____.(/   \
           (_/(/'---/`\_/`\     |  $  |
           /   \.___\_/`\_| .---'.___.'
          |  $  |   \\ '   \ \
          '.___.'    \\_'___\ \
                    .'   _   \/
                   /   .' \   \
                  /   /    \   \
                 |    |     |   |
                 \____\    /____/
                 (__,_|    |_,__)

                 IN DEBT WE TRUST
     */

}
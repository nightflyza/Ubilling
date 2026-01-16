<?php

/**
 * Capabilities directory base class
 */
class CapabilitiesDirectory {

    /**
     * Contains all available capabilities list as id=>data
     *
     * @var array
     */
    protected $allcapab = array();

    /**
     * Contains array of available capabilities states as id=>data
     *
     * @var array
     */
    protected $capabstates = array();

    /**
     * Contains array of all employee
     *
     * @var array
     */
    protected $employees = array();

    /**
     * Contains array of available capabs ids
     *
     * @var array
     */
    protected $availids = array();

    /**
     * Contains array of capabilities history
     *
     * @var array
     */
    protected $history = array();

    /**
     * System telepathy object placeholder
     *
     * @var object
     */
    protected $telepathy = '';

    const NO_ID = 'NO_SUCH_CAPABILITY_ID';
    const URL_CREATE = '?module=capabilities';
    const URL_ME = '?module=capabilities';
    const URL_CAPAB = '&edit=';

    /**
     * @param bool $noloaders Do not protess load subroutines at object creation
     * 
     * @return void
     */
    public function __construct($noloaders = false) {
        if (!$noloaders) {
            //load existing capabilities
            $this->loadCapabilities();
            //load they ids
            $this->loadAllIds();
            //load existing states
            $this->loadCapabStates();
            //loads capabs history
            $this->loadHistory();
            //load employees
            $this->loadEmployees();
            //init telepathy
            $this->initTelepathy();
        }
    }

    /**
     * stores all available capab ids into protected prop - used in pagination
     * 
     * @return void
     */
    protected function loadAllIds() {
        $query = "SELECT `id` from `capab`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->availids[$each['id']] = $each['id'];
            }
        }
    }

    /**
     * Loads capabs history into protected prop
     * 
     * @return void
     */
    protected function loadHistory() {
        $query = "SELECT * from `capabhist` ORDER BY `id` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->history[$each['capabid']][] = $each;
            }
        }
    }

    /**
     * Returns capab created admin login
     * 
     * @param int $capabId
     *
     * @return void/string
     */
    protected function getHistoryCreated($capabId) {
        $result = '';
        if (!empty($this->history)) {
            if (isset($this->history[$capabId])) {
                $capabHist = $this->history[$capabId];
                if (!empty($capabHist)) {
                    foreach ($capabHist as $io => $each) {
                        if ($each['type'] == 'create') {
                            $result = $each['admin'];
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
     * Inits system telepathy object
     * 
     * @return void
     */
    protected function initTelepathy() {
        $this->telepathy = new Telepathy(false, true);
    }

    /**
     * loads all of available capabilities as protected prop allcapab
     * 
     * @return void
     */
    protected function loadCapabilities() {
        $query = "SELECT * from `capab`;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allcapab[$each['id']]['id'] = $each['id'];
                $this->allcapab[$each['id']]['date'] = $each['date'];
                $this->allcapab[$each['id']]['address'] = $each['address'];
                $this->allcapab[$each['id']]['phone'] = $each['phone'];
                $this->allcapab[$each['id']]['stateid'] = $each['stateid'];
                $this->allcapab[$each['id']]['notes'] = $each['notes'];
                $this->allcapab[$each['id']]['price'] = $each['price'];
                $this->allcapab[$each['id']]['employeeid'] = $each['employeeid'];
                $this->allcapab[$each['id']]['donedate'] = $each['donedate'];
            }
        }
    }

    /**
     * loads available capability states into protected prop capabstates
     * 
     * @return void
     */
    protected function loadCapabStates() {
        $query = "SELECT * from `capabstates`";
        $all = simple_queryall($query);

        $this->capabstates[0]['id'] = 0;
        $this->capabstates[0]['state'] = __('Was not processed');
        $this->capabstates[0]['color'] = 'FF0000';

        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->capabstates[$each['id']]['id'] = $each['id'];
                $this->capabstates[$each['id']]['state'] = $each['state'];
                $this->capabstates[$each['id']]['color'] = $each['color'];
            }
        }
    }

    /**
     * Loads all existing employees into protected employees prop
     * 
     * @return void
     */
    protected function loadEmployees() {
        $query = "SELECT * from `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->employees[$each['id']]['id'] = $each['id'];
                $this->employees[$each['id']]['name'] = $each['name'];
                $this->employees[$each['id']]['active'] = $each['active'];
            }
        }
    }

    /**
     * Renders base capabilities list
     * 
     * @return type
     */
    public function render() {
        global $ubillingConfig;
        $dateSort = ($ubillingConfig->getAlterParam('CAPABDIR_DATE_SORT')) ? true : false;
        $result = '';
        $columns = array(__('Admin'), __('Date'), __('Address'), __('Phone'), __('Status'), __('Notes'), __('Price'), __('Employee'), __('Changed'), __('Actions'));
        $result = $this->panel();
        $opts = '"order": [[ 4, "asc" ]]';
        if ($dateSort) {
            $opts = '"order": [[ 1, "desc" ]]';
        }
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajlist=true', false, __('Objects'), 100, $opts);
        return ($result);
    }

    /**
     * Returns all capabs states color styles
     * 
     * @return string
     */
    protected function getColorStyles() {
        $result = wf_tag('style', false);
        if (!empty($this->capabstates)) {
            foreach ($this->capabstates as $io => $each) {
                $customColorStyleName = 'capabcolorcustom_' . $io;
                $result .= '.' . $customColorStyleName . ',
                                                   .' . $customColorStyleName . ' div,
                                                   .' . $customColorStyleName . ' span {
                                                        background-color: #' . $each['color'] . '; 
                                                        border-color: #' . $each['color'] . '; 
                                                        color: #FFFFFF;           
                                                    }';
            }
        }
        $result .= wf_tag('style', true);
        return ($result);
    }

    /**
     * Renders capabs as calendar
     * 
     * @return string
     */
    public function renderCalendar() {
        $result = '';
        $result .= $this->panel();
        $result .= $this->getColorStyles();
        $data = '';
        if (!empty($this->allcapab)) {
            foreach ($this->allcapab as $io => $each) {
                $stateName = @$this->capabstates[$each['stateid']]['state'];
                $employeeName = @$this->employees[$each['employeeid']]['name'];
                $coloring = "className : 'capabcolorcustom_" . $each['stateid'] . "',";
                $timestamp = strtotime($each['date']);
                $startDate = date("Y, n-1, j", $timestamp);

                $doneTimestamp = (!empty($each['donedate'])) ? strtotime($each['donedate']) : time();
                $doneDate = date("Y, n-1, j", $doneTimestamp);
                $daysSpent = zb_formatTime($doneTimestamp - $timestamp);
                $address = str_replace("'", '`', $each['address']);
                $data .= "
                      {
                        title: '" . $address . ' - ' . $stateName . ' (' . $daysSpent . ')' . "',
                        start: new Date(" . $startDate . "),
                        end: new Date(" . $doneDate . "),
                        " . $coloring . "
                        url: '" . self::URL_ME . "&edit=" . $each['id'] . "'
                      },";
            }

            $data = zb_CutEnd($data);
        }
        $result .= wf_FullCalendar($data);
        return ($result);
    }

    /**
     * Renders capab json data
     * 
     * 
     * @rerturn string
     */
    public function ajCapabList() {
        $jsonAAData = array();

        if (!empty($this->allcapab)) {
            foreach ($this->allcapab as $io => $each) {
                $jsonItem = array();

                $stateName = @$this->capabstates[$each['stateid']]['state'];
                $stateColor = @$this->capabstates[$each['stateid']]['color'];
                $employeeName = @$this->employees[$each['employeeid']]['name'];

                $actions = '';
                if (cfr('ROOT')) {
                    $actions .= wf_JSAlert(self::URL_ME . "&delete=" . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results')) . ' ';
                }
                $actions .= wf_link(self::URL_ME . "&edit=" . $each['id'], web_edit_icon(), false);

                $loginGuess = $this->telepathy->getLogin($each['address']);
                $profileLink = (!empty($loginGuess)) ? wf_Link('?module=userprofile&username=' . $loginGuess, web_profile_icon(), false, '') . ' (' . __('guessed') . ')' : '';
                $adminCreated = $this->getHistoryCreated($each['id']);

                $jsonItem[] = $adminCreated;
                $jsonItem[] = $each['date'];
                $jsonItem[] = $each['address'] . ' ' . $profileLink;
                $jsonItem[] = $each['phone'];
                $jsonItem[] = wf_tag('span', false, '', 'style = "display:none;"') . $each['stateid'] . wf_tag('span', true) . wf_tag('font', false, '', 'color = "#' . $stateColor . '"') . $stateName . wf_tag('font', true);
                $jsonItem[] = $each['notes'];
                $jsonItem[] = $each['price'];
                $jsonItem[] = $employeeName;
                $jsonItem[] = $each['donedate'];
                $jsonItem[] = $actions;
                $jsonAAData[] = $jsonItem;
            }
        }



        $result = array("aaData" => $jsonAAData);
        return (json_encode($result));

        return ($result);
    }

    /**
     * delete some capability from database
     * 
     * @param $id - capability id
     * 
     * @return void
     */
    public function deleteCapability($id) {
        $id = vf($id, 3);
        if (isset($this->availids[$id])) {
            $query = "DELETE from `capab` WHERE `id`='" . $id . "'";
            nr_query($query);
            log_register("CAPABILITY DELETE [" . $id . "]");
            $this->logCapability($id, 'delete');
        } else {
            throw new Exception(self::NO_ID);
        }
    }

    /**
     * Saves some capab actions into history
     * 
     * @param int $capabId
     * @param string $type
     * @param string $event
     */
    protected function logCapability($capabId, $type, $event = '') {
        $capabId = vf($capabId, 3);
        $type = vf($type);
        $eventF = mysql_real_escape_string($event);
        $admin = whoami();
        $date = curdatetime();
        $query = "INSERT INTO `capabhist` (`id`,`capabid`,`admin`,`date`,`type`,`event`) VALUES "
            . "(NULL, '" . $capabId . "','" . $admin . "','" . $date . "','" . $type . "','" . $eventF . "');";

        nr_query($query);
    }

    /**
     * creates new capability in database
     * 
     * @param $address - users address
     * @param $phone - users phone
     * @param $notes - text notes to task 
     * 
     * @return integer
     */
    public function addCapability($address, $phone, $notes) {
        $date = curdatetime();
        $address = mysql_real_escape_string($address);
        $phone = mysql_real_escape_string($phone);
        $notes = mysql_real_escape_string($notes);

        $query = "INSERT INTO `capab` (`id` , `date` , `address` , `phone` ,`stateid` ,`notes` ,`price` ,`employeeid` ,`donedate`) 
             VALUES ( NULL , '" . $date . "', '" . $address . "', '" . $phone . "', '0', '" . $notes . "', NULL , NULL , NULL);";

        nr_query($query);
        $lastId = simple_get_lastid('capab');
        log_register("CAPABILITY ADD [" . $lastId . "] `" . $address . "`");
        $this->logCapability($lastId, 'create');
    }

    /**
     * Generates random HTML color
     * 
     * @return string
     */
    protected function genRandomColor() {
        $result = strtoupper(dechex(rand(0, 10000000)));
        return ($result);
    }

    /**
     * returns capability creation form
     * 
     * @param string $address Pre set address fo form
     * @param string $phone  Pre set phone
     * @param string $notes Pre set notes
     * 
     * @return string
     */
    public function createForm($address = '', $phone = '', $notes = '') {
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        //$allAddress = zb_AddressGetFulladdresslistCached();
        $allAddress= zb_AddressGetBuildAllAddress(false);
        natsort($allAddress);
        $inputs = wf_AutocompleteTextInput('newaddress', $allAddress, __('Address') . $sup, $address, false);
        $inputs .= wf_TextInput('newphone', __('Phone') . $sup, $phone, true);
        $inputs .= __('Notes') . wf_tag('br');
        $inputs .= wf_TextArea('newnotes', '', $notes, true, '40x5');
        $inputs .= wf_Submit(__('Create'));

        $result = wf_Form(self::URL_CREATE, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns capability editing form by existing cap id
     * 
     * @return string
     */
    public function editForm($id) {
        $id = vf($id, 3);
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result = '';
        $messages = new UbillingMessageHelper();
        $stateSelector = array();
        $employeeSelector = array();
        $employeeSelector['NULL'] = '-';
        $employeeLogins = ts_GetAllEmployeeLoginsCached();
        $employeeLogins = unserialize($employeeLogins);

        if (isset($this->availids[$id])) {
            //states preprocessing
            if (!empty($this->capabstates)) {
                foreach ($this->capabstates as $io => $eachcap) {
                    $stateSelector[$eachcap['id']] = $eachcap['state'];
                }
            }
            //employee preprocessing
            if (!empty($this->employees)) {
                foreach ($this->employees as $ia => $eachemp) {
                    if ($eachemp['active']) {
                        $employeeSelector[$eachemp['id']] = $eachemp['name'];
                    }
                }
            }

            //task creation form
            $customData = wf_HiddenInput('unifiedformcapabdirgobackid', $id);
            $customData .= wf_CheckInput('unifiedformcapabdirgobackflag', __('Back to capability after creation'), true, true);

            $taskForm = ts_TaskCreateFormUnified($this->allcapab[$id]['address'], $this->allcapab[$id]['phone'], '', '', $customData, $this->allcapab[$id]['notes']);
            $taskControl = wf_modalAuto(wf_img('skins/createtask.gif') . ' ' . __('Create task'), __('Create task'), $taskForm, 'ubButton', '420', '500');

            $result = wf_BackLink(self::URL_ME) . ' ';
            $result .= $taskControl . wf_delimiter();

            $inputs = wf_TextInput('editaddress', __('Full address') . $sup, $this->allcapab[$id]['address'], true);
            $inputs .= wf_TextInput('editphone', __('Phone') . $sup, $this->allcapab[$id]['phone'], true);
            $inputs .= __('Notes') . wf_tag('br');
            $inputs .= wf_TextArea('editnotes', '', $this->allcapab[$id]['notes'], true, '40x5');
            $inputs .= wf_TextInput('editprice', __('Price'), $this->allcapab[$id]['price'], true);
            $inputs .= wf_Selector('editstateid', $stateSelector, __('Status'), $this->allcapab[$id]['stateid'], true);
            $inputs .= wf_Selector('editemployeeid', $employeeSelector, __('Worker'), $this->allcapab[$id]['employeeid'], true);
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Save'));


            $form = wf_Form("", 'POST', $inputs, 'glamour');
            $form .= wf_CleanDiv();

            $capabHist = '';
            if (isset($this->history[$id])) {
                if (!empty($this->history[$id])) {
                    foreach ($this->history[$id] as $io => $each) {
                        $employeeName = (isset($employeeLogins[$each['admin']])) ? $employeeLogins[$each['admin']] : $each['admin'];
                        $label = '';
                        switch ($each['type']) {
                            case 'create':
                                $style = 'success';
                                $label = __('Created');
                                break;
                            case 'edit':
                                $style = 'warning';
                                $label = __('Changed');
                                break;
                            case 'delete':
                                $style = 'error';
                                $label = __('Deleted');
                                break;
                            default:
                                $style = 'info';
                                break;
                        }
                        $capabHist .= $messages->getStyledMessage($label . ' ' . $each['date'] . ' ' . $employeeName, $style);
                    }
                }
            }
            $cells = wf_TableCell($form, '', '', 'valign="top"');
            $cells .= wf_TableCell($capabHist, '', '', 'valign="top"');
            $rows = wf_TableRow($cells);
            $result .= wf_TableBody($rows, '100%', 0);
        } else {
            throw new Exception(self::NO_ID);
        }
        return ($result);
    }

    /**
     * update capability into database by its id
     * 
     * @param int $id
     * @param int $address
     * @param string $phone
     * @param int $stateid
     * @param string $notes
     * @param string $price
     * @param int $employeeid
     * @throws Exception
     * 
     * @return void
     */
    public function editCapability($id, $address, $phone, $stateid, $notes, $price, $employeeid) {
        $id = vf($id, 3);
        $address = mysql_real_escape_string($address);
        $phone = mysql_real_escape_string($phone);
        $stateid = vf($stateid, 3);
        $price = mysql_real_escape_string($price);
        $employeeid = vf($employeeid, 3);
        $curdate = curdatetime();
        if (isset($this->availids[$id])) {
            simple_update_field('capab', 'donedate', $curdate, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'address', $address, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'phone', $phone, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'stateid', $stateid, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'notes', $notes, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'price', $price, "WHERE `id`='" . $id . "';");
            simple_update_field('capab', 'employeeid', $employeeid, "WHERE `id`='" . $id . "';");
            log_register("CAPABILITY EDIT [" . $id . "] `" . $address . "`");
            $this->logCapability($id, 'edit');
        } else {
            throw new Exception(self::NO_ID);
        }
    }

    /**
     * shows currently available capability states in table grid
     * 
     * @return string
     */
    public function statesList() {

        $cells = wf_TableCell(__('ID'));
        $cells .= wf_TableCell(__('Status'));
        $cells .= wf_TableCell(__('Color'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');


        if (!empty($this->capabstates)) {
            foreach ($this->capabstates as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell($each['state']);
                $color = wf_tag('font', false, '', 'color = "#' . $each['color'] . '"') . $each['color'] . wf_tag('font', true);
                $cells .= wf_TableCell($color);
                if ($each['id'] != 0) {
                    $actions = wf_JSAlert(self::URL_ME . "&states=true&deletestate=" . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                    $actions .= wf_JSAlert(self::URL_ME . "&states=true&editstate=" . $each['id'], web_edit_icon(), __('Are you serious'));
                } else {
                    $actions = '';
                }
                $cells .= wf_TableCell($actions);
                $rows .= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', '0', 'sortable');
        return ($result);
    }

    /**
     * returns capability states adding form
     * 
     * @return string
     */
    public function statesAddForm() {
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result = wf_BackLink(self::URL_ME, '', true);
        $inputs = wf_TextInput('createstate', __('New status') . $sup, '', true, '20');
        $inputs .= wf_ColPicker('createstatecolor', __('New status color') . $sup, '#' . $this->genRandomColor(), true, '10');
        $inputs .= wf_Submit(__('Create'));
        $result .= wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns capability states adding form
     * 
     * @param int $id
     * @return string
     */
    public function statesEditForm($id) {

        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result = wf_BackLink(self::URL_ME . '&states=true', '', true);
        $inputs = wf_TextInput('editstate', __('New status') . $sup, $this->capabstates[$id]['state'], true, '20');
        $inputs .= wf_ColPicker('editstatecolor', __('New status color') . $sup, '#' . $this->capabstates[$id]['color'], true, '10');
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * creates new capability state
     * 
     * @param string $state new state label
     * @param string $color new state color
     * 
     * @return void
     */
    public function statesCreate($state, $color) {
        $state = mysql_real_escape_string($state);
        $color = mysql_real_escape_string($color);
        $color = str_replace('#', '', $color);
        $query = "INSERT INTO `capabstates` (`id` , `state` , `color`) 
             VALUES ( NULL , '" . $state . "', '" . $color . "');";
        nr_query($query);
        log_register("CAPABILITY STATE ADD `" . $state . "`");
    }

    /**
     * delete state by its id
     * 
     * @param int $id - state id in database
     * 
     * @return void
     */
    public function statesDelete($id) {
        $id = vf($id, 3);
        if (!empty($id)) {
            $query = "DELETE FROM `capabstates` WHERE `id`='" . $id . "'";
            nr_query($query);
            log_register("CAPABILITY STATE DELETE [" . $id . "]");
        }
    }

    /**
     * updates state into database
     * 
     * @param int    $id    - existing state id
     * @param int    $state - new state title
     * @param string $color - new state color
     * 
     * @return void
     */
    public function statesChange($id, $state, $color) {
        $id = vf($id, 3);
        $state = mysql_real_escape_string($state);
        $color = mysql_real_escape_string($color);
        $color = str_replace('#', '', $color);
        if (!empty($id)) {
            simple_update_field('capabstates', 'state', $state, "WHERE `id`='" . $id . "'");
            simple_update_field('capabstates', 'color', $color, "WHERE `id`='" . $id . "'");
            log_register("CAPABILITY STATE EDIT [" . $id . "] ON `" . $state . "`");
        }
    }

    /**
     * Renders capab sources stats
     * 
     * @return string
     */
    public function renderSourcesStats() {
        $result = '';
        $capabSources = new Stigma('CAPABSOURCE');
        $result .= $capabSources->renderBasicReport();
        return ($result);
    }

    /**
     * Renders states charts
     * 
     * @return string
     */
    public function renderStatesStats() {
        $result = '';
        $statsTmp = array();
        if (!empty($this->allcapab)) {
            $total = sizeof($this->allcapab);
            foreach ($this->allcapab as $io => $each) {
                if (isset($statsTmp[$each['stateid']])) {
                    $statsTmp[$each['stateid']]++;
                } else {
                    $statsTmp[$each['stateid']] = 1;
                }
            }

            if (!empty($statsTmp)) {
                $cells = wf_TableCell(__('Status'));
                $cells .= wf_TableCell(__('Count'));
                $cells .= wf_TableCell(__('Visual'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($statsTmp as $stateid => $count) {
                    $cells = wf_TableCell(@$this->capabstates[$stateid]['state']);
                    $cells .= wf_TableCell($count);
                    $cells .= wf_TableCell(web_bar($count, $total));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            }
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * returns capabilities directory control panel
     * 
     * @return string
     */
    protected function panel() {
        $result = '';
        if (cfr('ROOT')) {
            $result .= wf_Link(self::URL_ME . "&states=true", wf_img('skins/settings.png', __('Modify states')), false, '') . '&nbsp;';
        }
        $result .= wf_modal(wf_img('skins/add_icon.png') . ' ' . __('Create'), __('Create'), $this->createForm(), 'ubButton', '400', '300');
        $result .= wf_Link(self::URL_ME . '&stats=true', wf_img('skins/icon_stats_16.gif') . ' ' . __('Stats'), false, 'ubButton');
        if (wf_CheckGet(array('calendar'))) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/icon_table.png') . ' ' . __('Grid view'), false, 'ubButton');
        } else {
            $result .= wf_Link(self::URL_ME . '&calendar=true', wf_img('skins/icon_calendar.gif') . ' ' . __('As calendar'), false, 'ubButton');
        }


        $result .= wf_delimiter();

        return ($result);
    }
}

<?php

class Warehouse {

    /**
     * Contains all available employee as employeeid=>name
     *
     * @var array
     */
    protected $allEmployee = array();

    /**
     * Contains all active employee as employeeid=>name
     *
     * @var array
     */
    protected $activeEmployee = array();

    /**
     * Contains available employee telegram chatid data as id=>chatid
     *
     * @var array
     */
    protected $allEmployeeTelegram = array();

    /**
     * Contains all available employee realnames as login=>name
     *
     * @var array
     */
    protected $allEmployeeLogins = array();

    /**
     * System alter.ini config stored as array key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * List of all available items categories as id=>category name
     *
     * @var array
     */
    protected $allCategories = array();

    /**
     * List of all available item types as id=>data
     *
     * @var array
     */
    protected $allItemTypes = array();

    /**
     * Contains all item type names as id=>name
     *
     * @var array
     */
    protected $allItemTypeNames = array();

    /**
     * All of available warehouse storages as id=>name
     *
     * @var array
     */
    protected $allStorages = array();

    /**
     * All of available warehouse contractors as id=>name
     *
     * @var array
     */
    protected $allContractors = array();

    /**
     * All available incoming operations
     *
     * @var array
     */
    protected $allIncoming = array();

    /**
     * All available outcoming operations
     *
     * @var type 
     */
    protected $allOutcoming = array();

    /**
     * Preloaded reserve entries as id=>reserve data
     *
     * @var array
     */
    protected $allReserve = array();

    /**
     * Contains previous reservation history
     *
     * @var array
     */
    protected $allReserveHistory = array();

    /**
     * Contains reserve creation dates as reserveId=>date string array
     *
     * @var array
     */
    protected $allReserveCreationDates = array();

    /**
     * Available unit types as unittype=>localized name
     *
     * @var array
     */
    protected $unitTypes = array();

    /**
     * Available outcoming destinations as destination=>localized name
     *
     * @var array
     */
    protected $outDests = array();

    /**
     * Contains previously detected tasks outcomings mappings
     *
     * @var array
     */
    protected $taskOutsCache = array();

    /**
     * System messages helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * System telegram object placeholder
     *
     * @var object
     */
    protected $telegram = '';

    /**
     * Telegram force notification flag
     *
     * @var bool
     */
    protected $telegramNotify = false;

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Default asterisk required fields notifier
     *
     * @var string
     */
    protected $sup = '';

    /**
     * Default routing defs
     */
    const URL_ME = '?module=warehouse';
    const URL_CATEGORIES = 'categories=true';
    const URL_ITEMTYPES = 'itemtypes=true';
    const URL_STORAGES = 'storages=true';
    const URL_CONTRACTORS = 'contractors=true';
    const URL_IN = 'in=true';
    const URL_OUT = 'out=true';
    const URL_OUTAJREMAINS = 'ajaxremains=true';
    const URL_AJITSELECTOR = 'ajits=';
    const URL_AJODSELECTOR = 'ajods=';
    const URL_INAJLIST = 'ajaxinlist=true';
    const URL_REAJTREM = 'ajaxtremains=true';
    const URL_OUTAJLIST = 'ajaxoutlist=true';
    const URL_VIEWERS = 'viewers=true';
    const URL_REPORTS = 'reports=true';
    const URL_RESERVE = 'reserve=true';
    const PHOTOSTORAGE_SCOPE = 'WAREHOUSEITEMTYPE';
    //some caching timeout
    const CACHE_TIMEOUT = 2592000;

    /**
     * Creates new warehouse instance
     * 
     * @param type $taskid
     * 
     * @return void
     */
    public function __construct($taskid = '') {
        $this->loadAltCfg();
        $this->setOptions();
        $this->loadOutOperations($taskid);
        $this->setUnitTypes();
        $this->setOutDests();
        $this->setSup();
        $this->loadMessages();
        $this->loadAllEmployeeData();
        $this->loadCategories();
        $this->loadItemTypes();
        $this->loadStorages();
        $this->loadContractors();
        $this->initTelegram();
        $this->initCache();
        $this->loadTaskOutsCache();
        if (empty($taskid)) {
            $this->loadReserve();
            $this->loadReserveHistory();
            $this->loadInOperations();
        }
    }

    /**
     * Loads system alter config
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAltCfg() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets some config based options
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg['WAREHOUSE_TELEGRAM']) AND $this->altCfg['WAREHOUSE_TELEGRAM']) {
            $this->telegramNotify = true;
        }
    }

    /**
     * Creates system message helper object instance
     * 
     * @return void
     */
    protected function loadMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits telegram object as protected instance for further usage
     * 
     * @return void
     */
    protected function initTelegram() {
        if ($this->altCfg['SENDDOG_ENABLED']) {
            $this->telegram = new UbillingTelegram();
        }
    }

    /**
     * Inits system cache for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Loads tasks=>outcomings cache
     * 
     * @return void
     */
    protected function loadTaskOutsCache() {
        $this->taskOutsCache = $this->cache->get('TASKSOUTS', self::CACHE_TIMEOUT);
    }

    /**
     * Loads all existing employees from database
     * 
     * @return void
     */
    protected function loadAllEmployeeData() {
        $query = "SELECT * from `employee`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allEmployee[$each['id']] = $each['name'];
                if ($each['active']) {
                    $this->activeEmployee[$each['id']] = $each['name'];
                }
                if (!empty($each['admlogin'])) {
                    $this->allEmployeeLogins[$each['admlogin']] = $each['name'];
                }
                $this->allEmployeeTelegram[$each['id']] = $each['telegram'];
            }
        }
    }

    /**
     * Sets default unit types
     * 
     * @return void
     */
    protected function setUnitTypes() {
        $this->unitTypes['quantity'] = __('quantity');
        $this->unitTypes['meter'] = __('meter');
        $this->unitTypes['kilometer'] = __('kilometer');
        $this->unitTypes['money'] = __('money');
        $this->unitTypes['time'] = __('time');
        $this->unitTypes['litre'] = __('litre');
        $this->unitTypes['pieces'] = __('pieces');
        $this->unitTypes['packing'] = __('packing');
    }

    /**
     * Sets default unit types
     * 
     * @return void
     */
    protected function setOutDests() {
        $this->outDests['task'] = __('Task');
        $this->outDests['contractor'] = __('Contractor');
        $this->outDests['employee'] = __('Employee');
        $this->outDests['storage'] = __('Warehouse storage');
        $this->outDests['user'] = __('User');
        $this->outDests['sale'] = __('Sale');
        $this->outDests['cancellation'] = __('Cancellation');
        $this->outDests['mistake'] = __('Mistake');
    }

    /**
     * Sets default required fields notification
     * 
     * @return void
     */
    protected function setSup() {
        $this->sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    }

    /**
     * Loads existing warehouse categories from DB
     * 
     * @return void
     */
    protected function loadCategories() {
        $query = "SELECT * from `wh_categories`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allCategories[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads all existing warehouse item types
     * 
     * @return void
     */
    protected function loadItemTypes() {
        $query = "SELECT* from `wh_itemtypes` ORDER BY `name` ASC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allItemTypes[$each['id']] = $each;
                $this->allItemTypeNames[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads existing warehouse storages from DB
     * 
     * @return void
     */
    protected function loadStorages() {
        $query = "SELECT * from `wh_storages`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allStorages[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads existing warehouse contractors from DB
     * 
     * @return void
     */
    protected function loadContractors() {
        $query = "SELECT * from `wh_contractors`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allContractors[$each['id']] = $each['name'];
            }
        }
    }

    /**
     * Loads existing incoming operations from database
     * 
     * @return void
     */
    protected function loadInOperations() {
        $query = "SELECT * from `wh_in`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allIncoming[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing outcoming operations from database
     * 
     * @param int $taskid existing taskId
     * 
     * @return void
     */
    protected function loadOutOperations($taskid = '') {
        $taskid = vf($taskid, 3);
        $where = (!empty($taskid)) ? "WHERE `desttype`='task' AND `destparam`='" . $taskid . "'" : '';
        $query = "SELECT * from `wh_out` " . $where . ";";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allOutcoming[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads available reserved items from database
     * 
     * @return void
     */
    protected function loadReserve() {
        $query = "SELECT * from `wh_reserve` ORDER BY `id` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allReserve[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads reserve history logs from database
     * 
     * @return void
     */
    protected function loadReserveHistory() {
        $query = "SELECT * from `wh_reshist` ORDER BY `id` DESC";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allReserveHistory[$each['id']] = $each;
                if (!empty($each['resid'])) {
                    if ($each['type'] == 'create') {
                        $this->allReserveCreationDates[$each['resid']] = $each['date'];
                    }
                }
            }
        }
    }

    /**
     * Returns count of itemtypes reserved on storage if available
     * 
     * @param int $storageId
     * @param int $itemtypeId
     * 
     * @return float
     */
    protected function reserveGet($storageId, $itemtypeId) {
        $result = 0;
        $storageId = vf($storageId, 3);
        $itemtypeId = vf($itemtypeId, 3);
        if (!empty($this->allReserve)) {
            foreach ($this->allReserve as $io => $each) {
                if (($each['storageid'] == $storageId) AND ( $each['itemtypeid'] == $itemtypeId)) {
                    $result+=$each['count'];
                }
            }
        }
        return ($result);
    }

    /**
     * Returns existing reserve data
     * 
     * @param int $reserveId
     * 
     * @return array
     */
    protected function reserveGetData($reserveId) {
        $result = array();
        if (isset($this->allReserve[$reserveId])) {
            $result = $this->allReserve[$reserveId];
        }
        return ($result);
    }

    /**
     * Stores reservation history log record into database
     * 
     * @param string $type - create/update/delete
     * @param int $storageId
     * @param int $itemtypeId
     * @param float $count
     * @param int $employeeId
     * @param int $reserveId
     */
    protected function reservePushLog($type, $storageId = '', $itemtypeId = '', $count = '', $employeeId = '', $reserveId = '') {
        $curdate = curdatetime();
        $type = vf($type);
        $adminLogin = mysql_real_escape_string(whoami());
        $storageId = "'" . vf($storageId, 3) . "'";
        $itemtypeId = "'" . vf($itemtypeId, 3) . "'";
        $count = "'" . mysql_real_escape_string($count) . "'";
        $employeeId = "'" . vf($employeeId, 3) . "'";
        $reserveId = vf($reserveId, 3);
        $query = "INSERT INTO `wh_reshist` (`id`,`resid`,`date`,`type`,`storageid`,`itemtypeid`,`count`,`employeeid`,`admin`) VALUES ";
        $query.= "(NULL,'" . $reserveId . "','" . $curdate . "','" . $type . "'," . $storageId . "," . $itemtypeId . "," . $count . "," . $employeeId . ",'" . $adminLogin . "');";
        nr_query($query);
    }

    /**
     * Stores Telegram message for some employee
     * 
     * @param int $employeeid
     * @param string $message
     * 
     * @return void
     */
    protected function sendTelegram($employeeId, $message) {
        if ($this->altCfg['SENDDOG_ENABLED']) {
            $chatId = @$this->allEmployeeTelegram[$employeeId];
            if (!empty($chatId)) {
                $this->telegram->sendMessage($chatId, $message, false, 'WAREHOUSE');
            }
        }
    }

    /**
     * Sends some notificaton about reserve creation to employee
     * 
     * @param int $storageId
     * @param int $itemtypeId
     * @param float $count
     * @param int $employeeId
     * 
     * @return void
     */
    protected function reserveCreationNotify($storageId, $itemtypeId, $count, $employeeId) {
        if ($this->telegramNotify) {
            $message = '';
            $adminLogin = whoami();
            $adminName = (isset($this->allEmployeeLogins[$adminLogin])) ? $this->allEmployeeLogins[$adminLogin] : $adminLogin;
            $message.=__('From warehouse storage') . ' ' . $this->allStorages[$storageId] . '\r\n ';
            $message.= $adminName . ' ' . __('reserved for you') . ': ';
            $message.=$this->allItemTypeNames[$itemtypeId] . ' ' . $count . ' ' . $this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']];
            $this->sendTelegram($employeeId, $message);
        }
    }

    /**
     * Sends reserve remains daily notifications to employees
     * 
     * @return void
     */
    public function telegramReserveDailyNotify() {
        if (!empty($this->allReserve)) {
            if ($this->altCfg['SENDDOG_ENABLED']) {
                $curdate = curdate();
                $sendTmp = array(); //employeeid => text aggregated
                $reserveTmp = array(); //employeeid=>reserve data with aggr
                foreach ($this->allReserve as $io => $eachReserve) {
                    $employeeId = $eachReserve['employeeid'];
                    $chatId = @$this->allEmployeeTelegram[$employeeId];
                    $itemtypeId = $eachReserve['itemtypeid'];
                    $itemCount = $eachReserve['count'];
                    if (!empty($chatId)) {
                        if (!isset($reserveTmp[$employeeId])) {
                            $reserveTmp[$employeeId] = array();
                        }

                        if (isset($reserveTmp[$employeeId][$itemtypeId])) {
                            $reserveTmp[$employeeId][$itemtypeId]+=$itemCount;
                        } else {
                            $reserveTmp[$employeeId][$itemtypeId] = $itemCount;
                        }
                    }
                }


                if (!empty($reserveTmp)) {
                    foreach ($reserveTmp as $eachEmployee => $reservedItems) {
                        $message = __('Is reserved for you') . '\r\n ';
                        ;
                        foreach ($reservedItems as $eachItemId => $eachItemCount) {
                            $message.= @$this->allItemTypeNames[$eachItemId] . ': ' . $eachItemCount . ' ' . @$this->unitTypes[$this->allItemTypes[$eachItemId]['unit']] . '\r\n ';
                        }
                        $message.='📦📦📦📦' . '\r\n '; // very vsrate emoji
                        $sendTmp[$eachEmployee] = $message;
                    }
                }

                if (!empty($sendTmp)) {
                    foreach ($sendTmp as $io => $eachMessage) {
                        $this->sendTelegram($io, $eachMessage);
                    }
                }
            }
        }
    }

    /**
     * Creates new reserve record in database
     * 
     * @param int $storageId
     * @param int $itemtypeId
     * @param float $count
     * @param int $employeeId
     * 
     * @return void/string  if succefull or error message
     */
    public function reserveCreate($storageId, $itemtypeId, $count, $employeeId) {
        $storageId = vf($storageId, 3);
        $itemtypeId = vf($itemtypeId, 3);
        $countF = mysql_real_escape_string($count);
        $countF = str_replace(',', '.', $countF);
        $employeeId = vf($employeeId, 3);
        $storageRemains = $this->remainsOnStorage($storageId);
        @$itemtypeRemains = $storageRemains[$itemtypeId];
        if (empty($itemtypeRemains)) {
            $itemtypeRemains = 0;
        }
        $alreadyReserved = $this->reserveGet($storageId, $itemtypeId);
        $realRemains = $itemtypeRemains - $alreadyReserved;

        $result = '';
        if (isset($this->allStorages[$storageId])) {
            if (isset($this->allItemTypes[$itemtypeId])) {
                if (isset($this->allEmployee[$employeeId])) {
                    if ($realRemains >= $countF) {
                        $query = "INSERT INTO `wh_reserve` (`id`,`storageid`,`itemtypeid`,`count`,`employeeid`) VALUES "
                                . "(NULL,'" . $storageId . "','" . $itemtypeId . "','" . $countF . "','" . $employeeId . "')";
                        nr_query($query);
                        $newId = simple_get_lastid('wh_reserve');
                        log_register('WAREHOUSE RESERVE CREATE [' . $newId . '] ITEM [' . $itemtypeId . '] COUNT `' . $count . '` EMPLOYEE [' . $employeeId . ']');
                        $this->reservePushLog('create', $storageId, $itemtypeId, $count, $employeeId, $newId);
                        $this->reserveCreationNotify($storageId, $itemtypeId, $count, $employeeId);
                    } else {
                        $result = $this->messages->getStyledMessage($this->allItemTypeNames[$itemtypeId] . '. ' . __('The balance of goods and materials in stock is less than the amount') . ' (' . $countF . ' > ' . $itemtypeRemains . '-' . $alreadyReserved . ')', 'error');
                    }
                } else {
                    $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No available workers for reserve creation'), 'error');
                }
            } else {
                $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No existing warehouse item types'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No existing warehouse storages'), 'error');
        }
        return ($result);
    }

    /**
     * Creates mass reservation if required and returns its results as formatted notifications
     * 
     * @return string
     */
    public function reserveMassCreate() {
        $result = '';
        $successCount = 0;
        if (wf_CheckPost(array('newmassemployeeid', 'newmassstorageid', 'newmasscreation'))) {
            $employeeId = vf($_POST['newmassemployeeid'], 3);
            $storageId = vf($_POST['newmassstorageid']);
            $postTmp = $_POST;
            foreach ($postTmp as $io => $each) {
                if (ispos($io, 'newmassitemtype_')) {
                    $rawData = explode('_', $io);
                    $itemtypeId = $rawData[1];
                    $itemCount = $each;
                    if ($itemCount > 0) {
                        $reserveResult = $this->reserveCreate($storageId, $itemtypeId, $itemCount, $employeeId);
                        if (!empty($reserveResult)) {
                            //some shit happened
                            $result.=$reserveResult;
                        } else {
                            //success!
                            $result.=$this->messages->getStyledMessage($this->allItemTypeNames[$itemtypeId] . '. ' . __('Reserved') . ' (' . $itemCount . ')', 'success');
                            $successCount++;
                        }
                    }
                }
            }
            //live data update
            if ($successCount > 0) {
                $this->loadReserve();
            }
        }
        return ($result);
    }

    /**
     * Renders mass reservation form
     * 
     * @return string
     */
    public function reserveMassForm() {
        $result = '';
        $emptyWarehouse = false;
        $realRemains = array();
        $employeeTmp = array('' => '-');
        $employeeTmp+= $this->activeEmployee;
        $storageTmp = array('' => '-');
        $storageTmp+=$this->allStorages;

        if (!empty($this->allStorages)) {
            $inputs = wf_SelectorAC('newmassemployeeid', $employeeTmp, __('Employee'), @$_POST['newmassemployeeid'], true);
            if (wf_CheckPost(array('newmassemployeeid'))) {
                $inputs.=wf_SelectorAC('newmassstorageid', $storageTmp, __('Warehouse storage'), @$_POST['newmassstorageid'], true);
                if (wf_CheckPost(array('newmassstorageid'))) {
                    $storageRemains = $this->remainsOnStorage($_POST['newmassstorageid']);
                    if (!empty($storageRemains)) {
                        foreach ($storageRemains as $io => $each) {
                            $alreadyReserved = $this->reserveGet($_POST['newmassstorageid'], $io);
                            $realCount = $each - $alreadyReserved;
                            if ($realCount > 0) {
                                $realRemains[$io] = $each - $alreadyReserved;
                            }
                        }

                        if (empty($realRemains)) {
                            $emptyWarehouse = true;
                        } else {
                            $cells = wf_TableCell(__('Category'));
                            $cells.= wf_TableCell(__('Warehouse item types'));
                            $cells.= wf_TableCell(__('Count'));
                            $cells.= wf_TableCell(__('Reserve'));
                            $rows = wf_TableRow($cells, 'row1');
                            foreach ($realRemains as $itemtypeId => $itemCount) {
                                $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeId, @$this->allItemTypeNames[$itemtypeId]);
                                $cells = wf_TableCell(@$this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']]);
                                $cells.= wf_TableCell($itemTypeLink);
                                $cells.= wf_TableCell($itemCount . ' ' . @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']]);
                                $cells.= wf_TableCell(wf_TextInput('newmassitemtype_' . $itemtypeId, '', '0', false, 4));
                                $rows.= wf_TableRow($cells, 'row5');
                            }
                            $inputs.=wf_TableBody($rows, '100%', 0, '');
                            $inputs.=wf_CheckInput('newmasscreation', __('I`m ready'), true, false);
                            $inputs.=wf_tag('br');
                            $inputs.=wf_Submit(__('Reservation'));
                        }
                    } else {
                        $emptyWarehouse = true;
                    }
                }
            }

            $result.=wf_Form('', 'POST', $inputs, '');
            if ($emptyWarehouse) {
                //       ,_    /) (\    _,
                //        >>  <<,_,>>  <<
                //       //   _0.-.0_   \\
                //       \'._/       \_.'/
                //        '-.\.--.--./.-'
                //        __/ : :Y: : \ _
                //';,  .-(_| : : | : : |_)-.  ,:'
                //  \\/.'  |: : :|: : :|  `.\//
                //   (/    |: : :|: : :|    \)
                //         |: : :|: : :;
                //        /\ : : | : : /\
                //       (_/'.: :.: :.'\_)
                //        \\  `""`""`  //
                //         \\         //
                //          ':.     .:'

                $result.=$this->messages->getStyledMessage(__('Warehouse storage is empty'), 'warning');
            }
        }
        return ($result);
    }

    /**
     * Returns itemtype reservation interface
     * 
     * @param int $storageId
     * @param int $itemtypeId
     * 
     * @return string
     */
    public function reserveCreateForm($storageId, $itemtypeId) {
        $storageId = vf($storageId, 3);
        $itemtypeId = vf($itemtypeId, 3);
        $result = '';
        if (isset($this->allStorages[$storageId])) {
            if (isset($this->allItemTypes[$itemtypeId])) {
                if (!empty($this->activeEmployee)) {
                    $storageRemains = $this->remainsOnStorage($storageId);
                    if (isset($storageRemains[$itemtypeId])) {
                        $itemRemainsStorage = $storageRemains[$itemtypeId];
                    } else {
                        $itemRemainsStorage = 0;
                    }
                    $alreadyReserved = $this->reserveGet($storageId, $itemtypeId);
                    $itemtypeData = $this->allItemTypes[$itemtypeId];
                    $itemtypeName = $this->allItemTypeNames[$itemtypeId];
                    $itemtypeUnit = $this->unitTypes[$itemtypeData['unit']];

                    $inputs = wf_HiddenInput('newreserveitemtypeid', $itemtypeId);
                    $inputs.= wf_HiddenInput('newreservestorageid', $storageId);
                    $inputs.= wf_Selector('newreserveemployeeid', $this->activeEmployee, __('Worker'), '', true);
                    $inputs.= wf_TextInput('newreservecount', $itemtypeUnit . ' (' . ($itemRemainsStorage - $alreadyReserved) . ' ' . __('maximum') . ')', '', true, 5);
                    $inputs.= wf_Submit(__('Create'));

                    $form = wf_Form('', 'POST', $inputs, 'glamour');
                    $remainsText = __('At storage') . ' ' . $this->allStorages[$storageId] . ' ' . __('remains') . ' ' . $itemRemainsStorage . ' ' . $itemtypeUnit . ' ' . $itemtypeName;
                    $remainsInfo = $this->messages->getStyledMessage($remainsText, 'success');

                    if ($alreadyReserved) {
                        $remainsInfo.=$this->messages->getStyledMessage(__('minus') . ' ' . $alreadyReserved . ' ' . __('already reserved'), 'info');
                    }

                    $cells = wf_TableCell($form, '40%');
                    $cells.= wf_TableCell($remainsInfo, '', '', 'valign="top"');
                    $rows = wf_TableRow($cells, '');
                    $result = wf_TableBody($rows, '100%', 0, '');
                } else {
                    $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No available workers for reserve creation'), 'error');
                }
            } else {
                $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No existing warehouse item types'), 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('No existing warehouse storages'), 'error');
        }
        return ($result);
    }

    /**
     * Deletes existing reservation record from database
     * 
     * @param int $id
     * 
     * @return void
     */
    public function reserveDelete($id) {
        $id = vf($id, 3);
        if (isset($this->allReserve[$id])) {
            $reserveData = $this->allReserve[$id];
            if (!empty($reserveData)) {
                $this->reservePushLog('delete', $reserveData['storageid'], $reserveData['itemtypeid'], $reserveData['count'], $reserveData['employeeid'], $id);
            }
            $query = "DELETE from `wh_reserve` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register('WAREHOUSE RESERVE DELETE [' . $id . ']');
        }
    }

    /**
     * Returns reserve record editing form
     * 
     * @param int $id
     * 
     * @return string
     */
    public function reserveEditForm($id) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->allReserve[$id])) {
            $reserveData = $this->allReserve[$id];
            $reserveStorage = $reserveData['storageid'];
            @$itemName = $this->allItemTypeNames[$reserveData['itemtypeid']];
            @$itemData = $this->allItemTypes[$reserveData['itemtypeid']];
            @$itemUnit = $this->unitTypes[$itemData['unit']];

            $inputs = wf_Selector('editreserveemployeeid', $this->activeEmployee, __('Worker'), $reserveData['employeeid'], true);
            $inputs.= wf_TextInput('editreservecount', $itemUnit . ' ' . $itemName, $reserveData['count'], true, 5);
            $inputs.= wf_HiddenInput('editreserveid', $id);
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Drain items from some reserve. If items count less than zero - deletes reserve.
     * 
     * @param int $reserveId
     * @param float $count
     * 
     * @return void
     */
    protected function reserveDrain($reserveId, $count) {
        $reserveId = vf($reserveId, 3);
        if (isset($this->allReserve[$reserveId])) {
            $reserveData = $this->allReserve[$reserveId];
            $oldCount = $reserveData['count'];
            $newCount = $oldCount - $count;
            $newCountF = mysql_real_escape_string($newCount);
            $where = " WHERE `id`='" . $reserveId . "';";
            if ($newCountF > 0) {
                simple_update_field('wh_reserve', 'count', $newCountF, $where);
                log_register('WAREHOUSE RESERVE DRAIN [' . $reserveId . ']  COUNT `' . $newCount . '` EMPLOYEE [' . $reserveData['employeeid'] . ']');
                $this->reservePushLog('update', $reserveData['storageid'], $reserveData['itemtypeid'], $newCount, $reserveData['employeeid'], $reserveId);
            } else {
                $this->reserveDelete($reserveId);
            }
        }
    }

    /**
     * Saves reserve changes into database
     * 
     * @return void
     */
    public function reserveSave() {
        if (wf_CheckPost(array('editreserveid', 'editreserveemployeeid', 'editreservecount'))) {
            $id = vf($_POST['editreserveid'], 3);
            if (isset($this->allReserve[$id])) {
                $reserveData = $this->allReserve[$id];
                if (!empty($reserveData)) {
                    $reserveStorage = $reserveData['storageid'];
                    $reserveItemtypeId = $reserveData['itemtypeid'];
                    $count = $_POST['editreservecount'];
                    $countF = mysql_real_escape_string($count);
                    $countF = str_replace(',', '.', $countF);
                    $employeeId = vf($_POST['editreserveemployeeid'], 3);
                    $where = " WHERE `id`='" . $id . "';";
                    $storageRemains = $this->remainsOnStorage($reserveStorage);
                    @$itemtypeRemains = $storageRemains[$reserveItemtypeId];
                    if (empty($itemtypeRemains)) {
                        $itemtypeRemains = 0;
                    }
                    $alreadyReserved = $this->reserveGet($reserveStorage, $reserveData['itemtypeid']);
                    $realRemains = $itemtypeRemains - $alreadyReserved;
                    $controlRemains = $realRemains + $reserveData['count'];
                    if ($controlRemains >= $countF) {
                        simple_update_field('wh_reserve', 'employeeid', $employeeId, $where);
                        simple_update_field('wh_reserve', 'count', $countF, $where);
                        log_register('WAREHOUSE RESERVE EDIT [' . $id . ']  COUNT `' . $count . '` EMPLOYEE [' . $employeeId . ']');
                        $this->reservePushLog('update', $reserveStorage, $reserveItemtypeId, $count, $employeeId, $id);
                    } else {
                        log_register('WAREHOUSE RESERVE FAIL [' . $id . ']  TO MANY  COUNT `' . $count . '` EMPLOYEE [' . $employeeId . ']');
                    }
                } else {
                    log_register('WAREHOUSE RESERVE FAIL [' . $id . ']  EMPTY DATA');
                }
            }
        }
    }

    /**
     * Returns reserve creation date extracted from log if exists
     * 
     * @param int $resId
     * 
     * @return string
     */
    protected function reserveGetCreationDate($resId) {
        $result = __('Nothing');
        if (isset($this->allReserveCreationDates[$resId])) {
            $result = $this->allReserveCreationDates[$resId];
        }
        return ($result);
    }

    /**
     * Renders list of available reserved items sorted by Employee with some controls
     * 
     * @return string
     */
    public function reserveRenderList() {
        $result = '';
        $printFlag = (wf_CheckGet(array('printable'))) ? true : false;
        if (!empty($this->allReserve)) {
            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Creation date'));
            $cells.=wf_TableCell(__('Warehouse storage'));
            $cells.=wf_TableCell(__('Category'));
            $cells.= wf_TableCell(__('Warehouse item type'));
            $cells.= wf_TableCell(__('Count'));
            $cells.= wf_TableCell(__('Worker'));
            if (!$printFlag) {
                $cells.= wf_TableCell(__('Actions'));
            }

            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allReserve as $io => $each) {
                $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], @$this->allItemTypeNames[$each['itemtypeid']]);
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell($this->reserveGetCreationDate($each['id']));
                $cells.=wf_TableCell(@$this->allStorages[$each['storageid']]);
                $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                $cells.= wf_TableCell($itemTypeLink);
                $cells.= wf_TableCell($each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']]);
                $cells.= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                if (!$printFlag) {
                    $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_RESERVE . '&deletereserve=' . $each['id'], web_delete_icon(), $this->messages->getEditAlert()) . ' ';
                    $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . __('Reservation'), $this->reserveEditForm($each['id']), '') . ' ';
                    if ($each['count'] > 0) {
                        if (cfr('WAREHOUSEOUTRESERVE')) {
                            $outcomeUrl = self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $each['storageid'] . '&outitemid=' . $each['itemtypeid'] . '&reserveid=' . $each['id'];
                            $actLinks.=wf_Link($outcomeUrl, wf_img('skins/whoutcoming_icon.png') . ' ' . __('Outcoming'), false, '');
                        }
                    }
                    $cells.= wf_TableCell($actLinks);
                }
                $rows.= wf_TableRow($cells, 'row5');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        if ($printFlag) {
            $this->reportPrintable(__('Reserved'), $result);
        } else {
            return ($result);
        }
    }

    /**
     * Renders json list of available reservation history log entries
     * 
     * @return void
     */
    public function reserveHistoryAjaxReply() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allReserveHistory)) {

            $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            foreach ($this->allReserveHistory as $io => $each) {
                $operationType = '';
                $administratorName = (isset($employeeLogins[$each['admin']])) ? $employeeLogins[$each['admin']] : $each['admin'];
                switch ($each['type']) {
                    case 'create':
                        $operationType = __('Created');
                        break;
                    case 'update':
                        $operationType = __('Updated');
                        break;
                    case 'delete':
                        $operationType = __('Deleted');
                        break;
                }

                if (!empty($each['resid'])) {
                    $resIdLabel = __('Reserve') . '@' . $each['resid'];
                } else {
                    $resIdLabel = '';
                }

                $data[] = $resIdLabel;
                $data[] = $each['date'];
                $data[] = $operationType;
                $data[] = @$this->allStorages[$each['storageid']];
                $data[] = @$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']];
                $data[] = wf_link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], @$this->allItemTypeNames[$each['itemtypeid']]);
                $data[] = $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                $data[] = @$this->allEmployee[$each['employeeid']];
                $data[] = $administratorName;


                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Renders reserve history print filtering form
     * 
     * @return string
     */
    public function reserveHistoryFilterForm() {
        $result = '';
        $inputs = __('From') . ' ' . wf_DatePickerPreset('reshistfilterfrom', date("Y-m") . '-01') . ' ';
        $inputs.= __('To') . ' ' . wf_DatePickerPreset('reshistfilterto', curdate()) . ' ';
        $inputs.= wf_Selector('reshistfilteremployeeid', $this->activeEmployee, __('Worker'), '', false);
        $inputs.= wf_Submit(__('Print'));

        $result.=wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders printable report of reserve operations history
     * 
     * @return void
     */
    public function reserveHistoryPrintFiltered() {
        $result = '';
        if (wf_CheckPost(array('reshistfilterfrom', 'reshistfilterto', 'reshistfilteremployeeid'))) {
            $dateFrom = $_POST['reshistfilterfrom'];
            $dateTo = $_POST['reshistfilterto'];
            $employeeId = vf($_POST['reshistfilteremployeeid'], 3);
            if (zb_checkDate($dateFrom) AND zb_checkDate($dateTo)) {
                $dateFrom = $dateFrom . ' 00:00:00';
                $dateTo = $dateTo . ' 23:59:59';
                $dateFrom = strtotime($dateFrom);
                $dateTo = strtotime($dateTo);

                if (!empty($this->allReserveHistory)) {
                    $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());

                    $cells = wf_TableCell(__('ID'));
                    $cells.= wf_TableCell(__('Date'));
                    $cells.= wf_TableCell(__('Type'));
                    $cells.= wf_TableCell(__('Warehouse storage'));
                    $cells.= wf_TableCell(__('Category'));
                    $cells.= wf_TableCell(__('Warehouse item type'));
                    $cells.= wf_TableCell(__('Count'));
                    $cells.= wf_TableCell(__('Employee'));
                    $cells.= wf_TableCell(__('Admin'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($this->allReserveHistory as $io => $each) {
                        $operationDate = strtotime($each['date']);
                        $filteredFlag = false;

                        //data filtering
                        if ($employeeId == $each['employeeid']) {
                            if ($operationDate >= $dateFrom AND $operationDate <= $dateTo) {
                                $filteredFlag = true;
                            }
                        }

                        //report assembly
                        if ($filteredFlag) {
                            $operationType = '';
                            $administratorName = (isset($employeeLogins[$each['admin']])) ? $employeeLogins[$each['admin']] : $each['admin'];
                            switch ($each['type']) {
                                case 'create':
                                    $operationType = __('Created');
                                    break;
                                case 'update':
                                    $operationType = __('Updated');
                                    break;
                                case 'delete':
                                    $operationType = __('Deleted');
                                    break;
                            }

                            if (!empty($each['resid'])) {
                                $resIdLabel = __('Reserve') . '@' . $each['resid'];
                            } else {
                                $resIdLabel = '';
                            }

                            $cells = wf_TableCell($resIdLabel);
                            $cells.= wf_TableCell($each['date']);
                            $cells.= wf_TableCell($operationType);
                            $cells.= wf_TableCell(@$this->allStorages[$each['storageid']]);
                            $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                            $cells.= wf_TableCell(@$this->allItemTypeNames[$each['itemtypeid']]);
                            $cells.= wf_TableCell($each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']]);
                            $cells.= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                            $cells.= wf_TableCell($administratorName);
                            $rows.= wf_TableRow($cells, 'row3');
                        }
                    }

                    $result.=wf_TableBody($rows, '100%', 0, 'sortable');

                    $this->reportPrintable(__('History'), $result);
                } else {
                    show_warning(__('Nothing to show'));
                }
            } else {
                show_error(__('Wrong date format'));
            }
        }
    }

    /**
     * Renders reservation history log
     * 
     * @return string
     */
    public function reserveRenderHistory() {
        $result = '';
        if (!empty($this->allReserveHistory)) {
            $colums = array('ID', 'Date', 'Type', 'Warehouse storage', 'Category', 'Warehouse item type', 'Count', 'Employee', 'Admin');
            $opts = '"order": [[ 0, "desc" ]]';
            $ajaxUrl = self::URL_ME . '&' . self::URL_RESERVE . '&reshistajlist=true';
            $result.= wf_JqDtLoader($colums, $ajaxUrl, false, __('Reserve'), 10, $opts);
            if (!empty($this->allReserveHistory)) {
                $result.=wf_delimiter();
                $result.=$this->reserveHistoryFilterForm();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }
        return ($result);
    }

    /**
     * Returns cetegory creation form
     * 
     * @return string
     */
    public function categoriesCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newcategory', __('Name'), '', false, 20);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form(self::URL_ME . '&' . self::URL_CATEGORIES, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns cetegory edit form
     * 
     * @param int $categoryId
     * 
     * @return string
     */
    protected function categoriesEditForm($categoryId) {
        $result = '';
        $inputs = wf_TextInput('editcategoryname', __('Name'), $this->allCategories[$categoryId], false, 20);
        $inputs.= wf_HiddenInput('editcategoryid', $categoryId);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form(self::URL_ME . '&' . self::URL_CATEGORIES, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders list of available categories with some controls
     * 
     * @return string
     */
    public function categoriesRenderList() {
        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allCategories)) {
            foreach ($this->allCategories as $id => $name) {
                $cells = wf_TableCell($id);
                $cells.= wf_TableCell($name);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_CATEGORIES . '&deletecategory=' . $id, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->categoriesEditForm($id));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Creates new category in database
     * 
     * @param string $name
     * 
     * @return void
     */
    public function categoriesCreate($name) {
        $nameF = mysql_real_escape_string($name);
        $query = "INSERT INTO `wh_categories` (`id`,`name`) VALUES (NULL,'" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('wh_categories');
        log_register('WAREHOUSE CATEGORY ADD [' . $newId . '] `' . $name . '`');
    }

    /**
     * Check is category used by some items?
     * 
     * @param int $categoryId
     * 
     * @return bool
     */
    protected function categoryProtected($categoryId) {
        $categoryId = vf($categoryId, 3);
        $result = false;
        if (!empty($this->allItemTypes)) {
            foreach ($this->allItemTypes as $io => $each) {
                if ($each['categoryid'] == $categoryId) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes existging category from database
     * 
     * @param int $categoryId
     * 
     * @return bool
     */
    public function categoriesDelete($categoryId) {

        if (isset($this->allCategories[$categoryId])) {
            if (!$this->categoryProtected($categoryId)) {
                $query = "DELETE from `wh_categories` WHERE `id`='" . $categoryId . "';";
                nr_query($query);
                log_register('WAREHOUSE CATEGORY DELETE [' . $categoryId . ']');
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Saves category changes in database by data recieved from form
     * 
     * @return void
     */
    public function categoriesSave() {
        if (wf_CheckPost(array('editcategoryname', 'editcategoryid'))) {
            $categoryId = vf($_POST['editcategoryid']);
            if (isset($this->allCategories[$categoryId])) {
                simple_update_field('wh_categories', 'name', $_POST['editcategoryname'], "WHERE `id`='" . $categoryId . "'");
                log_register('WAREHOUSE CATEGORY EDIT [' . $categoryId . '] `' . $_POST['editcategoryname'] . '`');
            } else {
                log_register('WAREHOUSE CATEGORY EDIT FAIL [' . $categoryId . '] NO_EXISTING');
            }
        }
    }

    /**
     * Renders default back control
     * 
     * @param string $url Optional URL
     * 
     * @return void
     */
    public function backControl($url = '') {
        if (empty($url)) {
            show_window('', wf_BackLink(self::URL_ME));
        } else {
            show_window('', wf_BackLink($url));
        }
    }

    /**
     * Renders control panel for whole module
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result.= wf_Link(self::URL_ME . '&' . self::URL_REPORTS . '&' . 'totalremains=true', wf_img_sized('skins/whstorage_icon.png') . ' ' . __('The remains in all storages'), false, 'ubButton');
        if (cfr('WAREHOUSEIN')) {
            $result.= wf_Link(self::URL_ME . '&' . self::URL_IN, wf_img_sized('skins/whincoming_icon.png') . ' ' . __('Incoming operations'), false, 'ubButton');
        }
        if ((cfr('WAREHOUSEOUT')) OR ( cfr('WAREHOUSEOUTRESERVE'))) {
            $result.= wf_Link(self::URL_ME . '&' . self::URL_OUT, wf_img_sized('skins/whoutcoming_icon.png') . ' ' . __('Outcoming operations'), false, 'ubButton');
        }

        if (cfr('WAREHOUSERESERVE')) {
            $result.=wf_Link(self::URL_ME . '&' . self::URL_RESERVE, wf_img('skins/whreservation.png') . ' ' . __('Reserved'), false, 'ubButton');
        }

        $dirControls = wf_Link(self::URL_ME . '&' . self::URL_CATEGORIES, wf_img_sized('skins/categories_icon.png') . ' ' . __('Warehouse categories'), false, 'ubButton');
        $dirControls.= wf_Link(self::URL_ME . '&' . self::URL_ITEMTYPES, wf_img_sized('skins/folder_icon.png') . ' ' . __('Warehouse item types'), false, 'ubButton');
        $dirControls.= wf_Link(self::URL_ME . '&' . self::URL_STORAGES, wf_img_sized('skins/whstorage_icon.png') . ' ' . __('Warehouse storages'), false, 'ubButton');
        $dirControls.= wf_Link(self::URL_ME . '&' . self::URL_CONTRACTORS, wf_img_sized('skins/whcontractor_icon.png') . ' ' . __('Contractors'), false, 'ubButton');
        if (cfr('WAREHOUSEDIR')) {
            $result.=wf_modalAuto(web_icon_extended() . ' ' . __('Directories'), __('Directories'), $dirControls, 'ubButton');
        }
        return ($result);
    }

    /**
     * Returns item types creation form
     * 
     * @return string
     */
    public function itemtypesCreateForm() {
        $result = '';
        if (!empty($this->allCategories)) {
            $inputs = wf_Selector('newitemtypecetegoryid', $this->allCategories, __('Category'), '', true);
            $inputs.= wf_TextInput('newitemtypename', __('Name'), '', true, '20');
            $inputs.= wf_Selector('newitemtypeunit', $this->unitTypes, __('Units'), '', true);
            $inputs.= wf_TextInput('newitemtypereserve', __('Desired reserve'), '', true, 5);
            $inputs.= wf_Submit(__('Create'));

            $result = wf_Form(self::URL_ME . '&' . self::URL_ITEMTYPES, 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('No existing categories'), 'warning');
        }
        return ($result);
    }

    /**
     * Returns item types editing form
     * 
     * @return string
     */
    public function itemtypesEditForm($itemtypeId) {
        $result = '';
        if (isset($this->allItemTypes[$itemtypeId])) {
            $itemtypeData = $this->allItemTypes[$itemtypeId];
            $inputs = wf_Selector('edititemtypecetegoryid', $this->allCategories, __('Category'), $itemtypeData['categoryid'], true);
            $inputs.= wf_TextInput('edititemtypename', __('Name'), $itemtypeData['name'], true, '20');
            $inputs.= wf_Selector('edititemtypeunit', $this->unitTypes, __('Units'), $itemtypeData['unit'], true);
            $inputs.= wf_TextInput('edititemtypereserve', __('Desired reserve'), $itemtypeData['reserve'], true, 5);
            $inputs.= wf_HiddenInput('edititemtypeid', $itemtypeId);
            $inputs.= wf_Submit(__('Save'));

            $result = wf_Form(self::URL_ME . '&' . self::URL_ITEMTYPES, 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Saves item type changes in database by data recieved from form
     * 
     * @return void
     */
    public function itemtypesSave() {
        if (wf_CheckPost(array('edititemtypeid', 'edititemtypename', 'edititemtypecetegoryid', 'edititemtypeunit'))) {
            $itemtypeId = vf($_POST['edititemtypeid']);
            if (isset($this->allItemTypes[$itemtypeId])) {
                $where = " WHERE `id`='" . $itemtypeId . "'";
                simple_update_field('wh_itemtypes', 'categoryid', $_POST['edititemtypecetegoryid'], $where);
                simple_update_field('wh_itemtypes', 'name', $_POST['edititemtypename'], $where);
                simple_update_field('wh_itemtypes', 'unit', $_POST['edititemtypeunit'], $where);
                if (isset($_POST['edititemtypereserve'])) {
                    $unit = str_replace(',', '.', $_POST['edititemtypereserve']);
                    simple_update_field('wh_itemtypes', 'reserve', $unit, $where);
                }
                log_register('WAREHOUSE ITEMTYPES EDIT [' . $itemtypeId . '] `' . $_POST['edititemtypename'] . '`');
            } else {
                log_register('WAREHOUSE ITEMTYPES EDIT FAIL [' . $itemtypeId . '] NO_EXISTING');
            }
        }
    }

    /**
     * Creates new items type
     * 
     * @param int $categoryid
     * @param string $name
     * @param string $unit
     * @param float $reserve
     * 
     * @return void
     */
    public function itemtypesCreate($categoryid, $name, $unit, $reserve = 0) {
        $categoryid = vf($categoryid, 3);
        if (isset($this->allCategories[$categoryid])) {
            $nameF = mysql_real_escape_string($name);
            $unit = mysql_real_escape_string($unit);
            $reserve = str_replace(',', '.', $reserve);
            $reserve = str_replace('-', '', $reserve);
            $reserve = mysql_real_escape_string($reserve);

            $query = "INSERT INTO `wh_itemtypes` (`id`,`categoryid`,`name`,`unit`,`reserve`) VALUES "
                    . "(NULL,'" . $categoryid . "','" . $nameF . "','" . $unit . "','" . $reserve . "')";
            nr_query($query);
            $newId = simple_get_lastid('wh_itemtypes');
            log_register('WAREHOUSE ITEMTYPES CREATE [' . $newId . '] `' . $name . '`');
        } else {
            log_register('WAREHOUSE ITEMTYPES CREATE FAIL NO_CATEGORY');
        }
    }

    /**
     * Renders of available warehouse item types
     * 
     * @return string
     */
    public function itemtypesRenderList() {
        $result = '';
        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Category'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Units'));
        $cells.= wf_TableCell(__('Reserve'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        $photoStorageEnabled = ($this->altCfg['PHOTOSTORAGE_ENABLED']) ? true : false;

        if (!empty($this->allItemTypes)) {
            foreach ($this->allItemTypes as $io => $each) {
                $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['id'], $each['name']);

                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell(@$this->allCategories[$each['categoryid']]);
                $cells.= wf_TableCell($itemTypeLink);
                $cells.= wf_TableCell(@$this->unitTypes[$each['unit']]);
                $cells.= wf_TableCell($each['reserve']);
                $actLinks = wf_JSAlertStyled(self::URL_ME . '&' . self::URL_ITEMTYPES . '&deleteitemtype=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->itemtypesEditForm($each['id']), '');
                if ($photoStorageEnabled) {
                    $photostorageUrl = '?module=photostorage&scope=WAREHOUSEITEMTYPE&itemid=' . $each['id'] . '&mode=list';
                    $photostorageControl = ' ' . wf_Link($photostorageUrl, wf_img_sized('skins/photostorage.png', __('Upload images'), '16', '16'), false);
                } else {
                    $photostorageControl = '';
                }
                $actLinks.=$photostorageControl;

                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');

        return ($result);
    }

    /**
     * Checks is itemtype protected by some existing operations?
     * 
     * @param type $itemtypeId
     * @return bool
     */
    protected function itemtypeProtected($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        $result = false;
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                if ($each['itemtypeid'] == $itemtypeId) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes items type by its ID
     * 
     * 
     * @param int $itemtypeId
     * 
     * @return bool
     */
    public function itemtypesDelete($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        if (!$this->itemtypeProtected($itemtypeId)) {
            $query = "DELETE from `wh_itemtypes` WHERE `id`='" . $itemtypeId . "';";
            nr_query($query);
            log_register('WAREHOUSE ITEMTYPES DELETE [' . $itemtypeId . ']');
            $result = true;
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Returns itemtype name by its ID
     * 
     * @param int $itemtypeId
     * @return string
     */
    public function itemtypeGetName($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        $result = '';
        if (isset($this->allItemTypeNames[$itemtypeId])) {
            $result = $this->allItemTypeNames[$itemtypeId];
        }
        return ($result);
    }

    /**
     * Returns item type count unit
     * 
     * @param int $itemtypeId
     * 
     * @return string
     */
    public function itemtypeGetUnit($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        $result = '';
        $result = @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']];
        return ($result);
    }

    /**
     * Returns item type count unit
     * 
     * @param int $itemtypeId
     * 
     * @return string
     */
    public function itemtypeGetCategory($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        $result = '';
        $result = @$this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']];
        return ($result);
    }

    /**
     * Returns storage creation form
     * 
     * @return string
     */
    public function storagesCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newstorage', __('Name'), '', false, 20);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form(self::URL_ME . '&' . self::URL_STORAGES, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new storage in database
     * 
     * @param string $name
     * 
     * @return void
     */
    public function storagesCreate($name) {
        $nameF = mysql_real_escape_string($name);
        $query = "INSERT INTO `wh_storages` (`id`,`name`) VALUES (NULL,'" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('wh_storages');
        log_register('WAREHOUSE STORAGES ADD [' . $newId . '] `' . $name . '`');
    }

    /**
     * Check is storage used by some incoming operations?
     * 
     * @param int $storageId
     * 
     * @return bool
     */
    protected function storageProtected($storageId) {
        $storageId = vf($storageId, 3);
        $result = false;
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                if ($each['storageid'] == $storageId) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Returns storage name by its ID
     * 
     * @param int $storageId
     * @return string
     */
    public function storageGetName($storageId) {
        $storageId = vf($storageId, 3);
        $result = '';
        if (isset($this->allStorages[$storageId])) {
            $result = $this->allStorages[$storageId];
        }
        return ($result);
    }

    /**
     * Deletes existing storage from database
     * 
     * @param int $storageId
     * 
     * @return bool
     */
    public function storagesDelete($storageId) {
        $storageId = vf($storageId);
        if (isset($this->allStorages[$storageId])) {
            if (!$this->storageProtected($storageId)) {
                $query = "DELETE from `wh_storages` WHERE `id`='" . $storageId . "';";
                nr_query($query);
                log_register('WAREHOUSE STORAGES DELETE [' . $storageId . ']');
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Returns storages edit form
     * 
     * @param int $storageId
     * 
     * @return string
     */
    protected function storagesEditForm($storageId) {
        $result = '';
        $inputs = wf_TextInput('editstoragename', __('Name'), $this->allStorages[$storageId], false, 20);
        $inputs.= wf_HiddenInput('editstorageid', $storageId);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form(self::URL_ME . '&' . self::URL_STORAGES, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Saves storage changes in database by data recieved from form
     * 
     * @return void
     */
    public function storagesSave() {
        if (wf_CheckPost(array('editstoragename', 'editstorageid'))) {
            $storageId = vf($_POST['editstorageid']);
            if (isset($this->allStorages[$storageId])) {
                simple_update_field('wh_storages', 'name', $_POST['editstoragename'], "WHERE `id`='" . $storageId . "'");
                log_register('WAREHOUSE STORAGE EDIT [' . $storageId . '] `' . $_POST['editstoragename'] . '`');
            } else {
                log_register('WAREHOUSE STORAGE EDIT FAIL [' . $storageId . '] NO_EXISTING');
            }
        }
    }

    /**
     * Renders list of available storages with some controls
     * 
     * @return string
     */
    public function storagesRenderList() {
        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $id => $name) {
                $cells = wf_TableCell($id);
                $cells.= wf_TableCell($name);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_STORAGES . '&deletestorage=' . $id, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->storagesEditForm($id));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
      I count the falling tears
      They fall before my eyes
      Seems like a thousand years
      Since we broke the ties
      I call you on the phone
      But never get a rise
      So sit there all alone
      It's time you realize
     */

    /**
     * Returns contractor creation form
     * 
     * @return string
     */
    public function contractorsCreateForm() {
        $result = '';
        $inputs = wf_TextInput('newcontractor', __('Name'), '', false, 20);
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form(self::URL_ME . '&' . self::URL_CONTRACTORS, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new contractor in database
     * 
     * @param string $name
     * 
     * @return void
     */
    public function contractorCreate($name) {
        $nameF = mysql_real_escape_string($name);
        $query = "INSERT INTO `wh_contractors` (`id`,`name`) VALUES (NULL,'" . $nameF . "');";
        nr_query($query);
        $newId = simple_get_lastid('wh_contractors');
        log_register('WAREHOUSE CONTRACTORS ADD [' . $newId . '] `' . $name . '`');
    }

    /**
     * Check is contractor used by some incoming operations?
     * 
     * @param int $contractorId 
     * 
     * @return bool
     */
    protected function contractorProtected($contractorId) {
        $contractorId = vf($contractorId, 3);
        $result = false;
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                if ($each['contractorid'] == $contractorId) {
                    $result = true;
                    break;
                }
            }
        }
        return ($result);
    }

    /**
     * Deletes existing contractor from database
     * 
     * @param int $contractorId
     * 
     * @return bool
     */
    public function contractorsDelete($contractorId) {
        $contractorId = vf($contractorId);
        if (isset($this->allContractors[$contractorId])) {
            if (!$this->contractorProtected($contractorId)) {
                $query = "DELETE from `wh_contractors` WHERE `id`='" . $contractorId . "';";
                nr_query($query);
                log_register('WAREHOUSE CONTRACTORS DELETE [' . $contractorId . ']');
                $result = true;
            } else {
                $result = false;
            }
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Returns contractors edit form
     * 
     * @param int $contractorId
     * 
     * @return string
     */
    protected function contractorsEditForm($contractorId) {
        $result = '';
        $inputs = wf_TextInput('editcontractorname', __('Name'), $this->allContractors[$contractorId], false, 20);
        $inputs.= wf_HiddenInput('editcontractorid', $contractorId);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form(self::URL_ME . '&' . self::URL_CONTRACTORS, 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Saves contractor changes in database by data recieved from form
     * 
     * @return void
     */
    public function contractorsSave() {
        if (wf_CheckPost(array('editcontractorname', 'editcontractorid'))) {
            $contractorId = vf($_POST['editcontractorid'], 3);
            if (isset($this->allContractors[$contractorId])) {
                simple_update_field('wh_contractors', 'name', $_POST['editcontractorname'], "WHERE `id`='" . $contractorId . "'");
                log_register('WAREHOUSE CONTRACTORS EDIT [' . $contractorId . '] `' . $_POST['editcontractorname'] . '`');
            } else {
                log_register('WAREHOUSE CONTRACTORS EDIT FAIL [' . $contractorId . '] NO_EXISTING');
            }
        }
    }

    /**
     * Renders list of available contractors with some controls
     * 
     * @return string
     */
    public function contractorsRenderList() {
        $result = '';

        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Name'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allContractors)) {
            foreach ($this->allContractors as $id => $name) {
                $cells = wf_TableCell($id);
                $cells.= wf_TableCell($name);

                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_CONTRACTORS . '&deletecontractor=' . $id, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->contractorsEditForm($id));
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }

        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Performs itemtypes per category filtering. Returns array id=>name
     * 
     * @param int $categoryId
     * @return array
     */
    protected function itemtypesFilterCategory($categoryId) {
        $result = array();
        if (!empty($this->allItemTypes)) {
            foreach ($this->allItemTypes as $io => $each) {
                if ($each['categoryid'] == $categoryId) {
                    $result[$each['id']] = $each['name'];
                }
            }
        }
        return ($result);
    }

    /**
     * Returns itemtype selector filtered by category
     * 
     * @param string $name
     * @param int $categoryId
     * @return string
     */
    public function itemtypesCategorySelector($name, $categoryId) {
        $result = '';
        $categoryItemtypes = $this->itemtypesFilterCategory($categoryId);
        $result = wf_Selector($name, $categoryItemtypes, __('Warehouse item types'), '', false);
        if (cfr('WAREHOUSEDIR')) {
            $result.= wf_Link(self::URL_ME . '&' . self::URL_ITEMTYPES, wf_img_sized('skins/folder_icon.png', '', '10', '10'), false);
        }
        return ($result);
    }

    /**
     * Returns incoming operation creation form
     * 
     * @return string
     */
    public function incomingCreateForm() {
        if ((!empty($this->allItemTypes)) AND ( !empty($this->allCategories)) AND ( !empty($this->allContractors)) AND ( !empty($this->allStorages))) {
            //ajax selector URL-s preprocessing
            $tmpCat = array();
            $firstCateKey = key($this->allCategories);
            foreach ($this->allCategories as $categoryId => $categoryName) {
                $tmpCat[self::URL_ME . '&' . self::URL_IN . '&' . self::URL_AJITSELECTOR . $categoryId] = $categoryName;
            }
            $result = wf_AjaxLoader();
            $inputs = wf_DatePickerPreset('newindate', curdate());
            $inputs.= wf_tag('br');
            $inputs.= wf_AjaxSelectorAC('ajItemtypesContainer', $tmpCat, __('Warehouse categories'), '', false);
            if (cfr('WAREHOUSEDIR')) {
                $inputs.= wf_Link(self::URL_ME . '&' . self::URL_CATEGORIES, wf_img_sized('skins/categories_icon.png', '', '10', '10'), false);
            }
            $inputs.= wf_tag('br');
            $inputs.= wf_AjaxContainer('ajItemtypesContainer', '', $this->itemtypesCategorySelector('newinitemtypeid', $firstCateKey));
            $inputs.= wf_Selector('newincontractorid', $this->allContractors, __('Contractor'), '', false);
            if (cfr('WAREHOUSEDIR')) {
                $inputs.= wf_Link(self::URL_ME . '&' . self::URL_CONTRACTORS, wf_img_sized('skins/whcontractor_icon.png', '', '10', '10'), false);
            }
            $inputs.= wf_tag('br');
            $inputs.= wf_Selector('newinstorageid', $this->allStorages, __('Warehouse storage'), '', false);
            if (cfr('WAREHOUSEDIR')) {
                $inputs.= wf_Link(self::URL_ME . '&' . self::URL_STORAGES, wf_img_sized('skins/whstorage_icon.png', '', '10', '10'), false);
            }
            $inputs.= wf_tag('br');
            $inputs.= wf_TextInput('newincount', __('Count'), '', false, 5);
            $inputs.= wf_tag('br');
            $inputs.= wf_TextInput('newinprice', __('Price per unit'), '', false, 5);
            $inputs.= wf_tag('br');
            $inputs.= wf_TextInput('newinbarcode', __('Barcode'), '', false, 15);
            $inputs.= wf_tag('br');
            $inputs.= wf_TextInput('newinnotes', __('Notes'), '', false, 30);
            $inputs.= wf_tag('br');
            $inputs.= wf_Submit(__('Create'));
            $result.= wf_Form(self::URL_ME . '&' . self::URL_IN, 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('You did not fill all the necessary directories'), 'error');
        }
        return ($result);
    }

    /**
     * Creates new incoming operation in database
     * 
     * @param string $date
     * @param int    $itemtypeid
     * @param int    $contractorid
     * @param int    $storageid
     * @param float  $count
     * @param float  $price
     * @param string $barcode
     * @param string $notes
     * 
     * @return void
     */
    public function incomingCreate($date, $itemtypeid, $contractorid, $storageid, $count, $price, $barcode, $notes) {
        $dateF = mysql_real_escape_string($date);
        $itemtypeid = vf($itemtypeid, 3);
        $contractorid = vf($contractorid, 3);
        $storageid = vf($storageid);
        $countF = str_replace(',', '.', $count);
        $countF = str_replace('-', '', $countF);
        $countF = mysql_real_escape_string($countF);
        $priceF = str_replace(',', '.', $price);
        $priceF = mysql_real_escape_string($priceF);
        $notes = mysql_real_escape_string($notes);
        $barcode = mysql_real_escape_string($barcode);
        $admin = mysql_real_escape_string(whoami());

        $query = "INSERT INTO `wh_in` (`id`, `date`, `itemtypeid`, `contractorid`, `count`, `barcode`, `price`, `storageid`, `notes`,`admin`) "
                . "VALUES (NULL, '" . $dateF . "', '" . $itemtypeid . "', '" . $contractorid . "', '" . $countF . "', '" . $barcode . "', '" . $priceF . "', '" . $storageid . "', '" . $notes . "','" . $admin . "');";
        nr_query($query);
        $newId = simple_get_lastid('wh_in');
        log_register('WAREHOUSE INCOME CREATE [' . $newId . '] ITEM [' . $itemtypeid . '] COUNT `' . $count . '` PRICE `' . $price . '`');
    }

    /**
     * Returns income operations list available at storages
     * 
     * @return string
     */
    public function incomingOperationsList() {
        $result = '';
        if (!empty($this->allIncoming)) {
            $opts = '"order": [[ 0, "desc" ]]';
            $columns = array('ID', 'Date', 'Category', 'Warehouse item types', 'Count', 'Price per unit', 'Sum', 'Warehouse storage', 'Notes', 'Actions');
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_IN . '&' . self::URL_INAJLIST, false, 'Incoming operations', 50, $opts);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Returns JQuery datatables reply for incoming operations list
     * 
     * @return string
     */
    public function incomingListAjaxReply() {
        $json = new wf_JqDtHelper();
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $each['id'], wf_img_sized('skins/whincoming_icon.png', '', '10', '10') . ' ' . __('Show'));
                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = @$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']];
                $data[] = wf_link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], $this->allItemTypeNames[$each['itemtypeid']]);
                $data[] = $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                $data[] = $each['price'];
                $data[] = ($each['price'] * $each['count']);
                $data[] = @$this->allStorages[$each['storageid']];
                $data[] = $each['notes'];
                $data[] = $actLink;
                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Returns default contol for QR code view interface
     * 
     * @param string $type
     * @param int $id
     * @return string
     */
    protected function qrControl($type, $id) {
        $result = '';
        $qrUrl = self::URL_ME . '&' . self::URL_VIEWERS . '&qrcode=' . $type . '&renderid=' . $id;
        $result = wf_modalAuto(wf_img_sized('skins/qrcode.png', __('QR code'), '16', '16'), __('QR code'), wf_img($qrUrl), '');
        return ($result);
    }

    /**
     * Renders incoming operation view interface
     * 
     * @param int $id
     * @return string
     */
    public function incomingView($id) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->allIncoming[$id])) {
            $operationData = $this->allIncoming[$id];

            $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            $administratorName = (isset($employeeLogins[$operationData['admin']])) ? $employeeLogins[$operationData['admin']] : $operationData['admin'];

            $cells = wf_TableCell(__('ID') . ' ' . $this->qrControl('in', $id), '30%', 'row2');
            $cells.= wf_TableCell($id);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Date'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['date']);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Contractor'), '30%', 'row2');
            //storage movement
            if ($operationData['contractorid'] == 0) {
                $contractorName = $operationData['notes'];
            } else {
                $contractorName = @$this->allContractors[$operationData['contractorid']];
            }

            $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $operationData['itemtypeid'], @$this->allItemTypeNames[$operationData['itemtypeid']]);

            $cells.= wf_TableCell($contractorName);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Category'), '30%', 'row2');
            $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$operationData['itemtypeid']]['categoryid']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse item type'), '30%', 'row2');
            $cells.= wf_TableCell($itemTypeLink);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Count'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['count'] . ' ' . $this->unitTypes[$this->allItemTypes[$operationData['itemtypeid']]['unit']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Price per unit'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['price']);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Sum'), '30%', 'row2');
            $cells.= wf_TableCell(($operationData['price'] * $operationData['count']));
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse storage'), '30%', 'row2');
            $cells.= wf_TableCell($this->allStorages[$operationData['storageid']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Barcode'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['barcode']);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Worker'), '30%', 'row2');
            $cells.= wf_TableCell($administratorName);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Notes'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['notes']);
            $rows.= wf_TableRow($cells, 'row3');

            $result.=wf_TableBody($rows, '100%', 0, 'wh_viewer');

            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $operationData['itemtypeid']);
                $result.=$photoStorage->renderImagesRaw();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Strange exeption') . ' NO_EXISTING_INCOME_ID', 'error');
        }

        //ADcomments support
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('WAREHOUSEINCOME');
            $result.=wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
            $result.=$adcomments->renderComments($id);
        }

        return ($result);
    }

    /**
     * Returns outcoming operation creation form
     * 
     * @return string
     */
    public function outcomingStoragesList() {
        $result = '';
        if (!empty($this->allStorages)) {

            $cells = wf_TableCell(__('Warehouse storage'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allStorages as $io => $each) {
                $conrolLink = wf_Link(self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $io, $each, false, '');
                $cells = wf_TableCell($conrolLink);
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result = $this->messages->getStyledMessage(__('You did not fill all the necessary directories'), 'error');
        }
        return ($result);
    }

    /**
     * Returns array of items remains on some storage
     * 
     * @param int $storageId
     * 
     * @return array 
     */
    protected function remainsOnStorage($storageId) {
        $storageId = vf($storageId, 3);
        $result = array();
        //counting income operations
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                if ($each['storageid'] == $storageId) {
                    if (isset($result[$each['itemtypeid']])) {
                        $result[$each['itemtypeid']]+=$each['count'];
                    } else {
                        $result[$each['itemtypeid']] = $each['count'];
                    }
                }
            }
        }

        //counting outcome operations
        if (!empty($this->allOutcoming)) {
            foreach ($this->allOutcoming as $io => $each) {
                if ($each['storageid'] == $storageId) {
                    if (isset($result[$each['itemtypeid']])) {
                        $result[$each['itemtypeid']]-=$each['count'];
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Returns array of all itemtypes available on all storages
     * 
     * @return array
     */
    public function remainsAll() {
        $result = array();
        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $storageId => $storageName) {
                $tmpArr = $this->remainsOnStorage($storageId);
                if (!empty($tmpArr)) {
                    foreach ($tmpArr as $itemtypeId => $itemtypeCount) {
                        if (isset($result[$itemtypeId])) {
                            $result[$itemtypeId]+=$itemtypeCount;
                        } else {
                            $result[$itemtypeId] = $itemtypeCount;
                        }
                    }
                }
            }
        }
        return ($result);
    }

    /**
      Жизнь дерьмо,
      Возненавидь любя.
      Всем смертям назло
      Убей себя сам.
     */

    /**
     * Returns JQuery datatables reply for storage remains itemtypes
     * 
     * @param int $storageId
     * @return string
     */
    public function outcomingRemainsAjaxReply($storageId) {
        $storageId = vf($storageId, 3);
        $remainItems = $this->remainsOnStorage($storageId);
        $json = new wf_JqDtHelper();

        if (!empty($remainItems)) {
            foreach ($remainItems as $itemtypeid => $count) {
                if ($count > 0) {
                    $actLink = '';
                    if (cfr('WAREHOUSEOUT')) {
                        $actLink.= wf_Link(self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $storageId . '&outitemid=' . $itemtypeid, wf_img_sized('skins/whoutcoming_icon.png', '', '10', '10') . ' ' . __('Outcoming')) . ' ';
                    }

                    if (cfr('WAREHOUSERESERVE')) {
                        $actLink.= wf_Link(self::URL_ME . '&' . self::URL_RESERVE . '&storageid=' . $storageId . '&itemtypeid=' . $itemtypeid, wf_img_sized('skins/whreservation.png', '', '10', '10') . ' ' . __('Reservation'));
                    }

                    $data[] = @$this->allCategories[$this->allItemTypes[$itemtypeid]['categoryid']];
                    $data[] = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeid, @$this->allItemTypeNames[$itemtypeid]);
                    $data[] = $count . ' ' . @$this->unitTypes[$this->allItemTypes[$itemtypeid]['unit']];
                    $data[] = $actLink;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    /**
     * Returns items list available at storage for further outcoming operation
     * 
     * @param int $storageId
     * @return string
     */
    public function outcomingItemsList($storageId) {
        $storageId = vf($storageId, 3);
        $result = '';
        if (!empty($this->allIncoming)) {
            $columns = array('Category', 'Warehouse item types', 'Count', 'Actions');
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $storageId . '&' . self::URL_OUTAJREMAINS, true, 'Warehouse item types', 50);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Returns outcoming operations list
     * 
     * @return string
     */
    public function outcomingOperationsList() {
        $result = '';
        if (!empty($this->allOutcoming)) {
            $opts = '"order": [[ 0, "desc" ]]';
            $columns = array('ID', 'Date', 'Destination', 'Warehouse storage', 'Category', 'Warehouse item types', 'Count', 'Price per unit', 'Sum', 'Actions');
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_OUT . '&' . self::URL_OUTAJLIST, false, 'Outcoming operations', 50, $opts);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Returns outcoming operation destination link
     * 
     * @param string $desttype
     * @param string $destparam
     * 
     * @return string
     */
    protected function outDestControl($desttype, $destparam) {
        $result = '';

        switch ($desttype) {
            case 'task':
                $result = ' : ' . wf_Link('?module=taskman&edittask=' . $destparam, $destparam);
                break;
            case 'contractor':
                $result = ' : ' . $this->allContractors[$destparam];
                break;
            case 'employee':
                $result = ' : ' . wf_Link('?module=employee', $this->allEmployee[$destparam]);
                break;
            case 'storage':
                $result = ' : ' . $this->allStorages[$destparam];
                break;
            case 'user':
                $result = ' : ' . wf_Link('?module=userprofile&username=' . $destparam, $destparam);
                break;
            case 'sale':
                $result = '';
                break;
            case 'cancellation':
                $result = '';
                break;
            case 'mistake':
                $result = '';
                break;
        }
        $result = str_replace('"', '', $result);
        $result = trim($result);
        return ($result);
    }

    /**
     * Returns JQuery datatables reply for incoming operations list
     * 
     * @param int $storageId
     * @return string
     */
    public function outcomingListAjaxReply() {
        $json = new wf_JqDtHelper();

        if (!empty($this->allOutcoming)) {
            foreach ($this->allOutcoming as $io => $each) {
                $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['id'], wf_img_sized('skins/whoutcoming_icon.png', '', '10', '10') . ' ' . __('Show'));
                $data[] = $each['id'];
                $data[] = $each['date'];
                $data[] = $this->outDests[$each['desttype']] . $this->outDestControl($each['desttype'], $each['destparam']);
                $data[] = @$this->allStorages[$each['storageid']];
                $data[] = @$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']];
                $data[] = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], $this->allItemTypeNames[$each['itemtypeid']]);
                $data[] = $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                $data[] = $each['price'];
                $data[] = ($each['price'] * $each['count']);
                $data[] = $actLink;
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Returns ajax selector reply for outcoming operation creation form
     * 
     * @param string $destMark
     * @return string
     */
    public function outcomindAjaxDestSelector($destMark) {
        $result = '';
        $destMark = vf($destMark);
        $result.=wf_HiddenInput('newoutdesttype', $destMark);
        switch ($destMark) {
            case 'task':
                $tasksTmp = array();
                $allJobTypes = ts_GetAllJobtypes();
                $allUndoneTasks = ts_GetUndoneTasksArray();
                if (!empty($allUndoneTasks)) {
                    foreach ($allUndoneTasks as $io => $each) {
                        $tasksTmp[$io] = $each['address'] . ' - ' . $allJobTypes[$each['jobtype']];
                    }
                }
                $result.= wf_Selector('newoutdestparam', $tasksTmp, __('Undone tasks'), '', false);
                break;

            case 'contractor':
                $result.=wf_Selector('newoutdestparam', $this->allContractors, __('Contractor'), '', false);
                break;

            case 'employee':
                $result.=wf_Selector('newoutdestparam', $this->activeEmployee, __('Worker'), '', false);
                break;

            case 'storage':
                $result.=wf_Selector('newoutdestparam', $this->allStorages, __('Warehouse storage'), '', false);
                break;

            case 'user':
                $allUsers = zb_UserGetAllIPs();
                if (!empty($allUsers)) {
                    $allUsers = array_flip($allUsers);
                }
                $result.=wf_AutocompleteTextInput('newoutdestparam', $allUsers, __('Login'), '', false);
                break;
            case 'sale':
                $result.=wf_HiddenInput('newoutdestparam', 'true');
                break;
            case 'cancellation':
                $result.=wf_HiddenInput('newoutdestparam', 'true');
                break;
            case 'mistake':
                $result.=wf_HiddenInput('newoutdestparam', 'true');
                break;

            default :
                $result = __('Strange exeption');
                break;
        }
        return ($result);
    }

    /**
     * Returns outcoming operation creation form
     * 
     * @param int $storageid
     * @param int $itemtypeid
     * @param int $reserveid
     * 
     * @return string
     */
    public function outcomingCreateForm($storageid, $itemtypeid, $reserveid = '') {
        $result = '';
        $storageid = vf($storageid, 3);
        $itemtypeid = vf($itemtypeid, 3);
        $reserveid = vf($reserveid, 3);
        $tmpDests = array();
        if ((isset($this->allStorages[$storageid])) AND ( isset($this->allItemTypes[$itemtypeid]))) {
            $itemData = $this->allItemTypes[$itemtypeid];
            $itemUnit = $this->unitTypes[$itemData['unit']];

            $storageRemains = $this->remainsOnStorage($storageid);
            $allRemains = $this->remainsAll();

            if (isset($storageRemains[$itemtypeid])) {
                $itemRemainsStorage = $storageRemains[$itemtypeid];
            } else {
                $itemRemainsStorage = 0;
            }

            if (isset($allRemains[$itemtypeid])) {
                $itemRemainsTotal = $allRemains[$itemtypeid];
            } else {
                $itemRemainsTotal = 0;
            }

            if (!empty($reserveid)) {
                $reserveData = $this->reserveGetData($reserveid);
                $fromReserve = true;
            } else {
                $fromReserve = false;
            }

            $isReserved = $this->reserveGet($storageid, $itemtypeid);

            foreach ($this->outDests as $destMark => $destName) {
                $tmpDests[self::URL_ME . '&' . self::URL_OUT . '&' . self::URL_AJODSELECTOR . $destMark] = $destName;
            }

            //displayed maximum items count
            $maxItemCount = ($fromReserve) ? $reserveData['count'] : ($itemRemainsStorage - $isReserved);

            //form construct
            $inputs = wf_AjaxLoader();
            $inputs.= wf_HiddenInput('newoutdate', curdate());
            $inputs.= wf_AjaxSelectorAC('ajoutdestselcontainer', $tmpDests, __('Destination'), '', false);
            $inputs.= wf_AjaxContainer('ajoutdestselcontainer', '', $this->outcomindAjaxDestSelector('task'));
            $inputs.= wf_HiddenInput('newoutitemtypeid', $itemtypeid);
            $inputs.= wf_HiddenInput('newoutstorageid', $storageid);
            $inputs.= wf_TextInput('newoutcount', $itemUnit . ' (' . __('maximum') . ' ' . $maxItemCount . ')', '', true, '4');
            $inputs.= wf_TextInput('newoutprice', __('Price') . ' (' . __('middle price') . ': ' . $this->getIncomeMiddlePrice($itemtypeid) . ')', '', true, '4');
            if ($fromReserve) {
                $inputs.=wf_HiddenInput('newoutfromreserve', $reserveid);
                $notesPreset = ' ' . __('from reserved on') . ' ' . @$this->allEmployee[$reserveData['employeeid']];
            } else {
                $notesPreset = '';
            }
            $inputs.= wf_TextInput('newoutnotes', __('Notes'), $notesPreset, true, 45);

            $inputs.= wf_tag('br');
            $inputs.=wf_Submit(__('Create'));
            $form = wf_Form('', 'POST', $inputs, 'glamour');

            //notifications 
            if ($itemRemainsTotal < $itemData['reserve']) {
                $remainsAlert = __('The balance of goods and materials in stock is less than the amount') . ' ' . $itemData['reserve'] . ' ' . $itemUnit;
            } else {
                $remainsAlert = '';
            }

            $remainsNotification = __('At storage') . ' ' . @$this->allStorages[$storageid] . ' ' . __('remains') . ' ' . $itemRemainsStorage . ' ' . $itemUnit . ' ' . $itemData['name'];
            $notifications = $this->messages->getStyledMessage($remainsNotification, 'success');

            if ($isReserved) {
                $notifications.= $this->messages->getStyledMessage(__('Reserved') . ' ' . $isReserved . ' ' . $itemUnit, 'info');
            }

            if ($remainsAlert) {
                $notifications.= $this->messages->getStyledMessage($remainsAlert, 'warning');
            }


            $notifications.=wf_CleanDiv();
            if (cfr('WAREHOUSERESERVE')) {
                $reserveLink = self::URL_ME . '&' . self::URL_RESERVE . '&itemtypeid=' . $itemtypeid . '&storageid=' . $storageid;
                $notifications.=wf_tag('div', false, '', 'style="margin: 20px 3% 0 3%;"') . wf_Link($reserveLink, wf_img('skins/whreservation.png') . ' ' . __('Reservation'), false, 'ubButton') . wf_tag('div', true);
                $notifications.=wf_CleanDiv();
            }


            $cells = wf_TableCell($form, '40%');
            $cells.= wf_TableCell($notifications, '', '', 'valign="top"');
            $rows = wf_TableRow($cells);
            $result = wf_TableBody($rows, '100%', 0, '');

            //photostorage integration
            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photostorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $itemtypeid);
                $result.=$photostorage->renderImagesRaw();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Strange exeption'), 'error');
        }
        return ($result);
    }

    /**
     * Renders outcoming operation view interface
     * 
     * @param int $id
     * @return string
     */
    public function outcomingView($id) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->allOutcoming[$id])) {
            $operationData = $this->allOutcoming[$id];

            $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            $administratorName = (isset($employeeLogins[$operationData['admin']])) ? $employeeLogins[$operationData['admin']] : $operationData['admin'];
            $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $operationData['itemtypeid'], @$this->allItemTypeNames[$operationData['itemtypeid']]);

            $cells = wf_TableCell(__('ID') . ' ' . $this->qrControl('out', $id), '30%', 'row2');
            $cells.= wf_TableCell($id);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Date'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['date']);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Destination'), '30%', 'row2');
            $cells.= wf_TableCell($this->outDests[$operationData['desttype']] . $this->outDestControl($operationData['desttype'], $operationData['destparam']));
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Category'), '30%', 'row2');
            $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$operationData['itemtypeid']]['categoryid']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse item type'), '30%', 'row2');
            $cells.= wf_TableCell($itemTypeLink);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Count'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['count'] . ' ' . $this->unitTypes[$this->allItemTypes[$operationData['itemtypeid']]['unit']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Price per unit'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['price']);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Sum'), '30%', 'row2');
            $cells.= wf_TableCell(($operationData['price'] * $operationData['count']));
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse storage'), '30%', 'row2');
            $cells.= wf_TableCell($this->allStorages[$operationData['storageid']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Worker'), '30%', 'row2');
            $cells.= wf_TableCell($administratorName);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Notes'), '30%', 'row2');
            $cells.= wf_TableCell($operationData['notes']);
            $rows.= wf_TableRow($cells, 'row3');

            $result.=wf_TableBody($rows, '100%', 0, 'wh_viewer');

            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $operationData['itemtypeid']);
                $result.=$photoStorage->renderImagesRaw();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Strange exeption') . ' NO_EXISTING_OUTCOME_ID', 'error');
        }

        //ADcomments support
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('WAREHOUSEOUTCOME');
            $result.=wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
            $result.=$adcomments->renderComments($id);
        }

        return ($result);
    }

    /**
     * Creates new outcoming operation record in database
     * 
     * @param string $date
     * @param string $desttype
     * @param string $destparam
     * @param int $storageid
     * @param int $itemtypeid
     * @param float $count
     * @param float $price
     * @param string $notes
     * @param int $reserveid
     * 
     * @return string not emplty if something went wrong
     */
    public function outcomingCreate($date, $desttype, $destparam, $storageid, $itemtypeid, $count, $price = '', $notes = '', $reserveid = '') {
        $result = '';
        $date = mysql_real_escape_string($date);
        $desttype = mysql_real_escape_string($desttype);
        $destparam = mysql_real_escape_string($destparam);
        $storageid = vf($storageid, 3);
        $itemtypeid = vf($itemtypeid, 3);
        $reserveid = vf($reserveid, 3);
        $countF = mysql_real_escape_string($count);
        $countF = str_replace('-', '', $countF);
        $countF = str_replace(',', '.', $countF);
        $priceF = mysql_real_escape_string($price);
        $priceF = str_replace(',', '.', $priceF);
        $notes = mysql_real_escape_string($notes);
        $admin = mysql_real_escape_string(whoami());

        $fromReserve = (!empty($reserveid)) ? true : false;
        if ($fromReserve) {
            $reserveData = $this->reserveGetData($reserveid);
        }

        if (isset($this->allStorages[$storageid])) {
            if (isset($this->allItemTypes[$itemtypeid])) {
                $allItemRemains = $this->remainsOnStorage($storageid);
                @$itemRemains = $allItemRemains[$itemtypeid];
                $itemsReserved = $this->reserveGet($storageid, $itemtypeid);
                if ($fromReserve) {
                    $realRemains = $reserveData['count'];
                } else {
                    $realRemains = $itemRemains - $itemsReserved;
                }

                if ($countF <= $realRemains) {
                    //removing items from reserve
                    if ($fromReserve) {
                        $this->reserveDrain($reserveid, $count);
                    }
                    //creating new outcome
                    $query = "INSERT INTO `wh_out` (`id`,`date`,`desttype`,`destparam`,`storageid`,`itemtypeid`,`count`,`price`,`notes`,`admin`) VALUES "
                            . "(NULL,'" . $date . "','" . $desttype . "','" . $destparam . "','" . $storageid . "','" . $itemtypeid . "','" . $countF . "','" . $priceF . "','" . $notes . "','" . $admin . "')";
                    nr_query($query);
                    $newId = simple_get_lastid('wh_out');
                    log_register('WAREHOUSE OUTCOME CREATE [' . $newId . '] ITEM [' . $itemtypeid . '] COUNT `' . $count . '` PRICE `' . $price . '`');
                    //movement of items between different storages
                    if ($desttype == 'storage') {
                        $this->incomingCreate($date, $itemtypeid, 0, $destparam, $count, $price, '', __('from') . ' ' . __('Warehouse storage') . ' `' . $this->allStorages[$storageid] . '`');
                    }
                } else {
                    if ($fromReserve) {
                        $quantityFailNotice = __('The balance of goods and materials in stock is less than the reserved');
                        $quantityFailReason = ' (' . $countF . ' > ' . $realRemains . ')';
                    } else {
                        $quantityFailNotice = __('The balance of goods and materials in stock is less than the amount');
                        $quantityFailReason = ' (' . $countF . ' > ' . $itemRemains . '-' . $itemsReserved . ')';
                    }

                    $result = $this->messages->getStyledMessage($quantityFailNotice . $quantityFailReason, 'error');
                }
            } else {
                $result = $this->messages->getStyledMessage(__('Strange exeption') . ' EX_WRONG_ITEMTYPE_ID', 'error');
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Strange exeption') . ' EX_WRONG_STORAGE_ID', 'error');
        }

        return ($result);
    }

    /**
     * Returns income operations list available at storages
     * 
     * @return string
     */
    public function reportAllStoragesRemains() {
        $result = '';
        if (!empty($this->allIncoming)) {
            $columns = array('Category', 'Warehouse item types', 'Count', 'Actions');
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_REPORTS . '&' . self::URL_REAJTREM, true, 'Warehouse item types', 50);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'warning');
        }

        return ($result);
    }

    /**
     * Returns JQuery datatables reply for total remains report
     * 
     * @return string
     */
    public function reportAllStoragesRemainsAjaxReply() {
        $all = $this->remainsAll();
        $json = new wf_JqDtHelper();

        if (!empty($all)) {
            foreach ($all as $itemtypeId => $count) {
                if ($count > 0) {
                    $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showremains=' . $itemtypeId, wf_img_sized('skins/icon_search_small.gif', '', '10', '10') . ' ' . __('Show'));
                    $data[] = $this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']];
                    $data[] = wf_link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeId, $this->allItemTypeNames[$itemtypeId]);
                    $data[] = $count . ' ' . $this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']];
                    $data[] = $actLink;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    /**
     * Renders itemtype storage availability view
     * 
     * @param int $itemtypeId
     * @return string
     */
    public function reportAllStoragesRemainsView($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        $result = '';
        $tmpArr = array();
        if (isset($this->allItemTypes[$itemtypeId])) {
            $itemtypeData = $this->allItemTypes[$itemtypeId];
            $itemtypeUnit = $this->unitTypes[$itemtypeData['unit']];
            $itemtypeName = $this->allItemTypeNames[$itemtypeId];

            $cells = wf_TableCell(__('Warehouse item types'));
            $cells.= wf_TableCell(__('Warehouse storage'));
            $cells.= wf_TableCell(__('Count'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->allStorages)) {
                foreach ($this->allStorages as $storageId => $StorageName) {
                    $tmpArr = $this->remainsOnStorage($storageId);
                    if (!empty($tmpArr)) {
                        foreach ($tmpArr as $io => $count) {
                            if ($io == $itemtypeId) {
                                if ($count > 0) {
                                    $actLinks = '';
                                    if (cfr('WAREHOUSEOUT')) {
                                        $actLinks.= wf_Link(self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $storageId . '&outitemid=' . $itemtypeId, wf_img_sized('skins/whoutcoming_icon.png', '', '10', '10') . ' ' . __('Outcoming')) . ' ';
                                    }

                                    if (cfr('WAREHOUSERESERVE')) {
                                        $actLinks.= wf_Link(self::URL_ME . '&' . self::URL_RESERVE . '&storageid=' . $storageId . '&itemtypeid=' . $itemtypeId, wf_img_sized('skins/whreservation.png', '', '10', '10') . ' ' . __('Reservation'));
                                    }

                                    $cells = wf_TableCell($itemtypeName);
                                    $cells.= wf_TableCell($StorageName);
                                    $cells.= wf_TableCell($count . ' ' . $itemtypeUnit);
                                    $cells.= wf_TableCell($actLinks);
                                    $rows.= wf_TableRow($cells, 'row3');
                                }
                            }
                        }
                    }
                }
            }

            $result.=wf_TableBody($rows, '100%', 0, 'sortable');

            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $itemtypeId);
                $result.=$photoStorage->renderImagesRaw();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Something went wrong') . ' EX_WRONG_ITEMTYPE_ID', 'error');
        }
        return ($result);
    }

    /**
     * Returns low reserve alert
     * 
     * @return string
     */
    protected function reserveAlert() {
        $result = '';
        if ((!empty($this->allItemTypes)) AND ( !empty($this->allStorages)) AND ( !empty($this->allIncoming))) {
            $allRemains = $this->remainsAll();

            foreach ($this->allItemTypes as $itemtypeId => $itemData) {
                $itemReserve = $itemData['reserve'];
                $itemName = $this->allItemTypeNames[$itemtypeId];
                $itemUnit = $this->unitTypes[$itemData['unit']];
                if ($itemReserve > 0) {
                    if ((!isset($allRemains[$itemtypeId])) OR ( $allRemains[$itemtypeId] < $itemReserve)) {
                        $result.=$this->messages->getStyledMessage(__('In warehouses remains less than') . ' ' . $itemReserve . ' ' . $itemUnit . ' ' . $itemName, 'warning');
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Shows warehouse summary report
     * 
     * @return void
     */
    public function summaryReport() {
        $result = '';
        if ($_SERVER['QUERY_STRING'] == 'module=warehouse') {
            $result.=$this->reserveAlert();

            if (empty($this->allCategories)) {
                $result.=$this->messages->getStyledMessage(__('No existing categories'), 'warning');
            } else {
                $result.=$this->messages->getStyledMessage(__('Available categories') . ': ' . sizeof($this->allCategories), 'info');
            }

            if (empty($this->allItemTypes)) {
                $result.=$this->messages->getStyledMessage(__('No existing warehouse item types'), 'warning');
            } else {
                $result.=$this->messages->getStyledMessage(__('Available item types') . ': ' . sizeof($this->allItemTypes), 'info');
            }

            if (empty($this->allStorages)) {
                $result.=$this->messages->getStyledMessage(__('No existing warehouse storages'), 'warning');
            } else {
                $result.=$this->messages->getStyledMessage(__('Available warehouse storages') . ': ' . sizeof($this->allStorages), 'info');
            }

            if (empty($this->allContractors)) {
                $result.=$this->messages->getStyledMessage(__('No existing contractors'), 'warning');
            } else {
                $result.=$this->messages->getStyledMessage(__('Available contractors') . ': ' . sizeof($this->allContractors), 'info');
            }

            if (empty($this->allIncoming)) {
                $result.=$this->messages->getStyledMessage(__('No incoming operations yet'), 'warning');
            } else {
                $result.=$this->messages->getStyledMessage(__('Total incoming operations') . ': ' . sizeof($this->allIncoming), 'success');
            }

            if (empty($this->allOutcoming)) {
                $result.=$this->messages->getStyledMessage(__('No outcoming operations yet'), 'warning');
            } else {
                $result.=$this->messages->getStyledMessage(__('Total outcoming operations') . ': ' . sizeof($this->allOutcoming), 'success');
            }


            if (!empty($result)) {
                show_window(__('Stats'), $result);
                zb_BillingStats(true);
            }
        }
    }

    /**
     * Renders QR code label of some type
     * 
     * @param string $type
     * @param int $id
     */
    public function qrCodeDraw($type, $id) {
        $type = vf($type);
        $id = vf($id, 3);
        $qr = new BarcodeQR();
        switch ($type) {
            case 'in':
                if (isset($this->allIncoming[$id])) {
                    $itemName = $this->allItemTypeNames[$this->allIncoming[$id]['itemtypeid']];
                    $qr->text($itemName . ' ' . __('Incoming operation') . '# ' . $id);
                } else {
                    $qr->text('Wrong ID');
                }
                break;

            case 'out':
                if (isset($this->allOutcoming[$id])) {
                    $itemName = $this->allItemTypeNames[$this->allOutcoming[$id]['itemtypeid']];
                    $qr->text($itemName . ' ' . __('Outcoming operation') . '# ' . $id);
                } else {
                    $qr->text('Wrong ID');
                }
                break;

            case 'itemtype':
                if (isset($this->allItemTypeNames[$id])) {
                    $qr->text($this->allItemTypeNames[$id]);
                } else {
                    $qr->text('Wrong ID');
                }
                break;

            default :
                $qr->text('Wrong type');
                break;
        }

        $qr->draw();
    }

    /**
     * Renders available operations in calendar widget
     * 
     * @return string
     */
    public function reportCalendarOps() {
        $calendarData = '';

        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $itemName = @$this->allItemTypeNames[$each['itemtypeid']];
                $itemCount = @$each['count'];
                $itemUnit = @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                $calendarData.="
                      {
                        title: '" . $itemName . " - " . $itemCount . ' ' . $itemUnit . "',
                        url: '" . self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $each['id'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                   },
                    ";
            }
        }


        if (!empty($this->allOutcoming)) {
            foreach ($this->allOutcoming as $io => $each) {
                $timestamp = strtotime($each['date']);
                $date = date("Y, n-1, j", $timestamp);
                $itemName = @$this->allItemTypeNames[$each['itemtypeid']];
                $itemCount = @$each['count'];
                $itemUnit = @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                $calendarData.="
                      {
                        title: '" . $itemName . " - " . $itemCount . ' ' . $itemUnit . "',
                        url: '" . self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['id'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                        className : 'undone',
                   },
                    ";
            }
        }




        $result = wf_FullCalendar($calendarData);
        return ($result);
    }

    /**
     * Returns additionally spent materials list for some task
     * 
     * @param int $taskid
     * 
     * @return string
     */
    public function taskMaterialsReport($taskid) {
        $taskid = vf($taskid, 3);
        $result = '';
        $tmpArr = array();
        $sum = 0;
        if (!empty($this->allOutcoming)) {
            $tmpArr = $this->allOutcoming;
            if (!empty($tmpArr)) {
                $cells = wf_TableCell(__('Date'));
                $cells.= wf_TableCell(__('Warehouse storage'));
                $cells.= wf_TableCell(__('Category'));
                $cells.= wf_TableCell(__('Warehouse item type'));
                $cells.= wf_TableCell(__('Count'));
                $cells.= wf_TableCell(__('Price'));
                $cells.= wf_TableCell(__('Sum'));
                $cells.= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($tmpArr as $io => $each) {
                    @$itemUnit = $this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                    $cells = wf_TableCell($each['date']);
                    $cells.= wf_TableCell(@$this->allStorages[$each['storageid']]);
                    $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                    $cells.= wf_TableCell(@$this->allItemTypeNames[$each['itemtypeid']]);
                    $cells.= wf_TableCell($each['count'] . ' ' . $itemUnit);
                    $cells.= wf_TableCell($each['price']);
                    $cells.= wf_TableCell($each['price'] * $each['count']);
                    if (cfr('WAREHOUSEOUT')) {
                        $actLinks = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['id'], wf_img_sized('skins/whoutcoming_icon.png', '', '12') . ' ' . __('Show'));
                    } else {
                        $actLinks = '';
                    }
                    $cells.= wf_TableCell($actLinks);
                    $rows.= wf_TableRow($cells, 'row3');
                    $sum = $sum + ($each['price'] * $each['count']);
                }
                $cells = wf_TableCell(__('Total'));
                $cells.= wf_TableCell('');
                $cells.= wf_TableCell('');
                $cells.= wf_TableCell('');
                $cells.= wf_TableCell('');
                $cells.= wf_TableCell('');
                $cells.= wf_TableCell($sum);
                $cells.= wf_TableCell('');
                $rows.= wf_TableRow($cells, 'row2');

                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
        }
        return ($result);
    }

    /**
     * Returns additionally spent materials price for some task
     * 
     * @param int $taskid
     * 
     * @return array sum=>float & items=>data
     */
    public function taskMaterialsSpentPrice($taskid) {
        $taskid = vf($taskid, 3);
        $result = array();
        $sum = 0;

        if (!empty($this->allOutcoming)) {
            if (!isset($this->taskOutsCache[$taskid])) {
                foreach ($this->allOutcoming as $io => $each) {
                    if (($each['desttype'] == 'task') AND ( $each['destparam'] == $taskid)) {
                        $sum = $sum + ($each['price'] * $each['count']);
                        $result['items'][] = $each;
                    }
                }
                $this->taskOutsCache[$taskid] = $result;
                $this->cache->set('TASKSOUTS', $this->taskOutsCache, self::CACHE_TIMEOUT);
            } else {
                $result = $this->taskOutsCache[$taskid];

                if (!empty($this->taskOutsCache[$taskid]['items'])) {
                    foreach ($this->taskOutsCache[$taskid]['items'] as $io => $each) {
                        $sum = $sum + ($each['price'] * $each['count']);
                    }
                }
            }
        }

        $result['sum'] = $sum;

        return ($result);
    }

    /**
     * shows printable report content
     * 
     * @param $title report title
     * @param $data  report data to printable transform
     * 
     * @return void
     */
    public function reportPrintable($title, $data) {

        $style = file_get_contents(CONFIG_PATH . "ukvprintable.css");

        $header = wf_tag('!DOCTYPE', false, '', 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"');
        $header.= wf_tag('html', false, '', 'xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru"');
        $header.= wf_tag('head', false);
        $header.= wf_tag('title') . $title . wf_tag('title', true);
        $header.= wf_tag('meta', false, '', 'http-equiv="Content-Type" content="text/html; charset=UTF-8" /');
        $header.= wf_tag('style', false, '', 'type="text/css"');
        $header.= $style;
        $header.=wf_tag('style', true);
        $header.= wf_tag('script', false, '', 'src="modules/jsc/sorttable.js" language="javascript"') . wf_tag('script', true);
        $header.=wf_tag('head', true);
        $header.= wf_tag('body', false);

        $footer = wf_tag('body', true);
        $footer.= wf_tag('html', true);

        $title = (!empty($title)) ? wf_tag('h2') . $title . wf_tag('h2', true) : '';
        $data = $header . $title . $data . $footer;
        die($data);
    }

    /**
     * Renders storage remains printable report
     * 
     * @param int $storageid
     * 
     * @return void
     */
    public function reportStorageRemainsPrintable($storageId) {
        $storageId = vf($storageId, 3);
        $result = '';
        if (isset($this->allStorages[$storageId])) {
            $storageName = $this->allStorages[$storageId];
            $allRemains = $this->remainsOnStorage($storageId);

            $cells = wf_TableCell(__('Category'));
            $cells.= wf_TableCell(__('Warehouse item types'));
            $cells.= wf_TableCell(__('Count'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($allRemains)) {
                foreach ($allRemains as $itemtypeId => $count) {
                    $cells = wf_TableCell(@$this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']]);
                    $cells.= wf_TableCell(@$this->allItemTypeNames[$itemtypeId]);
                    $itemUnit = @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']];
                    $cells.= wf_TableCell($count . ' ' . $itemUnit);
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            $result = wf_TableBody($rows, '100%', 0, 'sortable');
            $this->reportPrintable(__('The remains in the warehouse storage') . ': ' . $storageName, $result);
        } else {
            show_error(__('Something went wrong') . ': EX_WRONG_STORAGE_ID');
            show_window('', $this->backControl(self::URL_ME . '&' . self::URL_OUT));
        }
    }

    /**
     * Returns date remains report header
     * 
     * @param int    $year selected year
     * @param string $monthNumber selected month with leading zero
     * 
     * @return string
     */
    protected function reportDateRemainsHeader($year, $monthNumber) {
        $monthArr = months_array();
        $monthName = rcms_date_localise($monthArr[$monthNumber]);

        $result = '';
        $result.= wf_tag('table', false, '', 'border="0" cellspacing="2" width="100%" class="printable"');
        $result.= wf_tag('colgroup', false, '', 'span="4" width="80"');
        $result.=wf_tag('colgroup', true);
        $result.= wf_tag('colgroup', false, '', 'width="79"');
        $result.=wf_tag('colgroup', true);
        $result.= wf_tag('colgroup', false, '', 'span="6" width="80"');
        $result.=wf_tag('colgroup', true);
        $result.= wf_tag('tbody', false);
        $result.= wf_tag('tr', false, 'row2');
        $result.= wf_tag('td', false, '', 'colspan="3" rowspan="3" align="center" valign="bottom"');
        $result.= __('Warehouse item types');
        $result.= wf_tag('td', true);
        $result.= wf_tag('td', false, '', 'colspan="2" rowspan="2" align="center" valign="bottom"');
        $result.= __('Remains at the beginning of the month');
        $result.= wf_tag('td', true);
        $result.= wf_tag('td', false, '', 'colspan="4" align="center" valign="bottom"') . $monthName . ' ' . $year . wf_tag('td', true);
        $result.= wf_tag('td', false, '', 'colspan="2" rowspan="2" align="center" valign="bottom"');
        $result.= __('Remains at end of the month');
        $result.= wf_tag('td', true);
        $result.=wf_tag('tr', true);
        $result.=wf_tag('tr', false, 'row2');
        $result.= wf_tag('td', false, '', 'colspan="2" align="center" valign="bottom"') . __('Incoming') . wf_tag('td', true);
        $result.= wf_tag('td', false, '', 'colspan="2" align="center" valign="bottom"') . __('Outcoming') . wf_tag('td', true);
        $result.= wf_tag('tr', true);
        $result.= wf_tag('tr', false, 'row2');
        $result.= wf_TableCell(__('Count'));
        $result.= wf_TableCell(__('Sum'));
        $result.= wf_TableCell(__('Count'));
        $result.= wf_TableCell(__('Sum'));
        $result.= wf_TableCell(__('Count'));
        $result.= wf_TableCell(__('Sum'));
        $result.= wf_TableCell(__('Count'));
        $result.= wf_TableCell(__('Sum'));
        $result.=wf_tag('tr', true);
        $result.=wf_tag('tr', false);
        return ($result);
    }

    /**
     * Returns valid formatted table row form date remains report
     * 
     * @param int $itemtypeId
     * @param array $data
     * 
     * @return string
     */
    protected function reportDateRemainsAddRow($itemtypeId, $data) {
        if ($itemtypeId != '') {
            $itemData = $this->allItemTypeNames[$itemtypeId] . ' (' . $this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']] . ')';
        } else {
            $itemData = '';
        }
        $cells = wf_TableCell($itemData, '', '', 'colspan="3" align="center"');
        $cells.= wf_TableCell($data[0]);
        $cells.= wf_TableCell($data[1]);
        $cells.= wf_TableCell($data[2]);
        $cells.= wf_TableCell($data[3]);
        $cells.= wf_TableCell($data[4]);
        $cells.= wf_TableCell($data[5]);
        $cells.= wf_TableCell($data[6]);
        $cells.= wf_TableCell($data[7]);
        $result = wf_TableRow($cells, 'row3');

        return ($result);
    }

    /**
     * Returns middle price for some itemtype based of all incoming operations
     * 
     * @param int $itemtypeId
     * 
     * @return float
     */
    protected function getIncomeMiddlePrice($itemtypeId) {
        $itemsCount = 0;
        $totalSumm = 0;
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                if ($each['itemtypeid'] == $itemtypeId) {
                    if ($each['price'] != 0) {
                        if ($each['contractorid'] != 0) { //ignoring move ops
                            $totalSumm+=($each['price'] * $each['count']);
                            $itemsCount+=$each['count'];
                        }
                    }
                }
            }
        }

        if ($itemsCount != 0) {
            $result = round($totalSumm / $itemsCount, 2);
        } else {
            $result = $totalSumm;
        }


        return ($result);
    }

    /**
     * Renders date remains report
     * 
     * @return string
     */
    public function reportDateRemains() {
        $result = '';

        $curyear = (wf_CheckPost(array('yearsel'))) ? vf($_POST['yearsel'], 3) : date("Y");
        $curmonth = (wf_CheckPost(array('monthsel'))) ? vf($_POST['monthsel'], 3) : date("m");

        $inputs = wf_YearSelector('yearsel', __('Year')) . ' ';
        $inputs.= wf_MonthSelector('monthsel', __('Month'), $curmonth) . ' ';
        $inputs.= wf_CheckInput('printmode', __('Print'), false, false);
        $inputs.= wf_Submit(__('Show'));
        $searchForm = wf_Form('', 'POST', $inputs, 'glamour');
        $searchForm.= wf_CleanDiv();

        //append form to result
        if (!wf_CheckPost(array('printmode'))) {
            $result.=$searchForm;
        }


        $lowerOffset = strtotime($curyear . '-' . $curmonth . '-01');
        $upperOffset = strtotime($curyear . '-' . $curmonth . '-01');
        $upperOffset = date("t", $upperOffset);
        $upperOffset = strtotime($curyear . '-' . $curmonth . '-' . $upperOffset);
        $incomingLower = array();
        $outcomingLower = array();

        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                $incomingDate = strtotime($each['date']);
                if ($incomingDate < $lowerOffset) {
                    if ($each['contractorid'] != 0) { //ignoring move ops
                        $incomingLower[$each['id']] = $each;
                    }
                }
            }
        }


        if (!empty($this->allOutcoming)) {
            foreach ($this->allOutcoming as $io => $each) {
                $outcomingDate = strtotime($each['date']);
                if ($outcomingDate < $lowerOffset) {
                    if ($each['desttype'] != 'storage') { // ignoring move ops
                        $outcomingLower[$each['id']] = $each;
                    }
                }
            }
        }

        $lowerIncome = array();
        if (!empty($incomingLower)) {
            foreach ($incomingLower as $io => $each) {
                if (isset($lowerIncome[$each['itemtypeid']])) {
                    $lowerIncome[$each['itemtypeid']]['count'] = $lowerIncome[$each['itemtypeid']]['count'] + $each['count'];
                    $lowerIncome[$each['itemtypeid']]['price'] = $lowerIncome[$each['itemtypeid']]['price'] + ($each['count'] * $each['price']);
                } else {
                    $lowerIncome[$each['itemtypeid']]['count'] = $each['count'];
                    $lowerIncome[$each['itemtypeid']]['price'] = $each['count'] * $each['price'];
                }
            }
        }

        $lowerOutcome = array();
        if (!empty($outcomingLower)) {
            foreach ($outcomingLower as $io => $each) {

                if ($each['price'] == 0) {
                    $each['price'] = $this->getIncomeMiddlePrice($each['itemtypeid']);
                }

                if (isset($lowerOutcome[$each['itemtypeid']])) {
                    $lowerOutcome[$each['itemtypeid']]['count'] = $lowerOutcome[$each['itemtypeid']]['count'] + $each['count'];
                    $lowerOutcome[$each['itemtypeid']]['price'] = $lowerOutcome[$each['itemtypeid']]['price'] + ($each['count'] * $each['price']);
                } else {
                    $lowerOutcome[$each['itemtypeid']]['count'] = $each['count'];
                    $lowerOutcome[$each['itemtypeid']]['price'] = $each['count'] * $each['price'];
                }
            }
        }




        //first report column here
        $lowerRemains = array();
        if (!empty($incomingLower)) {
            foreach ($incomingLower as $io => $each) {
                $outcomeCount = (isset($lowerOutcome[$each['itemtypeid']])) ? $lowerOutcome[$each['itemtypeid']]['count'] : 0;
                $outcomePrice = (isset($lowerOutcome[$each['itemtypeid']])) ? $lowerOutcome[$each['itemtypeid']]['price'] : 0;
                $lowerRemains[$each['itemtypeid']]['count'] = $lowerIncome[$each['itemtypeid']]['count'] - $outcomeCount;
                $lowerRemains[$each['itemtypeid']]['price'] = $lowerIncome[$each['itemtypeid']]['price'] - $outcomePrice;
            }
        }




        //second column
        $upperIncome = array();
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                $incomeDate = strtotime($each['date']);
                if (($incomeDate >= $lowerOffset ) AND ( $incomeDate) <= $upperOffset) {
                    if ($each['contractorid'] != 0) { //ignoring move ops
                        if (isset($upperIncome[$each['itemtypeid']])) {
                            $upperIncome[$each['itemtypeid']]['count'] = $upperIncome[$each['itemtypeid']]['count'] + $each['count'];
                            $upperIncome[$each['itemtypeid']]['price'] = $upperIncome[$each['itemtypeid']]['price'] + ($each['count'] * $each['price']);
                        } else {
                            $upperIncome[$each['itemtypeid']]['count'] = $each['count'];
                            $upperIncome[$each['itemtypeid']]['price'] = $each['count'] * $each['price'];
                        }
                    }
                }
            }
        }

        //third column
        $upperOutcome = array();
        if (!empty($this->allOutcoming)) {
            foreach ($this->allOutcoming as $io => $each) {
                $outcomeDate = strtotime($each['date']);
                if (($outcomeDate >= $lowerOffset ) AND ( $outcomeDate) <= $upperOffset) {
                    if ($each['desttype'] != 'storage') { //ignoring move ops
                        if ($each['price'] == 0) {
                            $each['price'] = $this->getIncomeMiddlePrice($each['itemtypeid']);
                        }
                        if (isset($upperOutcome[$each['itemtypeid']])) {
                            $upperOutcome[$each['itemtypeid']]['count'] = $upperOutcome[$each['itemtypeid']]['count'] + $each['count'];
                            $upperOutcome[$each['itemtypeid']]['price'] = $upperOutcome[$each['itemtypeid']]['price'] + ($each['count'] * $each['price']);
                        } else {
                            $upperOutcome[$each['itemtypeid']]['count'] = $each['count'];
                            $upperOutcome[$each['itemtypeid']]['price'] = $each['count'] * $each['price'];
                        }
                    }
                }
            }
        }


        //mixing earlier non exists items into lower remains array
        if (!empty($upperIncome)) {
            foreach ($upperIncome as $io => $each) {
                if (!isset($lowerRemains[$io])) {
                    $lowerRemains[$io]['count'] = 0;
                    $lowerRemains[$io]['price'] = 0;
                }
            }
        }



        $result.=$this->reportDateRemainsHeader($curyear, $curmonth);

        if (!empty($lowerRemains)) {
            $firstColumnTotal = 0;
            $secondColumnTotal = 0;
            $thirdColumnTotal = 0;
            $fourthColumnTotal = 0;

            foreach ($lowerRemains as $io => $each) {
                $itemtypeId = $io;
                $firstColumnCount = (isset($lowerRemains[$itemtypeId])) ? $lowerRemains[$itemtypeId]['count'] : 0;
                $firstColumnPrice = (isset($lowerRemains[$itemtypeId])) ? $lowerRemains[$itemtypeId]['price'] : 0;
                $secondColumnCount = (isset($upperIncome[$itemtypeId])) ? $upperIncome[$itemtypeId]['count'] : 0;
                $secondColumnPrice = (isset($upperIncome[$itemtypeId])) ? $upperIncome[$itemtypeId]['price'] : 0;
                $thirdColumnCount = (isset($upperOutcome[$itemtypeId])) ? $upperOutcome[$itemtypeId]['count'] : 0;
                $thirdColumnPrice = (isset($upperOutcome[$itemtypeId])) ? $upperOutcome[$itemtypeId]['price'] : 0;

                $fourthColumnCount = $lowerRemains[$itemtypeId]['count'] + $secondColumnCount - $thirdColumnCount;
                $fourthColumnPrice = $lowerRemains[$itemtypeId]['price'] + $secondColumnPrice - $thirdColumnPrice;

                $result.=$this->reportDateRemainsAddRow($itemtypeId, array(
                    $firstColumnCount,
                    round($firstColumnPrice, 2),
                    $secondColumnCount,
                    round($secondColumnPrice, 2),
                    $thirdColumnCount,
                    round($thirdColumnPrice, 2),
                    $fourthColumnCount,
                    round($fourthColumnPrice, 2)));

                $firstColumnTotal+=$firstColumnPrice;
                $secondColumnTotal+=$secondColumnPrice;
                $thirdColumnTotal+=$thirdColumnPrice;
                $fourthColumnTotal+=$fourthColumnPrice;
            }

            $result.=$this->reportDateRemainsAddRow('', array('', $firstColumnTotal, '', $secondColumnTotal, '', $thirdColumnTotal, '', $fourthColumnTotal));
        }


        $result.= wf_tag('tbody', true);
        $result.= wf_tag('table', true);

        if (wf_CheckPost(array('printmode'))) {
            die($this->reportPrintable(__('Date remains'), $result));
        }

        return ($result);
    }

    /**
     * Renders itemtype history with income, outcome and reservation operations
     * 
     * @param int $itemtypeId
     * 
     * @return void
     */
    public function renderItemHistory($itemtypeId) {
        $itemtypeId = vf($itemtypeId, 3);
        $result = '';
        $tmpArr = array();
        if (isset($this->allItemTypeNames[$itemtypeId])) {
            $itemTypeName = $this->allItemTypeNames[$itemtypeId];
            $itemTypeCategory = $this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']];
            if (!empty($this->allIncoming)) {
                foreach ($this->allIncoming as $io => $each) {
                    if ($each['itemtypeid'] == $itemtypeId) {
                        $tmpArr[$each['date']]['in'][] = $each;
                    }
                }
            }

            if (!empty($this->allOutcoming)) {
                foreach ($this->allOutcoming as $io => $each) {
                    if ($each['itemtypeid'] == $itemtypeId) {
                        $tmpArr[$each['date']]['out'][] = $each;
                    }
                }
            }

            if (!empty($this->allReserveHistory)) {
                foreach ($this->allReserveHistory as $io => $each) {
                    $reserveDate = strtotime($each['date']);
                    $reserveDate = date("Y-m-d", $reserveDate);
                    if ($each['itemtypeid'] == $itemtypeId) {
                        $tmpArr[$reserveDate]['res'][] = $each;
                    }
                }
            }



            if (!empty($tmpArr)) {
                krsort($tmpArr);
                $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
                $cells = wf_TableCell(__('Date'));
                $cells.= wf_TableCell(__('Type'));
                $cells.= wf_TableCell(__('Warehouse storage'));
                $cells.= wf_TableCell(__('Count'));
                $cells.= wf_TableCell(__('Price'));
                $cells.= wf_TableCell(__('Actions'));
                $cells.= wf_TableCell(__('Admin'));
                $rows = wf_TableRow($cells, 'row1');

                foreach ($tmpArr as $io => $eachDate) {
                    if (!empty($eachDate)) {
                        foreach ($eachDate as $opType => $eachPack) {
                            if (!empty($eachPack)) {
                                foreach ($eachPack as $ix => $eachOp) {
                                    $administratorName = (isset($employeeLogins[$eachOp['admin']])) ? $employeeLogins[$eachOp['admin']] : $eachOp['admin'];
                                    $from = '';
                                    $to = '';
                                    $opTypeName = '';
                                    $opLink = '';
                                    $itemUnitType = @$this->unitTypes[$this->allItemTypes[$eachOp['itemtypeid']]['unit']];

                                    //incoming ops
                                    if ($opType == 'in') {
                                        if ($eachOp['contractorid'] == 0) {
                                            $from = $eachOp['notes'];
                                        } else {
                                            $from = @$this->allContractors[$eachOp['contractorid']];
                                        }
                                        $to = $this->allStorages[$eachOp['storageid']];
                                        $opTypeName = __('Incoming');
                                        $rowColor = '#009f04';
                                        $opLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $eachOp['id'], wf_img_sized('skins/whincoming_icon.png', __('Show'), '10', '10'));
                                    }

                                    //outcoming ops
                                    if ($opType == 'out') {
                                        $from = $this->allStorages[$eachOp['storageid']];
                                        $to = $this->outDests[$eachOp['desttype']] . $this->outDestControl($eachOp['desttype'], $eachOp['destparam']);
                                        $opTypeName = __('Outcoming');
                                        $rowColor = '#b50000';
                                        $opLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $eachOp['id'], wf_img_sized('skins/whoutcoming_icon.png', __('Show'), '10', '10'));
                                    }

                                    //reservation ops
                                    if ($opType == 'res') {
                                        $from = $this->allStorages[$eachOp['storageid']];
                                        $to = @$this->allEmployee[$eachOp['employeeid']];
                                        $opTypeName = __('Reservation');

                                        if ($eachOp['type'] == 'create') {
                                            $opTypeName.= ' (' . __('Created') . ')';
                                        }
                                        if ($eachOp['type'] == 'update') {
                                            $opTypeName.= ' (' . __('Updated') . ')';
                                        }
                                        if ($eachOp['type'] == 'delete') {
                                            $opTypeName.= ' (' . __('Deleted') . ')';
                                        }
                                        $rowColor = '#ff8a00';
                                    }

                                    //itemtype price calculation
                                    if (isset($eachOp['price'])) {
                                        $opPrice = $eachOp['price'] * $eachOp['count'];
                                    } else {
                                        $opPrice = 0;
                                    }

                                    $cells = wf_TableCell(wf_tag('font', false, '', 'color="' . $rowColor . '"') . $eachOp['date'] . wf_tag('font', true));
                                    $cells.= wf_TableCell(wf_tag('font', false, '', 'color="' . $rowColor . '"') . $opTypeName . wf_tag('font', true) . ' ' . $opLink);
                                    $cells.= wf_TableCell(@$this->allStorages[$eachOp['storageid']]);
                                    $cells.= wf_TableCell($eachOp['count'] . ' ' . $itemUnitType);
                                    $cells.= wf_TableCell($opPrice);
                                    $cells.= wf_TableCell($from . ' ' . wf_img('skins/arrow_right_green.png') . ' ' . $to);
                                    $cells.= wf_TableCell($administratorName);
                                    $rows.= wf_TableRow($cells, 'row3');
                                }
                            }
                        }
                    }
                }


                $result = wf_TableBody($rows, '100%', 0, 'sortable');
            }
            show_window(__('History') . ': ' . $itemTypeCategory . ', ' . $itemTypeName, $result);
        } else {
            show_error(__('Something went wrong'));
        }
    }

}

?>
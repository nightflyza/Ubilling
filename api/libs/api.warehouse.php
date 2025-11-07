<?php

/**
 * Basic warehouse accounting implementation
 */
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
     * All available outcoming operations as id=>outcomeData
     *
     * @var array
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
     * Returns database abstraction layer placeholder
     *
     * @var object
     */
    protected $returnsDb = '';

    /**
     * Contains all returns operation as outcomeid=>returnData
     *
     * @var array
     */
    protected $allReturns = array();

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
     * Recommended price calculation mode 
     *  0 - disabled, 1 - enabled, 2 - latest income price only, 3 - latest outcome price, 
     *  4 - latest outcome price, if empty - latest income price
     * @var int
     */
    protected $recPriceFlag = 0;

    /**
     * Recommended or average prices caching timeout
     *
     * @var int
     */
    protected $pricesCachingTimeout = 86400;

    /**
     * Contains array of cached middle itemtype prices as itemtypeId=>price
     *
     * @var array
     */
    protected $cachedPrices = array();

    /**
     * Default constants/routes/URLS etc..
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
    const ROUTE_DELOUT = 'outcomedelete';
    const ROUTE_DELIN = 'incomedelete';
    const PROUTE_MASSRESERVEOUT = 'massoutreserves';
    const PROUTE_MASSAGREEOUT = 'massoutagreement';
    const PROUTE_MASSNETWOUT = 'massoutnetwcb';
    const PROUTE_DOMASSRESOUT = 'runmassoutreserve';
    const PROUTE_RETURNOUTID = 'newreturnoutcomeid';
    const PROUTE_RETURNSTORAGE = 'newreturnstorageid';
    const PROUTE_RETURNPRICE = 'newreturnprice';
    const PROUTE_RETURNNOTE = 'newreturnnote';
    const PROUTE_EMPREPLACE = 'massoutemployeereplace';
    const PHOTOSTORAGE_SCOPE = 'WAREHOUSEITEMTYPE';

    /**
     * Default debug log path
     */
    const LOG_PATH = 'exports/whdebug.log';

    /**
     * Some caching default timeout
     */
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
        $this->initReturns();
        if (empty($taskid)) {
            $this->loadReserve();
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
        if (isset($this->altCfg['WAREHOUSE_TELEGRAM']) and $this->altCfg['WAREHOUSE_TELEGRAM']) {
            $this->telegramNotify = true;
        }

        if (isset($this->altCfg['WAREHOUSE_RECPRICE']) and $this->altCfg['WAREHOUSE_RECPRICE']) {
            $this->recPriceFlag = $this->altCfg['WAREHOUSE_RECPRICE'];
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
     * Inits returns database abstraction layer
     * 
     * @return void
     */
    protected function initReturns() {
        if (@$this->altCfg['WAREHOUSE_RETURNS_ENABLED']) {
            $this->returnsDb = new NyanORM('wh_returns');
        }
    }

    /**
     * Loads all existing return operations from database into protected prop
     * 
     * @return void
     */
    protected function loadReturns() {
        if (@$this->altCfg['WAREHOUSE_RETURNS_ENABLED']) {
            $this->allReturns = $this->returnsDb->getAll('outid');
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
        if (empty($this->taskOutsCache)) {
            $this->taskOutsCache = array();
        }
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
                if (($each['storageid'] == $storageId) and ($each['itemtypeid'] == $itemtypeId)) {
                    $result += $each['count'];
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
        $query .= "(NULL,'" . $reserveId . "','" . $curdate . "','" . $type . "'," . $storageId . "," . $itemtypeId . "," . $count . "," . $employeeId . ",'" . $adminLogin . "');";
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
     * @param int $reserveId
     * 
     * @return void
     */
    protected function reserveCreationNotify($storageId, $itemtypeId, $count, $employeeId, $reserveId = '') {
        if ($this->telegramNotify) {
            $message = '';
            $adminLogin = whoami();
            $adminName = (isset($this->allEmployeeLogins[$adminLogin])) ? $this->allEmployeeLogins[$adminLogin] : $adminLogin;
            $message .= __('From warehouse storage') . ' 📦 ' . $this->allStorages[$storageId] . '\r\n ';
            $message .= '👤 ' . $adminName . ' ' . __('reserved for you') . ' ' . '❤️️' . ' : ';
            $message .= $this->allItemTypeNames[$itemtypeId] . ' ' . $count . ' ' . $this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']] . '\r\n ';
            $message .= __('Reserve') . '@' . $reserveId . ' 🔒';
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
                            $reserveTmp[$employeeId][$itemtypeId] += $itemCount;
                        } else {
                            $reserveTmp[$employeeId][$itemtypeId] = $itemCount;
                        }
                    }
                }


                if (!empty($reserveTmp)) {
                    foreach ($reserveTmp as $eachEmployee => $reservedItems) {
                        $totalCostSumm = 0;
                        $message = __('Is reserved for you') . '\r\n ';;
                        foreach ($reservedItems as $eachItemId => $eachItemCount) {
                            $message .= @$this->allItemTypeNames[$eachItemId] . ': ' . $eachItemCount . ' ' . @$this->unitTypes[$this->allItemTypes[$eachItemId]['unit']] . '\r\n ';
                            $itemCost = $this->getIncomeMiddlePrice($eachItemId);
                            $totalCostSumm += $itemCost * $eachItemCount;
                        }

                        $message .= '📦📦📦📦' . '\r\n '; // very vsrate emoji
                        $message .= __('Total cost') . ': ' . $totalCostSumm . '\r\n '; //pugalo inside
                        $message .= '💸💸💸💸' . '\r\n '; // dont ask me why
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
                        $this->reserveCreationNotify($storageId, $itemtypeId, $count, $employeeId, $newId);
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
                            $result .= $reserveResult;
                        } else {
                            //success!
                            $result .= $this->messages->getStyledMessage($this->allItemTypeNames[$itemtypeId] . '. ' . __('Reserved') . ' (' . $itemCount . ' ' . @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']] . ')', 'success');
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
     * Renders current dayly items reserved for some employee
     * 
     * @param int $employeeId
     * 
     * @return string
     */
    protected function reserveRenderTodayReserved($employeeId) {
        $employeeId = vf($employeeId, 3);
        $result = '';
        $this->loadReserveHistory();
        if (!empty($this->allReserveHistory)) {
            $curDate = curdate();
            foreach ($this->allReserveHistory as $io => $each) {
                if ($each['employeeid'] == $employeeId) {
                    if ($each['type'] == 'create') {
                        if (ispos($each['date'], $curDate)) {
                            $itemtypeId = $each['itemtypeid'];
                            $label = @$this->allItemTypeNames[$itemtypeId] . '. ' . __('Already reserved today') . ' (' . $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']] . ')';
                            $result .= $this->messages->getStyledMessage($label, 'info');
                        }
                    }
                }
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
        $employeeTmp += $this->activeEmployee;
        $storageTmp = array('' => '-');
        $storageTmp += $this->allStorages;

        if (!empty($this->allStorages)) {
            $inputs = wf_SelectorAC('newmassemployeeid', $employeeTmp, __('Employee'), @$_POST['newmassemployeeid'], true);
            if (wf_CheckPost(array('newmassemployeeid'))) {
                $inputs .= wf_SelectorAC('newmassstorageid', $storageTmp, __('Warehouse storage'), @$_POST['newmassstorageid'], true);
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
                            $cells .= wf_TableCell(__('Warehouse item types'));
                            $cells .= wf_TableCell(__('Count'));
                            $cells .= wf_TableCell(__('Reserve'));
                            $rows = wf_TableRow($cells, 'row1');
                            foreach ($realRemains as $itemtypeId => $itemCount) {
                                $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeId, @$this->allItemTypeNames[$itemtypeId]);
                                $cells = wf_TableCell(@$this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']]);
                                $cells .= wf_TableCell($itemTypeLink);
                                $cells .= wf_TableCell($itemCount . ' ' . @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']]);
                                $cells .= wf_TableCell(wf_TextInput('newmassitemtype_' . $itemtypeId, '', '0', false, 4));
                                $rows .= wf_TableRow($cells, 'row5');
                            }
                            $inputs .= wf_TableBody($rows, '100%', 0, '');
                            $inputs .= wf_CheckInput('newmasscreation', __('I`m ready'), true, false);
                            $inputs .= wf_tag('br');
                            $inputs .= wf_Submit(__('Reservation'));
                        }
                    } else {
                        $emptyWarehouse = true;
                    }
                }
            }

            $result .= wf_Form('', 'POST', $inputs, '');
            if (wf_CheckPost(array('newmassemployeeid'))) {
                $result .= $this->reserveRenderTodayReserved(@$_POST['newmassemployeeid']);
            }
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

                $result .= $this->messages->getStyledMessage(__('Warehouse storage is empty'), 'warning');
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
                    $inputs .= wf_HiddenInput('newreservestorageid', $storageId);
                    $inputs .= wf_Selector('newreserveemployeeid', $this->activeEmployee, __('Worker'), '', true);
                    $inputs .= wf_TextInput('newreservecount', $itemtypeUnit . ' (' . ($itemRemainsStorage - $alreadyReserved) . ' ' . __('maximum') . ')', '', true, 5);
                    $inputs .= wf_Submit(__('Create'));

                    $form = wf_Form('', 'POST', $inputs, 'glamour');
                    $remainsText = __('At storage') . ' ' . $this->allStorages[$storageId] . ' ' . __('remains') . ' ' . $itemRemainsStorage . ' ' . $itemtypeUnit . ' ' . $itemtypeName;
                    $remainsInfo = $this->messages->getStyledMessage($remainsText, 'success');

                    if ($alreadyReserved) {
                        $remainsInfo .= $this->messages->getStyledMessage(__('minus') . ' ' . $alreadyReserved . ' ' . __('already reserved'), 'info');
                    }

                    $cells = wf_TableCell($form, '40%');
                    $cells .= wf_TableCell($remainsInfo, '', '', 'valign="top"');
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
     * @param bool $hideEmployee
     * 
     * @return string
     */
    public function reserveEditForm($id, $hideEmployee = false) {
        $id = vf($id, 3);
        $result = '';
        if (isset($this->allReserve[$id])) {
            $reserveData = $this->allReserve[$id];
            $reserveStorage = $reserveData['storageid'];
            @$itemName = $this->allItemTypeNames[$reserveData['itemtypeid']];
            @$itemData = $this->allItemTypes[$reserveData['itemtypeid']];
            @$itemUnit = $this->unitTypes[$itemData['unit']];

            if ($hideEmployee) {
                $inputs = wf_HiddenInput('editreserveemployeeid', $reserveData['employeeid']);
            } else {
                $inputs = wf_Selector('editreserveemployeeid', $this->activeEmployee, __('Worker'), $reserveData['employeeid'], true);
            }

            $inputs .= wf_TextInput('editreservecount', $itemUnit . ' ' . $itemName, $reserveData['count'], true, 5);
            $inputs .= wf_HiddenInput('editreserveid', $id);
            $inputs .= wf_Submit(__('Save'));
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
            if (empty($oldCount)) {
                $oldCount = 0;
            }
            if (empty($count)) {
                $count = 0;
            }
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
            $columns = array(
                __('ID'),
                __('Creation date'),
                __('Warehouse storage'),
                __('Category'),
                __('Warehouse item type'),
                __('Count'),
                __('Worker'),
                __('Actions')
            );

            $opts = '"order": [[ 0, "desc" ]]';

            $callbackUrl = self::URL_ME . '&' . self::URL_RESERVE . '&reserveajlist=true';
            if (ubRouting::checkGet('empidfilter')) {
                $callbackUrl .= '&empidfilter=' . ubRouting::get('empidfilter', 'int');
            }
            $result = wf_JqDtLoader($columns, $callbackUrl, false, __('Reserved'), 50, $opts);
        } else {
            $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
        }

        if ($printFlag) {
            $this->loadReserveHistory();
            //Printable report here
            if (!empty($this->allReserve)) {
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('Creation date'));
                $cells .= wf_TableCell(__('Warehouse storage'));
                $cells .= wf_TableCell(__('Category'));
                $cells .= wf_TableCell(__('Warehouse item type'));
                $cells .= wf_TableCell(__('Count'));
                $cells .= wf_TableCell(__('Worker'));

                $rows = wf_TableRow($cells, 'row1');
                foreach ($this->allReserve as $io => $each) {
                    $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], @$this->allItemTypeNames[$each['itemtypeid']]);
                    $cells = wf_TableCell($each['id']);
                    $cells .= wf_TableCell($this->reserveGetCreationDate($each['id']));
                    $cells .= wf_TableCell(@$this->allStorages[$each['storageid']]);
                    $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                    $cells .= wf_TableCell($itemTypeLink);
                    $cells .= wf_TableCell($each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']]);
                    $cells .= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result = wf_TableBody($rows, '100%', 0, 'sortable');
            }
            $this->reportPrintable(__('Reserved'), $result);
        } else {
            return ($result);
        }
    }

    /**
     * Renders JSON of available reserves list
     * 
     * @param int $employeeId
     * 
     * @return void
     */
    public function reserveListAjaxReply($employeeId = '') {
        $json = new wf_JqDtHelper();
        $employeeId = ubRouting::filters($employeeId, 'int');
        $hideEmployee = (empty($employeeId)) ? true : false;
        $filtered = true;
        if (!empty($this->allReserve)) {
            $this->loadReserveHistory();
            foreach ($this->allReserve as $io => $each) {
                if ($employeeId) {
                    if ($each['employeeid'] == $employeeId) {
                        $filtered = true;
                    } else {
                        $filtered = false;
                    }
                }

                if ($filtered) {
                    $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], @$this->allItemTypeNames[$each['itemtypeid']]);
                    $data[] = $each['id'];
                    $data[] = $this->reserveGetCreationDate($each['id']);
                    $data[] = @$this->allStorages[$each['storageid']];
                    $data[] = @$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']];
                    $data[] = $itemTypeLink;
                    $data[] = $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                    $employeeLinkUrl = self::URL_ME . '&' . self::URL_RESERVE . '&' . 'empidfilter=' . $each['employeeid'];
                    $employeeLinkAct = wf_Link($employeeLinkUrl, @$this->allEmployee[$each['employeeid']]);
                    $data[] = $employeeLinkAct;

                    $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_RESERVE . '&deletereserve=' . $each['id'], web_delete_icon(), $this->messages->getEditAlert()) . ' ';
                    $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . __('Reservation'), $this->reserveEditForm($each['id'], $hideEmployee), '') . ' ';
                    if ($each['count'] > 0) {
                        if (cfr('WAREHOUSEOUTRESERVE')) {
                            $outcomeUrl = self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $each['storageid'] . '&outitemid=' . $each['itemtypeid'] . '&reserveid=' . $each['id'];
                            $actLinks .= wf_Link($outcomeUrl, wf_img('skins/whoutcoming_icon.png') . ' ' . __('Outcoming'), false, '');
                        }
                    }
                    $data[] = $actLinks;

                    $json->addRow($data);
                    unset($data);
                }
            }
        }
        $json->getJson();
    }

    /**
     * Returns employee name by its ID
     * 
     * @param int $employeeId
     * 
     * @return string 
     */
    public function getEmployeeName($employeeId) {
        $employeeId = ubRouting::filters($employeeId, 'int');
        $result = '';
        if (isset($this->allEmployee[$employeeId])) {
            $result .= $this->allEmployee[$employeeId];
        }
        return ($result);
    }

    /**
     * Renders mass out employee replacement form and performs some redirects if required.
     * 
     * @param int $employeeId
     * 
     * @return string
     */
    public function renderMassOutEmployyeReplaceForm($employeeId) {
        $result = '';

        //redirect to new employee reserve
        if (ubRouting::checkPost(self::PROUTE_EMPREPLACE)) {
            $newEmpId = ubRouting::post(self::PROUTE_EMPREPLACE, 'int');
            $newRoute = self::URL_ME . '&' . self::URL_RESERVE . '&massoutemployee=' . $newEmpId;
            if (ubRouting::checkGet('taskidpreset')) {
                $taskId = ubRouting::get('taskidpreset', 'int');
                $newRoute .= '&taskidpreset=' . $taskId;
            }
            ubRouting::nav($newRoute);
        }

        //build some form
        if (!empty($this->activeEmployee)) {
            $inputs = wf_Selector(self::PROUTE_EMPREPLACE, $this->activeEmployee, __('Worker'), $employeeId, false) . ' ';
            $inputs .= wf_Submit(__('Change'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('No job types and employee available'), 'error');
        }
        return ($result);
    }

    /**
     * Renders mass outcome form for some employeeId reserved items
     * 
     * @param int $employeeId
     * 
     * @return string
     */
    public function renderMassOutForm($employeeId) {
        $result = '';
        $employeeId = ubRouting::filters($employeeId, 'int');
        $employeeInventory = array();
        $form = '';
        $form .= wf_FormDisabler();
        if (!empty($employeeId)) {
            if (isset($this->allEmployee[$employeeId])) {
                if (!empty($this->allReserve)) {
                    foreach ($this->allReserve as $reserveId => $reserveData) {
                        if ($reserveData['employeeid'] == $employeeId) {
                            $employeeInventory[$reserveId] = $reserveData;
                        }
                    }
                }

                if (!empty($employeeInventory)) {
                    $result .= wf_AjaxLoader();
                    //destination interface
                    $tmpDests = array();
                    foreach ($this->outDests as $destMark => $destName) {
                        $tmpDests[self::URL_ME . '&' . self::URL_OUT . '&' . self::URL_AJODSELECTOR . $destMark] = $destName;
                    }

                    $inputs = wf_HiddenInput(self::PROUTE_DOMASSRESOUT, $employeeId);
                    $inputs .= wf_AjaxSelectorAC('ajoutdestselcontainer', $tmpDests, __('Destination'), '', false);
                    $inputs .= wf_AjaxContainer('ajoutdestselcontainer', '', $this->outcomindAjaxDestSelector('task'));

                    $form .= $inputs;
                    $form .= wf_delimiter(0);
                    //reserves interface
                    $cells = wf_TableCell(__('Creation date'));
                    $cells .= wf_TableCell(__('Warehouse storage'));
                    $cells .= wf_TableCell(__('Category'));
                    $cells .= wf_TableCell(__('Warehouse item type'));
                    $cells .= wf_TableCell(__('Reserved'));
                    $cells .= wf_TableCell(__('Count'));
                    $cells .= wf_TableCell(__('Price'));
                    $cells .= wf_TableCell(__('Notes'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($employeeInventory as $eachInvId => $eachInvData) {
                        $midPriceNotice = '';
                        $itemTypeId = $eachInvData['itemtypeid'];
                        $itemTypeCategory = $this->allCategories[$this->allItemTypes[$itemTypeId]['categoryid']];
                        $itemTypeName = $this->allItemTypeNames[$itemTypeId];
                        $itemTypeStorageId = $this->allStorages[$eachInvData['storageid']];
                        $itemTypeUnit = $this->allItemTypes[$itemTypeId]['unit'];
                        $itemTypeRecPrice = $this->getIncomeMiddlePrice($itemTypeId);
                        $midPriceLabel = ($this->recPriceFlag) ? __('recommended') : __('middle price');
                        $hintTitle = $midPriceLabel . ': ' . $itemTypeRecPrice;
                        $priceInputId = wf_InputId() . '_price_' . $itemTypeId;
                        if ($this->recPriceFlag) {
                            $priceClickValue = addcslashes((string) $itemTypeRecPrice, "\\'\"");
                            $priceClickOptions = 'href="#" ';
                            $priceClickOptions .= 'onclick="';
                            $priceClickOptions .= 'event.preventDefault(); ';
                            $priceClickOptions .= 'var priceInput = document.getElementById(\'' . $priceInputId . '\'); ';
                            $priceClickOptions .= 'if (priceInput) { ';
                            $priceClickOptions .= "priceInput.value = '{$priceClickValue}'; ";
                            $priceClickOptions .= 'priceInput.focus(); ';
                            $priceClickOptions .= '}" ';
                            $priceClickOptions .= 'title="' . $hintTitle . '"';
                            $midPriceNotice .= wf_delimiter(0);
                            $midPriceNotice .= wf_tag('a', false, '', $priceClickOptions) . $itemTypeRecPrice . wf_tag('a', true);
                        } else {
                            $midPriceNotice .= wf_tag('abbr', false, '', 'title="' . $hintTitle . '"') . '?' . wf_tag('abbr', true);
                        }

                        $cells = wf_TableCell($this->reserveGetCreationDate($eachInvId));
                        $cells .= wf_TableCell($itemTypeStorageId);
                        $cells .= wf_TableCell($itemTypeCategory);
                        $cells .= wf_TableCell($itemTypeName);
                        $cells .= wf_TableCell($eachInvData['count'] . ' ' . __($itemTypeUnit));
                        $priceInput=wf_TextInput(self::PROUTE_MASSRESERVEOUT . '[' . $eachInvId . '][price]', $midPriceNotice, '', false, 6, 'finance', '', $priceInputId);
                        $cells .= wf_TableCell(wf_TextInput(self::PROUTE_MASSRESERVEOUT . '[' . $eachInvId . '][count]', $itemTypeUnit, '', false, 4, 'float'));
                        $cells .= wf_TableCell($priceInput);
                        $defaultNotePreset = '';
                        $cells .= wf_TableCell(wf_TextInput(self::PROUTE_MASSRESERVEOUT . '[' . $eachInvId . '][note]', '', $defaultNotePreset, false, 15));
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $form .= wf_TableBody($rows, '100%', 0, 'sortable');
                    $form .= wf_CheckInput(self::PROUTE_MASSNETWOUT, __('Network'), true, false);
                    $form .= wf_delimiter(0);
                    $massOutAgreement = __('I`m ready') . '. ';
                    $massOutAgreement .= __('I also understand well that no one will correct my mistakes for me and only I bear full financial responsibility for my mistakes') . '.';
                    $form .= wf_CheckInput(self::PROUTE_MASSAGREEOUT, $massOutAgreement, true, false);
                    $form .= wf_delimiter(0);
                    $form .= wf_Submit(__('Mass outcome'));
                    $result .= wf_Form('', 'POST', $form, '');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Nothing reserved for this employee'), 'error');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Worker') . ' [' . $employeeId . '] ' . __('Not exists'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Strange exeption') . ' EX_EMPLOYEEID_EMPTY', 'error');
        }
        return ($result);
    }

    /**
     * Runs batch outcome operations creation based on reserve
     * 
     * @return void/string on error
     */
    public function runMassReserveOutcome() {
        $result = '';
        $outCount = 0;
        if (ubRouting::checkPost(self::PROUTE_DOMASSRESOUT)) {
            if (ubRouting::checkPost(self::PROUTE_MASSAGREEOUT)) {
                if (ubRouting::checkPost(array('newoutdesttype', 'newoutdestparam', 'massoutreserves'))) {
                    $employeeId = ubRouting::post(self::PROUTE_DOMASSRESOUT);
                    $outDestType = ubRouting::post('newoutdesttype');
                    $outDestParam = ubRouting::post('newoutdestparam');
                    $outResArr = ubRouting::post(self::PROUTE_MASSRESERVEOUT);
                    $netwFlag = ubRouting::checkPost(self::PROUTE_MASSNETWOUT) ? true : false;
                    $curDate = curdate();
                    if (!empty($outResArr)) {
                        if (is_array($outResArr)) {
                            foreach ($outResArr as $eachReserveId => $eachReserveData) {
                                if (isset($this->allReserve[$eachReserveId])) {
                                    if ($eachReserveData['count'] > 0) {
                                        $reserveOpts = $this->allReserve[$eachReserveId];
                                        $storageId = $reserveOpts['storageid'];
                                        $itemtypeId = $reserveOpts['itemtypeid'];
                                        $count = $eachReserveData['count'];
                                        $price = $eachReserveData['price'];
                                        $defaultNote = ' ' . __('from reserved on') . ' ' . @$this->allEmployee[$employeeId];
                                        $outcomeNote = (!empty($eachReserveData['note'])) ? $defaultNote . ' ' . $eachReserveData['note'] : $defaultNote;
                                        $eachOutcomeResult = $this->outcomingCreate($curDate, $outDestType, $outDestParam, $storageId, $itemtypeId, $count, $price, $outcomeNote, $eachReserveId, $netwFlag);
                                        if (!empty($eachOutcomeResult)) {
                                            $itemtypeIssueLabel = __('Problem') . ': ' . $this->allItemTypeNames[$itemtypeId];
                                            $result .= $this->messages->getStyledMessage($itemtypeIssueLabel, 'warning');
                                            $result .= $eachOutcomeResult;
                                            log_register('WAREHOUSE RESMASSOUT FAIL ITEMID [' . $itemtypeId . '] COUNT `' . $count . '`');
                                            //Saving debug log
                                            file_put_contents(self::LOG_PATH, '==================' . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, curdatetime() . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, 'GET DATA:' . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, print_r(ubRouting::rawGet(), true) . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, 'POST DATA:' . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, print_r(ubRouting::rawPost(), true) . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, 'RESERVE OPTS:' . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, print_r($reserveOpts, true) . PHP_EOL, FILE_APPEND);
                                            file_put_contents(self::LOG_PATH, 'INVOKES:' . $itemtypeIssueLabel . ' ' . strip_tags($eachOutcomeResult) . PHP_EOL, FILE_APPEND);
                                        }
                                        $outCount++;
                                    }
                                } else {
                                    $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Reserve') . ' [' . $eachReserveId . '] ' . __('Not exists'), 'error');
                                    log_register('WAREHOUSE RESMASSOUT FAIL RESERVE [' . $eachReserveId . '] NOT EXISTS');
                                }
                            }

                            if ($outCount == 0) {
                                $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Outcoming operations') . ' - 0', 'warning');
                                log_register('WAREHOUSE RESMASSOUT FAIL ZERO OUTCOMES');
                            }
                        } else {
                            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ' EX_CORRUPT_RESARR', 'error');
                            log_register('WAREHOUSE RESMASSOUT FAIL CORRUPT_RESARR');
                        }
                    } else {
                        $result .= $this->messages->getStyledMessage(__('Something went wrong') . ' EX_EMPTY_RESARR', 'error');
                        log_register('WAREHOUSE RESMASSOUT FAIL EMPTY_RESARR');
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('You are not mentally prepared for this'), 'error');
                log_register('WAREHOUSE RESMASSOUT FAIL NO_AGREEMENT');
            }
        }
        return ($result);
    }

    /**
     * Renders printable per-employee reserves summary.
     * 
     * @param int $employeeId
     * 
     * @return void
     */
    public function reportEmployeeInventrory($employeeId) {
        $employeeId = ubRouting::filters($employeeId, 'int');
        $reportTmp = array();
        $result = '';
        if (!empty($employeeId)) {
            $filtered = true;
            if (!empty($this->allReserve)) {
                foreach ($this->allReserve as $io => $each) {
                    if ($each['employeeid'] == $employeeId) {
                        if (isset($reportTmp[$each['itemtypeid']])) {
                            $reportTmp[$each['itemtypeid']] += $each['count'];
                        } else {
                            $reportTmp[$each['itemtypeid']] = $each['count'];
                        }
                    }
                }
            }

            if (!empty($reportTmp)) {
                $cells = wf_TableCell(__('Category'));
                $cells .= wf_TableCell(__('Warehouse item type'));
                $cells .= wf_TableCell(__('Expected count') . ' (' . __('Reserved') . ')');
                if (ubRouting::checkGet('invprintable')) {
                    $cells .= wf_TableCell(__('Notes'));
                }

                $rows = wf_TableRow($cells, 'row1');
                foreach ($reportTmp as $itemTypeId => $count) {
                    $cells = wf_TableCell(@$this->allCategories[$this->allItemTypes[$itemTypeId]['categoryid']]);
                    $cells .= wf_TableCell(@$this->allItemTypeNames[$itemTypeId]);
                    $cells .= wf_TableCell($count . ' ' . @$this->unitTypes[$this->allItemTypes[$itemTypeId]['unit']]);
                    if (ubRouting::checkGet('invprintable')) {
                        $cells .= wf_TableCell('');
                    }
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                if (ubRouting::checkGet('invprintable')) {
                    //printable inventory report
                    $this->reportPrintable(__('Employee inventory') . ': ' . @$this->allEmployee[$employeeId], $result);
                } else {
                    //normal renderer
                    $inventoryUrl = self::URL_ME . '&' . self::URL_RESERVE . '&empinventory=' . $employeeId . '&invprintable=true';
                    $reportControls = wf_Link($inventoryUrl, web_icon_print());
                    show_window(__('Employee inventory') . ': ' . @$this->allEmployee[$employeeId] . ' ' . $reportControls, $result);
                }
            } else {
                show_info(__('Nothing to show'));
            }
        } else {
            show_error(__('Something went wrong') . ' EX_EMPLOYEEID_EMPTY');
        }
    }

    /**
     * Renders json list of available reservation history log entries
     * 
     * @return void
     */
    public function reserveHistoryAjaxReply() {
        $json = new wf_JqDtHelper();
        $this->loadReserveHistory();
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
     * Returns array of available administrators as login=>name
     * 
     * @return array
     */
    protected function getAdminNames() {
        $result = array();
        $all = rcms_scandir(USERS_PATH);
        if (!empty($all)) {
            $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
            foreach ($all as $each) {
                $administratorName = (isset($employeeLogins[$each])) ? $employeeLogins[$each] : $each;
                $result[$each] = $administratorName;
            }
        }
        return ($result);
    }

    /**
     * Renders reserve history print filtering form
     * 
     * @return string
     */
    public function reserveHistoryFilterForm() {
        $result = '';
        $adminNames = array('' => '-');
        $adminNames += $this->getAdminNames();
        $inputs = __('From') . ' ' . wf_DatePickerPreset('reshistfilterfrom', curdate()) . ' ';
        $inputs .= __('To') . ' ' . wf_DatePickerPreset('reshistfilterto', curdate()) . ' ';
        $inputs .= wf_Selector('reshistfilteremployeeid', $this->activeEmployee, __('Worker'), '', false);
        $inputs .= wf_Selector('reshistfilteradminlogin', $adminNames, __('Admin'), '', false);
        $inputs .= wf_Submit(__('Print'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders printable report of reserve operations history
     * 
     * @return void
     */
    public function reserveHistoryPrintFiltered() {
        $result = '';
        $this->loadReserveHistory();
        if (wf_CheckPost(array('reshistfilterfrom', 'reshistfilterto', 'reshistfilteremployeeid'))) {
            $dateFrom = $_POST['reshistfilterfrom'];
            $dateTo = $_POST['reshistfilterto'];
            $employeeId = vf($_POST['reshistfilteremployeeid'], 3);
            $adminLogin = @$_POST['reshistfilteradminlogin'];

            if (zb_checkDate($dateFrom) and zb_checkDate($dateTo)) {
                $dateFrom = $dateFrom . ' 00:00:00';
                $dateTo = $dateTo . ' 23:59:59';
                $dateFrom = strtotime($dateFrom);
                $dateTo = strtotime($dateTo);

                if (!empty($this->allReserveHistory)) {
                    $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());

                    $cells = wf_TableCell(__('ID'));
                    $cells .= wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Type'));
                    $cells .= wf_TableCell(__('Warehouse storage'));
                    $cells .= wf_TableCell(__('Category'));
                    $cells .= wf_TableCell(__('Warehouse item type'));
                    $cells .= wf_TableCell(__('Count'));
                    $cells .= wf_TableCell(__('Employee'));
                    $cells .= wf_TableCell(__('Admin'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($this->allReserveHistory as $io => $each) {
                        $operationDate = strtotime($each['date']);
                        $filteredFlag = false;

                        //data filtering
                        if ($employeeId == $each['employeeid']) {
                            if ($operationDate >= $dateFrom and $operationDate <= $dateTo) {
                                $filteredFlag = true;
                            }
                        }

                        //optional admin filtering
                        if (!empty($adminLogin)) {
                            if ($filteredFlag) {
                                if ($each['admin'] != $adminLogin) {
                                    $filteredFlag = false;
                                }
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
                            $cells .= wf_TableCell($each['date']);
                            $cells .= wf_TableCell($operationType);
                            $cells .= wf_TableCell(@$this->allStorages[$each['storageid']]);
                            $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                            $cells .= wf_TableCell(@$this->allItemTypeNames[$each['itemtypeid']]);
                            $cells .= wf_TableCell($each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']]);
                            $cells .= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                            $cells .= wf_TableCell($administratorName);
                            $rows .= wf_TableRow($cells, 'row3');
                        }
                    }

                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= wf_tag('br');
                    $result .= __('Signature') . '______________________';

                    $this->reportPrintable(__('Act of issuance of goods from the warehouse'), $result);
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
        $colums = array('ID', 'Date', 'Type', 'Warehouse storage', 'Category', 'Warehouse item type', 'Count', 'Employee', 'Admin');
        $opts = '"order": [[ 1, "desc" ]]';
        $ajaxUrl = self::URL_ME . '&' . self::URL_RESERVE . '&reshistajlist=true';

        $result .= wf_JqDtLoader($colums, $ajaxUrl, false, __('Reserve'), 50, $opts);
        $result .= wf_delimiter();
        $result .= $this->reserveHistoryFilterForm();

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
        $inputs .= wf_Submit(__('Create'));
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
        $inputs .= wf_HiddenInput('editcategoryid', $categoryId);
        $inputs .= wf_Submit(__('Save'));
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
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allCategories)) {
            foreach ($this->allCategories as $id => $name) {
                $cells = wf_TableCell($id);
                $cells .= wf_TableCell($name);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_CATEGORIES . '&deletecategory=' . $id, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->categoriesEditForm($id));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
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
     * returns report icon and link
     * 
     * @return string
     */
    protected function buildReportTask($link, $icon, $text) {
        $task_link = $link;
        $task_icon = $icon;
        $task_text = $text;
        $tbiconsize = 128;

        $template = wf_tag('div', false, 'dashtask', 'style="height:' . ($tbiconsize + 30) . 'px; width:' . ($tbiconsize + 30) . 'px;"');
        $template .= wf_tag('a', false, '', 'href="' . $task_link . '"');
        $template .= wf_img_sized($task_icon, $task_text, $tbiconsize, $tbiconsize);
        $template .= wf_tag('a', true);
        $template .= wf_tag('br');
        $template .= wf_tag('br');
        $template .= $task_text;
        $template .= wf_tag('div', true);
        return ($template);
    }


    /**
     * Renders control panel for whole module
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result .= wf_Link(self::URL_ME . '&' . self::URL_REPORTS . '&' . 'totalremains=true', wf_img_sized('skins/whstorage_icon.png') . ' ' . __('The remains in all storages'), false, 'ubButton');
        if (cfr('WAREHOUSEIN')) {
            $result .= wf_Link(self::URL_ME . '&' . self::URL_IN, wf_img_sized('skins/whincoming_icon.png') . ' ' . __('Incoming operations'), false, 'ubButton');
        }
        if ((cfr('WAREHOUSEOUT')) or (cfr('WAREHOUSEOUTRESERVE'))) {
            $result .= wf_Link(self::URL_ME . '&' . self::URL_OUT, wf_img_sized('skins/whoutcoming_icon.png') . ' ' . __('Outcoming operations'), false, 'ubButton');
        }

        if (cfr('WAREHOUSERESERVE')) {
            $result .= wf_Link(self::URL_ME . '&' . self::URL_RESERVE, wf_img('skins/whreservation.png') . ' ' . __('Reserved'), false, 'ubButton');
        }

        if (cfr('WAREHOUSEDIR')) {
            $dirControls = '';
            $dirControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_CATEGORIES, 'skins/taskbar/whcategories.png', __('Warehouse categories'));
            $dirControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_ITEMTYPES, 'skins/taskbar/whitemtypes.png', __('Warehouse item types'));
            $dirControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_STORAGES, 'skins/taskbar/whstorage.png', __('Warehouse storages'));
            $dirControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_CONTRACTORS, 'skins/taskbar/whcontractors.png', __('Contractors'));
            $result .= wf_modalAuto(web_icon_extended() . ' ' . __('Directories'), __('Directories'), $dirControls, 'ubButton');
        }

        if (cfr('WAREHOUSEREPORTS')) {
            $reportControls = '';
            if (@$this->altCfg['WAREHOUSE_RETURNS_ENABLED']) {
                $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&returns=true', 'skins/taskbar/whreturns.png', __('Returns'));
            }

            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&calendarops=true', 'skins/taskbar/whcalendar.png', __('Operations in the context of time'));
            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&dateremains=true', 'skins/taskbar/whbat.png', __('Date remains'));
            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&storagesremains=true', 'skins/taskbar/whremains.png',  __('The remains in the warehouse storage'));
            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&itemtypeoutcomes=true', 'skins/taskbar/whsaleold.png', __('Sales'));
            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&purchases=true', 'skins/shopping_cart.png', __('Purchases'));
            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&contractorincomes=true', 'skins/taskbar/whcontractors.png', __('Contractor'));
            $reportControls .= $this->buildReportTask(self::URL_ME . '&' . self::URL_REPORTS . '&netwupgrade=true', 'skins/taskbar/whnetw.png', __('Network upgrade report'));
            $reportControls .= $this->buildReportTask(WHSales::URL_ME, 'skins/taskbar/sales.png',  __('Sales report'));

            $result .= wf_modalAuto(wf_img('skins/ukv/report.png') . ' ' . __('Reports'), __('Reports'), $reportControls, 'ubButton');
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
            $inputs .= wf_TextInput('newitemtypename', __('Name'), '', true, '20');
            $inputs .= wf_Selector('newitemtypeunit', $this->unitTypes, __('Units'), '', true);
            $inputs .= wf_TextInput('newitemtypereserve', __('Desired reserve'), '', true, 5);
            $inputs .= wf_Submit(__('Create'));

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
            $inputs .= wf_TextInput('edititemtypename', __('Name'), $itemtypeData['name'], true, '20');
            $inputs .= wf_Selector('edititemtypeunit', $this->unitTypes, __('Units'), $itemtypeData['unit'], true);
            $inputs .= wf_TextInput('edititemtypereserve', __('Desired reserve'), $itemtypeData['reserve'], true, 5);
            $inputs .= wf_HiddenInput('edititemtypeid', $itemtypeId);
            $inputs .= wf_Submit(__('Save'));

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
                $nameF = $_POST['edititemtypename'];
                $nameF = str_replace('"', '``', $nameF);
                $nameF = str_replace("'", '`', $nameF);
                $where = " WHERE `id`='" . $itemtypeId . "'";
                simple_update_field('wh_itemtypes', 'categoryid', $_POST['edititemtypecetegoryid'], $where);
                simple_update_field('wh_itemtypes', 'name', $nameF, $where);
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
            $nameF = str_replace('"', '``', $nameF);
            $nameF = str_replace("'", '`', $nameF);
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
        $cells .= wf_TableCell(__('Category'));
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Units'));
        $cells .= wf_TableCell(__('Reserve'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        $photoStorageEnabled = ($this->altCfg['PHOTOSTORAGE_ENABLED']) ? true : false;
        if ($photoStorageEnabled) {
            $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, 'nope');
        }

        if (!empty($this->allItemTypes)) {
            $itemtypesList = $this->allItemTypes;
            krsort($itemtypesList); //default order from newer to older instead of order by name
            foreach ($itemtypesList as $io => $each) {
                $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['id'], $each['name']);

                $cells = wf_TableCell($each['id']);
                $cells .= wf_TableCell(@$this->allCategories[$each['categoryid']]);
                $cells .= wf_TableCell($itemTypeLink);
                $cells .= wf_TableCell(@$this->unitTypes[$each['unit']]);
                $cells .= wf_TableCell($each['reserve']);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_ITEMTYPES . '&deleteitemtype=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $actLinks .= wf_JSAlert(self::URL_ME . '&' . self::URL_ITEMTYPES . '&edititemtype=' . $each['id'], web_edit_icon(), $this->messages->getEditAlert());
                if ($photoStorageEnabled) {
                    $photostorageIcon = 'photostorage.png';
                    $itemIdImageCount = $photoStorage->getImagesCount($each['id']);
                    $photostorageUrl = '?module=photostorage&scope=' . self::PHOTOSTORAGE_SCOPE . '&itemid=' . $each['id'] . '&mode=list';
                    $photostorageCtrlLabel = __('Upload images');
                    if ($itemIdImageCount > 0) {
                        $photostorageIcon = 'photostorage_green.png';
                        $photostorageCtrlLabel .= ' (' . $itemIdImageCount . ')';
                    }
                    $photostorageControl = ' ' . wf_Link($photostorageUrl, wf_img_sized('skins/' . $photostorageIcon, $photostorageCtrlLabel, '16', '16'), false);
                } else {
                    $photostorageControl = '';
                }
                $actLinks .= $photostorageControl;

                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
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
        $inputs .= wf_Submit(__('Create'));
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
        $inputs .= wf_HiddenInput('editstorageid', $storageId);
        $inputs .= wf_Submit(__('Save'));
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
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $id => $name) {
                $cells = wf_TableCell($id);
                $cells .= wf_TableCell($name);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_STORAGES . '&deletestorage=' . $id, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->storagesEditForm($id));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
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
        $inputs .= wf_Submit(__('Create'));
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
        $nameF = ubRouting::filters($nameF, 'safe');
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
        $inputs .= wf_HiddenInput('editcontractorid', $contractorId);
        $inputs .= wf_Submit(__('Save'));
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
                simple_update_field('wh_contractors', 'name', ubRouting::post('editcontractorname', 'safe'), "WHERE `id`='" . $contractorId . "'");
                log_register('WAREHOUSE CONTRACTORS EDIT [' . $contractorId . '] `' . ubRouting::post('editcontractorname') . '`');
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
        $cells .= wf_TableCell(__('Name'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allContractors)) {
            foreach ($this->allContractors as $id => $name) {
                $cells = wf_TableCell($id);
                $cells .= wf_TableCell($name);

                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_CONTRACTORS . '&deletecontractor=' . $id, web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                $actLinks .= wf_modalAuto(web_edit_icon(), __('Edit'), $this->contractorsEditForm($id));
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row3');
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
        $searchableFlag = $this->altCfg['WAREHOUSE_INCOP_SEARCHBL'];
        $categoryItemtypes = $this->itemtypesFilterCategory($categoryId);
        if ($searchableFlag) {
            $result = wf_SelectorSearchable($name, $categoryItemtypes, __('Warehouse item types'), '', false);
        } else {
            $result = wf_Selector($name, $categoryItemtypes, __('Warehouse item types'), '', false);
        }
        if (cfr('WAREHOUSEDIR')) {
            $result .= wf_Link(self::URL_ME . '&' . self::URL_ITEMTYPES, wf_img_sized('skins/folder_icon.png', '', '10', '10'), false);
        }
        return ($result);
    }

    /**
     * Returns incoming operation creation form
     * 
     * @return string
     */
    public function incomingCreateForm() {
        if ((!empty($this->allItemTypes)) and (!empty($this->allCategories)) and (!empty($this->allContractors)) and (!empty($this->allStorages))) {
            $searchableFlag = $this->altCfg['WAREHOUSE_INCOP_SEARCHBL'];
            //ajax selector URL-s preprocessing
            $tmpCat = array();
            $firstCateKey = key($this->allCategories);
            foreach ($this->allCategories as $categoryId => $categoryName) {
                $tmpCat[self::URL_ME . '&' . self::URL_IN . '&' . self::URL_AJITSELECTOR . $categoryId] = $categoryName;
            }
            $result = wf_AjaxLoader();
            $inputs = wf_DatePickerPreset('newindate', curdate());
            $inputs .= wf_tag('br');
            if ($searchableFlag) {
                $inputs .= wf_AjaxSelectorSearchableAC('ajItemtypesContainer', $tmpCat, __('Warehouse categories'), '', false);
            } else {
                $inputs .= wf_AjaxSelectorAC('ajItemtypesContainer', $tmpCat, __('Warehouse categories'), '', false);
            }
            if (cfr('WAREHOUSEDIR')) {
                $inputs .= wf_Link(self::URL_ME . '&' . self::URL_CATEGORIES, wf_img_sized('skins/categories_icon.png', '', '10', '10'), false);
            }
            $inputs .= wf_tag('br');
            $inputs .= wf_AjaxContainer('ajItemtypesContainer', '', $this->itemtypesCategorySelector('newinitemtypeid', $firstCateKey));
            if ($searchableFlag) {
                $inputs .= wf_SelectorSearchable('newincontractorid', $this->allContractors, __('Contractor'), '', false);
            } else {
                $inputs .= wf_Selector('newincontractorid', $this->allContractors, __('Contractor'), '', false);
            }
            if (cfr('WAREHOUSEDIR')) {
                $inputs .= wf_Link(self::URL_ME . '&' . self::URL_CONTRACTORS, wf_img_sized('skins/whcontractor_icon.png', '', '10', '10'), false);
            }
            $inputs .= wf_tag('br');
            if ($searchableFlag) {
                $inputs .= wf_SelectorSearchable('newinstorageid', $this->allStorages, __('Warehouse storage'), '', false);
            } else {
                $inputs .= wf_Selector('newinstorageid', $this->allStorages, __('Warehouse storage'), '', false);
            }

            if (cfr('WAREHOUSEDIR')) {
                $inputs .= wf_Link(self::URL_ME . '&' . self::URL_STORAGES, wf_img_sized('skins/whstorage_icon.png', '', '10', '10'), false);
            }
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('newincount', __('Count'), '', false, 5, 'float');
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('newinprice', __('Price per unit'), '', false, 5, 'finance');
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('newinbarcode', __('Barcode'), '', false, 15);
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('newinnotes', __('Notes'), '', false, 30);
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Create'));
            $result .= wf_Form(self::URL_ME . '&' . self::URL_IN, 'POST', $inputs, 'glamour');
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
        $storageid = vf($storageid, 3);
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
                $data[] = round($each['price'] * $each['count'], 2);
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
            $cells .= wf_TableCell($id);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Date'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['date']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Contractor'), '30%', 'row2');
            //storage movement
            if ($operationData['contractorid'] == 0) {
                $contractorName = $operationData['notes'];
            } else {
                $contractorName = @$this->allContractors[$operationData['contractorid']];
            }

            $itemTypeLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $operationData['itemtypeid'], @$this->allItemTypeNames[$operationData['itemtypeid']]);

            $cells .= wf_TableCell($contractorName);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Category'), '30%', 'row2');
            $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$operationData['itemtypeid']]['categoryid']]);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse item type'), '30%', 'row2');
            $cells .= wf_TableCell($itemTypeLink);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Count'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['count'] . ' ' . $this->unitTypes[$this->allItemTypes[$operationData['itemtypeid']]['unit']]);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Price per unit'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['price']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Sum'), '30%', 'row2');
            $cells .= wf_TableCell(($operationData['price'] * $operationData['count']));
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse storage'), '30%', 'row2');
            $cells .= wf_TableCell($this->allStorages[$operationData['storageid']]);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Barcode'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['barcode']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Worker'), '30%', 'row2');
            $cells .= wf_TableCell($administratorName);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Notes'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['notes']);
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0, 'wh_viewer');
            //optional income editing controls
            if (cfr('WAREHOUSEINEDT')) {
                if ($this->altCfg['WAREHOUSE_INEDT_ENABLED']) {
                    if ($this->isIncomeEditable($id)) {
                        //editing form
                        $editForm = $this->incomingEditForm($id);
                        $result .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit'), __('Edit'), $editForm, 'ubButton');

                        //deletion form
                        $inDelUrl = self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $id . '&' . self::ROUTE_DELIN . '=' . $id;
                        $inDelCancelUrl = self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $id;
                        $inDelLabel = $this->messages->getDeleteAlert();
                        $result .= wf_ConfirmDialog($inDelUrl, web_delete_icon() . ' ' . __('Delete'), $inDelLabel, 'ubButton', $inDelCancelUrl, __('Delete') . '?');
                    } else {
                        $result .= $this->messages->getStyledMessage(__('This operation cannot be edited or deleted'), 'warning');
                        $result .= wf_delimiter();
                    }
                    $result .= wf_delimiter(0);
                }
            }

            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $operationData['itemtypeid']);
                $result .= $photoStorage->renderImagesRaw();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Strange exeption') . ' NO_EXISTING_INCOME_ID', 'error');
        }

        //File storage
        if (@$this->altCfg['FILESTORAGE_ENABLED']) {
            $fileStorage = new FileStorage('WAREHOUSEINCOME', $id);
            $result .= wf_tag('h3') . __('Uploaded files') . wf_tag('h3', true);
            $result .= $fileStorage->renderFilesPreview(true, '', 'ubButton', 64, '&callback=whin');
        }

        //ADcomments support
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('WAREHOUSEINCOME');
            $result .= wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
            $result .= $adcomments->renderComments($id);
        }
        return ($result);
    }

    /**
     * Renders incoming operation editing form
     * 
     * @param int $id
     * 
     * @return string
     */
    protected function incomingEditForm($id) {
        $result = '';
        if (isset($this->allIncoming[$id])) {
            $inData = $this->allIncoming[$id];
            $inputs = '<!--ugly hack to prevent datepicker autoopen -->';
            $inputs .= wf_tag('input', false, '', 'type="text" name="shittyhack" style="width: 0; height: 0; top: -100px; position: absolute;"');
            $inputs .= wf_DatePickerPreset('newindate', $inData['date']);
            $inputs .= wf_tag('br');
            $inputs .= wf_HiddenInput('editincomeid', $id);
            if ($inData['contractorid'] != 0) {
                //normal income
                $inputs .= wf_Selector('edincontractorid', $this->allContractors, __('Contractor'), $inData['contractorid'], false);
                $inputs .= wf_tag('br');
                $inputs .= wf_Selector('edinstorageid', $this->allStorages, __('Warehouse storage'), $inData['storageid'], false);
            } else {
                //storage move operation
                $inputs .= wf_HiddenInput('edincontractorid', $inData['contractorid']) . ' ' . __('Contractor') . ': ' . $inData['notes'];
                $inputs .= wf_tag('br');
                $inputs .= wf_HiddenInput('edinstorageid',  $inData['storageid']) . ' ' . __('Storage') . ': ' . $this->allStorages[$inData['storageid']];
            }


            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('edincount', __('Count'), $inData['count'], false, 5, 'float');
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('edinprice', __('Price per unit'), $inData['price'], false, 5, 'finance');
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('edinbarcode', __('Barcode'), $inData['barcode'], false, 15);
            $inputs .= wf_tag('br');
            $inputs .= wf_TextInput('edinnotes', __('Notes'), $inData['notes'], false, 30);
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Catches and performs incoming operation editing request
     * 
     * @return void/string
     */
    public function incomingSaveChanges() {
        $result = '';
        if (ubRouting::checkPost(array('editincomeid', 'newindate', 'edinstorageid', 'edincount'))) {
            $id = ubRouting::post('editincomeid', 'int');
            if ($this->isIncomeEditable($id)) {
                if ($this->altCfg['WAREHOUSE_INEDT_ENABLED']) {
                    $inData = $this->allIncoming[$id];

                    $newDate = ubRouting::post('newindate');
                    $newContractor = ubRouting::post('edincontractorid', 'int');
                    $newStorage = ubRouting::post('edinstorageid', 'int');
                    $newCount = ubRouting::post('edincount', 'mres');
                    $newCount = str_replace(',', '.', $newCount);
                    $newCount = str_replace('-', '', $newCount);
                    $newPrice = ubRouting::post('edinprice', 'mres');
                    $newPrice = str_replace(',', '.', $newPrice);
                    $newPrice = str_replace('-', '', $newPrice);
                    $newBarcode = ubRouting::post('edinbarcode', 'mres');
                    $newNotes = ubRouting::post('edinnotes', 'mres');

                    if (zb_checkDate($newDate)) {
                        $incomeDb = new NyanORM('wh_in');
                        $incomeDb->data('date', $newDate);
                        $incomeDb->data('contractorid', $newContractor);
                        $incomeDb->data('storageid', $newStorage);
                        $incomeDb->data('count', $newCount);
                        $incomeDb->data('price', $newPrice);
                        $incomeDb->data('barcode', $newBarcode);
                        $incomeDb->data('notes', $newNotes);
                        $incomeDb->where('id', '=', $id);
                        $incomeDb->save();
                        log_register('WAREHOUSE INCOME EDIT [' . $id . '] ITEM [' . $inData['itemtypeid'] . '] COUNT `' . $inData['count'] . '`=>`' . $newCount . '` PRICE `' . $inData['price'] . '`=>`' . $newPrice . '`');
                    } else {
                        $result .= __('Wrong date format');
                    }
                } else {
                    $result .= __('Disabled');
                }
            } else {
                $result .= __('This operation cannot be edited or deleted');
            }
        }
        return ($result);
    }

    /**
     * Deletes existing incoming operation
     * 
     * @param int $id
     * 
     * @return void/string
     */
    public function incomingDelete($id) {
        $result = '';
        $id = ubRouting::filters($id, 'int');
        if ($this->isIncomeEditable($id)) {
            if (@$this->altCfg['WAREHOUSE_INEDT_ENABLED']) {
                $incomeData = $this->allIncoming[$id];
                $itemtypeId = $incomeData['itemtypeid'];
                $count = $incomeData['count'];
                $price = $incomeData['price'];

                $incomeDb = new NyanORM('wh_in');
                $incomeDb->where('id', '=', $id);
                $incomeDb->delete();

                log_register('WAREHOUSE INCOME DELETE [' . $id . '] ITEM [' . $itemtypeId . '] COUNT `' . $count . '` PRICE `' . $price . '`');
            } else {
                $result .= __('Disabled');
            }
        } else {
            $result .= __('This operation cannot be edited or deleted');
        }
        return ($result);
    }

    /**
     * Checks is incoming operation existing and editable?
     * 
     * @param int $id
     * 
     * @return bool
     */
    public function isIncomeEditable($id) {
        $result = true;
        if (isset($this->allIncoming[$id])) {
            $operationData = $this->allIncoming[$id];
            $date = $operationData['date'];
            $storageId = $operationData['storageid'];
            $itemtypeId = $operationData['itemtypeid'];
            //checking outcomes
            if (!empty($this->allOutcoming)) {
                foreach ($this->allOutcoming as $io => $eachOut) {
                    if (($eachOut['itemtypeid'] == $itemtypeId) and ($eachOut['storageid'] == $storageId)) {
                        if ($eachOut['date'] >= $date) {
                            //this itemtype on this storage is already touched by outcoming operations
                            $result = false;
                            break;
                        }
                    }
                }
            }
        } else {
            //not exists
            $result = false;
        }
        return ($result);
    }

    /**
     * Returns outcoming operation creation form
     * 
     * @param bool $noOutControls not render storages outcoming controls
     * 
     * @return string
     */
    public function outcomingStoragesList($noOutControls = false) {
        $result = '';
        if (!empty($this->allStorages)) {

            $cells = wf_TableCell(__('Warehouse storage'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allStorages as $io => $each) {
                $storageId = $io;
                if ($noOutControls) {
                    $conrolLink = $each;
                } else {
                    $conrolLink = wf_Link(self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $storageId, $each, false, '');
                }
                $remainsLabel = wf_img('skins/icon_print.png', __('The remains in the warehouse storage') . ': ' . $each);
                $remainsPrintControls = ' ' . wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&printremainsstorage=' . $storageId, $remainsLabel);
                $cells = wf_TableCell($conrolLink);
                $cells .= wf_TableCell($remainsPrintControls);
                $rows .= wf_TableRow($cells, 'row5');
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
                        $result[$each['itemtypeid']] += $each['count'];
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
                        $result[$each['itemtypeid']] -= $each['count'];
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
                            $result[$itemtypeId] += $itemtypeCount;
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
      Туди не страшно йти
      Тому хто відчує
      І усвідомить це -
      Смерть перед очима,
      Та гірше за плечима -
      Там небуття сумирно жде 
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
                        $actLink .= wf_Link(self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $storageId . '&outitemid=' . $itemtypeid, wf_img_sized('skins/whoutcoming_icon.png', '', '10', '10') . ' ' . __('Outcoming')) . ' ';
                    }

                    if (cfr('WAREHOUSERESERVE')) {
                        $actLink .= wf_Link(self::URL_ME . '&' . self::URL_RESERVE . '&storageid=' . $storageId . '&itemtypeid=' . $itemtypeid, wf_img_sized('skins/whreservation.png', '', '10', '10') . ' ' . __('Reservation'));
                    }

                    $reservedCount = $this->reserveGet($storageId, $itemtypeid);

                    $data[] = @$this->allCategories[$this->allItemTypes[$itemtypeid]['categoryid']];
                    $data[] = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeid, @$this->allItemTypeNames[$itemtypeid]);
                    $itemtypeUnit = @$this->unitTypes[$this->allItemTypes[$itemtypeid]['unit']];
                    $data[] = ($count - $reservedCount) . ' ' . $itemtypeUnit;
                    $data[] = $reservedCount . ' ' . $itemtypeUnit;
                    $data[] = $count;
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
            $columns = array('Category', 'Warehouse item types', 'Count', 'Reserved', 'Total', 'Actions');
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
            $notesFlag = ubRouting::checkGet('withnotes') ? true : false;
            $urlParams = '';
            $opts = '"order": [[ 0, "desc" ]]';
            $columns = array('ID', 'Date', 'Destination', 'Warehouse storage', 'Category', 'Warehouse item types', 'Count', 'Price per unit', 'Sum', 'Actions');
            if ($notesFlag) {
                $columns = array('ID', 'Date', 'Destination', 'Warehouse storage', 'Category', 'Warehouse item types', 'Count', 'Price per unit', 'Sum', 'Notes', 'Actions');
                $urlParams = '&withnotes=true';
            }
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_OUT . '&' . self::URL_OUTAJLIST . $urlParams, false, 'Outcoming operations', 50, $opts);
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
                $result = ' : ' . wf_Link('?module=employee', @$this->allEmployee[$destparam]);
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
        $notesFlag = ubRouting::checkGet('withnotes') ? true : false;
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
                if ($notesFlag) {
                    $data[] = $each['notes'];
                }
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
        $result .= wf_HiddenInput('newoutdesttype', $destMark);
        switch ($destMark) {
            case 'task':
                $tasksTmp = array();
                $allJobTypes = ts_GetAllJobtypes();
                $allUndoneTasks = ts_GetUndoneTasksArray();
                $taskOutDateFlag = (@$this->altCfg['WAREHOUSE_TASKOUTDATE']) ? true : false;
                $taskOutEmpFlag = (@$this->altCfg['WAREHOUSE_TASKOUTEMPLOYEE']) ? true : false;
                $anyOneEmployeeId = (@$this->altCfg['TASKMAN_ANYONE_EMPLOYEEID']) ? $this->altCfg['TASKMAN_ANYONE_EMPLOYEEID'] : 0;
                $taskHideAnyoneFlag = ($anyOneEmployeeId) ? true : false;

                if (!empty($allUndoneTasks)) {
                    foreach ($allUndoneTasks as $io => $each) {
                        $taskJobType = (isset($allJobTypes[$each['jobtype']])) ? $allJobTypes[$each['jobtype']] : __('Something went wrong') . ': EX_NO_JOBTYPEID';
                        $jobLabel = $each['address'] . ' - ' . $taskJobType;
                        if ($taskOutDateFlag) {
                            $jobLabel .= ', ' . $each['startdate'];
                        }

                        if ($taskOutEmpFlag) {
                            $jobLabel .= ', ' . $this->allEmployee[$each['employee']];
                        }

                        if ($taskHideAnyoneFlag) {
                            if ($each['employee'] != $anyOneEmployeeId) {
                                $tasksTmp[$io] = $jobLabel;
                            }
                        } else {
                            $tasksTmp[$io] = $jobLabel;
                        }
                    }
                }
                $taskIdPreset = (ubRouting::checkGet('taskidpreset')) ? ubRouting::get('taskidpreset', 'int') : '';
                if (!empty($taskIdPreset)) {
                    if (!isset($tasksTmp[$taskIdPreset])) {
                        $taskPresetFailLabel = __('Fail') . ': ' . __('Task') . ' [' . $taskIdPreset . '] ' . __('Not found');
                        $result .= $this->messages->getStyledMessage($taskPresetFailLabel, 'warning') . wf_delimiter(0);
                    }
                }
                $result .= wf_Selector('newoutdestparam', $tasksTmp, __('Undone tasks'), $taskIdPreset, false);
                break;

            case 'contractor':
                $result .= wf_Selector('newoutdestparam', $this->allContractors, __('Contractor'), '', false);
                break;

            case 'employee':
                $result .= wf_Selector('newoutdestparam', $this->activeEmployee, __('Worker'), '', false);
                break;

            case 'storage':
                $result .= wf_Selector('newoutdestparam', $this->allStorages, __('Warehouse storage'), '', false);
                break;

            case 'user':
                $allUsers = zb_UserGetAllIPs();
                if (!empty($allUsers)) {
                    $allUsers = array_flip($allUsers);
                }
                $result .= wf_AutocompleteTextInput('newoutdestparam', $allUsers, __('Login'), '', false);
                break;
            case 'sale':
                $result .= wf_HiddenInput('newoutdestparam', 'true');
                break;
            case 'cancellation':
                $result .= wf_HiddenInput('newoutdestparam', 'true');
                break;
            case 'mistake':
                $result .= wf_HiddenInput('newoutdestparam', 'true');
                break;

            default:
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
        if ((isset($this->allStorages[$storageid])) and (isset($this->allItemTypes[$itemtypeid]))) {
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
            $maxItemCount = ($fromReserve) ? @$reserveData['count'] : ($itemRemainsStorage - $isReserved);
            //fix deleted reserve issue
            if (empty($maxItemCount)) {
                $maxItemCount = 0;
            }

            //form construct
            $inputs = wf_AjaxLoader();
            $inputs .= wf_HiddenInput('newoutdate', curdate());
            $inputs .= wf_AjaxSelectorAC('ajoutdestselcontainer', $tmpDests, __('Destination'), '', false);
            $inputs .= wf_AjaxContainer('ajoutdestselcontainer', '', $this->outcomindAjaxDestSelector('task'));
            $inputs .= wf_HiddenInput('newoutitemtypeid', $itemtypeid);
            $inputs .= wf_HiddenInput('newoutstorageid', $storageid);
            $inputs .= wf_TextInput('newoutcount', $itemUnit . ' (' . __('maximum') . ' ' . $maxItemCount . ')', '', true, '4', 'finance');
            $midPriceLabel = ($this->recPriceFlag) ? __('recommended') : __('middle price');
            $inputs .= wf_TextInput('newoutprice', __('Price') . ' (' . $midPriceLabel . ': ' . $this->getIncomeMiddlePrice($itemtypeid) . ')', '', true, '4', 'finance');
            if ($fromReserve) {
                $inputs .= wf_HiddenInput('newoutfromreserve', $reserveid);
                $notesPreset = ' ' . __('from reserved on') . ' ' . @$this->allEmployee[$reserveData['employeeid']];
            } else {
                $notesPreset = '';
            }
            $inputs .= wf_TextInput('newoutnotes', __('Notes'), $notesPreset, true, 45);
            $inputs .= wf_CheckInput('newoutnetw', __('Network'), true, false);

            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Create'));
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
                $notifications .= $this->messages->getStyledMessage(__('Reserved') . ' ' . $isReserved . ' ' . $itemUnit, 'info');
            }

            if ($remainsAlert) {
                $notifications .= $this->messages->getStyledMessage($remainsAlert, 'warning');
            }


            $notifications .= wf_CleanDiv();
            if (cfr('WAREHOUSERESERVE')) {
                $reserveLink = self::URL_ME . '&' . self::URL_RESERVE . '&itemtypeid=' . $itemtypeid . '&storageid=' . $storageid;
                $notifications .= wf_tag('div', false, '', 'style="margin: 20px 3% 0 3%;"') . wf_Link($reserveLink, wf_img('skins/whreservation.png') . ' ' . __('Reservation'), false, 'ubButton') . wf_tag('div', true);
                $notifications .= wf_CleanDiv();
            }


            $cells = wf_TableCell($form, '40%');
            $cells .= wf_TableCell($notifications, '', '', 'valign="top"');
            $rows = wf_TableRow($cells);
            $result = wf_TableBody($rows, '100%', 0, '');
            $result .= wf_FormDisabler();

            //photostorage integration
            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photostorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $itemtypeid);
                $result .= $photostorage->renderImagesRaw();
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
            $cells .= wf_TableCell($id);
            $rows = wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Date'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['date']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Destination'), '30%', 'row2');
            $cells .= wf_TableCell($this->outDests[$operationData['desttype']] . $this->outDestControl($operationData['desttype'], $operationData['destparam']));
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Category'), '30%', 'row2');
            $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$operationData['itemtypeid']]['categoryid']]);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse item type'), '30%', 'row2');
            $cells .= wf_TableCell($itemTypeLink);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Count'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['count'] . ' ' . $this->unitTypes[$this->allItemTypes[$operationData['itemtypeid']]['unit']]);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Price per unit'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['price']);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Sum'), '30%', 'row2');
            $cells .= wf_TableCell(($operationData['price'] * $operationData['count']));
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse storage'), '30%', 'row2');
            $cells .= wf_TableCell($this->allStorages[$operationData['storageid']]);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Network'), '30%', 'row2');
            $netLabel = ($operationData['netw']) ? wf_img_sized('skins/icon_active.gif', '', 12) . ' ' . __('Yes') : wf_img_sized('skins/icon_inactive.gif', '', 12) . ' ' . __('No');
            $cells .= wf_TableCell($netLabel);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Worker'), '30%', 'row2');
            $cells .= wf_TableCell($administratorName);
            $rows .= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Notes'), '30%', 'row2');
            $cells .= wf_TableCell($operationData['notes']);
            $rows .= wf_TableRow($cells, 'row3');

            $result .= wf_TableBody($rows, '100%', 0, 'wh_viewer');

            //returns controls here
            if (@$this->altCfg['WAREHOUSE_RETURNS_ENABLED']) {
                $this->loadReturns();
                $outReturnData = (isset($this->allReturns[$id])) ? $this->allReturns[$id] : array();
                if (empty($outReturnData)) {
                    //specific rights check
                    if (cfr('WAREHOUSERETURNS')) {
                        //return controller here
                        if (ubRouting::checkPost(array(self::PROUTE_RETURNOUTID, self::PROUTE_RETURNSTORAGE))) {
                            $this->createReturnOperation();
                            ubRouting::nav(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $id);
                        }
                        $returnDialogLabel = __('Return items to warehouse storage');
                        $result .= wf_modalAuto(wf_img('skins/return.png') . ' ' . $returnDialogLabel, $returnDialogLabel, $this->renderReturnForm($id), 'ubButton');
                    }
                } else {
                    $returnAdmName = (isset($employeeLogins[$outReturnData['admin']])) ? $employeeLogins[$outReturnData['admin']] : $outReturnData['admin'];
                    $returnedLabel = $outReturnData['date'] . ' ' . __('All items from this outcoming operation is already returned to warehouse storage') . ' ';
                    $returnedLabel .= $this->allStorages[$outReturnData['storageid']] . ', ';
                    $returnedLabel .= __('by administrator') . ' ' . $returnAdmName;

                    $result .= $this->messages->getStyledMessage($returnedLabel, 'warning');
                }
            }

            //outcome deletion controls here
            if (@$this->altCfg['WAREHOUSE_OUTDEL_ENABLED']) {
                if (cfr('ROOT')) {
                    $outDelUrl = self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $id . '&' . self::ROUTE_DELOUT . '=' . $id;
                    $outDelCancelUrl = self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $id;
                    $outDelLabel = $this->messages->getDeleteAlert();
                    $result .= wf_ConfirmDialog($outDelUrl, web_delete_icon() . ' ' . __('Delete'), $outDelLabel, 'ubButton', $outDelCancelUrl, __('Delete') . '?');
                }
            }

            $result .= wf_delimiter(0);

            //photostorage renderer
            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $operationData['itemtypeid']);
                $result .= $photoStorage->renderImagesRaw();
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Strange exeption') . ' NO_EXISTING_OUTCOME_ID', 'error');
        }

        //ADcomments support
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('WAREHOUSEOUTCOME');
            $result .= wf_tag('h3') . __('Additional comments') . wf_tag('h3', true);
            $result .= $adcomments->renderComments($id);
        }

        return ($result);
    }

    /**
     * Deletes existing outcoming operation
     * 
     * @param int $outId
     * 
     * @return void
     */
    public function outcomingDelete($outId) {
        $outId = ubRouting::filters($outId, 'int');
        if (isset($this->allOutcoming[$outId])) {
            if (cfr('ROOT')) {
                if (@$this->altCfg['WAREHOUSE_OUTDEL_ENABLED']) {
                    $outcomeData = $this->allOutcoming[$outId];
                    $itemtypeId = $outcomeData['itemtypeid'];
                    $count = $outcomeData['count'];
                    $price = $outcomeData['price'];

                    $outcomesDb = new NyanORM('wh_out');
                    $outcomesDb->where('id', '=', $outId);
                    $outcomesDb->delete();

                    log_register('WAREHOUSE OUTCOME DELETE [' . $outId . '] ITEM [' . $itemtypeId . '] COUNT `' . $count . '` PRICE `' . $price . '`');
                }
            }
        }
    }

    /**
     * Renders return operation form
     * 
     * @param int $outId
     * 
     * @return string
     */
    protected function renderReturnForm($outId) {
        $outId = ubRouting::filters($outId, 'int');
        $result = '';
        if (isset($this->allOutcoming[$outId])) {
            $outcomeData = $this->allOutcoming[$outId];

            $inputs = wf_HiddenInput(self::PROUTE_RETURNOUTID, $outId);
            $inputs .= wf_Selector(self::PROUTE_RETURNSTORAGE, $this->allStorages, __('Warehouse storage'), $outcomeData['storageid'], true);
            $inputs .= wf_TextInput(self::PROUTE_RETURNPRICE, __('Price'), $outcomeData['price'], true, 5, 'finance');
            $defaultNote = __('Return of an outcoming operation') . ' ID:' . $outId;
            $inputs .= wf_TextInput(self::PROUTE_RETURNNOTE, __('Notes'), $defaultNote, true, 30);
            $inputs .= wf_Submit(__('Return items to warehouse storage'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong') . ': ' . __('Outcoming operation') . ' [' . $outId . '] ' . __('Not exists'), 'error');
        }
        return ($result);
    }

    /**
     * Creates new outcome return operation
     * 
     * @return void
     */
    protected function createReturnOperation() {
        if (ubRouting::checkPost(array(self::PROUTE_RETURNOUTID, self::PROUTE_RETURNSTORAGE))) {
            $outId = ubRouting::post(self::PROUTE_RETURNOUTID, 'int');
            if (isset($this->allOutcoming[$outId])) {
                $outcomeData = $this->allOutcoming[$outId];
                $curDate = curdate();
                $curDateTime = curdatetime();
                $whoami = whoami();
                $itemtypeId = $outcomeData['itemtypeid'];
                $count = $outcomeData['count'];
                $storageId = ubRouting::post(self::PROUTE_RETURNSTORAGE, 'int');
                $price = ubRouting::post(self::PROUTE_RETURNPRICE);
                $notes = ubRouting::post(self::PROUTE_RETURNNOTE);
                $barcode = '';
                $contractorId = 0;

                //push database record about this return
                $this->returnsDb->data('outid', $outId);
                $this->returnsDb->data('storageid', $storageId);
                $this->returnsDb->data('itemtypeid', $itemtypeId);
                $this->returnsDb->data('count', $count);
                $this->returnsDb->data('price', $price);
                $this->returnsDb->data('date', $curDateTime);
                $this->returnsDb->data('admin', $whoami);
                $this->returnsDb->data('note', $notes);
                $this->returnsDb->create();

                //cast some incoming operation on this return
                $this->incomingCreate($curDate, $itemtypeId, $contractorId, $storageId, $count, $price, $barcode, $notes);
                log_register('WAREHOUSE RETURN CREATE [' . $outId . '] ITEM [' . $itemtypeId . '] COUNT `' . $count . '` PRICE `' . $price . '`');
            }
        }
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
     * @param bool $netw
     * 
     * @return string not emplty if something went wrong
     */
    public function outcomingCreate($date, $desttype, $destparam, $storageid, $itemtypeid, $count, $price = '', $notes = '', $reserveid = '', $netw = false) {
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
        if (is_numeric($priceF)) {
            $priceF = round($priceF, 2);
        } else {
            $priceF = 0;
        }
        $notes = mysql_real_escape_string($notes);
        $admin = mysql_real_escape_string(whoami());

        $fromReserve = (!empty($reserveid)) ? true : false;
        if ($fromReserve) {
            $reserveData = $this->reserveGetData($reserveid);
        }
        $netwF = ($netw) ? 1 : 0;

        if (isset($this->allStorages[$storageid])) {
            if (isset($this->allItemTypes[$itemtypeid])) {
                $allItemRemains = $this->remainsOnStorage($storageid);
                @$itemRemains = $allItemRemains[$itemtypeid];
                $itemsReserved = $this->reserveGet($storageid, $itemtypeid);
                if ($fromReserve) {
                    if (!empty($reserveData)) {
                        $realRemains = $reserveData['count'];
                    } else {
                        //reserve deleted?
                        $realRemains = 0;
                    }
                } else {
                    $realRemains = $itemRemains - $itemsReserved;
                }

                if ($countF <= $realRemains) {
                    //removing items from reserve
                    if ($fromReserve) {
                        $this->reserveDrain($reserveid, $count);
                    }
                    //creating new outcome
                    $query = "INSERT INTO `wh_out` (`id`,`date`,`desttype`,`destparam`,`storageid`,`itemtypeid`,`count`,`price`,`notes`,`netw`,`admin`) VALUES "
                        . "(NULL,'" . $date . "','" . $desttype . "','" . $destparam . "','" . $storageid . "','" . $itemtypeid . "','" . $countF . "','" . $priceF . "','" . $notes . "','" . $netwF . "','" . $admin . "')";
                    nr_query($query);
                    $newId = simple_get_lastid('wh_out');
                    log_register('WAREHOUSE OUTCOME CREATE [' . $newId . '] ITEM [' . $itemtypeid . '] COUNT `' . $count . '` PRICE `' . $price . '` NET `' . $netwF . '`');
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
            $columns = array('Category', 'Warehouse item types', 'At storage', 'Reserved', 'Total', 'Actions');
            $options = ' "dom": \'<"F"lfB>rti<"F"ps>\',  buttons: [\'csv\', \'excel\', \'pdf\', \'print\']';
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_REPORTS . '&' . self::URL_REAJTREM, true, 'Warehouse item types', 50, $options);
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
        $all = $this->remainsAllWithReserves();
        $json = new wf_JqDtHelper();

        if (!empty($all)) {
            foreach ($all as $itemtypeId => $remains) {
                $itemUnits = $this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']];
                $realRemains = $remains['count'] - $remains['reserved'];
                if (($remains['count'] > 0) or ($remains['reserved'] > 0) or $realRemains > 0) {
                    $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showremains=' . $itemtypeId, wf_img_sized('skins/icon_search_small.gif', '', '10', '10') . ' ' . __('Show'));
                    $data[] = $this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']];
                    $data[] = wf_link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeId, $this->allItemTypeNames[$itemtypeId]);
                    $data[] = $realRemains . ' ' . $itemUnits;
                    $data[] = $remains['reserved'] . ' ' . $itemUnits;
                    $data[] = $remains['count'];
                    $data[] = $actLink;
                    $json->addRow($data);
                    unset($data);
                }
            }
        }

        $json->getJson();
    }

    /**
     * Returns array of all itemtypes available on all storages with their reserved counts
     * 
     * @return array
     */
    public function remainsAllWithReserves() {
        $result = array();
        if (!empty($this->allStorages)) {
            foreach ($this->allStorages as $storageId => $storageName) {
                $tmpArr = $this->remainsOnStorage($storageId);
                if (!empty($tmpArr)) {
                    /**
                     * When you do wrong, no one forgives
                     * When you do good, no one will care
                     */
                    foreach ($tmpArr as $itemtypeId => $itemtypeCount) {
                        $reserved = $this->reserveGet($storageId, $itemtypeId);
                        if (isset($result[$itemtypeId])) {
                            $result[$itemtypeId]['count'] += $itemtypeCount;
                            $result[$itemtypeId]['reserved'] += $reserved;
                        } else {
                            $result[$itemtypeId]['count'] = $itemtypeCount;
                            $result[$itemtypeId]['reserved'] = $reserved;
                        }
                    }
                }
            }
        }
        return ($result);
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
            $cells .= wf_TableCell(__('Warehouse storage'));
            $cells .= wf_TableCell(__('Count'));
            $cells .= wf_TableCell(__('Actions'));
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
                                        $actLinks .= wf_Link(self::URL_ME . '&' . self::URL_OUT . '&storageid=' . $storageId . '&outitemid=' . $itemtypeId, wf_img_sized('skins/whoutcoming_icon.png', '', '10', '10') . ' ' . __('Outcoming')) . ' ';
                                    }

                                    if (cfr('WAREHOUSERESERVE')) {
                                        $actLinks .= wf_Link(self::URL_ME . '&' . self::URL_RESERVE . '&storageid=' . $storageId . '&itemtypeid=' . $itemtypeId, wf_img_sized('skins/whreservation.png', '', '10', '10') . ' ' . __('Reservation'));
                                    }

                                    $cells = wf_TableCell($itemtypeName);
                                    $cells .= wf_TableCell($StorageName);
                                    $cells .= wf_TableCell($count . ' ' . $itemtypeUnit);
                                    $cells .= wf_TableCell($actLinks);
                                    $rows .= wf_TableRow($cells, 'row3');
                                }
                            }
                        }
                    }
                }
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');

            if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $itemtypeId);
                $result .= $photoStorage->renderImagesRaw();
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
        if ((!empty($this->allItemTypes)) and (!empty($this->allStorages)) and (!empty($this->allIncoming))) {
            $allRemains = $this->remainsAll();

            foreach ($this->allItemTypes as $itemtypeId => $itemData) {
                $itemReserve = $itemData['reserve'];
                $itemName = $this->allItemTypeNames[$itemtypeId];
                $itemUnit = $this->unitTypes[$itemData['unit']];
                if ($itemReserve > 0) {
                    if ((!isset($allRemains[$itemtypeId])) or ($allRemains[$itemtypeId] < $itemReserve)) {
                        $result .= $this->messages->getStyledMessage(__('In warehouses remains less than') . ' ' . $itemReserve . ' ' . $itemUnit . ' ' . $itemName, 'warning');
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Returns low reserve alert
     * 
     * @return string
     */
    protected function reserveShoppingAlert() {
        $result = '';
        $photoStorageEnabled = ($this->altCfg['PHOTOSTORAGE_ENABLED']) ? true : false;
        if ($photoStorageEnabled) {
            $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, 'nope');
        }
        if ((!empty($this->allItemTypes)) and (!empty($this->allStorages)) and (!empty($this->allIncoming))) {
            $allRemains = $this->remainsAll();

            foreach ($this->allItemTypes as $itemtypeId => $itemData) {
                $itemReserve = $itemData['reserve'];
                $itemName = $this->allItemTypeNames[$itemtypeId];
                $itemUnit = $this->unitTypes[$itemData['unit']];
                if ($itemReserve > 0) {
                    if ((!isset($allRemains[$itemtypeId])) or ($allRemains[$itemtypeId] < $itemReserve)) {
                        $itemImage = 'skins/shopping.png';
                        if ($photoStorageEnabled) {
                            $itemImagesList = $photoStorage->getImagesList($itemtypeId);
                            if (!empty($itemImagesList)) {
                                $itemImage = $itemImagesList[0]; //just 1st image for item
                            }
                        }

                        $itemLabel = __('In warehouses remains less than') . ' ' . $itemReserve . ' ' . $itemUnit . ' ' . $itemName;
                        $itemImagePreview = wf_img_sized($itemImage, $itemLabel, '200', '200');
                        $result .= wf_tag('div', false, 'dashtask', 'style="height:230px; width:230px;"');
                        $result .= $itemImagePreview;
                        $result .= wf_delimiter(0);
                        $result .= $itemName . ' < ' . ' ' . $itemReserve . ' ' . $itemUnit;
                        $result .= wf_tag('div', true);
                    }
                }
            }
            $result .= wf_CleanDiv();
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
        if ($_SERVER['QUERY_STRING'] == 'module=warehouse&warehousestats=true') {

            $curMonth = curmonth();
            $result .= $this->reserveAlert();

            if (empty($this->allCategories)) {
                $result .= $this->messages->getStyledMessage(__('No existing categories'), 'warning');
            } else {
                $result .= $this->messages->getStyledMessage(__('Available categories') . ': ' . sizeof($this->allCategories), 'info');
            }

            if (empty($this->allItemTypes)) {
                $result .= $this->messages->getStyledMessage(__('No existing warehouse item types'), 'warning');
            } else {
                $result .= $this->messages->getStyledMessage(__('Available item types') . ': ' . sizeof($this->allItemTypes), 'info');
            }

            if (empty($this->allStorages)) {
                $result .= $this->messages->getStyledMessage(__('No existing warehouse storages'), 'warning');
            } else {
                $result .= $this->messages->getStyledMessage(__('Available warehouse storages') . ': ' . sizeof($this->allStorages), 'info');
            }

            if (empty($this->allContractors)) {
                $result .= $this->messages->getStyledMessage(__('No existing contractors'), 'warning');
            } else {
                $result .= $this->messages->getStyledMessage(__('Available contractors') . ': ' . sizeof($this->allContractors), 'info');
            }

            if (empty($this->allIncoming)) {
                $result .= $this->messages->getStyledMessage(__('No incoming operations yet'), 'warning');
            } else {
                $result .= $this->messages->getStyledMessage(__('Total incoming operations') . ': ' . sizeof($this->allIncoming), 'success');

                $monthInCount = 0;
                $monthInSumm = 0;
                foreach ($this->allIncoming as $io => $each) {
                    if (ispos($each['date'], $curMonth)) {
                        $monthInCount++;
                        $monthInSumm += $each['price'] * $each['count'];
                    }
                }
                $monthTotalsLabel = __('Current month') . ': ' . $monthInCount . ' ' . __('Incoming operations') . ' ' . __('on') . ' ' . zb_CashBigValueFormat($monthInSumm) . ' ' . __('money');
                $result .= $this->messages->getStyledMessage($monthTotalsLabel, 'success');
            }

            if (empty($this->allOutcoming)) {
                $result .= $this->messages->getStyledMessage(__('No outcoming operations yet'), 'warning');
            } else {
                $result .= $this->messages->getStyledMessage(__('Total outcoming operations') . ': ' . sizeof($this->allOutcoming), 'success');
            }


            if (!empty($result)) {
                $winControl = wf_Link(self::URL_ME, wf_img('skins/shopping_cart_small.png', __('Necessary purchases')));
                show_window(__('Stats') . ' ' . $winControl, $result);
                zb_BillingStats(true);
            }
        } else {
            if ($_SERVER['QUERY_STRING'] == 'module=warehouse') {
                //shopping grid
                $result .= $this->reserveShoppingAlert();
                if (empty($result)) {
                    $result .= $this->messages->getStyledMessage(__('It looks like your warehouse is fine'), 'success');
                }
                $winControl = wf_Link(self::URL_ME . '&warehousestats=true', web_icon_charts());
                show_window(__('Necessary purchases') . ' ' . $winControl, $result);
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

        switch ($type) {
            case 'in':
                if (isset($this->allIncoming[$id])) {
                    $itemName = $this->allItemTypeNames[$this->allIncoming[$id]['itemtypeid']];
                    $qrText = $itemName . ' ' . __('Incoming operation') . '# ' . $id;
                } else {
                    $qrText = ('Wrong ID');
                }
                break;

            case 'out':
                if (isset($this->allOutcoming[$id])) {
                    $itemName = $this->allItemTypeNames[$this->allOutcoming[$id]['itemtypeid']];
                    $qrText = $itemName . ' ' . __('Outcoming operation') . '# ' . $id;
                } else {
                    $qrText = 'Wrong ID';
                }
                break;

            case 'itemtype':
                if (isset($this->allItemTypeNames[$id])) {
                    $qrText = ($this->allItemTypeNames[$id]);
                } else {
                    $qrText = 'Wrong ID';
                }
                break;

            default:
                $qrText = 'Wrong type';
                break;
        }
        $qr = new QRCode($qrText);
        $qr->output_image();
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
                $itemName = str_replace("'", '`', $itemName);

                $itemCount = @$each['count'];
                $itemUnit = @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                $calendarData .= "
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
                $calendarData .= "
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
        $outcomesCount = 0;
        $notesFlag = (@$this->altCfg['WAREHOUSE_TASKMANNOTES']) ? true : false;
        $returnsFlag = (@$this->altCfg['WAREHOUSE_RETURNS_ENABLED']) ? true : false;
        if ($returnsFlag) {
            $this->loadReturns();
        }
        if (!empty($this->allOutcoming)) {
            $tmpArr = $this->allOutcoming;
            if (!empty($tmpArr)) {
                $cells = wf_TableCell(__('Date'));
                $cells .= wf_TableCell(__('Warehouse storage'));
                $cells .= wf_TableCell(__('Category'));
                $cells .= wf_TableCell(__('Warehouse item type'));
                $cells .= wf_TableCell(__('Count'));
                $cells .= wf_TableCell(__('Price'));
                $cells .= wf_TableCell(__('Sum'));
                if ($notesFlag) {
                    $cells .= wf_TableCell(__('Notes'));
                }
                if (cfr('WAREHOUSEOUT')) {
                    $cells .= wf_TableCell(__('Actions'));
                }
                $rows = wf_TableRow($cells, 'row1');
                foreach ($tmpArr as $io => $each) {
                    $operationReturned = false;

                    if ($returnsFlag) {
                        if (isset($this->allReturns[$each['id']])) {
                            $operationReturned = true;
                        }
                    }


                    @$itemUnit = $this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                    $rowClass = 'row5';
                    if ($operationReturned) {
                        $rowClass = 'ukvbankstadup';
                        $itemUnit .= ' ' . wf_img_sized('skins/return.png', __('All items from this outcoming operation is already returned to warehouse storage'), '12');
                    }
                    $cells = wf_TableCell($each['date']);
                    $cells .= wf_TableCell(@$this->allStorages[$each['storageid']]);
                    $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                    $cells .= wf_TableCell(@$this->allItemTypeNames[$each['itemtypeid']]);
                    $cells .= wf_TableCell($each['count'] . ' ' . $itemUnit);
                    $cells .= wf_TableCell($each['price']);
                    $cells .= wf_TableCell($each['price'] * $each['count']);
                    if (cfr('WAREHOUSEOUT')) {
                        $actLinks = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['id'], wf_img_sized('skins/whoutcoming_icon.png', '', '12') . ' ' . __('Show'));
                    } else {
                        $actLinks = '';
                    }

                    if ($notesFlag) {
                        $cells .= wf_TableCell($each['notes']);
                    }

                    if (cfr('WAREHOUSEOUT')) {
                        $cells .= wf_TableCell($actLinks);
                    }


                    $rows .= wf_TableRow($cells, $rowClass);
                    $sum = $sum + ($each['price'] * $each['count']);
                    $outcomesCount++;
                }
                $cells = wf_TableCell(__('Total') . ': ' . $outcomesCount);
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell($sum);
                if ($notesFlag) {
                    $cells .= wf_TableCell('');
                }
                if (cfr('WAREHOUSEOUT')) {
                    $cells .= wf_TableCell('');
                }
                $rows .= wf_TableRow($cells, 'row2');

                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
        }
        return ($result);
    }

    /**
     * Renders materials list spent on some tasks from array
     * 
     * @param array $tasksArr
     * @param string $userLogin
     * 
     * @return string
     */
    public function userSpentMaterialsReport($tasksArr = array(), $userLogin = '') {

        $result = '';
        $tmpArr = array();
        $sum = 0;
        $outcomesCount = 0;
        $notesFlag = (@$this->altCfg['WAREHOUSE_TASKMANNOTES']) ? true : false;
        $onlyTaskFilterFlag = (ubRouting::checkGet('onlytasks')) ? true : false;
        $onlyUserFilterFlag = (ubRouting::checkGet('onlyuser')) ? true : false;
        $returnsFlag = (@$this->altCfg['WAREHOUSE_RETURNS_ENABLED']) ? true : false;
        if ($returnsFlag) {
            $this->loadReturns();
        }
        if (!empty($this->allOutcoming)) {
            //prefiltering outcome operations
            foreach ($this->allOutcoming as $io => $each) {
                if (!$onlyUserFilterFlag) {
                    //filter by taskId
                    if ($each['desttype'] == 'task' and isset($tasksArr[$each['destparam']])) {
                        $tmpArr[] = $each;
                    }
                }

                if (!$onlyTaskFilterFlag) {
                    //filter by direct user outcome operation
                    if ($userLogin) {
                        if ($each['desttype'] == 'user' and $each['destparam'] == $userLogin) {
                            $tmpArr[] = $each;
                        }
                    }
                }
            }
            //rendering result
            if (!empty($tmpArr)) {
                $cells = wf_TableCell(__('Date'));
                $cells .= wf_TableCell(__('Warehouse storage'));
                $cells .= wf_TableCell(__('Category'));
                $cells .= wf_TableCell(__('Warehouse item type'));
                $cells .= wf_TableCell(__('Count'));
                $cells .= wf_TableCell(__('Price'));
                $cells .= wf_TableCell(__('Sum'));
                if ($notesFlag) {
                    $cells .= wf_TableCell(__('Notes'));
                }
                if (cfr('WAREHOUSEOUT')) {
                    $cells .= wf_TableCell(__('Actions'));
                }
                $rows = wf_TableRow($cells, 'row1');
                foreach ($tmpArr as $io => $each) {
                    $operationReturned = false;
                    if ($returnsFlag) {
                        if (isset($this->allReturns[$each['id']])) {
                            $operationReturned = true;
                        }
                    }

                    @$itemUnit = $this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']];
                    $rowClass = 'row5';
                    if ($operationReturned) {
                        $rowClass = 'ukvbankstadup';
                        $itemUnit .= ' ' . wf_img_sized('skins/return.png', __('All items from this outcoming operation is already returned to warehouse storage'), '12');
                    }
                    $cells = wf_TableCell($each['date']);
                    $cells .= wf_TableCell(@$this->allStorages[$each['storageid']]);
                    $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                    $cells .= wf_TableCell(@$this->allItemTypeNames[$each['itemtypeid']]);
                    $cells .= wf_TableCell($each['count'] . ' ' . $itemUnit);
                    $cells .= wf_TableCell($each['price']);
                    $cells .= wf_TableCell($each['price'] * $each['count']);
                    if (cfr('WAREHOUSEOUT')) {
                        $actUrl = self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['id'];
                        $actLinks = wf_Link($actUrl, wf_img_sized('skins/whoutcoming_icon.png', '', '12') . ' ' . __('Show'));
                    } else {
                        $actLinks = '';
                    }

                    if ($notesFlag) {
                        $cells .= wf_TableCell($each['notes']);
                    }

                    if (cfr('WAREHOUSEOUT')) {
                        $cells .= wf_TableCell($actLinks);
                    }


                    $rows .= wf_TableRow($cells, $rowClass);
                    $sum = $sum + ($each['price'] * $each['count']);
                    $outcomesCount++;
                }
                $cells = wf_TableCell(__('Total') . ': ' . $outcomesCount);
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell('');
                $cells .= wf_TableCell($sum);
                if ($notesFlag) {
                    $cells .= wf_TableCell('');
                }
                if (cfr('WAREHOUSEOUT')) {
                    $cells .= wf_TableCell('');
                }
                $rows .= wf_TableRow($cells, 'row2');
                $result = wf_TableBody($rows, '100%', 0, '');
            } else {
                $result = $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }

            //append some controls here
            $result .= wf_delimiter(0);
            $filterLabelAll = wf_img('skins/icon_ok.gif') . ' ' . __('All together');
            $filterUrlAll = '?module=warehouselookup&username=' . $userLogin;
            $result .= wf_Link($filterUrlAll, $filterLabelAll, false, 'ubButton') . ' ';
            $filterLabelTasks = wf_img('skins/icon_calendar.gif') . ' ' . __('Only tasks');
            $filterUrlTasks = '?module=warehouselookup&username=' . $userLogin . '&onlytasks=true';
            $result .= wf_Link($filterUrlTasks, $filterLabelTasks, false, 'ubButton') . ' ';
            $filterLabelUser = wf_img('skins/icons/userprofile.png') . ' ' . __('Only user');
            $filterUrlUser = '?module=warehouselookup&username=' . $userLogin . '&onlyuser=true';
            $result .= wf_Link($filterUrlUser, $filterLabelUser, false, 'ubButton') . ' ';
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
                    if (($each['desttype'] == 'task') and ($each['destparam'] == $taskid)) {
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
        $header .= wf_tag('html', false, '', 'xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru"');
        $header .= wf_tag('head', false);
        $header .= wf_tag('title') . $title . wf_tag('title', true);
        $header .= wf_tag('meta', false, '', 'http-equiv="Content-Type" content="text/html; charset=UTF-8" /');
        $header .= wf_tag('style', false, '', 'type="text/css"');
        $header .= $style;
        $header .= wf_tag('style', true);
        $header .= wf_tag('script', false, '', 'src="modules/jsc/sorttable.js" language="javascript"') . wf_tag('script', true);
        $header .= wf_tag('head', true);
        $header .= wf_tag('body', false);

        $footer = wf_tag('body', true);
        $footer .= wf_tag('html', true);

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
            $cells .= wf_TableCell(__('Warehouse item types'));
            $cells .= wf_TableCell(__('Count') . ' ' . __('On') . ' ' . $storageName);
            $cells .= wf_TableCell(__('Reserved'));
            $cells .= wf_TableCell(__('Total'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($allRemains)) {
                foreach ($allRemains as $itemtypeId => $count) {
                    //hide itemtypes with zero ramains
                    if ($count > 0) {
                        $reservedCount = $this->reserveGet($storageId, $itemtypeId);
                        $cells = wf_TableCell(@$this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']]);
                        $cells .= wf_TableCell(@$this->allItemTypeNames[$itemtypeId]);
                        $itemUnit = @$this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']];
                        $cells .= wf_TableCell(($count - $reservedCount) . ' ' . $itemUnit);
                        $cells .= wf_TableCell($reservedCount . ' ' . $itemUnit);
                        $cells .= wf_TableCell($count . ' ' . $itemUnit);
                        $rows .= wf_TableRow($cells, 'row3');
                    }
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
        $result .= wf_tag('table', false, '', 'border="0" cellspacing="2" width="100%" class="printable"');
        $result .= wf_tag('colgroup', false, '', 'span="4" width="80"');
        $result .= wf_tag('colgroup', true);
        $result .= wf_tag('colgroup', false, '', 'width="79"');
        $result .= wf_tag('colgroup', true);
        $result .= wf_tag('colgroup', false, '', 'span="6" width="80"');
        $result .= wf_tag('colgroup', true);
        $result .= wf_tag('tbody', false);
        $result .= wf_tag('tr', false, 'row2');
        $result .= wf_tag('td', false, '', 'colspan="3" rowspan="3" align="center" valign="bottom"');
        $result .= __('Warehouse item types');
        $result .= wf_tag('td', true);
        $result .= wf_tag('td', false, '', 'colspan="2" rowspan="2" align="center" valign="bottom"');
        $result .= __('Remains at the beginning of the month');
        $result .= wf_tag('td', true);
        $result .= wf_tag('td', false, '', 'colspan="4" align="center" valign="bottom"') . $monthName . ' ' . $year . wf_tag('td', true);
        $result .= wf_tag('td', false, '', 'colspan="2" rowspan="2" align="center" valign="bottom"');
        $result .= __('Remains at end of the month');
        $result .= wf_tag('td', true);
        $result .= wf_tag('tr', true);
        $result .= wf_tag('tr', false, 'row2');
        $result .= wf_tag('td', false, '', 'colspan="2" align="center" valign="bottom"') . __('Incoming') . wf_tag('td', true);
        $result .= wf_tag('td', false, '', 'colspan="2" align="center" valign="bottom"') . __('Outcoming') . ' (' . __('Signups') . '/' . __('Other') . ')' . wf_tag('td', true);
        $result .= wf_tag('tr', true);
        $result .= wf_tag('tr', false, 'row2');
        $result .= wf_TableCell(__('Count'));
        $result .= wf_TableCell(__('Sum'));
        $result .= wf_TableCell(__('Count'));
        $result .= wf_TableCell(__('Sum'));
        $result .= wf_TableCell(__('Count'));
        $result .= wf_TableCell(__('Sum'));
        $result .= wf_TableCell(__('Count'));
        $result .= wf_TableCell(__('Sum'));
        $result .= wf_tag('tr', true);
        $result .= wf_tag('tr', false);
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
        $cells .= wf_TableCell($data[0]);
        $cells .= wf_TableCell($data[1]);
        $cells .= wf_TableCell($data[2]);
        $cells .= wf_TableCell($data[3]);
        $cells .= wf_TableCell($data[4]);
        $cells .= wf_TableCell($data[5]);
        $cells .= wf_TableCell($data[6]);
        $cells .= wf_TableCell($data[7]);
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
    public function getIncomeMiddlePrice($itemtypeId) {
        $cacheTimeout = $this->pricesCachingTimeout;
        if (empty($this->cachedPrices)) {
            $this->cachedPrices = $this->cache->get('WH_ITMPRICES', $cacheTimeout);
            if (empty($this->cachedPrices)) {
                $this->cachedPrices = array();
            }
        }

        if (isset($this->cachedPrices[$itemtypeId])) {
            //just return from cached data
            $result = $this->cachedPrices[$itemtypeId];
        } else {
            //cache update is required
            $itemsCount = 0;
            $totalSumm = 0;
            $latestIncomePrice = 0;
            $latestOutcomePrice = 0;
            if (!empty($this->allIncoming)) {
                foreach ($this->allIncoming as $io => $each) {
                    if ($each['itemtypeid'] == $itemtypeId) {
                        if ($each['price'] != 0) {
                            if ($each['contractorid'] != 0) { //ignoring move ops
                                $totalSumm += ($each['price'] * $each['count']);
                                $itemsCount += $each['count'];
                                $latestIncomePrice = $each['price'];
                            }
                        }
                    }
                }
            }

            //if recommended price calculation mode is enabled, we need to subtract outcome prices from total sum
            if ($this->recPriceFlag==1 or $this->recPriceFlag==3 or $this->recPriceFlag==4) {
                if (!empty($this->allOutcoming)) {
                    foreach ($this->allOutcoming as $io => $each) {
                        if ($each['itemtypeid'] == $itemtypeId) {
                            if ($each['price'] != 0) {
                                $totalSumm -= (abs($each['price']) * $each['count']);
                                $itemsCount -= $each['count'];
                                $latestOutcomePrice = $each['price'];
                            }
                        }
                    }
                }
            }

            if ($itemsCount != 0) {
                $result = round($totalSumm / $itemsCount, 2);
            } else {
                $result = round($totalSumm, 2);
            }


            // if recommended price calculation mode is set to latest income price only, 
            // we need to return latest income price instead of calculated middle price
            if ($this->recPriceFlag==2) {
                $result = $latestIncomePrice;
            }

            // if recommended price calculation mode is set to latest outcome price only, 
            // we need to return latest outcome price instead of calculated middle price
            if ($this->recPriceFlag==3) {
                $result = $latestOutcomePrice;
            }

            // if recommended price set to latest outcome price, if empty - latest income price
            if ($this->recPriceFlag==4) {
                $result = ($latestOutcomePrice != 0) ? $latestOutcomePrice : $latestIncomePrice;
            }

            $this->cachedPrices[$itemtypeId] = $result;
            //cache update
            $this->cache->set('WH_ITMPRICES', $this->cachedPrices, $cacheTimeout);
        }

        return ($result);
    }

    /**
     * Returns list of all signup typed tasks as id=>id
     * 
     * @param int $year
     * 
     * @return array
     */
    protected function getAllSignupTasks($year = '') {
        $result = array();
        $signupJobTypes = array();
        $signupJobTypesTmp = $this->altCfg['TASKREPORT_SIGNUPJOBTYPES'];
        if (!empty($signupJobTypesTmp)) {
            $signupJobTypesTmp = explode(',', $signupJobTypesTmp);
            if (!empty($signupJobTypesTmp)) {
                foreach ($signupJobTypesTmp as $io => $each) {
                    $signupJobTypes[$each] = $each;
                }
            }
        }

        $allTasks = ts_GetAllTasks($year);
        if (!empty($allTasks)) {
            foreach ($allTasks as $io => $each) {
                if (isset($signupJobTypes[$each['jobtype']])) {
                    $result[$each['id']] = $each['id'];
                }
            }
        }
        return ($result);
    }

    /**
     * Renders report with list of controls to view some storages remains
     * 
     * @return string
     */
    public function reportStoragesRemains() {
        $result = '';
        $result .= $this->outcomingStoragesList(true);
        return ($result);
    }

    /**
     * Renders date remains report
     * 
     * @return string
     */
    public function reportDateRemains() {
        $result = '';

        $curyear = (ubRouting::checkPost('yearsel')) ? ubRouting::post('yearsel', 'int') : date("Y");
        $curmonth = (ubRouting::checkPost('monthsel')) ? ubRouting::post('monthsel', 'int') : date("m");
        $hideNoMoveFlag = (ubRouting::checkPost('ignorenotmoving')) ? true : false;
        $storageIdFilter = (ubRouting::checkPost('storageidfilter')) ? ubRouting::post('storageidfilter') : 0;
        $taskReportFlag = (@$this->altCfg['TASKREPORT_ENABLED']) ? true : false;
        $allSignupTasks = array();
        if ($taskReportFlag) {
            $allSignupTasks = $this->getAllSignupTasks($curyear);
        }

        //report form inputs
        $inputs = wf_YearSelector('yearsel', __('Year')) . ' ';
        $inputs .= wf_MonthSelector('monthsel', __('Month'), $curmonth) . ' ';
        $storageFilters = array('0' => __('Any'));
        $storageFilters += $this->allStorages;
        $inputs .= wf_Selector('storageidfilter', $storageFilters, __('Warehouse storage'), $storageIdFilter, false);
        $inputs .= wf_CheckInput('ignorenotmoving', __('Hide without movement'), false, $hideNoMoveFlag) . ' ';
        $inputs .= wf_CheckInput('printmode', __('Print'), false, false) . ' ';

        $inputs .= wf_Submit(__('Show'));
        $searchForm = wf_Form('', 'POST', $inputs, 'glamour');
        $searchForm .= wf_CleanDiv();

        //append form to result
        if (!ubRouting::checkPost('printmode')) {
            $result .= $searchForm;
        }

        //in-out properties copies for further report generation
        $allIncoming = $this->allIncoming;
        $allOutcoming = $this->allOutcoming;

        //optional storage-id filter here
        if ($storageIdFilter) {
            foreach ($allIncoming as $io => $each) {
                if ($each['storageid'] != $storageIdFilter) {
                    unset($allIncoming[$io]);
                }
            }

            foreach ($allOutcoming as $io => $each) {
                if ($each['storageid'] != $storageIdFilter) {
                    unset($allOutcoming[$io]);
                }
            }
        }


        $lowerOffset = strtotime($curyear . '-' . $curmonth . '-01');
        $upperOffset = strtotime($curyear . '-' . $curmonth . '-01');
        $upperOffset = date("t", $upperOffset);
        $upperOffset = strtotime($curyear . '-' . $curmonth . '-' . $upperOffset);
        $incomingLower = array();
        $outcomingLower = array();

        if (!empty($allIncoming)) {
            foreach ($allIncoming as $io => $each) {
                $incomingDate = strtotime($each['date']);
                if ($incomingDate <= $lowerOffset) {
                    if ($each['contractorid'] != 0) { //ignoring move ops
                        $incomingLower[$each['id']] = $each;
                    }
                }
            }
        }


        if (!empty($allOutcoming)) {
            foreach ($allOutcoming as $io => $each) {
                $outcomingDate = strtotime($each['date']);
                if ($outcomingDate <= $lowerOffset) {
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
        if (!empty($allIncoming)) {
            foreach ($allIncoming as $io => $each) {
                $incomeDate = strtotime($each['date']);
                if (($incomeDate >= $lowerOffset) and ($incomeDate) <= $upperOffset) {
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
        if (!empty($allOutcoming)) {
            foreach ($allOutcoming as $io => $each) {
                $outcomeDate = strtotime($each['date']);
                if (($outcomeDate >= $lowerOffset) and ($outcomeDate) <= $upperOffset) {
                    if ($each['desttype'] != 'storage') { //ignoring move ops
                        if ($each['price'] == 0) {
                            $each['price'] = $this->getIncomeMiddlePrice($each['itemtypeid']);
                        }
                        if (isset($upperOutcome[$each['itemtypeid']])) {
                            $upperOutcome[$each['itemtypeid']]['count'] = $upperOutcome[$each['itemtypeid']]['count'] + $each['count'];
                            $upperOutcome[$each['itemtypeid']]['price'] = $upperOutcome[$each['itemtypeid']]['price'] + ($each['count'] * $each['price']);
                            if ($each['desttype'] == 'task' and isset($allSignupTasks[$each['destparam']])) {
                                $upperOutcome[$each['itemtypeid']]['sigcount'] = $upperOutcome[$each['itemtypeid']]['sigcount'] + $each['count'];
                                $upperOutcome[$each['itemtypeid']]['sigprice'] = $upperOutcome[$each['itemtypeid']]['sigprice'] + ($each['count'] * $each['price']);
                            }
                        } else {
                            $upperOutcome[$each['itemtypeid']]['count'] = $each['count'];
                            $upperOutcome[$each['itemtypeid']]['price'] = $each['count'] * $each['price'];
                            if ($each['desttype'] == 'task' and isset($allSignupTasks[$each['destparam']])) {
                                $upperOutcome[$each['itemtypeid']]['sigcount'] = $each['count'];
                                $upperOutcome[$each['itemtypeid']]['sigprice'] = $each['count'] * $each['price'];
                            } else {
                                $upperOutcome[$each['itemtypeid']]['sigcount'] = 0;
                                $upperOutcome[$each['itemtypeid']]['sigprice'] = 0;
                            }
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



        $result .= $this->reportDateRemainsHeader($curyear, $curmonth);

        if (!empty($lowerRemains)) {
            $firstColumnTotal = 0;
            $secondColumnTotal = 0;
            $thirdColumnTotal = 0;
            $fourthColumnTotal = 0;

            foreach ($lowerRemains as $io => $each) {
                $appendResultsFlag = true;
                if ($hideNoMoveFlag) {
                    $appendResultsFlag = false;
                }
                $itemtypeId = $io;
                $firstColumnCount = (isset($lowerRemains[$itemtypeId])) ? $lowerRemains[$itemtypeId]['count'] : 0;
                $firstColumnPrice = (isset($lowerRemains[$itemtypeId])) ? $lowerRemains[$itemtypeId]['price'] : 0;
                $secondColumnCount = (isset($upperIncome[$itemtypeId])) ? $upperIncome[$itemtypeId]['count'] : 0;
                $secondColumnPrice = (isset($upperIncome[$itemtypeId])) ? $upperIncome[$itemtypeId]['price'] : 0;
                $thirdColumnCount = (isset($upperOutcome[$itemtypeId])) ? $upperOutcome[$itemtypeId]['count'] : 0;
                $thirdColumnPrice = (isset($upperOutcome[$itemtypeId])) ? $upperOutcome[$itemtypeId]['price'] : 0;

                if (isset($upperOutcome[$itemtypeId]['sigcount']) and isset($upperOutcome[$itemtypeId]['sigprice'])) {
                    $thirdColumnCountSig = (isset($upperOutcome[$itemtypeId])) ? $upperOutcome[$itemtypeId]['sigcount'] : 0;
                    $thirdColumnPriceSig = (isset($upperOutcome[$itemtypeId])) ? $upperOutcome[$itemtypeId]['sigprice'] : 0;
                } else {
                    $thirdColumnCountSig = 0;
                    $thirdColumnPriceSig = 0;
                }

                $fourthColumnCount = $lowerRemains[$itemtypeId]['count'] + $secondColumnCount - $thirdColumnCount;
                $fourthColumnPrice = $lowerRemains[$itemtypeId]['price'] + $secondColumnPrice - $thirdColumnPrice;

                //some movements is there?
                if ($hideNoMoveFlag) {
                    if ($secondColumnCount or $thirdColumnCount) {
                        $appendResultsFlag = true;
                    }
                }

                //appending row to results
                if ($appendResultsFlag) {
                    $result .= $this->reportDateRemainsAddRow($itemtypeId, array(
                        $firstColumnCount,
                        round($firstColumnPrice, 2),
                        $secondColumnCount,
                        round($secondColumnPrice, 2),
                        $thirdColumnCount . ' (' . $thirdColumnCountSig . '/' . ($thirdColumnCount - $thirdColumnCountSig) . ')',
                        round($thirdColumnPrice, 2) . ' (' . $thirdColumnPriceSig . '/' . ($thirdColumnPrice - $thirdColumnPriceSig) . ')',
                        $fourthColumnCount,
                        round($fourthColumnPrice, 2)
                    ));

                    $firstColumnTotal += $firstColumnPrice;
                    $secondColumnTotal += $secondColumnPrice;
                    $thirdColumnTotal += $thirdColumnPrice;
                    $fourthColumnTotal += $fourthColumnPrice;
                }
            }

            //table summary append
            $result .= $this->reportDateRemainsAddRow('', array('', $firstColumnTotal, '', $secondColumnTotal, '', $thirdColumnTotal, '', $fourthColumnTotal));
        }


        $result .= wf_tag('tbody', true);
        $result .= wf_tag('table', true);

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
            $this->loadReserveHistory();
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
                $cells .= wf_TableCell(__('Type'));
                $cells .= wf_TableCell(__('Warehouse storage'));
                $cells .= wf_TableCell(__('Count'));
                $cells .= wf_TableCell(__('Price'));
                $cells .= wf_TableCell(__('Actions'));
                $cells .= wf_TableCell(__('Admin'));
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
                                            $opTypeName .= ' (' . __('Created') . ')';
                                        }
                                        if ($eachOp['type'] == 'update') {
                                            $opTypeName .= ' (' . __('Updated') . ')';
                                        }
                                        if ($eachOp['type'] == 'delete') {
                                            $opTypeName .= ' (' . __('Deleted') . ')';
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
                                    $cells .= wf_TableCell(wf_tag('font', false, '', 'color="' . $rowColor . '"') . $opTypeName . wf_tag('font', true) . ' ' . $opLink);
                                    $cells .= wf_TableCell(@$this->allStorages[$eachOp['storageid']]);
                                    $cells .= wf_TableCell($eachOp['count'] . ' ' . $itemUnitType);
                                    $cells .= wf_TableCell($opPrice);
                                    $cells .= wf_TableCell($from . ' ' . wf_img('skins/arrow_right_green.png') . ' ' . $to);
                                    $cells .= wf_TableCell($administratorName);
                                    $rows .= wf_TableRow($cells, 'row3');
                                }
                            }
                        }
                    }
                }


                $result = wf_TableBody($rows, '100%', 0, 'sortable');

                if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
                    $photoStorage = new PhotoStorage(self::PHOTOSTORAGE_SCOPE, $itemtypeId);
                    $result .= $photoStorage->renderImagesRaw();
                }
            }
            show_window(__('History') . ': ' . $itemTypeCategory . ', ' . $itemTypeName, $result);
        } else {
            show_error(__('Something went wrong'));
        }
    }

    /**
     * Renders itemtypes report spent on network upgrades
     * 
     * @return string
     */
    public function renderNetwUpgradeReport() {
        $result = '';
        $reportDataTmp = array();
        $totalPrice = 0;
        $dateFrom = (ubRouting::checkPost('datefrom')) ? ubRouting::post('datefrom', 'mres') : date("Y-m") . '-01';
        $dateTo = (ubRouting::checkPost('dateto')) ? ubRouting::post('dateto', 'mres') : date("Y-m-d");
        $inputs = wf_DatePickerPreset('datefrom', $dateFrom, true) . ' ' . __('From') . ' ';
        $inputs .= wf_DatePickerPreset('dateto', $dateTo, true) . ' ' . __('To') . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        $result .= wf_delimiter();
        if ($dateFrom and $dateTo) {
            if (!empty($this->allOutcoming)) {
                foreach ($this->allOutcoming as $io => $each) {
                    if ($each['netw'] and zb_isDateBetween($dateFrom, $dateTo, $each['date'])) {
                        $itemtypeName = $this->allItemTypeNames[$each['itemtypeid']];
                        if (isset($reportDataTmp[$itemtypeName])) {
                            $reportDataTmp[$itemtypeName]['count'] += $each['count'];
                            $reportDataTmp[$itemtypeName]['price'] += $each['price'];
                        } else {
                            $reportDataTmp[$itemtypeName]['count'] = $each['count'];
                            $reportDataTmp[$itemtypeName]['price'] = $each['price'];
                            $reportDataTmp[$itemtypeName]['itemtypeid'] = $each['itemtypeid'];
                        }
                    }
                }

                if (!empty($reportDataTmp)) {
                    $cells = wf_TableCell(__('Category'));
                    $cells .= wf_TableCell(__('Warehouse item types'));
                    $cells .= wf_TableCell(__('Count'));
                    $cells .= wf_TableCell(__('Price'));
                    $rows = wf_TableRow($cells, 'row1');
                    foreach ($reportDataTmp as $eachItemType => $eachOutData) {
                        $eachItemCatetogy = $this->allCategories[$this->allItemTypes[$eachOutData['itemtypeid']]['categoryid']];
                        $cells = wf_TableCell($eachItemCatetogy);
                        $cells .= wf_TableCell($eachItemType);
                        $cells .= wf_TableCell($eachOutData['count']);
                        $cells .= wf_TableCell($eachOutData['price']);
                        $rows .= wf_TableRow($cells, 'row5');
                        $totalPrice += $eachOutData['price'];
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= __('Total cost') . ': ' . round($totalPrice, 2);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'warning');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('No outcoming operations yet'), 'error');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Something went wrong'), 'error');
        }

        return ($result);
    }

    /**
     * Renders some itemtype outomes history for some finance accounting purposes. Yep. I dont know what for.
     * 
     * @return string
     */
    public function renderItemtypeOutcomesHistory() {
        $result = '';
        $tmpArr = array();
        $filterYear = (ubRouting::checkPost('filtersomeyear')) ? ubRouting::post('filtersomeyear') : curyear();
        $yearMask = $filterYear . '-';
        $itemtypeId = (ubRouting::checkPost('showsomeitemtypeid')) ? ubRouting::post('showsomeitemtypeid', 'int') : 0; // Yeah. Come get some.
        $messages = new UbillingMessageHelper();
        $totalPrice = 0;
        $totalCount = 0;
        $countUnit = '';
        ///search form construction
        $inputs = wf_YearSelectorPreset('filtersomeyear', __('Year'), false, $filterYear, true) . ' ';
        $inputs .= wf_Selector('showsomeitemtypeid', $this->allItemTypeNames, __('Warehouse item type'), $itemtypeId, false) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        if ($itemtypeId and $filterYear) {
            if (isset($this->allItemTypeNames[$itemtypeId])) {
                $itemTypeName = $this->allItemTypeNames[$itemtypeId];
                $itemTypeCategory = $this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']];

                if (!empty($this->allOutcoming)) {
                    foreach ($this->allOutcoming as $io => $each) {

                        if ($each['itemtypeid'] == $itemtypeId) {
                            if ($each['desttype'] == 'task') {
                                if (ispos($each['date'], $yearMask)) {
                                    $tmpArr[$each['date']]['out'][] = $each;
                                }
                            }
                        }
                    }
                }


                if (!empty($tmpArr)) {
                    krsort($tmpArr);
                    $employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
                    $allTasksAddress = ts_GetAllTasksAddress();
                    $cells = wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Type'));
                    $cells .= wf_TableCell(__('Warehouse storage'));
                    $cells .= wf_TableCell(__('Count'));
                    $cells .= wf_TableCell(__('Price'));
                    $cells .= wf_TableCell(__('Actions'));
                    $cells .= wf_TableCell(__('Address'));
                    $cells .= wf_TableCell(__('Admin'));
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

                                        //outcoming ops
                                        if ($opType == 'out') {
                                            $from = $this->allStorages[$eachOp['storageid']];
                                            $to = $this->outDests[$eachOp['desttype']] . $this->outDestControl($eachOp['desttype'], $eachOp['destparam']);
                                            $opTypeName = __('Outcoming');
                                            $rowColor = '#b50000';
                                            $opLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $eachOp['id'], wf_img_sized('skins/whoutcoming_icon.png', __('Show'), '10', '10'));
                                        }

                                        //itemtype price calculation
                                        if (isset($eachOp['price'])) {
                                            $opPrice = $eachOp['price'] * $eachOp['count'];
                                        } else {
                                            $opPrice = 0;
                                        }

                                        $cells = wf_TableCell(wf_tag('font', false, '', 'color="' . $rowColor . '"') . $eachOp['date'] . wf_tag('font', true));
                                        $cells .= wf_TableCell(wf_tag('font', false, '', 'color="' . $rowColor . '"') . $opTypeName . wf_tag('font', true) . ' ' . $opLink);
                                        $cells .= wf_TableCell(@$this->allStorages[$eachOp['storageid']]);
                                        $cells .= wf_TableCell($eachOp['count'] . ' ' . $itemUnitType);
                                        $cells .= wf_TableCell($opPrice);
                                        $cells .= wf_TableCell($from . ' ' . wf_img('skins/arrow_right_green.png') . ' ' . $to);

                                        $taskAddress = (isset($allTasksAddress[$eachOp['destparam']])) ? $allTasksAddress[$eachOp['destparam']] : '';
                                        $cells .= wf_TableCell($taskAddress);
                                        $cells .= wf_TableCell($administratorName);
                                        $rows .= wf_TableRow($cells, 'row3');
                                        $totalCount += $eachOp['count'];
                                        $totalPrice += $opPrice;
                                        $countUnit = $itemUnitType;
                                    }
                                }
                            }
                        }
                    }


                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= __('Total') . ' ' . __('Count') . ': ' . $totalCount . ' ' . $countUnit;
                    $result .= wf_tag('br');
                    $result .= __('Total') . ' ' . __('Price') . ': ' . $totalPrice;
                } else {
                    $result .= $messages->getStyledMessage(__('Nothing found'), 'info');
                }
            } else {
                $result .= $messages->getStyledMessage(__('Something went wrong'), 'error');
            }
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders per year purchases report
     * 
     * @return string
     */
    public function renderPurchasesReport() {
        $result = '';
        $tmpResult = array();
        $totalSumm = 0;
        $showYear = (ubRouting::checkPost('purchasesyear')) ? ubRouting::post('purchasesyear', 'int') . '-' : curyear() . '-';

        $inputs = wf_YearSelectorPreset('purchasesyear', __('Year'), false, ubRouting::post('purchasesyear'), false) . ' ';
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {
                if ($each['contractorid'] != 0) {
                    if (ispos($each['date'], $showYear)) {
                        $opMonth = strtotime($each['date']);
                        $opMonth = date("m", $opMonth);
                        $opPrice = $each['price'] * $each['count'];

                        if (isset($tmpResult[$opMonth])) {
                            $tmpResult[$opMonth]['count']++;
                            $tmpResult[$opMonth]['price'] += $opPrice;
                        } else {
                            $tmpResult[$opMonth]['count'] = 1;
                            $tmpResult[$opMonth]['price'] = $opPrice;
                        }
                        $totalSumm += $opPrice;
                    }
                }
            }

            if (!empty($tmpResult)) {
                $yearTotalCount = 0;
                $yearTotalSumm = 0;

                $monthArr = months_array_localized();
                $cells = wf_TableCell('');
                $cells .= wf_TableCell(__('Month'));
                $cells .= wf_TableCell(__('Count'));
                $cells .= wf_TableCell(__('Sum'));
                $cells .= wf_TableCell(__('Visual'), '50%');
                $rows = wf_TableRow($cells, 'row1');
                foreach ($monthArr as $monthNum => $monthName) {
                    if (isset($tmpResult[$monthNum])) {
                        $monthCount = $tmpResult[$monthNum]['count'];
                        $monthSumm = $tmpResult[$monthNum]['price'];
                        $yearTotalCount += $monthCount;
                        $yearTotalSumm += $monthSumm;
                    } else {
                        $monthCount = 0;
                        $monthSumm = 0;
                    }

                    $cells = wf_TableCell($monthNum);
                    $cells .= wf_TableCell($monthName);
                    $cells .= wf_TableCell($monthCount);
                    $cells .= wf_TableCell(zb_CashBigValueFormat($monthSumm));
                    $cells .= wf_TableCell(web_bar($monthSumm, $totalSumm));
                    $rows .= wf_TableRow($cells, 'row3');
                }

                $cells = wf_TableCell('');
                $cells .= wf_TableCell(__('Total'));
                $cells .= wf_TableCell($yearTotalCount);
                $cells .= wf_TableCell(zb_CashBigValueFormat($yearTotalSumm));
                $cells .= wf_TableCell('');
                $rows .= wf_TableRow($cells, 'row2');

                $result .= wf_TableBody($rows, '100%', 0, '');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
        return ($result);
    }

    /**
     * Renders incomes by contractor report
     * 
     * @return string
     */
    public function renderContractorIncomesReport() {
        $result = '';
        $tmpResult = array();
        $totalSumm = 0;
        if (ubRouting::checkPost('conincomesyear')) {
            $rawYear = ubRouting::post('conincomesyear', 'int');
            if ($rawYear != '1488') {
                $showYear = $rawYear . '-';
            } else {
                $showYear = '-';
            }
        } else {
            $showYear = curyear() . '-';
        }
        $showContractor = (ubRouting::checkPost('conincomesid')) ? ubRouting::post('conincomesid', 'int') : '';

        $inputs = wf_YearSelectorPreset('conincomesyear', __('Year'), false, ubRouting::post('conincomesyear'), true) . ' ';
        $inputs .= wf_Selector('conincomesid', $this->allContractors, __('Contractor'), $showContractor, false);
        $inputs .= wf_Submit(__('Show'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        if ($showContractor) {
            if (!empty($this->allIncoming)) {
                foreach ($this->allIncoming as $io => $each) {
                    if ($each['contractorid'] != 0 and $each['contractorid'] == $showContractor) {
                        if (ispos($each['date'], $showYear)) {
                            $opPrice = $each['price'] * $each['count'];
                            $tmpResult[$each['id']] = $each;
                            $totalSumm += $opPrice;
                        }
                    }
                }

                if (!empty($tmpResult)) {
                    rsort($tmpResult); //from newest
                    $cells = wf_TableCell(__('ID'));
                    $cells .= wf_TableCell(__('Date'));
                    $cells .= wf_TableCell(__('Category'));
                    $cells .= wf_TableCell(__('Warehouse item types'));
                    $cells .= wf_TableCell(__('Count'));
                    $cells .= wf_TableCell(__('Price per unit'));
                    $cells .= wf_TableCell(__('Sum'));
                    $cells .= wf_TableCell(__('Warehouse storage'));
                    $cells .= wf_TableCell(__('Admin'));
                    $cells .= wf_TableCell(__('Notes'));
                    $cells .= wf_TableCell(__('Actions'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($tmpResult as $io => $each) {
                        $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $each['id'], wf_img_sized('skins/whincoming_icon.png', '', '10', '10') . ' ' . __('Show'));
                        $cells = wf_TableCell($each['id']);
                        $cells .= wf_TableCell($each['date']);
                        $cells .= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                        $cells .= wf_TableCell(wf_link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $each['itemtypeid'], $this->allItemTypeNames[$each['itemtypeid']]));
                        $cells .= wf_TableCell($each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']]);
                        $cells .= wf_TableCell($each['price']);
                        $cells .= wf_TableCell(round($each['price'] * $each['count'], 2));
                        $cells .= wf_TableCell(@$this->allStorages[$each['storageid']]);
                        $cells .= wf_TableCell($each['admin']);
                        $cells .= wf_TableCell($each['notes']);
                        $cells .= wf_TableCell($actLink);
                        $rows .= wf_TableRow($cells, 'row5');
                    }

                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                    $result .= wf_tag('b') . __('Total') . ': ' . zb_CashBigValueFormat($totalSumm) . wf_tag('b', true);
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing found'), 'info');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        }
        return ($result);
    }

    /**
     * Renders returns list container
     * 
     * @return string
     */
    public function renderReturnsReport() {
        $result = '';
        $columns = array('Date', 'Outcoming operation', 'Warehouse storage', 'Category', 'Warehouse item type', 'Count', 'Price', 'Admin', 'Notes');
        $opts = '"order": [[ 0, "desc" ]]';
        $ajUrl = self::URL_ME . '&' . self::URL_REPORTS . '&returns=true&ajreturnslist=true';
        $result .= wf_JqDtLoader($columns, $ajUrl, false, __('Outcoming operations'), 50, $opts);
        return ($result);
    }

    /**
     * Renders returned operations list
     * 
     * @return void
     */
    public function ajReturnsList() {
        $json = new wf_JqDtHelper();
        $this->loadReturns();
        if (!empty($this->allReturns)) {
            foreach ($this->allReturns as $io => $each) {
                $itemtypeId = $each['itemtypeid'];
                $itemtypeName = $this->allItemTypeNames[$itemtypeId];
                $itemCategory = $this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']];
                $itemtypeUnit = $this->allItemTypes[$itemtypeId]['unit'];
                $outOpLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['outid'], $each['outid']);
                $data[] = $each['date'];
                $data[] = $outOpLink;
                $data[] = $this->allStorages[$each['storageid']];
                $data[] = $itemCategory;
                $itemHistLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&itemhistory=' . $itemtypeId, $itemtypeName);
                $data[] = $itemHistLink;
                $data[] = $each['count'] . ' ' . __($itemtypeUnit);
                $data[] = $each['price'];
                $data[] = $each['admin'];
                $data[] = $each['note'];

                $json->addRow($data);
                unset($data);
            }
        }
        $json->getJson();
    }

    /**
     * Returns array of all existing item types
     * 
     * @return array
     */
    public function getAllItemTypes() {
        $result = array();
        if (!empty($this->allItemTypes)) {
            $result = $this->allItemTypes;
        }
        return ($result);
    }

    /**
     * Returns array of all existing item type categories
     * 
     * @return array
     */
    public function getAllItemCategories() {
        $result = array();
        if (!empty($this->allCategories)) {
            $result = $this->allCategories;
        }
        return ($result);
    }

    /**
     * Returns all available income operations
     * 
     * @return array
     */
    public function getAllIncomes() {
        return ($this->allIncoming);
    }

    /**
     * Returns all available outcome operations
     * 
     * @return array
     */
    public function getAllOutcomes() {
        return ($this->allOutcoming);
    }
}

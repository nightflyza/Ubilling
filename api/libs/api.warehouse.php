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
     * Preloaded reserve entries
     *
     * @var array
     */
    protected $allReserve = array();

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
     * System messages object
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Default asterisk required fields notifier
     *
     * @var string
     */
    protected $sup = '';

    /**
     * Default routing desc
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

    public function __construct() {
        $this->loadAltCfg();
        $this->setUnitTypes();
        $this->setOutDests();
        $this->setSup();
        $this->loadMessages();
        $this->loadAllEmployee();
        $this->loadActiveEmployee();
        $this->loadCategories();
        $this->loadItemTypes();
        $this->loadStorages();
        $this->loadContractors();
        $this->loadInOperations();
        $this->loadOutOperations();
        $this->loadReserve();
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
     * Creates system message helper object instance
     * 
     * @return void
     */
    protected function loadMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all existing employees from database
     * 
     * @return void
     */
    protected function loadAllEmployee() {
        $this->allEmployee = ts_GetAllEmployee();
    }

    /**
     * Loads all existing employees from database
     * 
     * @return void
     */
    protected function loadActiveEmployee() {
        $this->activeEmployee = ts_GetActiveEmployee();
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
     * @return void
     */
    protected function loadOutOperations() {
        $query = "SELECT * from `wh_out`";
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
                    } else {
                        $result = $this->messages->getStyledMessage(__('The balance of goods and materials in stock is less than the amount') . ' (' . $countF . ' > ' . $itemtypeRemains . '-' . $alreadyReserved . ')', 'error');
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
                    $count = $_POST['editreservecount'];
                    $countF = mysql_real_escape_string($count);
                    $countF = str_replace(',', '.', $countF);
                    $employeeId = vf($_POST['editreserveemployeeid'], 3);
                    $where = " WHERE `id`='" . $id . "';";
                    $storageRemains = $this->remainsOnStorage($reserveStorage);
                    @$itemtypeRemains = $storageRemains[$reserveData['itemtypeid']];
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
     * Renders list of available reserved items sorted by Employee with some controls
     * 
     * @return string
     */
    public function reserveRenderList() {
        $result = '';
        if (!empty($this->allReserve)) {
            $cells = wf_TableCell(__('ID'));
            $cells.=wf_TableCell(__('Warehouse storage'));
            $cells.=wf_TableCell(__('Category'));
            $cells.= wf_TableCell(__('Warehouse item type'));
            $cells.= wf_TableCell(__('Count'));
            $cells.= wf_TableCell(__('Worker'));
            $cells.= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($this->allReserve as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.=wf_TableCell(@$this->allStorages[$each['storageid']]);
                $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']]);
                $cells.= wf_TableCell(@$this->allItemTypeNames[$each['itemtypeid']]);
                $cells.= wf_TableCell($each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']]);
                $cells.= wf_TableCell(@$this->allEmployee[$each['employeeid']]);
                $actLinks = wf_JSAlert(self::URL_ME . '&' . self::URL_RESERVE . '&deletereserve=' . $each['id'], web_delete_icon(), $this->messages->getEditAlert()) . ' ';
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit') . ' ' . __('Reservation'), $this->reserveEditForm($each['id']), '');
                $cells.= wf_TableCell($actLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
            $result = wf_TableBody($rows, '100%', 0, 'sortable');
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
            show_window('', wf_Link(self::URL_ME, __('Back'), false, 'ubButton'));
        } else {
            show_window('', wf_Link($url, __('Back'), false, 'ubButton'));
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
        if (cfr('WAREHOUSEOUT')) {
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
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell(@$this->allCategories[$each['categoryid']]);
                $cells.= wf_TableCell($each['name']);
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
        $query = "INSERT INTO `wh_in` (`id`, `date`, `itemtypeid`, `contractorid`, `count`, `barcode`, `price`, `storageid`, `notes`) "
                . "VALUES (NULL, '" . $dateF . "', '" . $itemtypeid . "', '" . $contractorid . "', '" . $countF . "', '" . $barcode . "', '" . $priceF . "', '" . $storageid . "', '" . $notes . "');";
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
            $columns = array('ID', 'Date', 'Category', 'Warehouse item types', 'Count', 'Price per unit', 'Sum', 'Warehouse storage', 'Actions');
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_IN . '&' . self::URL_INAJLIST, true, 'Incoming operations', 50);
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
        $result = '{ 
                  "aaData": [ ';
        if (!empty($this->allIncoming)) {
            foreach ($this->allIncoming as $io => $each) {

                $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showinid=' . $each['id'], wf_img_sized('skins/whincoming_icon.png', '', '10', '10') . ' ' . __('Show'));
                $actLink = str_replace('"', '', $actLink);
                $actLink = trim($actLink);
                $result.='
                    [
                    "' . $each['id'] . '",
                    "' . $each['date'] . '",
                    "' . @$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']] . '",
                    "' . $this->allItemTypeNames[$each['itemtypeid']] . '",
                    "' . $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']] . '",
                    "' . $each['price'] . '",    
                    "' . ($each['price'] * $each['count']) . '",
                    "' . @$this->allStorages[$each['storageid']] . '",
                    "' . $actLink . '"
                    ],';
            }
        }

        $result = zb_CutEnd($result);
        $result.='] 
        }';
        die($result);
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
            $cells.= wf_TableCell($contractorName);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Category'), '30%', 'row2');
            $cells.= wf_TableCell(@$this->allCategories[$this->allItemTypes[$operationData['itemtypeid']]['categoryid']]);
            $rows.= wf_TableRow($cells, 'row3');
            $cells = wf_TableCell(__('Warehouse item type'), '30%', 'row2');
            $cells.= wf_TableCell(@$this->allItemTypeNames[$operationData['itemtypeid']]);
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
        $result = '{ 
                  "aaData": [ ';
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

                    $actLink = str_replace('"', '', $actLink);
                    $actLink = str_replace("\n", '', $actLink);
                    $actLink = trim($actLink);
                    $result.='
                    [
                    "' . @$this->allCategories[$this->allItemTypes[$itemtypeid]['categoryid']] . '",
                    "' . @$this->allItemTypeNames[$itemtypeid] . '",
                    "' . $count . ' ' . @$this->unitTypes[$this->allItemTypes[$itemtypeid]['unit']] . '",
                    "' . $actLink . '"
                    ],';
                }
            }
        }

        $result = zb_CutEnd($result);
        $result.='] 
        }';
        die($result);
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
            $columns = array('ID', 'Date', 'Destination', 'Warehouse storage', 'Category', 'Warehouse item types', 'Count', 'Price per unit', 'Sum', 'Actions');
            $result = wf_JqDtLoader($columns, self::URL_ME . '&' . self::URL_OUT . '&' . self::URL_OUTAJLIST, true, 'Outcoming operations', 50);
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
        $result = '{ 
                  "aaData": [ ';
        if (!empty($this->allOutcoming)) {
            foreach ($this->allOutcoming as $io => $each) {
                $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showoutid=' . $each['id'], wf_img_sized('skins/whoutcoming_icon.png', '', '10', '10') . ' ' . __('Show'));
                $actLink = str_replace('"', '', $actLink);
                $actLink = trim($actLink);
                $result.='
                    [
                    "' . $each['id'] . '",
                    "' . $each['date'] . '",
                    "' . $this->outDests[$each['desttype']] . $this->outDestControl($each['desttype'], $each['destparam']) . '",
                    "' . @$this->allStorages[$each['storageid']] . '",
                    "' . @$this->allCategories[$this->allItemTypes[$each['itemtypeid']]['categoryid']] . '",
                    "' . $this->allItemTypeNames[$each['itemtypeid']] . '",
                    "' . $each['count'] . ' ' . @$this->unitTypes[$this->allItemTypes[$each['itemtypeid']]['unit']] . '",
                    "' . $each['price'] . '",
                    "' . ($each['price'] * $each['count']) . '",
                    "' . $actLink . '"
                    ],';
            }
        }

        $result = zb_CutEnd($result);
        $result.='] 
        }';
        die($result);
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
     * 
     * @return string
     */
    public function outcomingCreateForm($storageid, $itemtypeid) {
        $result = '';
        $storageid = vf($storageid, 3);
        $itemtypeid = vf($itemtypeid, 3);
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


            $isReserved = $this->reserveGet($storageid, $itemtypeid);

            foreach ($this->outDests as $destMark => $destName) {
                $tmpDests[self::URL_ME . '&' . self::URL_OUT . '&' . self::URL_AJODSELECTOR . $destMark] = $destName;
            }

            //form construct
            $inputs = wf_AjaxLoader();
            $inputs.= wf_HiddenInput('newoutdate', curdate());
            $inputs.= wf_AjaxSelectorAC('ajoutdestselcontainer', $tmpDests, __('Destination'), '', false);
            $inputs.= wf_AjaxContainer('ajoutdestselcontainer', '', $this->outcomindAjaxDestSelector('task'));
            $inputs.= wf_HiddenInput('newoutitemtypeid', $itemtypeid);
            $inputs.= wf_HiddenInput('newoutstorageid', $storageid);
            $inputs.= wf_TextInput('newoutcount', $itemUnit . ' (' . ($itemRemainsStorage - $isReserved) . ' ' . __('maximum') . ')', '', true, '4');
            $inputs.= wf_TextInput('newoutprice', __('Price'), '', true, '4');
            $inputs.= wf_TextInput('newoutnotes', __('Notes'), '', true, 25);
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
            $cells.= wf_TableCell(@$this->allItemTypeNames[$operationData['itemtypeid']]);
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
     * 
     * @return string not emplty if something went wrong
     */
    public function outcomingCreate($date, $desttype, $destparam, $storageid, $itemtypeid, $count, $price = '', $notes = '') {
        $result = '';
        $date = mysql_real_escape_string($date);
        $desttype = mysql_real_escape_string($desttype);
        $destparam = mysql_real_escape_string($destparam);
        $storageid = vf($storageid, 3);
        $itemtypeid = vf($itemtypeid, 3);
        $countF = mysql_real_escape_string($count);
        $countF = str_replace('-', '', $countF);
        $countF = str_replace(',', '.', $countF);
        $priceF = mysql_real_escape_string($price);
        $priceF = str_replace(',', '.', $priceF);
        $notes = mysql_real_escape_string($notes);

        if (isset($this->allStorages[$storageid])) {
            if (isset($this->allItemTypes[$itemtypeid])) {
                $allItemRemains = $this->remainsOnStorage($storageid);
                @$itemRemains = $allItemRemains[$itemtypeid];
                $itemsReserved = $this->reserveGet($storageid, $itemtypeid);
                $realRemains = $itemRemains - $itemsReserved;
                if ($countF <= $realRemains) {
                    $query = "INSERT INTO `wh_out` (`id`,`date`,`desttype`,`destparam`,`storageid`,`itemtypeid`,`count`,`price`,`notes`) VALUES "
                            . "(NULL,'" . $date . "','" . $desttype . "','" . $destparam . "','" . $storageid . "','" . $itemtypeid . "','" . $countF . "','" . $priceF . "','" . $notes . "')";
                    nr_query($query);
                    $newId = simple_get_lastid('wh_out');
                    log_register('WAREHOUSE OUTCOME CREATE [' . $newId . '] ITEM [' . $itemtypeid . '] COUNT `' . $count . '` PRICE `' . $price . '`');
                    if ($desttype == 'storage') {
                        $this->incomingCreate($date, $itemtypeid, 0, $destparam, $count, $price, '', __('from') . ' ' . __('Warehouse storage') . ' `' . $this->allStorages[$storageid] . '`');
                    }
                } else {
                    $result = $this->messages->getStyledMessage(__('The balance of goods and materials in stock is less than the amount') . ' (' . $countF . ' > ' . $itemRemains . '-' . $itemsReserved . ')', 'error');
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
        $result = '{ 
                  "aaData": [ ';
        if (!empty($all)) {
            foreach ($all as $itemtypeId => $count) {
                if ($count > 0) {
                    $actLink = wf_Link(self::URL_ME . '&' . self::URL_VIEWERS . '&showremains=' . $itemtypeId, wf_img_sized('skins/icon_search_small.gif', '', '10', '10') . ' ' . __('Show'));
                    $actLink = str_replace('"', '', $actLink);
                    $actLink = trim($actLink);
                    $result.='
                    [
                    "' . $this->allCategories[$this->allItemTypes[$itemtypeId]['categoryid']] . '",
                    "' . $this->allItemTypeNames[$itemtypeId] . '",
                    "' . $count . ' ' . $this->unitTypes[$this->allItemTypes[$itemtypeId]['unit']] . '",
                    "' . $actLink . '"
                    ],';
                }
            }
        }

        $result = zb_CutEnd($result);
        $result.='] 
        }';
        die($result);
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

            case 'out':if (isset($this->allOutcoming[$id])) {
                    $itemName = $this->allItemTypeNames[$this->allOutcoming[$id]['itemtypeid']];
                    $qr->text($itemName . ' ' . __('Outcoming operation') . '# ' . $id);
                } else {
                    $qr->text('Wrong ID');
                }

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
            foreach ($this->allOutcoming as $io => $each) {
                if (($each['desttype'] == 'task') AND ( $each['destparam'] == $taskid)) {
                    $tmpArr[$each['id']] = $each;
                }
            }

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
                $fourthColumnTotal+=$firstColumnPrice;
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

}

?>
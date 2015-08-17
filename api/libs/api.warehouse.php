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
     * Available unit types as unittype=>localized name
     *
     * @var array
     */
    protected $unitTypes = array();

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
    const URL_AJITSELECTOR = 'ajits=';

    public function __construct() {
        $this->loadAltCfg();
        $this->setUnitTypes();
        $this->setSup();
        $this->loadMessages();
        $this->loadAllEmployee();
        $this->loadActiveEmployee();
        $this->loadCategories();
        $this->loadItemTypes();
        $this->loadStorages();
        $this->loadContractors();
        $this->loadInOperations();
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
        $query = "SELECT* from `wh_itemtypes`";
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
     * Renders control panel for whole module
     * 
     * @return string
     */
    public function renderPanel() {
        $result = '';
        $result.= wf_Link(self::URL_ME . '&' . self::URL_IN, wf_img_sized('skins/whincoming_icon.png') . ' ' . __('Incoming operations'), false, 'ubButton');
        $dirControls = wf_Link(self::URL_ME . '&' . self::URL_CATEGORIES, wf_img_sized('skins/categories_icon.png') . ' ' . __('Warehouse categories'), false, 'ubButton');
        $dirControls.= wf_Link(self::URL_ME . '&' . self::URL_ITEMTYPES, wf_img_sized('skins/folder_icon.png') . ' ' . __('Warehouse item types'), false, 'ubButton');
        $dirControls.= wf_Link(self::URL_ME . '&' . self::URL_STORAGES, wf_img_sized('skins/whstorage_icon.png') . ' ' . __('Warehouse storages'), false, 'ubButton');
        $dirControls.= wf_Link(self::URL_ME . '&' . self::URL_CONTRACTORS, wf_img_sized('skins/whcontractor_icon.png') . ' ' . __('Contractors'), false, 'ubButton');
        $result.=wf_modalAuto(web_icon_extended() . ' ' . __('Directories'), __('Directories'), $dirControls, 'ubButton');
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

        if (!empty($this->allItemTypes)) {
            foreach ($this->allItemTypes as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell(@$this->allCategories[$each['categoryid']]);
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell(@$this->unitTypes[$each['unit']]);
                $cells.= wf_TableCell($each['reserve']);
                $actLinks = wf_JSAlertStyled(self::URL_ME . '&' . self::URL_ITEMTYPES . '&deleteitemtype=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert());
                $actLinks.= wf_modalAuto(web_edit_icon(), __('Edit'), $this->itemtypesEditForm($each['id']), '');
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
        $result.= wf_Link(self::URL_ME . '&' . self::URL_ITEMTYPES, wf_img_sized('skins/folder_icon.png', '', '10', '10'), false);
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
            $inputs.= wf_Link(self::URL_ME . '&' . self::URL_CATEGORIES, wf_img_sized('skins/categories_icon.png', '', '10', '10'), false);
            $inputs.= wf_tag('br');
            $inputs.= wf_AjaxContainer('ajItemtypesContainer', '', $this->itemtypesCategorySelector('newinitemtypeid', $firstCateKey));
            $inputs.= wf_Selector('newincontractorid', $this->allContractors, __('Contractor'), '', false);
            $inputs.= wf_Link(self::URL_ME . '&' . self::URL_CONTRACTORS, wf_img_sized('skins/whcontractor_icon.png', '', '10', '10'), false);
            $inputs.= wf_tag('br');
            $inputs.= wf_Selector('newinstorageid', $this->allStorages, __('Warehouse storage'), '', false);
            $inputs.= wf_Link(self::URL_ME . '&' . self::URL_STORAGES, wf_img_sized('skins/whstorage_icon.png', '', '10', '10'), false);
            $inputs.= wf_tag('br');
            $inputs.= wf_TextInput('newincount', __('Count'), '', false, 5);
            $inputs.= wf_tag('br');
            $inputs.= wf_TextInput('newinprice', __('Price'), '', false, 5);
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
        $countF = mysql_real_escape_string($countF);
        $priceF = str_replace(',', '.', $price);
        $priceF = mysql_real_escape_string($priceF);
        $notes = mysql_real_escape_string($notes);
        $barcode = mysql_real_escape_string($barcode);
        $query = "INSERT INTO `wh_in` (`id`, `date`, `itemtypeid`, `contractorid`, `count`, `barcode`, `price`, `storageid`, `notes`) "
                . "VALUES (NULL, '" . $dateF . "', '" . $itemtypeid . "', '" . $contractorid . "', '" . $count . "', '" . $barcode . "', '" . $price . "', '" . $storageid . "', '" . $notes . "');";
        nr_query($query);
        $newId = simple_get_lastid('wh_in');
        log_register('WAREHOUSE INCOME CREATE [' . $newId . '] ITEM [' . $itemtypeid . '] COUNT `' . $count . '` PRICE `' . $price . '`');
    }

}

?>
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
     * @var string
     */
    protected $allItemTypes = array();

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
     * Default routing desc
     */
    const URL_ME = '?module=warehouse';
    const URL_CATEGORIES = 'categories=true';
    const URL_ITEMTYPES = 'itemtypes=true';

    public function __construct() {
        $this->loadAltCfg();
        $this->setUnitTypes();
        $this->loadMessages();
        $this->loadAllEmployee();
        $this->loadActiveEmployee();
        $this->loadCategories();
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
            }
        }
    }

    /**
     * Returns cetegory creation form
     * 
     * @return string
     */
    public function categoriesAddForm() {
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
        $result.= wf_Link(self::URL_ME . '&' . self::URL_CATEGORIES, wf_img_sized('skins/categories_icon.png') . ' ' . __('Warehouse categories'), false, 'ubButton');
        $result.= wf_Link(self::URL_ME . '&' . self::URL_ITEMTYPES, wf_img_sized('skins/folder_icon.png') . ' ' . __('Warehouse item types'), false, 'ubButton');
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
            $inputs.= wf_TextInput($result, __('Desired reserve'), '', true, 5);
            $inputs.= wf_Submit(__('Create'));

            $result = wf_Form(self::URL_ME . '&' . self::URL_ITEMTYPES, 'POST', $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('No existing categories'), 'warning');
        }
        return ($result);
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
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->allItemTypes)) {
            foreach ($this->allItemTypes as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell(@$this->allCategories[$each['categoryid']]);
                $cells.= wf_TableCell($each['name']);
                $cells.= wf_TableCell(@$this->unitTypes[$each['unit']]);
                $cells.= wf_TableCell($each['reserve']);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        
        $result=  wf_TableBody($rows, '100%', 0, 'sortable');
        
        return ($result);
    }
    
        

}

?>
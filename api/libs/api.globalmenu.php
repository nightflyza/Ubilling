<?php

class GlobalMenu {

    /**
     * Contains globalmenu config as key=>value with sections
     *
     * @var array
     */
    protected $rawData = array();

    /**
     * Contains some preprocessed menu data
     *
     * @var array
     */
    protected $menuData = array();

    /**
     * Contains available menu categories
     *
     * @var array
     */
    protected $categories = array();

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains disabled menu items
     *
     * @var array
     */
    protected $disabled = array();

    /**
     * Contains fast access menu items
     *
     * @var array
     */
    protected $fastAccess = array();

    /**
     * Contains default menu icons path
     *
     * @var string
     */
    protected $iconsPath = 'skins/menuicons/';

    /**
     * Pre-rendered menu code
     *
     * @var string
     */
    protected $menuCode = '';

    /**
     * Pre-rendered fast access menu code
     *
     * @var string
     */
    protected $menuCodeFA = '';

    /**
     * Current user`s login
     *
     * @var string
     */
    protected $myLogin = '';

    const DEFAULT_ICON = 'defaulticon.png';
    const CUSTOMS_PATH = 'content/documents/glmcustoms/';

    /**
     * Creates new GlobalMenu instance
     */
    public function __construct() {
        $this->setLogin();
        $this->loadCustoms();
        $this->loadConfig();
        $this->loadData();
        $this->extractCategories();
        $this->parseData();
    }

    /**
     * Sets current logged in user login into private property
     * 
     * @return void
     */
    protected function setLogin() {
        if (LOGGED_IN) {
            $this->myLogin = whoami();
        }
    }

    /**
     * Loads global menu custom data if its available
     * 
     * @return void
     */
    protected function loadCustoms() {
        if (!empty($this->myLogin)) {
            //read and preprocess disabled modules data
            $disabledFilename = self::CUSTOMS_PATH . $this->myLogin . '.disabled';
            if (file_exists($disabledFilename)) {
                $tmpData = file_get_contents($disabledFilename);
                if (!empty($tmpData)) {
                    $tmpData = explode(',', $tmpData);
                    if (!empty($tmpData)) {
                        foreach ($tmpData as $io => $modulename) {
                            $this->disabled[$modulename] = $io;
                        }
                    }
                }
            }

            //read and preprocess fast access modules data
            $fastaccFilename = self::CUSTOMS_PATH . $this->myLogin . '.fastacc';
            if (file_exists($fastaccFilename)) {
                $tmpData = file_get_contents($fastaccFilename);
                if (!empty($tmpData)) {
                    $tmpData = explode(',', $tmpData);
                    if (!empty($tmpData)) {
                        foreach ($tmpData as $io => $modulename) {
                            $this->fastAccess[$modulename] = $io;
                        }
                    }
                }
            }
        }
    }

    /**
     * Loads global alter.ini into protected data prop
     * 
     * @global type $ubillingConfig
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads menudata into private raw data property
     * 
     * @return void
     */
    protected function loadData() {
        $this->rawData = rcms_parse_ini_file(CONFIG_PATH . 'globalmenu.ini', true);
    }

    /**
     * Extracts categories from filled up rawData
     * 
     * @return void
     */
    protected function extractCategories() {
        if (!empty($this->rawData)) {
            if (isset($this->rawData['ubillingglobalmenucategories'])) {
                $this->categories = $this->rawData['ubillingglobalmenucategories'];
                unset($this->rawData['ubillingglobalmenucategories']);
                foreach ($this->categories as $io => $each) {
                    $this->menuData[$each] = '';
                }
            }
        }
    }

    /**
     * Parses raw menu data into ready to output menu array witch right/option checks
     * 
     * @return void
     */
    protected function parseData() {
        if (!empty($this->rawData)) {
            foreach ($this->rawData as $io => $each) {
                $icon = (!empty($each['ICON'])) ? $each['ICON'] : self::DEFAULT_ICON;
                $icon = $this->iconsPath . $icon;
                $name = __($each['NAME']);
                $checkRight = (!empty($each['NEED_RIGHT'])) ? cfr($each['NEED_RIGHT']) : true;
                $checkOption = (!empty($each['NEED_OPTION'])) ? @$this->altCfg[$each['NEED_OPTION']] : true;
                if ($checkRight and $checkOption) {
                    if (!isset($this->disabled[$io])) {
                        $this->menuData[$each['CATEGORY']] .= wf_tag('li', false) . wf_Link($each['URL'], wf_img($icon) . ' ' . $name, false) . wf_tag('li', true);
                    }
                }
            }
        }
    }

    /**
     * Formats existing menu data into printable HTML code
     * 
     * @return void
     */
    protected function formatMenuCode() {
        if (!empty($this->categories)) {
            if (!empty($this->menuData)) {
                foreach ($this->categories as $eachCategoryName => $eachCategoryId) {
                    if (!empty($this->menuData[$eachCategoryId])) {
                        $this->menuCode .= wf_tag('h3', false) . __($eachCategoryName) . wf_tag('h3', true);
                        $this->menuCode .= wf_tag('ul', false, 'toggleGMENU');
                        $this->menuCode .= $this->menuData[$eachCategoryId];
                        $this->menuCode .= wf_tag('ul', true);
                    }
                }
            }
        }
    }

    /**
     * Returns formatted menu code
     * 
     * @return string
     */
    public function render() {
        $this->formatMenuCode();
        return($this->menuCode);
    }

    /**
     * Formats existing menu data into printable HTML code as personalization form
     * 
     * @return string
     */
    public function getEditForm() {
        $result = '';
        $tmpArr = array();
        if (!empty($this->categories)) {
            if (!empty($this->rawData)) {
                foreach ($this->rawData as $io => $each) {
                    //table headers
                    if (!isset($tmpArr[$each['CATEGORY']])) {
                        $formCells = wf_TableCell(__('Module'), '50%');
                        $formCells .= wf_TableCell(__('Hidden'));
                        $formCells .= wf_TableCell(__('Fast access'));
                        $formRows = wf_TableRow($formCells, 'row1');
                        $tmpArr[$each['CATEGORY']] = $formRows;
                    }
                    //table rows
                    $icon = (!empty($each['ICON'])) ? $each['ICON'] : self::DEFAULT_ICON;
                    $icon = $this->iconsPath . $icon;
                    $name = __($each['NAME']);
                    $checkRight = (!empty($each['NEED_RIGHT'])) ? cfr($each['NEED_RIGHT']) : true;
                    $checkOption = (!empty($each['NEED_OPTION'])) ? @$this->altCfg[$each['NEED_OPTION']] : true;
                    if ($checkRight and $checkOption) {
                        $formCells = wf_TableCell(wf_Link($each['URL'], wf_img($icon) . ' ' . $name, false));
                        $disabledFlag = (isset($this->disabled[$io])) ? true : false;
                        $formCells .= wf_TableCell(wf_CheckInput('_glmdisabled[' . $io . ']', '', false, $disabledFlag));
                        $fastAccessFlag = (isset($this->fastAccess[$io])) ? true : false;
                        $formCells .= wf_TableCell(wf_CheckInput('_glmfastacc[' . $io . ']', '', false, $fastAccessFlag));
                        $formRows = wf_TableRow($formCells, 'row5');
                        $tmpArr[$each['CATEGORY']] .= $formRows;
                    }
                }
            }


            foreach ($this->categories as $eachCategoryName => $eachCategoryId) {
                $result .= wf_tag('h3', false) . __($eachCategoryName) . wf_tag('h3', true);
                $result .= wf_TableBody($tmpArr[$eachCategoryId], '100%', 0, '');
            }
        }

        if (!empty($result)) {
            $result .= wf_HiddenInput('glcustomconfedit', 'true');
            $result .= wf_tag('br');
            $result .= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $result, '');
        }

        return ($result);
    }

    /**
     * Saves currently posted custom configs to FS
     * 
     * @return void
     */
    public function saveCustomConfigs() {
        //disabled modules management
        $disabledFilename = self::CUSTOMS_PATH . $this->myLogin . '.disabled';
        $tmpData = '';

        if (wf_CheckPost(array('_glmdisabled'))) {
            if (!empty($_POST['_glmdisabled'])) {
                foreach ($_POST['_glmdisabled'] as $modulename => $on) {
                    $tmpData .= trim($modulename) . ',';
                }
                $tmpData = rtrim($tmpData, ",");
                file_put_contents($disabledFilename, $tmpData);
                $tmpData = '';
            }
        } else {
            file_put_contents($disabledFilename, '');
        }

        //fast access modules management
        $fastaccFilename = self::CUSTOMS_PATH . $this->myLogin . '.fastacc';
        $tmpData = '';

        if (wf_CheckPost(array('_glmfastacc'))) {
            if (!empty($_POST['_glmfastacc'])) {
                foreach ($_POST['_glmfastacc'] as $modulename => $on) {
                    $tmpData .= trim($modulename) . ',';
                }
                $tmpData = rtrim($tmpData, ",");
                file_put_contents($fastaccFilename, $tmpData);
                $tmpData = '';
            }
        } else {
            file_put_contents($fastaccFilename, '');
        }
    }

    /**
     * Rebuilds fast access menu data with newly saved config
     * 
     * @return void
     */
    public function rebuildFastAccessMenuData() {
        $delimiter = wf_tag('div', false, 'breadcrumb_divider') . wf_tag('div', true);
        $fastaccData = self::CUSTOMS_PATH . $this->myLogin . '.fastaccdata';
        $tmpData = '';
        if (!empty($this->rawData)) {
            foreach ($this->rawData as $io => $each) {
                if (isset($this->fastAccess[$io])) {
                    $name = __($each['NAME']);
                    $url = $each['URL'];
                    $tmpData .= $delimiter . wf_Link($url, $name, false);
                }
            }
        }
        //edit control here
        if (cfr('GLMENUCONF')) {
            $tmpData .= wf_tag('div', false, 'breadcrumb_divider') . wf_tag('div', true);
            $tmpData .= wf_Link('?module=glmenuconf', '+', false, '', 'title="' . __('Personalize menu') . '"');
        }
        file_put_contents($fastaccData, $tmpData);
    }

    /**
     * Loads prepared personal fast access menu data if it exists
     * 
     * @return void
     */
    protected function loadFastAccesMenu() {
        $this->menuCodeFA .= wf_Link('?module=taskbar', __('Taskbar'), false);
        $fastaccData = self::CUSTOMS_PATH . $this->myLogin . '.fastaccdata';
        if (file_exists($fastaccData)) {
            $this->menuCodeFA .= file_get_contents($fastaccData);
        }
    }

    /**
     * Returns raw fast acces menu code 
     * 
     * @return string
     */
    public function renderFastAccessMenu() {
        $this->loadFastAccesMenu();
        return ($this->menuCodeFA);
    }

}

?>

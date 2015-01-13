<?php

class GlobalMenu {

    protected $rawData = array();
    protected $menuData = array();
    protected $categories = array();
    protected $altCfg = array();
    protected $iconsPath = 'skins/menuicons/';
    protected $menuCode = '';

    const DEFAULT_ICON = 'defaulticon.png';

    public function __construct() {
        $this->loadConfig();
        $this->loadData();
        $this->extractCategories();
        $this->parseData();
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
                    $this->menuData[$each['CATEGORY']].=wf_tag('li', false) . wf_Link($each['URL'], wf_img($icon) . ' ' . $name, false) . wf_tag('li', true);
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
                    $this->menuCode.=wf_tag('h3', false) . __($eachCategoryName) . wf_tag('h3', true);
                    $this->menuCode.= wf_tag('ul', false, 'toggleGMENU');
                    $this->menuCode.=$this->menuData[$eachCategoryId];
                    $this->menuCode.= wf_tag('ul', true);
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

}

?>

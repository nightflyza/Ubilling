<?php

class UbillingTaskbar {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains system billing config as key=>value
     *
     * @var array
     */
    protected $billCfg = array();

    /**
     * Message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains currently loaded categories as dir=>name
     *
     * @var array
     */
    protected $categories = array();

    /**
     * Contains available icon sizes as size=>name
     *
     * @var array
     */
    protected $iconSizes = array();

    /**
     * Contains current run alerts if available
     *
     * @var string
     */
    protected $currentAlerts = '';

    /**
     * Contains full list of loaded taskbar elements
     *
     * @var array
     */
    protected $loadedElements = array();

    /**
     * Taskbar elements rendered content
     *
     * @var string
     */
    protected $taskbarContent = '';

    /**
     * Contains default taskbar elements path
     */
    const BASE_PATH = 'config/taskbar.d/';

    /**
     * Contains path to widgets code
     */
    const WIDGETS_CODEPATH = 'config/taskbar.d/widgets/';

    /**
     * Contains default module URL
     */
    const URL_ME = '?module=taskbar';

    /**
     * Creates new taskbar instance
     */
    public function __construct() {
        $this->loadConfigs();
        $this->initMessages();
        $this->setCategories();
        $this->setIconSizes();
    }

    /**
     * Loads system alter and billing configs into protected properties
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
    }

    /**
     * Inits system message helper object
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets available taskbar element categories
     * 
     * @return void
     */
    protected function setCategories() {
        $this->categories['widgets'] = '';
        $this->categories['iusers'] = __('Internet users');
        $this->categories['directories'] = __('Directories');
        $this->categories['reports'] = __('Reports');
        $this->categories['system'] = __('System');
    }

    /**
     * Sets available icon sizes
     * 
     * @return void
     */
    protected function setIconSizes() {
        $this->iconSizes = array(
            '128' => __('Normal icons'),
            '96' => __('Lesser'),
            '64' => __('Macro'),
            '48' => __('Micro'),
            '32' => __('Nano')
        );
    }

    /**
     * Sets current administrators login into protected prof for further usage
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Renders taskbar icon element
     * 
     * @param string $url
     * @param string $name
     * @param string $icon
     * 
     * @return string
     */
    protected function renderIconElement($url, $elementName, $elementIcon) {
        $result = '';
        $name = __($elementName);
        $iconPath = CUR_SKIN_PATH . 'taskbar/';
        $icon = $iconPath . $elementIcon;
        if (!file_exists($icon)) {
            $icon = 'skins/taskbar/' . $elementIcon;
        }

        if (isset($_COOKIE['tb_iconsize'])) {
            //is icon customize enabled?
            if ($this->altCfg['TB_ICONCUSTOMSIZE']) {
                $iconsize = vf($_COOKIE['tb_iconsize'], 3);
            } else {
                $iconsize = $this->billCfg['TASKBAR_ICON_SIZE'];
            }
        } else {
            $iconsize = $this->billCfg['TASKBAR_ICON_SIZE'];
        }

        if ($this->altCfg['TB_LABELED']) {
            if ($iconsize > 63) {
                $result = '<div class="dashtask" style="height:' . ($iconsize + 30) . 'px; width:' . ($iconsize + 30) . 'px;"> <a href="' . $url . '"><img  src="' . $icon . '" border="0" width="' . $iconsize . '"  height="' . $iconsize . '" alt="' . $name . '" title="' . $name . '"></a> <br><br>' . $name . ' </div>';
            } else {
                $result = '<div class="dashtask" style="height:' . ($iconsize + 10) . 'px; width:' . ($iconsize + 10) . 'px;"> <a href="' . $url . '"><img  src="' . $icon . '" border="0" width="' . $iconsize . '"  height="' . $iconsize . '" alt="' . $name . '" title="' . $name . '"></a></div>';
            }
        } else {
            $result = '<a href="' . $url . '"><img  src="' . $icon . '" border="0" width="' . $iconsize . '"  height="' . $tbiconsize . '" alt="' . $name . '" title="' . $name . '"></a><img src="' . $icon . 'spacer.gif">  ';
        }

        return ($result);
    }

    /**
     * Checks element required rights, options and returns element content
     * 
     * @param array $elementData
     * 
     * @return string
     */
    protected function buildElement($elementData) {
        $result = '';
        $elementId = (isset($elementData['ID'])) ? $elementData['ID'] : '';
        $elementType = (!empty($elementData['TYPE'])) ? $elementData['TYPE'] : '';
        //basic taskbar icon
        if ($elementType == 'icon') {
            $accesCheck = false;
            $elementRight = (!empty($elementData['NEED_RIGHT'])) ? $elementData['NEED_RIGHT'] : '';
            if (!empty($elementRight)) {
                if (cfr($elementRight)) {
                    $accesCheck = true;
                }
            } else {
                $accesCheck = true;
            }
            //basic rights check
            if ($accesCheck) {
                $elementOption = (!empty($elementData['NEED_OPTION'])) ? $elementData['NEED_OPTION'] : '';
                $optionCheck = false;
                if (!empty($elementOption)) {
                    if (isset($this->altCfg[$elementOption])) {
                        if ($this->altCfg[$elementOption]) {
                            $optionCheck = true;
                        }
                    } else {
                        if (!isset($elementData['UNIMPORTANT'])) {
                            $this->currentAlerts.=$this->messages->getStyledMessage(__('Missed config option') . ': ' . $elementOption . ' ' . __('required by') . ' ' . $elementId, 'error');
                        }
                    }
                } else {
                    $optionCheck = true;
                }

                if ($optionCheck) {
                    $elementName = (!empty($elementData['NAME'])) ? $elementData['NAME'] : '';
                    $elementUrl = (!empty($elementData['URL'])) ? $elementData['URL'] : '';
                    $elementIcon = (!empty($elementData['ICON'])) ? $elementData['ICON'] : '';
                    $result.=$this->renderIconElement($elementUrl, $elementName, $elementIcon);
                }
            }
        }

        //widgets loading
        if ($elementType == 'widget') {
            $accesCheck = false;
            $elementRight = (!empty($elementData['NEED_RIGHT'])) ? $elementData['NEED_RIGHT'] : '';
            if (!empty($elementRight)) {
                if (cfr($elementRight)) {
                    $accesCheck = true;
                }
            } else {
                $accesCheck = true;
            }
            //basic rights check
            if ($accesCheck) {
                $elementOption = (!empty($elementData['NEED_OPTION'])) ? $elementData['NEED_OPTION'] : '';
                $optionCheck = false;
                if (!empty($elementOption)) {
                    if (isset($this->altCfg[$elementOption])) {
                        if ($this->altCfg[$elementOption]) {
                            $optionCheck = true;
                        }
                    } else {
                        if (!isset($elementData['UNIMPORTANT'])) {
                            $this->currentAlerts.=$this->messages->getStyledMessage(__('Missed config option') . ': ' . $elementOption . ' ' . __('required by') . ' ' . $elementId, 'error');
                        }
                    }
                } else {
                    $optionCheck = true;
                }

                /**
                 * It's gonna take a lot to drag me away from you
                 * There's nothing that a hundred men or more could ever do
                 * I bless the rains down in Africa
                 * Gonna take some time to do the things we never had
                 */
                if ($optionCheck) {
                    //run widget code
                    if (isset($elementData['CODEFILE'])) {
                        if (file_exists(self::WIDGETS_CODEPATH . $elementData['CODEFILE'])) {
                            require_once (self::WIDGETS_CODEPATH . $elementData['CODEFILE']);
                            if (class_exists($elementData['ID'])) {
                                $widget = new $elementData['ID']();
                                $result.=$widget->render();
                            } else {
                                $this->currentAlerts.=$this->messages->getStyledMessage(__('Widget class not exists') . ': ' . $elementData['ID'], 'error');
                            }
                        } else {
                            $this->currentAlerts.=$this->messages->getStyledMessage(__('File not exist') . ': ' . self::WIDGETS_CODEPATH . $elementData['CODEFILE'], 'warning');
                        }
                    } else {
                        $this->currentAlerts.=$this->messages->getStyledMessage(__('Wrong element format') . ': ' . $elementData['ID'], 'warning');
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Loads and returns category taskbar elements
     * 
     * @param string $category
     * 
     * @return string
     */
    protected function loadCategoryElements($category) {
        $result = '';
        $elementsPath = self::BASE_PATH . $category . '/';
        $allElements = rcms_scandir($elementsPath, '*.ini');

        if (!empty($allElements)) {
            $categoryName = (isset($this->categories[$category])) ? $this->categories[$category] : '';

            $result.=wf_tag('p') . wf_tag('h3') . wf_tag('u') . $categoryName . wf_tag('u', true) . wf_tag('h3', true) . wf_tag('p', true);
            $result.= wf_tag('div', false, 'dashboard');
            foreach ($allElements as $io => $eachfilename) {
                $elementData = rcms_parse_ini_file($elementsPath . $eachfilename);
                if ((isset($elementData['TYPE'])) AND ( isset($elementData['ID']))) {
                    if (!isset($this->loadedElements[$elementData['ID']])) {
                        $this->loadedElements[$elementData['ID']] = $elementData;
                        $result.=$this->buildElement($elementData);
                    } else {
                        $this->currentAlerts.=$this->messages->getStyledMessage(__('Duplicate element ID') . ': ' . $elementData['ID'] . ' -> ' . $eachfilename, 'warning');
                    }
                } else {
                    $this->currentAlerts.=$this->messages->getStyledMessage(__('Wrong element format') . ': ' . $eachfilename, 'warning');
                }
            }
            $result.= wf_tag('div', true);
            $result.= wf_tag('div', false, '', 'style="clear:both"') . wf_tag('div', true);
        }

        return ($result);
    }

    /**
     * Loads and try to render all of available taskbar categories
     * 
     * @return string
     */
    protected function loadAllCategories() {
        $result = '';
        if (!empty($this->categories)) {
            foreach ($this->categories as $category => $categoryname) {
                $result.=$this->loadCategoryElements($category);
            }
        }
        return ($result);
    }

    /**
     * Returns icon resize form if enabled
     * 
     * @return string
     */
    protected function renderResizeForm() {
        $result = '';
        if ($this->altCfg['TB_ICONCUSTOMSIZE']) {
            if (isset($_COOKIE['tb_iconsize'])) {
                $currentsize = vf($_COOKIE['tb_iconsize'], 3);
            } else {
                $currentsize = $this->billCfg['TASKBAR_ICON_SIZE'];
            }
            $resizeinputs = wf_SelectorAC('iconsize', $this->iconSizes, '', $currentsize, false);

            $result.= wf_tag('br');
            $result.= wf_Form('', 'POST', $resizeinputs);
        }

        return ($result);
    }

    /**
     * Catches and applies icon resize event
     * 
     * @return void
     */
    protected function catchIconsizeChange() {
        if (isset($_POST['iconsize'])) {
            $iconsize = vf($_POST['iconsize'], 3);
            setcookie("tb_iconsize", $iconsize, time() + 2592000);
            rcms_redirect(self::URL_ME);
        }
    }

    /**
     * Renders instant messenger notification
     * 
     * @return void
     */
    protected function loadUbim() {
        //refresh IM container with notify
        if ($this->altCfg['TB_UBIM']) {
            if ($this->altCfg['TB_UBIM_REFRESH']) {
                if (cfr('UBIM')) {
                    im_RefreshContainer($this->altCfg['TB_UBIM_REFRESH']);
                }
            }
        }
    }

    /**
     * Returs available sticky notes if enabled
     * 
     * @return string
     */
    protected function loadStickyNotes() {
        $result = '';
        if (isset($this->altCfg['STICKY_NOTES_ENABLED'])) {
            if ($this->altCfg['STICKY_NOTES_ENABLED']) {
                $stickyNotes = new StickyNotes(true);
                $result = $stickyNotes->renderTaskbarNotify();
            }
        }
        return ($result);
    }

    /**
     * Renders administrators announcements if some unread is present/sets read some of them
     * 
     * @return string
     */
    protected function loadAnnouncements() {
        $result = '';
        if (isset($this->altCfg['ANNOUNCEMENTS'])) {
            if ($this->altCfg['ANNOUNCEMENTS']) {
                $admAnnouncements = new AdminAnnouncements();
                if (wf_CheckGet(array('setacquainted'))) {
                    $admAnnouncements->setAcquainted($_GET['setacquainted']);
                    rcms_redirect(self::URL_ME);
                }
                $result.=$admAnnouncements->showAnnouncements();
            }
        }
        return ($result);
    }

    /**
     * Returns rendered taskbar elements and services content
     * 
     * @return string
     */
    public function renderTaskbar() {
        $result = '';
        $this->catchIconsizeChange();
        $this->taskbarContent = $this->loadAllCategories();
        if (!empty($this->currentAlerts)) {
            $result.=$this->currentAlerts;
        }
        $result.=$this->taskbarContent;
        $result.=$this->renderResizeForm();
        $result.=$this->loadStickyNotes();
        $result.=$this->loadAnnouncements();
        $this->loadUbim();
        return ($result);
    }

}

/**
 * Basic widgets class.
 */
class TaskbarWidget {

    /**
     * Creates new instance of taskbar widget
     */
    public function __construct() {
        
    }

    /**
     * Returns content in default taskbar dashtask coontainer
     * 
     * @param string $content
     * @param string $options
     * 
     * @return string
     */
    protected function widgetContainer($content, $options = '') {
        $result = wf_tag('div', false, 'dashtask', $options);
        $result.= $content;
        $result.=wf_tag('div', true);
        return ($result);
    }

    /**
     * Returns result that directly embeds into taskbar
     * 
     * @return string
     */
    public function render() {
        $result = 'EMPTY_WIDGET';
        return ($result);
    }

}

?>
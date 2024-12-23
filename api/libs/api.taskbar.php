<?php

/**
 * Taskbar loading and rendering class
 */
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
     * Contains current instance administrator login
     *
     * @var string
     */
    protected $myLogin = '';

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
     * Some other predefined stuff
     */
    const ROUTE_WS = 'welcomescreen';
    const ROUTE_DISABLE_WS = 'disablewelcomescreen';

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
        $this->categories['iusers'] = __('Subscribers');
        $this->categories['instruments'] = __('Instruments');
        $this->categories['equipment'] = __('Equipment');
        $this->categories['maps'] = __('Maps');
        $this->categories['reports'] = __('Reports');
        $this->categories['directories'] = __('Directories');
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
     * @param string $elementName
     * @param string $elementIcon
     * @param string $elementTarget
     * 
     * @return string
     */
    protected function renderIconElement($url, $elementName, $elementIcon, $elementTarget = '') {
        $result = '';
        $name = __($elementName);
        $iconPath = CUR_SKIN_PATH . 'taskbar/';
        $icon = $iconPath . $elementIcon;
        $elemQsId = 'ubtbelcont_' . $name . '_' . $elementName;
        $linkOpts = '';
        if (!empty($elementTarget)) {
            $linkOpts .= 'target="' . $elementTarget . '"';
        }
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
                $result = '<div class="dashtask" id="' . $elemQsId . '" style="height:' . ($iconsize + 30) . 'px; width:' . ($iconsize + 30) . 'px;"> <a href="' . $url . '" ' . $linkOpts . '><img  src="' . $icon . '" border="0" width="' . $iconsize . '"  height="' . $iconsize . '" alt="' . $name . '" title="' . $name . '"></a> <br><br>' . $name . ' </div>';
            } else {
                $result = '<div class="dashtask" id="' . $elemQsId . '" style="height:' . ($iconsize + 10) . 'px; width:' . ($iconsize + 10) . 'px;"> <a href="' . $url . '" ' . $linkOpts . '><img  src="' . $icon . '" border="0" width="' . $iconsize . '"  height="' . $iconsize . '" alt="' . $name . '" title="' . $name . '"></a></div>';
            }
        } else {
            $result = '<a href="' . $url . '" ' . $linkOpts . '><img  src="' . $icon . '" border="0" width="' . $iconsize . '"  height="' . $iconsize . '" alt="' . $name . '" title="' . $name . '"></a><img src="skins/taskbar/spacer.gif">  ';
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
                            $this->currentAlerts .= $this->messages->getStyledMessage(__('Missed config option') . ': ' . $elementOption . ' ' . __('required by') . ' ' . $elementId, 'error');
                        }
                    }
                } else {
                    $optionCheck = true;
                }

                if ($optionCheck) {
                    $elementName = (!empty($elementData['NAME'])) ? $elementData['NAME'] : '';
                    $elementUrl = (!empty($elementData['URL'])) ? $elementData['URL'] : '';
                    $elementIcon = (!empty($elementData['ICON'])) ? $elementData['ICON'] : '';
                    $elementTarget = (!empty($elementData['LINK_TARGET'])) ? $elementData['LINK_TARGET'] : '';
                    $result .= $this->renderIconElement($elementUrl, $elementName, $elementIcon, $elementTarget);
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
                            $this->currentAlerts .= $this->messages->getStyledMessage(__('Missed config option') . ': ' . $elementOption . ' ' . __('required by') . ' ' . $elementId, 'error');
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
                            require_once(self::WIDGETS_CODEPATH . $elementData['CODEFILE']);
                            if (class_exists($elementData['ID'])) {
                                $widget = new $elementData['ID']();
                                $result .= $widget->render();
                            } else {
                                $this->currentAlerts .= $this->messages->getStyledMessage(__('Widget class not exists') . ': ' . $elementData['ID'], 'error');
                            }
                        } else {
                            $this->currentAlerts .= $this->messages->getStyledMessage(__('File not exist') . ': ' . self::WIDGETS_CODEPATH . $elementData['CODEFILE'], 'warning');
                        }
                    } else {
                        $this->currentAlerts .= $this->messages->getStyledMessage(__('Wrong element format') . ': ' . $elementData['ID'], 'warning');
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
        $categoryContent = '';
        if (!empty($allElements)) {
            $categoryName = (isset($this->categories[$category])) ? $this->categories[$category] : '';
            foreach ($allElements as $io => $eachfilename) {
                $elementData = parse_ini_file($elementsPath . $eachfilename);
                if ((isset($elementData['TYPE'])) and (isset($elementData['ID']))) {
                    if (!isset($this->loadedElements[$elementData['ID']])) {
                        $this->loadedElements[$elementData['ID']] = $elementData;
                        $categoryContent .= $this->buildElement($elementData);
                    } else {
                        $this->currentAlerts .= $this->messages->getStyledMessage(__('Duplicate element ID') . ': ' . $elementData['ID'] . ' -> ' . $eachfilename, 'warning');
                    }
                } else {
                    $this->currentAlerts .= $this->messages->getStyledMessage(__('Wrong element format') . ': ' . $eachfilename, 'warning');
                }
            }

            //injecting optional ReportMaster reports here
            if ($category == 'reports') {
                if (@$this->altCfg['TB_REPORTMASTER']) {
                    $reportMaster = new ReportMaster();
                    $availableReports = $reportMaster->getTaskBarReports();
                    if (!empty($availableReports)) {
                        foreach ($availableReports as $eachReportId => $eachReportElement) {
                            $categoryContent .= $this->buildElement($eachReportElement);
                        }
                    }
                }
            }

            if (!empty($categoryContent)) {
                $result .= wf_tag('p') . wf_tag('h3') . wf_tag('u') . $categoryName . wf_tag('u', true) . wf_tag('h3', true) . wf_tag('p', true);
                $result .= wf_tag('div', false, 'dashboard');
                $result .= $categoryContent;
                $result .= wf_tag('div', true);
                $result .= wf_CleanDiv();
            }
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
                $result .= $this->loadCategoryElements($category);
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

            $result .= wf_tag('br');
            $result .= wf_Form('', 'POST', $resizeinputs);
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
     * Checks for default password usage, etc.
     * 
     * @return void
     */
    protected function checkSecurity() {
        if (@!$this->altCfg['TB_DISABLE_SECURITY_CHECK']) {
            global $system;
            $controlLogin = 'admin';
            $badPasswords = file_get_contents(DATA_PATH . 'shitpass.dat');
            $badPasswords = trim($badPasswords);
            $badPasswords = explodeRows($badPasswords);
            $defaultPassOffset = 0;

            if (isset($_COOKIE['ubilling_user'])) {
                if (!file_exists('DEMO_MODE') and !file_exists('exports/FIRST_INSTALL')) {
                    //am i using default account?
                    if ($_COOKIE['ubilling_user'] == $controlLogin . ':' . $badPasswords[$defaultPassOffset]) {
                        $notice = __('You are using the default login and password') . '. ' . __('Dont do this') . '.';
                        // ugly hack to prevent elements autofocusing
                        $label = wf_TextInput('dontfocusonlinks', '', '', false, '', '', '', '', 'style="width: 0; height: 0; top: -100px; position: absolute;"');
                        $label .= wf_tag('div', false, '', 'style="min-width:550px;"') . $this->messages->getStyledMessage($notice, 'error') . wf_tag('div', true);
                        $label .= wf_tag('br');
                        $imagesPath = 'skins/changepass/';
                        $allImgs = rcms_scandir($imagesPath);
                        if (!empty($allImgs)) {
                            $imageRnd = array_rand($allImgs);
                            $randomImage = $allImgs[$imageRnd];
                            $label .= wf_tag('center') . wf_img_sized($imagesPath . $randomImage, '', '', '300') . wf_tag('center' . true);
                            $label .= wf_delimiter(1);
                        }

                        $label .= wf_Link('?module=adminreg&editadministrator=admin', __('Change admin user password'), true, 'confirmagree');
                        $this->currentAlerts .= wf_modalOpenedAuto(__('Oh no') . '!' . ' ' . __('Danger') . '!', $label);
                    } else {
                        //fast check for few shitty passwords
                        if (file_exists(USERS_PATH . $controlLogin)) {
                            $adminData = $system->getUserData($controlLogin);
                            if (!empty($adminData)) {
                                $adminHash = trim($adminData['password']);
                                foreach ($badPasswords as $passIdx => $eachHash) {
                                    if (!empty($eachHash)) {
                                        $eachHash = trim($eachHash);
                                        if (strpos($adminHash, $eachHash) !== false) {
                                            $this->currentAlerts .= $this->messages->getStyledMessage(__('For administrator') . ' «' . $controlLogin . '» ' . __('a very fucked up password is used') . '. ' . __('Dont do this') . '.', 'error');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders some welcome screen for newly installed Ubilling
     * 
     * @return void
     */
    protected function renderWelcome() {
        if (@!$this->altCfg['TB_DISABLE_WELCOME_SCREEN']) {
            $newInstallFlag = 'exports/FIRST_INSTALL';
            if (!ubRouting::checkGet(self::ROUTE_DISABLE_WS)) {
                if (file_exists($newInstallFlag) or ubRouting::checkGet(self::ROUTE_WS)) {
                    $urlsList = array(
                        'https://ubilling.net.ua/rds/defense/' => __('Donate to Armed Forces of Ukraine'),
                        'https://wiki.ubilling.net.ua/' => __('Read documentation'),
                        '?module=adminreg&editadministrator=admin' => __('Change admin user password'),
                        'https://t.me/ubilling' => __('Join our community chat'),
                    );

                    //render content
                    $welcomeLabel = '<!--ugly hack to prevent elements autofocusing --> <input type="text" name="dontfocusonlinks" style="width: 0; height: 0; top: -100px; position: absolute;"/>';
                    $welcomeLabel .= wf_tag('h2') . __('Welcome to your new billing system') . '!' . wf_tag('h2', true);
                    $welcomeLabel .= __('On behalf of the development team and everyone involved in the project, we would like to thank you for choosing Ubilling.');
                    $welcomeLabel .= wf_tag('br');
                    $welcomeLabel .= __('We hope you enjoy using it as much as we enjoyed working on it.');
                    $welcomeLabel .= wf_delimiter(1);
                    $welcomeLabel .= __('Here`s what you should do first') . ':';

                    if (!empty($urlsList)) {
                        $welcomeLabel .= wf_tag('ul');
                        foreach ($urlsList as $eachUrl => $eachLabel) {
                            $welcomeLabel .= wf_tag('li') . wf_Link($eachUrl, $eachLabel, false, '', 'target="_BLANK"') . wf_tag('li', true);
                        }
                        $welcomeLabel .= wf_tag('ul', true);
                    }

                    if (file_exists($newInstallFlag)) {
                        $welcomeLabel .= wf_Link(self::URL_ME . '&' . self::ROUTE_DISABLE_WS . '=true', wf_img('skins/hide16.png') . ' ' . __('Dont show me this anymore'), true, 'ubButton');
                    }

                    $this->currentAlerts .= wf_modalOpenedAuto(__('Welcome to Ubilling') . '!', $welcomeLabel);
                }
            } else {
                @unlink($newInstallFlag);
                if (file_exists($newInstallFlag)) {
                    log_register('WELCOME DISABLE FAIL');
                }
                ubRouting::nav(self::URL_ME);
            }
        }
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
                $result .= $admAnnouncements->showAnnouncements();
            }
        }
        return ($result);
    }

    /**
     * Renders administrators voting poll form
     * 
     * @return string
     */
    protected function loadPollVoteAdmin() {
        $result = '';
        if (isset($this->altCfg['POLLS_ENABLED'])) {
            if ($this->altCfg['POLLS_ENABLED']) {
                $poll = new PollVoteAdmin();
                if (wf_CheckPost(array('vote', 'poll_id'))) {
                    $poll->createAdminVoteOnDB(vf($_POST['vote'], 3), vf($_POST['poll_id'], 3));
                }
                $result .= $poll->renderVotingForm();
            }
        }
        return ($result);
    }

    /**
     * Returns touch devices hotfix for draggable and other JQuery UI things
     * 
     * @return string
     */
    protected function loadTouchFix() {
        $result = '';
        if (@$this->altCfg['TOUCH_FIX']) {
            $result .= '<!-- jQuery UI Touch Punch -->';
            $result .= wf_tag('script', false, '', 'type="text/javascript" language="javascript" src="modules/jsc/jquery.ui.touch-punch.min.js"');
            $result .= wf_tag('script', true);
        }
        return ($result);
    }

    /**
     * Renders the search form and frontend controller for taskbar elements
     *
     * @return string
     */
    public function renderQuickSearchForm() {
        global $ubillingConfig;
        $result = '';
        if (@$this->altCfg['TB_QUICKSEARCH_ENABLED']) {
            $result .= wf_tag('div', false, 'tbqsearchform');
            $result .= wf_TextInput('tbquicksearch', ' ' . '', '', false, 20, '', '', 'tbquicksearch', 'placeholder="' . __('Quick search') . '...' . '"');

            $result .= wf_tag('button', false, 'clear-btn', 'type="button" aria-label="Clear search"') . '&times;' . wf_tag('button', true);
            $result .= wf_tag('div', true);

            $result .= wf_tag('script');
            $result .= "
                    document.getElementById('tbquicksearch').addEventListener('input', function () {
                        const searchValue = this.value.toLowerCase();
                        const tbElements = document.querySelectorAll('[id^=\"ubtbelcont_\"]');
                        const statusContainer = document.getElementById('ubtbqsstatus');
                        let visibleCount = 0;
                
                        tbElements.forEach(tbElement => {
                            const idText = tbElement.id.toLowerCase();
                            if (searchValue === '' || idText.includes(searchValue)) {
                                tbElement.classList.remove('hiddentbelem');
                                tbElement.style.display = 'block';
                                requestAnimationFrame(() => tbElement.style.opacity = '1');
                                visibleCount++;
                            } else {
                                tbElement.classList.add('hiddentbelem');
                                setTimeout(() => {
                                    if (tbElement.classList.contains('hiddentbelem')) {
                                        tbElement.style.display = 'none';
                                    }
                                }, 300);
                            }
                        });
                
                        //no elements found
                        if (visibleCount === 0) {
                            statusContainer.textContent = '" . __('Nothing found') . "';
                        } else {
                            statusContainer.textContent = '';
                        }
                    });

                    document.addEventListener('DOMContentLoaded', () => {
                        const searchInput = document.getElementById('tbquicksearch');
                        const clearButton = document.querySelector('.clear-btn');
                        searchInput.addEventListener('input', () => {
                            if (searchInput.value.trim() !== '') {
                                clearButton.style.display = 'flex';
                            } else {
                                clearButton.style.display = 'none';
                            }
                        });

                        clearButton.addEventListener('click', () => {
                            searchInput.value = '';
                            clearButton.style.display = 'none';
                            searchInput.dispatchEvent(new Event('input'));
                            searchInput.focus();
                        });
                    });
                ";
            $result .= wf_tag('script', true);


            $result .= wf_CleanDiv();
        }
        return ($result);
    }

    /**
     * Renders quick search form as modal dialog
     *
     * @return string
     */
    public function renderQuickSearchModal() {
        $result = '';
        if (@$this->altCfg['TB_QUICKSEARCH_ENABLED'] and !@$this->altCfg['TB_QUICKSEARCH_INLINE']) {
            $result .= ' ' . wf_modalAuto(web_icon_search(), __('Search'), $this->renderQuickSearchForm());
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
        $this->checkSecurity();
        $this->renderWelcome();
        $this->catchIconsizeChange();
        $this->taskbarContent = $this->loadAllCategories();
        if (!empty($this->currentAlerts)) {
            $result .= $this->currentAlerts;
        }

        if (@$this->altCfg['TB_QUICKSEARCH_INLINE']) {
            $result .= $this->renderQuickSearchForm();
        }
        $result .= wf_AjaxContainer('ubtbqsstatus');
        $result .= $this->taskbarContent;
        $result .= $this->renderResizeForm();
        $result .= $this->loadStickyNotes();
        $result .= $this->loadAnnouncements();
        $result .= $this->loadPollVoteAdmin();
        $result .= $this->loadTouchFix();
        return ($result);
    }
}

/**
 * Basic taskbar widgets class.
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
        $result .= $content;
        $result .= wf_tag('div', true);
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

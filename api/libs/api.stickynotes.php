<?php

/**
 * Administator reminders and notes implementation
 */
class StickyNotes {

    /**
     * Contains available user notes
     *
     * @var array
     */
    protected $allnotes = array();

    /**
     * Contains all available revelations
     *
     * @var array
     */
    protected $allRevelations = array();

    /**
     * Is revelations enabled?
     *
     * @var bool
     */
    protected $revelationsFlag = false;

    /**
     * Contains active user notes which may require notification
     *
     * @var array
     */
    protected $activenotes = array();

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Just a flag of preview functionality in notes grid list
     *
     * @var bool
     */
    protected $notesPreview = true;

    /**
     * Default taskbar notifications caching timeout in seconds
     */
    const CACHE_TIMEOUT = 3600;

    /**
     * Default cache key name for personal notes
     */
    const CACHE_KEY_NOTES = 'STICKYNOTES';

    /**
     * Default cache key name for revelations
     */
    const CACHE_KEY_REVELATIONS = 'STICKYREVELATIONS';

    /**
     * Default notes management module URL
     */
    const URL_ME = '?module=stickynotes';

    /**
     * Default revelations management module URL
     */
    const URL_REVELATIONS = '?module=stickynotes&revelations=true';

    /**
     * Sets max uncutted note text size
     */
    const PREVIEW_LEN = 190;

    /**
     * Routes and other predefined stuff
     */
    const ROUTE_CALENDAR = 'calendarview';
    const ROUTE_BACK = 'backurl';
    const ROUTE_SHOW_NOTE = 'shownote';
    const ROUTE_EDIT_FORM = 'editform';
    const ROUTE_DEL_NOTE = 'delete';
    const PROUTE_NEW_NOTE = 'newtext';
    const PROUTE_EDIT_NOTE_TEXT = 'edittext';
    const PROUTE_EDIT_NOTE_ID = 'editnoteid';
    const PROUTE_NEW_REVELATION = 'newrevelationtext';
    const ROUTE_DEL_REV = 'deleterev';
    const PROUTE_EDIT_REV_TEXT = 'editrevelationtext';
    const PROUTE_EDIT_REV_ID = 'editrevelationid';
    const ROUTE_EDIT_REV_FORM = 'editrev';
    const ROUTE_REVELATIONS = 'revelations';
    const PROUTE_REMIND_DATE = 'reminddate';
    const PROUTE_REMIND_TIME = 'remindtime';

    /**
     * Creates new sticky notes instance
     * 
     * @param bool $onlyActive
     * 
     * @return void
     */
    public function __construct($onlyActive) {
        $this->setLogin();
        $this->loadConfig();
        $this->initCache();
        if ($onlyActive) {
            $this->loadActiveNotes();
            if ($this->revelationsFlag) {
                $this->loadRevelations(true);
            }
        } else {
            $this->loadAllNotes();
            if ($this->revelationsFlag) {
                $this->loadRevelations(false);
            }
        }
    }

    /**
     * Loads required options
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->revelationsFlag = $ubillingConfig->getAlterParam('STICKY_REVELATIONS_ENABLED');
        $noPreviewOption = $ubillingConfig->getAlterParam('STICKY_NOTES_NOPREVIEW');
        if ($noPreviewOption) {
            $this->notesPreview = false;
        }
    }

    /**
     * Sets current instance user login into protected property
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Inits system caching object instance
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Flushes cache keys on sticky notes or revelations changes
     * 
     * @return void
     */
    protected function flushCache() {
        $this->cache->delete(self::CACHE_KEY_NOTES);
        $this->cache->delete(self::CACHE_KEY_REVELATIONS);
    }

    /**
     * Loads notes from database into private property
     * 
     * @return void
     */
    protected function loadAllNotes() {
        $query = "SELECT * from `stickynotes` WHERE `owner`= '" . $this->myLogin . "' ORDER BY `id` DESC";
        $tmpArr = simple_queryall($query);
        //map id=>id
        if (!empty($tmpArr)) {
            foreach ($tmpArr as $io => $each) {
                $this->allnotes[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads active/remind notes from database into private property
     * 
     * @return void
     */
    protected function loadActiveNotes() {
        $cachedNotes = $this->cache->get(self::CACHE_KEY_NOTES, self::CACHE_TIMEOUT);
        if (empty($cachedNotes)) {
            $cachedNotes = array();
        }
        if (isset($cachedNotes[$this->myLogin])) {
            $this->activenotes = $cachedNotes[$this->myLogin];
        } else {
            $query = "SELECT * from `stickynotes` WHERE `owner`= '" . $this->myLogin . "' AND `active`='1' ORDER BY `id` ASC";
            $tmpArr = simple_queryall($query);
            //map id=>id
            if (!empty($tmpArr)) {
                foreach ($tmpArr as $io => $each) {
                    $this->activenotes[$each['id']] = $each;
                }
            }
            $cachedNotes[$this->myLogin] = $this->activenotes;
            $this->cache->set(self::CACHE_KEY_NOTES, $cachedNotes, self::CACHE_TIMEOUT);
        }
    }

    /**
     * Loads all revelations from database
     * 
     * @param bool $onlyForMe
     * 
     * @return void
     */
    protected function loadRevelations($onlyForMe = false) {
        if ($onlyForMe) {
            $cachedRevelations = $this->cache->get(self::CACHE_KEY_REVELATIONS, self::CACHE_TIMEOUT);
            if (empty($cachedRevelations)) {
                $cachedRevelations = array();
            }
            if (isset($cachedRevelations[$this->myLogin])) {
                $this->allRevelations = $cachedRevelations[$this->myLogin];
            } else {
                //yeah, logins must be space-separated
                $query = "SELECT * from `stickyrevelations` WHERE `showto` LIKE '% " . $this->myLogin . " %'  AND `active`='1' ORDER BY `id` ASC";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->allRevelations[$each['id']] = $each;
                    }
                }
                $cachedRevelations[$this->myLogin] = $this->allRevelations;
                $this->cache->set(self::CACHE_KEY_REVELATIONS, $cachedRevelations, self::CACHE_TIMEOUT);
            }
        } else {
            $query = "SELECT * from `stickyrevelations` ORDER BY `id` DESC";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->allRevelations[$each['id']] = $each;
                }
            }
        }
    }

    /**
     * Returns note data extracted from private allnotes property
     * 
     * @param int $noteId
     * 
     * @return array
     */
    public function getNoteData($noteId) {
        $result = array();
        if (!empty($this->allnotes)) {
            if (isset($this->allnotes[$noteId])) {
                $result = $this->allnotes[$noteId];
            }
        }
        return ($result);
    }

    /**
     * Returns revelation data
     * 
     * @param int $revelationId
     * 
     * @return array
     */
    protected function getRevelationData($revelationId) {
        $result = array();
        if (!empty($this->allRevelations)) {
            if (isset($this->allRevelations[$revelationId])) {
                $result = $this->allRevelations[$revelationId];
            }
        }
        return ($result);
    }

    /**
     * Returns cutted string if needed
     * 
     * @param string $string
     * @param int $size
     * 
     * @return string
     */
    protected function cutString($string, $size) {
        if ((mb_strlen($string, 'UTF-8') > $size)) {
            $string = mb_substr($string, 0, $size, 'utf-8') . '...';
        }
        return ($string);
    }

    /**
     * Returns list of available sticky notes with it controls as grid
     * 
     * @return string
     */
    public function renderListGrid() {
        $messages = new UbillingMessageHelper();
        $result = '';
        $cells = wf_TableCell(__('Creation date'));
        $cells .= wf_TableCell(__('Remind date'));
        $cells .= wf_TableCell(__('Time'));
        $cells .= wf_TableCell(__('Status'));
        $cells .= wf_TableCell(__('Text'));
        $cells .= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        /**
         * Amadare wa chi no shizuku to natte hoho wo
         * Tsutaiochiru
         * Mou doko ni mo kaeru basho ga nai nara
         */
        if (!empty($this->allnotes)) {
            foreach ($this->allnotes as $io => $each) {
                $cells = wf_TableCell($each['createdate']);
                $cells .= wf_TableCell($each['reminddate']);
                $cells .= wf_TableCell($each['remindtime']);
                $cells .= wf_TableCell(web_bool_led($each['active']), '', '', 'sorttable_customkey="' . $each['active'] . '"');
                $viewLink = wf_Link(self::URL_ME . '&shownote=' . $each['id'], $this->cutString(htmlentities($each['text'], ENT_COMPAT, "UTF-8"), 100), false, '');
                $cells .= wf_TableCell($viewLink);
                $actLinks = '';
                if ($this->notesPreview) {
                    //deletion dialog
                    $deletingPreview = nl2br($this->cutString(strip_tags($each['text']), 50));
                    $deletingPreview .= wf_delimiter();
                    $deletingPreview .= wf_JSAlert(self::URL_ME . '&delete=' . $each['id'], web_delete_icon() . ' ' . __('Delete'), $messages->getDeleteAlert(), '', 'ubButton') . ' ';
                    $deletingPreview .= wf_Link(self::URL_ME, wf_img('skins/back.png') . ' ' . __('Cancel'), false, 'ubButton');
                    $deletingDialog = wf_modalAuto(web_delete_icon(), __('Delete'), $deletingPreview);
                    $actLinks .= $deletingDialog;
                }
                //edit control
                $actLinks .= wf_Link(self::URL_ME . '&editform=' . $each['id'], web_edit_icon(), false) . ' ';
                if ($this->notesPreview) {
                    //preview dialog
                    $previewContent = nl2br($this->makeFullNoteLink($this->cutString(strip_tags(htmlentities($each['text'], ENT_COMPAT, "UTF-8")), self::PREVIEW_LEN), $each['id']));
                    $actLinks .= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $previewContent, '', '640', '480');
                }
                $cells .= wf_TableCell($actLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
        }

        $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * Returns available sticky notes list as full calendar
     * 
     * @return string
     */
    public function renderListCalendar() {
        $calendarData = '';
        if (!empty($this->allnotes)) {
            foreach ($this->allnotes as $io => $each) {
                $timestamp = strtotime($each['createdate']);
                $date = date("Y, n-1, j", $timestamp);
                $remindTime = '';

                if ($each['active'] == 1) {
                    $coloring = "className : 'undone',";
                } else {
                    $coloring = '';
                }

                if (!empty($each['reminddate'])) {
                    $remindtimestamp = strtotime($each['reminddate']);
                    $reminddate = date("Y, n-1, j", $remindtimestamp);
                    $remindTime = (!empty($each['remindtime'])) ? date("H:i", strtotime($each['remindtime'])) : '';
                    $textLenght = 48;
                } else {
                    $reminddate = $date;
                    $textLenght = 24;
                }

                $shortText = $each['text'];
                $shortText = str_replace("\n", '', $shortText);
                $shortText = str_replace("\r", '', $shortText);
                $shortText = str_replace("'", '`', $shortText);
                $shortText = strip_tags($shortText);
                $shortText = $this->cutString($shortText, $textLenght);

                $calendarData .= "
                      {
                        title: '" . $remindTime . " " . $shortText . " ',
                        url: '" . self::URL_ME . "&backurl=calendar&shownote=" . $each['id'] . "',
                        start: new Date(" . $reminddate . "),
                        end: new Date(" . $reminddate . "),
                       " . $coloring . "     
                   },
                    ";
            }
        }

        $result = wf_FullCalendar($calendarData);
        return ($result);
    }

    /**
     * Renders existing revelations list with some controls
     * 
     * @return string
     */
    public function renderRevelationsList() {
        $result = '';
        $messages = new UbillingMessageHelper();
        $daysOfWeek = daysOfWeek(true);
        if (!empty($this->allRevelations)) {
            $cells = wf_TableCell(__('Creation date'));
            $cells .= wf_TableCell(__('Day') . ' ' . __('From'));
            $cells .= wf_TableCell(__('Day') . ' ' . __('To'));
            $cells .= wf_TableCell(__('Day of week'));
            $cells .= wf_TableCell(__('Status'));
            $cells .= wf_TableCell(__('Text'));
            $cells .= wf_TableCell(__('Actions'));
            $rows = wf_TableRow($cells, 'row1');
            foreach ($this->allRevelations as $io => $each) {
                $cells = wf_TableCell($each['createdate']);
                $dayFrom = (!empty($each['dayfrom'])) ? $each['dayfrom'] : '';
                $dayTo = (!empty($each['dayto'])) ? $each['dayto'] : '';
                $dayWeek = (!empty($each['dayweek'])) ? $each['dayweek'] : 1488; //any day of week
                $cells .= wf_TableCell($dayFrom);
                $cells .= wf_TableCell($dayTo);
                $cells .= wf_TableCell($daysOfWeek[$dayWeek]);
                $cells .= wf_TableCell(web_bool_led($each['active']));
                $previewContent = nl2br($this->cutString(strip_tags($each['text']), self::PREVIEW_LEN));
                $cells .= wf_TableCell($previewContent);
                $actiLinks = wf_JSAlert(self::URL_REVELATIONS . '&deleterev=' . $each['id'], web_delete_icon(), $messages->getDeleteAlert()) . ' ';
                $actiLinks .= wf_Link(self::URL_REVELATIONS . '&editrev=' . $each['id'], web_edit_icon());
                $cells .= wf_TableCell($actiLinks);
                $rows .= wf_TableRow($cells, 'row5');
            }
            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'info');
        }
        return ($result);
    }

    /**
     * Creates new note in database
     * 

     * @param string  $remindDate
     * @param int     $activity
     * @param string  $text
     */
    protected function createNote($remindDate, $remindTime, $activity, $text) {
        $createDate = curdatetime();
        $owner = mysql_real_escape_string($this->myLogin);
        $text = strip_tags($text);
        $text = mysql_real_escape_string($text);
        $activity = vf($activity, 3);
        $createDate = mysql_real_escape_string($createDate);
        if (!empty($remindDate)) {
            $remindDate = "'" . mysql_real_escape_string($remindDate) . "'";
        } else {
            $remindDate = 'NULL';
        }

        if (!empty($remindTime)) {
            $remindTime = "'" . mysql_real_escape_string($remindTime) . "'";
        } else {
            $remindTime = 'NULL';
        }
        $query = "INSERT INTO `stickynotes` (`id`, `owner`, `createdate`, `reminddate`,`remindtime`, `active`, `text`) "
                . "VALUES (NULL, '" . $owner . "', '" . $createDate . "', " . $remindDate . ", " . $remindTime . " , '" . $activity . "', '" . $text . "');";
        nr_query($query);
        $this->flushCache();
    }

    /**
     * Creates new sticky revelation in database
     * 
     * @param int $dayFrom
     * @param int $dayTo
     * @param int $activity
     * @param string $text
     * @param string $showTo
     * @param int $dayWeek
     * 
     * @return void
     */
    protected function createRevelation($dayFrom, $dayTo, $activity, $text, $showTo, $dayWeek) {
        $owner = $this->myLogin;
        $createDate = curdatetime();
        $text = strip_tags($text);
        $text = mysql_real_escape_string($text);
        $activity = vf($activity, 3);

        if (!empty($dayFrom)) {
            $dayFrom = "'" . mysql_real_escape_string($dayFrom) . "'";
        } else {
            $dayFrom = 'NULL';
        }

        if (!empty($dayTo)) {
            $dayTo = "'" . mysql_real_escape_string($dayTo) . "'";
        } else {
            $dayTo = 'NULL';
        }

        if ($dayWeek == 1488) {
            $dayWeek = 0; //replace any day of week to zero
        }

        $query = "INSERT INTO `stickyrevelations` (`id`, `owner`, `showto`,`createdate`, `dayfrom`,`dayto`,`dayweek` ,`active`, `text`) "
                . "VALUES (NULL, '" . $owner . "', '" . $showTo . "' ,'" . $createDate . "', " . $dayFrom . ", " . $dayTo . " , '" . $dayWeek . "', '" . $activity . "', '" . $text . "');";
        nr_query($query);
        $this->flushCache();
    }

    /**
     * Renders notify container with some text inside
     * 
     * @param string $text
     * @param int $offsetLeft
     * @param bool $isRevelation
     * 
     * @return string
     */
    protected function renderStickyNote($text, $offsetLeft = 0, $isRevelation = false) {
        $result = '';
        if (!empty($text)) {
            if ($offsetLeft) {
                $offsetLeft = 35 + $offsetLeft . 'px';
                $dividedLeftOffset = vf($offsetLeft, 3) / 5;
                $roundedLeftOffset = round($dividedLeftOffset);
                $offsetTop = 25 + $roundedLeftOffset . 'px';
            } else {
                $offsetLeft = '35px';
                $offsetTop = '30px';
            }

            $result .= wf_tag('div', false, 'stickynote', 'style="margin:' . $offsetTop . ' ' . $offsetLeft . ' 20px 20px;"');
            if ($isRevelation) {
                $result .= wf_img('skins/pigeon_icon.png') . wf_tag('br');
            } else {
                $result .= wf_Link(self::URL_ME, wf_img('skins/pushpin.png'), false, '') . wf_tag('br');
            }
            $result .= wf_tag('div', false, 'stickynotetext');
            $result .= $text;
            $result .= wf_tag('div', true);
            $result .= wf_tag('div', true);
        }
        return ($result);
    }

    /**
     * Returns control panel
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        if (!wf_CheckGet(array('revelations'))) {
            $result .= wf_modalAuto(wf_img('skins/pushpin.png') . ' ' . __('Create new personal note'), __('Create new personal note'), $this->createForm(), 'ubButton');
            if (wf_CheckGet(array('calendarview'))) {
                $result .= wf_Link(self::URL_ME, wf_img('skins/icon_table.png') . ' ' . __('Grid view'), false, 'ubButton');
            } else {
                $result .= wf_Link(self::URL_ME . '&calendarview=true', wf_img('skins/icon_calendar.gif') . ' ' . __('As calendar'), false, 'ubButton');
            }

            if ($this->revelationsFlag) {
                if (cfr('REVELATIONS')) {
                    $result .= wf_link(self::URL_REVELATIONS, wf_img('skins/pigeon_icon.png') . ' ' . __('Revelations'), false, 'ubButton');
                }
            }
        } else {
            if (!wf_CheckGet(array('editrev'))) {
                if (ubRouting::get('backurl') == 'calendar') {
                    $result .= wf_BackLink(self::URL_ME . '&calendarview=true');
                } else {
                    $result .= wf_BackLink(self::URL_ME);
                }
            } else {
                $result .= wf_BackLink(self::URL_REVELATIONS);
            }
            if (cfr('REVELATIONS')) {
                $result .= wf_modalAuto(web_icon_create() . ' ' . __('Create'), __('Create'), $this->revelationCreateForm(), 'ubButton');
            }
        }


        return ($result);
    }

    /**
     * Returns note create form
     * 
     * @return string
     */
    protected function createForm() {
        $inputs = wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
        $inputs .= wf_TextArea('newtext', '', '', true, '50x15');
        $inputs .= wf_CheckInput('newactive', __('Create note as active'), true, true);
        $inputs .= wf_DatePickerPreset('newreminddate', '');
        $inputs .= wf_tag('label') . __('Remind only after this date') . wf_tag('label', true);
        $inputs .= wf_tag('br');
        $inputs .= wf_TimePickerPreset('newremindtime', '', __('Remind time'), false);
        $inputs .= wf_tag('br');
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');

        return ($result);
    }

    /**
     * Returns revelation creation form
     * 
     * @return string
     */
    protected function revelationCreateForm() {
        $daysWeek = daysOfWeek(true);
        $days = array('' => '-');
        for ($i = 1; $i <= 31; $i++) {
            $days[$i] = $i;
        }

        $alladmins = rcms_scandir(USERS_PATH);
        $adminNames = ts_GetAllEmployeeLoginsCached();
        $adminNames = unserialize($adminNames);

        $inputs = wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
        $inputs .= wf_TextArea('newrevelationtext', '', '', true, '50x15');
        $inputs .= wf_CheckInput('newrevelationactive', __('Active'), true, true);
        $inputs .= wf_tag('label') . __('Remind only between this days of month') . ' ' . wf_tag('label', true) . ' ';
        $inputs .= wf_Selector('newrevelationdayfrom', $days, __('From'), '', false) . ' ';
        $inputs .= wf_Selector('newrevelationdayto', $days, __('To'), '', true) . ' ';
        $inputs .= wf_Selector('newrevelationdayweek', $daysWeek, __('Day of week'), '', true);
        $inputs .= wf_tag('br');
        if (!empty($alladmins)) {
            foreach ($alladmins as $io => $eachAdmin) {
                $eachAdminName = (isset($adminNames[$eachAdmin])) ? $adminNames[$eachAdmin] : $eachAdmin;
                $inputs .= wf_CheckInput('newrevelationshowto[' . $eachAdmin . ']', $eachAdminName, false, false) . ' ' . wf_tag('br');
            }
        }
        $inputs .= wf_tag('br');
        $inputs .= wf_Submit(__('Create'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Returns revelation editing form
     * 
     * @return string
     */
    public function revelationEditForm($id) {
        $id = vf($id, 3);
        $result = '';
        $messages = new UbillingMessageHelper();

        if (isset($this->allRevelations[$id])) {
            $daysWeek = daysOfWeek(true);
            $days = array('' => '-');
            for ($i = 1; $i <= 31; $i++) {
                $days[$i] = $i;
            }

            $revData = $this->allRevelations[$id];
            $revDayWeek = (!empty($revData['dayweek'])) ? $revData['dayweek'] : 1488; //zero value in database

            $alladmins = rcms_scandir(USERS_PATH);
            $adminNames = ts_GetAllEmployeeLoginsCached();
            $adminNames = unserialize($adminNames);

            $inputs = wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
            $inputs .= wf_HiddenInput('editrevelationid', $id);
            $inputs .= wf_TextArea('editrevelationtext', '', $revData['text'], true, '50x15');
            $inputs .= wf_CheckInput('editrevelationactive', __('Active'), true, $revData['active']);
            $inputs .= wf_tag('label') . __('Remind only between this days of month') . ' ' . wf_tag('label', true) . ' ';
            $inputs .= wf_Selector('editrevelationdayfrom', $days, __('From'), $revData['dayfrom'], false) . ' ';
            $inputs .= wf_Selector('editrevelationdayto', $days, __('To'), $revData['dayto'], true) . ' ';
            $inputs .= wf_Selector('editrevelationdayweek', $daysWeek, __('Day of week'), $revData['dayweek'], true);
            $inputs .= wf_tag('br');
            if (!empty($alladmins)) {
                foreach ($alladmins as $io => $eachAdmin) {
                    $stateFlag = (ispos($revData['showto'], ' ' . $eachAdmin . ' ')) ? true : false;
                    $eachAdminName = (isset($adminNames[$eachAdmin])) ? $adminNames[$eachAdmin] : $eachAdmin;
                    $inputs .= wf_CheckInput('editrevelationshowto[' . $eachAdmin . ']', $eachAdminName, false, $stateFlag) . ' ';
                }
            }
            $inputs .= wf_tag('br');
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Save'));

            $result .= wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result .= $messages->getStyledMessage(__('Something went wrong') . ': EX_ID_NOT_EXISTS', 'error');
        }
        return ($result);
    }

    /**
     * Saves changes of revelation in database
     * 
     * @return void
     */
    public function saveMyRevelation() {
        if (wf_CheckPost(array('editrevelationtext', 'editrevelationid'))) {
            $revelationId = vf($_POST['editrevelationid'], 3);
            $revelationData = $this->getRevelationData($revelationId);
            if (!empty($revelationData)) {
                $where = "WHERE `id`='" . $revelationId . "';";
                //text
                $text = strip_tags($_POST['editrevelationtext']);
                $oldText = $revelationData['text'];
                if ($text != $oldText) {
                    simple_update_field('stickyrevelations', 'text', $text, $where);
                    log_register("REVELATION CHANGED TEXT [" . $revelationId . "]");
                }
                //days intervals
                $dayFrom = $_POST['editrevelationdayfrom'];
                $oldDayFrom = $revelationData['dayfrom'];
                if ($dayFrom != $oldDayFrom) {
                    simple_update_field('stickyrevelations', 'dayfrom', $dayFrom, $where);
                    log_register("REVELATION CHANGED DAYFROM [" . $revelationId . "] ON " . $dayFrom . "");
                }

                $dayTo = $_POST['editrevelationdayto'];
                $oldDayTo = $revelationData['dayto'];
                if ($dayTo != $oldDayTo) {
                    simple_update_field('stickyrevelations', 'dayto', $dayTo, $where);
                    log_register("REVELATION CHANGED DAYTO [" . $revelationId . "] ON " . $dayTo . "");
                }

                $dayWeek = ubRouting::post('editrevelationdayweek', 'int');
                if ($dayWeek == 1488) {
                    $dayWeek = 0; //any day of week
                }
                $oldDayWeek = $revelationData['dayweek'];
                if ($dayWeek != $oldDayWeek) {
                    simple_update_field('stickyrevelations', 'dayweek', $dayWeek, $where);
                    log_register("REVELATION CHANGED DAYWEEK [" . $revelationId . "] ON " . $dayWeek . "");
                }

                //activity flag
                $activity = (isset($_POST['editrevelationactive'])) ? 1 : 0;
                $oldActivity = $revelationData['active'];
                if ($activity != $oldActivity) {
                    simple_update_field('stickyrevelations', 'active', $activity, $where);
                    log_register("REVELATION CHANGED ACTIVE [" . $revelationId . "] ON " . $activity . "");
                }

                //admins selection
                $showTo = '';
                $oldsShowTo = $revelationData['showto'];
                if (!empty($_POST['editrevelationshowto'])) {
                    foreach ($_POST['editrevelationshowto'] as $io => $each) {
                        $showTo .= ' ' . $io . ' ';
                    }
                }
                if ($showTo != $oldsShowTo) {
                    simple_update_field('stickyrevelations', 'showto', $showTo, $where);
                    log_register("REVELATION CHANGED SHOWTO [" . $revelationId . "]");
                }
                $this->flushCache();
            }
        }
    }

    /**
     * Creates new revelation in database
     * 
     * @return void
     */
    public function addMyRevelation() {
        if (wf_CheckPost(array('newrevelationtext'))) {
            $dayFrom = (!empty($_POST['newrevelationdayfrom'])) ? $_POST['newrevelationdayfrom'] : '';
            $dayTo = (!empty($_POST['newrevelationdayto'])) ? $_POST['newrevelationdayto'] : '';
            $dayWeek = ubRouting::post('newrevelationdayweek', 'int');
            if ($dayWeek == 1488) {
                $dayWeek = 0; //any day of week
            }
            $showTo = '';
            if (!empty($_POST['newrevelationshowto'])) {
                foreach ($_POST['newrevelationshowto'] as $io => $each) {
                    $showTo .= ' ' . $io . ' ';
                }
            }
            $activity = (isset($_POST['newrevelationactive'])) ? 1 : 0;
            $text = $_POST['newrevelationtext'];
            $this->createRevelation($dayFrom, $dayTo, $activity, $text, $showTo, $dayWeek);

            $newId = simple_get_lastid('stickyrevelations');
            log_register("REVELATION CREATE [" . $newId . "]");
        }
    }

    /**
     * Returns edit form for some sticky note
     * 
     * @param int  $noteId
     * @param bool $wideForm
     * 
     * @return string
     */
    public function editForm($noteId, $wideForm = false) {
        $noteData = $this->getNoteData($noteId);
        if (!empty($noteData)) {
            $noteText = htmlentities($noteData['text'], ENT_COMPAT, "UTF-8");
            $textAreaDimensions = ($wideForm) ? '80x25' : '50x15';
            $inputs = wf_HiddenInput('editnoteid', $noteId);
            $inputs .= wf_tag('label') . __('Text') . ': ' . wf_tag('br') . wf_tag('label', true);
            $inputs .= wf_TextArea('edittext', '', $noteText, true, $textAreaDimensions);
            $checkState = ($noteData['active'] == 1) ? true : false;
            $inputs .= wf_CheckInput('editactive', __('Personal note active'), true, $checkState);
            $inputs .= wf_DatePickerPreset('editreminddate', $noteData['reminddate']);
            $inputs .= wf_tag('label') . __('Remind only after this date') . wf_tag('label', true);
            $inputs .= wf_tag('br');
            $inputs .= wf_TimePickerPreset('editremindtime', $noteData['remindtime'], __('Remind time'), true);
            $inputs .= wf_tag('br');
            $inputs .= wf_tag('br');
            $inputs .= wf_Submit(__('Save'));

            $result = wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $result = __('Strange exeption');
        }
        return ($result);
    }

    /**
     * Returns sticky note deletion dialog to render below of editing form
     * 
     * @param int $noteId
     * 
     * @return string
     */
    public function getEditFormDeleteControls($noteId) {
        $result = '';
        if (!$this->notesPreview) {
            $messages = new UbillingMessageHelper();
            $noteData = $this->getNoteData($noteId);
            if (!empty($noteData)) {
                $deletingPreview = nl2br($this->cutString(strip_tags($noteData['text']), 50));
                $deletingPreview .= wf_delimiter();
                $deletingPreview .= wf_JSAlert(self::URL_ME . '&delete=' . $noteData['id'], web_delete_icon() . ' ' . __('Delete'), $messages->getDeleteAlert(), '', 'ubButton') . ' ';
                $deletingPreview .= wf_Link(self::URL_ME . '&editform=' . $noteId, wf_img('skins/back.png') . ' ' . __('Cancel'), false, 'ubButton');
                $deletingDialog = wf_modalAuto(web_delete_icon() . ' ' . __('Delete'), __('Delete'), $deletingPreview, 'ubButton');
                $result .= $deletingDialog;
            }
        }
        return($result);
    }

    /**
     * Creates new personal note in database
     * 
     * @return void
     */
    public function addMyNote() {
        if (wf_CheckPost(array('newtext'))) {
            $remindDate = (!empty($_POST['newreminddate'])) ? $_POST['newreminddate'] : '';
            $remindTime = (!empty($_POST['newremindtime'])) ? $_POST['newremindtime'] : '';
            $activity = (isset($_POST['newactive'])) ? 1 : 0;
            $text = $_POST['newtext'];
            $this->createNote($remindDate, $remindTime, $activity, $text);

            $newId = simple_get_lastid('stickynotes');
            log_register("STICKY CREATE [" . $newId . "]");
        }
    }

    /**
     * Saves changes of note in database
     * 
     * @return void
     */
    public function saveMyNote() {
        if (wf_CheckPost(array('edittext', 'editnoteid'))) {
            $noteId = vf($_POST['editnoteid'], 3);
            $noteData = $this->getNoteData($noteId);
            if (!empty($noteData)) {
                $where = "WHERE `id`='" . $noteId . "';";
                //text
                $text = strip_tags($_POST['edittext']);
                $oldText = $noteData['text'];
                if ($text != $oldText) {
                    simple_update_field('stickynotes', 'text', $text, $where);
                    log_register("STICKY CHANGED TEXT [" . $noteId . "]");
                    $this->flushCache();
                }
                //remind date
                $remindDate = $_POST['editreminddate'];
                $oldRemindDate = $noteData['reminddate'];
                if ($remindDate != $oldRemindDate) {
                    if (!empty($remindDate)) {
                        $remindDate = "'" . mysql_real_escape_string($remindDate) . "'";
                    } else {
                        $remindDate = 'NULL';
                    }
                    $query = "UPDATE `stickynotes` SET `reminddate` = " . $remindDate . " " . $where; // ugly hack, i know
                    nr_query($query);
                    log_register("STICKY CHANGED REMINDDATE [" . $noteId . "] ON " . $remindDate . "");
                    $this->flushCache();
                }

                //remind time 
                $remindTime = $_POST['editremindtime'];
                $oldRemindTime = $noteData['remindtime'];

                if (ispos($oldRemindTime, ':')) {
                    $oldRemindTime = explode(':', $oldRemindTime);
                    $oldRemindTime = $oldRemindTime[0] . ':' . $oldRemindTime[1];
                }
                if ($remindTime != $oldRemindTime) {
                    if (!empty($remindTime)) {
                        $remindTime = "'" . mysql_real_escape_string($remindTime) . "'";
                    } else {
                        $remindTime = 'NULL';
                    }
                    $query = "UPDATE `stickynotes` SET `remindtime` = " . $remindTime . " " . $where;
                    nr_query($query);
                    log_register("STICKY CHANGED REMINDTIME [" . $noteId . "] ON " . $remindTime . "");
                    $this->flushCache();
                }

                //activity flag
                $activity = (isset($_POST['editactive'])) ? 1 : 0;
                $oldActivity = $noteData['active'];
                if ($activity != $oldActivity) {
                    simple_update_field('stickynotes', 'active', $activity, $where);
                    log_register("STICKY CHANGED ACTIVE [" . $noteId . "] ON " . $activity . "");
                    $this->flushCache();
                }
            }
        }
    }

    /**
     * Deletes some note by its ID
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteNote($id) {
        $id = vf($id, 3);
        if (!empty($id)) {
            $query = "DELETE FROM `stickynotes` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register("STICKY DELETE [" . $id . "]");
            $this->flushCache();
        }
    }

    /**
     * Deletes some revelation by its ID
     * 
     * @param int $id
     * 
     * @return void
     */
    public function deleteRevelation($id) {
        $id = vf($id, 3);
        if (!empty($id)) {
            $query = "DELETE FROM `stickyrevelations` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register("REVELATION DELETE [" . $id . "]");
            $this->flushCache();
        }
    }

    /**
     * Returns full text of sticky note with check of owner
     * 
     * @param int $noteId
     * 
     * @return string
     */
    public function renderNote($noteId) {
        $noteId = vf($noteId, 3);
        $result = '';
        $noteData = $this->getNoteData($noteId);

        if (!empty($noteData)) {
            $messages = new UbillingMessageHelper();
            if ($noteData['owner'] == $this->myLogin) {
                $result = htmlentities($noteData['text'], ENT_COMPAT, "UTF-8");
                $result = strip_tags($result);
                $result = nl2br($result);
                $result .= wf_delimiter(2);
                $backUrl = '';
                $customLink = '';
                if (ubRouting::get('backurl') == 'calendar') {
                    $backUrl .= '&calendarview=true';
                    $customLink .= '&backurl=calendar';
                }

                $result .= wf_BackLink(self::URL_ME . $backUrl);

                $result .= wf_modalAuto(web_edit_icon() . ' ' . __('Edit'), __('Edit'), $this->editForm($noteId), 'ubButton') . ' ';

                $deletingPreview = nl2br($this->cutString(strip_tags($noteData['text']), 50));
                $deletingPreview .= wf_delimiter();
                $deletingPreview .= wf_JSAlert(self::URL_ME . $customLink . '&delete=' . $noteData['id'], web_delete_icon() . ' ' . __('Delete'), $messages->getDeleteAlert(), '', 'ubButton') . ' ';
                $deletingPreview .= wf_Link(self::URL_ME . $customLink . '&shownote=' . $noteData['id'], wf_img('skins/back.png') . ' ' . __('Cancel'), false, 'ubButton');
                $deletingDialog = wf_modalAuto(web_delete_icon() . ' ' . __('Delete'), __('Delete'), $deletingPreview, 'ubButton');
                $result .= $deletingDialog;
            } else {
                $result = __('Access denied');
            }
        } else {
            $result = __('Strange exeption');
        }
        return ($result);
    }

    /**
     * Returns note text with link to full note view if required
     * 
     * @param int $text
     * @param int $noteId
     * 
     * @return string
     */
    protected function makeFullNoteLink($text, $noteId) {
        $ending = substr($text, -3);
        $result = $text;
        if ($ending == '...') {
            $noteId = vf($noteId, 3);
            if (!empty($noteId)) {
                $noteViewLink = wf_link(self::URL_ME . '&shownote=' . $noteId, '...', false, '', 'title="' . __('View full note') . '"');
                $result = substr_replace($text, $noteViewLink, -3, 3);
            }
        }
        return ($result);
    }

    /**
     * Returns available taskbar notifications as floating widget
     * 
     * @return string
     */
    public function renderTaskbarNotify() {
        $result = '';
        $output = '';
        $delimiterId = '{' . zb_rand_string(8) . '}';
        $delimiterCode = '';
        $offsetLeft = 0;

        if (!empty($this->activenotes)) {
            foreach ($this->activenotes as $io => $each) {
                $noteDate = $each['reminddate'];
                $noteTime = $each['remindtime'];
                if (!empty($noteTime)) {
                    if (!empty($noteDate)) {
                        $noteDate = $noteDate . ' ' . $noteTime;
                    } else {
                        $noteDate = curdate() . ' ' . $noteTime;
                    }
                }
                if (empty($noteDate) OR ( strtotime($noteDate) <= time())) {
                    $tmpText = $each['text'];
                    $tmpText = htmlentities($tmpText, ENT_COMPAT, "UTF-8");
                    $tmpText = strip_tags($tmpText);
                    $output = $tmpText;
                    $output .= $delimiterId;

                    $output = str_replace($delimiterId, $delimiterCode, $output);
                    $output = $this->cutString($output, self::PREVIEW_LEN);
                    $output = $this->makeFullNoteLink($output, $each['id']);
                    $output = nl2br($output);

                    $result .= $this->renderStickyNote($output, $offsetLeft);
                    $offsetLeft = $offsetLeft + 10;
                }
            }
        }

        if (!empty($this->allRevelations)) {
            foreach ($this->allRevelations as $io => $each) {
                $needToShow = false;
                $curDay = date("j");
                $curDayOfWeek = date("w");
                if ($curDayOfWeek == 0) {
                    $curDayOfWeek = 7; //thats is sunday!
                }

                if (!empty($each['dayfrom']) AND (!empty($each['dayto']))) {
                    if (($curDay >= $each['dayfrom']) AND ( $curDay <= $each['dayto'])) {
                        $needToShow = true;
                    }
                }

                if (!empty($each['dayfrom']) AND ( empty($each['dayto']))) {
                    if ($curDay >= $each['dayfrom']) {
                        $needToShow = true;
                    }
                }

                if (empty($each['dayfrom']) AND (!empty($each['dayto']))) {

                    if ($curDay <= $each['dayto']) {
                        $needToShow = true;
                    }
                }

                if (empty($each['dayfrom']) AND ( empty($each['dayto']))) {
                    $needToShow = true;
                }

                if (!empty($each['dayweek'])) {
                    if (empty($each['dayfrom']) AND empty($each['dayto'])) {
                        //just check for day of week on empty dates
                        if ($curDayOfWeek == $each['dayweek']) {
                            $needToShow = true;
                        } else {
                            $needToShow = false;
                        }
                    } else {
                        //or guess is day-time interval valid
                        if ($needToShow) {
                            if ($curDayOfWeek == $each['dayweek']) {
                                $needToShow = true;
                            } else {
                                $needToShow = false; //now is wrong day :(
                            }
                        }
                    }
                }

                if ($needToShow) {
                    $tmpText = $each['text'];
                    $tmpText = strip_tags($tmpText);
                    $output = $tmpText;
                    $output .= $delimiterId;

                    $output = str_replace($delimiterId, $delimiterCode, $output);
                    $output = $this->cutString($output, self::PREVIEW_LEN);
                    $output = nl2br($output);

                    $result .= $this->renderStickyNote($output, $offsetLeft, true);
                    $offsetLeft = $offsetLeft + 10;
                }
            }
        }

        if (!empty($result)) {
            $result .= wf_tag('script');
            $result .= '$( function() { $( ".stickynote" ).draggable({ scroll: false, cancel: ".stickynotetext" }); } );';
            $result .= wf_tag('script', true);
        }
        return ($result);
    }
}

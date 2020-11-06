<?php

/**
 * Ubilling user announcements basic class
 */
class Announcements {

    /**
     * Contains current user login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains Announce ID from $_GET
     *
     * @var string
     */
    protected $ann_id = '';

    /**
     * Contains Announce FOR from $_GET
     *
     * @var string
     */
    protected $ann_for = 'USERS';

    /**
     * Contains log parametr
     *
     * @var string
     */
    protected $log_register = '';

    /**
     * Contains admiface #_GET parametr
     *
     * @var string
     */
    protected $admiface = '';

    /**
     * Contains Databases announcements table
     *
     * @var string
     */
    protected $announcementsTable = 'zbsannouncements';

    /**
     * Contains Databases history table
     *
     * @var string
     */
    protected $historyTable = 'zbsannhist';

    /**
     * Contains admns Name as admin_login => admin_name
     *
     * @var array
     */
    protected $adminsName = array();

    /**
     * Contains all announces as id => array (public, type, title, text)
     *
     * @var array
     */
    protected $announcesAvaible = array();

    /**
     * Contains all announces history as [annid] => Array ( parametr => Array ( [login] => $value))
     *
     * @var array
     */
    protected $announcesHistory = array();

    /**
     * Contains announces history count as annid => count
     *
     * @var array
     */
    protected $announcesHistoryCount = array();

    /**
     * Contains current intro text
     *
     * @var string
     */
    protected $introText = '';

    /**
     * System caching instance placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Caching timeout
     *
     * @var int
     */
    protected $cacheTime = 2592000; //month by default

    /**
     * Contains default announcements cache key name
     */

    const CACHE_KEY_ANN = 'ANNOUNCEMENTS';

    /**
     * Contains default announcements viewed cache key name
     */
    const CACHE_KEY_ADMAQ = 'ADMACQUAINTED';

    /**
     * Contains intro text storage key name
     */
    const INTRO_KEY = 'ZBS_INTRO';

    /**
     * Other predefined constants
     */
    const URL_ME = '?module=announcements';
    const EX_ID_NO_EXIST = 'NO_EXISTING_ID_RECEIVED';

    public function __construct() {
        $this->initMessages();
        $this->setLogin();
        $this->initCache();
        $this->setAnnounceFor();
        $this->setAnnounceId();
        $this->loadAdminsName();
        $this->avaibleAnnouncementsCached();
        $this->loadAnnouncesHistoryCached();
        $this->loadIntroText();
    }

    /**
     * Inits system messages helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets current user login
     * 
     * @return void
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Initalizes system cache object
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Flushes precached data
     * 
     * @return void
     */
    protected function flushCache() {
        $this->cache->delete(self::CACHE_KEY_ANN);
        $this->cache->delete(self::CACHE_KEY_ADMAQ);
    }

    /**
     * Initalizes $ann_for
     * 
     * @return void
     */
    protected function setAnnounceFor() {
        if (wf_CheckGet(array('admiface')) OR ( (@$_GET['module'] == 'taskbar') OR ! isset($_GET['module']))) {
            $this->ann_for = 'ADMINS';
            $this->admiface = '&admiface=true';
            $this->announcementsTable = 'admannouncements';
            $this->historyTable = 'admacquainted';
            $this->log_register = 'ADM ';
        }
    }

    /**
     * Initalizes $ann_id
     * 
     * @return void
     */
    protected function setAnnounceId() {
        if (wf_CheckGet(array('ann_id'))) {
            $this->ann_id = vf($_GET['ann_id'], 3);
        }
    }

    /**
     * Loads admis Name
     * 
     * @return void
     */
    protected function loadAdminsName() {
        @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());
        if (!empty($employeeLogins)) {
            foreach ($employeeLogins as $login => $name) {
                $this->adminsName[$login] = $name;
            }
        }
    }

    /**
     * Init admin Name
     * 
     * @param string $admin
     * @return void
     */
    protected function initAdminName($admin) {
        $result = '';
        if (!empty($admin)) {
            $result = (isset($this->adminsName[$admin])) ? $this->adminsName[$admin] : $admin;
        }
        return ($result);
    }

    /**
     * Loads All avaible Announcements from cache
     * 
     * @return array
     */
    protected function avaibleAnnouncementsCached() {
        $cachedAnnouncements = $this->cache->get(self::CACHE_KEY_ANN, $this->cacheTime);
        if (empty($cachedAnnouncements)) {
            $cachedAnnouncements = array();
        }
        if (isset($cachedAnnouncements[$this->announcementsTable])) {
            $this->announcesAvaible = $cachedAnnouncements[$this->announcementsTable];
        } else {
            $ann_arr = $this->loadAvaibleAnnouncements();
            if (!empty($ann_arr)) {
                foreach ($ann_arr as $key => $data) {
                    $this->announcesAvaible[$data['id']]['public'] = @$data['public'];
                    $this->announcesAvaible[$data['id']]['type'] = @$data['type'];
                    $this->announcesAvaible[$data['id']]['title'] = $data['title'];
                    $this->announcesAvaible[$data['id']]['text'] = $data['text'];
                }
            }
            $cachedAnnouncements[$this->announcementsTable] = $this->announcesAvaible;
            $this->cache->set(self::CACHE_KEY_ANN, $cachedAnnouncements, $this->cacheTime);
        }
    }

    /**
     * Loads All avaible Announcements from databases
     * 
     * @return array
     */
    public function loadAvaibleAnnouncements() {
        $query = "SELECT * from `" . $this->announcementsTable . "` ORDER by `id` ASC";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Loads all avaible hystory results from cache
     * 
     * @return array announcesHistory
     * @return array announcesHistoryCount
     */
    protected function loadAnnouncesHistoryCached($ann_id = '') {
        // Initialises ann_id
        $ann_id = ($ann_id) ? $ann_id : $this->ann_id;

        if (isset($this->announcesAvaible[$ann_id])) {
            $votes_arr = $this->loadAnnounceHistory($ann_id);
            if (!empty($votes_arr)) {
                foreach ($votes_arr as $data) {
                    $this->announcesHistory[$data['annid']]['id'][$data['login']] = $data['id'];
                    $this->announcesHistory[$data['annid']]['date'][$data['login']] = $data['date'];
                }
                // Count Announces History votes
                $this->announcesHistoryCount[$data['annid']] = count($this->announcesHistory[$data['annid']]['id']);
            }
        }
    }

    /**
     * Loads all avaible votes result from databases
     * 
     * @return array
     */
    public function loadAnnounceHistory($ann_id) {
        if ($this->ann_for != 'ADMINS') {
            $query = "SELECT * FROM `" . $this->historyTable . "` WHERE `annid` = '" . $ann_id . "'";
        } else {
            $query = "SELECT *,`admin` as `login` FROM `" . $this->historyTable . "` WHERE `annid` = '" . $ann_id . "'";
        }
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Create Announce on database
     * 
     * @param int $public, $type, $title, $text
     * @return void
     */
    protected function createAnnounce($title, $text, $public, $type) {
        $ann_id = '';
        $public = vf($public, 3);
        $type = vf($type);
        $title = mysql_real_escape_string($title);
        $text = mysql_real_escape_string($text);
        if ($this->ann_for != 'ADMINS') {
            $query = "INSERT INTO `zbsannouncements` (`id`,`public`,`type`,`title`,`text`) VALUES
                    (NULL, '" . $public . "', '" . $type . "', '" . $title . "', '" . $text . "'); ";
        } else {
            $query = "INSERT INTO `admannouncements` (`id`,`title`,`text`) VALUES
                    (NULL, '" . $title . "', '" . $text . "'); ";
        }
        nr_query($query);
        $query_ann_id = "SELECT LAST_INSERT_ID() as id";
        $ann_id = simple_query($query_ann_id);
        $ann_id = $ann_id['id'];
        log_register("ANNOUNCEMENT " . $this->log_register . "CREATE [" . $ann_id . "]");
        $this->flushCache();
        return ($ann_id);
    }

    /**
     * Change Announce data on database
     * 
     * @param int $ann_id, array $new_ann_data
     * @return void
     */
    protected function editAnnounce($ann_id, $new_ann_data) {
        $old_ann_data = $this->announcesAvaible[$ann_id];
        $diff_data = array_diff_assoc($new_ann_data, $old_ann_data);
        if (!empty($diff_data)) {
            foreach ($diff_data as $field => $value) {
                simple_update_field($this->announcementsTable, $field, $value, "WHERE `id`='" . $ann_id . "'");
            }
            log_register("ANNOUNCEMENT " . $this->log_register . "EDIT [" . $ann_id . "]");
            $this->flushCache();
        }
    }

    /**
     * Delete Announce from database
     * 
     * @param int $ann_id
     * @return void
     */
    protected function deleteAnnounce($ann_id) {
        $this->deleteAnnounceHistory($ann_id);
        $query = "DELETE FROM `" . $this->announcementsTable . "` WHERE `id` ='" . $ann_id . "'";
        nr_query($query);
        $this->flushCache();
    }

    /**
     * Delete Announce History from database
     * 
     * @param int $ann_id
     * @return void
     */
    protected function deleteAnnounceHistory($ann_id) {
        $query = "DELETE FROM `" . $this->historyTable . "` WHERE `annid` = '" . $ann_id . "'";
        nr_query($query);
    }

    /**
     * Deletes all data about Announce from database by ID
     * 
     * @param int $ann_id
     * @return void
     */
    public function deleteAnnounceData() {
        if (isset($this->announcesAvaible[$this->ann_id])) {
            $this->deleteAnnounce($this->ann_id);
            log_register("ANNOUNCEMENT " . $this->log_register . "DELETE [" . $this->ann_id . "]");
        }
        rcms_redirect(self::URL_ME . $this->admiface);
    }

    /**
     * updates some existing announcement in database
     * 
     * @param int  $id   existing announcement ID
     * 
     * @return void
     */
    public function controlAnn(array $announcements_data) {
        $result = '';
        $message_warn = '';
        if (!empty($announcements_data)) {
            // Check announcements name
            if (!empty($announcements_data['title'])) {
                $name = ($announcements_data['title']);
            } else {
                $message_warn .= $this->messages->getStyledMessage(__('Title cannot be empty'), 'warning');
            }

            // Check that we dont have warning message and create Announce
            if (empty($message_warn) and @ $_POST['createann']) {
                $ann_id = $this->createAnnounce($name, $announcements_data['text'], @$announcements_data['public'], @$announcements_data['type']);
                // Check that we create Announce, get his $ann_id and redirect to module
                if ($ann_id) {
                    rcms_redirect(self::URL_ME . $this->admiface);
                }
            } elseif (empty($message_warn) and @ $_POST['editann']) {
                if ($this->ann_for != 'ADMINS') {
                    $new_ann_data = array('title' => $name, 'text' => $announcements_data['text'], 'public' => $announcements_data['public'], 'type' => $announcements_data['type']);
                } else {
                    $new_ann_data = array('title' => $name, 'text' => $announcements_data['text']);
                }
                $this->editAnnounce($this->ann_id, $new_ann_data);
                rcms_redirect(self::URL_ME . '&action=edit&ann_id=' . $this->ann_id . $this->admiface);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Poll data cannot be empty '), 'warning');
        }

        $result .= $message_warn;

        return ($result);
    }

    /**
     * returns announcement edit form
     * 
     * @param int $id existing announcement ID
     *  
     * @return string
     */
    public function renderForm() {
        $states = array(1 => __('Yes'), 0 => __('No'));
        $types = array("text" => __('Text'), "html" => __('HTML'));
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $result = '';
        if (!empty($this->ann_id)) {
            $ann_action = 'editann';
            $result .= wf_modal(web_icon_search() . ' ' . __('Preview'), __('Preview'), $this->preview($this->ann_id), 'ubButton', '600', '400');
            $result .= wf_delimiter();
            $inputs = wf_TextInput($ann_action . '[title]', __('Title'), $this->announcesAvaible[$this->ann_id]['title'], true, 40);
            $inputs .= __('Text') . ' (HTML)' . $sup . wf_tag('br');
            $inputs .= wf_TextArea($ann_action . '[text]', '', $this->announcesAvaible[$this->ann_id]['text'], true, '60x10');
            // Check that we dont use admin parametr
            if ($this->ann_for != 'ADMINS') {
                $inputs .= wf_Selector($ann_action . '[public]', $states, __('Public'), $this->announcesAvaible[$this->ann_id]['public'], false);
                $inputs .= wf_Selector($ann_action . '[type]', $types, __('Type'), $this->announcesAvaible[$this->ann_id]['type'], false);
            }
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Save'));
            $result .= wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        } else {
            $ann_action = 'createann';
            $inputs = wf_TextInput($ann_action . '[title]', __('Title'), '', true, 40);
            $inputs .= __('Text') . ' (HTML)' . $sup . wf_tag('br');
            $inputs .= wf_TextArea($ann_action . '[text]', '', '', true, '60x10');
            // Check that we dont use admin parametr
            if ($this->ann_for != 'ADMINS') {
                $inputs .= wf_Selector($ann_action . '[public]', $states, __('Public'), '', false);
                $inputs .= wf_Selector($ann_action . '[type]', $types, __('Type'), '', false);
            }
            $inputs .= wf_delimiter();
            $inputs .= wf_Submit(__('Create'));
            $result = wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        }
    }

    /**
     * Loads current intro text from database
     * 
     * @return void
     */
    protected function loadIntroText() {
        $this->introText = zb_StorageGet(self::INTRO_KEY);
    }

    /**
     * Renders intro text editing form
     * 
     * @return string
     */
    protected function introEditForm() {
        $result = '';
        $inputs = wf_HiddenInput('newzbsintro', 'true');
        $inputs .= __('Text') . ' (HTML)' . wf_tag('br');
        $inputs .= wf_TextArea('newzbsintrotext', '', $this->introText, true, '70x15');
        $inputs .= wf_Submit(__('Save'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Stores new intro text in database
     * 
     * @param string $data
     * 
     * @return void
     */
    public function saveIntroText($data) {
        zb_StorageSet(self::INTRO_KEY, $data);
        log_register('ANNOUNCEMENT INTRO UPDATE');
    }

    /**
     * Renders module controls
     * 
     * @return string
     */
    public function panel() {
        $result = '';
        // Add backlink
        if (wf_CheckGet(array('action')) OR wf_CheckGet(array('show_acquainted'))) {
            $result .= wf_BackLink(self::URL_ME . $this->admiface);
        } else {
            if (cfr('ZBSANNCONFIG')) {
                $result .= wf_Link(self::URL_ME . '&action=create' . $this->admiface, web_icon_create() . ' ' . __('Create'), false, 'ubButton');
            }
        }


        if (!wf_CheckGet(array('show_acquainted')) AND wf_CheckGet(array('admiface'))) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/zbsannouncements.png') . ' ' . __('Userstats announcements'), false, 'ubButton') . ' ';
        }

        if (!wf_CheckGet(array('show_acquainted')) AND ! wf_CheckGet(array('admiface'))) {
            if (cfr('ZBSANNCONFIG')) {
                $result .= wf_modalAuto(wf_img('skins/zbsannouncements.png') . ' ' . __('Userstats intro'), __('Userstats intro'), $this->introEditForm(), 'ubButton');
            }

            if (!wf_CheckGet(array('show_acquainted'))) {
                $result .= wf_Link(self::URL_ME . '&admiface=true', wf_img('skins/admannouncements.png') . ' ' . __('Administrators announcements'), false, 'ubButton');
            }
        }

        return ($result);
    }

    /**
     * returns announcement preview
     * 
     * @param int $id existing announcement ID
     * 
     * @return string
     */
    protected function preview($id) {
        $result = '';
        if (isset($this->announcesAvaible[$id])) {
            if (!empty($this->announcesAvaible[$id]['title'])) {
                $result = wf_tag('h3', false, 'row2', '') . $this->announcesAvaible[$id]['title'] . '&nbsp;' . wf_tag('h3', true);
            }
            $previewtext = $this->announcesAvaible[$id]['text'];
            $result .= nl2br($previewtext);
            $result .= wf_delimiter();
        }
        return ($result);
    }

    /**
     * Loads users address and realname data for further usage
     * 
     * @return void
     */
    protected function loadUsersData() {
        $this->allAddress = zb_AddressGetFulladdresslistCached();
        $this->allRealNames = zb_UserGetAllRealnames();
    }

    /**
     * Renders list of users which acquainted with some announcement
     * 
     * @param int $id
     * 
     * @return string
     */
    public function renderAcquaintedUsers() {
        $opts = '"order": [[ 0, "desc" ]]';
        if ($this->ann_for != 'ADMINS') {
            $columns = array('ID', 'Login', 'Address', 'Real Name', 'Date');
        } else {
            $columns = array('ID', 'Admin', 'Date');
        }
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxannusers=true&ann_id=' . $this->ann_id . $this->admiface, false, 'Users', 100, $opts);
        return ($result);
    }

    /**
     * Renders list of users which acquainted with some announcement
     * 
     * @param int $id
     * 
     * @return string
     */
    public function ajaxAvaibAcquaintedUsers() {
        $json = new wf_JqDtHelper();
        if (isset($this->announcesHistory[$this->ann_id])) {
            $this->loadUsersData();
            foreach ($this->announcesHistory[$this->ann_id]['id'] as $login => $value) {
                $data[] = $this->announcesHistory[$this->ann_id]['id'][$login];
                if ($this->ann_for != 'ADMINS') {
                    $data[] = wf_Link('?module=userprofile&username=' . $login, web_profile_icon() . ' ' . $login);
                    $data[] = @$this->allAddress[$login];
                    $data[] = @$this->allRealNames[$login];
                } else {
                    $data[] = $this->initAdminName($login);
                }
                $data[] = $this->announcesHistory[$this->ann_id]['date'][$login];

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Renders Announces module control panel interface
     * 
     * @return string
     */
    public function renderAvaibleAnnouncements() {
        $opts = '"order": [[ 0, "desc" ]]';
        if ($this->ann_for != 'ADMINS') {
            $columns = array('ID', 'Title', 'Status', 'Type', 'Text', 'Acquainted', 'Actions');
        } else {
            $columns = array('ID', 'Title', 'Text', 'Acquainted', 'Actions');
        }
        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajaxavaibleann=true' . $this->admiface, false, 'Announcements', 100, $opts);
        return ($result);
    }

    /**
     * Renders json formatted data about Announces
     * 
     * @return void
     */
    public function ajaxAvaibleAnnouncements() {
        $json = new wf_JqDtHelper();
        if (!empty($this->announcesAvaible)) {
            foreach ($this->announcesAvaible as $ann_id => $announce) {
                $this->loadAnnouncesHistoryCached($ann_id);
                $actionLinks = '';
                if (cfr('ZBSANNCONFIG')) {
                    $actionLinks .= wf_JSAlert(self::URL_ME . '&action=delete&ann_id=' . $ann_id . $this->admiface, web_delete_icon(), __('Removing this may lead to irreparable results'));
                    $actionLinks .= wf_JSAlert(self::URL_ME . '&action=edit&ann_id=' . $ann_id . $this->admiface, web_edit_icon(), __('Are you serious'));
                }
                $actionLinks .= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->preview($ann_id), '', '600', '400');

                if (isset($this->announcesHistoryCount[$ann_id])) {
                    $announcesHistory = wf_Link(self::URL_ME . '&show_acquainted=true&ann_id=' . $ann_id . $this->admiface, $this->announcesHistoryCount[$ann_id]);
                } else {
                    $announcesHistory = 0;
                }

                if (strlen($announce['text']) > 100) {
                    $textPreview = mb_substr(strip_tags($announce['text']), 0, 100, 'utf-8') . '...';
                } else {
                    $textPreview = strip_tags($announce['text']);
                }

                $data[] = $ann_id;
                $data[] = strip_tags($announce['title']);

                if ($this->ann_for != 'ADMINS') {
                    $data[] = web_bool_led($announce['public']);
                    $data[] = $announce['type'];
                }

                $data[] = $textPreview;
                $data[] = $announcesHistory;
                $data[] = $actionLinks;

                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

}

/**
 * Ubilling administrator announcements basic class
 */
class AdminAnnouncements extends Announcements {

    public function __construct() {
        $this->setLogin();
        $this->initCache();
        $this->setAnnounceFor();
        $this->avaibleAnnouncementsCached();
    }

    /**
     * gets poll that user not voted yet
     * 
     * @return array
     */
    protected function loadAnnouncementsForAcquainted() {
        $result = array();
        $cachedResult = $this->cache->get(self::CACHE_KEY_ADMAQ, $this->cacheTime);
        if (empty($cachedResult)) {
            $cachedResult = array();
        }
        if (isset($cachedResult[$this->myLogin])) {
            $result = $cachedResult[$this->myLogin];
        } else {
            $query = "SELECT * FROM `admannouncements` WHERE `id` NOT IN (SELECT `annid` FROM `admacquainted` "
                    . "WHERE `admin` = '" . $this->myLogin . "') ";
            $result = simple_queryall($query);
            $cachedResult[$this->myLogin] = $result;
            $this->cache->set(self::CACHE_KEY_ADMAQ, $cachedResult, $this->cacheTime);
        }
        return ($result);
    }

    /**
     * Renders current user announcements if required
     * 
     * @return string
     */
    public function showAnnouncements() {
        $result = '';
        $AnnouncementsForAcquainted = $this->loadAnnouncementsForAcquainted();
        if (!empty($AnnouncementsForAcquainted)) {
            if (!empty($this->myLogin)) {
                foreach ($AnnouncementsForAcquainted as $io => $each) {
                    $result .= $this->preview($each['id']);
                    $result .= wf_Link('?module=taskbar&setacquainted=' . $each['id'], wf_img('skins/icon_ok.gif') . ' ' . __('Acquainted'), true, 'ubButton');
                }
            }
        }

        if (!empty($result)) {
            $result = wf_modalOpened(__('Announcements'), $result, '800', '600');
        }
        return ($result);
    }

    /**
     * Sets some admiface announcement as read
     * 
     * @param int $announcementId
     * 
     * @return void
     */
    public function setAcquainted($announcementId) {
        $announcementId = vf($announcementId, 3);
        $curDate = curdatetime();
        $loginFiltered = mysql_real_escape_string($this->myLogin);
        if (!empty($loginFiltered)) {
            $query = "INSERT INTO `admacquainted` (`id`,`date`,`admin`,`annid`) VALUES "
                    . "(NULL, '" . $curDate . "','" . $loginFiltered . "','" . $announcementId . "');";
            nr_query($query);
            log_register("ANNOUNCEMENT ADM READ [" . $announcementId . "]");
            $this->flushCache();
        }
    }

}

?>

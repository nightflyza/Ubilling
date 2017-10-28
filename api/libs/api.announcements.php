<?php

/**
 * Renders module controls
 * 
 * @return string
 */
function web_AnnouncementsControls() {
    $introObj = new ZbsIntro();
    $result = '';
    $result.=wf_Link('?module=zbsannouncements', wf_img('skins/zbsannouncements.png') . ' ' . __('Userstats announcements'), false, 'ubButton') . ' ';
    $result.= wf_modalAuto(wf_img('skins/zbsannouncements.png') . ' ' . __('Userstats intro'), __('Userstats intro'), $introObj->introEditForm(), 'ubButton');
    $result.=wf_Link('?module=zbsannouncements&admiface=true', wf_img('skins/admannouncements.png') . ' ' . __('Administrators announcements'), false, 'ubButton');
    return ($result);
}

/**
 * Userstats announcements base class
 */
class ZbsAnnouncements {

    /**
     * Contains available userstats announcements data as id=>data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Contains available administrators announcements data as id=>data
     *
     * @var array
     */
    protected $adminData = array();

    /**
     * Contains acquainted users history log as id=>data
     *
     * @var array
     */
    protected $history = array();

    const EX_ID_NO_EXIST = 'NO_EXISTING_ID_RECEIVED';

    public function __construct() {
        $this->loadData();
    }

    /**
     * loads all existing announcements/history into private data property
     * 
     * @return void
     */
    protected function loadData() {
        //loading announcements data
        $query = "SELECT * from `zbsannouncements` ORDER by `id` DESC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->data[$each['id']] = $each;
            }
        }
        //loading acquainted data
        $query = "SELECT * from `zbsannhist`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->history[$each['id']] = $each;
            }
        }
    }

    /**
     * deletes announcement from database
     * 
     * @param int $id existing announcement ID
     * 
     * @return void
     */
    public function delete($id) {
        $id = vf($id, 3);
        if (isset($this->data[$id])) {
            $query = "DELETE from `zbsannouncements` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register("ANNOUNCEMENT DELETE [" . $id . "]");
            $queryHistory = "DELETE from `zbsannhist` WHERE `annid`='" . $id . "';";
            nr_query($queryHistory);
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
    }

    /**
     * creates new announcement in database
     * @param int       $public
     * @param string    $type
     * @param string    $title
     * @param string    $text
     * 
     * @return int
     */
    public function create($public, $type, $title, $text) {
        $public = vf($public, 3);
        $type = vf($type);
        $title = mysql_real_escape_string($title);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `zbsannouncements` (`id`,`public`,`type`,`title`,`text`) VALUES
                (NULL, '" . $public . "', '" . $type . "', '" . $title . "', '" . $text . "'); ";
        nr_query($query);
        $newId = simple_get_lastid('zbsannouncements');
        log_register("ANNOUNCEMENT CREATE [" . $newId . "]");
        return ($newId);
    }

    /**
     * updates some existing announcement in database
     * 
     * @param int  $id   existing announcement ID
     * 
     * @return void
     */
    public function save($id) {
        $id = vf($id, 3);
        if (isset($this->data[$id])) {
            simple_update_field('zbsannouncements', 'public', $_POST['editpublic'], "WHERE `id`='" . $id . "'");
            simple_update_field('zbsannouncements', 'type', $_POST['edittype'], "WHERE `id`='" . $id . "'");
            simple_update_field('zbsannouncements', 'title', $_POST['edittitle'], "WHERE `id`='" . $id . "'");
            simple_update_field('zbsannouncements', 'text', $_POST['edittext'], "WHERE `id`='" . $id . "'");
            log_register("ANNOUNCEMENT EDIT [" . $id . "]");
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
    }

    /**
     * returns announcement preview
     * 
     * @param int $id existing announcement ID
     * 
     * @return string
     */
    protected function preview($id) {
        $id = vf($id, 3);
        if (isset($this->data[$id])) {
            $result = wf_tag('h3', false, 'row2', '') . $this->data[$id]['title'] . '&nbsp;' . wf_tag('h3', true);
            $result.= wf_delimiter();
            if ($this->data[$id]['type'] == 'text') {
                $previewtext = strip_tags($this->data[$id]['text']);
                $result.= nl2br($previewtext);
            }

            if ($this->data[$id]['type'] == 'html') {
                $result.=$this->data[$id]['text'];
            }
            $result.=wf_delimiter();
            return ($result);
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
    }

    /**
     * Returns users count which acquainted with some announcement
     * 
     * @param int $id
     * 
     * @return int
     */
    protected function getAcquaintedUsersCount($id) {
        $result = 0;
        if (!empty($this->history)) {
            foreach ($this->history as $io => $each) {
                if ($each['annid'] == $id) {
                    $result++;
                }
            }
        }
        return ($result);
    }

    /**
     * renders list of existing announcements by private data prop
     * 
     * @return string
     */
    public function render() {
        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Public'));
        $cells.= wf_TableCell(__('Type'));
        $cells.= wf_TableCell(__('Acquainted'));
        $cells.= wf_TableCell(__('Title'));
        $cells.= wf_TableCell(__('Text'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');

        if (!empty($this->data)) {
            foreach ($this->data as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell(web_bool_led($each['public']));
                $cells.= wf_TableCell($each['type']);
                $cells.= wf_TableCell($this->getAcquaintedUsersCount($each['id']));
                $cells.= wf_TableCell(strip_tags($each['title']));
                if (strlen($each['text']) > 100) {
                    $textPreview = mb_substr(strip_tags($each['text']), 0, 100, 'utf-8') . '...';
                } else {
                    $textPreview = strip_tags($each['text']);
                }
                $cells.= wf_TableCell($textPreview);
                $actionLinks = wf_JSAlert('?module=zbsannouncements&delete=' . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $actionLinks.= wf_JSAlert('?module=zbsannouncements&edit=' . $each['id'], web_edit_icon(), __('Are you serious'));
                $actionLinks.= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->preview($each['id']), '', '600', '400');
                $cells.= wf_TableCell($actionLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * returns announcement create form
     * 
     * @return string
     */
    public function createForm() {
        $states = array("1" => __('Yes'), "0" => __('No'));
        $types = array("text" => __('Text'), "html" => __('HTML'));

        $inputs = wf_TextInput('newtitle', __('Title'), '', true, 40);
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs.= __('Text') . $sup . wf_tag('br');
        $inputs.= wf_TextArea('newtext', '', '', true, '60x10');
        $inputs.= wf_Selector('newpublic', $states, __('Public'), '', false);
        $inputs.= wf_Selector('newtype', $types, __('Type'), '', false);
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns announcement edit form
     * 
     * @param int $id existing announcement ID
     *  
     * @return string
     */
    public function editForm($id) {
        $id = vf($id, 3);
        $states = array(1 => __('Yes'), 0 => __('No'));
        $types = array("text" => __('Text'), "html" => __('HTML'));
        $result = wf_BackLink('?module=zbsannouncements');
        $result.=wf_modal(web_icon_search() . ' ' . __('Preview'), __('Preview'), $this->preview($id), 'ubButton', '600', '400');
        $result.=wf_delimiter();
        if (isset($this->data[$id])) {
            $inputs = wf_TextInput('edittitle', __('Title'), $this->data[$id]['title'], true, 40);
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs.= __('Text') . $sup . wf_tag('br');
            $inputs.= wf_TextArea('edittext', '', $this->data[$id]['text'], true, '60x10');
            $inputs.= wf_Selector('editpublic', $states, __('Public'), $this->data[$id]['public'], false);
            $inputs.= wf_Selector('edittype', $types, __('Type'), $this->data[$id]['type'], false);
            $inputs.= wf_delimiter();
            $inputs.= wf_Submit(__('Save'));
            $result.= wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
    }

}

/**
 * Administrator interface announcements base class
 */
class AdminAnnouncements {

    /**
     * Contains available administrators announcements data as id=>data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Contains current administrators login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains array of acquainted announcements as annid=>date
     *
     * @var array
     */
    protected $acquainted = array();

    /**
     * Contains data about administrators viewed some announcement as annid=>data
     *
     * @var array
     */
    protected $acStats = array();

    const EX_ID_NO_EXIST = 'NO_EXISTING_ID_RECEIVED';

    public function __construct() {
        $this->setLogin();
        if ($this->checkBaseAvail()) { // checking required tables availability
            $this->loadData();
            $this->loadAcquainted();
        }
    }

    /**
     * Must prevent update troubles and make billing usable between 0.8.2 and 0.8.3 releases
     * 
     * @return bool
     */
    protected function checkBaseAvail() {
        $result = false;
        $fileFlagPath = 'exports/admannouncementsdb';
        if (file_exists($fileFlagPath)) {
            $result = true;
        } else {
            if (zb_CheckTableExists('admannouncements')) {
                $result = true;
                file_put_contents($fileFlagPath, 'ok');
            } else {
                $result = false;
            }
        }
        return ($result);
    }

    /**
     * Sets current administrators login into protected property
     * 
     * @return voids
     */
    protected function setLogin() {
        $this->myLogin = whoami();
    }

    /**
     * loads all existing announcements into private data property
     * 
     * @return void
     */
    protected function loadData() {
        $query = "SELECT * from `admannouncements` ORDER by `id` DESC;";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->data[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads acquainted administrators list from database
     * 
     * @return void
     */
    protected function loadAcquainted() {
        if (!empty($this->myLogin)) {
            $loginFiltered = mysql_real_escape_string($this->myLogin);
            $query = "SELECT * from `admacquainted` WHERE `admin`='" . $loginFiltered . "';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->acquainted[$each['annid']][$each['admin']] = $each['date'];
                }
            }
        }
    }

    /**
     * Loads data about users which acquainted announcement
     * 
     * @return void
     */
    protected function loadAcquaintedStats() {
        $query = "SELECT * from `admacquainted`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->acStats[$each['annid']][$each['admin']] = $each['date'];
            }
        }
    }

    /**
     * deletes announcement from database
     * 
     * @param int $id existing announcement ID
     * 
     * @return void
     */
    public function delete($id) {
        $id = vf($id, 3);
        if (isset($this->data[$id])) {
            $query = "DELETE from `admannouncements` WHERE `id`='" . $id . "';";
            nr_query($query);
            log_register("ANNOUNCEMENT ADM DELETE [" . $id . "]");
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
    }

    /**
     * creates new announcement in database
     * 
     * @param int       $public
     * @param string    $type
     * @param string    $title
     * @param string    $text
     * 
     * @return int
     */
    public function create($title, $text) {
        $title = mysql_real_escape_string($title);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `admannouncements` (`id`,`title`,`text`) VALUES
                (NULL, '" . $title . "', '" . $text . "'); ";
        nr_query($query);
        $newId = simple_get_lastid('zbsannouncements');
        log_register("ANNOUNCEMENT ADM CREATE [" . $newId . "]");
        return ($newId);
    }

    /**
     * updates some existing announcement in database
     * 
     * @param int  $id   existing announcement ID
     * 
     * @return void
     */
    public function save($id) {
        $id = vf($id, 3);
        if (isset($this->data[$id])) {
            simple_update_field('admannouncements', 'title', $_POST['edittitle'], "WHERE `id`='" . $id . "'");
            simple_update_field('admannouncements', 'text', $_POST['edittext'], "WHERE `id`='" . $id . "'");
            log_register("ANNOUNCEMENT ADM EDIT [" . $id . "]");
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
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
        if (isset($this->data[$id])) {
            if (!empty($this->data[$id]['title'])) {
                $result = wf_tag('h3', false, 'row2', '') . $this->data[$id]['title'] . '&nbsp;' . wf_tag('h3', true);
            }
            $previewtext = strip_tags($this->data[$id]['text']);
            $result.= nl2br($previewtext);
            $result.=wf_delimiter();
        }
        return ($result);
    }

    /**
     * renders list of existing announcements by private data prop
     * 
     * @return string
     */
    public function render() {
        $cells = wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Title'));
        $cells.= wf_TableCell(__('Acquainted'));
        $cells.= wf_TableCell(__('Text'));
        $cells.= wf_TableCell(__('Actions'));
        $rows = wf_TableRow($cells, 'row1');
        $this->loadAcquaintedStats();
        $adminNames = ts_GetAllEmployeeLoginsCached();
        $adminNames = unserialize($adminNames);

        if (!empty($this->data)) {
            foreach ($this->data as $io => $each) {
                $cells = wf_TableCell($each['id']);
                $cells.= wf_TableCell(strip_tags($each['title']));
                $acCount = (isset($this->acStats[$each['id']])) ? sizeof($this->acStats[$each['id']]) : 0;
                if (!empty($acCount)) {
                    $acList = '';
                    if (!empty($this->acStats[$each['id']])) {
                        $acCells = wf_TableCell(__('Admin'));
                        $acCells.= wf_TableCell(__('Date'));
                        $acRows = wf_TableRow($acCells, 'row1');
                        foreach ($this->acStats[$each['id']] as $eachAdmLogin => $eachAc) {
                            $adminLabel = (isset($adminNames[$eachAdmLogin])) ? $adminNames[$eachAdmLogin] : $eachAdmLogin;
                            $acCells = wf_TableCell($adminLabel);
                            $acCells.= wf_TableCell($eachAc);
                            $acRows.= wf_TableRow($acCells, 'row3');
                        }


                        $acList.=wf_TableBody($acRows, '100%', 0, 'sortable');
                    }
                    $acControl = wf_modalAuto($acCount, __('Acquainted'), $acList);
                } else {
                    $acControl = $acCount;
                }
                $cells.= wf_TableCell($acControl);
                if (strlen($each['text']) > 100) {
                    $textPreview = mb_substr(strip_tags($each['text']), 0, 100, 'utf-8') . '...';
                } else {
                    $textPreview = strip_tags($each['text']);
                }
                $cells.= wf_TableCell($textPreview);
                $actionLinks = wf_JSAlert('?module=zbsannouncements&admiface=true&delete=' . $each['id'], web_delete_icon(), __('Removing this may lead to irreparable results'));
                $actionLinks.= wf_JSAlert('?module=zbsannouncements&admiface=true&&edit=' . $each['id'], web_edit_icon(), __('Are you serious'));
                $actionLinks.= wf_modal(wf_img('skins/icon_search_small.gif', __('Preview')), __('Preview'), $this->preview($each['id']), '', '600', '400');
                $cells.= wf_TableCell($actionLinks);
                $rows.= wf_TableRow($cells, 'row3');
            }
        }
        $result = wf_TableBody($rows, '100%', 0, 'sortable');
        return ($result);
    }

    /**
     * returns announcement create form
     * 
     * @return string
     */
    public function createForm() {
        $inputs = wf_TextInput('newtitle', __('Title'), '', true, 40);
        $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
        $inputs.= __('Text') . $sup . wf_tag('br');
        $inputs.= wf_TextArea('newtext', '', '', true, '60x10');
        $inputs.= wf_delimiter();
        $inputs.= wf_Submit(__('Create'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * returns announcement edit form
     * 
     * @param int $id existing announcement ID
     *  
     * @return string
     */
    public function editForm($id) {
        $id = vf($id, 3);
        $result = wf_BackLink('?module=zbsannouncements&admiface=true');
        $result.=wf_modal(web_icon_search() . ' ' . __('Preview'), __('Preview'), $this->preview($id), 'ubButton', '600', '400');
        $result.=wf_delimiter();
        if (isset($this->data[$id])) {
            $inputs = wf_TextInput('edittitle', __('Title'), $this->data[$id]['title'], true, 40);
            $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
            $inputs.= __('Text') . $sup . wf_tag('br');
            $inputs.= wf_TextArea('edittext', '', $this->data[$id]['text'], true, '60x10');
            $inputs.= wf_delimiter();
            $inputs.= wf_Submit(__('Save'));
            $result.= wf_Form("", 'POST', $inputs, 'glamour');
            return ($result);
        } else {
            throw new Exception(self::EX_ID_NO_EXIST);
        }
    }

    /**
     * Renders current user announcements if required
     * 
     * @return string
     */
    public function showAnnouncements() {
        $result = '';
        if (!empty($this->data)) {
            if (!empty($this->myLogin)) {
                foreach ($this->data as $io => $each) {
                    if (!isset($this->acquainted[$each['id']])) {
                        $result.=$this->preview($each['id']);
                        $result.=wf_Link('?module=taskbar&setacquainted=' . $each['id'], __('Acquainted'), true, 'ubButton');
                    }
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
        }
    }

}

class ZbsIntro {

    /**
     * Contains current intro text
     *
     * @var string
     */
    protected $introText = '';

    /**
     * Contains intro text storage key name
     */
    const INTRO_KEY = 'ZBS_INTRO';

    /**
     * Creates new intro instance
     */
    public function __construct() {
        $this->loadIntroText();
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
    public function introEditForm() {
        $result = '';
        $inputs = wf_HiddenInput('newzbsintro', 'true');
        $inputs.= __('Text') . ' (HTML)' . wf_tag('br');
        $inputs.= wf_TextArea('newzbsintrotext', '', $this->introText, true, '70x15');
        $inputs.= wf_Submit(__('Save'));
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

}

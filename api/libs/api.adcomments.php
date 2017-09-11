<?php

class ADcomments {

    /**
     * Current scope comments data
     *
     * @var array
     */
    protected $data = array();

    /**
     * Current instance scope
     *
     * @var string
     */
    protected $scope = '';

    /**
     * UbillingCache object placeholder
     *
     * @var object
     */
    protected $cache = '';

     /**
     * Comments caching time
     *
     * @var int
     */
    protected $cacheTime = 2592000; //month by default

    /**
     * Current instance item id
     *
     * @var string
     */
    protected $item = '';

    /**
     * Instance administrators login
     *
     * @var string
     */
    protected $mylogin = '';

    /**
     * Current scope items array
     *
     * @var array
     */
    protected $scopeItems = array();

    /**
     * Scope items loaded flag 
     *
     * @var bool
     */
    protected $scopeItemsLoaded = false;

    const EX_EMPTY_SCOPE = 'EMPTY_SCOPE_RECEIVED';
    const EX_EMPTY_ITEM = 'EMPTY_ITEMID_RECEIVED';
    const EX_EMPTY_QUERY_STRING = 'EMPTY_SERVER_QUERY_STRING_RECEIVED';

    /**
     * ADcomments class constructor
     * 
     * @param string $scope Object scope for comments tree
     */
    public function __construct($scope) {
        if (!empty($scope)) {
            $this->setScope($scope);
            $this->setMyLogin();
            $this->initCache();
        } else {
            throw new Exception(self::EX_EMPTY_SCOPE);
        }
    }

    /**
     * Initalizes system cache object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Clear scope cache object
     * 
     * @return void
     */
    protected function clearScopeCache() {
        $this->cache->delete('ADCOMMENTS_' . $this->scope);
    }

    /**
     * Sets current administrator login into private prop
     * 
     * @return void
     */
    protected function setMyLogin() {
        $this->mylogin = whoami();
    }

    /**
     * Current instance comments scope
     * 
     * @param string $scope
     * 
     * @return void
     */
    protected function setScope($scope) {
        $scope = trim($scope);
        $scope = mysql_real_escape_string($scope);
        $this->scope = $scope;
    }

    /**
     * Sets current scope item commenting ID
     * 
     * @param string $item target item ID
     * 
     * @return void
     */
    protected function setItem($item) {
        $item = trim($item);
        $item = mysql_real_escape_string($item);
        $this->item = $item;
    }

    /**
     * Loads selected scope and item comments into private data property
     * 
     * @return void
     */
    protected function loadComments() {
        if (!empty($this->scope)) {
            if (!empty($this->item)) {
                $query = "SELECT * from `adcomments` WHERE `scope`='" . $this->scope . "' AND `item`='" . $this->item . "' ORDER BY `date` ASC;";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $this->data[$each['id']] = $each;
                    }
                }
            } else {
                throw new Exception(self::EX_EMPTY_ITEM);
            }
        } else {
            throw new Exception(self::EX_EMPTY_SCOPE);
        }
    }

    /**
     * Returns new comment interface
     * 
     * @return string
     */
    protected function commentAddForm() {
        $inputs = wf_TextArea('newadcommentstext', '', '', true, '60x10');
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form("", 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * Creates new comment in database
     * 
     * @param string $text text for new comment
     * 
     * @return void
     */
    protected function createComment($text) {
        $curdate = curdatetime();
        $text = strip_tags($text);
        $text = mysql_real_escape_string($text);
        $query = "INSERT INTO `adcomments` (`id`, `scope`, `item`, `date`, `admin`, `text`) "
                . "VALUES (NULL, '" . $this->scope . "', '" . $this->item . "', '" . $curdate . "', '" . $this->mylogin . "', '" . $text . "');";
        nr_query($query);
        log_register("ADCOMM CREATE SCOPE `" . $this->scope . "` ITEM [" . $this->item . "]");
        $this->clearScopeCache();
    }

    /**
     * Deletes comment from database
     * 
     * @param type $id existing comment database ID
     * 
     * @return void
     */
    protected function deleteComment($id) {
        $id = vf($id, 3);
        $query = "DELETE FROM `adcomments` WHERE `id`='" . $id . "';";
        nr_query($query);
        log_register("ADCOMM DELETE SCOPE `" . $this->scope . "` ITEM [" . $this->item . "]");
        $this->clearScopeCache();
    }

    /**
     * Changes comment text in database
     * 
     * @param int  $id existing comment database ID
     * @param string $text new text for comment
     */
    protected function modifyComment($id, $text) {
        $id = vf($id, 3);
        $text = strip_tags($text);
        simple_update_field('adcomments', 'text', $text, "WHERE `id`='" . $id . "';");
        log_register("ADCOMM CHANGE SCOPE `" . $this->scope . "` ITEM [" . $this->item . "]");
        $this->clearScopeCache();
    }

    /**
     * Controls post environment and do something object actions when its required
     * 
     * @return void
     */
    protected function commentSaver() {
        //detecting return URL
        if (isset($_SERVER['QUERY_STRING'])) {
            $returnUrl = '?' . $_SERVER['QUERY_STRING'];
        } else {
            $returnUrl = '';
            show_error(__('Strange exeption') . ': ' . self::EX_EMPTY_QUERY_STRING);
        }

        ///new comment creation
        if (wf_CheckPost(array('newadcommentstext'))) {
            $this->createComment($_POST['newadcommentstext']);
            if ($returnUrl) {
                rcms_redirect($returnUrl);
            }
        }

        //comment deletion
        if (wf_CheckPost(array('adcommentsdeleteid'))) {
            $this->deleteComment($_POST['adcommentsdeleteid']);
            if ($returnUrl) {
                rcms_redirect($returnUrl);
            }
        }

        //comment editing
        if (wf_CheckPost(array('adcommentsmodifyid', 'adcommentsmodifytext'))) {
            $this->modifyComment($_POST['adcommentsmodifyid'], $_POST['adcommentsmodifytext']);
            if ($returnUrl) {
                rcms_redirect($returnUrl);
            }
        }
    }

    /**
     * Returns JavaScript comfirmation box for deleting/editing inputs
     * 
     * @param string $alert
     * @return string
     */
    protected function jsAlert($alert) {
        $result = 'onClick="return confirm(\'' . $alert . '\');"';
        return ($result);
    }

    /**
     * Returns coment controls for own comments or if im root user
     * 
     * @param int $commentid existing additional comment ID
     * @return string
     */
    protected function commentControls($commentid) {
        $result = '';
        if (isset($this->data[$commentid])) {
            if (($this->data[$commentid]['admin'] == $this->mylogin) OR ( cfr('ROOT'))) {
                $deleteInputs = wf_HiddenInput('adcommentsdeleteid', $commentid);
                $deleteInputs.= wf_tag('input', false, '', 'type="image" src="skins/icon_del.gif" title="' . __('Delete') . '" ' . $this->jsAlert(__('Removing this may lead to irreparable results')));
                $deleteForm = wf_Form('', 'POST', $deleteInputs, '');

                $editInputs = wf_HiddenInput('adcommentseditid', $commentid);
                $editInputs.= wf_tag('input', false, '', 'type="image" src="skins/icon_edit.gif"  title="' . __('Edit') . '" ' . $this->jsAlert(__('Are you serious')));
                $editForm = wf_Form('', 'POST', $editInputs, '');

                $result.=wf_tag('div', false, '', 'style="display:inline-block;"') . $deleteForm . wf_tag('div', true);
                $result.=wf_tag('div', false, '', 'style="display:inline-block;"') . $editForm . wf_tag('div', true);
            }
        }
        return ($result);
    }

    /**
     * Returns comment edit form
     * 
     * @param int $commentid existing database comment ID
     * @return string
     */
    protected function commentEditForm($commentid) {
        $result = '';
        if (isset($this->data[$commentid])) {
            $inputs = wf_HiddenInput('adcommentsmodifyid', $commentid);
            $inputs.= wf_TextArea('adcommentsmodifytext', '', $this->data[$commentid]['text'], true, '60x10');
            $inputs.= wf_Submit(__('Save'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
        }
        return ($result);
    }

    /**
     * Returns list of available comments for some item
     * 
     * @param string $item
     * @return string
     */
    public function renderComments($item) {
        $this->setItem($item);
        $this->loadComments();
        $this->commentSaver();
        @$employeeLogins = unserialize(ts_GetAllEmployeeLoginsCached());

        $result = '';
        $rows = '';

        if (!empty($this->data)) {
            foreach ($this->data as $io => $each) {
                $authorRealname = (isset($employeeLogins[$each['admin']])) ? $employeeLogins[$each['admin']] : $each['admin'];
                $authorName = wf_tag('center') . wf_tag('b') . $authorRealname . wf_tag('b', true) . wf_tag('center', true);
                $authorAvatar = wf_tag('center') . @gravatar_ShowAdminAvatar($each['admin'], '64') . wf_tag('center', true);
                $commentController = wf_tag('center') . $this->commentControls($each['id']) . wf_tag('center', true);
                $authorPanel = $authorName . wf_tag('br') . $authorAvatar . wf_tag('br') . $commentController;

                $commentText = nl2br($each['text']);
                if (wf_CheckPost(array('adcommentseditid'))) {
                    if ($_POST['adcommentseditid'] == $each['id']) {
                        $commentText = $this->commentEditForm($each['id']);
                    } else {
                        $commentText = nl2br($each['text']);
                    }
                }


                $cells = wf_TableCell('', '20%');
                $cells.= wf_TableCell($each['date']);
                $rows.= wf_TableRow($cells, 'row2');
                $cells = wf_TableCell($authorPanel);
                $cells.= wf_TableCell($commentText);
                $rows.= wf_TableRow($cells, 'row3');
            }

            $result.=wf_TableBody($rows, '100%', '0', '');
        }

        $result.=$this->commentAddForm();

        return ($result);
    }

    /**
     * Loads scope items list if its really needed
     * 
     * @rerturn void
     */
    protected function loadScopeItems() {
        if ($this->scope) {
            $query = "SELECT * from `adcomments` WHERE `scope`='" . $this->scope . "';";
            $all = $this->cache->getCallback('ADCOMMENTS_' . $this->scope, function() use ($query) {
                return (simple_queryall($query));
            }, $this->cacheTime);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    if (isset($this->scopeItems[$each['item']])) {
                        $this->scopeItems[$each['item']] ++;
                    } else {
                        $this->scopeItems[$each['item']] = 1;
                    }
                }
            }
            $this->scopeItemsLoaded = true;
        } else {
            throw new Exception(self::EX_EMPTY_SCOPE);
        }
    }

    /**
     * Checks have item some comments or not?
     * 
     * @param string $item
     * @return bool
     */
    public function haveComments($item) {
        if (!$this->scopeItemsLoaded) {
            $this->loadScopeItems();
        }

        if (isset($this->scopeItems[$item])) {
            $result = true;
        } else {
            $result = false;
        }
        return ($result);
    }

    /**
     * Checks have item some additional comments and return native indicator
     * 
     * @param string $item
     * @return int
     */
    public function getCommentsCount($item) {
        if ($this->haveComments($item)) {
            $result = $this->scopeItems[$item];
        } else {
            $result = 0;
        }
        return ($result);
    }

    /**
     * Checks have item some additional comments and return native indicator
     * 
     * @param string $item
     * @return string
     */
    public function getCommentsIndicator($item, $size = '') {
        if ($this->haveComments($item)) {
            $size = (!$size) ? 16 : $size;
            $counter = $this->getCommentsCount($item);
            $result = wf_img_sized('skins/adcomments.png', __('Additional comments') . ' (' . $counter . ')', $size, $size);
        } else {
            $result = '';
        }
        return ($result);
    }

}

?>
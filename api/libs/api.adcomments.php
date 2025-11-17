<?php

/**
 * Universal additional comments class which allows attach comments for any items on some scope
 */
class ADcomments {

    /**
     * Current scope and item comments data as id=>commentData
     *
     * @var array
     */
    protected $allCommentsData = array();

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
     * Current instance administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Current scope items counters as item=>commentsCount
     *
     * @var array
     */
    protected $scopeItems = array();

    /**
     * Comments data database abstraction layer
     *
     * @var object
     */
    protected $commentsDb = '';

    /**
     * Scope items loaded flag 
     *
     * @var bool
     */
    protected $scopeItemsLoaded = false;

    /**
     * Default editing area size
     *
     * @var string
     */
    protected $textAreaSize = '60x10';

    /**
     * Some predefined stuff here
     */
    const TABLE_COMMENTS = 'adcomments';
    const PROUTE_NEW_TEXT = 'newadcommentstext';
    const PROUTE_EDIT_FORM = 'adcommentseditid';
    const PROUTE_EDIT_ID = 'adcommentsmodifyid';
    const PROUTE_EDIT_TEXT = 'adcommentsmodifytext';
    const PROUTE_DELETE = 'adcommentsdeleteid';
    const CACHE_KEY = 'ADCOMMENTS_';
    const OPT_NOLINKIFY='ADCOMMENTS_NO_LINKIFY';

    /**
     * when everything goes wrong
     */
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
            $this->initDb();
        } else {
            throw new Exception(self::EX_EMPTY_SCOPE);
        }
    }

    /**
     * Current instance comments scope
     * 
     * @param string $scope scope of items comments
     * 
     * @return void
     */
    protected function setScope($scope) {
        $scope = trim($scope);
        $scope = ubRouting::filters($scope, 'mres');
        $this->scope = $scope;
    }


    /**
     * Sets current administrator login into private prop
     * 
     * @return void
     */
    protected function setMyLogin() {
        $this->myLogin = whoami();
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
     * Inits database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->commentsDb = new NyanORM(self::TABLE_COMMENTS);
    }

    /**
     * Clear scope cache object
     * 
     * @return void
     */
    protected function clearScopeCache() {
        $this->cache->delete(self::CACHE_KEY . $this->scope);
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
        $item = ubRouting::filters($item, 'mres');
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
                $this->commentsDb->where('scope', '=', $this->scope);
                $this->commentsDb->where('item', '=', $this->item);
                $this->commentsDb->orderBy('date', 'ASC');
                $this->allCommentsData = $this->commentsDb->getAll('id');
            } else {
                throw new Exception(self::EX_EMPTY_ITEM);
            }
        } else {
            throw new Exception(self::EX_EMPTY_SCOPE);
        }
    }



    /**
     * Returns new comment creation form
     * 
     * @return string
     */
    protected function commentAddForm() {
        $result = '';

        $inputs = wf_tag('textarea', false, '', 'name="' . self::PROUTE_NEW_TEXT . '" id="' . self::PROUTE_NEW_TEXT . '"');
        $inputs .= wf_tag('textarea', true);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Save'));

        $result .= wf_tag('div', false, '', 'style="float:left; width: 100%;"');
        $result .= wf_Form('', 'POST', $inputs, 'flatform');
        $result .= wf_tag('div', true);

        //princess fast reply form if its enabled?
        if ($this->scope == 'TASKMAN') {
            $adCommFr = new ADcommFR();
            $result .= $adCommFr->renderPrincessFastReplies();
        }

        $result .= wf_CleanDiv();
        return ($result);
    }

    /**
     * Creates some new comment in database
     * 
     * @param string $text text of new comment
     * 
     * @return void
     */
    protected function createComment($text) {
        $curdate = curdatetime();
        $text = strip_tags($text);
        $text = ubRouting::filters($text, 'mres');

        $this->commentsDb->data('scope', $this->scope);
        $this->commentsDb->data('item', $this->item);
        $this->commentsDb->data('date', $curdate);
        $this->commentsDb->data('admin', $this->myLogin);
        $this->commentsDb->data('text', $text);
        $this->commentsDb->create();

        log_register('ADCOMM CREATE SCOPE `' . $this->scope . '` ITEM [' . $this->item . ']');
        $this->clearScopeCache();
    }

    /**
     * Deletes comment from database
     * 
     * @param int $id existing comment database ID
     * 
     * @return void
     */
    protected function deleteComment($id) {
        $id = ubRouting::filters($id, 'int');

        $this->commentsDb->where('id', '=', $id);
        $this->commentsDb->delete();

        log_register('ADCOMM DELETE SCOPE `' . $this->scope . '` ITEM [' . $this->item . ']');
        $this->clearScopeCache();
    }

    /**
     * Edits some comment text in database
     * 
     * @param int  $id existing comment database ID
     * @param string $text new text for comment
     * 
     * @return void
     */
    protected function modifyComment($id, $text) {
        $id = ubRouting::filters($id, 'int');
        $text = strip_tags($text);
        $text = ubRouting::filters($text, 'mres');

        $this->commentsDb->data('text', $text);
        $this->commentsDb->where('id', '=', $id);
        $this->commentsDb->save();

        log_register('ADCOMM CHANGE SCOPE `' . $this->scope . '` ITEM [' . $this->item . ']');
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
        if (ubRouting::checkPost(self::PROUTE_NEW_TEXT)) {
            $this->createComment(ubRouting::post(self::PROUTE_NEW_TEXT));
            if ($returnUrl) {
                ubRouting::nav($returnUrl);
            }
        }

        //comment deletion
        if (ubRouting::checkPost(self::PROUTE_DELETE)) {
            $this->deleteComment(ubRouting::post(self::PROUTE_DELETE));
            if ($returnUrl) {
                ubRouting::nav($returnUrl);
            }
        }

        //comment editing
        if (ubRouting::checkPost(array(self::PROUTE_EDIT_ID, self::PROUTE_EDIT_TEXT))) {
            $this->modifyComment(ubRouting::post(self::PROUTE_EDIT_ID), ubRouting::post(self::PROUTE_EDIT_TEXT));
            if ($returnUrl) {
                ubRouting::nav($returnUrl);
            }
        }
    }

    /**
     * Returns JavaScript comfirmation box for deleting/editing inputs
     * 
     * @param string $alertText
     * 
     * @return string
     */
    protected function jsAlert($alertText) {
        $result = 'onClick="return confirm(\'' . $alertText . '\');"';
        return ($result);
    }

    /**
     * Returns coment controls for own comments or for the user with root rights
     * 
     * @param int $commentid existing additional comment ID
     * @return string
     */
    protected function commentControls($commentid) {
        $result = '';
        if (isset($this->allCommentsData[$commentid])) {
            if (($this->allCommentsData[$commentid]['admin'] == $this->myLogin) or (cfr('ROOT'))) {
                $deleteInputs = wf_HiddenInput(self::PROUTE_DELETE, $commentid);
                $deleteInputs .= wf_tag('input', false, '', 'type="image" src="skins/icon_del.gif" title="' . __('Delete') . '" ' . $this->jsAlert(__('Removing this may lead to irreparable results')));
                $deleteForm = wf_Form('', 'POST', $deleteInputs, '');

                $editInputs = wf_HiddenInput(self::PROUTE_EDIT_FORM, $commentid);
                $editInputs .= wf_tag('input', false, '', 'type="image" src="skins/icon_edit.gif"  title="' . __('Edit') . '" ' . $this->jsAlert(__('Are you serious')));
                $editForm = wf_Form('', 'POST', $editInputs, '');

                $result .= wf_tag('div', false, '', 'style="display:inline-block;"') . $deleteForm . wf_tag('div', true);
                $result .= wf_tag('div', false, '', 'style="display:inline-block;"') . $editForm . wf_tag('div', true);
            }
        }
        return ($result);
    }

    /**
     * Returns comment editing form
     * 
     * @param int $commentid existing database comment ID
     * 
     * @return string
     */
    protected function commentEditForm($commentid) {
        $result = '';
        if (isset($this->allCommentsData[$commentid])) {
            $inputs = wf_HiddenInput(self::PROUTE_EDIT_ID, $commentid);
            $inputs .= wf_TextArea(self::PROUTE_EDIT_TEXT, '', $this->allCommentsData[$commentid]['text'], true, $this->textAreaSize);
            $inputs .= wf_Submit(__('Save'));
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
        global $ubillingConfig;
        $result = '';
        $rows = '';
        $noLinkifyFlag=($ubillingConfig->getAlterParam(self::OPT_NOLINKIFY)) ? true : false;
        $this->setItem($item);
        $this->loadComments();
        $this->commentSaver();
        $employeeLogins = ts_GetAllEmployeeLoginsAssocCached();

        if (!empty($this->allCommentsData)) {
            foreach ($this->allCommentsData as $io => $each) {
                $authorRealname = (isset($employeeLogins[$each['admin']])) ? $employeeLogins[$each['admin']] : $each['admin'];
                $authorName = wf_tag('center') . wf_tag('b') . $authorRealname . wf_tag('b', true) . wf_tag('center', true);
                $authorAvatar = wf_tag('center') . @FaceKit::getAvatar($each['admin'], '64') . wf_tag('center', true);
                $commentController = wf_tag('center') . $this->commentControls($each['id']) . wf_tag('center', true);
                $authorPanel = $authorName . wf_tag('br') . $authorAvatar . wf_tag('br') . $commentController;
                $commentText = nl2br($each['text']);
                if (!$noLinkifyFlag) {
                     $commentText = zb_Linkify($commentText,'40%');
                }

                if (ubRouting::checkPost(self::PROUTE_EDIT_FORM)) {
                    //is editing form required for this comment?
                    if (ubRouting::post(self::PROUTE_EDIT_FORM) == $each['id']) {
                        //overriding text with editing form
                        $commentText = $this->commentEditForm($each['id']);
                    }
                }

                $cells = wf_TableCell('', '20%');
                $cells .= wf_TableCell($each['date']);
                $rows .= wf_TableRow($cells, 'row2');
                $cells = wf_TableCell($authorPanel);
                $cells .= wf_TableCell($commentText);
                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', '0', '');
        }

        $result .= $this->commentAddForm();

        return ($result);
    }

    /**
     * Loads current scope items from database or cache
     * 
     * @return array
     */
    protected function getScopeItemsCached() {
        $cachedData = array();
        //getting from cache
        $cachedData = $this->cache->get(self::CACHE_KEY . $this->scope, $this->cacheTime);
        if (empty($cachedData)) {
            //cache must be updated
            $this->commentsDb->selectable(array('id', 'scope', 'item', 'text'));
            $this->commentsDb->where('scope', '=', $this->scope);
            $cachedData = $this->commentsDb->getAll();
            if (empty($cachedData)) {
                $cachedData = array();
            }
            $this->cache->set(self::CACHE_KEY . $this->scope, $cachedData, $this->cacheTime);
        }
        return ($cachedData);
    }

    /**
     * Loads scope items list with counters if its really required
     * 
     * @rerturn void
     */
    protected function loadScopeItems() {
        if ($this->scope) {
            $cachedData = $this->getScopeItemsCached();
            if (!empty($cachedData)) {
                foreach ($cachedData as $io => $each) {
                    if (isset($this->scopeItems[$each['item']])) {
                        $this->scopeItems[$each['item']]++;
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
     * 
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
     * 
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
     * @param int $size
     * 
     * @return string
     */
    public function getCommentsIndicator($item, $size = '') {
        if ($this->haveComments($item)) {
            $size = (!$size) ? 16 : $size;
            $counter = $this->getCommentsCount($item);
            $result = wf_img_sized('skins/adcomments.png', __('Additional comments') . ' (' . $counter . ')', $size, $size);
        } else {
            //                                    .  .
            //                                    |\_|\
            //                                    | a_a\    I'm Batman.
            //                                    | | "]
            //                                ____| '-\___
            //                               /.----.___.-'\
            //                              //        _    \
            //                             //   .-. (~v~) /|
            //                            |'|  /\:  .--  / \
            //                           // |-/  \_/____/\/~|
            //                          |/  \ |  []_|_|_] \ |
            //                          | \  | \ |___   _\ ]_}
            //                          | |  '-' /   '.'  |
            //                          | |     /    /|:  |
            //                          | |     |   / |:  /\
            //                          | |     /  /  |  /  \
            //                          | |    |  /  /  |    \
            //                          \ |    |/\/  |/|/\    \
            //                           \|\ |\|  |  | / /\/\__\
            //                            \ \| | /   | |__
            //                                 / |   |____)
            //                                 |_/
            $result = '';
        }
        return ($result);
    }

    /**
     * Returns all items comments data for a given scope, like:
     *      $item => array( [0] => array($comment1),
     *                      [1] => array($comment2),
     *                      .......................
     *                      [N] => array($commentN)
     *                    )
     *
     *      where $comment will be represented as an associative array
     *      with following keys: id,scope,item,text
     *
     * @return array
     *
     * @throws Exception
     */
    public function getScopeItemsCommentsAll() {
        if ($this->scope) {
            $itemsComments = array();
            $cachedData = $this->getScopeItemsCached();
            if (!empty($cachedData)) {
                foreach ($cachedData as $io => $each) {
                    $itemsComments[$each['item']][] = $each;
                }
            }

            return ($itemsComments);
        } else {
            throw new Exception(self::EX_EMPTY_SCOPE);
        }
    }

    /**
     * Returns all comments for a given item in scope
     * 
     * @param string $item
     * 
     * @return array
     */
    public function getCommentsAll($item) {
        $result=array();
        if ($this->scope) {
            if (!empty($item)) {
              $cachedData = $this->getScopeItemsCached();
              if (!empty($cachedData)) {
                foreach ($cachedData as $io => $each) {
                    if ($each['item'] == $item) {
                        $result[] = $each;
                    }
                }
            }
            } else {
                throw new Exception(self::EX_EMPTY_ITEM);
            }
        } else { 
            throw new Exception(self::EX_EMPTY_SCOPE);
        }

        return($result);
    }
}

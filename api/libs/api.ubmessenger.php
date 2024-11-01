<?php

/**
 * Ubilling instant messenger API
 */
class UBMessenger {
    /**
     * Current user instance login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Messages database abstraction layer
     *
     * @var object
     */
    protected $messagesDb = '';

    /**
     * Constains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all cached employee names as login=>name
     *
     * @var array
     */
    protected $allEmployeeNames = array();

    /**
     * Contains available administrators
     *
     * @var array
     */
    protected $allAdmins = array();

    /**
     * Threads refresh interval in ms.
     *
     * @var int
     */
    protected $refreshInterval = 2000;

    /**
     * Contacts refresh interval in ms.
     *
     * @var int
     */
    protected $refreshContacts = 5000;

    /**
     * Admin online timeout interval in minutes
     *
     * @var int
     */
    protected $onlineTimeout = 10;

    /**
     * ZenFlow instance for refreshing messages data
     *
     * @var object
     */
    protected $threadsFlow = '';

    /**
     * Undocumented variable
     *
     * @var object
     */
    protected $contactsFlow = '';

    /**
     * Contains caching object instance
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Contains default cacning timeout in seconds
     *
     * @var int
     */
    protected $cachingTimeout = 3600;

    /**
     * System messages helper instance
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Contains current thread ID
     *
     * @var string
     */
    protected $currentThread = '';

    //some predefined stuff like routes and keys here
    const TABLE_MESSAGES = 'ub_im';
    const URL_ME = '?module=ubim';
    const URL_AVATAR_CONTROL = '?module=avacontrol';

    const ROUTE_THREAD = 'showthread';
    const ROUTE_GOTHREAD = 'gothread';
    const ROUTE_REFRESH = 'checknew';

    const PROUTE_MSG_TO = 'im_message_to';
    const PROUTE_MSG_TEXT = 'im_message_text';

    const SCOPE_THREAD = 'rtubimthread';
    const SCOPE_CONTACTS = 'rtubimcontacts';

    const KEY_ADMS_ONLINE = 'UBIM_ADM_ONLINE';
    const KEY_MSG_COUNT = 'UBIM_MSGCOUNT_';
    const KEY_MSG_THREADS = 'UBIM_MSG_TH_';
    const KEY_ADMS_LIST = 'UBIM_ADM_LIST';

    const OPT_NOLINKIFY = 'UBIM_NO_LINKIFY';
    const OPT_NOAJAXSEND = 'UBIM_MSGSEND_NATIVE';

    public function __construct() {
        $this->setMyLogin();
        $this->initDb();
        $this->loadConfigs();
        $this->initCache();
        $this->initMessages();
        $this->loadAdmins();
        $this->loadEmployeeNames();
    }

    /**
     * Sets current instance administrator login
     *
     * @return void
     */
    protected function setMyLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Loads required configs
     *
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Inits message helper
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Inits caching engine
     *
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Preloads existing employee names
     *
     * @return void
     */
    protected function loadEmployeeNames() {
        $this->allEmployeeNames = ts_GetAllEmployeeLoginsAssocCached();
    }

    /**
     * Inits database abstraction layer
     *
     * @return void
     */
    protected function initDb() {
        $this->messagesDb = new NyanORM(self::TABLE_MESSAGES);
    }

    /**
     * Loads existing administrators
     *
     * @return void
     */
    protected function loadAdmins() {
        $cachedData = $this->cache->get(self::KEY_ADMS_LIST, $this->cachingTimeout);
        if (empty($cachedData) and !is_array($cachedData)) {
            $adminsRaw =  rcms_scandir(DATA_PATH . 'users/');
            if (!empty($adminsRaw)) {
                foreach ($adminsRaw as $io => $eachAdmin) {
                    $this->allAdmins[$eachAdmin] = $eachAdmin;
                }
            }
            $this->cache->set(self::KEY_ADMS_LIST, $this->allAdmins, $this->cachingTimeout);
        } else {
            $this->allAdmins = $cachedData;
        }
    }

    /**
     * Renders self-refreshing thread content
     *
     * @param string $threadId
     * 
     * @return string
     */
    public function renderZenThread($threadId) {
        $result = '';
        if ($threadId) {
            if (isset($this->allAdmins[$threadId])) {
                $this->threadsFlow = new ZenFlow(self::SCOPE_THREAD . '_' . $threadId, $this->renderThreadContent($threadId), $this->refreshInterval);
                $result .= $this->threadsFlow->render();
            }
        }
        return ($result);
    }

    /**
     * Returns self-refreshing contacts list
     *
     * @return void
     */
    protected function renderZenContacts() {
        $this->contactsFlow = new ZenFlow(self::SCOPE_CONTACTS, $this->renderContactList(), $this->refreshContacts);
        return ($this->contactsFlow->render());
    }

    /**
     * Creates new message for some user
     * 
     * @param string $to   admin login
     * @param string $text message text
     * 
     * @return void
     */
    public function createMessage($to, $text) {
        $from = $this->myLogin;
        $to = ubRouting::filters($to, 'mres');
        $text = ubRouting::filters($text, 'mres');
        $text = strip_tags($text);

        $this->messagesDb->data('date', curdatetime());
        $this->messagesDb->data('from', $from);
        $this->messagesDb->data('to', $to);
        $this->messagesDb->data('text', $text);
        $this->messagesDb->data('read', '0');
        $this->messagesDb->create();

        $this->flushCachedData($to);
        log_register('UBIM SEND FROM {' . $from . '} TO {' . $to . '}');
    }

    /**
     * Flushes all relative to sent message cache keys
     *
     * @param string $to
     * 
     * @return void
     */
    protected function flushCachedData($to) {
        $this->cache->delete(self::KEY_ADMS_ONLINE);
        $this->cache->delete(self::KEY_MSG_COUNT . $to);
        $this->cache->delete(self::KEY_MSG_COUNT . $this->myLogin);
        $this->cache->delete(self::KEY_MSG_THREADS . $to . '_' . $this->myLogin);
        $this->cache->delete(self::KEY_MSG_THREADS . $this->myLogin . '_' . $to);
    }

    /**
     * Deletes message by its ID
     * 
     * @param int $msgid   message id from messages database
     * 
     * @return void
     */
    public function deleteMessage($msgId) {
        $msgId = ubRouting::filters($msgId, 'int');
        $this->messagesDb->where('id', '=', $msgId);
        $this->messagesDb->delete();
        log_register('UBIM DELETE [' . $msgId . ']');
    }


    /**
     * mark thread as read by sender
     * 
     * @param string $sender   sender login
     * 
     * @return void
     */
    protected function threadMarkAsRead($sender) {
        $sender = ubRouting::filters($sender, 'mres');

        $this->messagesDb->data('read', '1');
        $this->messagesDb->where('to', '=', $this->myLogin);
        $this->messagesDb->where('from', '=', $sender);
        $this->messagesDb->where('read', '=', '0');
        $this->messagesDb->save();
        $this->flushCachedData($sender);
    }

    /**
     * Returns array of users from which we have some unread messages as login=>count
     * 
     * @return array
     */
    function getAllUnreadMessagesUsers() {
        $result = array();
        $cachedData = $this->cache->get(self::KEY_MSG_COUNT . $this->myLogin, $this->cachingTimeout);
        if (empty($cachedData) and !is_array($cachedData)) {
            $this->messagesDb->where('read', '=', '0');
            $this->messagesDb->where('to', '=', $this->myLogin);
            $all = $this->messagesDb->getAll();

            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    if (isset($result[$each['from']])) {
                        $result[$each['from']]++;
                    } else {
                        $result[$each['from']] = 1;
                    }
                }
            }
            $this->cache->set(self::KEY_MSG_COUNT . $this->myLogin, $result, $this->cachingTimeout);
        } else {
            $result = $cachedData;
        }

        return ($result);
    }

    /**
     * Return contact list with some available users
     * 
     * @return string
     */
    protected function renderContactList() {
        $admListOrdered = array();
        $activeAdmins = $this->getActiveAdmins();
        $haveUnread = $this->getAllUnreadMessagesUsers();
        $result = wf_tag('div', false, 'ubim-contacts-container');

        //list reordering
        if (!empty($this->allAdmins)) {
            $order = sizeof($this->allAdmins);
            foreach ($this->allAdmins as $io => $eachadmin) {
                $order++;
                if ($eachadmin != $this->myLogin) {
                    if (isset($haveUnread[$eachadmin])) {
                        $admListOrdered[$order] = $eachadmin;
                    } else {
                        $admListOrdered[($order + 9000)] = $eachadmin; // It`s Over 9000!
                    }
                }
            }

            if (!empty($admListOrdered)) {
                ksort($admListOrdered);
                foreach ($admListOrdered as $io => $eachadmin) {
                    $unreadCounter = (isset($haveUnread[$eachadmin])) ? $haveUnread[$eachadmin] : 0;
                    if ($eachadmin != $this->myLogin) {
                        $unreadLabel = '';
                        $contactClass = 'ubim-contact';

                        if (isset($activeAdmins[$eachadmin])) {
                            $contactClass .= ' ubim-online ';
                        }

                        if (ubRouting::get(self::ROUTE_THREAD) == $eachadmin) {
                            $contactClass .= ' ubim-open ';
                        }


                        if ($unreadCounter != 0) {
                            $contactClass .= ' ubim-unread ';
                            $unreadLabel .= wf_tag('div', false, 'ubim-unread-label') . $unreadCounter . ' ' . __('Unread message') . wf_tag('div', true);
                        }

                        $conatactAvatar = gravatar_ShowAdminAvatar($eachadmin, '64', 'ubim-avatar');
                        $adminName = (isset($this->allEmployeeNames[$eachadmin])) ? $this->allEmployeeNames[$eachadmin] : $eachadmin;

                        $contactBody = $conatactAvatar;
                        $contactBody .= wf_tag('span', false, 'ubim-contact-name');
                        $contactBody .= $adminName;
                        $contactBody .= $unreadLabel;
                        $contactBody .= wf_tag('span', true);

                        $threadLink = wf_Link(self::URL_ME . '&' . self::ROUTE_THREAD . '=' . $eachadmin, $contactBody,  false, $contactClass);
                        $result .= $threadLink;
                    }
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Administrators') . ' ' . __('not exists'), 'ubim-contact-name');
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Administrators') . ' ' . __('not exists'), 'ubim-contact-name');
        }

        $result .= wf_tag('div', true);
        return ($result);
    }

    /**
     * Return messenger main window grid
     * 
     * @param string $threadContent
     * 
     * @return string
     */
    public function renderMainWindow($threadContent = '') {
        $contactList = '';
        $contactList .= $this->renderZenContacts();

        if (empty($threadContent)) {
            if (ubRouting::checkGet(self::ROUTE_REFRESH)) {
                $threadContent .= $this->messages->getStyledMessage(__('Select a chat to start a conversation'), 'info');
                $threadContent .= wf_delimiter(1);
            }
        }

        $threadContainer = wf_tag('div', false, 'ubim-thread', 'id="threadContainer"');
        $threadContainer .= $threadContent;
        $threadContainer .= wf_tag('div', true);

        $result = '';
        $result .= wf_tag('div', false, 'ubim-big');

        $result .= wf_tag('div', false, 'ubim-left');
        $result .= $contactList;
        $result .= wf_tag('div', true);


        $result .= wf_tag('div', false, 'ubim-right');
        $result .= $threadContainer;
        $result .= wf_tag('div', true);

        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        return ($result);
    }

    /**
     * Return conversation form for some thread
     * 
     * @param string $to - thread username 
     * 
     * @return string
     */
    public function renderConversationForm($to) {
        $result = '';
        if (isset($this->allAdmins[$to])) {
            $this->currentThread = $to;
            $sendButtonTitle = '';
            if (!@$this->altCfg[self::OPT_NOAJAXSEND]) {
                $sendButtonTitle = 'title="' . __('Ctrl-Enter') . '"';
            }
            $inputs = wf_HiddenInput(self::PROUTE_MSG_TO, $to);
            $inputs .= wf_tag('textarea', false, 'ubim-input-message', 'id="ubim-chat-box" name="' . self::PROUTE_MSG_TEXT . '" placeholder="' . __('Write message') . '..." required autofocus');
            $inputs .= wf_tag('textarea', true);
            $inputs .= wf_tag('button', false, 'ubim-send-button', 'type="submit" ' . $sendButtonTitle . ' ');
            $inputs .= __('Send');
            $inputs .= wf_tag('button', true);
            $result .= wf_Form('', 'POST', $inputs, 'ubim-chat-form', '', 'ubim-converstation');
            $result .= wf_tag('span', false, '', 'id="response"') . wf_tag('span', true);

            //preventing page refresh on sending message
            if (!@$this->altCfg[self::OPT_NOAJAXSEND]) {
                $result .= wf_tag('script');
                $result .= "
                        //Ctrl-Enter handling
                        $('form').keydown(function(event) {
                        if (event.ctrlKey && event.keyCode === 13) {
                            $(this).trigger('submit');
                        }
                        })

                        //smooth messages sending
                        $(document).ready(function() {
                        $('#ubim-converstation').on('submit', function(e) {
                            e.preventDefault();

                            $.ajax({
                            type: 'POST',
                            url: '" . self::URL_ME . '&' . self::ROUTE_THREAD . '=' . $to . "',
                            data: $(this).serialize(),
                            success: function(response) {
                                    $('#ubim-chat-box').val('');
                                    $('#ubim-chat-box').focus();
                                },
                            });
                        });
                        });
                    ";
                $result .= wf_tag('script', true);
            }
        } else {
            $result .= $this->messages->getStyledMessage(__('Administrator') . ' {' . $to . '} ' . __('not exists'), 'error');
            log_register('UBIM FAIL THREAD {' . $to . '} NOT_EXISTS');
        }
        return ($result);
    }

    /**
     * Returns all messages array from thread with some specified admin
     *
     * @param string $threadUser
     * 
     * @return array
     */
    protected function getThreadMessages($threadUser) {
        $threadUser = ubRouting::filters($threadUser, 'mres');
        $result = array();
        $cachedData = $this->cache->get(self::KEY_MSG_THREADS . $this->myLogin . '_' . $threadUser, $this->cachingTimeout);

        if (empty($cachedData) and !is_array($cachedData)) {
            $this->messagesDb->whereRaw("(`to`='" . $this->myLogin . "' AND `from`='" . $threadUser . "')  OR (`to`='" . $threadUser . "' AND `from`='" . $this->myLogin . "')");
            $this->messagesDb->orderBy('id', 'DESC');
            $result = $this->messagesDb->getAll();
            $this->cache->set(self::KEY_MSG_THREADS . $this->myLogin . '_' . $threadUser, $result, $this->cachingTimeout);
        } else {
            $result = $cachedData;
        }
        return ($result);
    }

    /**
     * Shows thread for me with some user
     * 
     * @param string $threadUser  user to show thread
     * 
     * @return string
     */
    public function renderThreadContent($threadUser) {
        $threadUser = ubRouting::filters($threadUser, 'mres');
        $adminName = (isset($this->allEmployeeNames[$threadUser])) ? $this->allEmployeeNames[$threadUser] : $threadUser;
        $curDate = curdate();
        $result = $this->messages->getStyledMessage(__('No conversations with') . ' ' . $adminName . ' ' . __('yet'), 'info');
        $unreadCount = 0;

        $alldata = $this->getThreadMessages($threadUser);

        if (!empty($alldata)) {
            $result = '';
            $result .= wf_tag('div', false, 'ubim-chat-container');
            foreach ($alldata as $io => $each) {
                //incrementing unread counter to me
                if (($each['read'] == 0) and ($each['to'] == $this->myLogin)) {
                    $unreadCount++;
                }
                $statusIcon = ($each['read']) ? wf_img("skins/message_read.png") : wf_img("skins/message_unread.png");
                $fromName = (isset($this->allEmployeeNames[$each['from']])) ? $this->allEmployeeNames[$each['from']] : $each['from'];

                $messageTimestamp = strtotime($each['date']);
                $messageDate = date("Y-m-d", $messageTimestamp);
                if ($messageDate != $curDate) {
                    $timeLabel = $each['date'];
                } else {
                    $timeLabel = date("H:i:s", $messageTimestamp);
                }


                $messageText = nl2br($each['text']);
                if (@!$this->altCfg[self::OPT_NOLINKIFY]) {
                    $messageText = $this->linkify($messageText);
                }


                //rendering message container
                $messageClass = 'ubim-message';
                if ($each['from'] == $this->myLogin) {
                    $messageClass .= ' ubim-from-user ';
                } else {
                    $messageClass .= ' ubim-from-other ';
                }

                $result .= wf_tag('div', false, $messageClass);
                //      $result .= $fromName;
                $result .= gravatar_ShowAdminAvatar($each['from'], '64', 'ubim-chat-avatar', $fromName);
                $result .= wf_tag('div', false, 'ubim-message-bubble');
                $result .= wf_tag('div', false, 'ubim-message-author');
                $result .= $fromName;
                $result .= wf_tag('div', true);
                $result .= wf_tag('div', false, 'ubim-message-content');
                $result .= $messageText;
                $result .= wf_tag('div', true);
                $result .= wf_tag('span', false, 'ubim-timestamp');
                $result .= $timeLabel;
                $result .=  ' ' . $statusIcon;
                $result .= wf_tag('span');
                $result .= wf_tag('div', true);
                $result .= wf_tag('div', true);
            }

            //mark all unread messages as read now
            if ($unreadCount > 0) {
                $this->threadMarkAsRead($threadUser);
            }
            $result .= wf_tag('div', true);
        }

        return ($result);
    }


    /**
     * Checks how many unread messages we have?
     * 
     * @return int
     */
    public function checkForUnreadMessages() {
        $result = 0;
        $this->messagesDb->where('to', '=', $this->myLogin);
        $this->messagesDb->where('read', '=', '0');
        $result = $this->messagesDb->getFieldsCount();
        return ($result);
    }


    /**
     * Returns array of "active" administrators
     * 
     * @return array
     */
    protected function getActiveAdmins() {
        $result = array();
        $cachedData = $this->cache->get(self::KEY_ADMS_ONLINE, $this->cachingTimeout);

        if (empty($cachedData) and !is_array($cachedData)) {
            $query = "SELECT DISTINCT `admin` from `weblogs` WHERE `date` > DATE_SUB(NOW(), INTERVAL " . $this->onlineTimeout . " MINUTE);";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $result[$each['admin']] = $each['admin'];
                }
            }
            $this->cache->set(self::KEY_ADMS_ONLINE, $result, $this->cachingTimeout);
        } else {
            $result = $cachedData;
        }
        return ($result);
    }

    /**
     * Turn all URLs in clickable links.
     *
     * @param string $text
     *
     * @return string
     */
    protected function linkify($text) {
        $urlPattern = '/\b(https?:\/\/[^\s<>"\'\)]+)/i';
        $result = preg_replace_callback($urlPattern, function ($matches) {
            $url = $matches[0];

            if (preg_match('/\.(jpg|png|gif|webp|jpeg)$/i', $url)) {
                return wf_link($url, wf_img_sized(htmlspecialchars($url), '', '100%'), false, '', 'target="_blank"');
            }

            return wf_Link($url, htmlspecialchars($url), false, '', 'target="_blank"');
        }, $text);

        return ($result);
    }

    /**
     * Returns primary messenger window title
     *
     * @return string
     */
    public function renderMainWinTitle() {
        $result = '';
        $avaLabel = gravatar_ShowAdminAvatar(whoami(), '16', 'ubim-avacontrol', __('Avatar control'));
        $returnUrl = self::URL_ME;
        if ($this->currentThread) {
            $returnUrl .= '&' . self::ROUTE_THREAD . '=' . $this->currentThread;
        } else {
            $returnUrl .= '&' . self::ROUTE_REFRESH . '=true';
        }
        $baseTitle = '';
        $baseTitle .= wf_Link(self::URL_AVATAR_CONTROL . '&back=' . base64_encode($returnUrl), $avaLabel, false);
        $baseTitle .= ' ' . __('Instant messaging service');
        if ($this->currentThread) {
            $baseTitle .= ': ' . @$this->allEmployeeNames[$this->currentThread];
        }
        $result .= $baseTitle;
        return ($result);
    }
}

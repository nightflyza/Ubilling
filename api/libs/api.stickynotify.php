<?php

/**
 * Sticky Notes daily notification implementation
 */
class StickyNotify {

    /**
     * Contains all existing notes data as id=>noteData
     * 
     * @var array
     */
    protected $allNotesData = array();

    /**
     * SitckyNotes database abstraction layer placeholder
     * 
     * @var object
     */
    protected $notesDb = '';

    /**
     * Telegram abstraction layer placeholder
     * 
     * @var object
     */
    protected $telegram = '';

    /**
     * Contains all employee administator logins as login=>employeeId
     * 
     * @var array
     */
    protected $allEmployeeLogins = array();

    /**
     * Contains all employee data as id=>name
     * 
     * @var array
     */
    protected $allEmployee = array();

    /**
     * Contains all employee Telegram chatId as id=>chatid
     * 
     * @var array
     */
    protected $allEmployeeChatIds = array();

    /**
     * Contains all active employee data as id=>name
     * 
     * @var array
     */
    protected $allActiveEmployee = array();

    public function __construct() {
        $this->initNotesDb();
        $this->initTelegram();
        $this->loadEmployeeData();
        $this->loadNotesData();
    }

    /**
     * Inits notes database abstraction layer
     * 
     * @return void
     */
    protected function initNotesDb() {
        $this->notesDb = new NyanORM('stickynotes');
    }

    /**
     * Inits telegram abstraction instance
     * 
     * @return void
     */
    protected function initTelegram() {
        $this->telegram = new UbillingTelegram();
    }

    /**
     * Preloads all existing employee data
     * 
     * @return void
     */
    protected function loadEmployeeData() {
        $allEmployeeTmp = ts_GetAllEmployeeData();
        if (!empty($allEmployeeTmp)) {
            foreach ($allEmployeeTmp as $io => $each) {
                $this->allEmployee[$each['id']] = $each['name'];
                if (!empty($each['admlogin'])) {
                    $this->allEmployeeLogins[$each['admlogin']] = $each['id'];
                }
                if ($each['active']) {
                    $this->allActiveEmployee[$each['id']] = $each['name'];
                }
                if ($each['telegram']) {
                    $this->allEmployeeChatIds[$each['id']] = $each['telegram'];
                }
            }
        }
    }

    /**
     * Preloads all existing notes data
     * 
     * @return void
     */
    protected function loadNotesData() {
        $this->notesDb->where('active', '=', '1');
        $this->allNotesData = $this->notesDb->getAll();
    }

    /**
     * Returns administrator chatId if he associated with active employee
     * 
     * @param string $adminLogin
     * 
     * @return int
     */
    protected function getAdminChatId($adminLogin) {
        $result = 0;
        if (!empty($adminLogin)) {
            //associated user?
            $adminEmployeeId = (isset($this->allEmployeeLogins[$adminLogin])) ? $this->allEmployeeLogins[$adminLogin] : 0;
            if ($adminEmployeeId) {
                //is it active?
                if (isset($this->allActiveEmployee[$adminEmployeeId])) {
                    //have chat id?
                    if (isset($this->allEmployeeChatIds[$adminEmployeeId])) {
                        $result = $this->allEmployeeChatIds[$adminEmployeeId];
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Performs telegram sending of some messages queue as chatId=>message
     * 
     * @param array $sendingQueue
     * 
     * @return void
     */
    protected function sendNotifications($sendingQueue) {
        if (!empty($sendingQueue)) {
            foreach ($sendingQueue as $eachChatId => $eachMessage) {
                $this->telegram->sendMessage($eachChatId, $eachMessage, false, 'STICKYNOTIFY');
            }
        }
    }

    /**
     * Returns message queue for active notes planned today for each active employee
     * 
     * @return array
     */
    protected function getNotesTodayCount() {
        $result = array();
        $curDate = curdate();
        if (!empty($this->allNotesData)) {
            $sendingQueue = array(); //contains sending queue as chatId=>notesCount
            foreach ($this->allNotesData as $io => $each) {
                if ($each['reminddate'] == $curDate) {
                    $adminChatId = $this->getAdminChatId($each['owner']);
                    if ($adminChatId) {
                        if (isset($sendingQueue[$adminChatId])) {
                            $sendingQueue[$adminChatId]++;
                        } else {
                            $sendingQueue[$adminChatId] = 1;
                        }
                    }
                }
            }

            if (!empty($sendingQueue)) {
                foreach ($sendingQueue as $eachChatId => $notesCount) {
                    $result[$eachChatId] = '🙀 ' . __('You have') . ' ' . $notesCount . ' ' . __('notes or reminders for today');
                }
            }
        }
        return($result);
    }

    /**
     * Performs notification for notes planned today
     * 
     * @return void
     */
    public function run() {
        $todayPlannedNotes = $this->getNotesTodayCount();
        $this->sendNotifications($todayPlannedNotes);
    }
}

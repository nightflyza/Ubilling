<?php

/**
 * Taskman daily tasks notification
 */
class TaskmanNotify {

    /**
     * Contains all existing tasks data as id=>taskData
     * 
     * @var array
     */
    protected $allTasksData = array();

    /**
     * Taskman database abstraction layer placeholder
     * 
     * @var object
     */
    protected $tasksDb = '';

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
        $this->initTasksDb();
        $this->initTelegram();
        $this->loadEmployeeData();
        $this->loadTasksData();
    }

    /**
     * Inits taskman database abstraction layer
     * 
     * @return void
     */
    protected function initTasksDb() {
        $this->tasksDb = new NyanORM('taskman');
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
     * Preloads all existing tasks data
     * 
     * @return void
     */
    protected function loadTasksData() {
        $curDate = curdate();
        $this->tasksDb->where('status', '=', '0'); //only open tasks
        $this->tasksDb->where('startdate', '=', $curDate); //saving few resources
        $this->allTasksData = $this->tasksDb->getAll();
    }

    /**
     * Returns chatId if he associated with active employee
     * 
     * @param string $employeeId
     * 
     * @return int
     */
    protected function getEmployeeChatId($employeeId) {
        $result = 0;
        //is it active?
        if (isset($this->allActiveEmployee[$employeeId])) {
            //have chat id?
            if (isset($this->allEmployeeChatIds[$employeeId])) {
                $result = $this->allEmployeeChatIds[$employeeId];
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
                $this->telegram->sendMessage($eachChatId, $eachMessage, false, 'TASKMANNOTIFY');
            }
        }
    }

    /**
     * Returns message queue for active tasks planned today for each active employee
     * 
     * @return array
     */
    protected function getTasksTodayCount() {
        $result = array();
        $curDate = curdate();
        if (!empty($this->allTasksData)) {
            $sendingQueue = array(); //contains sending queue as chatId=>tasksCount
            foreach ($this->allTasksData as $io => $each) {
                if ($each['startdate'] == $curDate) {
                    $adminChatId = $this->getEmployeeChatId($each['employee']);
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
                foreach ($sendingQueue as $eachChatId => $tasksCount) {
                    $result[$eachChatId] = '😾 ' . __('You have') . ' ' . $tasksCount . ' ' . __('undone tasks for today');
                }
            }
        }
        return($result);
    }

    /**
     * Performs notification for tasks planned today
     * 
     * @return void
     */
    public function run() {
        $todayPlannedTasks = $this->getTasksTodayCount();
        $this->sendNotifications($todayPlannedTasks);
    }
}

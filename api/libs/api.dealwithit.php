<?php

class DealWithIt {

    /**
     * Contains available tasks as id=>taskdata
     *
     * @var array
     */
    protected $allTasks = array();
    
    /**
     * Contains available actions array as action=>name
     *
     * @var array
     */
    protected $actions=array();

    public function __construct() {
        $this->setActions();
        $this->loadTasks();
    }

    /**
     * Loads existing tasks for further usage
     * 
     * @return void
     */
    protected function loadTasks() {
        $query = "SELECT * from `dealwithit`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTasks[$each['id']] = $each;
            }
        }
    }
    
    protected function setActions() {
        $this->actions=array();
    }

    /**
     * 
     * @param string $date
     * @param string $login
     * @param string $action
     * @param string $param
     * @param string $note
     * 
     * @return void
     */
    public function createTask($date, $login, $action, $param, $note) {
        $dateF = mysql_real_escape_string($date);
        $loginF = mysql_real_escape_string($login);
        $actionF = mysql_real_escape_string($action);
        $paramF = mysql_real_escape_string($param);
        $noteF = mysql_real_escape_string($note);
        $query = "INSERT INTO `dealwithit` (`id`,`date`,`login`,`action`,`param`,`note`) VALUES";
        $query.="(NULL,'" . $dateF . "','" . $loginF . "','" . $actionF . "','" . $paramF . "','" . $noteF . "');";
        nr_query($query);
        $newId=  simple_get_lastid('dealwithit');
        log_register('SCHEDULED (' . $login . ') ID ['.$newId.'] DATE `' . $date . ' `ACTION `' . $action . '` NOTE `' . $note . '`');
    }
    
    /**
     * Renders task creation form
     * 
     * @return string
     */
    public function renderCreateForm($login) {
        $result='';
        $inputs=  wf_HiddenInput('newschedlogin', $login);
        $inputs.= wf_DatePickerPreset('newscheddate', curdate()).' '.__('Target date');
        $inputs.= wf_Selector('newschedaction', $this->actions, __('Actions'), '', false);
        
        $result=  wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

}

?>
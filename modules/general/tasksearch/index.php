<?php

if (cfr('TASKMANSEARCH')) {

    class TaskmanSearch {

        /**
         * Contains all available employee as employeeid=>name
         *
         * @var array
         */
        protected $allEmployee = array();

        /**
         * Contains all active employee as employeeid=>name
         *
         * @var array
         */
        protected $activeEmployee = array();

        /**
         * System alter.ini config stored as array key=>value
         *
         * @var array
         */
        protected $altCfg = array();

        /**
         * Available jobtypes as jobtypeid=>name
         *
         * @var array
         */
        protected $allJobtypes = array();
        
        const URL_ME='?module=tasksearch';
        const URL_TWJ='salarytwj=true';


        public function __construct() {
            $this->loadAllEmployee();
            $this->loadActiveEmployee();
            $this->loadAltcfg();
            $this->loadJobtypes();
        }

        /**
         * Loads all existing employees from database
         * 
         * @return void
         */
        protected function loadAllEmployee() {
            $this->allEmployee = ts_GetAllEmployee();
        }

        /**
         * Loads all existing employees from database
         * 
         * @return void
         */
        protected function loadActiveEmployee() {
            $this->activeEmployee = ts_GetActiveEmployee();
        }

        /**
         * Loads system alter config
         * 
         * @global object $ubillingConfig
         * 
         * @return void
         */
        protected function loadAltcfg() {
            global $ubillingConfig;
            $this->altCfg = $ubillingConfig->getAlter();
        }

        /**
         * Loads available jobtypes from database
         * 
         * @return void
         */
        protected function loadJobtypes() {
            $this->allJobtypes = ts_GetAllJobtypes();
        }

        /**
         * Renders search form. Deal with it.
         * 
         * @return string
         */
        public function renderSearchForm() {
            $result = '';
            $inputs = __('Date') . ' ' . wf_DatePickerPreset('datefrom', curdate(), true) . ' ' . __('From') . ' ' . wf_DatePickerPreset('dateto', curdate(), true) . ' ' . __('To');
            $inputs.= wf_tag('br');
            $inputs.= wf_CheckInput('cb_id', '', false, false);
            $inputs.= wf_TextInput('taskid', __('ID'), '', true, 4);
            $inputs.= wf_CheckInput('cb_taskdays', '', false, false);
            $inputs.= wf_TextInput('taskdays', __('Implementation took more days'), '', true, 4);
            $inputs.= wf_CheckInput('cb_taskaddress', '', false, false);
            $inputs.= wf_TextInput('taskaddress', __('Task address'), '', true, 20);
            $inputs.= wf_CheckInput('cb_taskphone', '', false, false);
            $inputs.= wf_TextInput('taskphone', __('Phone'), '', true, 20);
            $inputs.= wf_CheckInput('cb_employee', '', false, false);
            $inputs.= wf_Selector('employee', $this->activeEmployee, __('Who should do'), '', true);
            $inputs.= wf_CheckInput('cb_employeedone', '', false, false);
            $inputs.= wf_Selector('employeedone', $this->activeEmployee, __('Worker done'), '', true);
            $inputs.= wf_CheckInput('cb_duplicateaddress', __('Duplicate address'), true, false);
            $inputs.= wf_CheckInput('cb_showlate', __('Show late'), true, false);
            $inputs.= wf_CheckInput('cb_onlydone', __('Done tasks'), true, false);
            $inputs.= wf_CheckInput('cb_onlyundone', __('Undone tasks'), true, false);
            if ($this->altCfg['SALARY_ENABLED']) {
                $inputs.=wf_CheckInput('cb_nosalsaryjobs', __('Tasks without jobs'),true, false);
            }

            $inputs.=wf_Submit(__('Search'));
            $result = wf_Form('', 'POST', $inputs, 'glamour');
            $result.= wf_CleanDiv();
            
            return ($result);
        }
        
        
        public function commonSearch() {
            $result=array();
             if (wf_CheckPost(array('datefrom','dateto'))) {
                $dateFrom=  mysql_real_escape_string($_POST['datefrom']);
                $dateTo= mysql_real_escape_string($_POST['dateto']);
                $baseQuery="SELECT * from `taskman` WHERE `startdate` BETWEEN '".$dateFrom."' AND '".$dateTo."' ";
                $appendQuery='';
                //task id
                if (wf_CheckPost(array('cb_id','taskid'))) {
                    $taskid=vf($_POST['taskid'],3);
                    $appendQuery.=" AND `id`='".$taskid."' ";
                }
                //more than some days count
                if (wf_CheckPost(array('cb_taskdays','taskdays'))) {
                    $taskdays=vf($_POST['taskdays'],3);
                    $appendQuery.=" AND DATEDIFF(`enddate`, `startdate`) > '".$taskdays."' ";
                }
                
                $query=$baseQuery.$appendQuery;
                deb($query);
                $raw=  simple_queryall($query);
                debarr($raw);
            }
            return ($result);
        }
        
        }

    $taskmanSearch = new TaskmanSearch();
    show_window(__('Tasks search'),$taskmanSearch->renderSearchForm());
    if (wf_CheckPost(array('datefrom','dateto'))) {
        $taskmanSearch->commonSearch();
    }
    show_window('', wf_Link('?module=taskman', __('Back'), true, 'ubButton'));
    
    
} else {
    show_error(__('You cant control this module'));
}
?>
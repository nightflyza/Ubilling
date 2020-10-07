<?php

class TaskFlow {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains array of all available employee from directory
     *
     * @var array
     */
    protected $allActiveEmployee = array();

    /**
     * Task states instance placeholder
     *
     * @var object
     */
    protected $taskStates = '';

    /**
     * Photostorage object instance for tasks scope
     *
     * @var object
     */
    protected $photoStorage = '';

    /**
     * ADcomments object instance placeholder
     *
     * @var object
     */
    protected $adComments = '';

    /**
     * Contains all warehouse outcome counters as taskid=>count
     *
     * @vara array
     */
    protected $allWarehouseOutcomes = array();

    /**
     * Predefined routes/URLs/etc
     */
    const URL_ME = '?module=taskflow';
    const PROUTE_STATE = 'searchtaskstate';
    const PROUTE_PHOTO = 'searchtaskphoto';
    const PROUTE_WAREHOUSE = 'searchtaskwarehouse';

    public function __construct() {
        $this->loadAlter();
        $this->loadEmployee();
        $this->initTaskStates();
        //TODO: move it into search sub
        //$this->initPhotostorage();
        //$this->loadWarehouseOutcomes();
        //$this->initADcomments();
    }

    /**
     * Loads system alter config into protected prop
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads available employee from database
     * 
     * @return void
     */
    protected function loadEmployee() {
        $this->allActiveEmployee = ts_GetActiveEmployee();
    }

    /**
     * Inits TaskStates instance for further usage
     * 
     * @return void
     */
    protected function initTaskStates() {
        $this->taskStates = new TaskStates();
    }

    /**
     * Inits photostorage instance if required
     * 
     * @return void
     */
    protected function initPhotostorage() {
        if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $this->photoStorage = new PhotoStorage('TASKMAN');
        }
    }

    /**
     * Inits ADcomments instance for further usage
     * 
     * @return void
     */
    protected function initADcomments() {
        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            $this->adComments = new ADcomments('TASKMAN');
        }
    }

    /**
     * Loads existing warehouse outcome operations from database
     * 
     * @return void
     */
    protected function loadWarehouseOutcomes() {
        if ($this->altCfg['WAREHOUSE_ENABLED']) {
            $outcomes = new NyanORM('wh_out');
            $outcomes->where('desttype', '=', 'task');
            $all = $outcomes->getAll();
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    if (isset($this->allWarehouseOutcomes[$each['destparam']])) {
                        $this->allWarehouseOutcomes[$each['destparam']] ++;
                    } else {
                        $this->allWarehouseOutcomes[$each['destparam']] = 1;
                    }
                }
            }
        }
    }

    /**
     * Render primary module controls aka filters
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';

        /**
         * Here some preset defaults requires for warehouse/salary/project managers
         */
        /**
         * + photo / without photo
         * + warehouse
         * - adcomments
         * - employee
         * + taskstates [done/undone etc]
         * 
         */
        $stateFlag = ubRouting::post(self::PROUTE_STATE);
        $inputs = wf_SelectorAC(self::PROUTE_STATE, $this->taskStates->getStateTypes(), __('Task state'), $stateFlag, false) . ' ';

        if ($this->altCfg['PHOTOSTORAGE_ENABLED']) {
            $photoFlag = ubRouting::post(self::PROUTE_PHOTO);
            $inputs .= wf_SelectorAC(self::PROUTE_PHOTO, array('1' => __('Yes'), '0' => __('No')), __('Image'), $photoFlag, false) . ' ';
        }

        if ($this->altCfg['WAREHOUSE_ENABLED']) {
            $whFlag = ubRouting::post(self::PROUTE_WAREHOUSE);
            $inputs .= wf_SelectorAC(self::PROUTE_WAREHOUSE, array('1' => __('Yes'), '0' => __('No')), __('Additionally spent materials'), $whFlag, false) . ' ';
        }

        if ($this->altCfg['ADCOMMENTS_ENABLED']) {
            //TODO
        }



        $inputs .= wf_Submit(__('Search'));

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return($result);
    }

}

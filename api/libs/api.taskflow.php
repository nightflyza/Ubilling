<?php

class TaskFlow {

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
     * Predefined routes/URLs/etc
     */
    const URL_ME = '?module=taskflow';
    const PROUTE_STATE = 'searchtaskstate';

    public function __construct() {
        $this->loadEmployee();
        $this->initTaskStates();
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
     * Render primary module controls aka filters
     * 
     * @return string
     */
    public function renderControls() {
        $result = '';
        /**
         * - photo / without photo
         * - warehouse
         * - adcomments
         * - employee
         * + taskstates [done/undone etc]
         * 
         */
        $inputs = wf_Selector(self::PROUTE_STATE, $this->taskStates->getStateTypes(), __('Task state'), '', false);

        $result .= wf_Form('', 'POST', $inputs, 'glamour');

        return($result);
    }

}

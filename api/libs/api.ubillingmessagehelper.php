<?php

/**
 * System message helper class
 */
class UbillingMessageHelper {

    /**
     * Default item deletion alert here
     *
     * @var string
     */
    protected $deleteAlert = '';

    /**
     * Default item editing alert here
     *
     * @var string
     */
    protected $editAlert = '';

    public function __construct() {
        $this->setDeleteAlert();
        $this->setEditAlert();
    }

    /**
     * Sets localized string as default deletion warning
     */
    protected function setDeleteAlert() {
        $this->deleteAlert = __('Removing this may lead to irreparable results');
    }

    /**
     * Sets localized string as default edit warning
     */
    protected function setEditAlert() {
        $this->editAlert = __('Are you serious');
    }

    /**
     * Returns localized deletion warning message
     * 
     * @return string
     */
    public function getDeleteAlert() {
        return ($this->deleteAlert);
    }

    /**
     * Returns localized editing warning message
     * 
     * @return string
     */
    public function getEditAlert() {
        return ($this->editAlert);
    }

    /**
     * Returns styled message
     * 
     * @param string $data text message for styling
     * @param string $style error, warning, info, success
     * @param string $opts custom container options
     * 
     * @return string
     */
    public function getStyledMessage($data, $style, $opts = '') {
        $class = 'alert_' . $style;
        $result = wf_tag('span', false, $class, $opts) . $data . wf_tag('span', true);
        return ($result);
    }

}

<?php

class widget_fastsms extends TaskbarWidget {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * UbillingSMS object placeholder
     *
     * @var object
     */
    protected $sms = '';

    const TASKBAR_URL = '?module=taskbar';

    /**
     * Creates new widget_fastsms instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadAlter();
        $this->initSMS();
    }

    /**
     * Loads system alter config into protected prop for further usage
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
     * Initalizes system SMS sending abstraction layer
     * 
     * @return void
     */
    protected function initSMS() {
        $this->sms = new UbillingSMS();
    }

    /**
     * Returns SMS sending form
     * 
     * @return string
     */
    protected function smsSendForm() {
        $result = '';
        $inputs = wf_TextInput('fastsmsnumber', __('Mobile'), '', true, '20');
        $inputs.= wf_TextArea('fastsmsmessage', '', '', true, '30x5');
        $inputs.= wf_CheckInput('fastsmstranslit', __('Forced transliteration'), true, true);
        $inputs.= wf_Submit(__('Create'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');

        //displaying sending form as button or inline taskbar form
        if ($this->altCfg['WIDGET_FASTSMS'] == 1) {
            $result = wf_modalAuto(wf_img('skins/icon_mobile.gif', __('Create new SMS')) . ' ' . __('Send SMS'), __('Create new SMS'), $form, 'ubButton');
        }

        if ($this->altCfg['WIDGET_FASTSMS'] == 2) {
            $result = $form;
        }

        return ($result);
    }

    /**
     * Catches form sending request and performs SMS queue storing
     * 
     * @return void
     */
    protected function catchSMSSending() {
        if (wf_CheckPost(array('fastsmsnumber', 'fastsmsmessage'))) {
            $translitFlag = (wf_CheckPost(array('fastsmstranslit'))) ? true : false;
            $this->sms->sendSMS($_POST['fastsmsnumber'], $_POST['fastsmsmessage'], $translitFlag, 'WIDGET_FASTSMS');
            //preventing sms resending on page refresh
            rcms_redirect(self::TASKBAR_URL);
        }
    }

    /**
     * Runs and renders widget code
     * 
     * @return string
     */
    public function render() {
        $result = '';
        if ($this->altCfg['SENDDOG_ENABLED']) {
            //performs sms sending if required
            $this->catchSMSSending();
            //rendering sending form
            $result.=$this->widgetContainer($this->smsSendForm());
        } else {
            $messages = new UbillingMessageHelper();
            $result = $messages->getStyledMessage(__('SendDog') . ' ' . __('Disabled') . ' :(', 'error');
        }
        return ($result);
    }

}

?>
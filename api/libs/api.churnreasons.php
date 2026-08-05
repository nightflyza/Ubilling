<?php

class ChurnReasons {

    /**
     * Stigma object placeholder
     *
     * @var object
     */
    protected $reasonsStigma = '';

    /**
     * Current instance user login
     *
     * @var string
     */
    protected $userLogin = '';

    /**
     * System message helper object placeholder
     *
     * @var object
     */
    protected $messages='';

    // some other predefined stuff
    const STIGMA_SCOPE = 'CHURNREASONS';
    const LOG_TABLE='churnreasons';
    const URL_ME='?module=churnreasonedit';
    const ROUTE_LOGIN='username';

    public function __construct($userLogin='') {
        $this->setUserLogin($userLogin);
        $this->initMessages();
        $this->initStigma();
    }

    /**
     * Initializes system message helper object
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Sets user login
     *
     * @param string $userLogin
     * 
     * @return void
     */
    protected function setUserLogin($userLogin='') {
        if (!empty($userLogin)) {
            $this->userLogin = ubRouting::filters($userLogin,'login');
        }
    }

    /**
     * Initializes main stigma object
     *
     * @return void
     */
    protected function initStigma() {
        $this->reasonsStigma = new Stigma(self::STIGMA_SCOPE, $this->userLogin);
    }

    /**
     * Renders text render for churn reasons
     *
     * @param string $userLogin
     * 
     * @return string
     */
    public function textRender($userLogin='') {
        $result='';
        if (!empty($userLogin)) {
           $result=$this->reasonsStigma->textRender($userLogin);
        }
        return($result);
    }

    /**
     * Renders churn controller
     *
     * @return string
     */
    public function renderChurnController() {
        $result='';
        if (!empty($this->userLogin)) {
          $this->reasonsStigma->stigmaController('CUSTOM:'.self::LOG_TABLE);
          $result.=$this->reasonsStigma->render($this->userLogin);

        } else {
            $result.=$this->messages->getStyledMessage(__('User login is required'),'error');
        }

        return($result);
    }

    /**
     * Renders basic report for churn reasons
     *
     * @return string
     */
    public function renderBasicReport() {
        $result='';
        $result.=$this->reasonsStigma->renderBasicReport();
        return($result);
    }

    /**
     * Renders extended report for churn reasons
     *
     * @param array $options
     *
     * @return string
     */
    public function renderExtendedReport($options = array()) {
        $result = '';
        if (!is_array($options)) {
            $options = array();
        }
        if (!isset($options['linkMode'])) {
            $options['linkMode'] = 'users';
        }
        if (!isset($options['showDateForm'])) {
            $options['showDateForm'] = true;
        }
        $result .= $this->reasonsStigma->renderExtendedReport($options);
        return($result);
    }
}
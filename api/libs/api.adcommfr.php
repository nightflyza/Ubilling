<?php

/**
 * ADcomments basic fast reply implementation class
 */
class ADcommFR {
    /**
     * Contains alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Current instance administrator login
     *
     * @var string
     */
    protected $myLogin = '';

    /**
     * Contains fast replies array as idx=>replyText
     *
     * @var array
     */
    protected $fastRepliesList = array();


    /**
     * Contains available princess list logins as login=>login
     *
     * @var array
     */
    protected $princessList = array();


    public function  __construct() {
        $this->loadAlter();
        $this->setmyLogin();
        $this->loadPrincessList();
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
     * Preloads princess list from config option
     * 
     * @return void
     */
    protected function loadPrincessList() {
        if (isset($this->altCfg['PRINCESS_LIST'])) {
            if (!empty($this->altCfg['PRINCESS_LIST'])) {
                $princessRaw = explode(',', $this->altCfg['PRINCESS_LIST']);
                if (!empty($princessRaw)) {
                    foreach ($princessRaw as $io => $eachPrincess) {
                        $eachPrincess = trim($eachPrincess);
                        $this->princessList[$eachPrincess] = $eachPrincess;
                    }
                }
            }
        }
    }


    /**
     * Checks is me an princess or not?
     * 
     * @return bool
     */
    public function iAmPrincess() {
        $result = false;
        if (isset($this->princessList[$this->myLogin])) {
            $result = true;
        }
        return ($result);
    }


    /**
     * Sets current administrator login into private prop
     * 
     * @return void
     */
    protected function setmyLogin() {
        $this->myLogin = whoami();
    }

    /**
     * Sets available fast replies
     *
     * @param array $replyArr
     * 
     * @return void
     */
    protected function setFastReplies($replyArr) {
        if (!empty($replyArr)) {
            $this->fastRepliesList = $replyArr;
        }
    }


    /**
     * Renders princess fast replies form
     *
     * @return string
     */
    public function renderPrincessFastReplies() {
        $result = '';
        $this->loadAlter();
        $this->loadPrincessList();
        if ($this->iAmPrincess()) {
            if (@$this->altCfg['PRINCESS_FAST_REPLIES']) {
                $replyArr = explode(',', $this->altCfg['PRINCESS_FAST_REPLIES']);
                $this->setFastReplies($replyArr);
                if (!empty($this->fastRepliesList)) {
                    $result .= wf_tag('div', false, '', 'style="float: left; margin-left: 15px;"');
                    foreach ($this->fastRepliesList as $io => $eachReply) {
                        $btnLabel = wf_img('skins/icon_ok.gif') . ' ' . $eachReply;
                        $btnStyle = 'style="width: 100%; text-align: left;"';
                        $inputs = wf_SubmitClassed($eachReply, 'frButton', ADcomments::PROUTE_NEW_TEXT, $btnLabel, '', $btnStyle);
                        $result .= wf_Form('', 'POST', $inputs, '');
                        $result .= wf_delimiter(0);
                    }
                    $result .= wf_tag('div', true);
                }
            }
        }
        return ($result);
    }
}

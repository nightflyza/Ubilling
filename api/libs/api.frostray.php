<?php

/**
 * FrostRay draft implementation
 */
class FrostRay {
    protected $enableFlag = false;
    protected $tagId = 0;
    protected $limit = 0;
    protected $ip = '';
    protected $userLogin = '';

    const PROCESS_PID='FROSTRAY';
    const OPTION_ENABLED='FROSTRAY_ENABLED';
    const OPTION_TAGID='FROSTRAY_TAGID';
    const OPTION_LIMIT='FROSTRAY_LIMIT';

    public function __construct($ip = '') {
        $this->loadConfig();
        $this->setIP($ip);
        $this->setUserLogin();
    }

    protected function setIP($ip = '') {
        $this->ip = ubRouting::filters($ip, 'mres');
    }

    protected function loadConfig() {
        global $ubillingConfig;
        $this->enableFlag = $ubillingConfig->getAlterParam(self::OPTION_ENABLED);
        $this->tagId = $ubillingConfig->getAlterParam(self::OPTION_TAGID);
        $this->limit = $ubillingConfig->getAlterParam(self::OPTION_LIMIT);
    }

    protected function setUserLogin() {
        if (!empty($this->ip)) {
            $this->userLogin = zb_UserGetLoginByIp($this->ip);
        }
    }

    public function frost() {
        global $billing;
        if ($this->enableFlag and $this->userLogin and $this->tagId and $this->limit) {
            $frostedFlag = false;
            $userTags = zb_UserGetAllTagsUnique($this->userLogin);
            if (!empty($userTags)) {
                foreach ($userTags[$this->userLogin] as $eachTagRec => $eachTagId) {
                    if ($eachTagId == $this->tagId) {
                        $frostedFlag = true;
                    }
                }
            }

            if (!$frostedFlag) {
                $currentSpeed = zb_UserGetSpeedOverride($this->userLogin);
                if (!$currentSpeed) {
                    zb_UserSetSpeedOverride($this->userLogin, $this->limit);
                    $billing->resetuser($this->userLogin);
                    stg_add_user_tag($this->userLogin, $this->tagId);
                }
            }
        }
    }

    public function defrost() {
        global $billing;
        if ($this->enableFlag and $this->userLogin and $this->tagId and $this->limit) {
            $frostedFlag = false;
            $userTags = zb_UserGetAllTagsUnique($this->userLogin);
            if (!empty($userTags)) {
                foreach ($userTags[$this->userLogin] as $eachTagRec => $eachTagId) {
                    if ($eachTagId == $this->tagId) {
                        $frostedFlag = true;
                    }
                }
            }

            if ($frostedFlag) {
                $currentSpeed = zb_UserGetSpeedOverride($this->userLogin);
                if ($currentSpeed) {
                    zb_UserSetSpeedOverride($this->userLogin, 0);
                    $billing->resetuser($this->userLogin);
                    stg_del_user_tagid($this->userLogin, $this->tagId);
                }
            }
        }
    }
}

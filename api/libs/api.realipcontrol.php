<?php

/**
 * Inactive users with white IP report
 */
class RealIPControl {

    /**
     * Contains mask of gray IP networks
     *
     * @var string
     */
    protected $grayIpMask = 'RFC';

    /**
     * Months debt count to free unused IP addresses
     *
     * @var int
     */
    protected $debtLimit = 3;

    /**
     * Contains all available users userdata
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains all available tariff prices
     *
     * @var array
     */
    protected $allTrariffPrices = array();

    /**
     * Local message helper object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Some predefined keys for further usage
     */
    const OPT_GRAYIP = 'RIC_GRAYMASK';
    const OPT_DEBTLIM = 'RIC_DEBTLIMIT';
    const PROUTE_GRAYMASK = 'newgrayipmask';
    const PROUTE_DEBTLIM = 'newdebtlimit';
    const URL_ME = '?module=realipcontrol';

    public function __construct() {
        $this->initMessages();
        $this->loadSettings();
        $this->loadUserData();
        $this->loadTariffPrices();
    }

    /**
     * Inits message helper object instance
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Loads all available users data
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllStargazerData();
    }

    protected function loadTariffPrices() {
        $this->allTrariffPrices = zb_TariffGetPricesAll();
        if (!empty($this->allTrariffPrices)) {
            $tariffPeriods = zb_TariffGetPeriodsAll();
            foreach ($this->allTrariffPrices as $tariffName => $tariffFee) {
                if (isset($tariffPeriods[$tariffName])) {
                    $tariffPeriod = $tariffPeriods[$tariffName];
                    if ($tariffPeriod == 'day') {
                        // I'm too lazy to think, so we'll have like 30 days in a month. 
                        // And I don't care if you think otherwise.
                        $tariffMontlyFee = $tariffFee * 30;
                        // Yeah.. now we just updating tariff fees property with approximate monthly fee
                        $this->allTrariffPrices[$tariffName] = $tariffMontlyFee;
                    }
                }
            }
        }
    }

    /**
     * Loads some settings from database or sets some default values
     * 
     * @return void
     */
    protected function loadSettings() {
        $optionGrayIpMask = zb_StorageGet(self::OPT_GRAYIP);
        if (empty($optionGrayIpMask)) {
            //initial settings on 1st usage
            zb_StorageSet(self::OPT_GRAYIP, $this->grayIpMask);
        } else {
            $this->grayIpMask = $optionGrayIpMask;
        }

        $optionFeeLimit = zb_StorageGet(self::OPT_DEBTLIM);
        if (empty($optionFeeLimit)) {
            zb_StorageSet(self::OPT_DEBTLIM, $this->debtLimit);
        } else {
            $this->debtLimit = $optionFeeLimit;
        }
    }

    /**
     * Renders module configuration interface
     * 
     * @return string
     */
    public function renderConfigForm() {
        $result = '';
        $helpLabel = __('For example') . ': 192.168.0.,172.16.,10.14.88. ' . __('(separator - comma)') . '. ';
        $helpLabel .= __('Or') . ' RFC ' . __('for all');
        $helpLabel = wf_tag('abbr', false, '', 'title="' . $helpLabel . '"') . wf_img('skins/question.png') . wf_tag('abbr', true);
        $inputs = $helpLabel . ' ';
        $inputs .= wf_TextInput(self::PROUTE_GRAYMASK, __('Mask for non real IP in your network'), $this->grayIpMask, false, 20) . ' ';
        $inputs .= wf_TextInput(self::PROUTE_DEBTLIM, __('Month limit to withdraw real IP'), $this->debtLimit, false, 3);
        $inputs .= wf_Submit(__('Save'));
        $result .= wf_Form('', 'POST', $inputs, 'glamour');
        return($result);
    }

    /**
     * Updates module configuration in database if required
     * 
     * @return void
     */
    public function saveSettings() {
        if (ubRouting::checkPost(array(self::PROUTE_GRAYMASK, self::PROUTE_DEBTLIM))) {
            $newMask = ubRouting::post(self::PROUTE_GRAYMASK, 'mres');
            if ($newMask != $this->grayIpMask) {
                zb_StorageSet(self::OPT_GRAYIP, $newMask);
            }
            $newDebtLimit = ubRouting::post(self::PROUTE_DEBTLIM, 'int');
            if ($newDebtLimit != $this->debtLimit) {
                zb_StorageSet(self::OPT_DEBTLIM, $newDebtLimit);
            }
        }
    }

    /**
     * Checks is IP private or public
     * 
     * @param string $ip
     * 
     * @return int 0/1
     */
    public function isPrivateIp($ip) {
        $pattern = '$(10(\.(25[0-5]|2[0-4][0-9]|1[0-9]{1,2}|[0-9]{1,2})){3}|((172\.(1[6-9]|2[0-9]|3[01]))|192\.168)(\.(25[0-5]|2[0-4][0-9]|1[0-9]{1,2}|[0-9]{1,2})){2})$';
        $result = preg_match($pattern, $ip);
        return($result);
    }

    /**
     * Renders report of users which need withdraw real IP
     * 
     * @return void
     */
    public function renderReport() {
        $result = '';
        $tmpArr = array();
        $tmpMbAlive = array();
        if (!empty($this->allUserData)) {
            foreach ($this->allUserData as $io => $each) {
                if (!empty($each['IP'])) {
                    $userLogin = $each['login'];
                    $userTariff = $each['Tariff'];
                    //Real IP?
                    if ($this->grayIpMask == 'RFC') {
                        $realIpFlag = ($this->isPrivateIp($each['IP'])) ? false : true;
                    } else {
                        $realIpFlag = true;
                        $wildcardTmp = explode(',', $this->grayIpMask);
                        if (!empty($wildcardTmp)) {
                            foreach ($wildcardTmp as $maskIndex => $eachGrayIpMask) {
                                $eachGrayIpMask = trim($eachGrayIpMask);
                                if (!empty($eachGrayIpMask)) {
                                    if (ispos($each['IP'], $eachGrayIpMask)) {
                                        $realIpFlag = false;
                                    }
                                }
                            }
                        }
                    }

                    if ($realIpFlag) {
                        //Tariff exists
                        if (isset($this->allTrariffPrices[$userTariff])) {
                            $tariffPrice = $this->allTrariffPrices[$userTariff];
                            //Tariff isnt free
                            if ($tariffPrice > 0) {
                                $maxDebt = '-' . ($this->debtLimit * $tariffPrice);
                                $curMoneyLimit = $each['Cash'] + $each['Credit'];
                                if ($curMoneyLimit <= $maxDebt) {
                                    if ($each['U0'] == 0) {
                                        $tmpArr[$userLogin] = $userLogin;
                                    } else {
                                        $tmpMbAlive[$userLogin] = $userLogin;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            show_window(__('May be withdraw some IPs from this users'), web_UserArrayShower($tmpArr));
            show_window(__('This users is debtors but seems it alive'), web_UserArrayShower($tmpMbAlive));
        } else {
            show_window('', $this->messages->getStyledMessage(__('Nothing to show'), 'warning'));
        }
        return($result);
    }

}

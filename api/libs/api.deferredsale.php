<?php

/**
 * Deferred sales basic implementation
 */
class DeferredSale {

    /**
     * Minimum sale term in months
     *
     * @var int
     */
    protected $minMonth = 1;

    /**
     * Maximum sale term in months
     *
     * @var int
     */
    protected $maxMonth = 12;

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $login = '';

    /**
     * Some other predefined stuff
     */
    const PROUTE_RUN = 'dsalerun';
    const PROUTE_SUMM = 'dsalesumm';
    const PROUTE_TERM = 'dsaleterm';
    const PROUTE_NOTE = 'dsalenote';
    const NOTE_PREFIX = 'DEFSALE:';

    public function __construct($userLogin) {
        $this->setLogin($userLogin);
        $this->setOptions();
    }

    /**
     * Sets some current instance options
     *
     * @return void
     */
    protected function setOptions() {
        global $ubillingConfig;
        $defOptionState = $ubillingConfig->getAlterParam('DEFERRED_SALE_ENABLED', 0);
        $defOptionState = ubRouting::filters($defOptionState, 'int');
        if ($defOptionState > 1) {
            $this->maxMonth = $defOptionState;
        }
    }

    /**
     * Sets current instance user login
     * 
     * @param string $userLogin
     * 
     * @return void
     */
    protected function setLogin($userLogin) {
        $this->login = $userLogin;
    }

    /**
     * Returns months term array for term selector
     * 
     * @return array
     */
    protected function getTermsArr() {
        $result = array();
        for ($i = $this->minMonth; $i <= $this->maxMonth; $i++) {
            $result[$i] = $i;
        }
        return ($result);
    }

    /**
     * Returns deferred sale form
     * 
     * @return string
     */
    public function renderForm() {
        $result = '';
        //
        //         m
        //        / \
        //       .   \
        //      ( O   \      This pretty mushroom is for you           
        //     /  _   O\     When it's spreading in the clouds
        //    /  (_)    .
        //   .       O   \
        //  (__O__________)
        //   \((((())))))/
        //       /  X
        //      |   / 
        //_m.n.(___)nmm._.,n._
        //
        $inputs = wf_HiddenInput(self::PROUTE_RUN, 'true');
        $inputs .= wf_TextInput(self::PROUTE_SUMM, __('Withdraw from user account'), '', true, 5, 'finance');
        $inputs .= wf_Selector(self::PROUTE_TERM, $this->getTermsArr(), __('for so many months'), '', true);
        $inputs .= wf_TextInput(self::PROUTE_NOTE, __('Notes'), '', true, 30);
        $inputs .= wf_delimiter(0);
        $inputs .= wf_Submit(__('Charge'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
        $result .= ' ' . wf_modalAuto(wf_img_sized('skins/letter-r16.png', __('Deferred sale'), '10'), __('Deferred sale'), $form);
        return ($result);
    }

    /**
     * Catches sale request and performs sale
     * 
     * @return void/string on error
     */
    public function catchRequest() {
        $result = '';
        if (ubRouting::checkPost(array(self::PROUTE_RUN, self::PROUTE_SUMM, self::PROUTE_TERM))) {
            $chargeSumm = ubRouting::post(self::PROUTE_SUMM, 'mres');
            $chargeTerm = ubRouting::post(self::PROUTE_TERM, 'int');
            $chargeNoteRaw = ubRouting::post(self::PROUTE_NOTE, 'mres');
            if (zb_checkMoney($chargeSumm)) {
                if ($chargeTerm) {
                    $dealWithIt = new DealWithIt();
                    $chargeSumm = abs($chargeSumm); //always positive
                    $chargeFee = $chargeSumm / $chargeTerm; //calculating monthly fee
                    $chargeFee = round($chargeFee, 2); //rounded to cents
                    $chargeFee = '-' . $chargeFee; //yes, its fee

                    for ($monthCount = 1; $monthCount <= $chargeTerm; $monthCount++) {
                        $chargeNote = self::NOTE_PREFIX . $chargeNoteRaw . ' #' . $monthCount;
                        //calculating each next month start date
                        $targetChargeDate = date('Y-m-d', mktime(0, 0, 0, date('m') + $monthCount, 1, date('Y')));
                        //planning tasks
                        $dealWithIt->createTask($targetChargeDate, $this->login, 'corrcash', $chargeFee, $chargeNote);
                    }
                    log_register('DEFSALE (' . $this->login . ') SCHEDULED `' . $chargeSumm . '` FOR `' . $chargeTerm . '` MONTHS');

                    //preventing charge duplicates
                    if (ubRouting::checkGet('module')) {
                        $currentModule = ubRouting::get('module');

                        $redirectUrl = '';
                        if ($currentModule == 'userprofile') {
                            $redirectUrl = '?module=userprofile&username=' . $this->login;
                        }

                        if ($currentModule == 'addcash') {
                            $redirectUrl = '?module=addcash&username=' . $this->login . '#cashfield';
                        }

                        if (!empty($redirectUrl)) {
                            //must be an header redirect to avoid fails with URLs that contains #anchor
                            ubRouting::nav($redirectUrl, true);
                        }
                    }
                }
            } else {
                $result .= wf_modalOpened(__('Error'), __('Wrong format of a sum of money to pay'), '400', '200');
                log_register('DEFSALE (' . $this->login . ') WRONG SUMM `' . $chargeSumm . '`');
            }
        }
        return ($result);
    }
}

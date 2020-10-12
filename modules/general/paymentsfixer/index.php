<?php

if (cfr('ROOT')) {


    class PaymentFixer {

        /**
         * Contains system alter.ini config as key=>value
         *
         * @var array
         */
        protected $altCfg = array();

        /**
         * Contains system billing.ini config as key=>value
         *
         * @var array
         */
        protected $billCfg = array();

        /**
         * Contains all available users data as login=>userData
         *
         * @var array
         */
        protected $allUserData = array();

        /**
         * System messages helper object placeholder
         *
         * @var object
         */
        protected $messages = '';

        /**
         * Payments table data model placeholder
         *
         * @var object
         */
        protected $payments = '';

        /**
         * Date to detect unprocessed transactions
         *
         * @var string
         */
        protected $checkDate = '';

        /**
         * Routing etc...
         */
        const URL_PROFILE = '?module=userprofile&username=';
        const URL_ME = '?module=paymentsfixer';

        public function __construct() {
            $this->initMessages();
            $this->loadConfigs();
            $this->loadUserData();
            $this->initDataModels();
        }

        /**
         * Loads existing user data from database
         * 
         * @return void
         */
        protected function loadUserData() {
            $this->allUserData = zb_UserGetAllData(); //here must be an actual data
        }

        /**
         * Inits message helper instance
         * 
         * @return void
         */
        protected function initMessages() {
            $this->messages = new UbillingMessageHelper();
        }

        /**
         * Loads all required configs into protected props for further usage
         * 
         * @global object $ubillingConfig
         * 
         * @return void
         */
        protected function loadConfigs() {
            global $ubillingConfig;
            $this->altCfg = $ubillingConfig->getAlter();
            $this->billCfg = $ubillingConfig->getBilling();
        }

        /**
         * Inits required data models for further usage
         * 
         * @return void
         */
        protected function initDataModels() {
            $this->payments = new NyanORM('payments');
        }

        /**
         * Sets required date into protected prop
         * 
         * @param string $date
         * 
         * @return void
         */
        public function setDate($date = '') {
            if (empty($date)) {
                $this->checkDate = curdate();
            } else {
                $this->checkDate = $date;
            }
        }

        /**
         * Returns array of positive payments by selected date
         * 
         * @return array
         */
        protected function getPayments() {
            $result = array();
            if (!empty($this->checkDate)) {
                $this->payments->where('date', 'LIKE', $this->checkDate . '%');
                $this->payments->where('summ', '>', '0');
                $result = $this->payments->getAll();
            }
            return($result);
        }

        /**
         * Returns all available cash operations by selected date
         * 
         * @return string
         */
        protected function getStargazerOps() {
            $result = '';
            if (!empty($this->checkDate)) {
                $sudo = $this->billCfg['SUDO'];
                $cat = $this->billCfg['CAT'];
                $stgLog = $this->altCfg['STG_LOG_PATH'];
                $grep = $this->billCfg['GREP'];
                $command = $sudo . ' ' . $cat . ' ' . $stgLog . ' | ' . $grep . ' ' . $this->checkDate . ' | ' . $grep . ' cash | ' . $grep . ' -v fee';
                $result .= shell_exec($command);
            }
            return($result);
        }

        /**
         * Returns array of payments that was not detected in stargazer log on selected date
         * 
         * @return array
         */
        public function getFailedPayments() {
            $stgDataRaw = $this->getStargazerOps();
            $allPayments = $this->getPayments();
            $result = array();
            if (!empty($allPayments)) {
                foreach ($allPayments as $io => $eachPayment) {
                    if (!ispos($eachPayment['note'], 'MOCK:')) {
                        if (!ispos($stgDataRaw, $eachPayment['login'])) {
                            $result[$eachPayment['id']] = $eachPayment;
                        }
                    }
                }
            }
            return($result);
        }

        /**
         * Renders failed payments list with some controls
         * 
         * @return string
         */
        public function renderFailedPayments() {
            $result = '';
            $allCashtypes = zb_CashGetAllCashTypes();
            $allFailed = $this->getFailedPayments();
            if (!empty($allFailed)) {
                $cells = wf_TableCell(__('ID'));
                $cells .= wf_TableCell(__('Date'));
                $cells .= wf_TableCell(__('Sum'));
                $cells .= wf_TableCell(__('Previous') . ' ' . __('Balance'));
                $cells .= wf_TableCell(__('Cash type'));
                $cells .= wf_TableCell(__('User'));
                $cells .= wf_TableCell(__('Current Cash state'));
                $cells .= wf_TableCell(__('Notes'));
                $cells .= wf_TableCell(__('Actions'));
                $rows = wf_TableRow($cells, 'row1');
                foreach ($allFailed as $io => $each) {
                    @$userData = $this->allUserData[$each['login']];
                    $cells = wf_TableCell($each['id']);
                    $cells .= wf_TableCell($each['date']);
                    $cells .= wf_TableCell($each['summ']);
                    $cells .= wf_TableCell($each['balance']);
                    $cells .= wf_TableCell(__(@$allCashtypes[$each['cashtypeid']]));
                    $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . @$userData['fulladress']);
                    $cells .= wf_TableCell($userLink);
                    $cells .= wf_TableCell($userData['Cash']);
                    $cells .= wf_TableCell($each['note']);
                    $actionControls = '';
                    $actionControls .= wf_JSAlert(self::URL_ME . '&paymentdelete=' . $each['id'], web_delete_icon(), $this->messages->getDeleteAlert()) . ' ';
                    $actionControls .= wf_JSAlert(self::URL_ME . '&fixpaymentid=' . $each['id'], wf_img('skins/icon_repair.gif', __('Fix')), $this->messages->getEditAlert() . ' ' . __('Add cash') . '?') . ' ';
                    $cells .= wf_TableCell($actionControls);
                    $rows .= wf_TableRow($cells, 'row5');
                }
                $result .= wf_TableBody($rows, '100%', 0, 'sortable');
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
            }
            return($result);
        }

        /**
         * Renders users that failed with their payments
         * 
         * @return string
         */
        public function renderDebtorUsers() {
            $result = '';
            $allPayments = $this->getPayments();
            $debtorsTmp = array();
            if (!empty($allPayments)) {
                foreach ($allPayments as $io => $each) {
                    if (isset($this->allUserData[$each['login']])) {
                        $userData = $this->allUserData[$each['login']];
                        if ($userData['Cash'] < '-' . $userData['Credit']) {
                            if (!ispos($each['note'], 'MOCK:')) {
                                $debtorsTmp[] = $each;
                            }
                        }
                    }
                }

                if (!empty($debtorsTmp)) {
                    $allCashtypes = zb_CashGetAllCashTypes();
                    $allTariffPrices = zb_TariffGetPricesAll();

                    $cells = wf_TableCell(__('Sum'));
                    $cells .= wf_TableCell(__('Previous') . ' ' . __('Balance'));
                    $cells .= wf_TableCell(__('Current Cash state'));
                    $cells .= wf_TableCell(__('Cash type'));
                    $cells .= wf_TableCell(__('User'));
                    $cells .= wf_TableCell(__('Tariff') . ' / ' . __('Fee'));
                    $cells .= wf_TableCell(__('Notes'));
                    $cells .= wf_TableCell(__('Reason'));
                    $rows = wf_TableRow($cells, 'row1');

                    foreach ($debtorsTmp as $io => $each) {
                        $userData = $this->allUserData[$each['login']];
                        $userTariff = $userData['Tariff'];
                        $userTariffPrice = (isset($allTariffPrices[$userTariff])) ? $allTariffPrices[$userTariff] : 0;

                        $cells = wf_TableCell($each['summ']);
                        $cells .= wf_TableCell($each['balance']);
                        $cells .= wf_TableCell($userData['Cash']);

                        $cells .= wf_TableCell(__(@$allCashtypes[$each['cashtypeid']]));
                        $userLink = wf_Link(self::URL_PROFILE . $each['login'], web_profile_icon() . ' ' . @$userData['fulladress']);
                        $cells .= wf_TableCell($userLink);
                        $cells .= wf_TableCell($userTariff . ' / ' . $userTariffPrice);
                        $cells .= wf_TableCell($each['note']);
                        $reason = '';

                        if ($each['summ'] < $userTariffPrice) {
                            $reason .= __('Less than tariff price') . ' ';
                        } else {
                            if ($userData['Cash'] < '-' . $userTariffPrice) {
                                $reason .= __('User was inactive for a long time') . ' ';
                            }
                        }
                        $cells .= wf_TableCell($reason);
                        $rows .= wf_TableRow($cells, 'row5');
                    }
                    $result .= wf_TableBody($rows, '100%', 0, 'sortable');
                } else {
                    $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
                }
            } else {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'success');
            }
            return($result);
        }

        /**
         * Rdenders default date setup form
         * 
         * @param string $date
         * 
         * @return string
         */
        public function renderDateSelectorForm($date) {
            $result = '';

            $inputs = wf_DatePickerPreset('checkdate', $date, true) . ' ';
            $inputs .= wf_Submit(__('Find'));
            $result .= wf_Form('', 'POST', $inputs, 'glamour');
            return($result);
        }

        /**
         * Adds cash on stargazer user account, pushes payment to log.
         * 
         * @param int $paymentId
         * 
         * @return void/string on error
         */
        public function fixPayment($paymentId) {
            $result = '';
            $paymentId = ubRouting::filters($paymentId, 'int');
            if (!empty($paymentId)) {
                $this->payments->where('id', '=', $paymentId);
                $paymentData = $this->payments->getAll();
                if (!empty($paymentData)) {
                    $paymentData = $paymentData[0];
                    zb_CashAdd($paymentData['login'], $paymentData['summ'], 'correct', $paymentData['cashtypeid'], 'PAYFIXED:' . $paymentId);
                    $sudo = $this->billCfg['SUDO'];
                    $stgLog = $this->altCfg['STG_LOG_PATH'];
                    $command = 'echo "' . $paymentData['date'] . ' ' . $paymentData['login'] . ' cash fixed manually summ:' . $paymentData['summ'] . '" | ' . $sudo . ' tee -a ' . $stgLog;
                    shell_exec($command);
                } else {
                    $result .= __('Something went wrong') . ': EX_EMPTYPAYMENTDATA';
                }
            } else {
                $result .= __('Something went wrong') . ': EX_EMPTYPAYMENTID';
            }
            return($result);
        }

    }

    $alter = $ubillingConfig->getAlter();
    $checkDate = (ubRouting::checkPost('checkdate')) ? ubRouting::post('checkdate', 'mres') : curdate();
    $fixer = new PaymentFixer();

    //payment correction
    if (ubRouting::checkGet('fixpaymentid')) {
        $repairResult = $fixer->fixPayment(ubRouting::get('fixpaymentid'));
        if (empty($repairResult)) {
            ubRouting::nav($fixer::URL_ME);
        } else {
            show_error($repairResult);
        }
    }

    //payment deletion
    if (ubRouting::checkGet('paymentdelete')) {
        $deletePaymentId = ubRouting::get('paymentdelete', 'int');
        $deletingAdmins = array();
        $iCanDeletePayments = false;
        $currentAdminLogin = whoami();
        $paymentsDb = new nya_payments();
        $paymentsDb->selectable(array('id', 'login'));
        $paymentsDb->where('id', '=', $deletePaymentId);
        $login = $paymentsDb->getAll();
        $login = @$login[0]['login'];
        if (!empty($login)) {
            //extract delete admin logins
            if (!empty($alter['CAN_DELETE_PAYMENTS'])) {
                $deletingAdmins = explode(',', $alter['CAN_DELETE_PAYMENTS']);
                $deletingAdmins = array_flip($deletingAdmins);
            }

            $iCanDeletePayments = (isset($deletingAdmins[$currentAdminLogin])) ? true : false;
            //right check
            if ($iCanDeletePayments) {
                $paymentsDb = new nya_payments();
                $paymentsDb->where('id', '=', $deletePaymentId);
                $paymentsDb->delete();
                log_register("PAYMENT DELETE [" . $deletePaymentId . "] (" . $login . ")");
            } else {
                log_register("PAYMENT UNAUTH DELETION ATTEMPT [" . $deletePaymentId . "] (" . $login . ")");
            }
        }
        ubRouting::nav($fixer::URL_ME);
    }

    show_window('', $fixer->renderDateSelectorForm($checkDate));
    $fixer->setDate($checkDate);
    show_window(__('Money transactions that may was not processed'), $fixer->renderFailedPayments());
    show_window(__('That users payed something but still is debtors'), $fixer->renderDebtorUsers());

    show_window('', wf_BackLink('?module=report_finance'));
} else {
    show_error(__('Access denied'));
}
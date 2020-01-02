<?php
if (cfr('ROOT')) {
    

class PaymentFixer {

    protected $altCfg = array();
    protected $billCfg = array();
    protected $payments = '';
    protected $opTransactions = '';
    protected $checkDate = '';

    public function __construct() {
        $this->loadConfigs();
        $this->initDataModels();
    }

    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->billCfg = $ubillingConfig->getBilling();
    }

    protected function initDataModels() {
        $this->payments = new NyanORM('payments');
        $this->opTransactions = new NyanORM('op_transactions');
    }

    public function setDate($date = '') {
        if (empty($date)) {
            $this->checkDate = curdate();
        } else {
            $this->checkDate = $date;
        }
    }

    protected function getPayments() {
        $result = array();
        if (!empty($this->checkDate)) {
            $this->payments->where('date', 'LIKE', $this->checkDate . '%');
            $this->payments->where('summ','>','0');
            $result = $this->payments->getAll();
        }
        return($result);
    }
    
    protected function getStargazerOps() {
        $result='';
        if (!empty($this->checkDate)) {
            $sudo= $this->billCfg['SUDO'];
            $cat= $this->billCfg['CAT'];
            $stgLog= $this->altCfg['STG_LOG_PATH'];
            $grep= $this->billCfg['GREP'];
            $command=$sudo.' '.$cat.' '.$stgLog.' | '.$grep .' '.$this->checkDate.' | '.$grep.' cash';
            $result.= shell_exec($command);

        }
        return($result);
    }
    
    public function checkPayments() {
        $stgDataRaw=$this->getStargazerOps();
        $allPayments= $this->getPayments();
        
        if (!empty($allPayments)) {
            foreach ($allPayments as $io=>$eachPayment) {
                if (!ispos($eachPayment['note'],'MOCK:')) {
                     if (!ispos($stgDataRaw, $eachPayment['login'])) {
                         debarr($eachPayment);
                     }
                }
            }
        }
    }

}

$fixer = new PaymentFixer();
$fixer->setDate('2020-01-03');
$fixer->checkPayments();

} else {
    show_error(__('Access denied'));
}
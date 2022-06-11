<?php

/**
 * OLT Huawei hardware abstraction layer
 */
class PONHuaweiGpon extends PonZte {

    public function __construct($oltParameters, $snmpTemplates) {
        $oltParameters['TYPE'] = 'GPON';
        parent::__construct($oltParameters, $snmpTemplates);
    }

    public function collect() {
        $this->huaweiPollGpon();
    }

}

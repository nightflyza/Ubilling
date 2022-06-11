<?php

/**
 * OLT ZTE-EPON hardware abstraction layer
 */
class PONZteEpon extends PonZte {

    public function __construct($oltParameters, $snmpTemplates) {
        $oltParameters['TYPE'] = 'EPON';
        parent::__construct($oltParameters, $snmpTemplates);
    }

    public function collect() {
        $this->pollEpon();
    }

}

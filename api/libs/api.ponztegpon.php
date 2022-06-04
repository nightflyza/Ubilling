<?php

class PONZteGpon extends PonZte {

    public function __construct($oltParameters, $snmpTemplates) {
        $oltParameters['TYPE'] = 'GPON';
        parent::__construct($oltParameters, $snmpTemplates);
    }

    public function collect() {
        $this->pollGpon();
    }

}

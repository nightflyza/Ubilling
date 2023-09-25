<?php

class PseudoCRM {

    protected $leadsDb = '';
    protected $messages = '';

    const TABLE_LEADS = 'crm_leads';
    const TABLE_ACTIVITIES = 'crm_activities';

    public function __construct() {
        $this->initMessages();
        $this->initLeadsDb();
    }

    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    protected function initLeadsDb() {
        $this->leadsDb = new NyanORM(self::TABLE_LEADS);
    }
}

<?php

class MeaCulpa {

    protected $culpasDb = '';

    const TABLE_CULPA = 'mlg_culpas';

    public function __construct() {
        
    }

    protected function initDb() {
        $this->culpasDb = new NyanORM(self::TABLE_CULPA);
    }
}

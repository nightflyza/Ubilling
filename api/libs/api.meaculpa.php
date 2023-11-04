<?php

/**
 * Next Gen auth basic implementation
 */
class MeaCulpa {

    /**
     * Culpa records database abstraction layer
     * 
     * @var object
     */
    protected $culpasDb = '';

    /**
     * Contains all available user culpas as login=>id/login/culpa
     * 
     * @var array
     */
    protected $allCulpas = array();

    /**
     * Some predefined stuff here
     */
    const TABLE_CULPA = 'mlg_culpas';

    public function __construct() {
        $this->initDb();
        $this->loadCulpas();
    }

    /**
     * Inits protected database abstraction layer
     * 
     * @return void
     */
    protected function initDb() {
        $this->culpasDb = new NyanORM(self::TABLE_CULPA);
    }

    /**
     * Loads all available culpas data
     * 
     * @return void
     */
    protected function loadCulpas() {
        $this->allCulpas = $this->culpasDb->getAll('login');
    }

    /**
     * Checks is culpa unique or not?
     * 
     * @param  string $culpa
     * 
     * @return bool
     */
    protected function isCulpaUnique($culpa) {
        $result = true;
        if (!empty($this->allCulpas)) {
            foreach ($this->allCulpas as $io => $each) {
                if ($each['culpa'] == $culpa) {
                    $result = false;
                    break;
                }
            }
        }
        return($result);
    }

    /**
     * Returns all existing user culpas as login=>culpa
     * 
     * @return array
     */
    protected function getAll() {
        $result = array();
        if (!empty($this->allCulpas)) {
            foreach ($this->allCulpas as $io => $each) {
                $result[$each['login']] = $each['culpa'];
            }
        }
        return($result);
    }

    /**
     * Sets some culpa for user
     * 
     * @param string $login
     * @param string $culpa
     * 
     * @return bool
     */
    public function set($login, $culpa) {
        $login = ubRouting::filters($login, 'mres');
        $culpaF = ubRouting::filters($culpa, 'mres');
        $result = true;
        if ($this->isCulpaUnique($culpa)) {
            $this->culpasDb->data('culpa', $culpaF);
            if (isset($this->allCulpas[$login])) {
                $this->culpasDb->where('login', '=', $login);
                $this->culpasDb->save();
                log_register('CULPA UPDATE (' . $login . ') CULPA `' . $culpa . '`');
            } else {
                $this->culpasDb->data('login', $login);
                $this->culpasDb->create();
                log_register('CULPA CREATE (' . $login . ') CULPA `' . $culpa . '`');
            }
        } else {
            $result = false;
            log_register('CULPA FAIL (' . $login . ') DUPLICATE CULPA `' . $culpa . '`');
        }
        return($result);
    }

    /**
     * 
     * @param type $login
     */
    public function delete($login) {
        $login = ubRouting::filters($login, 'mres');
        $this->culpasDb->where('login', '=', $login);
        $this->culpasDb->delete();
        log_register('CULPA DELETE (' . $login . ')');
    }

    /**
     * Returns user assigned culpa if it exists
     * 
     * @param string $login
     * 
     * @return string
     */
    public function get($login) {
        $result = '';
        if (isset($this->allCulpas[$login])) {
            $result = $this->allCulpas[$login]['culpa'];
        }
        return($result);
    }
}

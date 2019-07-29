<?php

class NotOrm {

    protected $tablename = '';

    public function __construct($name = '') {
        $this->setTableName($name);
    }

    public function setTableName($name) {
        if (!empty($name)) {
            $this->tablename = $name;
        } else {
            $this->tablename = strtolower(get_class($this));
        }
    }

    public function getAll($options = '') {
        $options = (!empty($options)) ? ' ' . $options : '';
        return(simple_queryall("SELECT * from `" . $this->tablename . "`" . $options));
    }

}

<?php

class MegogoFrontend {

    /**
     * Contains available megogo service tariffs id=>tariffdata
     *
     * @var array
     */
    protected $allTariffs = array();

    /**
     * Contains available and active megogo service subscriptions as id=>data
     *
     * @var array
     */
    protected $allSubscribers = array();

    /**
     * Contains all subscribtions history by all of users id=>data
     *
     * @var array
     */
    protected $allHistory = array();

    public function __construct() {
        $this->loadTariffs();
        $this->loadSubscribers();
        $this->loadHistory();
    }

    /**
     * Loads existing tariffs from database for further usage
     * 
     * @return void
     */
    protected function loadTariffs() {
        $query = "SELECT * from `mg_tariffs`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allTariffs[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadSubscribers() {
        $query = "SELECT * from `mg_subscribers`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allSubscribers[$each['id']] = $each;
            }
        }
    }

    /**
     * Loads existing subscribers data
     * 
     * @return void
     */
    protected function loadHistory() {
        $query = "SELECT * from `mg_history`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->allHistory[$each['id']] = $each;
            }
        }
    }

    /**
     * Renders tariffs list with subscribtion form
     * 
     * @return string
     */
    public function renderSubscribeForm() {
        $result = '';
        if (!empty($this->allTariffs)) {
            foreach ($this->allTariffs as $io => $each) {
                $headerColor=($each['primary']) ? '#adc8ff' : '#fff298' ;
                $tariffInfo=la_tag('div', false, '','style="height:32px; width:100%; background-color:'.$headerColor.';"').$each['name'].  la_tag('div',true);
                
                $result.=la_tag('div', false, '','style="height:256px; width:256px; border:1px solid; float:left; margin:10px;"').$tariffInfo.  la_tag('div',true);
            }
        }
        return ($result);
    }

}

?>
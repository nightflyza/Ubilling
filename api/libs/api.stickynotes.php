<?php

class StickyNotes {
    
    protected $allnotes=array();
    protected $myLogin='';



    public function __construct() {
        $this->setLogin();
        $this->loadAllNotes();
    }
    
    protected function setLogin() {
        $this->myLogin=  whoami();
    }


    /**
     * Loads notes from database into private property
     * 
     * @return void
     */
    protected function loadAllNotes() {
        
        $query="SELECT * from `stickynotes` WHERE `owner`= '".$this->myLogin."' ORDER BY `id` DESC";
        $this->allnotes=  simple_queryall($query);
    }
    
    
    
    /**
     * Renders all available notes
     * 
     * @return string
     */
    public function getAll() {
        $result='';
        if (!empty($this->allnotes)) {
            foreach ($this->allnotes as $io=>$each) {
                $result.=$each['text'];
            }
        }
        return ($result);
    }
    
        public function createNote() {
        $text='';
        $createDate=  curdatetime();
        $activity=1;
        $remindDate=NULL;
        $query="INSERT INTO `stickynotes` (`id`, `owner`, `createdate`, `reminddate`, `active`, `text`) "
             . "VALUES (NULL, '".$this->myLogin."', '".$createDate."', '".$remindDate."', '".$activity."', '".$text."');";
    }

    

    /**
     * Renders notify container with some text inside
     * 
     * @param string $text
     * @return string
     */
    public function renderStickyNote($text) {
        $result='';
        if (!empty($text)) {
            $result.= wf_tag('div', false, 'stickynote');
            $result.= wf_Link('?module=stickynotes', wf_img('skins/pushpin.png'), false, '').' ';
            $result.= $text;
            $result.= wf_tag('div',true);
        }
        return ($result);
    }
    
    
}

?>
<?php

class widget_tbdrag extends TaskbarWidget {

    public function render() {
        $result = wf_tag('script');
        $result.= '$( function() { $( ".dashtask" ).draggable({ scroll: false}); } );';
        $result.=wf_tag('script', true);
        return ($result);
    }

}

?>
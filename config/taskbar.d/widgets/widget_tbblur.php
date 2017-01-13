<?php

class widget_tbblur extends TaskbarWidget {

    public function render() {
        $result = wf_tag('style', false, '', 'type="text/css"');
        $result.='.dashtask img:hover { -webkit-filter: blur(5px); filter: blur(5px); }
                  .dashtask img { -webkit-filter: none; filter: none; }';
        $result.=wf_tag('style', true);
        return ($result);
    }

}

?>
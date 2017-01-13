<?php

class widget_tbbw extends TaskbarWidget {

    public function render() {
        $result = wf_tag('style', false, '', 'type="text/css"');
        $result.='.dashtask img:hover { -webkit-filter: grayscale(1); filter: grayscale(1); }
                  .dashtask img { -webkit-filter: none; filter: none; }';
        $result.=wf_tag('style', true);
        return ($result);
    }

}

?>
<?php

class widget_psycho extends TaskbarWidget {

    /**
     * Just demonstration of unstandart widgets usage
     * 
     * @return string
     */
    public function render() {
        $angle = rand(160, 280);
        $result = wf_tag('style');
        $result.='body.transform {
                     transform:rotate(' . $angle . 'deg);
                     -ms-transform:rotate(' . $angle . 'deg);
                    -webkit-transform:rotate(' . $angle . 'deg);
                }
        ';
        $result.=wf_tag('style', true);
        $result.=wf_tag('script', false, '', '');
        $result.='
                document.body.className = \'transform\';
                setTimeout(function(){ document.body.className = \'\'; },3000);';
        $result.=wf_tag('script', true);
        return ($result);
    }

}

?>
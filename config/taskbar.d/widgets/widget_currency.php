<?php

class widget_currency extends TaskbarWidget {

    public function render() {
        $result = '';
        $script = '

<div style="text-align:center;background-color:#A8A8A8;width:100%;font-size:13px;font-weight:bold;height:18px;padding-top:2px;">
Currency Converter
</div>
<script type="text/javascript" src="//www.exchangeratewidget.com/converter.php?l=ru&f=USD&t=UAH&a=1&d=E8E8E8&n=FFFFFF&o=000000&v=1"></script>

';
        $result.=$this->widgetContainer($script, 'style="width:256px; height:256px;"');


        return ($result);
    }

}

?>
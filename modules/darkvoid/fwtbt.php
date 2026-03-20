<?php

$result = '';

if (isset($darkVoidContext['altCfg']['FWTBT_ENABLED'])) {
    if ($darkVoidContext['altCfg']['FWTBT_ENABLED']) {
        $fwtbtFront = new ForWhomTheBellTolls();
        $result .= $fwtbtFront->renderWidget();
    }
}

return ($result);

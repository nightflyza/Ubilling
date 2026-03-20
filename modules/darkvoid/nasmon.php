<?php

$result = '';

if (isset($darkVoidContext['altCfg']['NASMON_ENABLED'])) {
    if ($darkVoidContext['altCfg']['NASMON_ENABLED']) {
        $nasMon = new NasMon();
        $result .= $nasMon->getNasAlerts();
    }
}

return ($result);

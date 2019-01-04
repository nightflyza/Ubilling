<?php

$fwtbt = new ForWhomTheBellTolls();
if (wf_CheckGet(array('getcalls'))) {
    if (cfr('FWTBT')) {
        $fwtbt->getCalls();
    } else {
        die(json_encode(array()));
    }
}
?>
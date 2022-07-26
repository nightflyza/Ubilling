<?php

if (cfr('SWITCHESEDIT')) {
    if (ubRouting::checkGet('switchid')) {
        //run replace
        if (ubRouting::checkPost(array('switchreplace', 'toswtichreplace', 'replaceemployeeid'))) {
            $oldSwitchId = ubRouting::post('switchreplace');
            $newSwitchId = ubRouting::post('toswtichreplace');
            zb_SwitchReplace($oldSwitchId, $newSwitchId, ubRouting::post('replaceemployeeid'));
            ubRouting::nav('?module=switches&edit=' . $newSwitchId);
        }

        //display form
        $switchId = ubRouting::get('switchid', 'int');
        $switchData = zb_SwitchGetData($switchId);
        show_window(__('Switch replacement') . ': ' . $switchData['location'] . ' - ' . $switchData['ip'], zb_SwitchReplaceForm($switchId));
    } else {
        show_error(__('Strange exeption'));
    }
} else {
    show_error(__('Access denied'));
}


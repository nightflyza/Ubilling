<?php

if (cfr('SWITCHPOLL')) {
    //display all of available fdb tables
    $fdbData_raw = rcms_scandir('./exports/', '*_fdb');
    if (!empty($fdbData_raw)) {
        // mac filters setup
        if (ubRouting::checkPost('setmacfilters')) {
            //setting new MAC filters
            if (ubRouting::checkPost('newmacfilters')) {
                $newFilters = base64_encode(ubRouting::post('newmacfilters'));
                zb_StorageSet('FDBCACHEMACFILTERS', $newFilters);
            }
            //deleting old filters
            if (ubRouting::checkPost('deletemacfilters', false)) {
                zb_StorageDelete('FDBCACHEMACFILTERS');
            }
        }

        //push ajax data
        if (ubRouting::checkGet('ajax')) {
            if (ubRouting::checkGet('swfilter')) {
                $fdbData_raw = array(ubRouting::get('swfilter') . '_fdb');
            }
            if (ubRouting::checkGet('macfilter')) {
                $macFilter = ubRouting::get('macfilter');
            } else {
                $macFilter = '';
            }
            sn_SnmpParseFdbCacheJson($fdbData_raw, $macFilter);
        } else {
            if (ubRouting::checkGet('fdbfor')) {
                $fdbSwitchFilter = ubRouting::get('fdbfor');
            } else {
                $fdbSwitchFilter = '';
            }
            if (ubRouting::checkGet('macfilter')) {
                $fdbMacFilter = ubRouting::get('macfilter');
            } else {
                $fdbMacFilter = '';
            }

            web_FDBTableShowDataTable($fdbSwitchFilter, $fdbMacFilter);
        }
    } else {
        show_warning(__('Nothing found'));
    }
} else {
    show_error(__('Access denied'));
}
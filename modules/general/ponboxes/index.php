<?php

if (cfr('PON')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['PONBOXES_ENABLED']) {
        if ($altCfg['PON_ENABLED']) {
            $boxes = new PONBoxes(true);

            //boxes list rendering
            if (ubRouting::checkGet($boxes::ROUTE_BOXLIST)) {
                $boxes->ajBoxesList();
            }

            //new box creation
            if (ubRouting::checkPost('newboxname')) {
                $creationResult = $boxes->createBox(ubRouting::post('newboxname'), ubRouting::post('newboxgeo'));
                if (empty($creationResult)) {
                    ubRouting::nav($boxes::URL_ME);
                } else {
                    show_error($creationResult);
                }
            }

            //default module controls panel
            show_window('', $boxes->renderControls());

            if (!ubRouting::checkGet($boxes::ROUTE_BOXEDIT)) {
                //rendering available boxes list
                if (!ubRouting::checkGet($boxes::ROUTE_MAP)) {
                    show_window(__('Available boxes'), $boxes->renderBoxesList());
                }

                //render pon boxes map
                if (ubRouting::checkGet($boxes::ROUTE_MAP)) {
                    show_window(__('Map'), $boxes->renderBoxesMap());
                }
            } else {
                //boxes editing interface
                show_window(__('Edit'), 'TODO');
            }
        } else {
            show_error(__('PONizer') . ' ' . __('disabled'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
    
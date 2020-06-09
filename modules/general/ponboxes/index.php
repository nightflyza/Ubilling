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

            //existing box editing
            if (ubRouting::checkPost('editboxid')) {
                $savingResult = $boxes->saveBox();
                if (empty($savingResult)) {
                    ubRouting::nav($boxes::URL_ME . '&' . $boxes::ROUTE_BOXEDIT . '=' . ubRouting::post('editboxid'));
                } else {
                    show_error($savingResult);
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
                show_window(__('Edit'), $boxes->renderBoxEditForm(ubRouting::get($boxes::ROUTE_BOXEDIT)));
                show_window(__('Links'), $boxes->renderBoxLinksList(ubRouting::get($boxes::ROUTE_BOXEDIT)));
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
    
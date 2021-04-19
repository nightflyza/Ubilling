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
            if (ubRouting::checkPost($boxes::PROUTE_NEWBOXNAME)) {
                $creationResult = $boxes->createBox(ubRouting::post($boxes::PROUTE_NEWBOXNAME), ubRouting::post($boxes::PROUTE_NEWBOEXTENINFO), ubRouting::post($boxes::PROUTE_NEWBOXGEO));
                if (empty($creationResult)) {
                    ubRouting::nav($boxes::URL_ME);
                } else {
                    show_error($creationResult);
                }
            }

            //existing box editing
            if (ubRouting::checkPost($boxes::ROUTE_BOXEDIT)) {
                if (ubRouting::checkPost($boxes::ROUTE_SPLITTERADD)) {
                    $savingResult = $boxes->addSplitter();
                } else {
                    $savingResult = $boxes->saveBox();
                }

                if (empty($savingResult)) {
                    ubRouting::nav($boxes::URL_ME . '&' . $boxes::ROUTE_BOXEDIT . '=' . ubRouting::post($boxes::ROUTE_BOXEDIT));
                } else {
                    show_error($savingResult);
                }
            }

            //existing box deletion
            if (ubRouting::checkGet($boxes::ROUTE_BOXDEL)) {
                $deletionResult = $boxes->deleteBox(ubRouting::get($boxes::ROUTE_BOXDEL));
                if (empty($deletionResult)) {
                    ubRouting::nav($boxes::URL_ME);
                } else {
                    show_error($deletionResult);
                }
            }

            //existing link deletion
            if (ubRouting::checkGet($boxes::ROUTE_LINKDEL)) {
                $linkDelResult = $boxes->deleteLink(ubRouting::get($boxes::ROUTE_LINKDEL));
                if (empty($linkDelResult)) {
                    ubRouting::nav($boxes::URL_ME . '&' . $boxes::ROUTE_BOXEDIT . '=' . ubRouting::get($boxes::ROUTE_BOXNAV));
                } else {
                    show_error($linkDelResult);
                }
            }

            //existing splitter deletion
            if (ubRouting::checkGet($boxes::ROUTE_SPLITTERDEL)) {
                $splitterDelResult = $boxes->deleteLink(ubRouting::get($boxes::ROUTE_SPLITTERDEL), true);
                if (empty($splitterDelResult)) {
                    ubRouting::nav($boxes::URL_ME . '&' . $boxes::ROUTE_BOXEDIT . '=' . ubRouting::get($boxes::ROUTE_BOXNAV));
                } else {
                    show_error($splitterDelResult);
                }
            }

            //fast box navigation
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
                show_window(__('Schemes and images'), $boxes->renderBoxImageControls(ubRouting::get($boxes::ROUTE_BOXEDIT)));
                show_window(__('Splitters/couplers in this box'), $boxes->renderSplittersControls(ubRouting::get($boxes::ROUTE_BOXEDIT))
                            . $boxes->renderSplittersList(ubRouting::get($boxes::ROUTE_BOXEDIT))
                            );
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
    
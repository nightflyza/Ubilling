<?php

if ($ubillingConfig->getAlterParam('MULTIGEN_ENABLED')) {
    if (cfr('MULTIGEN')) {

        $customNas = new MultigenECN();

        //new NAS creation
        if (ubRouting::checkPost(array($customNas::PROUTE_NEWIP, $customNas::PROUTE_NEWNAME, $customNas::PROUTE_NEWSECRET))) {
            $newIp = ubRouting::post($customNas::PROUTE_NEWIP);
            $newName = ubRouting::post($customNas::PROUTE_NEWNAME);
            $newSecret = ubRouting::post($customNas::PROUTE_NEWSECRET);
            $creationResult = $customNas->create($newIp, $newName, $newSecret);
            if (empty($creationResult)) {
                ubRouting::nav($customNas::URL_ME);
            } else {
                show_error($creationResult);
            }
        }

        //existing NAS editing
        if (ubRouting::checkPost(array($customNas::PROUTE_EDID, $customNas::PROUTE_EDNAME, $customNas::PROUTE_EDSECRET))) {
            $editNasId = ubRouting::post($customNas::PROUTE_EDID);
            $editNasName = ubRouting::post($customNas::PROUTE_EDNAME);
            $editNasSecret = ubRouting::post($customNas::PROUTE_EDSECRET);
            $savingResult = $customNas->save($editNasId, $editNasName, $editNasSecret);
            if (empty($savingResult)) {
                ubRouting::nav($customNas::URL_ME);
            } else {
                show_error($savingResult);
            }
        }

        //existing NAS deletion
        if (ubRouting::checkGet($customNas::ROUTE_DELETE)) {
            $nasIdToDelete = ubRouting::get($customNas::ROUTE_DELETE);
            $deletionResult = $customNas->delete($nasIdToDelete);
            if (empty($deletionResult)) {
                ubRouting::nav($customNas::URL_ME);
            } else {
                show_error($deletionResult);
            }
        }

        show_window('', wf_BackLink('?module=nas'));
        show_window(__('Extra chromosome NASes'), $customNas->renderList());
        show_window(__('Add new'), $customNas->renderCreateForm());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
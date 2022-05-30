<?php

if (cfr('ROOT')) {

    $aclMgr = new IpACLMgr();

    //new IP ACL creation
    if (ubRouting::checkPost($aclMgr::PROUTE_NEWIPACLIP)) {
        $creationResult = $aclMgr->createIpAcl(ubRouting::post($aclMgr::PROUTE_NEWIPACLIP), ubRouting::post($aclMgr::PROUTE_NEWIPACLNOTE));
        if (empty($creationResult)) {
            ubRouting::nav($aclMgr::URL_ME);
        } else {
            show_error($creationResult);
        }
    }

    //editing IP ACL notes
    if (ubRouting::checkPost($aclMgr::PROUTE_EDIPACLIP)) {
        $editingResult = $aclMgr->saveIpAcl(ubRouting::post($aclMgr::PROUTE_EDIPACLIP), ubRouting::post($aclMgr::PROUTE_EDIPACLNOTE));
        if (empty($editingResult)) {
            ubRouting::nav($aclMgr::URL_ME);
        } else {
            show_error($editingResult);
        }
    }

    //IP ACL deletion
    if (ubRouting::checkGet($aclMgr::ROUTE_DELIPACL)) {
        $deletionResult = $aclMgr->deleteIpAcl(ubRouting::get($aclMgr::ROUTE_DELIPACL));
        if (empty($deletionResult)) {
            ubRouting::nav($aclMgr::URL_ME);
        } else {
            show_error($deletionResult);
        }
    }

    //new network ACL creation
    if (ubRouting::checkPost($aclMgr::PROUTE_NEWNETACLNET)) {
        $creationResult = $aclMgr->createNetAcl(ubRouting::post($aclMgr::PROUTE_NEWNETACLNET), ubRouting::post($aclMgr::PROUTE_NEWNETACLNOTE));
        if (empty($creationResult)) {
            ubRouting::nav($aclMgr::URL_ME);
        } else {
            show_error($creationResult);
        }
    }

    //editing network ACL notes
    if (ubRouting::checkPost($aclMgr::PROUTE_EDNETACLNET)) {
        $editingResult = $aclMgr->saveNetAcl(ubRouting::post($aclMgr::PROUTE_EDNETACLNET), ubRouting::post($aclMgr::PROUTE_EDNETACLNOTE));
        if (empty($editingResult)) {
            ubRouting::nav($aclMgr::URL_ME);
        } else {
            show_error($editingResult);
        }
    }

    //Network ACL deletion
    if (ubRouting::checkGet($aclMgr::ROUTE_DELNETACL)) {
        $deletionResult = $aclMgr->deleteNetAcl(ubRouting::get($aclMgr::ROUTE_DELNETACL));
        if (empty($deletionResult)) {
            ubRouting::nav($aclMgr::URL_ME);
        } else {
            show_error($deletionResult);
        }
    }

    show_window(__('IP Access restrictions'), $aclMgr->renderControls());
    show_window(__('IPs from which access to the administrative web interface is allowed'), $aclMgr->renderIpAclsList());
    show_window(__('Networks from which access is allowed'), $aclMgr->renderNetAclsList());


    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
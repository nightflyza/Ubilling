<?php
if (cfr('UKV')) {
    
  
    
    //creating base system object
    $ukv=new UkvSystem();
    
    /*
     * controller section
     */
    
    //fast ajax render
    if (wf_CheckGet(array('ajax'))) {
        $ukv->ajaxUsers();
    }
    
    
    //tariffs editing
    if (wf_CheckPost(array('edittariff'))) {
        $ukv->tariffSave($_POST['edittariff'], $_POST['edittariffname'], $_POST['edittariffprice']);
        rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
    }
    
    //tariffs creation
    if (wf_CheckPost(array('createtariff'))) {
        $ukv->tariffCreate($_POST['createtariffname'], $_POST['createtariffprice']);
        rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
    }
    
    //tariffs deletion
    if (wf_CheckGet(array('tariffdelete'))) {
        $ukv->tariffDelete($_GET['tariffdelete']);
        rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
    }
    
    /*
     * some views here
     */
    
    //show global management panel
    show_window('', $ukv->panel());
    
    //renders tariffs list with controls
    if (wf_CheckGet(array('tariffs'))) {
        show_window(__('Available tariffs'),$ukv->renderTariffs());
    }
    
    //full users listing
    if (wf_CheckGet(array('users','userslist'))) {
        show_window(__('Available users'), $ukv->renderUsers());
    }
    
    //user profile show
    if (wf_CheckGet(array('users','showuser'))) {
        
        //user editing processing
        if (wf_CheckPost(array('usereditprocessing'))) {
            $ukv->userSave();
            rcms_redirect(UkvSystem::URL_USERS_PROFILE.$_POST['usereditprocessing']);
        }
        
        show_window(__('User profile'), $ukv->userProfile($_GET['showuser']));
    }
    
    
    
} else {
    show_window(__('Error'), __('Access denied'));
}

?>
<?php

if (cfr('GLMENUCONF')) {

    $glMenu = new GlobalMenu();

    if (wf_CheckPost(array('glcustomconfedit'))) {
        $glMenu->saveCustomConfigs();
        rcms_redirect('?module=glmenuconf&rebuildfastacc=true');
    }
    
    if (wf_CheckGet(array('rebuildfastacc'))) {
        $glMenu->rebuildFastAccessMenuData();
       rcms_redirect('?module=glmenuconf');
    }
    
    
    show_window(__('Personalize menu'), $glMenu->getEditForm());
} else {
    show_error(__('You cant control this module'));
}

?>
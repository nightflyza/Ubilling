<?php

if (cfr('SWITCHMAP')) {

    $altercfg = $ubillingConfig->getAlter();

    if ($altercfg['CUSTMAP_ENABLED']) {
        $custmaps=new CustomMaps();
        
        // new custom map creation
        if (wf_CheckPost(array('newmapname'))) {
            $custmaps->mapCreate($_POST['newmapname']);
            rcms_redirect('?module=custmaps');
        }
        
        //custom map deletion
        if (wf_CheckGet(array('deletemap'))) {
            $custmaps->mapDelete($_GET['deletemap']);
            rcms_redirect('?module=custmaps');
        }
        
        //editing existing custom map name
        if (wf_CheckPost(array('editmapid','editmapname'))) {
            $custmaps->mapEdit($_POST['editmapid'], $_POST['editmapname']);
            rcms_redirect('?module=custmaps');
        }
        
        
        //render existing custom maps list
        if (!wf_CheckGet(array('showmap'))) {
            show_window(__('Available custom maps'),$custmaps->renderMapList());
        } else {
            $mapId=$_GET['showmap'];
            $placemarks=  sm_MapAddMark('48.9269, 24.7111', 'test', 'content', 'footer', sm_MapGoodIcon(), 'ok', true);
            show_window($custmaps->mapGetName($mapId), $custmaps->mapInit($placemarks, ''));
        }
        
        
        
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>
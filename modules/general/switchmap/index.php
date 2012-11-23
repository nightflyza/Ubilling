<?php
if(cfr('SWITCHMAP')) {

    $altercfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    
    if ($altercfg['SWYMAP_ENABLED']) {
        
    
    $ymconf=  rcms_parse_ini_file(CONFIG_PATH."ymaps.ini");
    $ym_center=$ymconf['CENTER'];
    $ym_zoom=$ymconf['ZOOM'];
    $ym_type=$ymconf['TYPE'];
    $ym_lang=$ymconf['LANG'];
    
    //show map container
    sm_ShowMapContainer();
    
    //collect switches geolocation data
    $placemarks=sm_MapDrawSwitches();

    if (wf_CheckGet(array('locfinder'))) {
      sm_MapInit($ym_center,$ym_zoom,$ym_type,$placemarks, sm_MapLocationFinder(),$ym_lang);   
    } else {
      sm_MapInit($ym_center,$ym_zoom,$ym_type,$placemarks, '',$ym_lang);   
    }
    
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
        

    
} else {
    show_error(__('Access denied'));
}


?>
<?php
if(cfr('SWITCHMAP')) {

    $altercfg=  $ubillingConfig->getAlter();
    
    if ($altercfg['SWYMAP_ENABLED']) {
   
    //wysiwyg switch placement    
    if (wf_CheckPost(array('switchplacing','placecoords'))) {
        if (cfr('SWITCHESEDIT')) {
            $switchid=vf($_POST['switchplacing'],3);
            $placegeo=  mysql_real_escape_string($_POST['placecoords']);
            
            simple_update_field('switches', 'geo', $placegeo,"WHERE `id`='".$switchid."'");
            log_register('SWITCH CHANGE ['.$switchid.']'.' GEO '.$placegeo);
            rcms_redirect("?module=switchmap&locfinder=true");
        } else {
             show_error(__('Access denied'));
        }
    }    
        
    
    $ymconf=  $ubillingConfig->getYmaps();
    $ym_center=$ymconf['CENTER'];
    $ym_zoom=$ymconf['ZOOM'];
    $ym_type=$ymconf['TYPE'];
    $ym_lang=$ymconf['LANG'];
    $area='';
    
    //show map container
    sm_ShowMapContainer();
    
    //collect switches geolocation data
    if (!wf_CheckGet(array('coverage'))) {
        $placemarks=sm_MapDrawSwitches();
        //uplinks display mode
        if (wf_CheckGet(array('showuplinks'))) {
            $placemarks.=sm_MapDrawSwitchUplinks();
        }
    } else {
        $placemarks=sm_MapDrawSwitchesCoverage();
    }
    
    
    //setting custom zoom and map center if need to find device
    
    if (wf_CheckGet(array('finddevice'))) {
            $ym_zoom=$ymconf['FINDING_ZOOM'];
            $ym_center=vf($_GET['finddevice']);
           
            if ($ymconf['FINDING_CIRCLE']) {
                $radius=30;
                $area=sm_MapAddCircle($_GET['finddevice'], $radius,__('Search area radius').' '.$radius.' '.__('meters'),__('Search area'));
            } else {
                $area='';
            }
            
        //uplinks display mode
        if (wf_CheckGet(array('showuplinks'))) {
            $placemarks.=sm_MapDrawSwitchUplinks();
        }
            
    }

    if (wf_CheckGet(array('locfinder'))) {
      sm_MapInit($ym_center,$ym_zoom,$ym_type,$placemarks, sm_MapLocationFinder(),$ym_lang);   
    } else {
      sm_MapInit($ym_center,$ym_zoom,$ym_type,$area.$placemarks, '',$ym_lang);   
    }
    
    } else {
        show_error(__('This module is disabled'));
    }
        

    
} else {
    show_error(__('Access denied'));
}


?>
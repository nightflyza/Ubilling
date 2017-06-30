<?php
if(cfr('USERSMAP')) {

    $altercfg=  $ubillingConfig->getAlter();
    
    if ($altercfg['SWYMAP_ENABLED']) {
   
    //wysiwyg build map placement
    if (wf_CheckPost(array('buildplacing','placecoords'))) {
        if (cfr('BUILDS')) {
            $buildid = vf($_POST['buildplacing'],3);
            $buildid = trim($buildid);
            $placegeo = mysql_real_escape_string($_POST['placecoords']);
            $placegeo = preg_replace('/[^0-9\.,]/i', '', $placegeo);
            
            simple_update_field('build', 'geo', $placegeo,"WHERE `id`='" . $buildid . "'");
            log_register('BUILD CHANGE ['.$buildid.']'.' GEO `'.$placegeo.'`');
            rcms_redirect("?module=usersmap&locfinder=true");
        } else {
             show_window(__('Error'), __('Access denied'));
        }
    }    
        
    
    $ymconf = $ubillingConfig->getYmaps();
    $ym_center = $ymconf['CENTER'];
    $ym_zoom = $ymconf['ZOOM'];
    $ym_type = $ymconf['TYPE'];
    $ym_lang = $ymconf['LANG'];
    $area = '';
    
    //show map container
    um_ShowMapContainer();
    
    //collect biulds geolocation data
    $placemarks = um_MapDrawBuilds();
    
    
    
    //setting custom zoom and map center if need to find some build
    
    if (wf_CheckGet(array('findbuild'))) {
            $ym_zoom = $ymconf['FINDING_ZOOM'];
            $ym_center = vf($_GET['findbuild']);
           
            if ($ymconf['FINDING_CIRCLE']) {
                $radius = 30;
                $area = sm_MapAddCircle($_GET['findbuild'], $radius,__('Search area radius').' '.$radius.' '.__('meters'),__('Search area'));
            } else {
                $area = '';
            }
            
    }

    if (wf_CheckGet(array('locfinder'))) {
      sm_MapInit($ym_center,$ym_zoom,$ym_type,$placemarks, um_MapLocationFinder(),$ym_lang);
    } else {
      sm_MapInit($ym_center,$ym_zoom,$ym_type,$area.$placemarks, '',$ym_lang);   
    }
    
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
        

    
} else {
    show_error(__('Access denied'));
}


?>
<?php
if (cfr('SWITCHMAP')) {

    $altercfg =  $ubillingConfig->getAlter();

    if ($altercfg['SWYMAP_ENABLED']) {
        $ymconf =  $ubillingConfig->getYmaps();
        $ym_center = $ymconf['CENTER'];
        $ym_zoom = $ymconf['ZOOM'];
        $ym_type = $ymconf['TYPE'];
        $ym_lang = $ymconf['LANG'];
        $area = '';
        $locator = '';

        //wysiwyg switch placement    
        if (ubRouting::checkPost(array('switchplacing', 'placecoords'))) {
            if (cfr('SWITCHESEDIT')) {
                $switchid = ubRouting::post('switchplacing', 'int');
                $placegeo =  ubRouting::post('placecoords', 'mres');
                simple_update_field('switches', 'geo', $placegeo, "WHERE `id`='" . $switchid . "'");
                log_register('SWITCH CHANGE [' . $switchid . ']' . ' GEO ' . $placegeo);
                ubRouting::nav('?module=switchmap&locfinder=true');
            } else {
                show_error(__('Access denied'));
            }
        }

        //show map container
        sm_ShowMapContainer();

        //collect switches geolocation data
        if (!ubRouting::checkGet('coverage')) {
            $placemarks = sm_MapDrawSwitches();
            //uplinks display mode
            if ((ubRouting::checkGet('showuplinks')) and (!ubRouting::checkGet('traceid'))) {
                $placemarks .= sm_MapDrawSwitchUplinks();
            }
        } else {
            $placemarks = sm_MapDrawSwitchesCoverage();
        }


        //setting custom zoom and map center if need to find device
        if (ubRouting::checkGet(('finddevice'))) {
            $ym_zoom = $ymconf['FINDING_ZOOM'];
            $ym_center = ubRouting::get('finddevice', 'vf');

            if ($ymconf['FINDING_CIRCLE']) {
                $radius = 30;
                $area =  sm_MapAddCircle($ym_center, $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), __('Search area'));
            } else {
                $area = '';
            }

            //uplinks display mode
            if (ubRouting::checkGet('showuplinks')) {
                $traceLinks = (ubRouting::checkGet('traceid')) ? ubRouting::get('traceid','int') : '';
                $placemarks .= sm_MapDrawSwitchUplinks($traceLinks);
            }
        }

        if (wf_CheckGet(array('locfinder'))) {
            $locator = sm_MapLocationFinder();
        }

        show_window('', generic_MapInit($ym_center, $ym_zoom, $ym_type, $placemarks.$area, $locator, $ym_lang));
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}

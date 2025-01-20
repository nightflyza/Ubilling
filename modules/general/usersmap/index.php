<?php

if (cfr('USERSMAP')) {

    $altercfg = $ubillingConfig->getAlter();

    if ($altercfg['SWYMAP_ENABLED']) {
        set_time_limit(0);

        $ymconf = $ubillingConfig->getYmaps();
        $ym_center = $ymconf['CENTER'];
        $ym_zoom = $ymconf['ZOOM'];
        $ym_type = $ymconf['TYPE'];
        $ym_lang = $ymconf['LANG'];
        $area = '';
        $locator = '';
        $searchPrefill = '';

        //wysiwyg build map placement
        if (ubRouting::checkPost(array('buildplacing', 'placecoords'))) {
            if (cfr('BUILDS')) {
                zb_AddressChangeBuildGeo(ubRouting::post('buildplacing'), ubRouting::post('placecoords'));
                ubRouting::nav('?module=usersmap&locfinder=true');
            } else {
                show_window(__('Error'), __('Access denied'));
            }
        }



        //collect biulds geolocation data
        $placemarks = um_MapDrawBuilds();

        //setting custom zoom and map center if need to find some build

        if (ubRouting::checkGet('findbuild')) {
            $ym_zoom = $ymconf['FINDING_ZOOM'];
            $ym_center = ubRouting::get('findbuild', 'vf');

            if ($ymconf['FINDING_CIRCLE']) {
                $radius = 30;
                $area = sm_MapAddCircle($ym_center, $radius, __('Search area radius') . ' ' . $radius . ' ' . __('meters'), __('Search area'));
            }
        }


        if (ubRouting::checkGet('locfinder')) {
            $locator = um_MapLocationFinder();
        }

        if (ubRouting::checkGet('placebld')) {
            $allBuildsAddr = zb_AddressGetBuildAllAddress();
            $buildLookupId = ubRouting::get('placebld', 'int');
            $searchPrefill = (isset($allBuildsAddr[$buildLookupId])) ? $allBuildsAddr[$buildLookupId] : '';
        }

        //render map container
        um_ShowMapContainer();
        show_window('', generic_MapInit($ym_center, $ym_zoom, $ym_type, $area . $placemarks, $locator, $ym_lang, 'ubmap', $searchPrefill));
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}

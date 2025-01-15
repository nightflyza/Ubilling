<?php

/*
 * Switches coverage map
 */
if (ubRouting::get('action')  == 'switchescoverage') {
    $ymconf = $ubillingConfig->getYmaps();
    $ym_center = $ymconf['CENTER'];
    $ym_zoom = $ymconf['ZOOM'];
    $ym_type = $ymconf['TYPE'];
    $ym_lang = $ymconf['LANG'];
    $area = '';
    if (ubRouting::checkGet('param')) {
        $mapDimensions = explode('x', ubRouting::get('param','safe'));
    } else {
        $mapDimensions[0] = '1000';
        $mapDimensions[1] = '800';
    }
    $switchesCoverage = sm_MapDrawSwitchesCoverage();
    $coverageSwMap = wf_tag('div', false, '', 'id="ubmap" style="width: ' . $mapDimensions[0] . 'px; height:' . $mapDimensions[1] . 'px;"');
    $coverageSwMap .= wf_tag('div', true);
    $coverageSwMap .= generic_MapInit($ym_center, $ym_zoom, $ym_type, $area . $switchesCoverage, '', $ym_lang);
    die($coverageSwMap);
}
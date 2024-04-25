<?php

/*
 * Switches coverage map
 */
if (ubRouting::get('action')  == 'switchescoverage') {
    $ymconf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
    $ym_center = $ymconf['CENTER'];
    $ym_zoom = $ymconf['ZOOM'];
    $ym_type = $ymconf['TYPE'];
    $ym_lang = $ymconf['LANG'];
    $area = '';
    if (wf_CheckGet(array('param'))) {
        $mapDimensions = explode('x', $_GET['param']);
    } else {
        $mapDimensions[0] = '1000';
        $mapDimensions[1] = '800';
    }
    $switchesCoverage = sm_MapDrawSwitchesCoverage();
    $coverageSwMap = wf_tag('div', false, '', 'id="ubmap" style="width: ' . $mapDimensions[0] . 'px; height:' . $mapDimensions[1] . 'px;"');
    $coverageSwMap .= wf_tag('div', true);
    $coverageSwMap .= sm_MapInitBasic($ym_center, $ym_zoom, $ym_type, $area . $switchesCoverage, '', $ym_lang);
    die($coverageSwMap);
}
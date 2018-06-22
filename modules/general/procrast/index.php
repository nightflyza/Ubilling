<?php

if (cfr('PROCRAST')) {
    if (wf_CheckGet(array('run'))) {
        $application = vf($_GET['run']);
        switch ($application) {
            case 'tetris':
                $jsTetris = file_get_contents('modules/jsc/jstetris/tetris.html');
                $jsTetris = str_replace('START_LABEL', __('Press space to play'), $jsTetris);
                $jsTetris = str_replace('SCORE_LABEL', __('score'), $jsTetris);
                $jsTetris = str_replace('ROWS_LABEL', __('rows'), $jsTetris);
                show_window(__('Tetris'), $jsTetris);
                break;
               case '2048':
                $jsCode = file_get_contents('modules/jsc/2048/2048.html');
                show_window(__('2048'), $jsCode);
                break;
        }
        show_window('', wf_BackLink('?module=procrast'));
    } else {
        $applicationsList = '';
        $applicationArr = array(
            'tetris' => __('Tetris'),
            '2048' => __('2048')
        );

        if (!empty($applicationArr)) {
            foreach ($applicationArr as $io => $each) {
                $applicationsList.=wf_Link('?module=procrast&run=' . $io, wf_img_sized('skins/gamepad.png', '', '16', '16') . ' ' . $each, false, 'ubButton') . ' ';
            }
        }
        show_window(__('Procrastination helper'), $applicationsList);
    }
} else {
    show_error(__('Access denied'));
}
?>
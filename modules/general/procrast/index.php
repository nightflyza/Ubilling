<?php

if (cfr('PROCRAST')) {
    $jsTetris = file_get_contents('modules/jsc/jstetris/tetris.html');
    $jsTetris = str_replace('START_LABEL', __('Press space to play'), $jsTetris);
    $jsTetris = str_replace('SCORE_LABEL', __('score'), $jsTetris);
    $jsTetris = str_replace('ROWS_LABEL', __('rows'), $jsTetris);
    show_window(__('Procrastination helper'), $jsTetris);
} else {
    show_error(__('Access denied'));
}
?>
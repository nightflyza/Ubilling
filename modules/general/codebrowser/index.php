<?php
if (cfr('SQLCONSOLE')) {
    $cbrowser = new UBCodeBrowser();
    show_window('', $cbrowser->renderControls());

    $renderScope = ubRouting::get($cbrowser::ROUTE_SCOPE, 'gigasafe');
    if (empty($renderScope)) {
        $renderScope = $cbrowser::SCOPE_FUNC;
    }
    switch ($renderScope) {
        case $cbrowser::SCOPE_FUNC;
            show_window(__('Ubilling functions dictionary'), $cbrowser->renderFuncsList());
            break;
        case $cbrowser::SCOPE_FUNC_DESC:
            $funcName = ubRouting::get($cbrowser::ROUTE_FUNC_NAME, 'safe');
            show_window(__('Function description') . ': ' . $funcName . ' ', $cbrowser->renderFuncDescription($funcName));
            break;
        case $cbrowser::SCOPE_CLASSES;
            show_window(__('Ubilling classes dictionary'), $cbrowser->renderClassesList());
            break;
    }
} else {
    show_error(__('Access denied'));
}

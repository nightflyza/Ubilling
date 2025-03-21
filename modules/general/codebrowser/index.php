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
        case $cbrowser::SCOPE_CLASS_DESC:
            $className = ubRouting::get($cbrowser::ROUTE_CLASS_NAME, 'safe');
            $methodName = ubRouting::get($cbrowser::ROUTE_METHOD_NAME, 'safe');
            show_window(__('Class') . ': ' . $className . ' ' . __('method') . ' ' . $methodName . ' ', $cbrowser->renderMethodDescription($className, $methodName));
            break;
        case $cbrowser::SCOPE_PHP_FUNC:
            show_window(__('PHP built in functions dictionary'), $cbrowser->renderBuiltInFuncsList());
            break;
        case $cbrowser::SCOPE_PHP_CLASSES:
            show_window(__('PHP built in classes directory'), $cbrowser->renderBuiltInClassesList());
            break;
    }
} else {
    show_error(__('Access denied'));
}

if (isset($alter_conf['WATCHDOG_ENABLED'])) {
if ($alter_conf['WATCHDOG_ENABLED']) {
$taskbar.=build_task('WATCHDOG','?module=watchdog','watchdog.jpg',__('Watchdog'));
}
}
if (isset($alter_conf['WATCHDOG_ENABLED'])) {
if ($alter_conf['WATCHDOG_ENABLED']) {
$taskbar.=build_task('WATCHDOG','?module=tsmsqueue','tsmsqueue.png',__('SMS in queue'));
}
}
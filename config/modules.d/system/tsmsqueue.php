if (isset($alter_conf['SENDDOG_ENABLED'])) {
if ($alter_conf['SENDDOG_ENABLED']) {
$taskbar.=build_task('SENDDOG','?module=tsmsqueue','messagesqueue.png',__('Messages queue'));
}
}
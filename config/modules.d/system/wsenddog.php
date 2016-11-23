if (isset($alter_conf['SENDDOG_ENABLED'])) {
if ($alter_conf['SENDDOG_ENABLED']) {
$taskbar.=build_task('SENDDOG','?module=senddog','senddog.jpg',__('SendDog'));
}
}
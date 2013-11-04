if (isset($alter_conf['DSHAPER_ENABLED'])) {
if ($alter_conf['DSHAPER_ENABLED']) {
$taskbar.=build_task('DSHAPER','?module=dshaper','dshaper.jpg',__('Dynamic shaper'));
}
}
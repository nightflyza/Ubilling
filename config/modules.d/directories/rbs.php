if(isset($alter_conf['RBS_ENABLED'])) {
 if ( $alter_conf['RBS_ENABLED'] ) {
  $taskbar .= build_task('RBS','?module=rbs','rbs.png',__('Registry of banned sites'));
 }
}
function taskbar_load($dir) {
global $taskbar;
$path=CONFIG_PATH.'modules.d/'.$dir.'/';
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
$allmodules=rcms_scandir($path);
$result='';
if (!empty ($allmodules)) {
    foreach ($allmodules as $eachmodule) {
        $result.=file_get_contents($path.$eachmodule);
       }
}
eval($result);
}

$taskbar.='<p><h3><u>'.__('Internet users').'</u></h3></p>';
$taskbar.=taskbar_load('iusers');
$taskbar.='<p><h3><u>'.__('Directories').'</u></h3></p>';
$taskbar.=taskbar_load('directories');
$taskbar.='<p><h3><u>'.__('Reports').'</u></h3></p>';
$taskbar.=taskbar_load('reports');
$taskbar.='<p><h3><u>'.__('System').'</u></h3></p>';
$taskbar.=taskbar_load('system');







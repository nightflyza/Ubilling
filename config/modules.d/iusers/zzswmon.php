/*
if ($alter_conf['TB_SWITCHMON']) {
$dead_raw=zb_StorageGet('SWDEAD');
$deadarr=array();
$content='<a href="?module=switches&forcereping=true" target="refrsw"><img src="skins/refresh.gif" border="0" title="'.__('Force ping').'"></a><iframe name="refrsw" frameborder="0" width="1" height="1" src="about:blank"></iframe><br>';

if ($dead_raw) {
$deadarr=unserialize($dead_raw);
if (!empty($deadarr)) {

$deadcount=sizeof($deadarr);    
foreach ($deadarr as $ip=>$switch) {
    $content.=$ip.' - '.$switch.'<br>';
}

$taskbar.=wf_modal(__('Dead switches').': '.$deadcount, __('Dead switches'), $content, 'ubButton', '500', '400');
} else {
   $content.=__('Switches are okay, everything is fine - I guarantee');
   $taskbar.=wf_modal(__('All switches alive'), __('All switches alive'), $content, 'ubButton', '500', '400');
}

} else {
   $content.=__('Switches are okay, everything is fine - I guarantee');
   $taskbar.=wf_modal(__('All switches alive'), __('All switches alive'), $content, 'ubButton', '500', '400');
}


}
*/
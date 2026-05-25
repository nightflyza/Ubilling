<?php


$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

if ($us_config['ZL_ENABLED']) {
    $zl_options=$us_config['ZL_OPTIONS'];
    if (!empty ($zl_options)) {
    $zl_options=explode(',',$zl_options);
    
    
    $rows='';
    if (!empty ($zl_options)) {
        foreach ($zl_options as $eachlink) {
            $ldata=explode('|', $eachlink);
            $icon=$ldata[0];
            $url=$ldata[1];
            $title=$ldata[2];
      
            $cells=  wf_TableCell(wf_Link($url, wf_img($icon)));
            $cells.= wf_TableCell(wf_tag('h3').wf_Link($url, $title).wf_tag('h3',true));
            $rows.=wf_TableRow($cells);
        }
    }
    $result=wf_TableBody($rows, '', 0);
    
    show_window(__('Downloads'),$result);
    }

} else {
     show_window(__('Sorry'),__('Unfortunately downloads are now disabled'));
}

?>

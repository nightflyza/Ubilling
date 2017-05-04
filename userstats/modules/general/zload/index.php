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
      
            $cells=  la_TableCell(la_Link($url, la_img($icon)));
            $cells.= la_TableCell(la_tag('h3').la_Link($url, $title).la_tag('h3',true));
            $rows.=la_TableRow($cells);
        }
    }
    $result=la_TableBody($rows, '', 0);
    
    show_window(__('Downloads'),$result);
    }

} else {
     show_window(__('Sorry'),__('Unfortunately downloads are now disabled'));
}

?>

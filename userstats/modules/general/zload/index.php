<?php


$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();

if ($us_config['ZL_ENABLED']) {
    $zl_options=$us_config['ZL_OPTIONS'];
    if (!empty ($zl_options)) {
    $zl_options=explode(',',$zl_options);
    
    $result='<table  border="0">';
    if (!empty ($zl_options)) {
        foreach ($zl_options as $eachlink) {
            $ldata=explode('|', $eachlink);
            $icon=$ldata[0];
            $url=$ldata[1];
            $title=$ldata[2];
            
            $result.='
                <tr >
                 <td><a href="'.$url.'"><img src="'.$icon.'" border="0"></td>
                 <td><h3><a href="'.$url.'">'.$title.'</a></h3></td>
                </tr>
                ';
        }
    }
    $result.='</table>';
    show_window(__('Downloads'),$result);
    }

} else {
     show_window(__('Sorry'),__('Unfortunately downloads is now disabled'));
}

?>

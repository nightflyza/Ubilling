<?php

$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();


function zbs_AnnouncementsShow() {
    $query="SELECT * from `zbsannouncements` WHERE `public`='1' ORDER by `id` DESC";
    $all=  simple_queryall($query);
    $result='';
    if (!empty($all)) {
        foreach ($all as $io=>$each) {
            $result.=la_tag('h3', false, 'row1', '').$each['title'].'&nbsp;'.  la_tag('h3',true);
            $result.=la_delimiter();
            if ($each['type']=='text') {
                $eachtext=  strip_tags($each['text']);
                $result.=   nl2br($eachtext);
            }
            
            if ($each['type']=='html') {
                $result.=$each['text'];
            }
            $result.=la_delimiter();
            
        }
    } else {
        show_window(__('Sorry'), __('Do not have any announcements.'));
    }
    
    show_window('',$result);
}

if ($us_config['AN_ENABLED']) {
zbs_AnnouncementsShow();
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}

?>
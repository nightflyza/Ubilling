<?php
if(cfr('TAGS')) {

    function fn_get_all_tags() {
    $query='SELECT DISTINCT `tagid` from `tags` ORDER BY `tagid` ASC';
    $alltags=simple_queryall($query);
   return ($alltags);
}

function fn_get_tag_power($tag) {
    $tag=mysql_real_escape_string($tag);
    $query='SELECT COUNT(`tagid`) FROM `tags` where `tagid`="'.$tag.'"';
    $result=simple_query($query);
    return($result['COUNT(`tagid`)']);
}


function fn_get_all_tagnames() {
    $query="SELECT `id`,`tagname` from `tagtypes`";
    $result=array();
    $alltags=simple_queryall($query);
    if (!empty ($alltags)) {
        foreach ($alltags as $io=>$eachtag) {
            $result[$eachtag['id']]=$eachtag['tagname'];
        }

    }
    return($result);
}


function fn_show_tagcloud () {
$alltags=fn_get_all_tags();
$allnames=fn_get_all_tagnames();

$result='<center>';
if (!empty ($alltags)) {
    foreach ($alltags as $key=>$eachtag) {
       $power=fn_get_tag_power($eachtag['tagid']);
       $fsize=$power/2;
       if (isset($allnames[$eachtag['tagid']])) {
       $result.='<font size="'.$fsize.'">
           <a href="?module=tagcloud&tagid='.$eachtag['tagid'].'">'.$allnames[$eachtag['tagid']].'<sup>'.$power.'</sup></a>
           </font> ';
       }
    }
 
 
 }
 $result.='</center>';
 show_window(__('Tags'),$result);

}

function fn_get_taggedusers($tagid) {
    $alltagnames=fn_get_all_tagnames();
    $alladdrz=zb_AddressGetFulladdresslist();
    $allrealnames=zb_UserGetAllRealnames();
    $query="SELECT DISTINCT `login` from `tags` where `tagid`='".$tagid."'";
    $allusers=simple_queryall($query);
    $userarr=array();
    if (!empty ($allusers)) {
        foreach ($allusers as $io=>$eachuser) {
            $userarr[]=$eachuser['login'];
        }
        
    }
    $result=web_UserArrayShower($userarr);
    show_window($alltagnames[$tagid], $result);
}


fn_show_tagcloud();
if (isset($_GET['tagid'])) {
    $tagid=vf($_GET['tagid'],3);
    fn_get_taggedusers($tagid);
    }
        
}
?>

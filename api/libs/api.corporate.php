<?php

 /*
  * Corporate users API
 */

function cu_GetAllLinkedUsers() {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $linkcfid=$alterconf['USER_LINKING_CFID'];
    $query="SELECT `login`,`content` from `cfitems` WHERE `typeid`='".$linkcfid."' AND `content` !=''";
    $allusers=simple_queryall($query);
    $result=array();
    if (!empty ($allusers)) {
        foreach ($allusers as $io=>$eachuser) {
            $result[$eachuser['login']]=$eachuser['content'];
        }
    }
    return ($result);
}

function cu_GetParentUserLogin($param) {
    $param=mysql_real_escape_string($param);
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $linkfield=$alterconf['USER_LINKING_FIELD'];
    $query="SELECT `login` from `users` WHERE `".$linkfield."`='".$param."'";
    $result=simple_query($query);
    if (!empty ($result)) {
        $result=$result['login'];
    }
    return ($result);
}

function cu_GetAllChildUsers($param) {
    $param=mysql_real_escape_string($param);
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $linkcfid=$alterconf['USER_LINKING_CFID'];
    $query="SELECT `login` from `cfitems` WHERE `typeid`='".$linkcfid."' AND `content`='".$param."' ";
    $result=array();
    $alllinks=simple_queryall($query);
    if (!empty ($alllinks)) {
        foreach ($alllinks as $io=>$eachlink) {
            $result[]=$eachlink['login'];
        }
    }
    return ($result);
}

function cu_GetAllParentUsers() {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $linkfield=$alterconf['USER_LINKING_FIELD'];
    $linkcfid=$alterconf['USER_LINKING_CFID'];
    $result=array();
    $query_cfs="SELECT DISTINCT `content` FROM `cfitems` WHERE `typeid`='".$linkcfid."'";
    $allcfs=simple_queryall($query_cfs);
    if (!empty ($allcfs)) {
        foreach ($allcfs as $io=>$eachcf) {
            $query_user="SELECT `login` from `users` WHERE `".$linkfield."`='".$eachcf['content']."' ";
            $userlogin=simple_query($query_user);
            $result[$userlogin['login']]=$eachcf['content'];
        }
    }
    return ($result);
}  

function cu_IsChild($login) {
    $login=mysql_real_escape_string($login);
    $allchilds=cu_GetAllLinkedUsers();
    if (isset($allchilds[$login])) {
        return (true);
    } else {
        return (false);
    }
 }

 function cu_IsParent($login) {
     $login=mysql_real_escape_string($login);
     $allparents=cu_GetAllParentUsers();
     if (isset($allparents[$login])) {
        return (true);
    } else {
        return (false);
    }
 }
        
?>
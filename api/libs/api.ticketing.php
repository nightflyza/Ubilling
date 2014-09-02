<?php

function zb_TicketsGetAll(){
    $query="SELECT * from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL ORDER BY `date` DESC";
    $result=simple_queryall($query);
    return ($result);
}

function zb_TicketsGetCount(){
    $query="SELECT COUNT(`id`) from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL";
    $result=simple_query($query);
    $result=$result['COUNT(`id`)'];
    return ($result);
}
function zb_TicketsGetLimited($from,$to){
    $query="SELECT * from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL  ORDER BY `date` DESC LIMIT ".$from.",".$to.";";
    $result=simple_queryall($query);
    return ($result);
}


function zb_TicketsGetAllNewCount(){
    $query="SELECT COUNT(`id`) from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL AND `status`='0' ORDER BY `date` DESC";
    $result=simple_query($query);
    $result=$result['COUNT(`id`)'];
    return ($result);
}

function zb_TicketsGetAllByUser($login){
    $login=vf($login);
    $query="SELECT `id`,`date`,`status` from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL AND `from`='".$login."' ORDER BY `date` DESC";
    $result=simple_queryall($query);
    return ($result);
}

function zb_TicketGetData($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="SELECT * from `ticketing` WHERE `id`='".$ticketid."'";
    $result=simple_query($query);
    return ($result);
}

function zb_TicketGetReplies($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="SELECT * from `ticketing` WHERE `replyid`='".$ticketid."' ORDER by `id` ASC";
    $result=simple_queryall($query);
    return ($result);
}

function zb_TicketDelete($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="DELETE FROM `ticketing` WHERE `id`='".$ticketid."'";
    nr_query($query);
    log_register("TICKET DELETE [".$ticketid."]");
}

function zb_TicketDeleteReplies($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="DELETE FROM `ticketing` WHERE `replyid`='".$ticketid."'";
    nr_query($query);
    log_register("TICKET REPLIES DELETE [".$ticketid."]");
}


function zb_TicketDeleteReply($replyid) {
    $replyid=vf($replyid,3);
    $query="DELETE FROM `ticketing` WHERE `id`='".$replyid."'";
    nr_query($query);
    log_register("TICKET REPLY DELETE [".$replyid."]");
}


function zb_TicketUpdateReply($replyid,$newtext) {
    $replyid=vf($replyid,3);
    $newtext=strip_tags($newtext);
    simple_update_field('ticketing', 'text', $newtext, "WHERE `id`='".$replyid."'");
    log_register("TICKET REPLY EDIT [".$replyid."]");
}



function zb_TicketCreate($from,$to,$text,$replyto='NULL',$admin='') {
    $from=mysql_real_escape_string($from);
    $to=mysql_real_escape_string($to);
    $admin=mysql_real_escape_string($admin);
    $text=mysql_real_escape_string(strip_tags($text));
    $date=curdatetime();
    $replyto=vf($replyto);
    $query="
        INSERT INTO `ticketing` (
    `id` ,
    `date` ,
    `replyid` ,
    `status` ,
    `from` ,
    `to` ,
    `text`,
    `admin`
        )
    VALUES (
    NULL ,
    '".$date."',
    ".$replyto.",
    '0',
    '".$from."',
    '".$to."',
    '".$text."',
    '".$admin."'
           );
        ";
    nr_query($query);
    log_register("TICKET CREATE (".$to.")");
}

function zb_TicketSetDone($ticketid) {
    $ticketid=vf($ticketid);
    simple_update_field('ticketing', 'status', '1', "WHERE `id`='".$ticketid."'");
    log_register("TICKET CLOSE [".$ticketid."]");
}


function zb_TicketSetUnDone($ticketid) {
    $ticketid=vf($ticketid);
    simple_update_field('ticketing', 'status', '0', "WHERE `id`='".$ticketid."'");
    log_register("TICKET OPEN [".$ticketid."]");
}



?>

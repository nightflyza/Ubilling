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
    log_register("TICKET DELETE ".$ticketid);
}

function zb_TicketDeleteReplies($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="DELETE FROM `ticketing` WHERE `replyid`='".$ticketid."'";
    nr_query($query);
    log_register("TICKET REPLIES DELETE ".$ticketid);
}


function zb_TicketDeleteReply($replyid) {
    $replyid=vf($replyid,3);
    $query="DELETE FROM `ticketing` WHERE `id`='".$replyid."'";
    nr_query($query);
    log_register("TICKET REPLY DELETE ".$replyid);
}


function zb_TicketUpdateReply($replyid,$newtext) {
    $replyid=vf($replyid,3);
    $newtext=strip_tags($newtext);
    simple_update_field('ticketing', 'text', $newtext, "WHERE `id`='".$replyid."'");
    log_register("TICKET REPLY EDIT ".$replyid);
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
    log_register("TICKET CREATE ".$to);
}

function zb_TicketSetDone($ticketid) {
    $ticketid=vf($ticketid);
    simple_update_field('ticketing', 'status', '1', "WHERE `id`='".$ticketid."'");
    log_register("TICKET CLOSE ".$ticketid);
}


function zb_TicketSetUnDone($ticketid) {
    $ticketid=vf($ticketid);
    simple_update_field('ticketing', 'status', '0', "WHERE `id`='".$ticketid."'");
    log_register("TICKET OPEN ".$ticketid);
}

//
//  signup reporting API
//

function zb_SigreqsGetAllNewCount(){
    $query="SELECT COUNT(`id`) from `sigreq` WHERE `state`='0'";
    $result=simple_query($query);
    $result=$result['COUNT(`id`)'];
    return ($result);
}

    function zb_SigreqsGetAllRequests() {
        $query="SELECT * from `sigreq` ORDER BY `date` DESC";
        $allreqs=  simple_queryall($query);
        $result=array();
        
        if (!empty($allreqs)) {
            $result=$allreqs;
        }
        
        return ($result);
    }
    
    
    function zb_SigreqsGetReqData($reqid) {
        $requid=vf($reqid,3);
        $query="SELECT * from `sigreq` WHERE `id`='".$reqid."'";
        $result=simple_query($query);
        return($result);
    }
    
    function zb_SigreqsSetDone($reqid) {
        $requid=vf($reqid,3);
        simple_update_field('sigreq', 'state', '1', "WHERE `id`='".$reqid."'");
        log_register('SIGREQ DONE '.$reqid);
    }
    
    function zb_SigreqsSetUnDone($reqid) {
        $requid=vf($reqid,3);
        simple_update_field('sigreq', 'state', '0', "WHERE `id`='".$reqid."'");
        log_register('SIGREQ UNDONE '.$reqid);
    }
    
    function zb_SigreqsDeleteReq($reqid) {
        $requid=vf($reqid,3);
        $query="DELETE from `sigreq` WHERE `id`='".$reqid."'";
        nr_query($query);
        log_register('SIGREQ DELETE '.$reqid);
    }
    
        
    function web_SigreqsShowReq($reqid) {
        $requid=vf($reqid,3);
        $reqdata=zb_SigreqsGetReqData($reqid);
        
        $cells=wf_TableCell(__('Date'));
        $cells.=wf_TableCell($reqdata['date']);
        $rows=  wf_TableRow($cells, 'row3');
        
        $whoislink='http://whois.domaintools.com/'.$reqdata['ip'];
        $iplookup=  wf_Link($whoislink, $reqdata['ip'], false, '');
        
        $cells=wf_TableCell(__('IP'));
        $cells.=wf_TableCell($iplookup);
        $rows.=  wf_TableRow($cells, 'row3');
        
        
        if (empty($reqdata['apt'])) {
            $apt=0;
        } else {
            $apt=$reqdata['apt'];
        }
        $cells=wf_TableCell(__('Full address'));
        $cells.=wf_TableCell($reqdata['street'].' '.$reqdata['build'].'/'.$apt);
        $rows.=  wf_TableRow($cells, 'row3');
        
        $cells=wf_TableCell(__('Real Name'));
        $cells.=wf_TableCell($reqdata['realname']);
        $rows.=  wf_TableRow($cells, 'row3');
        
        $cells=wf_TableCell(__('Phone'));
        $cells.=wf_TableCell($reqdata['phone']);
        $rows.=  wf_TableRow($cells, 'row3');
        
        $cells=wf_TableCell(__('Service'));
        $cells.=wf_TableCell($reqdata['service']);
        $rows.=wf_TableRow($cells, 'row3');
        
        $cells=wf_TableCell(__('Processed'));
        $cells.=wf_TableCell(web_bool_led($reqdata['state']));
        $rows.=wf_TableRow($cells, 'row3');
        
        $cells=wf_TableCell(__('Notes'));
        $cells.=wf_TableCell($reqdata['notes']);
        $rows.=wf_TableRow($cells, 'row3');
        
        $result=  wf_TableBody($rows, '100%', '0','glamour');
        
        
        $actlinks= wf_Link('?module=sigreq', __('Back'), false, 'ubButton');
        if ($reqdata['state']==0) {
           $actlinks.=wf_Link('?module=sigreq&reqdone='.$reqid, __('Close'), false, 'ubButton');
        } else {
           $actlinks.=wf_Link('?module=sigreq&requndone='.$reqid, __('Open'), false, 'ubButton');
        }
        
        $deletelink=' '.  wf_JSAlert("?module=sigreq&deletereq=".$reqid, web_delete_icon(), 'Are you serious');
        
        show_window(__('Signup request').': '.$reqid.$deletelink,$result);
        show_window('', $actlinks);
    }
    
    
    function web_SigreqsShowAll() {
        $allreqs=  zb_SigreqsGetAllRequests();
        $result='';
        
        $tablecells=  wf_TableCell(__('ID'));
        $tablecells.= wf_TableCell(__('Date'));
        $tablecells.= wf_TableCell(__('Full address'));
        $tablecells.= wf_TableCell(__('Real Name'));
        $tablecells.= wf_TableCell(__('Processed'));
        $tablecells.= wf_TableCell(__('Actions'));
        $tablerows=  wf_TableRow($tablecells, 'row1');
        
        if (!empty($allreqs)) {
            foreach ($allreqs as $io=>$eachreq) {
                
                $tablecells=  wf_TableCell($eachreq['id']);
                $tablecells.= wf_TableCell($eachreq['date']);
                if (empty($eachreq['apt'])) {
                    $apt=0;
                } else {
                    $apt=$eachreq['apt'];
                }
                $reqaddr=$eachreq['street'].' '.$eachreq['build'].'/'.$apt;
                $tablecells.= wf_TableCell($reqaddr);
                $tablecells.= wf_TableCell($eachreq['realname']);
                $tablecells.= wf_TableCell(web_bool_led($eachreq['state']));
                $actlinks=  wf_Link('?module=sigreq&showreq='.$eachreq['id'], 'Show', true, 'ubButton');
                $tablecells.= wf_TableCell($actlinks);
                $tablerows.=  wf_TableRow($tablecells, 'row3');
                
            }
            
            
            
        }
        
        $result=  wf_TableBody($tablerows, '100%','0','sortable');
        
        show_window(__('Available signup requests'),$result);
    }
    



?>

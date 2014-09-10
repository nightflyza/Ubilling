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

function web_TicketsShow() {
      global $ubillingConfig;
      $alterconf=$ubillingConfig->getAlter();
      //pagination section
      $totalcount=zb_TicketsGetCount();
      $perpage=$alterconf['TICKETS_PERPAGE'];
      
      if (!isset ($_GET['page'])) {
          $current_page=1;
      } else {
          $current_page=vf($_GET['page'],3);
      }
      
      if ($totalcount>$perpage) {
          $paginator=wf_pagination($totalcount, $perpage, $current_page, "?module=ticketing",'ubButton');
          $alltickets=zb_TicketsGetLimited($perpage*($current_page-1),$perpage);
      } else {
          $paginator='';
          $alltickets=zb_TicketsGetAll();
      }
      
    
      $tablecells=wf_TableCell(__('ID'));
      $tablecells.=wf_TableCell(__('Date'));
      $tablecells.=wf_TableCell(__('From'));
      $tablecells.=wf_TableCell(__('Real Name'));
      $tablecells.=wf_TableCell(__('Full address'));
      $tablecells.=wf_TableCell(__('IP'));
      $tablecells.=wf_TableCell(__('Tariff'));
      $tablecells.=wf_TableCell(__('Balance'));
      $tablecells.=wf_TableCell(__('Credit'));
      $tablecells.=wf_TableCell(__('Processed'));
      $tablecells.=wf_TableCell(__('Actions'));
      $tablerows=wf_TableRow($tablecells, 'row1');
      
      
      if (!empty ($alltickets)) {
          $allrealnames=zb_UserGetAllRealnames();
          $alladdress=zb_AddressGetFulladdresslist();
          $alltariffs=zb_TariffsGetAllUsers();
          $allcash=zb_CashGetAllUsers();
          $allcredits=zb_CreditGetAllUsers();
          $alluserips=  zb_UserGetAllIPs();
          
      foreach ($alltickets as $io=>$eachticket) {
  
      $tablecells=wf_TableCell($eachticket['id']);
      $tablecells.=wf_TableCell($eachticket['date']);
      $fromlink=wf_Link('?module=userprofile&username='.$eachticket['from'], web_profile_icon().' '.$eachticket['from']);
      $tablecells.=wf_TableCell($fromlink);
      $tablecells.=wf_TableCell(@$allrealnames[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$alladdress[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$alluserips[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$alltariffs[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$allcash[$eachticket['from']]);
      $tablecells.=wf_TableCell(@$allcredits[$eachticket['from']]);
      $tablecells.=wf_TableCell(web_bool_led($eachticket['status']), '', '', 'sorttable_customkey="'.$eachticket['status'].'"');
      $actionlink=wf_Link('?module=ticketing&showticket='.$eachticket['id'], 'Show', false, 'ubButton');
      $tablecells.=wf_TableCell($actionlink);
      $tablerows.=wf_TableRow($tablecells, 'row3');
      
          }
      }
      $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
      $result.=$paginator;
      
      
      return ($result);
  }
  
  function web_TicketReplyForm($ticketid) {
      $ticketid=vf($ticketid);
      $ticketdata=zb_TicketGetData($ticketid);
      $ticketstate=$ticketdata['status'];
      if (!$ticketstate) {
      $replyinputs=wf_HiddenInput('postreply', $ticketid);
      $replyinputs.=wf_TextArea('replytext', '', '', true, '60x10');
      $replyinputs.=wf_Submit('Reply');
      $replyform=wf_Form('', 'POST', $replyinputs, 'glamour');
      } else {
          $replyform=__('Ticket is closed');
      }
      return ($replyform);
  }
  
  
    function web_TicketReplyEditForm($replyid) {
      $replyid=vf($replyid);
      $ticketdata=zb_TicketGetData($replyid);
      $replytext=$ticketdata['text'];
     
      $inputs=wf_HiddenInput('editreply', $replyid);
      $inputs.=wf_TextArea('editreplytext', '', $replytext, true, '60x10');
      $inputs.=wf_Submit('Save');
      $form=wf_Form('', 'POST', $inputs, 'glamour');
      
      return ($form);
  }
  
  function web_TicketDialogue($ticketid) {
      $ticketid=vf($ticketid);
      $ticketdata=zb_TicketGetData($ticketid);
      $ticketreplies=zb_TicketGetReplies($ticketid);
      $result='<p align="right">'. wf_Link('?module=ticketing', 'Back to tickets list', true, 'ubButton').'</p>';
      if (!empty ($ticketdata)) {
          $alladdress=zb_AddressGetFulladdresslist();
          $allrealnames=zb_UserGetAllRealnames();
          $alltariffs=zb_TariffsGetAllUsers();
          $allcash=zb_CashGetAllUsers();
          $allcredits=zb_CreditGetAllUsers();
          $alluserips=  zb_UserGetAllIPs();
          
          if ($ticketdata['status']) {
              $actionlink=wf_Link('?module=ticketing&openticket='.$ticketdata['id'], 'Open', false, 'ubButton');
          } else {
              $actionlink=wf_Link('?module=ticketing&closeticket='.$ticketdata['id'], 'Close', false, 'ubButton');
          }
          
            
          $tablecells=wf_TableCell(__('ID'));
          $tablecells.=wf_TableCell(__('Date'));
          $tablecells.=wf_TableCell(__('Login'));
          $tablecells.=wf_TableCell(__('Real Name'));
          $tablecells.=wf_TableCell(__('Full address'));
          $tablecells.=wf_TableCell(__('IP'));
          $tablecells.=wf_TableCell(__('Tariff'));
          $tablecells.=wf_TableCell(__('Balance'));
          $tablecells.=wf_TableCell(__('Credit'));
          $tablecells.=wf_TableCell(__('Processed'));
          $tablerows=wf_TableRow($tablecells, 'row1');
          
          $tablecells=wf_TableCell($ticketdata['id']);
          $tablecells.=wf_TableCell($ticketdata['date']);
          $profilelink=wf_Link('?module=userprofile&username='.$ticketdata['from'], web_profile_icon().' '.$ticketdata['from']);
          $tablecells.=wf_TableCell($profilelink);
          $tablecells.=wf_TableCell(@$allrealnames[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$alladdress[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$alluserips[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$alltariffs[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$allcash[$ticketdata['from']]);
          $tablecells.=wf_TableCell(@$allcredits[$ticketdata['from']]);
          $tablecells.=wf_TableCell(web_bool_led($ticketdata['status']));
          $tablerows.=wf_TableRow($tablecells, 'row3');
          $result.=wf_TableBody($tablerows, '100%', '0');
          
          //
          //ticket body
          // 

          $tickettext=strip_tags($ticketdata['text']);
          $tickettext=  nl2br($tickettext);
          $tablecells=wf_TableCell('', '20%');
          $tablecells.=wf_TableCell($ticketdata['date']);
          $tablerows=wf_TableRow($tablecells, 'row2');
          
           $ticketauthor='<center><b>'.@$allrealnames[$ticketdata['from']].'</b></center>';    
           $ticketavatar='<center><img src="skins/userava.png"></center>';
           $ticketpanel=$ticketauthor.'<br>'.$ticketavatar;
          
          $tablecells=wf_TableCell($ticketpanel);
          $tablecells.=wf_TableCell($tickettext);
          $tablerows.=wf_TableRow($tablecells, 'row3');
          
          $result.=wf_TableBody($tablerows, '100%', '0','glamour');
          $result.=$actionlink;
      }
      
      
      if (!empty ($ticketreplies)) {
          $result.='<h2>'.__('Replies').'</h2>';
          foreach ($ticketreplies as $io=>$eachreply) {
           //reply
          if ($eachreply['admin']) {
              $replyauthor='<center><b>'.$eachreply['admin'].'</b></center>';    
              $replyavatar='<center>'.  gravatar_ShowAdminAvatar($eachreply['admin'], '64').'</center>';
          } else {
              $replyauthor='<center><b>'.@$allrealnames[$eachreply['from']].'</b></center>';    
              $replyavatar='<center><img src="skins/userava.png"></center>';
          }
          
          $replyactions='<center>';
          $replyactions.=  wf_JSAlert('?module=ticketing&showticket='.$ticketdata['id'].'&deletereply='.$eachreply['id'], web_delete_icon(), 'Removing this may lead to irreparable results').' ';
          $replyactions.=  wf_JSAlert('?module=ticketing&showticket='.$ticketdata['id'].'&editreply='.$eachreply['id'], web_edit_icon(), 'Are you serious');
          $replyactions.='</center>';
          
          //
          // reply body 
          //
          
          if (isset($_GET['editreply'])) {
              
              if ($_GET['editreply']==$eachreply['id']) {
                  //is this reply editing?
                  $replytext=  web_TicketReplyEditForm($eachreply['id']);
              } else {
                  //not this ticket edit
                  $replytext=strip_tags($eachreply['text']);
                  
              }
          } else {
              //normal text by default
              $replytext=strip_tags($eachreply['text']);
              $replytext=  nl2br($replytext);
          }
          
          $replypanel=$replyauthor.'<br>'.$replyavatar.'<br>'.$replyactions;
          
              
         
          
          $tablecells=wf_TableCell('', '20%');
          $tablecells.=wf_TableCell($eachreply['date']);
          $tablerows=wf_TableRow($tablecells, 'row2');
          
          $tablecells=wf_TableCell($replypanel);
          $tablecells.=wf_TableCell($replytext);
          $tablerows.=wf_TableRow($tablecells, 'row3');
          
          $result.=wf_TableBody($tablerows, '100%', '0','glamour');
                
          }
          
      }
      
      
      
      //reply form and previous tickets
      $allprevious=zb_TicketsGetAllByUser($ticketdata['from']);
      $previoustickets='';
      if (!empty ($allprevious)) {
          $previoustickets='<h2>'.__('All tickets by this user').'</h2>';
          foreach ($allprevious as $io=>$eachprevious) {
              $tablecells=wf_TableCell($eachprevious['date']);
              $tablecells.=wf_TableCell(web_bool_led($eachprevious['status']));
              $prevaction=wf_Link('?module=ticketing&showticket='.$eachprevious['id'], 'Show', false, 'ubButton');
              $tablecells.=wf_TableCell($prevaction);
              $tablerows=wf_TableRow($tablecells, 'row3');
              $previoustickets.=wf_TableBody($tablerows, '100%','0');
          }
          
      }
      
      $tablecells=wf_TableCell(web_TicketReplyForm($ticketid),'50%','','valign="top"');
      $tablecells.=wf_TableCell($previoustickets,'50%','','valign="top"');
      $tablerows=wf_TableRow($tablecells);
      
      $result.=wf_TableBody($tablerows, '100%', '0','glamour');
      return ($result);
  }

  
 function web_TicketsCalendar() {
        $curyear=  curyear();
        $query="SELECT * from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL AND `date` LIKE '".$curyear."-%' ORDER BY `date` ASC";
        $all= simple_queryall($query);
        $allAddress=  zb_AddressGetFulladdresslistCached();
        $result='';
        $calendarData='';
        if (!empty($all)) {
            
            foreach ($all as $io=>$each)  {
                $timestamp=strtotime($each['date']);
                $date=date("Y, n-1, j",$timestamp);
                $rawTime=date("H:i:s",$timestamp);
               if ($each['status']==0) {
                    $coloring="className : 'undone',";
                } else {
                    $coloring='';
                }
                      $calendarData.="
                      {
                        title: '".$rawTime.' '.@$allAddress[$each['from']]."',
                        url: '?module=ticketing&showticket=".$each['id']."',
                        start: new Date(".$date."),
                        end: new Date(".$date."),
                       ".$coloring."     
                   },
                    ";
            }
            
            
        }
        $result=  wf_FullCalendar($calendarData);
        return ($result);
    }
    
?>

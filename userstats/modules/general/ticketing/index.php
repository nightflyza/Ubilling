<?php
$user_ip=zbs_UserDetectIp('debug');
$user_login=zbs_UserGetLoginByIp($user_ip);
$us_config=zbs_LoadConfig();
if ($us_config['TICKETING_ENABLED']) {
    ///// ticketing API

function zbs_TicketsGetAllMy($mylogin){
    $query="SELECT * from `ticketing` WHERE `from`= '".$mylogin."' AND `replyid` IS NULL ORDER BY `date` DESC";
    $result=simple_queryall($query);
    return ($result);
}

function zbs_MessagesGetAllMy($mylogin){
    $query="SELECT * from `ticketing` WHERE `to`= '".$mylogin."' AND `from`='NULL' AND `status`='1' ORDER BY `date` DESC";
    $result=simple_queryall($query);
    return ($result);
}


function zbs_TicketGetData($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="SELECT * from `ticketing` WHERE `id`='".$ticketid."'";
    $result=simple_query($query);
    return ($result);
}

function zbs_TicketGetReplies($ticketid) {
    $ticketid=vf($ticketid,3);
    $query="SELECT * from `ticketing` WHERE `replyid`='".$ticketid."' ORDER by `id` ASC";
    $result=simple_queryall($query);
    return ($result);
}

function zbs_TicketIsMy($ticketid,$login) {
    $ticketid=vf($ticketid,3);
    $login=mysql_real_escape_string($login);
    $query="SELECT `id` from `ticketing` WHERE `id`='".$ticketid."' AND `from`='".$login."'";
    $result=simple_query($query);
    if (!empty ($result)) {
        return(true);
    } else {
        return(false);
    }
}

function zbs_TicketCreate($from,$to,$text,$replyto='NULL') {
    $from=mysql_real_escape_string($from);
    $to=mysql_real_escape_string($to);
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
    `text`
        )
    VALUES (
    NULL ,
    '".$date."',
    ".$replyto.",
    '0',
    '".$from."',
    ".$to.",
    '".$text."'
          );
        ";
    nr_query($query);
  }
  
    function zbs_TicketCreateForm() {
        $result='
            <form action="" method="POST">
            <textarea cols="60" rows="10" name="newticket"></textarea> <br>
            <input type="submit" value="'.__('Post').'">
            </form>
            ';
        return ($result);
    }
    
      function zbs_TicketReplyForm($ticketid) {
          $ticketid=vf($ticketid);
          $ticketdata=zbs_TicketGetData($ticketid);
          if ($ticketdata['status']==0) {
           $result='
            <form action="" method="POST">
            <textarea cols="60" rows="10" name="replyticket"></textarea> <br>
            <input type="submit" value="'.__('Post').'">
            </form>
            ';
          } else   {
              $result=__('Closed');
          }
        return ($result);
          
    }
    
    function zbs_TicketsShowMy() {
        global $user_login;
        $allmytickets=zbs_TicketsGetAllMy($user_login);
        $result='<table width="100%" border="0">';
        $result.='
                    <tr class="row1">
                    <td>'.__('ID').'</td>
                    <td>'.__('Date').'</td>
                    <td>'.__('Status').'</td>
                    <td>'.__('Actions').'</td>
                    </tr>
                    ';
        if (!empty ($allmytickets)) {
            foreach ($allmytickets as $io=>$eachticket) {
                if ($eachticket['status']) {
                    $ticketstatus=__('Closed');
                } else {
                    $ticketstatus=__('Open');
                }
                $result.='
                    <tr class="row2">
                    <td>'.$eachticket['id'].'</td>
                    <td>'.$eachticket['date'].'</td>
                    <td>'.$ticketstatus.'</td>
                    <td><a href="?module=ticketing&showticket='.$eachticket['id'].'">'.__('View').'</a></td>
                    </tr>
                    ';
            }
        }
        $result.='</table>';
        return ($result);
    }
    
    function zbs_TicketShowWithReplies($ticketid) {
        $ticketid=vf($ticketid,3);
        $ticketdata=zbs_TicketGetData($ticketid);
        $ticketreplies=zbs_TicketGetReplies($ticketid);
        $result='<table width="100%" border="0">';
        if (!empty ($ticketdata)) {
            $result.='
                <tr class="row1">
                <td>'.__('Date').'</td>
                <td>'.$ticketdata['date'].'</td>
                </tr>
                <tr class="row2">
                <td></td>
                <td>'.$ticketdata['text'].'</td>
                </tr>
                ';
            }
            if (!empty ($ticketreplies)) {
                foreach ($ticketreplies as $io=>$eachreply) {
                    $result.='
                        <tr class="row1">
                        <td>'.__('Date').'</td>
                        <td>'.$eachreply['date'].'</td>
                        </tr>
                        <tr class="row3">
                        <td></td>
                        <td>'.$eachreply['text'].'</td>
                        </tr>
                        
                        ';
                }
            }
            
            $result.='</table>';
             return ($result);
        }
        
        
        
  function zbs_MessagesShowMy() {
      global $user_login;
        $allmymessages=zbs_MessagesGetAllMy($user_login);
        $result='<table width="100%" border="0">';
        $result.='
                    <tr class="row1">
                    <td>'.__('Date').'</td>
                    <td>'.__('Message').'</td>
                    </tr>
                    ';
        if (!empty ($allmymessages)) {
            foreach ($allmymessages as $io=>$eachmessage) {
                $result.='
                    <tr class="row2">
                    <td>'.$eachmessage['date'].'</td>
                    <td>'.$eachmessage['text'].'</td>
                    </tr>
                    ';
            }
        }
        $result.='</table>';
        return ($result);
  }
       
    
    
 //////////////////////
    
    if (!isset($_GET['showticket'])) {
        //mb post new ticket?
       if (isset($_POST['newticket'])) {
           $newtickettext=strip_tags($_POST['newticket']);
           if (!empty ($newtickettext)) {
               zbs_TicketCreate($user_login, 'NULL', $newtickettext);
               rcms_redirect("?module=ticketing");
           }
       }
      //show previous tickets
      show_window(__('Help request'),  zbs_TicketCreateForm());    
      show_window(__('Previous help requests'),zbs_TicketsShowMy());
      show_window(__('Messages from administration'),  zbs_MessagesShowMy());
      
    } else {
        $ticketid=vf($_GET['showticket'],3);
        if (!empty ($ticketid)) {
            //ok thats my ticket
            if (zbs_TicketIsMy($ticketid, $user_login)) {
                //mb post reply?
                if (isset($_POST['replyticket'])) {
                    $replytickettext=strip_tags($_POST['replyticket']);
                    if (!empty ($replytickettext)) {
                    zbs_TicketCreate($user_login, 'NULL', $replytickettext, $ticketid);
                    rcms_redirect("?module=ticketing&showticket=".$ticketid);
                    }
                    
                }
                
                
                //let view it
                show_window(__('Help request').': '.$ticketid,zbs_TicketShowWithReplies($ticketid));
                show_window(__('Reply'),  zbs_TicketReplyForm($ticketid));
                
               
            } else {
                show_window(__('Error'), __('No such ticket'));
            }
        }
        
    }
    
    
    
} else {
     show_window(__('Sorry'),__('Unfortunately helpdesk is now disabled'));
}
?>
